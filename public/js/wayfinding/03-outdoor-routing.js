    /* =========================================================
       OUTDOOR BUILDING-CROSSING AVOIDANCE HELPERS
       Prevents outdoor Dijkstra from using path segments that cut
       through building polygons.
    ========================================================= */

    function pointInRingLngLat(point, ring) {
        if (!Array.isArray(ring) || ring.length < 3) return false;

        const x = Number(point[0]);
        const y = Number(point[1]);
        let inside = false;

        for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
            const xi = Number(ring[i][0]);
            const yi = Number(ring[i][1]);
            const xj = Number(ring[j][0]);
            const yj = Number(ring[j][1]);

            const intersects =
                ((yi > y) !== (yj > y)) &&
                (x < ((xj - xi) * (y - yi)) / ((yj - yi) || 1e-12) + xi);

            if (intersects) inside = !inside;
        }

        return inside;
    }

    function pointInPolygonLngLat(point, polygonRings) {
        if (!Array.isArray(polygonRings) || !polygonRings.length) return false;

        const outerRing = polygonRings[0];
        if (!pointInRingLngLat(point, outerRing)) return false;

        // holes
        for (let i = 1; i < polygonRings.length; i++) {
            if (pointInRingLngLat(point, polygonRings[i])) return false;
        }

        return true;
    }

    function pointInsideBuildingGeometry(lng, lat, geometry) {
        if (!geometry || !geometry.type || !geometry.coordinates) return false;

        const point = [Number(lng), Number(lat)];

        if (geometry.type === 'Polygon') {
            return pointInPolygonLngLat(point, geometry.coordinates);
        }

        if (geometry.type === 'MultiPolygon') {
            return geometry.coordinates.some(poly => pointInPolygonLngLat(point, poly));
        }

        return false;
    }

    function pointInsideAnyBuilding(lng, lat) {
        return (buildingRecords || []).some(building => {
            return pointInsideBuildingGeometry(lng, lat, building.geometry);
        });
    }

    function lerpCoord(a, b, t) {
        return [
            Number(a[0]) + ((Number(b[0]) - Number(a[0])) * t),
            Number(a[1]) + ((Number(b[1]) - Number(a[1])) * t)
        ];
    }

    function segmentCutsThroughBuilding(aCoord, bCoord) {
        /*
        |--------------------------------------------------------------------------
        | Why sample only inner points?
        |--------------------------------------------------------------------------
        | Endpoints can be exactly at or near an entrance. That is allowed.
        | But if the middle of the segment is inside a building polygon, that means
        | the route is cutting through a building footprint, so penalize it.
        |--------------------------------------------------------------------------
        */
        const samplePoints = [
            lerpCoord(aCoord, bCoord, 0.25),
            lerpCoord(aCoord, bCoord, 0.50),
            lerpCoord(aCoord, bCoord, 0.75)
        ];

        return samplePoints.some(coord => pointInsideAnyBuilding(coord[0], coord[1]));
    }


    function addOutdoorEdge(aKey, aLat, aLng, bKey, bLat, bLng, weight, meta = {}) {
        if (!outdoorGraph[aKey]) outdoorGraph[aKey] = [];
        if (!outdoorGraph[bKey]) outdoorGraph[bKey] = [];

        outdoorNodeCoords[aKey] = [aLat, aLng];
        outdoorNodeCoords[bKey] = [bLat, bLng];

        outdoorGraph[aKey].push({
            key: bKey,
            weight,
            meta
        });
        outdoorGraph[bKey].push({
            key: aKey,
            weight,
            meta
        });

        outdoorEdgeMeta[edgeKey(aKey, bKey)] = meta;
    }

    let outdoorRouteWorker = null;
    let outdoorRouteWorkerRevision = 0;
    let outdoorRouteRequestSequence = 0;
    let latestOutdoorRouteRequestId = 0;
    const pendingOutdoorRouteRequests = new Map();

    function stopOutdoorRouteWorker(error = null) {
        outdoorRouteWorker?.terminate();
        outdoorRouteWorker = null;

        pendingOutdoorRouteRequests.forEach(pending => {
            clearTimeout(pending.timeoutId);
            pending.reject(error || Object.assign(new Error('Route worker stopped.'), {
                code: 'ROUTE_WORKER_STOPPED'
            }));
        });
        pendingOutdoorRouteRequests.clear();
    }

    function initializeOutdoorRouteWorker() {
        if (typeof Worker !== 'function') return false;

        stopOutdoorRouteWorker();

        try {
            outdoorRouteWorker = new Worker('/js/wayfinding-route-worker.js');
        } catch (error) {
            outdoorRouteWorker = null;
            return false;
        }

        outdoorRouteWorker.addEventListener('message', event => {
            const message = event.data || {};
            if (!['result', 'error'].includes(message.type)) return;

            const requestId = Number(message.requestId || 0);
            const pending = pendingOutdoorRouteRequests.get(requestId);
            if (!pending) return;

            pendingOutdoorRouteRequests.delete(requestId);
            clearTimeout(pending.timeoutId);

            if (pending.latestOnly && requestId !== latestOutdoorRouteRequestId) {
                pending.reject(Object.assign(new Error('A newer destination replaced this route.'), {
                    code: 'STALE_ROUTE_REQUEST'
                }));
                return;
            }

            if (message.type === 'error') {
                pending.reject(Object.assign(
                    new Error(message.error?.message || 'Route worker failed.'),
                    { code: message.error?.code || 'ROUTE_WORKER_ERROR' }
                ));
                return;
            }

            pending.resolve(message.result || null);
        });

        outdoorRouteWorker.addEventListener('error', event => {
            stopOutdoorRouteWorker(Object.assign(
                new Error(event?.message || 'Route worker became unavailable.'),
                { code: 'ROUTE_WORKER_UNAVAILABLE' }
            ));
        });

        outdoorRouteWorkerRevision += 1;
        outdoorRouteWorker.postMessage({
            type: 'init',
            requestId: 0,
            snapshotVersion: outdoorRouteWorkerRevision,
            graph: outdoorGraph
        });

        return true;
    }

    function buildOutdoorGraph(featureCollection) {
        outdoorGraph = {};
        outdoorNodeCoords = {};
        outdoorEdgeMeta = {};

        const features = featureCollection?.features || [];

        /*
        |--------------------------------------------------------------------------
        | ROUTING FIX: AUTO-CONNECT / AUTO-SPLIT PATH INTERSECTIONS
        |--------------------------------------------------------------------------
        | Problem sa imong screenshot:
        | - Sa QGIS, makita nga nagtapad / nag-cross ang path lines.
        | - Pero sa Dijkstra graph, mo-connect ra ang route kung SAME EXACT vertex
        |   ang duha ka path. Kung ni-cross ra visually pero walay vertex/intersection
        |   point sa GeoJSON, dili siya maka-turn didto, so mo libot siya.
        |
        | Fix ani nga function:
        | 1. Kuhaon tanan path segments.
        | 2. Pangitaon ang intersections sa segments.
        | 3. I-split ang segments sa intersection points.
        | 4. I-snap ang very-near points para connected bisan gamay og offset sa QGIS.
        | 5. Then dha na mag-build og Dijkstra edges.
        |--------------------------------------------------------------------------
        */
        const SNAP_TOLERANCE_METERS = 1.25;     // v4 FIX: smaller snap so route will NOT jump/shortcut to nearby separate paths
        const INTERSECTION_EPSILON = 1e-10;
        const segments = [];
        const smartNodes = [];

        function toBool(value) {
            return value === true ||
                value === 1 ||
                value === '1' ||
                String(value || '').toLowerCase() === 'true' ||
                String(value || '').toLowerCase() === 'yes';
        }

        function makePoint(lng, lat) {
            return {
                lng: Number(lng),
                lat: Number(lat)
            };
        }

        function getSmartNodeKey(lng, lat) {
            lng = Number(lng);
            lat = Number(lat);

            /*
            |--------------------------------------------------------------------------
            | V4 ROUTE-JUMP FIX
            |--------------------------------------------------------------------------
            | Ayaw na i-merge ang nodes nga layo ra kaayo.
            | Sa old value nga 6 meters, ang duha ka lain nga path nga tapad ra tan-awon
            | mahimong usa ka node sa graph, mao nga ang green route murag mo-ambak
            | from one path to another.
            |
            | 1.25m ra ang snap: enough para sa small QGIS digitizing mismatch,
            | pero dili na siya mo shortcut across nearby separate walkways.
            |--------------------------------------------------------------------------
            */
            for (const node of smartNodes) {
                const d = map.distance([lat, lng], [node.lat, node.lng]);
                if (d <= SNAP_TOLERANCE_METERS) {
                    return node.key;
                }
            }

            const key = formatCoordKey(lng, lat);
            smartNodes.push({ key, lat, lng });
            return key;
        }

        function cross2D(a, b) {
            return (a.x * b.y) - (a.y * b.x);
        }

        function getSegmentIntersection(segA, segB) {
            /*
            |--------------------------------------------------------------------------
            | IMPORTANT FIX
            |--------------------------------------------------------------------------
            | Do NOT use map.latLngToLayerPoint() here.
            | buildOutdoorGraph() runs while data is loading, and sometimes Leaflet has
            | not finished setView()/fitBounds() yet. That causes:
            | "Set map center and zoom first."
            |
            | This local meter projection is enough for small campus paths and works
            | even before the map view is initialized.
            */
            const originLat = (
                Number(segA.a.lat) + Number(segA.b.lat) +
                Number(segB.a.lat) + Number(segB.b.lat)
            ) / 4;

            const metersPerDegLat = 110540;
            const metersPerDegLng = 111320 * Math.cos(originLat * Math.PI / 180);

            function projectPoint(point) {
                return {
                    x: Number(point.lng) * metersPerDegLng,
                    y: Number(point.lat) * metersPerDegLat
                };
            }

            const p = projectPoint(segA.a);
            const p2 = projectPoint(segA.b);
            const q = projectPoint(segB.a);
            const q2 = projectPoint(segB.b);

            const r = { x: p2.x - p.x, y: p2.y - p.y };
            const s = { x: q2.x - q.x, y: q2.y - q.y };
            const qp = { x: q.x - p.x, y: q.y - p.y };
            const denom = cross2D(r, s);

            // Parallel / almost parallel: skip. Near endpoints are handled by snap tolerance.
            if (Math.abs(denom) < INTERSECTION_EPSILON) {
                return null;
            }

            const t = cross2D(qp, s) / denom;
            const u = cross2D(qp, r) / denom;

            if (t < -INTERSECTION_EPSILON || t > 1 + INTERSECTION_EPSILON) return null;
            if (u < -INTERSECTION_EPSILON || u > 1 + INTERSECTION_EPSILON) return null;

            const safeT = Math.max(0, Math.min(1, t));
            const safeU = Math.max(0, Math.min(1, u));

            const lng = segA.a.lng + ((segA.b.lng - segA.a.lng) * safeT);
            const lat = segA.a.lat + ((segA.b.lat - segA.a.lat) * safeT);

            return {
                lng,
                lat,
                tA: safeT,
                tB: safeU
            };
        }

        function getPointAtT(seg, t) {
            return {
                lng: seg.a.lng + ((seg.b.lng - seg.a.lng) * t),
                lat: seg.a.lat + ((seg.b.lat - seg.a.lat) * t),
                t
            };
        }

        function projectPointToSegmentMeters(seg, point) {
            /*
            |--------------------------------------------------------------------------
            | V3 GAP FIX / T-JUNCTION FIX
            |--------------------------------------------------------------------------
            | QGIS paths often look connected but the endpoint is just beside the other
            | line, not exactly on its vertex. Intersection detection cannot catch this
            | because there is a tiny gap. This projects an endpoint to the nearest
            | point on a nearby segment, then splits that target segment there.
            |--------------------------------------------------------------------------
            */
            const originLat = (Number(seg.a.lat) + Number(seg.b.lat) + Number(point.lat)) / 3;
            const metersPerDegLat = 110540;
            const metersPerDegLng = 111320 * Math.cos(originLat * Math.PI / 180);

            function project(p) {
                return {
                    x: Number(p.lng) * metersPerDegLng,
                    y: Number(p.lat) * metersPerDegLat
                };
            }

            const a = project(seg.a);
            const b = project(seg.b);
            const p = project(point);

            const abx = b.x - a.x;
            const aby = b.y - a.y;
            const ab2 = (abx * abx) + (aby * aby);
            if (ab2 <= 0.000001) return null;

            let t = (((p.x - a.x) * abx) + ((p.y - a.y) * aby)) / ab2;
            t = Math.max(0, Math.min(1, t));

            const cx = a.x + (abx * t);
            const cy = a.y + (aby * t);
            const dx = p.x - cx;
            const dy = p.y - cy;
            const distanceMeters = Math.sqrt((dx * dx) + (dy * dy));

            const lng = seg.a.lng + ((seg.b.lng - seg.a.lng) * t);
            const lat = seg.a.lat + ((seg.b.lat - seg.a.lat) * t);

            return { lng, lat, t, distanceMeters };
        }

        function addSplitPoint(seg, point) {
            if (!Number.isFinite(point.t)) return;
            if (point.t < -0.000001 || point.t > 1.000001) return;

            const safeT = Math.max(0, Math.min(1, point.t));

            const exists = seg.splitPoints.some(existing => Math.abs(existing.t - safeT) < 0.000001);
            if (exists) return;

            seg.splitPoints.push({
                lng: Number(point.lng),
                lat: Number(point.lat),
                t: safeT
            });
        }

        function shouldSkipSegment(props) {
            return window.WayfindingRouting.isPathBlocked(props);
        }

        // 1) Flatten LineString / MultiLineString into segments.
        features.forEach(feature => {
            if (!feature.geometry) return;

            const props = feature.properties || {};
            if (shouldSkipSegment(props)) return;

            const pathId = props.id;
            const rawType = String(props.type || '').trim().toLowerCase();

            const lines = feature.geometry.type === 'MultiLineString' ?
                feature.geometry.coordinates : [feature.geometry.coordinates];

            lines.forEach(line => {
                if (!Array.isArray(line) || line.length < 2) return;

                for (let i = 0; i < line.length - 1; i++) {
                    const a = line[i];
                    const b = line[i + 1];

                    const lngA = Number(a[0]);
                    const latA = Number(a[1]);
                    const lngB = Number(b[0]);
                    const latB = Number(b[1]);

                    if (![lngA, latA, lngB, latB].every(Number.isFinite)) continue;
                    if (map.distance([latA, lngA], [latB, lngB]) <= 0.05) continue;

                    const segment = {
                        id: segments.length,
                        pathId,
                        rawType,
                        props,
                        a: makePoint(lngA, latA),
                        b: makePoint(lngB, latB),
                        splitPoints: []
                    };

                    segment.splitPoints.push({ lng: lngA, lat: latA, t: 0 });
                    segment.splitPoints.push({ lng: lngB, lat: latB, t: 1 });

                    segments.push(segment);
                }
            });
        });

        // 2) Auto-split every segment where another segment crosses it.
        for (let i = 0; i < segments.length; i++) {
            for (let j = i + 1; j < segments.length; j++) {
                const segA = segments[i];
                const segB = segments[j];

                const hit = getSegmentIntersection(segA, segB);
                if (!hit) continue;

                addSplitPoint(segA, { lng: hit.lng, lat: hit.lat, t: hit.tA });
                addSplitPoint(segB, { lng: hit.lng, lat: hit.lat, t: hit.tB });
            }
        }

        // 2.5) V3: connect endpoints that are very close to the middle of another path.
        // This is the common reason why the route still goes around even if the paths
        // look connected in QGIS / Leaflet.
        for (let i = 0; i < segments.length; i++) {
            const source = segments[i];
            const endpoints = [
                { lng: source.a.lng, lat: source.a.lat, t: 0 },
                { lng: source.b.lng, lat: source.b.lat, t: 1 }
            ];

            for (let j = 0; j < segments.length; j++) {
                if (i === j) continue;
                const target = segments[j];

                endpoints.forEach(endpoint => {
                    const projected = projectPointToSegmentMeters(target, endpoint);
                    if (!projected) return;

                    // Avoid adding duplicate endpoints of the same target segment.
                    if (projected.t <= 0.000001 || projected.t >= 0.999999) return;

                    if (projected.distanceMeters <= SNAP_TOLERANCE_METERS) {
                        addSplitPoint(target, projected);
                    }
                });
            }
        }

        // 3) Add edges between consecutive split points.
        segments.forEach(seg => {
            const props = seg.props || {};
            const rawType = seg.rawType;
            const pathId = seg.pathId;

            let typeMultiplier = 1;
            let typeExtraPenalty = 0;
            const isOutdoorStairs = rawType.includes('stairs');

            if (isOutdoorStairs) {
                typeMultiplier = 1.25;
                typeExtraPenalty = 2;
            }

            if (rawType === 'covered_stairs') {
                typeMultiplier = 1.15;
                typeExtraPenalty = 1;
            }

            const allowBuildingCrossing =
                toBool(props.allow_building_crossing) ||
                toBool(props.allow_building_passage) ||
                rawType.includes('covered_walkway') ||
                rawType.includes('building_passage') ||
                rawType.includes('passage') ||
                rawType.includes('corridor');

            const hazardProfile = getPathHazardProfile(pathId);

            seg.splitPoints
                .sort((a, b) => a.t - b.t)
                .forEach((point, index, arr) => {
                    if (index >= arr.length - 1) return;

                    const a = point;
                    const b = arr[index + 1];

                    const distance = map.distance([a.lat, a.lng], [b.lat, b.lng]);
                    if (distance <= 0.05) return;

                    const keyA = getSmartNodeKey(a.lng, a.lat);
                    const keyB = getSmartNodeKey(b.lng, b.lat);
                    if (keyA === keyB) return;

                    const cutsBuilding = segmentCutsThroughBuilding([a.lng, a.lat], [b.lng, b.lat]);

                    /*
                    |--------------------------------------------------------------------------
                    | V3 FORCE-SHORTEST FIX
                    |--------------------------------------------------------------------------
                    | Your current campus data has some valid walking lines very close to / under
                    | building polygons. The old 10000 penalty forced Dijkstra to go around the
                    | field. For your case, do not punish building-overlap paths; only blocked
                    | paths are avoided.
                    |--------------------------------------------------------------------------
                    */
                    const buildingCrossingPenalty = 0;

                    const finalWeight =
                        (((distance * typeMultiplier) + typeExtraPenalty) * hazardProfile.penalty) +
                        buildingCrossingPenalty;

                    addOutdoorEdge(keyA, a.lat, a.lng, keyB, b.lat, b.lng, finalWeight, {
                        pathId,
                        pathType: rawType,
                        isStairs: isOutdoorStairs,
                        stairsPenalty: typeExtraPenalty,
                        cutsBuilding,
                        allowBuildingCrossing,
                        buildingCrossingPenalty,
                        autoSplitIntersection: arr.length > 2,
                        snapToleranceMeters: SNAP_TOLERANCE_METERS,
                        maxSeverity: hazardProfile.maxSeverity,
                        hasHazard: hazardProfile.hasHazard,
                        colorHint: hazardProfile.colorHint,
                        baseDistance: distance,
                    });
                });
        });

        initializeOutdoorRouteWorker();

    }

    function dijkstra(startKey, endKey) {
        return window.WayfindingRouting.outdoorShortestPath(
            outdoorGraph,
            startKey,
            endKey
        );
    }

    function dijkstraAsync(startKey, endKey, options = {}) {
        const latestOnly = options.latestOnly !== false;

        if (!outdoorRouteWorker) {
            return Promise.resolve(dijkstra(startKey, endKey));
        }

        const requestId = ++outdoorRouteRequestSequence;
        if (latestOnly) latestOutdoorRouteRequestId = requestId;

        return new Promise((resolve, reject) => {
            const timeoutId = setTimeout(() => {
                pendingOutdoorRouteRequests.delete(requestId);

                if (latestOnly && requestId !== latestOutdoorRouteRequestId) {
                    reject(Object.assign(new Error('A newer destination replaced this route.'), {
                        code: 'STALE_ROUTE_REQUEST'
                    }));
                    return;
                }

                resolve(dijkstra(startKey, endKey));
            }, 1800);

            pendingOutdoorRouteRequests.set(requestId, {
                resolve,
                reject,
                timeoutId,
                latestOnly
            });

            outdoorRouteWorker.postMessage({
                type: 'route',
                requestId,
                snapshotVersion: outdoorRouteWorkerRevision,
                startKey,
                endKey
            });
        }).catch(error => {
            if (error?.code === 'STALE_ROUTE_REQUEST') throw error;
            return dijkstra(startKey, endKey);
        });
    }


    function drawAnimatedRoute(mapInstance, layerGroup, latlngs, options = {}) {
        /*
        |--------------------------------------------------------------------------
        | STATIC ROUTE LINE ONLY
        |--------------------------------------------------------------------------
        | Removed moving arrow animations for both outdoor and indoor routes.
        | This function keeps the same name so the rest of your routing code
        | will still work without changing the Dijkstra / destination logic.
        */
        if (!mapInstance || !layerGroup || !Array.isArray(latlngs) || latlngs.length < 2) {
            return {
                polyline: null,
                timer: null
            };
        }

        const color = options.color || '#16a34a';
        const weight = options.weight || 8;
        const opacity = options.opacity ?? 1;
        const pane = options.pane || null;
        const className = options.className || 'route-line-live';

        const routePoints = latlngs
            .map(p => {
                if (!p) return null;

                if (p instanceof L.LatLng) {
                    return L.latLng(Number(p.lat), Number(p.lng));
                }

                if (Array.isArray(p)) {
                    return L.latLng(Number(p[0]), Number(p[1]));
                }

                if (typeof p === 'object' && p.lat !== undefined && p.lng !== undefined) {
                    return L.latLng(Number(p.lat), Number(p.lng));
                }

                return null;
            })
            .filter(p => p && Number.isFinite(p.lat) && Number.isFinite(p.lng));

        if (routePoints.length < 2) {
            return {
                polyline: null,
                timer: null
            };
        }

        const polylineOptions = {
            color,
            weight,
            opacity,
            dashArray: options.dashArray ?? null,
            lineCap: 'round',
            lineJoin: 'round',
            interactive: false,
            className
        };

        const outlineOptions = {
            color: options.outlineColor || '#082338',
            weight: options.outlineWeight || (weight + 5),
            opacity: options.outlineOpacity ?? 0.92,
            dashArray: options.outlineDashArray ?? null,
            lineCap: 'round',
            lineJoin: 'round',
            interactive: false,
            className: 'route-line-outline'
        };

        if (pane) {
            polylineOptions.pane = pane;
            outlineOptions.pane = pane;
        }

        if (pane === 'pathsPane' && typeof OUTDOOR_ROUTE_RENDERER !== 'undefined') {
            polylineOptions.renderer = OUTDOOR_ROUTE_RENDERER;
            outlineOptions.renderer = OUTDOOR_ROUTE_RENDERER;
        }

        const routeOutline = L.polyline(routePoints, outlineOptions).addTo(layerGroup);
        const routePolyline = L.polyline(routePoints, polylineOptions).addTo(layerGroup);

        return {
            polyline: routePolyline,
            outline: routeOutline,
            timer: null
        };
    }

    function drawOutdoorRoute(result, options = {}) {
        if (!result || !result.path || result.path.length < 2) return;

        clearRouteLayer();
        routeLayer = L.layerGroup().addTo(map);

        const latlngs = result.path.map(key => parseCoordKey(key));

        const staticRoute = drawAnimatedRoute(map, routeLayer, latlngs, {
            pane: 'pathsPane',
            color: '#25c9f2',
            weight: 5.5,
            dashArray: null,
            outlineColor: '#67dcfa',
            outlineWeight: 11,
            outlineOpacity: 0.28,
            className: 'route-line-live',
        });

        outdoorRouteAnimationTimer = staticRoute.timer;

        if (options.fitBounds !== false) {
            const routeBounds = L.latLngBounds(latlngs);
            const routeAlreadyVisible = IS_MOBILE_OUTDOOR_VIEW
                && map.getBounds?.().contains(routeBounds);

            /* Most phone users already have the whole campus in view. Avoid a
               redundant camera reset in that case: the route paints at once
               and manual pinch/drag remains untouched. */
            if (routeAlreadyVisible) return;

            map.fitBounds(routeBounds, {
                padding: IS_MOBILE_OUTDOOR_VIEW ? [80, 80] : [60, 60],
                maxZoom: IS_MOBILE_OUTDOOR_VIEW
                    ? MOBILE_OUTDOOR_ROUTE_ZOOM_VALUE
                    : undefined,
                animate: !IS_MOBILE_OUTDOOR_VIEW
            });
        }
    }

    function drawHazardMarkers() {
        clearHazardLayer();
        hazardLayer = L.layerGroup().addTo(map);

        hazardPoints
            .filter(h => Boolean(h.is_active))
            .forEach(hazard => {
                const severity = Number(hazard.severity_level || 1);
                const severityClass = getHazardSeverityClass(severity);
                const severityColor = getHazardSeverityColor(severity);

                const lat = Number(hazard.latitude);
                const lng = Number(hazard.longitude);

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | HAZARD ICON SAME-SCREEN-SIZE FIX
                |--------------------------------------------------------------------------
                | Use L.marker + L.divIcon instead of L.circleMarker.
                | No CSS/JS scale is applied, so the icon keeps the same pixel size
                | in zoom in and zoom out.
                */
                L.marker([lat, lng], {
                    icon: createHazardIcon(hazard),
                    keyboard: false
                }).bindPopup(`
                    <div class="hazard-popup-content">
                        <div class="hazard-popup-title">
                            <span class="hazard-popup-dot" style="--hazard-dot:${severityColor}"></span>
                            <span>${escapeHazardHtml(hazard.title || 'Hazard')}</span>
                        </div>

                        <div class="hazard-popup-type">
                            <span class="hazard-popup-label">Type</span>
                            <strong>${escapeHazardHtml(hazard.warning_type || 'Unknown')}</strong>
                        </div>

                        <div class="hazard-popup-badge ${severityClass}">
                            Hazard Level ${severity}
                        </div>
                    </div>
                `, {
                    className: 'hazard-map-popup',
                    minWidth: 210,
                    maxWidth: 260
                }).addTo(hazardLayer);
            });
    }


    function showPickPathHelper() {
        closeFloatingActionCard();

        document.body.classList.add('pick-path-active', 'pick-path-tap-only');

        if (pickPathHelper) {
            pickPathHelper.style.display = 'block';
        }

        updatePickPathHelperText();
    }

    function hidePickPathHelper() {
        document.body.classList.remove('pick-path-active', 'pick-path-tap-only');

        if (pickPathHelper) {
            pickPathHelper.style.display = 'none';
        }
    }

    function updatePickPathHelperText() {
        if (!pickPathHelperText || !placingStartMode) return;

        pickPathHelperText.textContent = 'Tap anywhere on the campus map. The pin will automatically snap to the nearest path.';
    }

    function showPickTapRipple(latlng) {
        if (!latlng) return;

        const point = map.latLngToContainerPoint(latlng);
        const ripple = document.createElement('div');
        ripple.className = 'pick-tap-ripple';
        ripple.style.left = `${point.x}px`;
        ripple.style.top = `${point.y}px`;

        const mapEl = document.getElementById('map');
        if (!mapEl) return;

        mapEl.appendChild(ripple);

        window.setTimeout(() => {
            if (ripple && ripple.parentNode) {
                ripple.parentNode.removeChild(ripple);
            }
        }, 680);
    }

    function placePickStartAndClose(lat, lng, label = 'Start Point on Path', tappedLatLng = null) {
        if (!isInsideCampus(lat, lng)) {
            alert('Please tap a start point inside the campus area.');
            return;
        }

        if (tappedLatLng) {
            showPickTapRipple(tappedLatLng);
        }

        setStartFromLatLng(lat, lng, label, 'path');
        placingStartMode = false;
        hidePickPathHelper();
        updateRouteLabels();
        setRouteResultLabel('Start point placed. Your tap was snapped to the nearest campus path.');

        /*
        | If the user clicked Route to Room/Event while Pick Path mode was active,
        | continue the route automatically after the tap has been snapped.
        */
        if (pendingRouteAfterPickPath) {
            pendingRouteAfterPickPath = false;

            setTimeout(() => {
                findRouteByDestination();
            }, 150);
        }
    }

    function pickStartFromMapCenter() {
        if (!placingStartMode) {
            enablePathStartPlacement();
        }

        const center = map.getCenter();
        placePickStartAndClose(center.lat, center.lng, 'Start Point from Map Center', center);
    }

    function cancelPickPathMode() {
        placingStartMode = false;
        selectedStartMode = startNodeKey ? 'path' : 'default';
        hidePickPathHelper();
        setActiveStartModeButton(startNodeKey ? 'path' : 'default');
        updateRouteLabels();
        setRouteResultLabel(startNodeKey ? 'Tap My Location cancelled. Previous start point kept.' : 'Tap My Location cancelled.');
    }

    wayfindingInteraction.register('pick-path-helper', updatePickPathHelperText);

    map.on('click', function(e) {
        if (!placingStartMode) return;

        const target = e.originalEvent?.target;
        if (target?.closest?.('#floating-route-ui, #pick-path-helper, .floating-modal-backdrop')) return;

        placePickStartAndClose(e.latlng.lat, e.latlng.lng, 'Start Point from Map Tap', e.latlng);
    });

    function setStartFromLatLng(lat, lng, label = 'Start Point', source = 'path') {
        const nearestKey = nearestNodeKey(lat, lng);
        if (!nearestKey) {
            alert('No nearest routing node found.');
            return;
        }

        startNodeKey = nearestKey;
        startSourceType = source;

        const snappedStartCoord = outdoorNodeCoords[nearestKey] || [lat, lng];
        const markerLat = Number(snappedStartCoord[0]);
        const markerLng = Number(snappedStartCoord[1]);

        clearOutsideGuideLine();
        clearRouteLayer();
        clearStartMarker();

        startMarker = L.marker([markerLat, markerLng], {
            draggable: source === 'path',
            icon: createDivIcon('<div class="route-start-arrow"></div>')
        }).addTo(map).bindPopup(label);

        if (source === 'path') {
            startMarker.on('dragend', function(e) {
                const newLatLng = e.target.getLatLng();

                if (!isInsideCampus(newLatLng.lat, newLatLng.lng)) {
                    alert('Start point must remain inside the campus area.');
                    e.target.setLatLng([markerLat, markerLng]);
                    return;
                }

                const newKey = nearestNodeKey(newLatLng.lat, newLatLng.lng);
                if (!newKey) {
                    e.target.setLatLng([markerLat, markerLng]);
                    return;
                }

                const snappedCoord = outdoorNodeCoords[newKey] || [newLatLng.lat, newLatLng.lng];
                e.target.setLatLng([Number(snappedCoord[0]), Number(snappedCoord[1])]);

                startNodeKey = newKey;
                updateRouteLabels();
                setRouteResultLabel('Start point moved and snapped to the nearest path.');
            });
        }

        updateRouteLabels();
    }

    function enablePathStartPlacement() {
        placingStartMode = true;
        startSourceType = 'path';
        selectedStartMode = 'path';
        setActiveStartModeButton('path');
        showPickPathHelper();

        if (IS_MOBILE_OUTDOOR_VIEW) {
            const targetZoom = Math.min(MOBILE_OUTDOOR_MAX_ZOOM_VALUE, Math.max(map.getZoom(), MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE));
            map.setZoom(targetZoom, { animate: true });
        }

        updateRouteLabels();
        setRouteResultLabel('Tap anywhere on the campus map. Your start pin will snap to the nearest path automatically.');
    }

    function closeFloatingActionCard() {
        const card = document.getElementById('floating-action-card');
        const oldMenu = document.getElementById('destination-menu');
        const pin = document.getElementById('destination-menu-toggle');

        if (card) {
            card.style.display = 'none';
        }

        if (oldMenu) {
            oldMenu.style.display = 'none';
        }

        if (pin) {
            pin.classList.remove('active');
        }
    }

    function toggleFloatingActionCard() {
        const card = document.getElementById('floating-action-card');
        const oldMenu = document.getElementById('destination-menu');
        const pin = document.getElementById('destination-menu-toggle');
        const targetMenu = card || oldMenu;

        if (!targetMenu) {
            console.error(
                'No floating action card found. Check dashboard IDs: floating-action-card or destination-menu.');
            return;
        }

        const isOpen = targetMenu.style.display === 'block';
        targetMenu.style.display = isOpen ? 'none' : 'block';

        if (pin) {
            pin.classList.toggle('active', !isOpen);
        }
    }

    function toggleDestinationMenu() {
        toggleFloatingActionCard();
    }

    function openTextSearchModal() {
        closeFloatingActionCard();

        const modal = document.getElementById('textSearchModal');

        if (modal) {
            modal.style.display = 'flex';
        }

        setTimeout(() => {
            const input = document.getElementById('destination-search-input');
            if (input) input.focus();
        }, 80);
    }

    function closeTextSearchModal() {
        const modal = document.getElementById('textSearchModal');

        if (modal) {
            modal.style.display = 'none';
        }
    }

    function openBrowseOptionsModal() {
        const modal = document.getElementById('browseOptionsModal');
        const activeElement = document.activeElement;
        const returnFocus = activeElement instanceof HTMLElement && !modal?.contains(activeElement)
            ? activeElement
            : document.getElementById('destination-menu-toggle');

        closeFloatingActionCard();

        if (modal) {
            modal.__wayfindingReturnFocus = returnFocus;
            modal.inert = false;
            modal.removeAttribute('inert');
            modal.removeAttribute('aria-hidden');
            modal.style.display = 'flex';
        }

        if (typeof updateDestinationUi === 'function') {
            updateDestinationUi();
        }

        if (typeof updateRouteLabels === 'function') {
            updateRouteLabels();
        }
    }

    function closeBrowseOptionsModal() {
        const modal = document.getElementById('browseOptionsModal');

        if (modal) {
            const focusedElement = document.activeElement;
            if (focusedElement instanceof HTMLElement && modal.contains(focusedElement)) {
                const returnFocus = modal.__wayfindingReturnFocus;
                const fallbackFocus = document.getElementById('destination-menu-toggle');
                const returnFocusIsUsable = returnFocus instanceof HTMLElement
                    && returnFocus.isConnected
                    && !returnFocus.closest('[inert]')
                    && returnFocus.getClientRects().length > 0;
                const focusTarget = returnFocusIsUsable
                    ? returnFocus
                    : fallbackFocus;

                if (focusTarget instanceof HTMLElement) {
                    focusTarget.focus({ preventScroll: true });
                }

                if (modal.contains(document.activeElement)) {
                    focusedElement.blur();
                }
            }

            modal.inert = true;
            modal.setAttribute('inert', '');
            modal.removeAttribute('aria-hidden');
            modal.style.display = 'none';
        }
    }

    function startVoiceSearchFlow() {
        closeFloatingActionCard();

        if (typeof ensureDefaultStartBeforeRoute === 'function') {
            if (!ensureDefaultStartBeforeRoute()) return;
        }

        if (typeof toggleVoiceCommand === 'function') {
            toggleVoiceCommand();
        } else {
            console.error('toggleVoiceCommand() not found.');
            alert('Voice command function not found.');
        }
    }

    function setActiveStartModeButton(mode) {
        document.querySelectorAll('.floating-mode-btn, .mode-pill').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-pressed', 'false');
        });

        const floatingSelector =
            mode === 'path' ? '.floating-mode-btn.pick' :
            mode === 'gps' ? '.floating-mode-btn.gps' :
            '.floating-mode-btn.default';

        const oldSelector =
            mode === 'path' ? '.mode-pill.pick' :
            mode === 'gps' ? '.mode-pill.gps' :
            '.mode-pill.default';

        const activeBtn = document.querySelector(floatingSelector) || document.querySelector(oldSelector);
        if (activeBtn) {
            activeBtn.classList.add('active');
            activeBtn.setAttribute('aria-pressed', 'true');
        }
    }

    function selectPickPathMode() {
        selectedStartMode = 'path';
        setActiveStartModeButton('path');
        enablePathStartPlacement();
    }

    function selectGpsMode() {
        if (window.WAYFINDING_GUEST_MODE === true) {
            window.showWayfindingToast?.(
                'GPS is available after signing in. Guest Mode can use Default Route or Pick Path.',
                { kind: 'info' }
            );
            return;
        }

        placingStartMode = false;
        hidePickPathHelper();
        selectedStartMode = 'gps';
        setActiveStartModeButton('gps');
        useCurrentLocationAsStart();
    }

    function selectDefaultMode() {
        placingStartMode = false;
        hidePickPathHelper();
        selectedStartMode = 'default';
        setActiveStartModeButton('default');
        useDefaultEntryPointAsStart();
    }

    function getFirstDefaultEntryOption() {
        if (!defaultEntrySelect) return null;

        const options = Array.from(defaultEntrySelect.options || []);

        return options.find(opt =>
            opt.value &&
            opt.dataset.latitude &&
            opt.dataset.longitude
        ) || null;
    }

    function ensureDefaultStartBeforeRoute() {
        if (startNodeKey) return true;

        const firstDefault = getFirstDefaultEntryOption();

        if (!firstDefault) {
            alert('No default entry point found. Please add at least one entry point.');
            return false;
        }

        defaultEntrySelect.value = firstDefault.value;

        const lat = Number(firstDefault.dataset.latitude);
        const lng = Number(firstDefault.dataset.longitude);

        setStartFromLatLng(
            lat,
            lng,
            firstDefault.dataset.name || firstDefault.textContent.trim() || 'Default Entry Point',
            'default_start'
        );

        selectedStartMode = 'default';
        setActiveStartModeButton('default');
        setRouteResultLabel('Default Start automatically selected.');

        return true;
    }

    function useDefaultEntryPointAsStart() {
        let selected = defaultEntrySelect?.selectedOptions?.[0];

        if (!selected || !selected.value) {
            selected = getFirstDefaultEntryOption();

            if (selected && defaultEntrySelect) {
                defaultEntrySelect.value = selected.value;
            }
        }

        if (!selected || !selected.value) {
            alert('No default entry point found.');
            return false;
        }

        const lat = Number(selected.dataset.latitude);
        const lng = Number(selected.dataset.longitude);

        setStartFromLatLng(
            lat,
            lng,
            selected.dataset.name || selected.textContent.trim() || 'Default Entry Point',
            'default_start'
        );

        selectedStartMode = 'default';
        setActiveStartModeButton('default');

        return true;
    }

    function useCurrentLocationAsStart() {
        selectedStartMode = 'gps';
        setActiveStartModeButton('gps');

        if (!navigator.geolocation) {
            alert('Geolocation is not supported on this device.');
            return;
        }

        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            clearCurrentLocationMarker();
            currentLocationMarker = L.marker([lat, lng], {
                icon: createDivIcon('<div class="route-gps-dot"></div>', [18, 18], [9, 9])
            }).addTo(map).bindPopup('Your Current Location');

            if (!isInsideCampus(lat, lng)) {
                const fallback = entryPoints[0];
                if (!fallback) {
                    alert('No entry point found for outside-campus routing.');
                    return;
                }

                const gatewayLat = Number(fallback.latitude);
                const gatewayLng = Number(fallback.longitude);

                drawOutsideGuideLine(lat, lng, gatewayLat, gatewayLng);
                setStartFromLatLng(gatewayLat, gatewayLng, fallback.name || 'Campus Entry',
                    'gps_outside_campus');
                setRouteResultLabel('Outside campus: guiding first to campus entry point.');
                return;
            }

            setStartFromLatLng(lat, lng, 'Your Current Location', 'gps_inside_campus');
            setRouteResultLabel('GPS start point selected.');
        }, function() {
            alert('Unable to get your current location.');
        }, {
            enableHighAccuracy: true,
            timeout: 10000
        });
    }



    function useCurrentLocationAsStartForRouting() {
        selectedStartMode = 'gps';
        setActiveStartModeButton('gps');

        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported on this device. You can use Pick Path or Default Route instead.');
                resolve(false);
                return;
            }

            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                clearCurrentLocationMarker();

                currentLocationMarker = L.marker([lat, lng], {
                    icon: createDivIcon('<div class="route-gps-dot"></div>', [18, 18], [9, 9])
                }).addTo(map).bindPopup('Your Current Location');

                if (!isInsideCampus(lat, lng)) {
                    const fallback = entryPoints[0];

                    if (!fallback) {
                        alert('No entry point found for outside-campus routing.');
                        resolve(false);
                        return;
                    }

                    const gatewayLat = Number(fallback.latitude);
                    const gatewayLng = Number(fallback.longitude);

                    drawOutsideGuideLine(lat, lng, gatewayLat, gatewayLng);

                    setStartFromLatLng(
                        gatewayLat,
                        gatewayLng,
                        fallback.name || 'Campus Entry',
                        'gps_outside_campus'
                    );

                    setRouteResultLabel('Outside campus: guiding first to campus entry point.');
                    resolve(true);
                    return;
                }

                setStartFromLatLng(lat, lng, 'Your Current Location', 'gps_inside_campus');
                setRouteResultLabel('GPS start point selected.');
                resolve(true);

            }, function() {
                alert('Unable to get your current location. You can use Pick Path or Default Route instead.');
                resolve(false);
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        });
    }

    async function ensureSelectedStartBeforeRoute() {
        /* If a start is already selected from GPS, Pick Path, or Default, keep it. */
        if (startNodeKey) {
            return true;
        }

        if (selectedStartMode === 'gps') {
            setRouteResultLabel('Getting GPS location before routing...');
            return await useCurrentLocationAsStartForRouting();
        }

        if (selectedStartMode === 'path' || placingStartMode) {
            pendingRouteAfterPickPath = true;
            enablePathStartPlacement();
            setRouteResultLabel('Tap your current spot on the map first. After tapping, the event route will continue automatically.');
            return false;
        }

        return ensureDefaultStartBeforeRoute();
    }
