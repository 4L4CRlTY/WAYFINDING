    /*
    |--------------------------------------------------------------------------
    | MOBILE OUTDOOR ZOOM SETTINGS
    |--------------------------------------------------------------------------
    | Mobile only ni siya.
    | Default outdoor map zoom = 18
    | Route / navigation outdoor zoom = 17
    | Lower value = mas zoom out.
    */
    const IS_MOBILE_OUTDOOR_VIEW = window.matchMedia('(max-width: 768px)').matches;
    const MOBILE_OUTDOOR_MIN_ZOOM_VALUE = 17;
    const MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE = 18;
    const MOBILE_OUTDOOR_ROUTE_ZOOM_VALUE = 17;
    const MOBILE_OUTDOOR_MAX_ZOOM_VALUE = 19;

    const map = L.map('map', {
        zoomControl: true,

        /*
        |--------------------------------------------------------------------------
        | MAP ROTATION NOTE
        |--------------------------------------------------------------------------
        | Leaflet does not natively rotate all custom panes/GeoJSON layers.
        | Rotation is handled below by visually rotating the main Leaflet map pane,
        | so tiles + buildings + paths + landuse overlays rotate together.
        */
        minZoom: IS_MOBILE_OUTDOOR_VIEW ? MOBILE_OUTDOOR_MIN_ZOOM_VALUE : 18.3,
        maxZoom: 19,
        zoomSnap: IS_MOBILE_OUTDOOR_VIEW ? 0.5 : 1,
        zoomDelta: IS_MOBILE_OUTDOOR_VIEW ? 0.5 : 1,

        /*
        |--------------------------------------------------------------------------
        | SMOOTHNESS SETTINGS
        |--------------------------------------------------------------------------
        | These reduce wheel/touch over-processing and keep interaction responsive.
        | Routing logic is unchanged.
        */
        wheelDebounceTime: 80,
        wheelPxPerZoomLevel: 90,
        bounceAtZoomLimits: false,


        /*
        |--------------------------------------------------------------------------
        | LANDUSE IMAGE LUHAG HARD FIX
        |--------------------------------------------------------------------------
        | Disable Leaflet zoom animation so image overlays will not shake/fly
        | during zoom in/out. The map still zooms normally, but without animated
        | stretching that causes landuse image overlays to look loose.
        */
        /*
        | LANDUSE IMAGE STABLE FIX
        | Keep zoom animation OFF because the custom exact landuse SVG is
        | recalculated using map.latLngToLayerPoint(). If zoom animation is ON,
        | Leaflet scales vector layers while this custom SVG waits until zoomend,
        | so the landuse image looks like it jumps then returns.
        | Tiles are still smooth because updateWhenZooming + keepBuffer remain enabled.
        */
        zoomAnimation: false,
        fadeAnimation: false,
        markerZoomAnimation: false,
        inertia: true
    });

    map.createPane('pathsPane');
    map.getPane('pathsPane').style.zIndex = 400;

    map.createPane('buildingsPane');
    map.getPane('buildingsPane').style.zIndex = 450;

    map.createPane('landuseImagePane');
    map.getPane('landuseImagePane').style.zIndex = 430;
    map.getPane('landuseImagePane').style.pointerEvents = 'none';

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_nolabels/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        maxZoom: 19,
        maxNativeZoom: 19,
        detectRetina: !IS_MOBILE_OUTDOOR_VIEW,

        /*
        |--------------------------------------------------------------------------
        | TILE SMOOTHNESS
        |--------------------------------------------------------------------------
        | Load fewer tiles while moving/zooming. This helps mobile smoothness.
        */
        updateWhenIdle: false,
        updateWhenZooming: true,
        keepBuffer: IS_MOBILE_OUTDOOR_VIEW ? 3 : 4
    }).addTo(map);

    /*
    |--------------------------------------------------------------------------
    | FULL VISUAL MAP ROTATION HELPERS
    |--------------------------------------------------------------------------
    | This version rotates the WHOLE Leaflet map pane, not only the basemap.
    | Result: CARTO/OSM tiles + GeoJSON buildings + paths + markers + labels +
    | custom panes rotate together.
    |
    | Important: This is a visual rotation. For the most accurate Pick Path click,
    | reset to North (0°) before choosing a start point.
    */
    window.currentCampusMapBearing = window.currentCampusMapBearing || 0;
    window.campusVisualRotationFrame = window.campusVisualRotationFrame || null;

    function normalizeCampusBearing(degrees) {
        let value = Number(degrees) || 0;
        value = value % 360;

        if (value < 0) {
            value += 360;
        }

        return value;
    }

    function stripCampusRotationFromTransform(transformValue) {
        return String(transformValue || '')
            .replace(/\s*rotateZ?\([^)]*\)/gi, '')
            .trim();
    }

    function updateCampusRotationUi() {
        const bearing = normalizeCampusBearing(window.currentCampusMapBearing || 0);
        const label = document.getElementById('campus-rotate-value');
        const arrow = document.querySelector('.campus-compass-arrow');

        if (label) {
            label.textContent = `${Math.round(bearing)}°`;
        }

        if (arrow) {
            arrow.style.transform = `rotate(${-bearing}deg)`;
        }
    }

    function getCampusRotatablePanes() {
        if (typeof map === 'undefined' || !map || !map.getPanes) {
            return [];
        }

        const panes = map.getPanes();

        /*
        | We do NOT rotate mapPane directly because Leaflet uses mapPane
        | for pan/zoom translation. If we rotate mapPane itself, GeoJSON can
        | look like it slides or floats while Leaflet updates transforms.
        |
        | Instead, rotate the child panes using the same center origin.
        | This keeps tiles, SVG GeoJSON, paths, buildings, markers, popups,
        | labels, and custom panes visually locked together.
        */
        return [
            panes.tilePane,
            panes.overlayPane,
            panes.shadowPane,
            panes.markerPane,
            panes.tooltipPane,
            panes.popupPane,
            panes.pathsPane,
            panes.buildingsPane,
            panes.landuseImagePane
        ].filter(Boolean);
    }

    function applyCampusVisualRotation() {
        if (typeof map === 'undefined' || !map || !map.getPanes) {
            return;
        }

        const panes = map.getPanes();
        const mapPane = panes?.mapPane;

        if (!mapPane) {
            return;
        }

        const bearing = normalizeCampusBearing(window.currentCampusMapBearing || 0);
        const mapSize = map.getSize ? map.getSize() : L.point(0, 0);
        const panePosition = L.DomUtil.getPosition(mapPane) || L.point(0, 0);

        /*
        | Because all child panes live inside mapPane, their correct rotation
        | origin is the visible screen center translated into mapPane-local
        | coordinates. This prevents even small shifting while rotating.
        */
        const originX = (mapSize.x / 2) - panePosition.x;
        const originY = (mapSize.y / 2) - panePosition.y;

        const rotatablePanes = getCampusRotatablePanes();

        rotatablePanes.forEach((pane) => {
            const baseTransform = stripCampusRotationFromTransform(pane.style.transform);

            pane.style.transformOrigin = `${originX}px ${originY}px`;
            pane.style.webkitTransformOrigin = `${originX}px ${originY}px`;

            if (bearing === 0) {
                pane.style.transform = baseTransform;
                pane.style.webkitTransform = baseTransform;
                pane.style.willChange = '';
                pane.classList.remove('campus-layer-pane-rotated');
            } else {
                const rotatedTransform = `${baseTransform} rotate(${bearing}deg)`.trim();
                pane.style.transform = rotatedTransform;
                pane.style.webkitTransform = rotatedTransform;
                pane.style.willChange = 'transform';
                pane.classList.add('campus-layer-pane-rotated');
            }
        });

        /* Make sure mapPane itself is not rotated. Leaflet owns this transform. */
        mapPane.classList.remove('campus-map-pane-rotated');

        updateCampusRotationUi();
    }

    function scheduleCampusVisualRotation() {
        if (window.campusVisualRotationFrame) {
            cancelAnimationFrame(window.campusVisualRotationFrame);
        }

        window.campusVisualRotationFrame = requestAnimationFrame(() => {
            window.campusVisualRotationFrame = null;
            applyCampusVisualRotation();
        });
    }

    function setCampusMapBearing(degrees) {
        window.currentCampusMapBearing = normalizeCampusBearing(degrees);
        scheduleCampusVisualRotation();

        /*
        | When the map is visually rotated, normal Leaflet dragging feels wrong
        | because Leaflet still calculates drag direction as if the map is north-up.
        | Disable native dragging while rotated; the custom rotated drag fix below
        | will handle screen drag correctly. Restore normal dragging at 0°.
        */
        if (typeof map !== 'undefined' && map) {
            const rotated = Math.abs((window.currentCampusMapBearing || 0) % 360) > 0.01;
            const container = map.getContainer ? map.getContainer() : null;

            if (container) {
                container.classList.toggle('campus-rotated-view', rotated);
            }

            if (map.dragging) {
                if (rotated) {
                    map.dragging.disable();
                } else {
                    map.dragging.enable();
                }
            }
        }
    }

    function rotateMapLeft() {
        setCampusMapBearing((window.currentCampusMapBearing || 0) - 15);
    }

    function rotateMapRight() {
        setCampusMapBearing((window.currentCampusMapBearing || 0) + 15);
    }

    function resetMapRotation() {
        setCampusMapBearing(0);
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateCampusRotationUi();
        scheduleCampusVisualRotation();
    });


    /*
    |--------------------------------------------------------------------------
    | SMOOTH ROTATED MAP DRAG FIX
    |--------------------------------------------------------------------------
    | CSS rotation keeps GeoJSON locked to the basemap, but Leaflet's native
    | drag does not understand the rotated screen direction. This version is
    | smoother because it does not call panBy on every pointermove. It batches
    | movement using requestAnimationFrame, then pans once per frame.
    */
    (function installCampusSmoothRotatedDragFix() {
        if (window.campusSmoothRotatedDragFixInstalled) return;
        window.campusSmoothRotatedDragFixInstalled = true;

        let isDragging = false;
        let activePointerId = null;
        let lastPoint = null;
        let pendingPanX = 0;
        let pendingPanY = 0;
        let dragFrame = null;
        let lastMoveTime = 0;
        let velocityX = 0;
        let velocityY = 0;
        let inertiaFrame = null;

        function getBearing() {
            return Number(window.currentCampusMapBearing || 0);
        }

        function isRotated() {
            return Math.abs(getBearing() % 360) > 0.01;
        }

        function getContainer() {
            if (typeof map === 'undefined' || !map || !map.getContainer) return null;
            return map.getContainer();
        }

        function shouldIgnoreDrag(event) {
            const target = event.target;
            if (!target || !target.closest) return false;

            return !!target.closest(
                '.campus-rotate-control, ' +
                '.leaflet-control, ' +
                '.leaflet-popup, ' +
                '.leaflet-tooltip, ' +
                '.ai-floating-dock, ' +
                '.floating-modal-backdrop, ' +
                '.user-profile-wrap, ' +
                '.campus-brand-wrap, ' +
                '.indoor-panel, ' +
                '.indoor-backdrop, ' +
                'button, select, input, textarea, a'
            );
        }

        function screenDeltaToLeafletPan(dx, dy) {
            const rad = getBearing() * Math.PI / 180;
            const cos = Math.cos(rad);
            const sin = Math.sin(rad);

            /*
            | Convert rotated screen drag into the unrotated Leaflet pan vector.
            | This makes the map follow the finger/mouse direction naturally.
            */
            return {
                x: -(dx * cos + dy * sin),
                y: (dx * sin - dy * cos)
            };
        }

        function flushPan() {
            dragFrame = null;

            if (!isDragging && Math.abs(pendingPanX) < 0.001 && Math.abs(pendingPanY) < 0.001) {
                return;
            }

            const x = pendingPanX;
            const y = pendingPanY;
            pendingPanX = 0;
            pendingPanY = 0;

            if (Math.abs(x) > 0.001 || Math.abs(y) > 0.001) {
                map.panBy([x, y], {
                    animate: false,
                    duration: 0,
                    easeLinearity: 1,
                    noMoveStart: true
                });

                if (typeof scheduleCampusVisualRotation === 'function') {
                    scheduleCampusVisualRotation();
                }
            }
        }

        function requestFlush() {
            if (dragFrame) return;
            dragFrame = requestAnimationFrame(flushPan);
        }

        function cancelInertia() {
            if (inertiaFrame) {
                cancelAnimationFrame(inertiaFrame);
                inertiaFrame = null;
            }
        }

        function runTinyInertia() {
            /*
            | Very small inertia only. Too much inertia feels wrong on rotated
            | CSS maps, so this gives a soft finish without sliding too far.
            */
            let vx = velocityX * 0.55;
            let vy = velocityY * 0.55;

            function step() {
                vx *= 0.86;
                vy *= 0.86;

                if (!isRotated() || (Math.abs(vx) < 0.08 && Math.abs(vy) < 0.08)) {
                    inertiaFrame = null;
                    return;
                }

                const pan = screenDeltaToLeafletPan(vx, vy);
                map.panBy([pan.x, pan.y], {
                    animate: false,
                    duration: 0,
                    easeLinearity: 1,
                    noMoveStart: true
                });

                if (typeof scheduleCampusVisualRotation === 'function') {
                    scheduleCampusVisualRotation();
                }

                inertiaFrame = requestAnimationFrame(step);
            }

            if (Math.abs(vx) > 0.2 || Math.abs(vy) > 0.2) {
                inertiaFrame = requestAnimationFrame(step);
            }
        }

        function stopDrag(event) {
            if (!isDragging) return;

            const container = getContainer();
            isDragging = false;

            if (dragFrame) {
                cancelAnimationFrame(dragFrame);
                dragFrame = null;
            }
            flushPan();

            if (container) {
                container.classList.remove('campus-rotated-dragging');

                try {
                    if (activePointerId !== null && container.releasePointerCapture) {
                        container.releasePointerCapture(activePointerId);
                    }
                } catch (e) {}
            }

            activePointerId = null;
            lastPoint = null;
            lastMoveTime = 0;

            runTinyInertia();

            if (event && event.preventDefault) {
                event.preventDefault();
            }
        }

        function setupWhenMapReady() {
            const container = getContainer();
            if (!container) {
                setTimeout(setupWhenMapReady, 300);
                return;
            }

            container.addEventListener('pointerdown', function(event) {
                if (!isRotated()) return;
                if (shouldIgnoreDrag(event)) return;
                if (event.pointerType === 'mouse' && event.button !== 0) return;

                cancelInertia();

                isDragging = true;
                activePointerId = event.pointerId;
                lastPoint = {
                    x: event.clientX,
                    y: event.clientY
                };
                lastMoveTime = performance.now();
                velocityX = 0;
                velocityY = 0;
                pendingPanX = 0;
                pendingPanY = 0;

                container.classList.add('campus-rotated-dragging');

                if (map.dragging && map.dragging.enabled()) {
                    map.dragging.disable();
                }

                try {
                    if (container.setPointerCapture) {
                        container.setPointerCapture(event.pointerId);
                    }
                } catch (e) {}

                event.preventDefault();
            }, { passive: false });

            container.addEventListener('pointermove', function(event) {
                if (!isDragging || !lastPoint) return;
                if (activePointerId !== null && event.pointerId !== activePointerId) return;

                const now = performance.now();
                const dx = event.clientX - lastPoint.x;
                const dy = event.clientY - lastPoint.y;
                const dt = Math.max(8, now - lastMoveTime);

                lastPoint = {
                    x: event.clientX,
                    y: event.clientY
                };
                lastMoveTime = now;

                /* Smooth velocity tracking for a soft release. */
                velocityX = (velocityX * 0.75) + ((dx / dt) * 16.67 * 0.25);
                velocityY = (velocityY * 0.75) + ((dy / dt) * 16.67 * 0.25);

                const pan = screenDeltaToLeafletPan(dx, dy);
                pendingPanX += pan.x;
                pendingPanY += pan.y;

                requestFlush();
                event.preventDefault();
            }, { passive: false });

            container.addEventListener('pointerup', stopDrag, { passive: false });
            container.addEventListener('pointercancel', stopDrag, { passive: false });
            container.addEventListener('lostpointercapture', stopDrag, { passive: false });
        }

        setupWhenMapReady();
    })();


    function updateShadows() {
        /*
        |--------------------------------------------------------------------------
        | 3-LAYER LIGHTWEIGHT FAKE 3D DEPTH SCALE
        |--------------------------------------------------------------------------
        | Keeps 3D visible without making the side too thick.
        | Updates after zoom settles through runtime patch.
        */
        document.documentElement.style.setProperty('--step', '1px');

        if (typeof map === 'undefined' || !map) {
            return;
        }

        const isMobileView = window.matchMedia('(hover: none), (max-width: 768px)').matches;

        if (!isMobileView) {
            document.documentElement.style.setProperty('--mobile-side-1', '1px');
            document.documentElement.style.setProperty('--mobile-side-2', '2px');
            document.documentElement.style.setProperty('--mobile-side-3', '3px');
            document.documentElement.style.setProperty('--mobile-edge-width', '1.35');

            if (typeof updateBuildingPerformanceMode === 'function') {
                updateBuildingPerformanceMode();
            }

            return;
        }

        const zoom = map.getZoom ? map.getZoom() : 18;
        const baseZoom = (typeof MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE !== 'undefined' && MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE)
            ? MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE
            : 18;

        const zoomDiff = zoom - baseZoom;
        let scale = Math.pow(2, zoomDiff * 0.08);
        scale = Math.max(0.60, Math.min(0.92, scale));

        const round2 = (n) => Math.round(n * 100) / 100;

        const d1 = Math.max(0.65, round2(1.00 * scale));
        const d2 = Math.max(1.15, round2(1.85 * scale));
        const d3 = Math.max(1.65, round2(2.70 * scale));
        const edgeWidth = Math.max(1.18, Math.min(1.38, round2(1.35 * scale)));

        document.documentElement.style.setProperty('--mobile-side-1', `${d1}px`);
        document.documentElement.style.setProperty('--mobile-side-2', `${d2}px`);
        document.documentElement.style.setProperty('--mobile-side-3', `${d3}px`);
        document.documentElement.style.setProperty('--mobile-edge-width', `${edgeWidth}`);

        if (typeof updateBuildingPerformanceMode === 'function') {
            updateBuildingPerformanceMode();
        }
    }
    map.on('zoom move moveend zoomend viewreset resize', () => {
        scheduleCampusVisualRotation();
    });

    map.on('zoomend', () => {
        updateShadows();
        updateRouteBuildingPopupScale();
        scheduleCampusVisualRotation();
    });

    map.on('moveend viewreset resize', () => {
        updateRouteBuildingPopupScale();
        scheduleCampusVisualRotation();
    });

    let campusBounds = null;
    let buildingRecords = [];
    let landuseRecords = [];
    let buildingEntrances = [];
    let entryPoints = [];
    let hazardPoints = [];
    let pathGeojson = null;

    let landuseLayer = null;
    let landuseLabelLayer = null;
    let landuseImageLayer = null;

    let allIndoorMaps = [];
    let allIndoorRooms = {
        type: 'FeatureCollection',
        features: []
    };
    let allIndoorPaths = {
        type: 'FeatureCollection',
        features: []
    };
    let allIndoorEntrances = {
        type: 'FeatureCollection',
        features: []
    };
    let allBuildingEntranceLinks = [];
    let allIndoorStairsLinks = [];

    let outdoorGraph = {};
    let outdoorNodeCoords = {};
    let outdoorEdgeMeta = {};

    let startNodeKey = null;
    let selectedDestinationBuildingId = null;
    let selectedDestinationLanduseId = null;
    let selectedBuildingEntranceId = null;
    let selectedIndoorRoomFeature = null;

    let placingStartMode = false;
    let startSourceType = null;

    let startMarker = null;
    let destinationMarker = null;
    let currentLocationMarker = null;
    let routeLayer = null;
    let outsideGuideLine = null;
    let hazardLayer = null;
    let outdoorRouteAnimationTimer = null;

    let campusEvents = [];
    let campusEventLayer = null;

    let indoorMap = null;
    let indoorRoomsLayer = null;
    let indoorPathsLayer = null;
    let indoorEntrancesLayer = null;
    let indoorRouteLayer = null;
    let indoorRouteAnimationTimer = null;
    let indoorStartMarker = null;
    let indoorEndMarker = null;

    /*
    |--------------------------------------------------------------------------
    | PREMIUM INDOOR START ROUTE ARROW
    |--------------------------------------------------------------------------
    | Small mobile-friendly marker shown at the indoor entrance only.
    | It rotates toward the first indoor route segment so users know where
    | to start walking without covering the room labels/path.
    */
    function normalizeIndoorRoutePoint(point) {
        if (!point) return null;

        if (point instanceof L.LatLng) {
            return point;
        }

        if (Array.isArray(point) && point.length >= 2) {
            return L.latLng(Number(point[0]), Number(point[1]));
        }

        if (typeof point === 'object' && point.lat !== undefined && point.lng !== undefined) {
            return L.latLng(Number(point.lat), Number(point.lng));
        }

        return null;
    }

    function getIndoorStartArrowRotation(startLatLng, routePoints = []) {
        if (!indoorMap || !startLatLng || !Array.isArray(routePoints) || routePoints.length < 2) {
            return 0;
        }

        const start = normalizeIndoorRoutePoint(startLatLng);
        if (!start) return 0;

        let nextPoint = null;

        /*
        | Find the first point after the entrance that is far enough away.
        | This prevents wrong direction if duplicate/same nodes exist.
        */
        for (let i = 0; i < routePoints.length; i++) {
            const candidate = normalizeIndoorRoutePoint(routePoints[i]);
            if (!candidate) continue;

            if (start.distanceTo(candidate) > 0.05) {
                nextPoint = candidate;
                break;
            }
        }

        if (!nextPoint) return 0;

        const p1 = indoorMap.latLngToLayerPoint(start);
        const p2 = indoorMap.latLngToLayerPoint(nextPoint);
        const dx = p2.x - p1.x;
        const dy = p2.y - p1.y;

        if (Math.abs(dx) < 0.01 && Math.abs(dy) < 0.01) {
            return 0;
        }

        /* CSS arrow points upward by default. Rotate it toward the route. */
        return Math.atan2(dx, -dy) * 180 / Math.PI;
    }

    function createIndoorStartArrowMarker(latlng, routePoints = []) {
        /*
        | Keep Leaflet iconSize exactly matched with CSS marker size.
        | This fixes mobile misalignment where the pin was not centered on
        | the indoor entrance dot.
        */
        const isTinyPhone = window.matchMedia('(max-width: 420px)').matches;
        const isMobilePhone = window.matchMedia('(max-width: 768px)').matches;

        const markerSize = isTinyPhone
            ? [23, 31]
            : isMobilePhone
                ? [25, 34]
                : [30, 40];

        return L.marker(latlng, {
            interactive: true,
            keyboard: false,
            zIndexOffset: 100000,
            icon: L.divIcon({
                className: 'route-indoor-start-marker',
                html: `
                    <div class="route-indoor-start-wrap" aria-label="Indoor route start entrance">
                        <div class="route-indoor-start-pin">
                            <div class="route-indoor-start-hole"></div>
                            <div class="route-indoor-start-text">START</div>
                        </div>
                    </div>
                `,
                iconSize: markerSize,
                iconAnchor: [markerSize[0] / 2, markerSize[1]],
                popupAnchor: [0, -markerSize[1]]
            })
        });
    }

    let indoorImageLayer = null;
    let indoorGeometryDebugLayer = null;

    let currentIndoorBuildingId = null;
    let currentIndoorFloor = null;

    let lastIndoorRoutePackage = null;
    let persistentIndoorRouteByFloor = {};
    let pendingIndoorOpenForBuildingId = null;
    let pendingIndoorFocusFloor = null;

    let speechRecognition = null;
    let isVoiceListening = false;
    let voiceSupported = false;

    const defaultEntrySelect = document.getElementById('default-entry-select');
    const destinationTypeSelect = document.getElementById('destination-type-select');
    const destinationBuildingSelect = document.getElementById('destination-building-select');
    const destinationLanduseSelect = document.getElementById('destination-landuse-select');
    const destinationRoomSelect = document.getElementById('destination-room-select');
    const roomBuildingFilterSelect = document.getElementById('room-building-filter-select');
    const roomOfficeSearchInput = document.getElementById('room-office-search-input');
    const roomFloorFilterChips = document.getElementById('room-floor-filter-chips');
    const roomOfficeResultsList = document.getElementById('room-office-results-list');
    const roomResultCount = document.getElementById('room-result-count');
    let browseRoomSelectedFloor = 'all';
    const destinationSearchInput = document.getElementById('destination-search-input');
    const buildingDestinationWrap = document.getElementById('building-destination-wrap');
    const landuseDestinationWrap = document.getElementById('landuse-destination-wrap');
    const roomDestinationWrap = document.getElementById('room-destination-wrap');
    const voiceCommandBtn = document.getElementById('voice-command-btn');
    const voiceStatusLabel = document.getElementById('voice-status-label');
    const voiceHeardText = document.getElementById('voice-heard-text');
    const voiceHeardValue = document.getElementById('voice-heard-value');
    const destinationMenu = document.getElementById('destination-menu');
    const destinationMenuToggle = document.getElementById('destination-menu-toggle');
    const floatingActionCard = document.getElementById('floating-action-card');
    const textSearchModal = document.getElementById('textSearchModal');
    const browseOptionsModal = document.getElementById('browseOptionsModal');
    const pickPathHelper = document.getElementById('pick-path-helper');
    const pickPathHelperText = document.getElementById('pick-path-helper-text');

    let selectedStartMode = 'default';
