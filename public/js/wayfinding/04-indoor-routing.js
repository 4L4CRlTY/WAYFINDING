    /* =========================================================
       INDOOR DISPLAY BLOCK RESTORED FROM OLD WORKING CODE
       Only the indoor visual/rendering block was restored.
       Outdoor routing and indoor Dijkstra routing below were not changed.
    ========================================================= */

    function debugIndoorGraphWarning(message, details) {
        if (window.WAYFINDING_DEBUG !== true) return;
        console.warn(message, details);
    }

    /*
    | Low-end phones should only have to transform one static floor surface
    | while a finger is moving. The normal renderer remains unchanged, while
    | Oppo-class sessions cache a floorplan with its rooms, paths, and entrance
    | dots already painted into it. Routing still uses the original GeoJSON.
    */
    const indoorLowEndSurfaceCache = new Map();
    let indoorLowEndSurfaceRequest = 0;
    let indoorLowEndRoomTapInstalled = false;
    let indoorLowEndFloorRooms = [];

    function isLowEndIndoorSurfaceMode() {
        return window.wayfindingRenderProfile?.mode === 'low';
    }

    function indoorPointInRing(lng, lat, ring) {
        let inside = false;
        for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
            const xi = Number(ring[i]?.[0]);
            const yi = Number(ring[i]?.[1]);
            const xj = Number(ring[j]?.[0]);
            const yj = Number(ring[j]?.[1]);
            if (![xi, yi, xj, yj].every(Number.isFinite)) continue;
            const intersects = ((yi > lat) !== (yj > lat))
                && (lng < ((xj - xi) * (lat - yi)) / ((yj - yi) || Number.EPSILON) + xi);
            if (intersects) inside = !inside;
        }
        return inside;
    }

    function indoorFeatureContainsLatLng(feature, latlng) {
        const geometry = feature?.geometry;
        if (!geometry || !latlng) return false;
        const polygons = geometry.type === 'Polygon'
            ? [geometry.coordinates]
            : (geometry.type === 'MultiPolygon' ? geometry.coordinates : []);

        return polygons.some(polygon => {
            if (!Array.isArray(polygon?.[0]) || !indoorPointInRing(latlng.lng, latlng.lat, polygon[0])) return false;
            return !polygon.slice(1).some(hole => indoorPointInRing(latlng.lng, latlng.lat, hole));
        });
    }

    function installIndoorLowEndRoomTap() {
        if (!indoorMap || indoorLowEndRoomTapInstalled) return;
        indoorLowEndRoomTapInstalled = true;
        indoorMap.on('click', event => {
            if (!isLowEndIndoorSurfaceMode() || indoorInteractionFlags.size) return;
            const room = indoorLowEndFloorRooms.find(feature => indoorFeatureContainsLatLng(feature, event.latlng));
            if (!room) return;

            selectedIndoorRoomFeature = room;
            renderIndoorRoomList();
            const p = room.properties || {};
            const safeName = String(p.name || 'Room').replace(/[&<>"']/g, character => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            })[character]);
            const safeCode = String(p.room_code || 'N/A').replace(/[&<>"']/g, character => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            })[character]);
            L.popup({ autoPan: false })
                .setLatLng(event.latlng)
                .setContent(`<div style="min-width:150px">
                    <strong>${safeName}</strong>
                    <div>Code: ${safeCode}</div>
                    <button type="button" onclick="window.routeToIndoorRoom(${Number(p.id)})"
                        style="margin-top:8px;padding:7px 10px;">
                        Route to this room
                    </button>
                </div>`)
                .openOn(indoorMap);
        });
    }

    function ensureIndoorMap() {
        if (indoorMap) return;

        const mobileIndoorView = window.matchMedia('(hover: none), (pointer: coarse), (max-width: 768px)').matches;
        const lowEndIndoorView = window.wayfindingRenderProfile?.mode === 'low';

        indoorMap = L.map('indoorMap', {
            zoomControl: true,
            /*
            |----------------------------------------------------------------------
            | MOBILE INDOOR ZOOM FIX
            |----------------------------------------------------------------------
            | Lower minZoom so the full indoor floor can zoom out on phones.
            */
            minZoom: 15,
            maxZoom: 24,
            preferCanvas: true,
            zoomSnap: mobileIndoorView ? 0 : 1,
            zoomDelta: mobileIndoorView ? 0.5 : 1,
            zoomAnimation: !mobileIndoorView,
            fadeAnimation: false,
            markerZoomAnimation: !mobileIndoorView,
            bounceAtZoomLimits: false,
            inertia: !lowEndIndoorView
        });

        /* Keep rooms, paths, and entrances in one Canvas surface on every
           phone. On low-end/high-DPR Android devices Leaflet normally creates
           a 2x backing store (four times as many pixels) for that full-floor
           canvas. The Oppo A16K does not have enough GPU bandwidth to transform
           that surface smoothly during every pinch frame, so use a 1x backing
           store only for the already-detected low profile. Geometry, hit
           testing, coordinates, routes, and GPS are unchanged. */
        indoorMap.__wayfindingVectorRenderer = createIndoorVectorRenderer(lowEndIndoorView);
        indoorMap.__wayfindingVectorRendererMode = 'canvas';
        indoorMap.__wayfindingVectorPixelRatio = lowEndIndoorView ? 1 : Math.min(2, window.devicePixelRatio || 1);

        installIndoorInteractionController();

        indoorMap.setView([10.2925, 124.9985], 20);

        requestAnimationFrame(() => {
            scheduleIndoorViewportFit({ reason: 'map-created' });
        });
    }

    function createIndoorVectorRenderer(lowEndIndoorView) {
        const options = {
            padding: lowEndIndoorView ? 0.02 : 0.1,
            tolerance: lowEndIndoorView ? 7 : 5
        };

        if (!lowEndIndoorView || !L.Canvas || !L.Renderer) {
            return L.canvas(options);
        }

        const LowResolutionIndoorCanvas = L.Canvas.extend({
            _update: function() {
                if (this._map._animatingZoom && this._bounds) return;

                L.Renderer.prototype._update.call(this);

                const bounds = this._bounds;
                const container = this._container;
                const size = bounds.getSize();

                L.DomUtil.setPosition(container, bounds.min);

                /* Deliberately keep CSS pixels and backing pixels 1:1. The
                   regular renderer remains retina-sharp on balanced/desktop
                   devices; only the low-end session receives this budget. */
                container.width = Math.max(1, Math.ceil(size.x));
                container.height = Math.max(1, Math.ceil(size.y));
                container.style.width = `${size.x}px`;
                container.style.height = `${size.y}px`;

                this._ctx.translate(-bounds.min.x, -bounds.min.y);
                this.fire('update');
            }
        });

        return new LowResolutionIndoorCanvas(options);
    }

    function getIndoorBuildingMaps(buildingId) {
        return allIndoorMaps
            .filter(m => Number(m.building_id) === Number(buildingId))
            .sort((a, b) => Number(a.floor_number) - Number(b.floor_number));
    }

    function getIndoorRoomsFor(buildingId, floorNumber = null) {
        return (allIndoorRooms.features || []).filter(f => {
            const p = f.properties || {};
            if (Number(p.building_id) !== Number(buildingId)) return false;
            if (floorNumber !== null && Number(p.floor_number) !== Number(floorNumber)) return false;
            return true;
        });
    }

    function getIndoorPathsFor(buildingId, floorNumber = null) {
        return (allIndoorPaths.features || []).filter(f => {
            const p = f.properties || {};
            if (Number(p.building_id) !== Number(buildingId)) return false;
            if (floorNumber !== null && Number(p.floor_number) !== Number(floorNumber)) return false;
            return true;
        });
    }

    function getIndoorEntrancesFor(buildingId, floorNumber = null) {
        return (allIndoorEntrances.features || []).filter(f => {
            const p = f.properties || {};
            if (Number(p.building_id) !== Number(buildingId)) return false;
            if (floorNumber !== null && Number(p.floor_number) !== Number(floorNumber)) return false;
            return true;
        });
    }

    function findIndoorEntranceFeatureById(id) {
        return (allIndoorEntrances.features || []).find(f => Number(f.properties?.id) === Number(id)) || null;
    }

    function drawIndoorGeometryPath(context, geometry, project) {
        const type = String(geometry?.type || '');
        const coordinates = geometry?.coordinates;
        if (!Array.isArray(coordinates)) return;

        context.beginPath();
        const drawLine = (line, closePath = false) => {
            if (!Array.isArray(line) || !line.length) return;
            line.forEach((coordinate, index) => {
                const point = project(coordinate);
                if (!point) return;
                context[index ? 'lineTo' : 'moveTo'](point.x, point.y);
            });
            if (closePath) context.closePath();
        };

        if (type === 'LineString') drawLine(coordinates);
        else if (type === 'MultiLineString') coordinates.forEach(line => drawLine(line));
        else if (type === 'Polygon') coordinates.forEach(ring => drawLine(ring, true));
        else if (type === 'MultiPolygon') coordinates.forEach(polygon => polygon.forEach(ring => drawLine(ring, true)));
    }

    function loadIndoorSurfaceImage(url) {
        return new Promise((resolve, reject) => {
            const image = new Image();
            image.decoding = 'async';
            image.addEventListener('load', () => resolve(image), { once: true });
            image.addEventListener('error', reject, { once: true });
            image.src = url;
        });
    }

    async function createLowEndIndoorSurface({ mapItem, floorplanUrl, bounds, rooms, paths, entrances }) {
        const cacheKey = [
            Number(mapItem.id || mapItem.building_id || 0),
            Number(mapItem.floor_number || 0),
            floorplanUrl,
            rooms.length,
            paths.length,
            entrances.length
        ].join('|');
        if (indoorLowEndSurfaceCache.has(cacheKey)) return indoorLowEndSurfaceCache.get(cacheKey);

        const surfacePromise = (async () => {
            const image = await loadIndoorSurfaceImage(floorplanUrl);
            const naturalWidth = Math.max(1, image.naturalWidth || image.width || 900);
            const naturalHeight = Math.max(1, image.naturalHeight || image.height || 600);
            /* Oppo-class phones have limited texture-upload bandwidth. An
               800px surface keeps room labels readable at the normal indoor
               camera while cutting the texture area by 36% versus 1000px.
               GeoJSON remains full precision and is still used for taps and
               routing; only the painted display surface is downsampled. */
            const maxDimension = 800;
            const scale = Math.min(1, maxDimension / Math.max(naturalWidth, naturalHeight));
            const width = Math.max(1, Math.round(naturalWidth * scale));
            const height = Math.max(1, Math.round(naturalHeight * scale));
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const context = canvas.getContext('2d');
            if (!context) throw new Error('Indoor surface canvas is unavailable.');
            context.drawImage(image, 0, 0, width, height);

            const west = bounds.getWest();
            const east = bounds.getEast();
            const south = bounds.getSouth();
            const north = bounds.getNorth();
            const project = coordinate => {
                const lng = Number(coordinate?.[0]);
                const lat = Number(coordinate?.[1]);
                if (!Number.isFinite(lng) || !Number.isFinite(lat)) return null;
                return {
                    x: ((lng - west) / (east - west)) * width,
                    y: ((north - lat) / (north - south)) * height
                };
            };
            const widthScale = Math.max(0.75, width / 900);

            paths.forEach(feature => {
                const type = String(feature.properties?.path_type || 'hallway').toLowerCase();
                context.save();
                context.strokeStyle = type.includes('stairs') ? '#f59e0b' : '#334155';
                context.lineWidth = (type.includes('stairs') ? 5 : 6) * widthScale;
                context.globalAlpha = 0.95;
                context.lineCap = 'round';
                context.lineJoin = 'round';
                if (type.includes('stairs')) context.setLineDash([6 * widthScale, 6 * widthScale]);
                drawIndoorGeometryPath(context, feature.geometry, project);
                context.stroke();
                context.restore();
            });

            rooms.forEach(feature => {
                const style = getIndoorRoomLayerStyle(feature);
                context.save();
                context.fillStyle = style.fillColor;
                context.strokeStyle = style.color;
                context.lineWidth = Math.max(1.5, style.weight * widthScale);
                drawIndoorGeometryPath(context, feature.geometry, project);
                context.globalAlpha = style.fillOpacity;
                context.fill('evenodd');
                context.globalAlpha = 1;
                context.stroke();
                context.restore();
            });

            entrances.forEach(feature => {
                const coordinate = feature.geometry?.coordinates;
                const point = project(coordinate);
                if (!point) return;
                const type = String(feature.properties?.ent_type || '').toLowerCase();
                let color = '#ef4444';
                if (type.includes('main')) color = '#16a34a';
                else if (type.includes('stairs')) color = '#f59e0b';
                else if (type.includes('door')) color = '#7c3aed';
                else if (type.includes('side')) color = '#0ea5e9';
                context.beginPath();
                context.arc(point.x, point.y, 6 * widthScale, 0, Math.PI * 2);
                context.fillStyle = color;
                context.fill();
                context.strokeStyle = '#ffffff';
                context.lineWidth = 2 * widthScale;
                context.stroke();
            });

            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', 0.86));
            return blob ? URL.createObjectURL(blob) : canvas.toDataURL('image/png');
        })().catch(error => {
            indoorLowEndSurfaceCache.delete(cacheKey);
            throw error;
        });

        indoorLowEndSurfaceCache.set(cacheKey, surfacePromise);
        return surfacePromise;
    }

    function loadIndoorFloorImage({ rooms = [], paths = [], entrances = [] } = {}) {
        const mapItem = allIndoorMaps.find(m =>
            Number(m.building_id) === Number(currentIndoorBuildingId) &&
            Number(m.floor_number) === Number(currentIndoorFloor)
        );

        if (indoorImageLayer) {
            indoorMap.removeLayer(indoorImageLayer);
            indoorImageLayer = null;
        }

        clearIndoorGeometryDebug();

        if (!mapItem || !mapItem.floorplan_image) return;

        const bounds = getIndoorMapBoundsFromGeometry(mapItem.geometry);
        if (!bounds) return;

        const originalFloorplanUrl = mapItem.floorplan_image;
        let floorplanUrl = originalFloorplanUrl;
        let balancedMobileFloorplanUrl = null;
        const mobileFloorplan = window.matchMedia('(max-width: 768px), (pointer: coarse)').matches;

        if (mobileFloorplan && /^\/floorplan_image\//.test(originalFloorplanUrl)) {
            try {
                const originalName = decodeURIComponent(originalFloorplanUrl.split('/').pop() || '');
                const optimizedName = originalName.replace(/\.[^.]+$/, '') + '.webp';
                balancedMobileFloorplanUrl = `/floorplan_image/mobile/${encodeURIComponent(optimizedName)}`;
                floorplanUrl = window.wayfindingRenderProfile?.mode === 'low'
                    ? `/floorplan_image/mobile-low/${encodeURIComponent(optimizedName)}`
                    : balancedMobileFloorplanUrl;
            } catch (_) {
                floorplanUrl = originalFloorplanUrl;
            }
        }

        const requestId = ++indoorLowEndSurfaceRequest;
        indoorImageLayer = L.imageOverlay(floorplanUrl, bounds, {
            opacity: 1,
            interactive: false
        }).addTo(indoorMap);

        if (floorplanUrl !== originalFloorplanUrl) {
            const image = indoorImageLayer.getElement?.();
            image?.addEventListener('error', () => {
                if (indoorImageLayer?.getElement?.() === image) {
                    const fallbackUrl = floorplanUrl !== balancedMobileFloorplanUrl
                        ? balancedMobileFloorplanUrl
                        : originalFloorplanUrl;
                    indoorImageLayer.setUrl(fallbackUrl || originalFloorplanUrl);
                }
            }, { once: true });
        }

        indoorImageLayer.bringToBack();

        if (isLowEndIndoorSurfaceMode()) {
            createLowEndIndoorSurface({ mapItem, floorplanUrl, bounds, rooms, paths, entrances })
                .then(surfaceUrl => {
                    if (requestId !== indoorLowEndSurfaceRequest || !indoorImageLayer) return;
                    indoorImageLayer.setUrl(surfaceUrl);
                })
                .catch(error => debugIndoorGraphWarning('Low-end indoor surface fallback was used.', error));
        }

        // Optional debug outline, set fillOpacity 0
        indoorGeometryDebugLayer = getIndoorMapGeometryLayer(mapItem.geometry, {
            style: {
                color: '#7c3aed',
                weight: 1.5,
                opacity: 0.55,
                fillOpacity: 0
            }
        });

        // Uncomment if you want to see the rectangle/polygon bounds
        // if (indoorGeometryDebugLayer) indoorGeometryDebugLayer.addTo(indoorMap);
    }

    function renderIndoorRoomList() {
        if (!currentIndoorBuildingId || !currentIndoorFloor) return;

        /* Focus mode hides this legacy sidebar. Do not build invisible room
           cards on every floor change; room polygons remain interactive. */
        const roomSidebar = roomList?.closest('.indoor-sidebar');
        if (roomList && roomSidebar && window.getComputedStyle(roomSidebar).display === 'none') {
            roomList.replaceChildren();
            return;
        }

        const keyword = (indoorRoomSearch.value || '').trim().toLowerCase();

        let rooms = getIndoorRoomsFor(currentIndoorBuildingId, currentIndoorFloor);

        if (keyword) {
            rooms = rooms.filter(room => {
                const p = room.properties || {};
                return String(p.name || '').toLowerCase().includes(keyword) ||
                    String(p.room_code || '').toLowerCase().includes(keyword) ||
                    String(p.type || '').toLowerCase().includes(keyword);
            });
        }

        rooms.sort((a, b) => String(a.properties?.name || '').localeCompare(String(b.properties?.name || '')));

        roomList.innerHTML = '';

        if (!rooms.length) {
            roomList.innerHTML =
                `<div style="padding:12px;font-size:12px;color:#64748b;">No rooms found on this floor.</div>`;
            return;
        }

        rooms.forEach(room => {
            const p = room.properties || {};
            const isActive = selectedIndoorRoomFeature &&
                Number(selectedIndoorRoomFeature.properties?.id) === Number(p.id);

            const div = document.createElement('div');
            div.className = `room-item ${isActive ? 'active' : ''}`;
            div.innerHTML = `
                <div class="room-name">${p.name || 'Room'}</div>
                <div class="room-meta">
                    ${p.room_code || 'No code'} • ${p.type || 'room'} • ${(typeof formatIndoorFloorLabel === 'function' ? formatIndoorFloorLabel(p.floor_number, p.floor_label) : (p.floor_label || p.floor_number || '-'))}
                </div>
            `;
            div.addEventListener('click', function() {
                selectedIndoorRoomFeature = room;
                renderIndoorRoomList();
                renderIndoorFloor();
                computeCompleteRouteToRoom(room);
            });

            roomList.appendChild(div);
        });
    }

    function getIndoorRoomLayerStyle(feature) {
        const p = feature?.properties || {};
        const type = String(p.type || '').toLowerCase();
        const isSelected = selectedIndoorRoomFeature
            && Number(selectedIndoorRoomFeature.properties?.id) === Number(p.id);

        let fillColor = '#dbeafe';
        if (type.includes('office')) fillColor = '#dcfce7';
        else if (type.includes('restroom')) fillColor = '#fef3c7';
        else if (type.includes('storage')) fillColor = '#e5e7eb';

        return {
            color: isSelected ? '#1d4ed8' : '#2563eb',
            weight: isSelected ? 3 : 2,
            fillColor,
            fillOpacity: isSelected ? 0.65 : 0.38
        };
    }

    let indoorViewportFitFrame = null;
    let indoorViewportResizeObserver = null;
    let indoorViewportObservedElement = null;
    let indoorViewportFitRequest = null;
    let indoorViewportLastSize = '';
    const indoorInteractionFlags = new Set();
    let indoorInteractionSettleFrame = null;
    let indoorResizeFitPending = false;

    function installIndoorInteractionController() {
        if (!indoorMap || indoorMap.__wayfindingInteractionControllerInstalled) return;
        indoorMap.__wayfindingInteractionControllerInstalled = true;

        const begin = type => {
            indoorInteractionFlags.add(type);
            if (indoorInteractionSettleFrame) {
                cancelAnimationFrame(indoorInteractionSettleFrame);
                indoorInteractionSettleFrame = null;
            }
            document.body.classList.add('indoor-map-interacting');
            if (type === 'zoom' && isLowEndIndoorSurfaceMode()) {
                document.body.classList.add('indoor-map-zooming');
            }
        };
        const end = type => {
            indoorInteractionFlags.delete(type);
            if (type === 'zoom') document.body.classList.remove('indoor-map-zooming');
            if (indoorInteractionFlags.size) return;
            if (indoorInteractionSettleFrame) cancelAnimationFrame(indoorInteractionSettleFrame);
            indoorInteractionSettleFrame = requestAnimationFrame(() => {
                indoorInteractionSettleFrame = null;
                document.body.classList.remove('indoor-map-interacting');

                if (indoorResizeFitPending) {
                    indoorResizeFitPending = false;
                    scheduleIndoorViewportFit({
                        reason: 'settled-container-resize',
                        preferRoute: Boolean(lastIndoorRoutePackage)
                    });
                }
            });
        };

        indoorMap.on('dragstart', () => begin('drag'));
        indoorMap.on('dragend', () => end('drag'));
        indoorMap.on('zoomstart', () => begin('zoom'));
        indoorMap.on('zoomend', () => end('zoom'));
    }

    function isIndoorPanelVisible() {
        return Boolean(indoorPanel?.classList.contains('active'));
    }

    function getIndoorViewportBounds(preferRoute = false) {
        if (preferRoute && typeof persistentIndoorRouteByFloor !== 'undefined') {
            const routePoints = persistentIndoorRouteByFloor?.[currentIndoorFloor] || [];
            if (routePoints.length >= 2) {
                return {
                    bounds: L.latLngBounds(routePoints),
                    route: true
                };
            }
        }

        const mapItem = allIndoorMaps.find(item =>
            Number(item.building_id) === Number(currentIndoorBuildingId) &&
            Number(item.floor_number) === Number(currentIndoorFloor)
        );
        const geometryBounds = getIndoorMapBoundsFromGeometry(mapItem?.geometry);
        if (geometryBounds?.isValid()) {
            return {
                bounds: geometryBounds,
                route: false
            };
        }

        const layers = [indoorPathsLayer, indoorRoomsLayer, indoorEntrancesLayer]
            .filter(Boolean);
        if (!layers.length) return null;

        const bounds = L.featureGroup(layers).getBounds();
        return bounds.isValid() ? { bounds, route: false } : null;
    }

    function fitIndoorViewportOnce(options = {}) {
        if (
            !indoorMap ||
            !isIndoorPanelVisible() ||
            !currentIndoorBuildingId ||
            !hasIndoorFloorValue(currentIndoorFloor)
        ) {
            return false;
        }

        if (indoorInteractionFlags.size) return false;

        const container = indoorMap.getContainer?.();
        if (!container || container.clientWidth < 2 || container.clientHeight < 2) {
            return false;
        }

        indoorMap.invalidateSize({ animate: false, pan: false });
        const viewport = getIndoorViewportBounds(Boolean(options.preferRoute));
        if (!viewport) return false;

        const mobile = window.matchMedia('(max-width: 768px)').matches;
        const paddedBounds = viewport.route
            ? viewport.bounds
            : viewport.bounds.pad(mobile ? 0.03 : 0.12);

        /* Drawing a route often happens while its complete floor segment is
           already visible. Re-fitting the same bounds forces a full Canvas and
           image-overlay reprojection on low-end Android, so keep the current
           camera when no movement is necessary. */
        if (
            viewport.route
            && mobile
            && indoorMap.getBounds?.().contains(viewport.bounds)
        ) {
            return true;
        }

        indoorMap.fitBounds(paddedBounds, {
            animate: false,
            padding: viewport.route
                ? (mobile ? [18, 18] : [44, 44])
                : (mobile ? [8, 8] : [28, 28]),
            maxZoom: 22
        });

        return true;
    }

    function scheduleIndoorViewportFit(options = {}) {
        if (indoorInteractionFlags.size) {
            if (options?.reason === 'container-resize') indoorResizeFitPending = true;
            return;
        }

        indoorViewportFitRequest = {
            ...(indoorViewportFitRequest || {}),
            ...(options || {})
        };

        if (indoorViewportFitFrame) cancelAnimationFrame(indoorViewportFitFrame);
        indoorViewportFitFrame = requestAnimationFrame(() => {
            indoorViewportFitFrame = null;
            const request = indoorViewportFitRequest || {};
            if (fitIndoorViewportOnce(request)) {
                indoorViewportFitRequest = null;
            }
        });
    }

    function ensureIndoorViewportObserver() {
        const target = indoorMap?.getContainer?.()?.parentElement || indoorPanel;
        if (!target || typeof ResizeObserver !== 'function') return;
        if (indoorViewportResizeObserver && indoorViewportObservedElement === target) return;

        indoorViewportResizeObserver?.disconnect();
        indoorViewportObservedElement = target;
        indoorViewportResizeObserver = new ResizeObserver(entries => {
            const rect = entries[0]?.contentRect;
            if (!rect || !isIndoorPanelVisible()) return;
            const nextSize = `${Math.round(rect.width)}x${Math.round(rect.height)}`;
            if (nextSize === indoorViewportLastSize) return;
            indoorViewportLastSize = nextSize;
            if (indoorInteractionFlags.size) {
                indoorResizeFitPending = true;
                return;
            }
            scheduleIndoorViewportFit({
                reason: 'container-resize',
                preferRoute: Boolean(lastIndoorRoutePackage)
            });
        });
        indoorViewportResizeObserver.observe(target);
    }

    function renderIndoorFloor() {
        if (!indoorMap || !currentIndoorBuildingId || !(currentIndoorFloor !== null && currentIndoorFloor !== undefined && currentIndoorFloor !== '')) return;

        if (indoorRoomsLayer) indoorMap.removeLayer(indoorRoomsLayer);
        if (indoorPathsLayer) indoorMap.removeLayer(indoorPathsLayer);
        if (indoorEntrancesLayer) indoorMap.removeLayer(indoorEntrancesLayer);
        indoorRoomsLayer = null;
        indoorPathsLayer = null;
        indoorEntrancesLayer = null;
        clearIndoorRoute();

        const floorRooms = getIndoorRoomsFor(currentIndoorBuildingId, currentIndoorFloor);
        const floorPaths = getIndoorPathsFor(currentIndoorBuildingId, currentIndoorFloor);
        const floorEntrances = getIndoorEntrancesFor(currentIndoorBuildingId, currentIndoorFloor);
        const lowEndSurfaceMode = isLowEndIndoorSurfaceMode();
        indoorLowEndFloorRooms = lowEndSurfaceMode ? floorRooms : [];
        if (lowEndSurfaceMode) installIndoorLowEndRoomTap();

        if (!lowEndSurfaceMode) {
        indoorPathsLayer = L.geoJSON({
            type: 'FeatureCollection',
            features: floorPaths
        }, {
            renderer: indoorMap.__wayfindingVectorRenderer,
            interactive: false,
            style: function(feature) {
                const p = feature.properties || {};
                const type = String(p.path_type || 'hallway').toLowerCase();

                if (type.includes('stairs')) {
                    return {
                        color: '#f59e0b',
                        weight: 6,
                        opacity: 0.95,
                        dashArray: '6,6',
                        lineCap: 'round',
                        lineJoin: 'round'
                    };
                }

                return {
                    color: '#334155',
                    weight: 7,
                    opacity: 0.95,
                    lineCap: 'round',
                    lineJoin: 'round'
                };
            }
        }).addTo(indoorMap);

        indoorRoomsLayer = L.geoJSON({
            type: 'FeatureCollection',
            features: floorRooms
        }, {
            renderer: indoorMap.__wayfindingVectorRenderer,
            style: getIndoorRoomLayerStyle,
            onEachFeature: function(feature, layer) {
                const p = feature.properties || {};

                layer.bindPopup(`
                    <div style="font-family:'Plus Jakarta Sans',sans-serif;min-width:180px;text-align:left;">
                        <div style="font-size:15px;font-weight:800;color:#0f172a;">${p.name || 'Room'}</div>
                        <div style="font-size:12px;color:#64748b;margin-top:5px;">
                            Code: ${p.room_code || 'N/A'}<br>
                            Type: ${p.type || 'room'}<br>
                            Floor: ${(typeof formatIndoorFloorLabel === 'function' ? formatIndoorFloorLabel(p.floor_number, p.floor_label) : (p.floor_label || p.floor_number || '-'))}
                        </div>
                        <div style="margin-top:10px;">
                            <button
                                type="button"
                                onclick="window.routeToIndoorRoom(${Number(p.id)})"
                                style="border:none;background:#2563eb;color:white;padding:8px 12px;border-radius:10px;font-size:12px;font-weight:800;cursor:pointer;">
                                Route to this room
                            </button>
                        </div>
                    </div>
                `);

                layer.on('click', function() {
                    selectedIndoorRoomFeature = feature;
                    renderIndoorRoomList();
                    /* Keep the open popup and route action alive. Rebuilding all
                       indoor layers here caused a visible pause on slower phones. */
                    indoorRoomsLayer?.eachLayer(roomLayer => {
                        if (typeof roomLayer.setStyle !== 'function') return;
                        roomLayer.setStyle(getIndoorRoomLayerStyle(roomLayer.feature));
                    });
                });
            }
        }).addTo(indoorMap);

        indoorEntrancesLayer = L.geoJSON({
            type: 'FeatureCollection',
            features: floorEntrances
        }, {
            renderer: indoorMap.__wayfindingVectorRenderer,
            pointToLayer: function(feature, latlng) {
                const p = feature.properties || {};
                const entType = String(p.ent_type || '').toLowerCase();

                let fillColor = '#ef4444';
                if (entType.includes('main')) fillColor = '#16a34a';
                else if (entType.includes('stairs')) fillColor = '#f59e0b';
                else if (entType.includes('door')) fillColor = '#7c3aed';
                else if (entType.includes('side')) fillColor = '#0ea5e9';

                return L.circleMarker(latlng, {
                    renderer: indoorMap.__wayfindingVectorRenderer,
                    radius: 7,
                    color: '#ffffff',
                    weight: 2,
                    fillColor,
                    fillOpacity: 1
                });
            },
            onEachFeature: function(feature, layer) {
                const p = feature.properties || {};
                layer.bindPopup(`
                    <div style="font-family:'Plus Jakarta Sans',sans-serif;min-width:160px;text-align:left;">
                        <div style="font-size:14px;font-weight:800;color:#0f172a;">${p.name || 'Entrance'}</div>
                        <div style="font-size:12px;color:#64748b;">
                            Type: ${p.ent_type || 'entrance'}<br>
                            Room Code: ${p.room_code || '-'}<br>
                            Floor: ${(typeof formatIndoorFloorLabel === 'function' ? formatIndoorFloorLabel(p.floor_number, p.floor_label) : (p.floor_label || p.floor_number || '-'))}
                        </div>
                    </div>
                `);
            }
        }).addTo(indoorMap);
        }

        loadIndoorFloorImage({ rooms: floorRooms, paths: floorPaths, entrances: floorEntrances });

        indoorFooter.innerHTML = `
            <span class="indoor-badge badge-blue">${getBuildingNameById(currentIndoorBuildingId)}</span>
            <span class="indoor-badge badge-green">${indoorFloorSelect.selectedOptions[0]?.textContent || ('Floor ' + currentIndoorFloor)}</span>
            Click a room card or room polygon to compute the exact route.
        `;
    }

    function restoreIndoorRouteIfAvailable() {
        if (!lastIndoorRoutePackage) return;
        if (!currentIndoorBuildingId) return;

        const routedBuildingId = Number(lastIndoorRoutePackage.roomFeature?.properties?.building_id);
        if (Number(routedBuildingId) !== Number(currentIndoorBuildingId)) return;

        if (pendingIndoorFocusFloor !== null && pendingIndoorFocusFloor !== undefined) {
            currentIndoorFloor = Number(pendingIndoorFocusFloor);
            indoorFloorSelect.value = String(currentIndoorFloor);
        }

        redrawPersistentIndoorRouteForCurrentFloor();
    }


    function hasIndoorMapForBuilding(buildingId) {
        return getIndoorBuildingMaps(buildingId)
            .some(mapItem => Boolean(String(mapItem?.floorplan_image || '').trim()));
    }

    let indoorStylesPromise = null;
    let latestIndoorOpenRequestId = 0;

    window.addEventListener('wayfinding:indoor-panel-closed', () => {
        latestIndoorOpenRequestId += 1;
    });

    function ensureIndoorStyles() {
        if (document.querySelector('link[data-wayfinding-indoor-styles]')) {
            return Promise.resolve();
        }

        if (indoorStylesPromise) return indoorStylesPromise;

        indoorStylesPromise = new Promise((resolve, reject) => {
            const stylesheet = document.createElement('link');
            stylesheet.rel = 'stylesheet';
            stylesheet.href = '/css/wayfinding/04-indoor-navigation.css?v=20260815.2';
            stylesheet.dataset.wayfindingIndoorStyles = 'true';
            stylesheet.addEventListener('load', resolve, { once: true });
            stylesheet.addEventListener('error', () => {
                indoorStylesPromise = null;
                reject(new Error('Indoor navigation styles could not be loaded.'));
            }, { once: true });
            /*
             * Keep the original cascade order: indoor component rules first,
             * then the main futuristic theme. Appending the component after the
             * theme made its white legacy header override the dark HUD while the
             * theme's white title remained, producing invisible text.
             */
            const mainWayfindingStylesheet = Array.from(
                document.querySelectorAll('link[rel="stylesheet"]')
            ).find(link => /\/build\/assets\/wayfinding-[^/]+\.css/.test(link.href));

            if (mainWayfindingStylesheet?.parentNode) {
                mainWayfindingStylesheet.parentNode.insertBefore(
                    stylesheet,
                    mainWayfindingStylesheet
                );
            } else {
                document.head.appendChild(stylesheet);
            }
        });

        return indoorStylesPromise;
    }

    async function openIndoorPanelForBuilding(buildingId) {
        const normalizedBuildingId = Number(buildingId);
        if (!normalizedBuildingId) return;

        const requestId = ++latestIndoorOpenRequestId;
        const buildingName = getBuildingNameById(normalizedBuildingId);

        /*
        | Show useful feedback before any network or CSS request. On a slow
        | campus connection the old flow appeared frozen because the modal was
        | opened only after the indoor stylesheet and JSON had both arrived.
        */
        indoorTitle.textContent = `${buildingName} Indoor Navigation`;
        indoorSubtitle.textContent = 'Loading rooms and indoor map...';
        indoorFloorSelect.innerHTML = '<option value="">Loading floors...</option>';
        indoorFloorSelect.disabled = true;
        openIndoorPanelModal();
        setIndoorLoading(true);

        try {
            await Promise.all([
                ensureIndoorStyles(),
                ensureIndoorBuildingData(normalizedBuildingId)
            ]);
        } catch (error) {
            if (requestId !== latestIndoorOpenRequestId) return false;

            console.error('Indoor resources failed to load:', error);
            setIndoorLoading(false);
            closeIndoorPanelFn();
            window.showWayfindingToast?.(
                'Indoor map could not load. Check your connection and try again.',
                { kind: 'error' }
            );
            return false;
        }

        if (requestId !== latestIndoorOpenRequestId || !isIndoorPanelVisible()) {
            return false;
        }

        const buildingMaps = getIndoorBuildingMaps(normalizedBuildingId)
            .filter(mapItem => Boolean(String(mapItem?.floorplan_image || '').trim()));

        /*
        |--------------------------------------------------------------------------
        | SILENT SKIP FOR BUILDINGS WITHOUT INDOOR MAP
        |--------------------------------------------------------------------------
        | Some buildings intentionally do not have indoor navigation.
        | If no active indoor map exists, do not show alert and do not open panel.
        */
        if (!buildingMaps.length) {
            setIndoorLoading(false);
            closeIndoorPanelFn();
            return false;
        }

        ensureIndoorMap();

        currentIndoorBuildingId = normalizedBuildingId;
        selectedDestinationBuildingId = normalizedBuildingId;

        pendingIndoorOpenForBuildingId = normalizedBuildingId;

        indoorTitle.textContent = `${buildingName} Indoor Navigation`;
        indoorSubtitle.textContent = 'Choose room or office to compute full route';

        indoorFloorSelect.innerHTML = '<option value="">Select Floor</option>';
        indoorFloorSelect.disabled = false;

        buildingMaps.forEach(mapItem => {
            const option = document.createElement('option');
            option.value = mapItem.floor_number;
            option.textContent = (typeof formatIndoorFloorLabel === 'function' ? formatIndoorFloorLabel(mapItem.floor_number, mapItem.floor_label) : (mapItem.floor_label || (`Floor ${mapItem.floor_number}`)));
            indoorFloorSelect.appendChild(option);
        });

        const routedBuildingId = Number(lastIndoorRoutePackage?.roomFeature?.properties?.building_id || 0);

        if (
            routedBuildingId === normalizedBuildingId &&
            pendingIndoorFocusFloor !== null &&
            pendingIndoorFocusFloor !== undefined
        ) {
            currentIndoorFloor = Number(pendingIndoorFocusFloor);
        } else {
            currentIndoorFloor = Number(buildingMaps[0].floor_number);
        }

        indoorFloorSelect.value = String(currentIndoorFloor);

        requestAnimationFrame(() => {
            if (requestId !== latestIndoorOpenRequestId || !isIndoorPanelVisible()) {
                return;
            }

            try {
                renderIndoorFloor();
                renderIndoorRoomList();
                restoreIndoorRouteIfAvailable();

                if (typeof renderIndoorFloorButtonsFinal === 'function') {
                    renderIndoorFloorButtonsFinal();
                }

                if (typeof updateIndoorFloorButtonActiveFinal === 'function') {
                    updateIndoorFloorButtonActiveFinal();
                }

                ensureIndoorViewportObserver();
                scheduleIndoorViewportFit({
                    reason: 'panel-open',
                    preferRoute: Boolean(lastIndoorRoutePackage)
                });
                setIndoorLoading(false);
            } catch (error) {
                console.error('Indoor render failed:', error);
                setIndoorLoading(false);
                // No alert popup. Just keep the app quiet and prevent wrong panel behavior.
                closeIndoorPanelFn();
            }
        });

        return true;
    }

    const indoorGraphCache = new Map();

    window.clearWayfindingIndoorGraphCache = function(buildingId = null) {
        if (buildingId === null || buildingId === undefined) {
            indoorGraphCache.clear();
            return;
        }

        indoorGraphCache.delete(Number(buildingId));
    };

    function buildIndoorGraph(buildingId) {
        const normalizedBuildingId = Number(buildingId);
        const cachedGraph = indoorGraphCache.get(normalizedBuildingId);
        if (cachedGraph) return cachedGraph;

        const graph = {};
        const coords = {};
        const entranceNodeById = {};
        const roomNodeById = {};

        function addNode(key, lat, lng) {
            if (!graph[key]) graph[key] = [];
            coords[key] = [lat, lng];
        }

        function addEdge(a, b, weight, meta = {}) {
            if (!graph[a]) graph[a] = [];
            if (!graph[b]) graph[b] = [];

            graph[a].push({
                key: b,
                weight,
                meta
            });
            graph[b].push({
                key: a,
                weight,
                meta
            });
        }

        const paths = getIndoorPathsFor(buildingId, null).filter(f => !Boolean(f.properties?.is_blocked));
        const rooms = getIndoorRoomsFor(buildingId, null);
        const entrances = getIndoorEntrancesFor(buildingId, null);

        paths.forEach(feature => {
            const p = feature.properties || {};
            const floor = Number(p.floor_number || 0);
            const rawType = String(p.path_type || 'hallway').toLowerCase();

            /*
            |--------------------------------------------------------------------------
            | STAIR AVOIDANCE WEIGHT
            |--------------------------------------------------------------------------
            | Dijkstra will still use stairs if stairs are clearly the shortest/only
            | practical path. If another nearby route avoids stairs, this soft
            | penalty lets the non-stair route win.
            */
            let multiplier = 1;
            let extraPenalty = 0;

            if (rawType.includes('stairs')) {
                /*
                | Very light penalty only:
                | Avoid stairs only if a non-stairs path is close enough.
                | If stairs are clearly shorter, Dijkstra will still choose stairs.
                */
                multiplier = 1.25;
                extraPenalty = 2;
            }

            const lines = feature.geometry.type === 'MultiLineString' ?
                feature.geometry.coordinates : [feature.geometry.coordinates];

            lines.forEach(line => {
                if (!Array.isArray(line) || line.length < 2) return;

                for (let i = 0; i < line.length - 1; i++) {
                    const a = line[i];
                    const b = line[i + 1];

                    const latA = Number(a[1]);
                    const lngA = Number(a[0]);
                    const latB = Number(b[1]);
                    const lngB = Number(b[0]);

                    const keyA = `p_${lngA}_${latA}_f${floor}`;
                    const keyB = `p_${lngB}_${latB}_f${floor}`;

                    addNode(keyA, latA, lngA);
                    addNode(keyB, latB, lngB);

                    const dist = L.latLng(latA, lngA).distanceTo(L.latLng(latB, lngB));
                    addEdge(keyA, keyB, (dist * multiplier) + extraPenalty, {
                        type: rawType,
                        floor_number: floor,
                        is_stairs: rawType.includes('stairs')
                    });
                }
            });
        });

        function nearestPathNode(latlng, floor) {
            let bestKey = null;
            let bestDistance = Infinity;

            Object.entries(coords).forEach(([key, value]) => {
                if (!key.startsWith('p_')) return;
                if (!String(key).endsWith(`_f${floor}`)) return;

                const d = latlng.distanceTo(L.latLng(value[0], value[1]));
                if (d < bestDistance) {
                    bestDistance = d;
                    bestKey = key;
                }
            });

            return bestKey;
        }

        entrances.forEach(feature => {
            const p = feature.properties || {};
            const floor = Number(p.floor_number || 0);
            const latlng = getPointLatLng(feature);
            if (!latlng) return;

            const entKey = `e_${p.id}_f${floor}`;
            addNode(entKey, latlng.lat, latlng.lng);

            const nearestPath = nearestPathNode(latlng, floor);
            if (nearestPath) {
                const pathCoord = coords[nearestPath];
                const dist = latlng.distanceTo(L.latLng(pathCoord[0], pathCoord[1]));
                addEdge(entKey, nearestPath, dist, {
                    type: 'entrance_connector',
                    floor_number: floor
                });
            }

            entranceNodeById[Number(p.id)] = entKey;
        });

        /*
        |--------------------------------------------------------------------------
        | INDOOR ROOM DOOR-ONLY ROUTING FIX
        |--------------------------------------------------------------------------
        | Old issue:
        | - If a room has no linked indoor_entrance by room_code, the old code
        |   connected the room center directly to the nearest hallway/path.
        | - That creates a diagonal line that looks like the route passes through
        |   the wall instead of entering through the actual door.
        |
        | New behavior:
        | - A room connects ONLY through a door/indoor entrance.
        | - First priority: indoor_entrance.room_code == indoor_room.room_code.
        | - Second priority: detect doors placed inside/along the room polygon.
        | - Last safe fallback: nearest same-floor door close to the room boundary.
        | - If no door is found, the room is not connected to the graph so the user
        |   can fix the missing door data instead of showing a wrong route.
        */
        function getEntranceType(entFeature) {
            return String(entFeature?.properties?.ent_type || '').trim().toLowerCase();
        }

        function isRoomDoorEntrance(entFeature) {
            const type = getEntranceType(entFeature);

            // Stairs/main/side are building navigation entrances, not room doors.
            if (type.includes('stairs')) return false;
            if (type.includes('main')) return false;
            if (type.includes('side')) return false;

            // Accept explicit door, empty type, or entrances with room_code.
            return type === '' ||
                type.includes('door') ||
                type.includes('room') ||
                String(entFeature?.properties?.room_code || '').trim() !== '';
        }

        function pointInIndoorRing(lng, lat, ring) {
            if (!Array.isArray(ring) || ring.length < 3) return false;

            const x = Number(lng);
            const y = Number(lat);
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

        function pointInsideIndoorPolygon(lng, lat, rings) {
            if (!Array.isArray(rings) || !rings.length) return false;
            if (!pointInIndoorRing(lng, lat, rings[0])) return false;

            // Holes
            for (let i = 1; i < rings.length; i++) {
                if (pointInIndoorRing(lng, lat, rings[i])) return false;
            }

            return true;
        }

        function pointInsideIndoorRoomGeometry(lng, lat, geometry) {
            if (!geometry || !geometry.type || !geometry.coordinates) return false;

            if (geometry.type === 'Polygon') {
                return pointInsideIndoorPolygon(lng, lat, geometry.coordinates);
            }

            if (geometry.type === 'MultiPolygon') {
                return geometry.coordinates.some(poly => pointInsideIndoorPolygon(lng, lat, poly));
            }

            return false;
        }

        function projectPointToIndoorSegmentMeters(point, a, b) {
            const originLat = (Number(point.lat) + Number(a[1]) + Number(b[1])) / 3;
            const metersPerDegLat = 110540;
            const metersPerDegLng = 111320 * Math.cos(originLat * Math.PI / 180);

            const ax = Number(a[0]) * metersPerDegLng;
            const ay = Number(a[1]) * metersPerDegLat;
            const bx = Number(b[0]) * metersPerDegLng;
            const by = Number(b[1]) * metersPerDegLat;
            const px = Number(point.lng) * metersPerDegLng;
            const py = Number(point.lat) * metersPerDegLat;

            const abx = bx - ax;
            const aby = by - ay;
            const ab2 = (abx * abx) + (aby * aby);
            if (ab2 <= 0.000001) return Infinity;

            let t = (((px - ax) * abx) + ((py - ay) * aby)) / ab2;
            t = Math.max(0, Math.min(1, t));

            const cx = ax + (abx * t);
            const cy = ay + (aby * t);
            const dx = px - cx;
            const dy = py - cy;

            return Math.sqrt((dx * dx) + (dy * dy));
        }

        function distancePointToRoomBoundaryMeters(latlng, geometry) {
            if (!latlng || !geometry || !geometry.type || !geometry.coordinates) return Infinity;

            const polygons = geometry.type === 'MultiPolygon' ?
                geometry.coordinates :
                [geometry.coordinates];

            let best = Infinity;
            const point = {
                lat: Number(latlng.lat),
                lng: Number(latlng.lng)
            };

            polygons.forEach(poly => {
                (poly || []).forEach(ring => {
                    if (!Array.isArray(ring) || ring.length < 2) return;

                    for (let i = 0; i < ring.length - 1; i++) {
                        const d = projectPointToIndoorSegmentMeters(point, ring[i], ring[i + 1]);
                        if (d < best) best = d;
                    }
                });
            });

            return best;
        }

        function findDoorEntrancesForRoom(roomFeature, roomFloor) {
            const p = roomFeature.properties || {};
            const roomCode = String(p.room_code || '').trim().toLowerCase();
            const roomGeometry = roomFeature.geometry;

            const sameFloorDoors = entrances.filter(ent => {
                return Number(ent.properties?.floor_number || 0) === Number(roomFloor) &&
                    isRoomDoorEntrance(ent) &&
                    getPointLatLng(ent);
            });

            // 1) Best/cleanest data: explicit room_code match.
            let linkedDoors = [];
            if (roomCode) {
                linkedDoors = sameFloorDoors.filter(ent => {
                    return String(ent.properties?.room_code || '').trim().toLowerCase() === roomCode;
                });
            }

            if (linkedDoors.length) return linkedDoors;

            // 2) Door point is inside the room polygon or very near the room wall.
            const touchingDoors = sameFloorDoors
                .map(ent => {
                    const doorLatLng = getPointLatLng(ent);
                    if (!doorLatLng) return null;

                    const insideRoom = pointInsideIndoorRoomGeometry(
                        doorLatLng.lng,
                        doorLatLng.lat,
                        roomGeometry
                    );

                    const boundaryDistance = distancePointToRoomBoundaryMeters(doorLatLng, roomGeometry);

                    return {
                        ent,
                        insideRoom,
                        boundaryDistance
                    };
                })
                .filter(item => item && (item.insideRoom || item.boundaryDistance <= 1.8))
                .sort((a, b) => a.boundaryDistance - b.boundaryDistance)
                .map(item => item.ent);

            if (touchingDoors.length) return touchingDoors;

            // 3) Last safe fallback: nearest door to room boundary, but only if close.
            const NEAREST_ROOM_DOOR_MAX_METERS = 8;
            const nearestDoor = sameFloorDoors
                .map(ent => {
                    const doorLatLng = getPointLatLng(ent);
                    return {
                        ent,
                        boundaryDistance: distancePointToRoomBoundaryMeters(doorLatLng, roomGeometry)
                    };
                })
                .filter(item => Number.isFinite(item.boundaryDistance))
                .sort((a, b) => a.boundaryDistance - b.boundaryDistance)[0];

            if (nearestDoor && nearestDoor.boundaryDistance <= NEAREST_ROOM_DOOR_MAX_METERS) {
                return [nearestDoor.ent];
            }

            return [];
        }

        rooms.forEach(feature => {
            const p = feature.properties || {};
            const floor = Number(p.floor_number || 0);
            const center = getFeatureCenter(feature);

            const roomKey = `r_${p.id}_f${floor}`;
            addNode(roomKey, center.lat, center.lng);

            const linkedDoors = findDoorEntrancesForRoom(feature, floor);

            if (linkedDoors.length > 0) {
                linkedDoors.forEach(linkedDoor => {
                    const doorLatLng = getPointLatLng(linkedDoor);
                    const entKey = entranceNodeById[Number(linkedDoor.properties?.id)];

                    if (doorLatLng && entKey) {
                        addEdge(roomKey, entKey, center.distanceTo(doorLatLng), {
                            type: 'room_to_door',
                            floor_number: floor,
                            door_id: Number(linkedDoor.properties?.id || 0),
                            door_only_fix: true
                        });
                    }
                });
            } else {
                debugIndoorGraphWarning('[IndoorGraph] Room has no usable door entrance, route disabled for this room:', {
                    room_id: p.id,
                    room_name: p.name,
                    room_code: p.room_code,
                    floor_number: floor,
                    building_id: buildingId
                });
            }

            roomNodeById[Number(p.id)] = roomKey;
        });

        allIndoorStairsLinks
            .filter(link => Number(link.building_id) === Number(buildingId))
            .forEach(link => {
                const fromId = Number(link.from_entrance_id || link.from_entrance?.id);
                const toId = Number(link.to_entrance_id || link.to_entrance?.id);

                const fromKey = entranceNodeById[fromId];
                const toKey = entranceNodeById[toId];

                if (fromKey && toKey) {
                    /*
                    |--------------------------------------------------------------------------
                    | INTER-FLOOR STAIR LINK PENALTY
                    |--------------------------------------------------------------------------
                    | Going to another floor is still allowed, but not over-preferred.
                    | If the destination floor has a valid/nearby entrance, Dijkstra will
                    | prefer it. If stairs are the practical route, it will still use stairs.
                    */
                    addEdge(fromKey, toKey, 6, {
                        type: 'stairs_link',
                        is_stairs: true
                    });
                }
            });

        const graphData = {
            graph,
            coords,
            entranceNodeById,
            roomNodeById
        };

        indoorGraphCache.set(normalizedBuildingId, graphData);
        return graphData;
    }

    function dijkstraIndoor(graph, startKey, endKey) {
        return window.WayfindingRouting.indoorShortestPath(
            graph,
            startKey,
            endKey
        );
    }

    function buildIndoorRouteByFloor(indoorGraphData, indoorResult) {
        const grouped = {};

        if (!indoorResult || !indoorResult.path || !indoorResult.path.length) {
            return grouped;
        }

        const fullPoints = indoorResult.path.map(key => {
            const floor = getFloorFromNodeKey(key);
            const coord = indoorGraphData.coords[key];
            if (!coord) return null;

            return {
                key,
                floor,
                latlng: L.latLng(coord[0], coord[1])
            };
        }).filter(Boolean);

        for (let i = 0; i < fullPoints.length; i++) {
            const point = fullPoints[i];
            if (!grouped[point.floor]) grouped[point.floor] = [];
            grouped[point.floor].push(point.latlng);
        }

        return grouped;
    }

    function drawIndoorRoute(indoorGraphData, indoorResult, entranceFeature, roomFeature) {
        clearIndoorRoute();

        if (!indoorResult || !indoorResult.path?.length) return;

        indoorRouteLayer = L.layerGroup().addTo(indoorMap);

        const groupedRoutes = buildIndoorRouteByFloor(indoorGraphData, indoorResult);
        persistentIndoorRouteByFloor = groupedRoutes;

        const currentFloorPoints = groupedRoutes[currentIndoorFloor] || [];

        if (currentFloorPoints.length >= 2) {
            const staticIndoorRoute = drawAnimatedRoute(indoorMap, indoorRouteLayer, currentFloorPoints, {
                color: '#25c9f2',
                weight: 5.5,
                opacity: 1,
                dashArray: null,
                outlineColor: '#67dcfa',
                outlineWeight: 11,
                outlineOpacity: 0.28,
                className: 'route-line-live-indoor'
            });

            indoorRouteAnimationTimer = staticIndoorRoute.timer;
        }

        const entLatLng = entranceFeature ? getPointLatLng(entranceFeature) : null;
        const roomCenter = roomFeature ? getFeatureCenter(roomFeature) : null;

        if (entLatLng && Number(entranceFeature.properties?.floor_number) === Number(currentIndoorFloor)) {
            indoorStartMarker = createIndoorStartArrowMarker(entLatLng, currentFloorPoints)
                .addTo(indoorMap)
                .bindPopup('Start here - Indoor Entrance');
        }

        if (roomCenter && Number(roomFeature.properties?.floor_number) === Number(currentIndoorFloor)) {
            indoorEndMarker = L.circleMarker(roomCenter, {
                radius: 8,
                color: '#fff',
                weight: 2,
                fillColor: '#dc2626',
                fillOpacity: 1
            }).addTo(indoorMap).bindPopup(roomFeature.properties?.name || 'Destination Room');
        }

        lastIndoorRoutePackage = {
            indoorGraphData,
            indoorResult,
            entranceFeature,
            roomFeature
        };

        scheduleIndoorViewportFit({
            reason: 'route-created',
            preferRoute: currentFloorPoints.length >= 2
        });

        if (indoorFooter && currentFloorPoints.length >= 2) {
            indoorFooter.innerHTML = `
                <span class="indoor-badge badge-green">Indoor Route Ready</span>
                Follow the solid cyan route line to the destination.
            `;
        }
    }

    function redrawPersistentIndoorRouteForCurrentFloor() {
        if (!lastIndoorRoutePackage) return;

        clearIndoorRoute();

        indoorRouteLayer = L.layerGroup().addTo(indoorMap);

        const currentFloorPoints = persistentIndoorRouteByFloor[currentIndoorFloor] || [];

        if (currentFloorPoints.length >= 2) {
            const staticIndoorRoute = drawAnimatedRoute(indoorMap, indoorRouteLayer, currentFloorPoints, {
                color: '#25c9f2',
                weight: 5.5,
                opacity: 1,
                dashArray: null,
                outlineColor: '#67dcfa',
                outlineWeight: 11,
                outlineOpacity: 0.28,
                className: 'route-line-live-indoor'
            });

            indoorRouteAnimationTimer = staticIndoorRoute.timer;
        }

        const {
            entranceFeature,
            roomFeature
        } = lastIndoorRoutePackage;

        const entLatLng = entranceFeature ? getPointLatLng(entranceFeature) : null;
        const roomCenter = roomFeature ? getFeatureCenter(roomFeature) : null;

        if (entLatLng && Number(entranceFeature.properties?.floor_number) === Number(currentIndoorFloor)) {
            indoorStartMarker = createIndoorStartArrowMarker(entLatLng, currentFloorPoints)
                .addTo(indoorMap)
                .bindPopup('Start here - Indoor Entrance');
        }

        if (roomCenter && Number(roomFeature.properties?.floor_number) === Number(currentIndoorFloor)) {
            indoorEndMarker = L.circleMarker(roomCenter, {
                radius: 8,
                color: '#fff',
                weight: 2,
                fillColor: '#dc2626',
                fillOpacity: 1
            }).addTo(indoorMap).bindPopup(roomFeature.properties?.name || 'Destination Room');
        }

        scheduleIndoorViewportFit({
            reason: 'route-floor-redraw',
            preferRoute: currentFloorPoints.length >= 2
        });

        if (indoorFooter && lastIndoorRoutePackage) {
            const entranceFloor = Number(entranceFeature?.properties?.floor_number ?? NaN);
            const roomFloor = Number(roomFeature?.properties?.floor_number ?? NaN);
            const currentFloorLabel = indoorFloorSelect?.selectedOptions?.[0]?.textContent || (`Floor ${currentIndoorFloor}`);

            let guideText = 'Follow the solid cyan route line on this floor.';

            if (Number(currentIndoorFloor) === entranceFloor && Number(currentIndoorFloor) !== roomFloor) {
                guideText = 'Start here at the entrance. Follow this floor route first, then use the floor buttons/stairs to continue.';
            } else if (Number(currentIndoorFloor) === roomFloor) {
                guideText = 'This is the destination floor. Follow the solid cyan route to the selected room or office.';
            }

            indoorFooter.innerHTML = `
                <span class="indoor-badge badge-green">Indoor Route Ready</span>
                <span class="indoor-badge badge-blue">${currentFloorLabel}</span>
                ${guideText}
            `;
        }
    }

    function findNearestOutdoorEntranceForBuilding(buildingId) {
        const buildingLinks = allBuildingEntranceLinks.filter(link => Number(link.building_id) === Number(buildingId));

        if (buildingLinks.length > 0) {
            const firstLink = buildingLinks[0];
            const outdoorEntrance = buildingEntrances.find(
                e => Number(e.id) === Number(firstLink.building_entrance_id || firstLink.building_entrance?.id)
            );
            if (outdoorEntrance) {
                return outdoorEntrance;
            }
        }

        return buildingEntrances.find(e => Number(e.building_id) === Number(buildingId)) || null;
    }

    let completeRoomRouteRequestSequence = 0;

    function cancelPendingCompleteRoomRoute() {
        completeRoomRouteRequestSequence += 1;
    }

    async function findRouteToLanduse(landuseId) {
        cancelPendingCompleteRoomRoute();

        if (!startNodeKey) {
            alert('Please choose your starting point first.');
            return;
        }

        const landuse = (landuseRecords || []).find(
            l => Number(l.id) === Number(landuseId)
        );

        if (!landuse) {
            alert('Please choose a landuse area.');
            return;
        }

        if (isDesignLanduse(landuse)) {
            alert('This landuse is Design only and cannot be used as a route destination.');
            setRouteResultLabel('Design landuse is display-only and not available for routing.');

            if (destinationLanduseSelect) {
                destinationLanduseSelect.value = '';
            }

            selectedDestinationLanduseId = null;
            updateRouteLabels();
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT FIX
        |--------------------------------------------------------------------------
        | Landuse route is outdoor-only. Clear old indoor room route memory first.
        */
        clearIndoorRoutingStateOnly();

        const targetNodeKey = getLanduseNearestNodeKey(landuse);

        if (!targetNodeKey) {
            alert('No reachable route node found for this landuse.');
            return;
        }

        let result;
        try {
            result = await dijkstraAsync(startNodeKey, targetNodeKey);
        } catch (error) {
            if (error?.code === 'STALE_ROUTE_REQUEST') return;
            throw error;
        }

        if (!result) {
            alert('No route found to this landuse.');
            setRouteResultLabel('No route found to selected landuse.');
            return;
        }

        drawOutdoorRoute(result);
        clearDestinationMarker();

        const center = getLanduseCenter(landuse);

        if (center) {
            destinationMarker = L.marker([center.lat, center.lng], {
                icon: createDivIcon('<div class="route-landuse-dot"></div>', [18, 18], [9, 9])
            }).addTo(map).bindPopup(landuse.name || 'Landuse Area');
        }

        selectedDestinationLanduseId = Number(landuse.id);
        selectedDestinationBuildingId = null;
        selectedBuildingEntranceId = null;
        selectedIndoorRoomFeature = null;

        updateRouteLabels();
        setRouteResultLabel(`Route ready to ${landuse.name || 'landuse area'} only.`);
    }

    async function findRouteToBuilding(buildingId) {
        cancelPendingCompleteRoomRoute();

        if (!startNodeKey) {
            alert('Please choose your start point first.');
            return;
        }

        if (!buildingId) {
            alert('Please choose a destination building.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT FIX
        |--------------------------------------------------------------------------
        | Building-only route must never keep old indoor room routing.
        | Example: user routed IT Building Room 202, then routes only to another
        | building. This clears old indoor route memory before outdoor routing.
        */
        clearIndoorRoutingStateOnly();

        selectedDestinationBuildingId = Number(buildingId);
        selectedDestinationLanduseId = null;
        selectedBuildingEntranceId = null;

        const chosenEntrance = findNearestOutdoorEntranceForBuilding(buildingId);

        if (!chosenEntrance) {
            alert('No entrance found for this building.');
            return;
        }

        const gatewayNodeKey = nearestNodeKey(
            Number(chosenEntrance.latitude),
            Number(chosenEntrance.longitude)
        );

        if (!gatewayNodeKey) {
            alert('No outdoor routing node found near entrance.');
            return;
        }

        selectedBuildingEntranceId = Number(chosenEntrance.id);

        let result;
        try {
            result = await dijkstraAsync(startNodeKey, gatewayNodeKey);
        } catch (error) {
            if (error?.code === 'STALE_ROUTE_REQUEST') return;
            throw error;
        }

        if (!result) {
            alert('No outdoor route found.');
            return;
        }

        drawOutdoorRoute(result);

        clearDestinationMarker();

        destinationMarker = L.marker(
            [Number(chosenEntrance.latitude), Number(chosenEntrance.longitude)], {
                icon: createDivIcon('<div class="route-destination-dot"></div>', [18, 18], [9, 9])
            }
        ).addTo(map).bindPopup(chosenEntrance.name || 'Entrance');

        updateRouteLabels();
        setRouteResultLabel('Outdoor route computed to selected building only.');
        if (typeof showRoutePopupForSelectedBuilding === 'function') {
            showRoutePopupForSelectedBuilding(buildingId);
        }
    }

    async function findBestEntranceLinkForRoom(roomFeature) {
        if (!roomFeature || !startNodeKey) return null;

        const roomProps = roomFeature.properties || {};
        const buildingId = Number(roomProps.building_id);
        const roomFloor = Number(roomProps.floor_number || 0);

        const indoorGraphData = buildIndoorGraph(buildingId);
        const roomNodeKey = indoorGraphData.roomNodeById[Number(roomProps.id)];

        if (!roomNodeKey) return null;

        /*
        |--------------------------------------------------------------------------
        | V5 ENTRANCE DECISION - CLOSEST FLOOR + MAIN ENTRANCE PRIORITY
        |--------------------------------------------------------------------------
        | Fix for your latest case:
        | - Destination room is 3F.
        | - Main entrance is 2F.
        | - Side entrance is 1F.
        | - Old logic only prioritized exact same-floor entrance. Since there is no
        |   3F outdoor entrance, it fell back to the nearest outside doorway, which
        |   can be the 1F side entrance.
        |
        | New behavior:
        | - For upper-floor rooms, choose the entrance with the CLOSEST FLOOR first.
        |   Example: 3F room => 2F entrance wins over 1F entrance.
        | - If there are multiple entrances on the closest floor, prefer main/primary
        |   and then shortest outdoor route.
        | - Wrong-floor doorway lock is only allowed when user is almost exactly beside
        |   that doorway, so it will not keep forcing side entrance.
        |--------------------------------------------------------------------------
        */
        const WRONG_FLOOR_DOORWAY_LOCK_METERS = 5;
        const SAME_FLOOR_DOORWAY_LOCK_METERS = 24;
        const ROOM_1F_SAME_FLOOR_WINDOW_M = 34;
        const UPPER_CLOSEST_FLOOR_OUTDOOR_EXTRA_M = 170;
        const UPPER_CLOSEST_FLOOR_DIRECT_EXTRA_M = 140;
        const UPPER_CLOSEST_FLOOR_TOTAL_EXTRA_M = 220;
        const UPPER_CLOSEST_FLOOR_BIG_PENALTY = 900;
        const MAIN_PRIMARY_BONUS = 85;
        const SAME_FLOOR_BONUS = 120;
        const CLOSEST_FLOOR_BONUS = 95;
        const SIDE_ENTRANCE_PENALTY_FOR_UPPER = 45;
        const MAIN_ENTRANCE_TIE_METERS = 15;
        const INDOOR_WEIGHT = 0.16;
        const OUTDOOR_WEIGHT = 0.62;

        let candidateLinks = allBuildingEntranceLinks.filter(
            link => Number(link.building_id) === Number(buildingId)
        );

        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        | If wala kay building_entrance_links, pair outdoor entrances with indoor
        | entrances so the route can still compute.
        */
        if (!candidateLinks.length) {
            const fallbackOutdoorEntrances = buildingEntrances.filter(
                be => Number(be.building_id) === Number(buildingId)
            );

            const fallbackIndoorEntrances = getIndoorEntrancesFor(buildingId, null).filter(indoorEnt => {
                const t = String(indoorEnt.properties?.ent_type || '').toLowerCase();

                return (
                    t.includes('main') ||
                    t.includes('door') ||
                    t.includes('stairs') ||
                    t.includes('side')
                );
            });

            candidateLinks = fallbackOutdoorEntrances.flatMap(outdoorEnt => {
                return fallbackIndoorEntrances.map(indoorEnt => ({
                    id: `fallback_${outdoorEnt.id}_${indoorEnt.properties?.id}`,
                    building_id: buildingId,
                    building_entrance_id: outdoorEnt.id,
                    indoor_entrance_id: indoorEnt.properties?.id
                }));
            });
        }

        function routeUsesCoveredStairs(outdoorResult) {
            return (outdoorResult?.metas || []).some(meta => {
                const type = String(meta?.pathType || meta?.type || '').toLowerCase();
                return type === 'covered_stairs' || (type.includes('covered') && type.includes('stairs'));
            });
        }

        function getStartLatLngFromNode() {
            const coord = outdoorNodeCoords[startNodeKey];
            if (!coord) return null;

            return {
                lat: Number(coord[0]),
                lng: Number(coord[1])
            };
        }

        function entranceText(outdoorEntrance, indoorEntranceFeature) {
            return [
                outdoorEntrance?.name,
                outdoorEntrance?.properties?.name,
                outdoorEntrance?.ent_type,
                outdoorEntrance?.type,
                outdoorEntrance?.properties?.ent_type,
                outdoorEntrance?.properties?.type,
                indoorEntranceFeature?.properties?.name,
                indoorEntranceFeature?.properties?.ent_type,
                indoorEntranceFeature?.properties?.type
            ].filter(Boolean).join(' ').toLowerCase();
        }

        function isPrimaryOrMain(outdoorEntrance, indoorEntranceFeature) {
            const text = entranceText(outdoorEntrance, indoorEntranceFeature);
            return Boolean(outdoorEntrance?.is_primary) ||
                Number(outdoorEntrance?.is_primary || 0) === 1 ||
                text.includes('main') ||
                text.includes('primary');
        }

        function isSideEntrance(outdoorEntrance, indoorEntranceFeature) {
            const text = entranceText(outdoorEntrance, indoorEntranceFeature);
            return text.includes('side') || text.includes('back') || text.includes('rear');
        }

        const startLatLng = getStartLatLngFromNode();
        const candidateResults = await Promise.all(candidateLinks.map(async link => {
            const outdoorEntrance = buildingEntrances.find(
                be => Number(be.id) === Number(link.building_entrance_id || link.building_entrance?.id)
            );

            const indoorEntranceId = Number(link.indoor_entrance_id || link.indoor_entrance?.id);
            const indoorEntranceFeature = findIndoorEntranceFeatureById(indoorEntranceId);

            if (!outdoorEntrance || !indoorEntranceFeature) return null;

            const indoorEntranceFloor = Number(indoorEntranceFeature.properties?.floor_number || 0);
            const floorDiff = Math.abs(Number(roomFloor) - Number(indoorEntranceFloor));

            const outdoorNodeKey = nearestNodeKey(
                Number(outdoorEntrance.latitude),
                Number(outdoorEntrance.longitude)
            );

            if (!outdoorNodeKey) return null;

            const indoorStartNodeKey = indoorGraphData.entranceNodeById[indoorEntranceId];
            if (!indoorStartNodeKey) return null;

            /* Entrance alternatives are independent. Let the existing route
               Worker evaluate them away from the UI thread; latestOnly=false
               prevents sibling candidates from cancelling one another. */
            const outdoorResult = await dijkstraAsync(startNodeKey, outdoorNodeKey, {
                latestOnly: false
            });
            if (!outdoorResult) return null;

            const indoorResult = dijkstraIndoor(
                indoorGraphData.graph,
                indoorStartNodeKey,
                roomNodeKey
            );
            if (!indoorResult) return null;

            const directDoorMeters = startLatLng ? map.distance(
                [startLatLng.lat, startLatLng.lng],
                [Number(outdoorEntrance.latitude), Number(outdoorEntrance.longitude)]
            ) : Number(outdoorResult.totalCost || 0);

            const primaryOrMain = isPrimaryOrMain(outdoorEntrance, indoorEntranceFeature);
            const sideEntrance = isSideEntrance(outdoorEntrance, indoorEntranceFeature);
            const isSameFloorEntrance = indoorEntranceFloor === roomFloor;
            const usesCoveredStairs = routeUsesCoveredStairs(outdoorResult);

            return {
                link,
                outdoorEntrance,
                indoorEntranceFeature,
                indoorGraphData,
                outdoorResult,
                indoorResult,
                indoorEntranceFloor,
                roomFloor,
                floorDiff,
                isSameFloorEntrance,
                usesCoveredStairs,
                primaryOrMain,
                sideEntrance,
                directDoorMeters,
                outdoorCost: Number(outdoorResult.totalCost || 0),
                indoorCost: Number(indoorResult.totalCost || 0),
                totalCost: Number(outdoorResult.totalCost || 0) + Number(indoorResult.totalCost || 0)
            };
        }));
        const validCandidates = candidateResults.filter(Boolean);

        if (!validCandidates.length) {
            return null;
        }

        const nearestAny = [...validCandidates].sort((a, b) => a.directDoorMeters - b.directDoorMeters)[0];
        const minFloorDiff = Math.min(...validCandidates.map(c => Number(c.floorDiff || 0)));

        function smartScore(c) {
            const isUpperRoom = roomFloor > 1;
            const wrongFloorPenalty = isUpperRoom ? (c.floorDiff * UPPER_CLOSEST_FLOOR_BIG_PENALTY) : 0;
            const sameFloorBonus = isUpperRoom && c.isSameFloorEntrance ? SAME_FLOOR_BONUS : 0;
            const closestFloorBonus = isUpperRoom && c.floorDiff === minFloorDiff ? CLOSEST_FLOOR_BONUS : 0;
            const mainBonus = c.primaryOrMain ? MAIN_PRIMARY_BONUS : 0;
            const sidePenalty = isUpperRoom && c.sideEntrance && c.floorDiff > 0 ? SIDE_ENTRANCE_PENALTY_FOR_UPPER : 0;

            return wrongFloorPenalty +
                c.directDoorMeters +
                (c.outdoorCost * OUTDOOR_WEIGHT) +
                (c.indoorCost * INDOOR_WEIGHT) +
                sidePenalty -
                sameFloorBonus -
                closestFloorBonus -
                mainBonus;
        }

        function sortSmart(a, b) {
            const aScore = smartScore(a);
            const bScore = smartScore(b);
            if (aScore !== bScore) return aScore - bScore;

            if (a.floorDiff !== b.floorDiff) return a.floorDiff - b.floorDiff;
            if (a.primaryOrMain !== b.primaryOrMain) return a.primaryOrMain ? -1 : 1;
            if (a.outdoorCost !== b.outdoorCost) return a.outdoorCost - b.outdoorCost;
            if (a.directDoorMeters !== b.directDoorMeters) return a.directDoorMeters - b.directDoorMeters;
            return a.indoorCost - b.indoorCost;
        }

        /*
        |--------------------------------------------------------------------------
        | RULE 0: Shortest complete ground/first-floor route.
        |--------------------------------------------------------------------------
        | Compare the walk to each outdoor entrance plus its linked indoor route
        | to the room. Main wins only when the choices are within a small tie
        | margin; a meaningfully shorter side entrance wins automatically.
        */
        if (roomFloor <= 1) {
            return window.WayfindingRouting.selectBestEntranceCandidate(
                validCandidates,
                INDOOR_WEIGHT,
                MAIN_ENTRANCE_TIE_METERS
            );
        }

        /*
        |--------------------------------------------------------------------------
        | RULE 1: Exact same-floor doorway lock.
        |--------------------------------------------------------------------------
        */
        const sameFloorDoorwayCandidates = validCandidates
            .filter(c => c.isSameFloorEntrance && c.directDoorMeters <= SAME_FLOOR_DOORWAY_LOCK_METERS)
            .sort(sortSmart);

        if (sameFloorDoorwayCandidates.length) {
            const chosen = sameFloorDoorwayCandidates[0];
            return chosen;
        }

        /*
        |--------------------------------------------------------------------------
        | RULE 2: Upper floor closest-floor priority.
        |--------------------------------------------------------------------------
        | 3F destination + 2F main entrance + 1F side entrance:
        | minFloorDiff = 1, so all 2F entrances are considered before 1F entrances.
        */
        if (roomFloor > 1) {
            const closestFloorCandidates = validCandidates
                .filter(c => c.floorDiff === minFloorDiff)
                .sort(sortSmart);

            if (closestFloorCandidates.length) {
                const bestClosestFloor = closestFloorCandidates[0];

                const closestFloorStillPractical =
                    bestClosestFloor.directDoorMeters <= nearestAny.directDoorMeters + UPPER_CLOSEST_FLOOR_DIRECT_EXTRA_M ||
                    bestClosestFloor.outdoorCost <= nearestAny.outdoorCost + UPPER_CLOSEST_FLOOR_OUTDOOR_EXTRA_M ||
                    bestClosestFloor.totalCost <= nearestAny.totalCost + UPPER_CLOSEST_FLOOR_TOTAL_EXTRA_M;

                if (closestFloorStillPractical) {
                    return bestClosestFloor;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RULE 3: Wrong-floor doorway lock only when almost beside it.
        |--------------------------------------------------------------------------
        */
        const wrongFloorDoorwayCandidates = validCandidates
            .filter(c => {
                if (roomFloor <= 1) return c.directDoorMeters <= SAME_FLOOR_DOORWAY_LOCK_METERS;
                if (c.isSameFloorEntrance) return c.directDoorMeters <= SAME_FLOOR_DOORWAY_LOCK_METERS;
                return c.directDoorMeters <= WRONG_FLOOR_DOORWAY_LOCK_METERS;
            })
            .sort(sortSmart);

        if (wrongFloorDoorwayCandidates.length) {
            const chosen = wrongFloorDoorwayCandidates[0];
            return chosen;
        }

        /*
        |--------------------------------------------------------------------------
        | RULE 4: 1F room same-floor practicality.
        |--------------------------------------------------------------------------
        */
        if (roomFloor === 1) {
            const sameFloorCandidates = validCandidates
                .filter(c => c.isSameFloorEntrance)
                .sort(sortSmart);

            if (sameFloorCandidates.length) {
                const bestSameFloor = sameFloorCandidates[0];
                const sameFloorStillPractical =
                    bestSameFloor.directDoorMeters <= nearestAny.directDoorMeters + ROOM_1F_SAME_FLOOR_WINDOW_M;

                if (sameFloorStillPractical) {
                    return bestSameFloor;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RULE 5: Final smart score fallback.
        |--------------------------------------------------------------------------
        */
        const chosen = [...validCandidates].sort(sortSmart)[0];
        return chosen;
    }


    async function computeCompleteRouteToRoom(roomFeature) {
        if (!roomFeature) return;

        if (!startNodeKey) {
            alert('Please choose your starting point first.');
            return;
        }

        const routeRequestId = ++completeRoomRouteRequestSequence;

        selectedIndoorRoomFeature = roomFeature;
        selectedDestinationBuildingId = Number(roomFeature.properties?.building_id);

        await ensureIndoorBuildingData(selectedDestinationBuildingId);
        if (routeRequestId !== completeRoomRouteRequestSequence) return;

        const bestRoute = await findBestEntranceLinkForRoom(roomFeature);
        if (routeRequestId !== completeRoomRouteRequestSequence) return;

        if (!bestRoute) {
            alert('No complete outdoor + indoor route found for this room.');
            setRouteResultLabel('No complete outdoor + indoor route found.');
            return;
        }

        drawOutdoorRoute(bestRoute.outdoorResult);

        clearDestinationMarker();
        destinationMarker = L.marker(
            [Number(bestRoute.outdoorEntrance.latitude), Number(bestRoute.outdoorEntrance.longitude)], {
                icon: createDivIcon('<div class="route-destination-dot"></div>', [18, 18], [9, 9])
            }
        ).addTo(map).bindPopup(bestRoute.outdoorEntrance.name || 'Building Entrance');

        selectedBuildingEntranceId = Number(bestRoute.outdoorEntrance.id);

        lastIndoorRoutePackage = {
            indoorGraphData: bestRoute.indoorGraphData,
            indoorResult: bestRoute.indoorResult,
            entranceFeature: bestRoute.indoorEntranceFeature,
            roomFeature: roomFeature
        };

        persistentIndoorRouteByFloor = buildIndoorRouteByFloor(
            bestRoute.indoorGraphData,
            bestRoute.indoorResult
        );

        pendingIndoorOpenForBuildingId = Number(roomFeature.properties?.building_id);

        /*
        |--------------------------------------------------------------------------
        | INDOOR FIRST VIEW FIX
        |--------------------------------------------------------------------------
        | After Find Route, do NOT open indoor directly on the destination floor.
        | Open first on the actual indoor entrance floor so the user can see where
        | they entered the building, then they can follow stairs/floor buttons to
        | the destination floor.
        |--------------------------------------------------------------------------
        */
        const chosenIndoorEntranceFloorForFirstView = Number(
            bestRoute.indoorEntranceFeature?.properties?.floor_number ??
            roomFeature.properties?.floor_number ??
            0
        );

        pendingIndoorFocusFloor = chosenIndoorEntranceFloorForFirstView;

        updateRouteLabels();

        const floorsWithRoute = Object.keys(persistentIndoorRouteByFloor)
            .map(Number)
            .sort((a, b) => a - b)
            .map(f => `${f}F`)
            .join(', ');

        const chosenIndoorEntranceFloor = Number(bestRoute.indoorEntranceFeature?.properties?.floor_number || 0);
        const chosenIndoorEntranceName = bestRoute.indoorEntranceFeature?.properties?.name || 'Indoor Entrance';

        const coveredStairsNote = bestRoute.usesCoveredStairs ? ' via covered stairs' : '';

        setRouteResultLabel(
            `Route ready via nearest path + entrance${coveredStairsNote}: ${chosenIndoorEntranceName} (${chosenIndoorEntranceFloor}F). Indoor floors: ${floorsWithRoute || (roomFeature.properties?.floor_label || '1F')}`
        );
        if (typeof showRoutePopupForSelectedBuilding === 'function') {
            showRoutePopupForSelectedBuilding(selectedDestinationBuildingId);
        }
    }

    async function findRouteByDestination() {
        /*
        | Before: always ensureDefaultStartBeforeRoute(), so Campus Event routes
        | used Default Start even when the user selected GPS or Pick Path.
        |
        | Now: respect selectedStartMode.
        */
        if (!await ensureSelectedStartBeforeRoute()) return;

        const destinationType = getDestinationType();

        if (destinationType === 'building') {
            const buildingId = Number(destinationBuildingSelect?.value || selectedDestinationBuildingId);

            if (!buildingId) {
                alert('Please choose a destination building.');
                return;
            }

            selectedDestinationBuildingId = buildingId;
            closeBrowseOptionsModal();
            findRouteToBuilding(buildingId);
            return;
        }

        if (destinationType === 'landuse') {
            const landuseId = Number(destinationLanduseSelect?.value || selectedDestinationLanduseId);

            if (!landuseId) {
                alert('Please choose a landuse area.');
                return;
            }

            selectedDestinationLanduseId = landuseId;
            closeBrowseOptionsModal();
            findRouteToLanduse(landuseId);
            return;
        }

        if (destinationType === 'room') {
            const roomId = Number(destinationRoomSelect?.value);

            let room = selectedIndoorRoomFeature;

            if (!room || Number(room.properties?.id) !== roomId) {
                room = (allIndoorRooms.features || []).find(
                    f => Number(f.properties?.id) === roomId
                );
            }

            if (!room) {
                alert('Please choose a room or office.');
                return;
            }

            selectedIndoorRoomFeature = room;
            selectedDestinationBuildingId = Number(room.properties?.building_id);
            closeBrowseOptionsModal();
            await computeCompleteRouteToRoom(room);
            return;
        }
    }

    function resetRouteSelection() {
        startNodeKey = null;
        selectedDestinationBuildingId = null;
        selectedDestinationLanduseId = null;
        selectedBuildingEntranceId = null;
        selectedIndoorRoomFeature = null;
        currentIndoorBuildingId = null;
        currentIndoorFloor = null;
        placingStartMode = false;
        startSourceType = null;
        hidePickPathHelper();

        lastIndoorRoutePackage = null;
        persistentIndoorRouteByFloor = {};
        pendingIndoorOpenForBuildingId = null;
        pendingIndoorFocusFloor = null;

        clearStartMarker();
        clearDestinationMarker();
        clearCurrentLocationMarker();
        clearRouteLayer();
        clearOutsideGuideLine();

        if (indoorMap) {
            clearIndoorRoute();
        }

        if (destinationBuildingSelect) destinationBuildingSelect.value = '';
        if (destinationLanduseSelect) destinationLanduseSelect.value = '';
        if (destinationRoomSelect) destinationRoomSelect.value = '';
        if (roomBuildingFilterSelect) roomBuildingFilterSelect.value = '';
        if (roomOfficeSearchInput) roomOfficeSearchInput.value = '';
        browseRoomSelectedFloor = 'all';
        if (destinationSearchInput) destinationSearchInput.value = '';
        if (defaultEntrySelect) defaultEntrySelect.value = '';
        if (destinationTypeSelect) destinationTypeSelect.value = 'building';
        if (indoorFloorSelect) indoorFloorSelect.innerHTML = '<option value="">Select Floor</option>';
        if (indoorRoomSearch) indoorRoomSearch.value = '';

        updateDestinationUi();
        updateRouteLabels();
        setRouteResultLabel('No route yet');

        roomList.innerHTML = '';
        closeIndoorPanelFn();
        if (typeof closeRouteBuildingPopup === 'function') {
            closeRouteBuildingPopup();
        }

        if (campusBounds?.isValid?.()) {
            map.fitBounds(campusBounds, {
                padding: [50, 50],
                maxZoom: IS_MOBILE_OUTDOOR_VIEW
                    ? MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE
                    : 18.5,
                animate: !IS_MOBILE_OUTDOOR_VIEW
            });
        }
    }

    window.routeToIndoorRoom = function(roomId) {
        const room = (allIndoorRooms.features || []).find(f =>
            Number(f.properties?.id) === Number(roomId) &&
            Number(f.properties?.building_id) === Number(currentIndoorBuildingId) &&
            Number(f.properties?.floor_number) === Number(currentIndoorFloor)
        );

        if (!room) return;

        selectedIndoorRoomFeature = room;
        computeCompleteRouteToRoom(room);
    };
