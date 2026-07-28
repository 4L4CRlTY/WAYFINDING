/* =========================================================
   OUTDOOR LIVE GPS + QGIS PATH SNAPPING
   - The latest trusted position moves the marker continuously.
   - Moderate weak readings keep the watcher alive instead of freezing it.
   - Active routes are recalculated as the user advances.
   - Indoor navigation remains floor-aware and route-based.
========================================================= */
(function () {
    if (window.__outdoorLiveGpsTrackingInstalled) return;
    window.__outdoorLiveGpsTrackingInstalled = true;

    /*
    | ACCURACY-BASED GPS SNAP SETTINGS
    | Instead of fixed 10m snap only, the snap radius now follows GPS accuracy.
    | Example: accuracy 15m => snap radius about 15m. A small grace is added
    | for QGIS digitizing offset so the marker can still snap to the walkway.
    */
    const GPS_STRONG_ACCURACY_M = 25;          // good phone GPS fix
    const GPS_PREVIEW_ACCURACY_M = 45;         // usable when snapped to a campus path
    const GPS_REJECT_ACCURACY_M = 60;          // keep watching, but do not move the route start
    const GPS_MIN_SNAP_RADIUS_M = 12;          // minimum snap radius
    const GPS_MAX_SNAP_RADIUS_M = 30;          // strict cap to avoid snapping to wrong campus path
    const GPS_SNAP_GRACE_M = 4;                // small tolerance for QGIS/path offset
    const GPS_ACTIVE_ROUTE_EXTRA_M = 6;        // active route gets a little more allowance
    const GPS_QUALITY_LOCK_REQUIRED_SAMPLES = 4;
    const GPS_QUALITY_LOCK_MAX_ACCURACY_M = 20;
    const GPS_QUALITY_LOCK_MAX_SPREAD_M = 10;
    const GPS_OFF_ROUTE_CONFIRMATION_SAMPLES = 3;
    const GPS_BAD_JUMP_SPEED_MPS = 14;         // reject only physically implausible jumps
    const GPS_SMOOTH_FACTOR = 0.58;             // responsive enough for walking guidance
    const GPS_REROUTE_INTERVAL_MS = 2500;
    const GPS_REROUTE_MIN_MOVE_M = 4;
    const GPS_ARRIVAL_DISTANCE_M = 10;
    const GPS_TURN_NOTICE_DISTANCE_M = 22;
    const GPS_CALIBRATION_THRESHOLDS = Object.freeze({
        strongAccuracy: GPS_QUALITY_LOCK_MAX_ACCURACY_M,
        fairAccuracy: GPS_PREVIEW_ACCURACY_M,
        rejectAccuracy: GPS_REJECT_ACCURACY_M,
        maxSpread: GPS_QUALITY_LOCK_MAX_SPREAD_M,
        requiredLockSamples: GPS_QUALITY_LOCK_REQUIRED_SAMPLES,
        minSnapRadius: GPS_MIN_SNAP_RADIUS_M,
        maxSnapRadius: GPS_MAX_SNAP_RADIUS_M,
        snapGrace: GPS_SNAP_GRACE_M,
        requiredOffRouteSamples: GPS_OFF_ROUTE_CONFIRMATION_SAMPLES,
        arrivalDistance: GPS_ARRIVAL_DISTANCE_M,
    });

    function clampGpsMeters(value, min, max) {
        value = Number(value || 0);
        return Math.max(min, Math.min(max, value));
    }

    function getGpsSnapRadiusMeters(accuracy) {
        /*
        | Mao ni imong gipangayo:
        | if GPS says 15 meters accuracy, gamiton nato ang around 15 meters
        | as snap radius, with small grace para sa QGIS mismatch.
        */
        return clampGpsMeters(Number(accuracy || 999) + GPS_SNAP_GRACE_M, GPS_MIN_SNAP_RADIUS_M, GPS_MAX_SNAP_RADIUS_M);
    }

    function getGpsActiveRouteSnapRadiusMeters(accuracy) {
        return clampGpsMeters(getGpsSnapRadiusMeters(accuracy) + GPS_ACTIVE_ROUTE_EXTRA_M, GPS_MIN_SNAP_RADIUS_M, GPS_MAX_SNAP_RADIUS_M + GPS_ACTIVE_ROUTE_EXTRA_M);
    }

    let liveGpsWatchId = null;
    let liveGpsProvider = null;
    let liveGpsMarker = null;
    let liveGpsAccuracyCircle = null;
    let liveGpsStatusEl = null;
    let liveGpsSamples = [];
    let gpsQualityLockSamples = [];
    let gpsQualityLockAcquired = false;
    let consecutiveOffRouteReadings = 0;
    let lastRawGpsLatLng = null;
    let lastRawGpsTime = null;
    let lastSmoothGpsLatLng = null;
    let lastSnappedGpsLatLng = null;
    let lastNavigationPosition = null;
    let lastMovementHeading = null;
    let liveGpsHasAcceptedFix = false;
    let liveGpsFollow = true;
    let cachedCampusSegments = null;
    let cachedCampusSegmentCount = 0;
    let activeOutdoorRouteResult = null;
    let activeOutdoorDestinationKey = null;
    let lastLiveRerouteAt = 0;
    let lastLiveRerouteLatLng = null;
    let lastLiveRerouteNodeKey = null;
    let drawingLiveGpsReroute = false;
    let liveGpsProviderName = 'device';
    let gpsMapDragActive = false;
    let pendingGpsRouteRefreshPosition = null;

    const baseSelectGpsMode = typeof selectGpsMode === 'function' ? selectGpsMode : null;
    const baseSelectPickPathMode = typeof selectPickPathMode === 'function' ? selectPickPathMode : null;
    const baseSelectDefaultMode = typeof selectDefaultMode === 'function' ? selectDefaultMode : null;
    const baseResetRouteSelection = typeof resetRouteSelection === 'function' ? resetRouteSelection : null;
    const baseDrawOutdoorRoute = typeof drawOutdoorRoute === 'function' ? drawOutdoorRoute : null;
    const baseClearRouteLayer = typeof clearRouteLayer === 'function' ? clearRouteLayer : null;

    function emitGpsDiagnostic(type, detail = {}) {
        window.dispatchEvent(new CustomEvent('wayfinding:gps-diagnostic', {
            detail: {
                type,
                timestamp: Number(detail.timestamp || Date.now()),
                provider: liveGpsProviderName,
                thresholds: GPS_CALIBRATION_THRESHOLDS,
                routeActive: Boolean(activeOutdoorDestinationKey),
                ...detail,
            },
        }));
    }

    function emitGpsReading(status, sample, qualityLock, detail = {}) {
        emitGpsDiagnostic('reading', {
            status,
            lat: Number(sample?.latLng?.lat),
            lng: Number(sample?.latLng?.lng),
            accuracy: Number(sample?.accuracy || 999),
            heading: Number.isFinite(Number(sample?.heading))
                ? Number(sample.heading)
                : null,
            speed: Number.isFinite(Number(sample?.speed))
                ? Number(sample.speed)
                : null,
            altitude: Number.isFinite(Number(sample?.altitude))
                ? Number(sample.altitude)
                : null,
            qualityLocked: qualityLock?.locked === true,
            qualitySamples: Number(qualityLock?.sampleCount || 0),
            spread: Number(qualityLock?.spread || 0),
            offRouteCount: consecutiveOffRouteReadings,
            timestamp: Number(sample?.time || Date.now()),
            ...detail,
        });
    }

    function getOutdoorGpsProvider() {
        const simulator = window.WayfindingGpsSimulator;

        if (
            window.WAYFINDING_GPS_SIMULATOR_ENABLED === true
            && simulator
            && simulator.geolocation
        ) {
            return simulator.geolocation;
        }

        return navigator.geolocation || null;
    }

    function clearOutdoorGpsWatch() {
        if (liveGpsWatchId === null) return;

        if (liveGpsProvider && typeof liveGpsProvider.clearWatch === 'function') {
            liveGpsProvider.clearWatch(liveGpsWatchId);
        }

        liveGpsWatchId = null;
        liveGpsProvider = null;
    }

    function setGpsQualityLockDataset(state) {
        if (!document.body) return;

        if (state) {
            document.body.dataset.gpsQualityLock = state;
        } else {
            delete document.body.dataset.gpsQualityLock;
        }
    }

    function resetGpsQualityLock() {
        gpsQualityLockSamples = [];
        gpsQualityLockAcquired = false;
        consecutiveOffRouteReadings = 0;
        setGpsQualityLockDataset('waiting');
    }

    function evaluateGpsQualityLock(sample) {
        if (gpsQualityLockAcquired) {
            return {
                locked: true,
                justLocked: false,
                point: sample.latLng,
                accuracy: sample.accuracy,
                sampleCount: GPS_QUALITY_LOCK_REQUIRED_SAMPLES,
                spread: 0,
            };
        }

        gpsQualityLockSamples.push(sample);
        gpsQualityLockSamples = gpsQualityLockSamples.slice(
            -(GPS_QUALITY_LOCK_REQUIRED_SAMPLES * 2)
        );

        const evaluation = window.WayfindingRouting.evaluateGpsQualitySamples(
            gpsQualityLockSamples,
            {
                requiredSamples: GPS_QUALITY_LOCK_REQUIRED_SAMPLES,
                maxAccuracy: GPS_QUALITY_LOCK_MAX_ACCURACY_M,
                maxSpread: GPS_QUALITY_LOCK_MAX_SPREAD_M,
            }
        );

        if (!evaluation.locked) {
            setGpsQualityLockDataset('waiting');

            return {
                ...evaluation,
                point: evaluation.point
                    ? L.latLng(evaluation.point.lat, evaluation.point.lng)
                    : sample.latLng,
            };
        }

        gpsQualityLockAcquired = true;
        setGpsQualityLockDataset('locked');

        return {
            ...evaluation,
            justLocked: true,
            point: L.latLng(evaluation.point.lat, evaluation.point.lng),
        };
    }

    function showGpsQualityLockStatus(lockResult) {
        const accuracy = Math.round(Number(lockResult.accuracy || 999));
        const spread = Math.round(Number(lockResult.spread || 0));
        const progress = `${lockResult.sampleCount}/${GPS_QUALITY_LOCK_REQUIRED_SAMPLES}`;
        let message = `Hold still while GPS collects ${GPS_QUALITY_LOCK_REQUIRED_SAMPLES} reliable readings.`;

        if (lockResult.reason === 'accuracy') {
            message = `Current accuracy is ${accuracy}m. GPS needs ${GPS_QUALITY_LOCK_MAX_ACCURACY_M}m or better for four consecutive readings.`;
        } else if (lockResult.reason === 'spread') {
            message = `Readings are spread across ${spread}m. Keep still until they remain within ${GPS_QUALITY_LOCK_MAX_SPREAD_M}m.`;
        } else if (lockResult.sampleCount > 0) {
            message = `Accuracy is ${accuracy}m and spread is ${spread}m. Keep the phone still for a stable lock.`;
        }

        setLiveGpsStatus('loading', `GPS quality lock (${progress})`, message);
        setRouteResultLabel(`Calibrating GPS: ${progress} stable readings.`);
    }

    function toLiveLatLng(point) {
        if (!point) return null;
        if (point instanceof L.LatLng) return L.latLng(Number(point.lat), Number(point.lng));
        if (Array.isArray(point) && point.length >= 2) return L.latLng(Number(point[0]), Number(point[1]));
        if (typeof point === 'object' && point.lat !== undefined && point.lng !== undefined) {
            return L.latLng(Number(point.lat), Number(point.lng));
        }
        return null;
    }

    function makeLiveGpsIcon() {
        return L.divIcon({
            className: 'live-gps-marker',
            html: `
                <div class="live-gps-dot-wrap">
                    <div class="live-gps-pulse"></div>
                    <div class="live-gps-heading-arrow" aria-hidden="true"></div>
                    <div class="live-gps-dot"></div>
                </div>
            `,
            iconSize: [34, 34],
            iconAnchor: [17, 17]
        });
    }

    function makeGpsStartIcon() {
        return L.divIcon({
            className: 'gps-route-start-marker',
            html: `<div class="gps-route-start-pin">GPS</div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 17]
        });
    }

    function ensureLiveGpsStatus() {
        if (liveGpsStatusEl) return liveGpsStatusEl;

        liveGpsStatusEl = document.createElement('div');
        liveGpsStatusEl.id = 'live-gps-status-card';
        liveGpsStatusEl.className = 'live-gps-status-card';
        liveGpsStatusEl.innerHTML = `
            <div class="live-gps-status-top">
                <div>
                    <div class="live-gps-status-kicker">Outdoor Live GPS</div>
                    <div class="live-gps-status-title" id="live-gps-status-title">Getting location...</div>
                </div>
                <button type="button" class="live-gps-status-x" onclick="stopOutdoorLiveGpsTracking()">×</button>
            </div>
            <div class="live-gps-status-text" id="live-gps-status-text">Please wait for a better GPS reading.</div>
            <div class="live-gps-guidance" id="live-gps-guidance" hidden>
                <div class="live-gps-guidance-arrow" id="live-gps-guidance-arrow">↑</div>
                <div class="live-gps-guidance-copy">
                    <div class="live-gps-guidance-title" id="live-gps-guidance-title">Continue straight</div>
                    <div class="live-gps-guidance-meta" id="live-gps-guidance-meta"></div>
                </div>
            </div>
            <div class="live-gps-status-actions">
                <button type="button" class="live-gps-mini-btn" onclick="toggleOutdoorLiveGpsFollow()" id="live-gps-follow-btn">Follow: ON</button>
                <button type="button" class="live-gps-mini-btn ghost" onclick="selectPickPathMode()">Tap My Location</button>
                <button type="button" class="live-gps-mini-btn ghost" onclick="openGpsDiagnostics()">GPS Details</button>
            </div>
        `;
        document.body.appendChild(liveGpsStatusEl);
        return liveGpsStatusEl;
    }

    function setLiveGpsStatus(kind, title, text) {
        const el = ensureLiveGpsStatus();
        el.classList.remove('good', 'weak', 'bad', 'loading');
        el.classList.add(kind || 'loading');

        const titleEl = document.getElementById('live-gps-status-title');
        const textEl = document.getElementById('live-gps-status-text');
        const followBtn = document.getElementById('live-gps-follow-btn');

        if (titleEl) titleEl.textContent = title || 'Outdoor Live GPS';
        if (textEl) textEl.textContent = text || '';
        if (followBtn) followBtn.textContent = liveGpsFollow ? 'Follow: ON' : 'Follow: OFF';

        window.WayfindingNavigationUi?.updateGpsStatus(
            kind || 'loading',
            title || 'Outdoor Live GPS',
            text || ''
        );
    }

    function setLiveGpsGuidance(instruction = null) {
        if (instruction) {
            window.WayfindingNavigationUi?.updateGuidance(instruction);
        }

        const guidanceEl = document.getElementById('live-gps-guidance');
        const titleEl = document.getElementById('live-gps-guidance-title');
        const metaEl = document.getElementById('live-gps-guidance-meta');
        const arrowEl = document.getElementById('live-gps-guidance-arrow');

        if (!guidanceEl) return;

        if (!instruction) {
            guidanceEl.hidden = true;
            return;
        }

        guidanceEl.hidden = false;
        if (titleEl) titleEl.textContent = instruction.title || 'Continue on route';
        if (metaEl) metaEl.textContent = instruction.meta || '';
        if (arrowEl) {
            arrowEl.textContent = instruction.symbol || '↑';
            arrowEl.style.transform = `rotate(${Number(instruction.arrowBearing || 0)}deg)`;
        }
    }

    function updateLiveMarkerHeading(heading) {
        if (!liveGpsMarker || !Number.isFinite(Number(heading))) return;

        const markerElement = liveGpsMarker.getElement?.();
        const arrow = markerElement?.querySelector('.live-gps-heading-arrow');

        if (arrow) {
            arrow.style.transform = `translateX(-50%) rotate(${Number(heading)}deg)`;
        }
    }

    function hideLiveGpsStatus() {
        if (liveGpsStatusEl) {
            liveGpsStatusEl.remove();
            liveGpsStatusEl = null;
        }

        window.WayfindingNavigationUi?.clearGpsStatus();
    }

    function removeLiveGpsMarkerOnly() {
        if (liveGpsMarker) {
            map.removeLayer(liveGpsMarker);
            liveGpsMarker = null;
        }
    }

    function clearGpsStartOnly() {
        if (startSourceType && String(startSourceType).includes('gps')) {
            clearStartMarker();
            startNodeKey = null;
            startSourceType = null;
            updateRouteLabels();
        }
    }

    function closestPointOnSegmentMeters(p, a, b) {
        p = toLiveLatLng(p);
        a = toLiveLatLng(a);
        b = toLiveLatLng(b);
        if (!p || !a || !b) return null;

        const px = p.lng, py = p.lat;
        const ax = a.lng, ay = a.lat;
        const bx = b.lng, by = b.lat;
        const dx = bx - ax;
        const dy = by - ay;

        if (dx === 0 && dy === 0) return a;

        let t = ((px - ax) * dx + (py - ay) * dy) / (dx * dx + dy * dy);
        t = Math.max(0, Math.min(1, t));

        return L.latLng(ay + (t * dy), ax + (t * dx));
    }

    function flattenLiveLatLngs(latlngs, out = []) {
        if (!Array.isArray(latlngs)) return out;
        latlngs.forEach(item => {
            if (Array.isArray(item)) {
                flattenLiveLatLngs(item, out);
            } else {
                const ll = toLiveLatLng(item);
                if (ll && Number.isFinite(ll.lat) && Number.isFinite(ll.lng)) out.push(ll);
            }
        });
        return out;
    }

    function extractSegmentsFromPolylineLayer(layer, segments = []) {
        if (!layer) return segments;

        if (String(layer.options?.className || '').includes('route-line-outline')) {
            return segments;
        }

        if (typeof layer.eachLayer === 'function') {
            layer.eachLayer(child => extractSegmentsFromPolylineLayer(child, segments));
            return segments;
        }

        if (typeof layer.getLatLngs === 'function') {
            const points = flattenLiveLatLngs(layer.getLatLngs());
            for (let i = 0; i < points.length - 1; i++) {
                segments.push({ a: points[i], b: points[i + 1], source: 'active_route' });
            }
        }

        return segments;
    }

    function getActiveRouteSegments() {
        const segments = [];
        if (typeof routeLayer !== 'undefined' && routeLayer) {
            extractSegmentsFromPolylineLayer(routeLayer, segments);
        }
        return segments;
    }

    function getCampusPathSegments() {
        const features = pathGeojson?.features || [];
        const count = features.length;

        if (cachedCampusSegments && cachedCampusSegmentCount === count) {
            return cachedCampusSegments;
        }

        const segments = [];
        features.forEach(feature => {
            const geometry = feature?.geometry;
            if (!geometry) return;

            if (geometry.type === 'LineString') {
                const coords = geometry.coordinates || [];
                for (let i = 0; i < coords.length - 1; i++) {
                    segments.push({
                        a: L.latLng(Number(coords[i][1]), Number(coords[i][0])),
                        b: L.latLng(Number(coords[i + 1][1]), Number(coords[i + 1][0])),
                        source: 'campus_path',
                        feature
                    });
                }
            }

            if (geometry.type === 'MultiLineString') {
                (geometry.coordinates || []).forEach(line => {
                    for (let i = 0; i < line.length - 1; i++) {
                        segments.push({
                            a: L.latLng(Number(line[i][1]), Number(line[i][0])),
                            b: L.latLng(Number(line[i + 1][1]), Number(line[i + 1][0])),
                            source: 'campus_path',
                            feature
                        });
                    }
                });
            }
        });

        cachedCampusSegments = segments;
        cachedCampusSegmentCount = count;
        return segments;
    }

    function snapToSegments(gpsLatLng, segments, maxDistanceMeters) {
        gpsLatLng = toLiveLatLng(gpsLatLng);
        if (!gpsLatLng || !segments || !segments.length) {
            return { point: gpsLatLng, distance: Infinity, snapped: false, source: 'raw_gps' };
        }

        let best = null;
        let bestDistance = Infinity;
        let bestSource = 'raw_gps';

        segments.forEach(segment => {
            const candidate = closestPointOnSegmentMeters(gpsLatLng, segment.a, segment.b);
            if (!candidate) return;

            const distance = map.distance(gpsLatLng, candidate);
            if (distance < bestDistance) {
                bestDistance = distance;
                best = candidate;
                bestSource = segment.source || 'path';
            }
        });

        if (best && bestDistance <= maxDistanceMeters) {
            return { point: best, distance: bestDistance, snapped: true, source: bestSource };
        }

        return { point: gpsLatLng, distance: bestDistance, snapped: false, source: 'raw_gps' };
    }

    function snapOutdoorGps(gpsLatLng, accuracy) {
        const activeSnapRadius = getGpsActiveRouteSnapRadiusMeters(accuracy);
        const pathSnapRadius = getGpsSnapRadiusMeters(accuracy);

        const activeRouteSnap = snapToSegments(gpsLatLng, getActiveRouteSegments(), activeSnapRadius);
        if (activeRouteSnap.snapped) {
            return {
                ...activeRouteSnap,
                activeRouteDistance: activeRouteSnap.distance,
                allowedDistance: activeSnapRadius,
                accuracy: Number(accuracy || 999)
            };
        }

        const campusPathSnap = snapToSegments(gpsLatLng, getCampusPathSegments(), pathSnapRadius);
        if (campusPathSnap.snapped) {
            return {
                ...campusPathSnap,
                activeRouteDistance: activeRouteSnap.distance,
                allowedDistance: pathSnapRadius,
                accuracy: Number(accuracy || 999)
            };
        }

        const nearestDistance = Math.min(activeRouteSnap.distance || Infinity, campusPathSnap.distance || Infinity);
        return {
            point: gpsLatLng,
            distance: nearestDistance,
            activeRouteDistance: activeRouteSnap.distance,
            allowedDistance: pathSnapRadius,
            snapped: false,
            source: 'raw_gps',
            accuracy: Number(accuracy || 999)
        };
    }

    function smoothGpsPoint(oldPoint, newPoint, factor = GPS_SMOOTH_FACTOR) {
        oldPoint = toLiveLatLng(oldPoint);
        newPoint = toLiveLatLng(newPoint);
        if (!oldPoint) return newPoint;
        if (!newPoint) return oldPoint;

        return L.latLng(
            oldPoint.lat + ((newPoint.lat - oldPoint.lat) * factor),
            oldPoint.lng + ((newPoint.lng - oldPoint.lng) * factor)
        );
    }

    function formatNavigationDistance(distance) {
        const meters = Math.max(0, Number(distance || 0));

        if (meters >= 1000) {
            return `${(meters / 1000).toFixed(meters >= 10000 ? 0 : 1)} km`;
        }

        return `${Math.round(meters)} m`;
    }

    function clearLiveNavigationState() {
        activeOutdoorRouteResult = null;
        activeOutdoorDestinationKey = null;
        lastLiveRerouteAt = 0;
        lastLiveRerouteLatLng = null;
        lastLiveRerouteNodeKey = null;
        consecutiveOffRouteReadings = 0;
        setLiveGpsGuidance(null);
    }

    function rememberOutdoorRoute(result) {
        if (!result?.path || result.path.length < 2) return;

        activeOutdoorRouteResult = result;
        activeOutdoorDestinationKey = result.path[result.path.length - 1];
        consecutiveOffRouteReadings = 0;
    }

    function getRouteProgress(position) {
        if (!activeOutdoorRouteResult?.path || activeOutdoorRouteResult.path.length < 2) {
            return null;
        }

        const points = activeOutdoorRouteResult.path
            .map(key => toLiveLatLng(parseCoordKey(key)))
            .filter(Boolean);

        if (points.length < 2) return null;

        const segmentLengths = [];
        let totalDistance = 0;

        for (let index = 0; index < points.length - 1; index++) {
            const length = map.distance(points[index], points[index + 1]);
            segmentLengths.push(length);
            totalDistance += length;
        }

        let best = null;
        let distanceBeforeSegment = 0;

        for (let index = 0; index < points.length - 1; index++) {
            const projected = closestPointOnSegmentMeters(position, points[index], points[index + 1]);
            if (!projected) {
                distanceBeforeSegment += segmentLengths[index];
                continue;
            }

            const offRouteDistance = map.distance(position, projected);
            const distanceAlongSegment = map.distance(points[index], projected);

            if (!best || offRouteDistance < best.offRouteDistance) {
                best = {
                    points,
                    segmentIndex: index,
                    projected,
                    offRouteDistance,
                    distanceToNextVertex: map.distance(projected, points[index + 1]),
                    travelledDistance: distanceBeforeSegment + distanceAlongSegment,
                    totalDistance,
                };
            }

            distanceBeforeSegment += segmentLengths[index];
        }

        if (!best) return null;

        best.remainingDistance = Math.max(0, best.totalDistance - best.travelledDistance);
        best.destinationDistance = map.distance(position, points[points.length - 1]);

        return best;
    }

    function buildNavigationInstruction(position) {
        const progress = getRouteProgress(position);
        if (!progress) return null;

        if (
            progress.remainingDistance <= GPS_ARRIVAL_DISTANCE_M
            || progress.destinationDistance <= GPS_ARRIVAL_DISTANCE_M
        ) {
            return {
                kind: 'arrived',
                title: 'You have arrived',
                meta: 'You are now at the destination.',
                symbol: '✓',
                arrowBearing: 0,
                remainingDistance: 0,
            };
        }

        const index = progress.segmentIndex;
        const currentSegmentBearing = window.WayfindingRouting.bearingBetween(
            progress.points[index],
            progress.points[index + 1]
        );
        let turn = { type: 'straight' };
        let arrowBearing = currentSegmentBearing;
        let title = 'Continue straight';
        let instructionDistance = progress.remainingDistance;
        let nextTurnBearing = null;
        let distanceToTurn = progress.distanceToNextVertex;

        for (let vertexIndex = index + 1; vertexIndex < progress.points.length - 1; vertexIndex++) {
            const incomingBearing = window.WayfindingRouting.bearingBetween(
                progress.points[vertexIndex - 1],
                progress.points[vertexIndex]
            );
            const outgoingBearing = window.WayfindingRouting.bearingBetween(
                progress.points[vertexIndex],
                progress.points[vertexIndex + 1]
            );
            const candidateTurn = window.WayfindingRouting.relativeTurn(
                incomingBearing,
                outgoingBearing
            );

            if (candidateTurn.type !== 'straight') {
                turn = candidateTurn;
                nextTurnBearing = outgoingBearing;
                instructionDistance = distanceToTurn;
                break;
            }

            distanceToTurn += map.distance(
                progress.points[vertexIndex],
                progress.points[vertexIndex + 1]
            );
        }

        if (turn.type !== 'straight' && instructionDistance <= GPS_TURN_NOTICE_DISTANCE_M) {
            if (turn.type === 'left') title = 'Turn left';
            if (turn.type === 'right') title = 'Turn right';
            if (turn.type === 'u_turn') title = 'Make a U-turn';
            arrowBearing = nextTurnBearing;
        } else {
            turn = { type: 'straight' };
            instructionDistance = Math.min(instructionDistance, progress.remainingDistance);
        }

        const actionDistance = Math.min(
            progress.remainingDistance,
            Math.max(0, instructionDistance)
        );

        return {
            kind: turn.type,
            title,
            meta: `${formatNavigationDistance(actionDistance)} to the next instruction • ${formatNavigationDistance(progress.remainingDistance)} remaining`,
            symbol: '↑',
            arrowBearing,
            remainingDistance: progress.remainingDistance,
            offRouteDistance: progress.offRouteDistance,
        };
    }

    function updateNavigationGuidance(position) {
        const instruction = buildNavigationInstruction(position);
        setLiveGpsGuidance(instruction);

        if (!instruction) return;

        if (
            window.WayfindingNavigationUi
            && !window.WayfindingNavigationUi.isNavigationStarted()
        ) {
            setRouteResultLabel('Route preview ready. Start navigation when you are ready to walk.');
            return;
        }

        if (instruction.kind === 'arrived') {
            setLiveGpsStatus('good', 'Destination reached', 'Continue to the indicated entrance or room marker for an indoor destination.');
            setRouteResultLabel('Destination reached.');
            return;
        }

        setRouteResultLabel(`${instruction.title}. ${instruction.meta}`);
    }

    function refreshActiveRouteFromGps(position, force = false) {
        if (!activeOutdoorDestinationKey || !position) return false;

        /*
        | Manual map dragging must stay responsive. GPS samples are still accepted,
        | but the route SVG is redrawn only after the finger is released.
        */
        if (gpsMapDragActive && !force) {
            pendingGpsRouteRefreshPosition = L.latLng(position.lat, position.lng);
            updateNavigationGuidance(position);
            return false;
        }

        const currentNodeKey = nearestNodeKey(position.lat, position.lng);
        if (!currentNodeKey) return false;

        startNodeKey = currentNodeKey;
        const now = Date.now();
        const movedDistance = lastLiveRerouteLatLng
            ? map.distance(lastLiveRerouteLatLng, position)
            : Infinity;

        if (
            !force
            && currentNodeKey === lastLiveRerouteNodeKey
            && movedDistance < GPS_REROUTE_MIN_MOVE_M
        ) {
            updateNavigationGuidance(position);
            return false;
        }

        if (!force && now - lastLiveRerouteAt < GPS_REROUTE_INTERVAL_MS) {
            updateNavigationGuidance(position);
            return false;
        }

        if (currentNodeKey === activeOutdoorDestinationKey) {
            updateNavigationGuidance(position);
            return true;
        }

        const refreshedRoute = dijkstra(currentNodeKey, activeOutdoorDestinationKey);
        if (!refreshedRoute) {
            setLiveGpsStatus(
                'weak',
                'Recalculating route...',
                'You moved away from the current route. Finding a new connected path.'
            );
            return false;
        }

        lastLiveRerouteAt = now;
        lastLiveRerouteLatLng = L.latLng(position.lat, position.lng);
        lastLiveRerouteNodeKey = currentNodeKey;

        drawOutdoorRoute(refreshedRoute, {
            fitBounds: false,
            liveUpdate: true,
        });
        updateNavigationGuidance(position);

        return true;
    }

    function pauseGpsFollowForManualDrag() {
        gpsMapDragActive = true;

        if (liveGpsWatchId === null || !liveGpsFollow) {
            return;
        }

        liveGpsFollow = false;
        emitGpsDiagnostic('state', {
            status: 'follow_paused_by_drag',
            qualityLocked: gpsQualityLockAcquired,
            accepted: liveGpsHasAcceptedFix,
        });
        setLiveGpsStatus(
            'loading',
            'Map follow paused',
            'You moved the map manually. GPS tracking continues; tap Follow: OFF to recenter and resume map follow.'
        );
    }

    function resumeDeferredGpsRouteRefresh() {
        gpsMapDragActive = false;

        if (!pendingGpsRouteRefreshPosition) {
            return;
        }

        const deferredPosition = pendingGpsRouteRefreshPosition;
        pendingGpsRouteRefreshPosition = null;

        requestAnimationFrame(() => {
            refreshActiveRouteFromGps(deferredPosition, true);
        });
    }

    if (typeof map !== 'undefined' && map) {
        map.on('dragstart', pauseGpsFollowForManualDrag);
        map.on('dragend', resumeDeferredGpsRouteRefresh);
    }

    function collectGpsSample(position) {
        const sample = {
            latLng: L.latLng(Number(position.coords.latitude), Number(position.coords.longitude)),
            accuracy: Number(position.coords.accuracy || 999),
            heading: position.coords.heading === null
                || position.coords.heading === undefined
                ? null
                : Number(position.coords.heading),
            speed: position.coords.speed === null
                || position.coords.speed === undefined
                ? null
                : Number(position.coords.speed),
            altitude: position.coords.altitude === null
                || position.coords.altitude === undefined
                ? null
                : Number(position.coords.altitude),
            time: Number(position.timestamp || Date.now())
        };

        liveGpsSamples.push(sample);
        liveGpsSamples = liveGpsSamples.slice(-6);
        return sample;
    }

    function updateRawGpsAccuracyCircle(rawLatLng, accuracy) {
        if (!rawLatLng) return;

        if (!liveGpsAccuracyCircle) {
            liveGpsAccuracyCircle = L.circle(rawLatLng, {
                radius: accuracy || 15,
                color: '#f59e0b',
                weight: 1,
                fillColor: '#fbbf24',
                fillOpacity: 0.07,
                opacity: 0.34,
                interactive: false
            }).addTo(map);
        } else {
            liveGpsAccuracyCircle.setStyle({
                color: '#f59e0b',
                fillColor: '#fbbf24',
                fillOpacity: 0.07,
                opacity: 0.34
            });
            liveGpsAccuracyCircle.setLatLng(rawLatLng);
            liveGpsAccuracyCircle.setRadius(accuracy || 15);
        }
    }

    function updateLiveGpsVisual(rawLatLng, displayLatLng, accuracy, heading = null) {
        if (!liveGpsMarker) {
            liveGpsMarker = L.marker(displayLatLng, {
                icon: makeLiveGpsIcon(),
                zIndexOffset: 99999,
                interactive: true
            }).addTo(map).bindPopup('Your live GPS location');
        } else {
            liveGpsMarker.setLatLng(displayLatLng);
        }

        updateLiveMarkerHeading(heading);

        if (!liveGpsAccuracyCircle) {
            liveGpsAccuracyCircle = L.circle(rawLatLng, {
                radius: accuracy || 15,
                color: '#2563eb',
                weight: 1,
                fillColor: '#3b82f6',
                fillOpacity: 0.08,
                opacity: 0.35,
                interactive: false
            }).addTo(map);
        } else {
            liveGpsAccuracyCircle.setStyle({
                color: '#2563eb',
                fillColor: '#3b82f6',
                fillOpacity: 0.08,
                opacity: 0.35
            });
            liveGpsAccuracyCircle.setLatLng(rawLatLng);
            liveGpsAccuracyCircle.setRadius(accuracy || 15);
        }
    }

    function updateGpsRouteStart(snappedLatLng, sourceText) {
        if (!snappedLatLng || !isInsideCampus(snappedLatLng.lat, snappedLatLng.lng)) {
            const fallback = entryPoints?.[0];
            if (!fallback) return false;

            const gatewayLat = Number(fallback.latitude);
            const gatewayLng = Number(fallback.longitude);
            const nearestKey = nearestNodeKey(gatewayLat, gatewayLng);
            if (!nearestKey) return false;

            startNodeKey = nearestKey;
            startSourceType = 'gps_outside_campus';
            drawOutsideGuideLine(snappedLatLng.lat, snappedLatLng.lng, gatewayLat, gatewayLng);
            updateRouteLabels();
            return true;
        }

        const nearestKey = nearestNodeKey(snappedLatLng.lat, snappedLatLng.lng);
        if (!nearestKey) return false;

        startNodeKey = nearestKey;
        startSourceType = sourceText || 'live_gps_snapped';

        if (!startMarker) {
            startMarker = L.marker(snappedLatLng, {
                icon: makeGpsStartIcon(),
                zIndexOffset: 99990,
                interactive: false
            }).addTo(map);
        } else {
            startMarker.setLatLng(snappedLatLng);
            startMarker.setIcon(makeGpsStartIcon());
        }

        clearOutsideGuideLine();
        updateRouteLabels();
        return true;
    }

    function stopOutdoorLiveGpsWatchOnly() {
        clearOutdoorGpsWatch();

        liveGpsSamples = [];
        resetGpsQualityLock();
        lastRawGpsLatLng = null;
        lastRawGpsTime = null;
        lastSmoothGpsLatLng = null;
        lastSnappedGpsLatLng = null;
        lastNavigationPosition = null;
        lastMovementHeading = null;
        liveGpsHasAcceptedFix = false;
        liveGpsFollow = false;
    }

    function activateTapMyLocationFallback(title, message, rawLatLng = null, accuracy = null) {
        if (rawLatLng && accuracy !== null) {
            updateRawGpsAccuracyCircle(rawLatLng, accuracy);
        }

        stopOutdoorLiveGpsWatchOnly();
        removeLiveGpsMarkerOnly();
        clearGpsStartOnly();

        placingStartMode = true;
        selectedStartMode = 'path';
        startSourceType = 'path';

        if (typeof setActiveStartModeButton === 'function') {
            setActiveStartModeButton('path');
        }

        if (typeof showPickPathHelper === 'function') {
            showPickPathHelper();
        }

        if (typeof updatePickPathHelperText === 'function') {
            updatePickPathHelperText();
        }

        const fallbackTitle = title || 'Tap My Location enabled';
        const fallbackMessage = message || 'GPS is not accurate enough. Tap your actual current walkway on the map to set an exact start point.';

        setLiveGpsStatus('bad', fallbackTitle, fallbackMessage);
        if (typeof setRouteResultLabel === 'function') {
            setRouteResultLabel(fallbackMessage);
        }
        if (typeof updateRouteLabels === 'function') {
            updateRouteLabels();
        }
    }

    function handleLiveGpsPosition(position) {
        if (!position || !position.coords) return;

        const latestSample = collectGpsSample(position);
        const qualityLock = evaluateGpsQualityLock(latestSample);
        let rawLatLng = qualityLock.point || latestSample.latLng;
        let accuracy = Number(qualityLock.accuracy || latestSample.accuracy || 999);
        const sampleTime = Number(position.timestamp || Date.now());
        const reportedHeading = position.coords.heading === null
            || position.coords.heading === undefined
            ? null
            : Number(position.coords.heading);

        if (!qualityLock.locked) {
            updateRawGpsAccuracyCircle(latestSample.latLng, latestSample.accuracy);
            updateLiveGpsVisual(
                latestSample.latLng,
                latestSample.latLng,
                latestSample.accuracy,
                null
            );
            emitGpsReading('calibrating', latestSample, qualityLock, {
                accepted: false,
                reason: qualityLock.reason || 'samples',
                snapRadius: getGpsSnapRadiusMeters(latestSample.accuracy),
            });
            showGpsQualityLockStatus(qualityLock);
            return;
        }

        const movementDistance = lastNavigationPosition
            ? map.distance(lastNavigationPosition, rawLatLng)
            : 0;

        if (lastNavigationPosition && movementDistance >= 1.5) {
            lastMovementHeading = window.WayfindingRouting.bearingBetween(
                lastNavigationPosition,
                rawLatLng
            );
        }

        const heading = Number.isFinite(reportedHeading) && reportedHeading >= 0
            ? reportedHeading
            : lastMovementHeading;

        lastNavigationPosition = rawLatLng;

        if (accuracy > GPS_REJECT_ACCURACY_M) {
            updateRawGpsAccuracyCircle(rawLatLng, accuracy);
            emitGpsReading('weak_accuracy', latestSample, qualityLock, {
                accepted: false,
                reason: 'accuracy',
                snapRadius: getGpsSnapRadiusMeters(accuracy),
            });
            setLiveGpsStatus(
                'weak',
                `GPS weak (${Math.round(accuracy)}m)`,
                'Live tracking is still running, but this reading is too weak to move the route. Move to an open area or use Tap My Location.'
            );
            return;
        }

        if (lastRawGpsLatLng && lastRawGpsTime) {
            const jumpDistance = map.distance(lastRawGpsLatLng, rawLatLng);
            const elapsedSeconds = Math.max(1, (sampleTime - lastRawGpsTime) / 1000);
            const plausibleDistance = Math.max(
                35,
                (elapsedSeconds * GPS_BAD_JUMP_SPEED_MPS) + accuracy
            );

            if (jumpDistance > plausibleDistance && accuracy > GPS_STRONG_ACCURACY_M) {
                updateRawGpsAccuracyCircle(rawLatLng, accuracy);
                emitGpsReading('jump_rejected', latestSample, qualityLock, {
                    accepted: false,
                    reason: 'implausible_jump',
                    jumpDistance,
                    snapRadius: getGpsSnapRadiusMeters(accuracy),
                });
                setLiveGpsStatus(
                    'weak',
                    `GPS jump ignored (${Math.round(jumpDistance)}m)`,
                    'Live tracking remains active while the next reliable reading is requested.'
                );
                return;
            }
        }

        lastRawGpsLatLng = rawLatLng;
        lastRawGpsTime = sampleTime;

        const snapResult = snapOutdoorGps(rawLatLng, accuracy);
        const snappedLatLng = snapResult.point || null;
        const snapDistanceText = Number.isFinite(snapResult.distance) ? `${Math.round(snapResult.distance)}m from path` : 'no nearby path';
        const snapRadiusText = `${Math.round(snapResult.allowedDistance || getGpsSnapRadiusMeters(accuracy))}m snap radius`;
        const snapText = snapResult.snapped
            ? (snapResult.source === 'active_route' ? `locked to active route (${snapDistanceText}, ${snapRadiusText})` : `snapped to QGIS path (${snapDistanceText}, ${snapRadiusText})`)
            : `not close to any QGIS path (${snapDistanceText}, ${snapRadiusText})`;

        if (activeOutdoorDestinationKey && snapResult.source !== 'active_route') {
            const offRouteConfirmation = window.WayfindingRouting.nextGpsOffRouteConfirmation(
                consecutiveOffRouteReadings,
                false,
                GPS_OFF_ROUTE_CONFIRMATION_SAMPLES
            );
            consecutiveOffRouteReadings = offRouteConfirmation.count;

            if (!offRouteConfirmation.confirmed) {
                const activeDistance = Number.isFinite(snapResult.activeRouteDistance)
                    ? `${Math.round(snapResult.activeRouteDistance)}m from the active route`
                    : 'outside the active route snap area';

                updateRawGpsAccuracyCircle(rawLatLng, accuracy);
                updateLiveGpsVisual(
                    rawLatLng,
                    lastSmoothGpsLatLng || lastSnappedGpsLatLng || rawLatLng,
                    accuracy,
                    heading
                );
                emitGpsReading('off_route_confirming', latestSample, qualityLock, {
                    accepted: false,
                    reason: 'off_route_confirmation',
                    heading,
                    snapDistance: snapResult.distance,
                    activeRouteDistance: snapResult.activeRouteDistance,
                    snapRadius: snapResult.allowedDistance,
                    snapSource: snapResult.source,
                    offRouteCount: consecutiveOffRouteReadings,
                });
                setLiveGpsStatus(
                    'weak',
                    `Confirming route position (${consecutiveOffRouteReadings}/${GPS_OFF_ROUTE_CONFIRMATION_SAMPLES})`,
                    `This reading is ${activeDistance}. The route will change only after repeated confirmation.`
                );

                if (lastSnappedGpsLatLng) {
                    updateNavigationGuidance(lastSnappedGpsLatLng);
                }

                return;
            }
        } else {
            consecutiveOffRouteReadings = window.WayfindingRouting
                .nextGpsOffRouteConfirmation(
                    consecutiveOffRouteReadings,
                    true,
                    GPS_OFF_ROUTE_CONFIRMATION_SAMPLES
                )
                .count;
        }

        if (!snapResult.snapped || !snappedLatLng) {
            updateLiveGpsVisual(rawLatLng, rawLatLng, accuracy, heading);
            emitGpsReading('off_path', latestSample, qualityLock, {
                accepted: false,
                reason: 'outside_snap_radius',
                heading,
                snapDistance: snapResult.distance,
                activeRouteDistance: snapResult.activeRouteDistance,
                snapRadius: snapResult.allowedDistance,
                snapSource: snapResult.source,
            });
            setLiveGpsStatus(
                'weak',
                `GPS off path (${Math.round(accuracy)}m)`,
                `Nearest path is ${snapDistanceText}. Tracking continues; move closer to a walkway or use Tap My Location.`
            );
            return;
        }

        if (consecutiveOffRouteReadings >= GPS_OFF_ROUTE_CONFIRMATION_SAMPLES) {
            consecutiveOffRouteReadings = 0;
        }

        const smoothLatLng = liveGpsHasAcceptedFix
            ? smoothGpsPoint(lastSmoothGpsLatLng, snappedLatLng)
            : snappedLatLng;
        lastSmoothGpsLatLng = smoothLatLng;
        lastSnappedGpsLatLng = snappedLatLng;
        liveGpsHasAcceptedFix = true;

        updateLiveGpsVisual(rawLatLng, smoothLatLng, accuracy, heading);

        updateGpsRouteStart(snappedLatLng, 'live_gps_snapped');
        refreshActiveRouteFromGps(snappedLatLng);

        emitGpsReading('accepted', latestSample, qualityLock, {
            accepted: true,
            heading,
            snappedLat: Number(snappedLatLng.lat),
            snappedLng: Number(snappedLatLng.lng),
            snapDistance: snapResult.distance,
            activeRouteDistance: snapResult.activeRouteDistance,
            snapRadius: snapResult.allowedDistance,
            snapSource: snapResult.source,
        });

        setLiveGpsStatus(
            accuracy <= GPS_STRONG_ACCURACY_M ? 'good' : 'weak',
            activeOutdoorDestinationKey
                ? `Live navigation (${Math.round(accuracy)}m)`
                : `Live GPS ready (${Math.round(accuracy)}m)`,
            accuracy <= GPS_PREVIEW_ACCURACY_M
                ? `Your marker follows each trusted GPS update and is ${snapText}.`
                : `Your marker is following a path estimate. Accuracy is ${Math.round(accuracy)}m.`
        );

        if (activeOutdoorDestinationKey) {
            updateNavigationGuidance(snappedLatLng);
        } else {
            setRouteResultLabel(`Live GPS ready. Accuracy ${Math.round(accuracy)}m; ${snapText}.`);
        }

        if (liveGpsFollow && smoothLatLng && !gpsMapDragActive) {
            map.panTo(smoothLatLng, { animate: true, duration: 0.35 });
        }
    }

    function handleLiveGpsError(error) {
        console.error('Outdoor live GPS error:', error);

        let message = 'Unable to get GPS location.';
        if (error?.code === 1) message = 'GPS permission denied. Please allow location access.';
        if (error?.code === 2) message = 'GPS position unavailable. Move to an open area or use Tap My Location.';
        if (error?.code === 3) message = 'GPS timed out. Try again or use Tap My Location.';

        emitGpsDiagnostic('error', {
            status: 'gps_error',
            code: Number(error?.code || 0),
            message,
        });
        setLiveGpsStatus('bad', 'GPS unavailable', message);
        setRouteResultLabel(message);
    }

    function startOutdoorLiveGpsTracking() {
        selectedStartMode = 'gps';
        placingStartMode = false;
        hidePickPathHelper();
        setActiveStartModeButton('gps');

        const gpsProvider = getOutdoorGpsProvider();
        const usingSimulator = window.WAYFINDING_GPS_SIMULATOR_ENABLED === true
            && window.WayfindingGpsSimulator
            && gpsProvider === window.WayfindingGpsSimulator.geolocation;
        liveGpsProviderName = usingSimulator ? 'simulator' : 'device';

        if (!gpsProvider) {
            liveGpsProviderName = 'unavailable';
            emitGpsDiagnostic('error', {
                status: 'unsupported',
                message: 'Geolocation is not supported on this device/browser.',
            });
            alert('Geolocation is not supported on this device/browser. Please use Tap My Location.');
            return;
        }

        if (!usingSimulator && location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            alert('GPS requires HTTPS when deployed online. Please use HTTPS hosting.');
            return;
        }

        if (liveGpsWatchId !== null) {
            liveGpsFollow = true;
            setLiveGpsStatus('loading', 'Outdoor Live GPS already active', 'Follow mode enabled. If location is wrong, tap Tap My Location.');
            return;
        }

        liveGpsSamples = [];
        resetGpsQualityLock();
        lastRawGpsLatLng = null;
        lastRawGpsTime = null;
        lastSmoothGpsLatLng = null;
        lastSnappedGpsLatLng = null;
        lastNavigationPosition = null;
        lastMovementHeading = null;
        liveGpsHasAcceptedFix = false;
        liveGpsFollow = true;

        clearCurrentLocationMarker();
        clearStartMarker();
        removeLiveGpsMarkerOnly();

        setLiveGpsStatus(
            'loading',
            'Starting GPS quality lock...',
            `Hold the phone still. Waiting for ${GPS_QUALITY_LOCK_REQUIRED_SAMPLES} readings at ${GPS_QUALITY_LOCK_MAX_ACCURACY_M}m accuracy or better.`
        );
        setRouteResultLabel('Live GPS tracking started. Calibrating a stable location lock.');

        liveGpsProvider = gpsProvider;
        emitGpsDiagnostic('state', {
            status: 'tracking_started',
            qualityLocked: false,
            accepted: false,
        });
        liveGpsWatchId = gpsProvider.watchPosition(
            handleLiveGpsPosition,
            handleLiveGpsError,
            {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 18000
            }
        );
    }

    function stopOutdoorLiveGpsTracking(options = {}) {
        const wasTracking = liveGpsWatchId !== null;
        clearOutdoorGpsWatch();

        liveGpsSamples = [];
        resetGpsQualityLock();
        lastRawGpsLatLng = null;
        lastRawGpsTime = null;
        lastSmoothGpsLatLng = null;
        lastSnappedGpsLatLng = null;
        lastNavigationPosition = null;
        lastMovementHeading = null;
        liveGpsHasAcceptedFix = false;
        liveGpsFollow = false;

        if (liveGpsMarker) {
            map.removeLayer(liveGpsMarker);
            liveGpsMarker = null;
        }

        if (liveGpsAccuracyCircle) {
            map.removeLayer(liveGpsAccuracyCircle);
            liveGpsAccuracyCircle = null;
        }

        hideLiveGpsStatus();

        if (!options.keepStart && startSourceType && String(startSourceType).includes('gps')) {
            clearStartMarker();
            startNodeKey = null;
            startSourceType = null;
            updateRouteLabels();
        }

        if (wasTracking) {
            emitGpsDiagnostic('state', {
                status: 'tracking_stopped',
                accepted: false,
            });
        }
    }

    function toggleOutdoorLiveGpsFollow() {
        liveGpsFollow = !liveGpsFollow;
        emitGpsDiagnostic('state', {
            status: liveGpsFollow ? 'follow_enabled' : 'follow_paused',
            accepted: liveGpsHasAcceptedFix,
        });
        setLiveGpsStatus(
            liveGpsFollow ? 'good' : 'loading',
            liveGpsFollow ? 'Follow mode enabled' : 'Follow mode paused',
            liveGpsFollow ? 'The map will follow your accepted/snapped GPS dot.' : 'The dot will update only when GPS is trusted.'
        );

        if (liveGpsFollow && lastSmoothGpsLatLng) {
            map.panTo(lastSmoothGpsLatLng, { animate: true, duration: 0.35 });
        }
    }

    if (baseClearRouteLayer) {
        clearRouteLayer = function () {
            const result = baseClearRouteLayer.apply(this, arguments);

            if (!drawingLiveGpsReroute) {
                clearLiveNavigationState();
            }

            return result;
        };
    }

    if (baseDrawOutdoorRoute) {
        drawOutdoorRoute = function (result, options = {}) {
            const previousDestinationKey = activeOutdoorDestinationKey;
            drawingLiveGpsReroute = true;

            try {
                const rendered = baseDrawOutdoorRoute.call(this, result, options);
                rememberOutdoorRoute(result);

                if (previousDestinationKey !== activeOutdoorDestinationKey) {
                    lastLiveRerouteAt = 0;
                    lastLiveRerouteLatLng = null;
                    lastLiveRerouteNodeKey = null;
                }

                if (liveGpsHasAcceptedFix && lastSnappedGpsLatLng) {
                    updateNavigationGuidance(lastSnappedGpsLatLng);
                }

                return rendered;
            } finally {
                drawingLiveGpsReroute = false;
            }
        };
    }

    selectGpsMode = function () {
        /* AUTO LOCATE now starts continuous outdoor live tracking. */
        startOutdoorLiveGpsTracking();
    };

    selectPickPathMode = function () {
        stopOutdoorLiveGpsTracking({ keepStart: false });
        if (baseSelectPickPathMode) return baseSelectPickPathMode.apply(this, arguments);
    };

    selectDefaultMode = function () {
        stopOutdoorLiveGpsTracking({ keepStart: false });
        if (baseSelectDefaultMode) return baseSelectDefaultMode.apply(this, arguments);
    };

    if (baseResetRouteSelection) {
        resetRouteSelection = function () {
            stopOutdoorLiveGpsTracking({ keepStart: false });
            clearLiveNavigationState();
            return baseResetRouteSelection.apply(this, arguments);
        };
    }

    window.selectGpsMode = selectGpsMode;
    window.selectPickPathMode = selectPickPathMode;
    window.selectDefaultMode = selectDefaultMode;
    window.resetRouteSelection = resetRouteSelection;
    window.startOutdoorLiveGpsTracking = startOutdoorLiveGpsTracking;
    window.stopOutdoorLiveGpsTracking = stopOutdoorLiveGpsTracking;
    window.toggleOutdoorLiveGpsFollow = toggleOutdoorLiveGpsFollow;
    window.activateTapMyLocationFallback = activateTapMyLocationFallback;
    window.refreshActiveRouteFromGps = refreshActiveRouteFromGps;
    window.refreshOutdoorLiveGpsGuidance = function () {
        if (lastSnappedGpsLatLng) {
            updateNavigationGuidance(lastSnappedGpsLatLng);
        }
    };
    window.WayfindingGpsCalibration = Object.freeze({
        thresholds: GPS_CALIBRATION_THRESHOLDS,
        getState() {
            return {
                tracking: liveGpsWatchId !== null,
                provider: liveGpsProviderName,
                qualityLocked: gpsQualityLockAcquired,
                qualitySamples: gpsQualityLockSamples.length,
                acceptedFix: liveGpsHasAcceptedFix,
                follow: liveGpsFollow,
                offRouteCount: consecutiveOffRouteReadings,
                routeActive: Boolean(activeOutdoorDestinationKey),
                lastRawPosition: lastRawGpsLatLng
                    ? {
                        lat: Number(lastRawGpsLatLng.lat),
                        lng: Number(lastRawGpsLatLng.lng),
                    }
                    : null,
                lastSnappedPosition: lastSnappedGpsLatLng
                    ? {
                        lat: Number(lastSnappedGpsLatLng.lat),
                        lng: Number(lastSnappedGpsLatLng.lng),
                    }
                    : null,
            };
        },
    });

    if (window.WAYFINDING_GPS_SIMULATOR_ENABLED === true) {
        window.getWayfindingGpsQualityState = function () {
            return {
                locked: gpsQualityLockAcquired,
                sampleCount: gpsQualityLockSamples.length,
                requiredSamples: GPS_QUALITY_LOCK_REQUIRED_SAMPLES,
                maxAccuracy: GPS_QUALITY_LOCK_MAX_ACCURACY_M,
                maxSpread: GPS_QUALITY_LOCK_MAX_SPREAD_M,
                consecutiveOffRouteReadings,
                requiredOffRouteReadings: GPS_OFF_ROUTE_CONFIRMATION_SAMPLES,
            };
        };

        window.getWayfindingGpsSimulatorRoute = function () {
            if (!activeOutdoorRouteResult?.path || activeOutdoorRouteResult.path.length < 2) {
                return null;
            }

            const points = activeOutdoorRouteResult.path
                .map(key => toLiveLatLng(parseCoordKey(key)))
                .filter(Boolean)
                .map(point => ({
                    lat: Number(point.lat),
                    lng: Number(point.lng),
                }));

            if (points.length < 2) return null;

            return {
                destinationKey: activeOutdoorDestinationKey,
                points,
            };
        };
    }
})();
