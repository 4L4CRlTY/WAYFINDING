<script>
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

    /*
    |--------------------------------------------------------------------------
    | EVENT / DESTINATION ROUTE START MODE FIX
    |--------------------------------------------------------------------------
    | If the user taps Route to Room from Campus Events, the route should not
    | always force Default Start. It now respects the currently selected start
    | mode: Default Route, Use GPS, or Pick Path.
    */
    let pendingRouteAfterPickPath = false;

    const routeModeLabel = document.getElementById('route-mode-label');
    const routeSourceLabel = document.getElementById('route-source-label');
    const routeStartLabel = document.getElementById('route-start-label');
    const routeDestinationTypeLabel = document.getElementById('route-destination-type-label');
    const routeEndLabel = document.getElementById('route-end-label');
    const routeGatewayLabel = document.getElementById('route-gateway-label');
    const routeResultLabel = document.getElementById('route-result-label');

    const indoorPanel = document.getElementById('indoorPanel');
    const indoorBackdrop = document.getElementById('indoorBackdrop');
    const indoorTitle = document.getElementById('indoorTitle');
    const indoorSubtitle = document.getElementById('indoorSubtitle');
    const indoorFloorSelect = document.getElementById('indoorFloorSelect');
    const indoorRoomSearch = document.getElementById('indoorRoomSearch');
    const roomList = document.getElementById('roomList');
    const indoorFooter = document.getElementById('indoorFooter');
    const closeIndoorPanel = document.getElementById('closeIndoorPanel');
    const indoorLoading = document.getElementById('indoorLoading');

    function setIndoorLoading(show = true) {
        indoorLoading.style.display = show ? 'flex' : 'none';
    }

    function safeSetText(element, text) {
        if (element) {
            element.textContent = text;
        }
    }

    function hasIndoorFloorValue(value) {
        return value !== null && value !== undefined && value !== '';
    }

    function formatIndoorFloorLabel(floorNumber, floorLabel = null) {
        if (floorLabel !== null && floorLabel !== undefined && String(floorLabel).trim() !== '') {
            return String(floorLabel);
        }

        if (!hasIndoorFloorValue(floorNumber)) {
            return '-';
        }

        const floor = Number(floorNumber);

        if (floor === 0) {
            return '0F / Basement';
        }

        return `Floor ${floor}`;
    }

    function setRouteResultLabel(text) {
        safeSetText(routeResultLabel, text);
    }

    function getDestinationType() {
        return destinationTypeSelect?.value || 'building';
    }

    function updateDestinationUi() {
        const type = getDestinationType();

        if (buildingDestinationWrap) {
            buildingDestinationWrap.style.display = type === 'building' ? 'block' : 'none';
        }

        if (landuseDestinationWrap) {
            landuseDestinationWrap.style.display = type === 'landuse' ? 'block' : 'none';
        }

        if (roomDestinationWrap) {
            roomDestinationWrap.style.display = type === 'room' ? 'block' : 'none';
        }

        document.querySelectorAll('.browse-type-card').forEach(card => {
            card.classList.toggle('active', card.dataset.destinationType === type);
        });

        if (type === 'room') {
            syncRoomBuildingFilterFromSelectedRoomOrBuilding();
            renderBrowseRoomPicker();
        }

        if (routeDestinationTypeLabel) {
            routeDestinationTypeLabel.textContent =
                type === 'room' ?
                'Room / Office' :
                type === 'landuse' ?
                'Landuse Area' :
                'Building';
        }
    }

    function setBrowseDestinationType(type) {
        if (!destinationTypeSelect) return;

        destinationTypeSelect.value = type;

        if (type === 'building') {
            selectedDestinationLanduseId = null;
            selectedIndoorRoomFeature = null;
            selectedBuildingEntranceId = null;
            if (destinationRoomSelect) destinationRoomSelect.value = '';
            if (destinationLanduseSelect) destinationLanduseSelect.value = '';
        } else if (type === 'landuse') {
            selectedDestinationBuildingId = null;
            selectedIndoorRoomFeature = null;
            selectedBuildingEntranceId = null;
            if (destinationBuildingSelect) destinationBuildingSelect.value = '';
            if (destinationRoomSelect) destinationRoomSelect.value = '';
        } else if (type === 'room') {
            selectedDestinationLanduseId = null;
            selectedBuildingEntranceId = null;
            if (destinationLanduseSelect) destinationLanduseSelect.value = '';
        }

        updateDestinationUi();
        updateRouteLabels();
    }

    function normalizeBrowseSearchText(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, ' ')
            .trim();
    }

    function escapeBrowseHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function getRoomBuildingId(room) {
        return Number(room?.properties?.building_id || 0);
    }

    function getRoomFloorNumber(room) {
        const raw = room?.properties?.floor_number;
        if (raw === null || raw === undefined || raw === '') return null;
        const floor = Number(raw);
        return Number.isFinite(floor) ? floor : null;
    }

    function getRoomDisplayName(room) {
        const p = room?.properties || {};
        return p.name || p.room_code || 'Room / Office';
    }

    function getRoomDisplayCode(room) {
        const p = room?.properties || {};
        return p.room_code ? String(p.room_code) : '';
    }

    function getFilteredBrowseRooms() {
        let rooms = [...(allIndoorRooms.features || [])];
        const selectedBuildingId = Number(roomBuildingFilterSelect?.value || 0);
        const searchText = normalizeBrowseSearchText(roomOfficeSearchInput?.value || '');

        if (selectedBuildingId) {
            rooms = rooms.filter(room => getRoomBuildingId(room) === selectedBuildingId);
        }

        if (browseRoomSelectedFloor !== 'all') {
            const selectedFloor = Number(browseRoomSelectedFloor);
            rooms = rooms.filter(room => getRoomFloorNumber(room) === selectedFloor);
        }

        if (searchText) {
            rooms = rooms.filter(room => {
                const p = room.properties || {};
                const haystack = normalizeBrowseSearchText([
                    p.name,
                    p.room_code,
                    p.type,
                    p.building_name,
                    p.floor_label,
                    formatIndoorFloorLabel(p.floor_number, p.floor_label)
                ].filter(Boolean).join(' '));

                return haystack.includes(searchText);
            });
        }

        return rooms.sort((a, b) => {
            const af = getRoomFloorNumber(a) ?? 999;
            const bf = getRoomFloorNumber(b) ?? 999;
            if (af !== bf) return af - bf;

            const ab = String(a.properties?.building_name || '').localeCompare(String(b.properties?.building_name || ''));
            if (ab !== 0) return ab;

            return String(getRoomDisplayName(a)).localeCompare(String(getRoomDisplayName(b)));
        });
    }

    function populateRoomBuildingFilterSelect() {
        if (!roomBuildingFilterSelect) return;

        const existingValue = roomBuildingFilterSelect.value;
        const buildingMap = new Map();

        (allIndoorRooms.features || []).forEach(room => {
            const p = room.properties || {};
            const buildingId = Number(p.building_id || 0);
            if (!buildingId) return;

            buildingMap.set(buildingId, p.building_name || getBuildingNameById(buildingId) || `Building ${buildingId}`);
        });

        const buildings = Array.from(buildingMap.entries())
            .sort((a, b) => String(a[1]).localeCompare(String(b[1])));

        roomBuildingFilterSelect.innerHTML = '<option value="">All Buildings</option>';
        buildings.forEach(([id, name]) => {
            roomBuildingFilterSelect.innerHTML += `<option value="${id}">${escapeBrowseHtml(name)}</option>`;
        });

        if (existingValue && Array.from(roomBuildingFilterSelect.options).some(opt => opt.value === existingValue)) {
            roomBuildingFilterSelect.value = existingValue;
        }
    }

    function syncRoomBuildingFilterFromSelectedRoomOrBuilding() {
        if (!roomBuildingFilterSelect) return;

        if (selectedIndoorRoomFeature?.properties?.building_id) {
            roomBuildingFilterSelect.value = String(selectedIndoorRoomFeature.properties.building_id);
            return;
        }

        if (selectedDestinationBuildingId) {
            const exists = Array.from(roomBuildingFilterSelect.options || []).some(
                opt => Number(opt.value) === Number(selectedDestinationBuildingId)
            );
            if (exists) roomBuildingFilterSelect.value = String(selectedDestinationBuildingId);
        }
    }

    function renderRoomFloorFilterChips() {
        if (!roomFloorFilterChips) return;

        const selectedBuildingId = Number(roomBuildingFilterSelect?.value || 0);
        let rooms = [...(allIndoorRooms.features || [])];

        if (selectedBuildingId) {
            rooms = rooms.filter(room => getRoomBuildingId(room) === selectedBuildingId);
        }

        const floorMap = new Map();
        rooms.forEach(room => {
            const floor = getRoomFloorNumber(room);
            if (floor === null) return;
            floorMap.set(floor, formatIndoorFloorLabel(floor, room.properties?.floor_label));
        });

        const floors = Array.from(floorMap.entries()).sort((a, b) => a[0] - b[0]);

        if (browseRoomSelectedFloor !== 'all' && !floorMap.has(Number(browseRoomSelectedFloor))) {
            browseRoomSelectedFloor = 'all';
        }

        let html = `
            <button type="button" class="room-floor-chip ${browseRoomSelectedFloor === 'all' ? 'active' : ''}" data-floor="all">
                All Floors
            </button>
        `;

        floors.forEach(([floor, label]) => {
            html += `
                <button type="button" class="room-floor-chip ${Number(browseRoomSelectedFloor) === Number(floor) ? 'active' : ''}" data-floor="${floor}">
                    ${escapeBrowseHtml(label)}
                </button>
            `;
        });

        roomFloorFilterChips.innerHTML = html;

        roomFloorFilterChips.querySelectorAll('.room-floor-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                browseRoomSelectedFloor = this.dataset.floor || 'all';
                renderBrowseRoomPicker();
            });
        });
    }

    function selectBrowseRoom(roomId) {
        const room = (allIndoorRooms.features || []).find(
            f => Number(f.properties?.id) === Number(roomId)
        );

        if (!room) return;

        selectedIndoorRoomFeature = room;
        selectedDestinationBuildingId = Number(room.properties?.building_id || 0) || null;
        selectedDestinationLanduseId = null;
        selectedBuildingEntranceId = null;

        if (destinationRoomSelect) {
            destinationRoomSelect.value = String(roomId);
        }

        if (roomBuildingFilterSelect && selectedDestinationBuildingId) {
            roomBuildingFilterSelect.value = String(selectedDestinationBuildingId);
        }

        if (destinationBuildingSelect && selectedDestinationBuildingId) {
            destinationBuildingSelect.value = String(selectedDestinationBuildingId);
        }

        renderBrowseRoomPicker();
        updateRouteLabels();
    }

    function renderBrowseRoomPicker() {
        populateRoomBuildingFilterSelect();
        renderRoomFloorFilterChips();

        if (!roomOfficeResultsList) return;

        const rooms = getFilteredBrowseRooms();
        const selectedId = Number(destinationRoomSelect?.value || selectedIndoorRoomFeature?.properties?.id || 0);

        if (roomResultCount) {
            roomResultCount.textContent = `${rooms.length} ${rooms.length === 1 ? 'room' : 'rooms'}`;
        }

        if (!rooms.length) {
            roomOfficeResultsList.innerHTML = `
                <div class="room-empty-state">
                    <div class="room-empty-icon">🔎</div>
                    <div class="room-empty-title">No room or office found</div>
                    <div class="room-empty-text">Try another building, floor, or search keyword.</div>
                </div>
            `;
            return;
        }

        const grouped = new Map();
        rooms.forEach(room => {
            const floor = getRoomFloorNumber(room);
            const floorLabel = formatIndoorFloorLabel(floor, room.properties?.floor_label);
            if (!grouped.has(floorLabel)) grouped.set(floorLabel, []);
            grouped.get(floorLabel).push(room);
        });

        let html = '';
        grouped.forEach((floorRooms, floorLabel) => {
            html += `
                <div class="room-floor-group">
                    <div class="room-floor-group-title">
                        <span>${escapeBrowseHtml(floorLabel)}</span>
                        <small>${floorRooms.length} ${floorRooms.length === 1 ? 'choice' : 'choices'}</small>
                    </div>
            `;

            floorRooms.slice(0, 80).forEach(room => {
                const p = room.properties || {};
                const roomId = Number(p.id);
                const active = selectedId === roomId;
                const code = getRoomDisplayCode(room);
                const type = p.type ? String(p.type).replaceAll('_', ' ') : 'Room / Office';
                const buildingName = p.building_name || getBuildingNameById(p.building_id) || 'Building';

                html += `
                    <button type="button" class="room-office-card ${active ? 'active' : ''}" onclick="selectBrowseRoom(${roomId})">
                        <span class="room-office-card-icon">${active ? '✅' : '🚪'}</span>
                        <span class="room-office-card-main">
                            <strong>${escapeBrowseHtml(getRoomDisplayName(room))}</strong>
                            <small>${escapeBrowseHtml([code, type].filter(Boolean).join(' · '))}</small>
                            <em>${escapeBrowseHtml(buildingName)}</em>
                        </span>
                    </button>
                `;
            });

            html += '</div>';
        });

        roomOfficeResultsList.innerHTML = html;
    }

    function updateRouteLabels() {
        safeSetText(routeModeLabel, placingStartMode ? 'Pick On Path Mode' : 'Idle');
        safeSetText(routeSourceLabel, startSourceType ? startSourceType.replaceAll('_', ' ') : 'None');
        safeSetText(routeStartLabel, startNodeKey || 'Not selected');

        const destinationType = getDestinationType();

        safeSetText(
            routeDestinationTypeLabel,
            destinationType === 'room' ?
            'Room / Office' :
            destinationType === 'landuse' ?
            'Landuse Area' :
            'Building'
        );

        if (destinationType === 'building') {
            const selectedText = destinationBuildingSelect?.selectedOptions?.[0]?.text || 'Not selected';
            safeSetText(routeEndLabel, selectedDestinationBuildingId ? selectedText : 'Not selected');
        } else if (destinationType === 'landuse') {
            const selectedText = destinationLanduseSelect?.selectedOptions?.[0]?.text || 'Not selected';
            safeSetText(routeEndLabel, selectedDestinationLanduseId ? selectedText : 'Not selected');
        } else {
            const roomText = destinationRoomSelect?.selectedOptions?.[0]?.text || 'Not selected';
            safeSetText(routeEndLabel, roomText);
        }

        const selectedEntrance = buildingEntrances.find(e => Number(e.id) === Number(selectedBuildingEntranceId));
        safeSetText(routeGatewayLabel, selectedEntrance ? (selectedEntrance.name || 'Entrance') : 'None');
    }

    function normalizeColor(color) {
        if (!color || typeof color !== 'string') return '#2b82cc';
        color = color.trim();
        if (/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/.test(color)) {
            if (color.length === 4) {
                return '#' + color[1] + color[1] + color[2] + color[2] + color[3] + color[3];
            }
            return color;
        }
        return '#2b82cc';
    }

    function hexToRgb(hex) {
        hex = normalizeColor(hex).replace('#', '');
        return {
            r: parseInt(hex.substring(0, 2), 16),
            g: parseInt(hex.substring(2, 4), 16),
            b: parseInt(hex.substring(4, 6), 16)
        };
    }

    function rgbToHex(r, g, b) {
        return '#' + [r, g, b].map(v => {
            v = Math.max(0, Math.min(255, Math.round(v)));
            return v.toString(16).padStart(2, '0');
        }).join('');
    }

    function darkenColor(hex, percent) {
        const {
            r,
            g,
            b
        } = hexToRgb(hex);
        return rgbToHex(r * (1 - percent), g * (1 - percent), b * (1 - percent));
    }

    /*
    |--------------------------------------------------------------------------
    | 3-LAYER LIGHTWEIGHT FAKE 3D BUILDING HELPERS
    |--------------------------------------------------------------------------
    | One global CSS style only. Each building SVG path gets color variables.
    | Result: visible fake 3D, but much lighter than one style tag per building.
    */
    function ensureBuildingDepthGlobalStyle() {
        if (document.getElementById('building-depth-global-style')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'building-depth-global-style';
        style.textContent = `
            .fake-3d-building {
                filter:
                    drop-shadow(1px 1px 0 var(--building-side-1, rgba(15, 23, 42, 0.28)))
                    drop-shadow(2px 2px 0 var(--building-side-2, rgba(15, 23, 42, 0.20)))
                    drop-shadow(3px 3px 1px var(--building-side-3, rgba(15, 23, 42, 0.14)));
                transform: none !important;
                transition: fill-opacity 0.12s ease, stroke-width 0.12s ease, filter 0.12s ease !important;
                cursor: pointer;
                vector-effect: non-scaling-stroke;
                shape-rendering: geometricPrecision;
            }

            .fake-3d-building:hover {
                filter:
                    drop-shadow(1px 1px 0 var(--building-side-1, rgba(15, 23, 42, 0.28)))
                    drop-shadow(2px 2px 0 var(--building-side-2, rgba(15, 23, 42, 0.20)))
                    drop-shadow(3px 3px 1px var(--building-side-3, rgba(15, 23, 42, 0.14)));
                transform: none !important;
                stroke: var(--building-border-color, #1f2937) !important;
                stroke-width: 2 !important;
                fill-opacity: 0.96 !important;
            }

            body.many-buildings-mode .fake-3d-building,
            body.many-buildings-mode .fake-3d-building:hover {
                filter:
                    drop-shadow(1px 1px 0 var(--building-side-1, rgba(15, 23, 42, 0.26)))
                    drop-shadow(2px 2px 0 var(--building-side-2, rgba(15, 23, 42, 0.18)))
                    drop-shadow(3px 3px 1px var(--building-side-3, rgba(15, 23, 42, 0.10))) !important;
                transition: none !important;
            }

            body.map-zooming .fake-3d-building,
            body.map-zooming .fake-3d-building:hover {
                filter:
                    drop-shadow(1px 1px 0 var(--building-side-1, rgba(15, 23, 42, 0.24)))
                    drop-shadow(2px 2px 0 var(--building-side-2, rgba(15, 23, 42, 0.16)))
                    drop-shadow(3px 3px 1px var(--building-side-3, rgba(15, 23, 42, 0.08))) !important;
                transition: none !important;
                transform: none !important;
            }

            @media (hover: none), (max-width: 768px) {
                .fake-3d-building,
                .fake-3d-building:hover,
                body.map-moving .fake-3d-building,
                body.map-moving .fake-3d-building:hover {
                    filter:
                        drop-shadow(var(--mobile-side-1, 1px) var(--mobile-side-1, 1px) 0 var(--building-side-1, rgba(15, 23, 42, 0.26)))
                        drop-shadow(var(--mobile-side-2, 2px) var(--mobile-side-2, 2px) 0 var(--building-side-2, rgba(15, 23, 42, 0.18)))
                        drop-shadow(var(--mobile-side-3, 3px) var(--mobile-side-3, 3px) 1px var(--building-side-3, rgba(15, 23, 42, 0.10))) !important;
                    transform: none !important;
                    transition: none !important;
                    stroke-width: var(--mobile-edge-width, 1.35) !important;
                    vector-effect: non-scaling-stroke !important;
                }

                body.many-buildings-mode .fake-3d-building,
                body.many-buildings-mode .fake-3d-building:hover,
                body.map-zooming .fake-3d-building,
                body.map-zooming .fake-3d-building:hover {
                    filter:
                        drop-shadow(var(--mobile-side-1, 1px) var(--mobile-side-1, 1px) 0 var(--building-side-1, rgba(15, 23, 42, 0.24)))
                        drop-shadow(var(--mobile-side-2, 2px) var(--mobile-side-2, 2px) 0 var(--building-side-2, rgba(15, 23, 42, 0.15)))
                        drop-shadow(var(--mobile-side-3, 3px) var(--mobile-side-3, 3px) 1px var(--building-side-3, rgba(15, 23, 42, 0.08))) !important;
                }
            }
        `;

        document.head.appendChild(style);
    }

    function addDynamicBuildingStyle(className, baseColor) {
        ensureBuildingDepthGlobalStyle();
    }

    function applyBuildingDepthVariables(geojsonLayer, baseColor) {
        ensureBuildingDepthGlobalStyle();

        const borderColor = darkenColor(baseColor, 0.28);
        const sideColor1 = darkenColor(baseColor, 0.18);
        const sideColor2 = darkenColor(baseColor, 0.32);
        const sideColor3 = darkenColor(baseColor, 0.46);

        function applyToPath(pathLayer) {
            const el = pathLayer?.getElement ? pathLayer.getElement() : null;
            if (!el) return;

            el.style.setProperty('--building-border-color', borderColor);
            el.style.setProperty('--building-side-1', sideColor1);
            el.style.setProperty('--building-side-2', sideColor2);
            el.style.setProperty('--building-side-3', sideColor3);
        }

        if (geojsonLayer && typeof geojsonLayer.eachLayer === 'function') {
            geojsonLayer.eachLayer(layer => {
                applyToPath(layer);
                if (layer && typeof layer.once === 'function') {
                    layer.once('add', () => applyToPath(layer));
                }
            });
        }
    }

    function updateBuildingPerformanceMode() {
        const count = Array.isArray(buildingRecords) ? buildingRecords.length : 0;
        const mobileView = window.matchMedia('(hover: none), (max-width: 768px)').matches;
        const manyBuildings = count >= 24;

        document.body.classList.toggle('many-buildings-mode', manyBuildings || mobileView);
    }

    function createDivIcon(html, size = [22, 22], anchor = [11, 11]) {
        return L.divIcon({
            html,
            className: '',
            iconSize: size,
            iconAnchor: anchor
        });
    }



    function getHazardSeverityClass(severity) {
        severity = Number(severity || 1);

        if (severity >= 3) return 'severity-3';
        if (severity === 2) return 'severity-2';

        return 'severity-1';
    }

    function getHazardSeverityColor(severity) {
        severity = Number(severity || 1);

        if (severity >= 3) return '#dc2626';
        if (severity === 2) return '#facc15';

        return '#ffffff';
    }

    function getHazardSymbolByType(warningType) {
        const type = String(warningType || '').toLowerCase();

        if (type.includes('construction')) return '!';
        if (type.includes('broken')) return '!';
        if (type.includes('slippery')) return '!';
        if (type.includes('flood')) return '!';
        if (type.includes('stairs')) return '!';
        if (type.includes('caution')) return '!';

        return '!';
    }

    function createHazardIcon(hazard) {
        const severity = Number(hazard?.severity_level || 1);
        const severityClass = getHazardSeverityClass(severity);
        const symbol = getHazardSymbolByType(hazard?.warning_type);

        return L.divIcon({
            className: '',
            html: `
                <div class="hazard-pin-wrap">
                    <div class="hazard-pin ${severityClass}">
                        <span class="hazard-pin-symbol">${symbol}</span>
                    </div>
                </div>
            `,
            iconSize: [34, 42],
            iconAnchor: [17, 38],
            popupAnchor: [0, -32]
        });
    }

function formatCoordKey(lng, lat) {
        return `${Number(lng).toFixed(12)},${Number(lat).toFixed(12)}`;
    }

    function parseCoordKey(key) {
        const [lng, lat] = key.split(',').map(Number);
        return [lat, lng];
    }

    function edgeKey(a, b) {
        return [a, b].sort().join('__');
    }

    function nearestNodeKey(lat, lng) {
        let bestKey = null;
        let bestDistance = Infinity;

        Object.entries(outdoorNodeCoords).forEach(([key, coord]) => {
            const d = map.distance([lat, lng], coord);
            if (d < bestDistance) {
                bestDistance = d;
                bestKey = key;
            }
        });

        return bestKey;
    }

    function getBuildingNameById(buildingId) {
        const option = Array.from(destinationBuildingSelect.options).find(
            opt => Number(opt.value) === Number(buildingId)
        );
        return option ? option.textContent.trim() : 'Building';
    }

    function getLanduseNameById(landuseId) {
        const option = Array.from(destinationLanduseSelect?.options || []).find(
            opt => Number(opt.value) === Number(landuseId)
        );
        return option ? option.textContent.trim() : 'Landuse Area';
    }

    function getLanduseCenter(landuse) {
        if (!landuse || !landuse.geometry) return null;

        try {
            const layer = L.geoJSON({
                type: 'Feature',
                geometry: landuse.geometry,
                properties: landuse.properties || {}
            });

            const bounds = layer.getBounds();
            if (!bounds.isValid()) return null;

            return bounds.getCenter();
        } catch (e) {
            return null;
        }
    }

    function getLanduseNearestNodeKey(landuse) {
        if (!landuse || !landuse.geometry) return null;
        if (isDesignLanduse(landuse)) return null;

        const center = getLanduseCenter(landuse);
        if (!center) return null;

        return nearestNodeKey(center.lat, center.lng);
    }

    function isOpenFieldLanduse(landuse) {
        const rawName = String(landuse?.name || landuse?.properties?.name || '').toLowerCase().trim();
        const p = landuse?.properties || {};

        return rawName.includes('field') ||
            rawName.includes('garden') ||
            rawName.includes('park') ||
            rawName.includes('open') ||
            rawName.includes('grass') ||
            String(p.type || '').toLowerCase().includes('field') ||
            String(p.category || '').toLowerCase().includes('field');
    }

    function isMultipurposeCourtLanduse(landuse) {
        const rawName = String(landuse?.name || landuse?.properties?.name || '').toLowerCase().trim();
        const p = landuse?.properties || {};

        return rawName.includes('court') ||
            rawName.includes('multipurpose') ||
            rawName.includes('multi purpose') ||
            String(p.type || '').toLowerCase().includes('court') ||
            String(p.category || '').toLowerCase().includes('court');
    }

    function getLandusePopupSubtitle(landuse) {
        if (isDesignLanduse(landuse)) return 'Design / Display Only';
        if (isMultipurposeCourtLanduse(landuse)) return 'Multi Purpose Court';
        if (isOpenFieldLanduse(landuse)) return 'Open Field Area';
        return 'Campus Landuse';
    }

    function normalizeLanduseTypeValue(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, ' ')
            .trim();
    }

    function getLanduseTypeValue(landuse) {
        const p = landuse?.properties || {};

        return normalizeLanduseTypeValue(
            landuse?.type ??
            landuse?.landuse_type ??
            landuse?.land_use_type ??
            p.type ??
            p.landuse_type ??
            p.land_use_type ??
            p.category ??
            p.kind ??
            ''
        );
    }

    function isDesignLanduse(landuse) {
        const typeValue = getLanduseTypeValue(landuse);

        return typeValue === 'design' ||
            typeValue === 'display design' ||
            typeValue === 'decorative design' ||
            typeValue.includes('design only');
    }

    function getRoutableLanduses() {
        return (landuseRecords || []).filter(landuse => !isDesignLanduse(landuse));
    }

    function getGeometryBounds(geometry) {
        if (!geometry) return null;

        try {
            const layer = L.geoJSON({
                type: 'Feature',
                geometry: geometry,
                properties: {}
            });

            const bounds = layer.getBounds();
            return bounds.isValid() ? bounds : null;
        } catch (e) {
            return null;
        }
    }

    function getPolygonRingsFromGeometry(geometry) {
        if (!geometry || !geometry.type || !geometry.coordinates) return [];

        if (geometry.type === 'Polygon') {
            return geometry.coordinates || [];
        }

        if (geometry.type === 'MultiPolygon') {
            return geometry.coordinates[0] || [];
        }

        return [];
    }

    function projectRingToBounds(ring, bounds, viewWidth, viewHeight) {
        const west = bounds.getWest();
        const east = bounds.getEast();
        const north = bounds.getNorth();
        const south = bounds.getSouth();

        const lngSpan = east - west || 1;
        const latSpan = north - south || 1;

        return ring.map(coord => {
            const lng = Number(coord[0]);
            const lat = Number(coord[1]);

            const x = ((lng - west) / lngSpan) * viewWidth;
            const y = ((north - lat) / latSpan) * viewHeight;

            return `${x},${y}`;
        }).join(' ');
    }

    function latLngToMapPoint(lat, lng) {
        return map.latLngToLayerPoint([Number(lat), Number(lng)]);
    }

    function ringToMapPath(ring) {
        if (!Array.isArray(ring) || !ring.length) return '';

        return ring.map((coord, index) => {
            const pt = latLngToMapPoint(coord[1], coord[0]);
            return `${index === 0 ? 'M' : 'L'} ${pt.x} ${pt.y}`;
        }).join(' ') + ' Z';
    }

    function buildClipPathDFromGeometry(geometry) {
        const rings = getPolygonRingsFromGeometry(geometry);
        if (!rings.length) return '';

        return rings.map(ring => ringToMapPath(ring)).join(' ');
    }

    function distance2D(a, b) {
        const dx = b.x - a.x;
        const dy = b.y - a.y;
        return Math.sqrt((dx * dx) + (dy * dy));
    }

    function hasExactImageCorners(p) {
        return [
            p.image_tl_lat, p.image_tl_lng,
            p.image_tr_lat, p.image_tr_lng,
            p.image_bl_lat, p.image_bl_lng,
            p.image_br_lat, p.image_br_lng
        ].every(v => v !== null && v !== undefined && v !== '');
    }

    function createExactLanduseSvgForCurrentMap(feature, p) {
        if (!feature || !feature.geometry || !p.image) return null;

        const mapSize = map.getSize();
        const widthPx = mapSize.x;
        const heightPx = mapSize.y;

        const clipPathId = `landuse-clip-${Number(p.id)}-${Date.now()}`;
        const polygonPathD = buildClipPathDFromGeometry(feature.geometry);

        if (!polygonPathD) return null;

        let imageMarkup = '';

        if (hasExactImageCorners(p)) {
            const tl = latLngToMapPoint(p.image_tl_lat, p.image_tl_lng);
            const tr = latLngToMapPoint(p.image_tr_lat, p.image_tr_lng);
            const bl = latLngToMapPoint(p.image_bl_lat, p.image_bl_lng);
            const br = latLngToMapPoint(p.image_br_lat, p.image_br_lng);

            const imageWidth = distance2D(tl, tr);
            const imageHeight = distance2D(tl, bl);

            const centerX = (tl.x + tr.x + bl.x + br.x) / 4;
            const centerY = (tl.y + tr.y + bl.y + br.y) / 4;

            const angle = Math.atan2(tr.y - tl.y, tr.x - tl.x) * (180 / Math.PI);

            const imageX = centerX - (imageWidth / 2);
            const imageY = centerY - (imageHeight / 2);

            imageMarkup = `
            <image
                href="/landuse_images/${p.image}"
                x="${imageX}"
                y="${imageY}"
                width="${imageWidth}"
                height="${imageHeight}"
                preserveAspectRatio="none"
                transform="rotate(${angle} ${centerX} ${centerY})"
            />
        `;
        } else {
            const bounds = getGeometryBounds(feature.geometry);
            if (!bounds) return null;

            const viewWidth = 1000;
            const viewHeight = 1000;

            const rings = getPolygonRingsFromGeometry(feature.geometry);
            const outerRing = projectRingToBounds(rings[0], bounds, viewWidth, viewHeight);
            const holeRings = rings.slice(1).map(ring => projectRingToBounds(ring, bounds, viewWidth, viewHeight));
            const fallbackClipId = `landuse-fallback-clip-${Number(p.id)}-${Date.now()}`;

            const polygonBaseAngle = Number(p.polygon_base_angle ?? 0);
            const localScaleX = Math.max(0.01, Number(p.image_local_scale_x ?? 1));
            const localScaleY = Math.max(0.01, Number(p.image_local_scale_y ?? 1));
            const localOffsetU = Number(p.image_local_offset_u ?? 0);
            const localOffsetV = Number(p.image_local_offset_v ?? 0);
            const localRotation = Number(p.image_local_rotation ?? 0);

            const finalRotation = polygonBaseAngle + localRotation;
            const imageWidth = viewWidth * localScaleX;
            const imageHeight = viewHeight * localScaleY;

            const theta = polygonBaseAngle * Math.PI / 180;
            const offsetXRatio = (localOffsetU * Math.cos(theta)) - (localOffsetV * Math.sin(theta));
            const offsetYRatio = (localOffsetU * Math.sin(theta)) + (localOffsetV * Math.cos(theta));

            const imageX = ((viewWidth - imageWidth) / 2) + (offsetXRatio * viewWidth);
            const imageY = ((viewHeight - imageHeight) / 2) + (offsetYRatio * viewHeight);

            const holesMarkup = holeRings.map(points => `<polygon points="${points}" />`).join('');

            return `
            <svg xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 ${viewWidth} ${viewHeight}"
                 width="${viewWidth}"
                 height="${viewHeight}"
                 style="position:absolute;left:0;top:0;pointer-events:none;overflow:hidden;">
                <defs>
                    <clipPath id="${fallbackClipId}" clipPathUnits="userSpaceOnUse">
                        <polygon points="${outerRing}" />
                        ${holesMarkup}
                    </clipPath>
                </defs>
                <g clip-path="url(#${fallbackClipId})">
                    <image
                        href="/landuse_images/${p.image}"
                        x="${imageX}"
                        y="${imageY}"
                        width="${imageWidth}"
                        height="${imageHeight}"
                        preserveAspectRatio="none"
                        transform="rotate(${finalRotation} ${viewWidth / 2} ${viewHeight / 2})"
                    />
                </g>
            </svg>
        `;
        }

        return `
        <svg xmlns="http://www.w3.org/2000/svg"
             width="${widthPx}"
             height="${heightPx}"
             viewBox="0 0 ${widthPx} ${heightPx}"
             style="position:absolute;left:0;top:0;pointer-events:none;overflow:visible;">
            <defs>
                <clipPath id="${clipPathId}" clipPathUnits="userSpaceOnUse">
                    <path d="${polygonPathD}" fill-rule="evenodd" clip-rule="evenodd"></path>
                </clipPath>
            </defs>
            <g clip-path="url(#${clipPathId})">
                ${imageMarkup}
            </g>
        </svg>
    `;
    }

    const ExactLanduseOverlay = L.Layer.extend({
        initialize: function(feature, props, options = {}) {
            this._feature = feature;
            this._props = props;
            L.setOptions(this, options);
        },

        onAdd: function(mapInstance) {
            this._map = mapInstance;
            this._pane = mapInstance.getPane(this.options.pane || 'landuseImagePane');

            this._container = L.DomUtil.create('div', 'exact-landuse-overlay');
            this._container.style.position = 'absolute';
            this._container.style.left = '0';
            this._container.style.top = '0';
            this._container.style.width = '100%';
            this._container.style.height = '100%';
            this._container.style.pointerEvents = 'none';
            this._pane.appendChild(this._container);

            this._queuedUpdateFrame = null;
            this._update = this._update.bind(this);
            this._queueUpdate = this._queueUpdate.bind(this);

            mapInstance.on('zoomend moveend viewreset resize', this._queueUpdate);

            /*
            |--------------------------------------------------------------------------
            | REAL PHONE PINCH-ZOOM LANDUSE PATCH
            |--------------------------------------------------------------------------
            | Do NOT convert the landuse image to another overlay type.
            | Keep the original exact-corner SVG logic, but update it during real
            | mobile pinch zoom through requestAnimationFrame so it follows the map
            | instead of waiting only for zoomend.
            |
            | This runs only on touch/mobile devices.
            */
            const isRealTouchPhone = window.matchMedia('(pointer: coarse), (hover: none)').matches;
            if (isRealTouchPhone) {
                mapInstance.on('zoom move', this._queueUpdate);
            }
            this._queueUpdate();
        },

        onRemove: function(mapInstance) {
            mapInstance.off('zoomend moveend viewreset resize', this._queueUpdate);
            mapInstance.off('zoom move', this._queueUpdate);

            if (this._queuedUpdateFrame) {
                cancelAnimationFrame(this._queuedUpdateFrame);
                this._queuedUpdateFrame = null;
            }

            if (this._container && this._container.parentNode) {
                this._container.parentNode.removeChild(this._container);
            }

            this._container = null;
            this._map = null;
        },

        _queueUpdate: function() {
            if (this._queuedUpdateFrame) {
                cancelAnimationFrame(this._queuedUpdateFrame);
            }

            this._queuedUpdateFrame = requestAnimationFrame(() => {
                this._queuedUpdateFrame = null;
                this._update();
            });
        },

        _update: function() {
            if (!this._map || !this._container) return;

            const svgMarkup = createExactLanduseSvgForCurrentMap(this._feature, this._props);
            this._container.innerHTML = svgMarkup || '';
        }
    });

    function projectLatLngToBoundsLocal(lat, lng, bounds, viewWidth, viewHeight) {
        const west = bounds.getWest();
        const east = bounds.getEast();
        const north = bounds.getNorth();
        const south = bounds.getSouth();

        const lngSpan = east - west || 1;
        const latSpan = north - south || 1;

        return {
            x: ((Number(lng) - west) / lngSpan) * viewWidth,
            y: ((north - Number(lat)) / latSpan) * viewHeight
        };
    }

    function distance2D(a, b) {
        const dx = b.x - a.x;
        const dy = b.y - a.y;
        return Math.sqrt((dx * dx) + (dy * dy));
    }

    function hasExactImageCorners(p) {
        return [
            p.image_tl_lat, p.image_tl_lng,
            p.image_tr_lat, p.image_tr_lng,
            p.image_bl_lat, p.image_bl_lng,
            p.image_br_lat, p.image_br_lng
        ].every(v => v !== null && v !== undefined && v !== '');
    }

    function createClippedLanduseSvg(geometry, p, bounds) {
        const rings = getPolygonRingsFromGeometry(geometry);
        if (!rings.length) return null;

        const viewWidth = 1000;
        const viewHeight = 1000;

        const outerRing = projectRingToBounds(rings[0], bounds, viewWidth, viewHeight);
        const holeRings = rings.slice(1).map(ring =>
            projectRingToBounds(ring, bounds, viewWidth, viewHeight)
        );

        const imageUrl = `/landuse_images/${p.image}`;
        const clipId = `landuse-clip-${Number(p.id)}-${Date.now()}`;

        let imageMarkup = '';

        if (hasExactImageCorners(p)) {
            const tl = projectLatLngToBoundsLocal(p.image_tl_lat, p.image_tl_lng, bounds, viewWidth, viewHeight);
            const tr = projectLatLngToBoundsLocal(p.image_tr_lat, p.image_tr_lng, bounds, viewWidth, viewHeight);
            const bl = projectLatLngToBoundsLocal(p.image_bl_lat, p.image_bl_lng, bounds, viewWidth, viewHeight);
            const br = projectLatLngToBoundsLocal(p.image_br_lat, p.image_br_lng, bounds, viewWidth, viewHeight);

            const width = distance2D(tl, tr);
            const height = distance2D(tl, bl);

            const centerX = (tl.x + tr.x + bl.x + br.x) / 4;
            const centerY = (tl.y + tr.y + bl.y + br.y) / 4;

            const angle = Math.atan2(tr.y - tl.y, tr.x - tl.x) * (180 / Math.PI);

            const imageX = centerX - (width / 2);
            const imageY = centerY - (height / 2);

            imageMarkup = `
            <image
                href="${imageUrl}"
                x="${imageX}"
                y="${imageY}"
                width="${width}"
                height="${height}"
                preserveAspectRatio="none"
                transform="rotate(${angle} ${centerX} ${centerY})"
            />
        `;
        } else {
            const polygonBaseAngle = Number(p.polygon_base_angle ?? 0);
            const localScaleX = Math.max(0.01, Number(p.image_local_scale_x ?? 1));
            const localScaleY = Math.max(0.01, Number(p.image_local_scale_y ?? 1));
            const localOffsetU = Number(p.image_local_offset_u ?? 0);
            const localOffsetV = Number(p.image_local_offset_v ?? 0);
            const localRotation = Number(p.image_local_rotation ?? 0);

            const finalRotation = polygonBaseAngle + localRotation;

            const imageWidth = viewWidth * localScaleX;
            const imageHeight = viewHeight * localScaleY;

            const theta = polygonBaseAngle * Math.PI / 180;
            const offsetXRatio =
                (localOffsetU * Math.cos(theta)) -
                (localOffsetV * Math.sin(theta));

            const offsetYRatio =
                (localOffsetU * Math.sin(theta)) +
                (localOffsetV * Math.cos(theta));

            const imageX = ((viewWidth - imageWidth) / 2) + (offsetXRatio * viewWidth);
            const imageY = ((viewHeight - imageHeight) / 2) + (offsetYRatio * viewHeight);

            imageMarkup = `
            <image
                href="${imageUrl}"
                x="${imageX}"
                y="${imageY}"
                width="${imageWidth}"
                height="${imageHeight}"
                preserveAspectRatio="none"
                transform="rotate(${finalRotation} ${viewWidth / 2} ${viewHeight / 2})"
            />
        `;
        }

        const holesMarkup = holeRings.map(points => `<polygon points="${points}" />`).join('');

        return `
        <svg xmlns="http://www.w3.org/2000/svg"
             viewBox="0 0 ${viewWidth} ${viewHeight}"
             preserveAspectRatio="none"
             style="width:100%;height:100%;display:block;pointer-events:none;overflow:hidden;">
            <defs>
                <clipPath id="${clipId}" clipPathUnits="userSpaceOnUse">
                    <polygon points="${outerRing}" />
                    ${holesMarkup}
                </clipPath>
            </defs>

            <g clip-path="url(#${clipId})">
                ${imageMarkup}
            </g>
        </svg>
    `;
    }

    function addClippedLanduseOverlay(feature, p, layerGroup) {
        if (!feature || !feature.geometry || !p.image || !layerGroup) return null;

        /*
        |--------------------------------------------------------------------------
        | LANDUSE IMAGE ZOOM FIX
        |--------------------------------------------------------------------------
        | Keep using the exact SVG/clip overlay.
        | Important: the overlay is NOT recalculated during active zoom.
        | Leaflet scales the pane while zooming, then we update after zoomend.
        */
        const overlay = new ExactLanduseOverlay(feature, p, {
            pane: 'landuseImagePane'
        });

        layerGroup.addLayer(overlay);
        return overlay;
    }

    function normalizeIndoorFeature(feature) {
        const p = feature.properties || {};
        const extra = p.properties || {};

        return {
            ...feature,
            properties: {
                ...p,
                ...extra,
                id: p.id ?? extra.id ?? null,
                indoor_map_id: p.indoor_map_id ?? extra.indoor_map_id ?? null,
                building_id: p.building_id ?? extra.building_id ?? null,
                building_name: p.building_name ?? extra.building_name ?? extra.building ?? p.building ?? null,
                floor_number: p.floor_number ?? extra.floor_number ?? extra.floor ?? p.floor ?? null,
                floor_label: p.floor_label ?? extra.floor_label ?? (
                    hasIndoorFloorValue(p.floor_number ?? extra.floor_number ?? extra.floor ?? p.floor) ?
                    formatIndoorFloorLabel(p.floor_number ?? extra.floor_number ?? extra.floor ?? p.floor) :
                    null
                ),
                name: p.name ?? extra.name ?? 'Unnamed',
                room_code: p.room_code ?? extra.room_code ?? null,
                type: p.type ?? extra.type ?? null,
                path_type: p.path_type ?? extra.path_type ?? null,
                ent_type: p.ent_type ?? extra.ent_type ?? null,
                is_blocked: Boolean(p.is_blocked ?? extra.is_blocked ?? false),
            }
        };
    }

    function normalizeIndoorMapRecord(mapItem) {
        if (!mapItem) return null;

        let geometry = mapItem.geometry ?? null;

        if (typeof geometry === 'string') {
            try {
                geometry = JSON.parse(geometry);
            } catch (e) {
                geometry = null;
            }
        }

        return {
            ...mapItem,
            floor_number: Number(mapItem.floor_number ?? 0),
            geometry
        };
    }

    function getIndoorMapBoundsFromGeometry(geometry) {
        if (!geometry || !geometry.type || !geometry.coordinates) return null;

        let latlngs = [];

        if (geometry.type === 'Polygon') {
            const ring = geometry.coordinates[0] || [];
            latlngs = ring.map(coord => [Number(coord[1]), Number(coord[0])]);
        } else if (geometry.type === 'MultiPolygon') {
            const firstPolygon = geometry.coordinates[0] || [];
            const ring = firstPolygon[0] || [];
            latlngs = ring.map(coord => [Number(coord[1]), Number(coord[0])]);
        } else {
            return null;
        }

        if (!latlngs.length) return null;

        const bounds = L.latLngBounds(latlngs);
        return bounds.isValid() ? bounds : null;
    }

    function getIndoorMapGeometryLayer(geometry, options = {}) {
        if (!geometry) return null;

        try {
            return L.geoJSON({
                    type: 'Feature',
                    geometry,
                    properties: {}
                },
                options
            );
        } catch (e) {
            return null;
        }
    }

    function normalizeFeatureCollection(fc) {
        return {
            type: 'FeatureCollection',
            features: (fc?.features || []).map(normalizeIndoorFeature)
        };
    }

    function getFeatureCenter(feature) {
        const bounds = L.geoJSON(feature).getBounds();
        return bounds.getCenter();
    }

    function getPointLatLng(feature) {
        const coords = feature?.geometry?.coordinates || [];
        if (!Array.isArray(coords) || coords.length < 2) return null;
        return L.latLng(Number(coords[1]), Number(coords[0]));
    }

    function getFloorFromNodeKey(key) {
        const match = String(key).match(/_f(\d+)$/);
        return match ? Number(match[1]) : null;
    }

    function clearRouteLayer() {
        if (outdoorRouteAnimationTimer) {
            clearInterval(outdoorRouteAnimationTimer);
            outdoorRouteAnimationTimer = null;
        }

        if (routeLayer) {
            map.removeLayer(routeLayer);
            routeLayer = null;
        }
    }

    function clearStartMarker() {
        if (startMarker) {
            map.removeLayer(startMarker);
            startMarker = null;
        }
    }

    function clearDestinationMarker() {
        if (destinationMarker) {
            map.removeLayer(destinationMarker);
            destinationMarker = null;
        }
    }

    function clearCurrentLocationMarker() {
        if (currentLocationMarker) {
            map.removeLayer(currentLocationMarker);
            currentLocationMarker = null;
        }
    }

    function clearOutsideGuideLine() {
        if (outsideGuideLine) {
            map.removeLayer(outsideGuideLine);
            outsideGuideLine = null;
        }
    }

    function clearHazardLayer() {
        if (hazardLayer) {
            map.removeLayer(hazardLayer);
            hazardLayer = null;
        }
    }


    function clearIndoorRoute() {
        if (indoorRouteAnimationTimer) {
            clearInterval(indoorRouteAnimationTimer);
            indoorRouteAnimationTimer = null;
        }

        if (indoorRouteLayer && indoorMap) {
            indoorMap.removeLayer(indoorRouteLayer);
            indoorRouteLayer = null;
        }

        if (indoorStartMarker && indoorMap) {
            indoorMap.removeLayer(indoorStartMarker);
            indoorStartMarker = null;
        }

        if (indoorEndMarker && indoorMap) {
            indoorMap.removeLayer(indoorEndMarker);
            indoorEndMarker = null;
        }
    }

    function clearIndoorRoutingStateOnly() {
        /*
        |--------------------------------------------------------------------------
        | OUTDOOR-ONLY ROUTE CLEANUP
        |--------------------------------------------------------------------------
        | Use this before routing to Building-only or Landuse-only destination.
        | This removes any old indoor room route so it will not remain when user
        | switches from indoor room routing to building routing.
        */
        selectedIndoorRoomFeature = null;

        lastIndoorRoutePackage = null;
        persistentIndoorRouteByFloor = {};
        pendingIndoorOpenForBuildingId = null;
        pendingIndoorFocusFloor = null;

        if (indoorMap) {
            clearIndoorRoute();

            if (typeof clearIndoorRoomCenterLabelsSmart === 'function') {
                clearIndoorRoomCenterLabelsSmart();
            }

            if (typeof clearIndoorRoomCenterLabelsFinal === 'function') {
                clearIndoorRoomCenterLabelsFinal();
            }

            if (typeof clearIndoorRoomCenterLabels === 'function') {
                clearIndoorRoomCenterLabels();
            }

            if (typeof clearIndoorRoomCenterLabelsFinal === 'function') {
                clearIndoorRoomCenterLabelsFinal();
            }
        }

        closeIndoorPanelFn();

        if (destinationRoomSelect) {
            destinationRoomSelect.value = '';
        }

        if (indoorFooter) {
            indoorFooter.innerHTML = `
                <span class="indoor-badge badge-blue">Select Building</span>
                Choose a room or office to compute the route.
            `;
        }
    }

    function clearIndoorGeometryDebug() {
        if (indoorGeometryDebugLayer && indoorMap) {
            indoorMap.removeLayer(indoorGeometryDebugLayer);
            indoorGeometryDebugLayer = null;
        }
    }

    function drawOutsideGuideLine(fromLat, fromLng, toLat, toLng) {
        clearOutsideGuideLine();
        outsideGuideLine = L.polyline([
            [fromLat, fromLng],
            [toLat, toLng]
        ], {
            color: '#7c3aed',
            weight: 4,
            opacity: 0.85,
            dashArray: '10, 10'
        }).addTo(map);
    }

    function isInsideCampus(lat, lng) {
        if (!campusBounds) return false;
        return campusBounds.contains([lat, lng]);
    }

    function openIndoorPanelModal() {
        if (indoorBackdrop) indoorBackdrop.classList.add('active');
        if (indoorPanel) indoorPanel.classList.add('active');

        setTimeout(() => {
            if (indoorMap) indoorMap.invalidateSize();
        }, 120);
    }

    function closeIndoorPanelFn() {
        if (indoorBackdrop) indoorBackdrop.classList.remove('active');
        if (indoorPanel) indoorPanel.classList.remove('active');
    }

    function getHazardsForPath(pathId) {
        return hazardPoints.filter(h =>
            Number(h.path_id) === Number(pathId) &&
            Boolean(h.is_active) &&
            Boolean(h.affects_routing)
        );
    }

    function getPathHazardProfile(pathId) {
        const hazards = getHazardsForPath(pathId);

        if (!hazards.length) {
            return {
                maxSeverity: 0,
                hasHazard: false,
                colorHint: 'safe',
                penalty: 1
            };
        }

        const maxSeverity = Math.max(...hazards.map(h => Number(h.severity_level || 1)));

        if (maxSeverity >= 3) {
            return {
                maxSeverity: 3,
                hasHazard: true,
                colorHint: 'danger',
                penalty: 200
            };
        }

        if (maxSeverity === 2) {
            return {
                maxSeverity: 2,
                hasHazard: true,
                colorHint: 'caution',
                penalty: 4
            };
        }

        return {
            maxSeverity: 1,
            hasHazard: true,
            colorHint: 'caution',
            penalty: 1.7
        };
    }


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
            return toBool(props.is_blocked) ||
                toBool(props.blocked) ||
                String(props.status || '').toLowerCase() === 'blocked';
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
        let nearTouchConnections = 0;
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
                        nearTouchConnections++;
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

        console.log('[OutdoorGraph V4 no-jump snap] Auto-connected routing graph:', {
            originalPathFeatures: features.length,
            builtSegments: segments.length,
            graphNodes: Object.keys(outdoorNodeCoords).length,
            graphEdges: Object.values(outdoorGraph).reduce((sum, list) => sum + list.length, 0) / 2,
            snapToleranceMeters: SNAP_TOLERANCE_METERS,
            nearTouchConnections
        });
    }

    function dijkstra(startKey, endKey) {
        const distances = {};
        const previous = {};
        const previousMeta = {};
        const visited = new Set();
        const queue = [];

        Object.keys(outdoorGraph).forEach(key => {
            distances[key] = Infinity;
            previous[key] = null;
            previousMeta[key] = null;
        });

        if (!outdoorGraph[startKey] || !outdoorGraph[endKey]) return null;

        distances[startKey] = 0;
        queue.push({
            key: startKey,
            distance: 0
        });

        while (queue.length > 0) {
            queue.sort((a, b) => a.distance - b.distance);
            const current = queue.shift();
            if (!current) break;
            if (visited.has(current.key)) continue;

            visited.add(current.key);
            if (current.key === endKey) break;

            const neighbors = outdoorGraph[current.key] || [];

            neighbors.forEach(neighbor => {
                if (visited.has(neighbor.key)) return;
                const alt = distances[current.key] + neighbor.weight;

                if (alt < distances[neighbor.key]) {
                    distances[neighbor.key] = alt;
                    previous[neighbor.key] = current.key;
                    previousMeta[neighbor.key] = neighbor.meta || null;
                    queue.push({
                        key: neighbor.key,
                        distance: alt
                    });
                }
            });
        }

        if (distances[endKey] === Infinity) return null;

        const path = [];
        const metas = [];
        let current = endKey;

        while (current) {
            path.unshift(current);
            if (previousMeta[current]) metas.unshift(previousMeta[current]);
            current = previous[current];
        }

        const maxSeverityOnRoute = metas.length ? Math.max(...metas.map(m => Number(m?.maxSeverity || 0))) : 0;
        const hasAnyHazard = metas.some(m => Boolean(m?.hasHazard));

        return {
            path,
            totalCost: distances[endKey],
            maxSeverityOnRoute,
            hasAnyHazard,
            metas
        };
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
            className
        };

        if (pane) {
            polylineOptions.pane = pane;
        }

        const routePolyline = L.polyline(routePoints, polylineOptions).addTo(layerGroup);

        return {
            polyline: routePolyline,
            timer: null
        };
    }

    function drawOutdoorRoute(result) {
        if (!result || !result.path || result.path.length < 2) return;

        clearRouteLayer();
        routeLayer = L.layerGroup().addTo(map);

        const latlngs = result.path.map(key => parseCoordKey(key));

        const staticRoute = drawAnimatedRoute(map, routeLayer, latlngs, {
            pane: 'pathsPane',
            color: '#16a34a',
            weight: 8,
            dashArray: null,
            className: 'route-line-live',
            // Static line only: arrow animation options removed.
        });

        outdoorRouteAnimationTimer = staticRoute.timer;

        map.fitBounds(L.latLngBounds(latlngs), {
            padding: [60, 60]
        });
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
                    <div style="font-family:'Plus Jakarta Sans',sans-serif;min-width:190px;text-align:left;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                            <div style="width:12px;height:12px;border-radius:999px;background:${severityColor};border:1.5px solid #cbd5e1;"></div>
                            <div style="font-weight:800;color:#0f172a;">${hazard.title || 'Hazard'}</div>
                        </div>

                        <div style="font-size:12px; color:#475569; margin-bottom:8px;">
                            ${hazard.description || 'No description provided.'}
                        </div>

                        <div style="font-size:12px; line-height:1.7; color:#334155;">
                            <strong>Type:</strong> ${hazard.warning_type || 'Unknown'}<br>
                            <strong>Severity:</strong> ${severity}
                        </div>

                        <div class="hazard-popup-badge ${severityClass}">
                            Hazard Level ${severity}
                        </div>
                    </div>
                `).addTo(hazardLayer);
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

    map.on('move zoomend', function() {
        updatePickPathHelperText();
    });

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
        closeFloatingActionCard();

        const modal = document.getElementById('browseOptionsModal');

        if (modal) {
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
        if (activeBtn) activeBtn.classList.add('active');
    }

    function selectPickPathMode() {
        selectedStartMode = 'path';
        setActiveStartModeButton('path');
        enablePathStartPlacement();
    }

    function selectGpsMode() {
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

    /* =========================================================
       INDOOR DISPLAY BLOCK RESTORED FROM OLD WORKING CODE
       Only the indoor visual/rendering block was restored.
       Outdoor routing and indoor Dijkstra routing below were not changed.
    ========================================================= */

    function ensureIndoorMap() {
        if (indoorMap) return;

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
            preferCanvas: true
        });

        indoorMap.setView([10.2925, 124.9985], 20);

        setTimeout(() => {
            indoorMap.invalidateSize();
        }, 50);
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

    function loadIndoorFloorImage() {
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

        indoorImageLayer = L.imageOverlay(mapItem.floorplan_image, bounds, {
            opacity: 1,
            interactive: false
        }).addTo(indoorMap);

        indoorImageLayer.bringToBack();

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

    function renderIndoorFloor() {
        if (!indoorMap || !currentIndoorBuildingId || !(currentIndoorFloor !== null && currentIndoorFloor !== undefined && currentIndoorFloor !== '')) return;

        if (indoorRoomsLayer) indoorMap.removeLayer(indoorRoomsLayer);
        if (indoorPathsLayer) indoorMap.removeLayer(indoorPathsLayer);
        if (indoorEntrancesLayer) indoorMap.removeLayer(indoorEntrancesLayer);
        clearIndoorRoute();

        const floorRooms = getIndoorRoomsFor(currentIndoorBuildingId, currentIndoorFloor);
        const floorPaths = getIndoorPathsFor(currentIndoorBuildingId, currentIndoorFloor);
        const floorEntrances = getIndoorEntrancesFor(currentIndoorBuildingId, currentIndoorFloor);

        indoorPathsLayer = L.geoJSON({
            type: 'FeatureCollection',
            features: floorPaths
        }, {
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
            style: function(feature) {
                const p = feature.properties || {};
                const type = String(p.type || '').toLowerCase();
                const isSelected = selectedIndoorRoomFeature &&
                    Number(selectedIndoorRoomFeature.properties?.id) === Number(p.id);

                let fillColor = '#dbeafe';
                if (type.includes('office')) fillColor = '#dcfce7';
                else if (type.includes('restroom')) fillColor = '#fef3c7';
                else if (type.includes('storage')) fillColor = '#e5e7eb';
                else if (type.includes('classroom')) fillColor = '#dbeafe';

                return {
                    color: isSelected ? '#1d4ed8' : '#2563eb',
                    weight: isSelected ? 3 : 2,
                    fillColor,
                    fillOpacity: isSelected ? 0.65 : 0.38
                };
            },
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
                    renderIndoorFloor();
                });
            }
        }).addTo(indoorMap);

        indoorEntrancesLayer = L.geoJSON({
            type: 'FeatureCollection',
            features: floorEntrances
        }, {
            pointToLayer: function(feature, latlng) {
                const p = feature.properties || {};
                const entType = String(p.ent_type || '').toLowerCase();

                let fillColor = '#ef4444';
                if (entType.includes('main')) fillColor = '#16a34a';
                else if (entType.includes('stairs')) fillColor = '#f59e0b';
                else if (entType.includes('door')) fillColor = '#7c3aed';
                else if (entType.includes('side')) fillColor = '#0ea5e9';

                return L.circleMarker(latlng, {
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

        loadIndoorFloorImage();

        const currentMapItem = allIndoorMaps.find(m =>
            Number(m.building_id) === Number(currentIndoorBuildingId) &&
            Number(m.floor_number) === Number(currentIndoorFloor)
        );

        const geometryBounds = getIndoorMapBoundsFromGeometry(currentMapItem?.geometry);

        if (geometryBounds && geometryBounds.isValid()) {
            indoorMap.fitBounds(geometryBounds.pad(0.08));
        } else {
            const layers = [];
            if (floorPaths.length) layers.push(indoorPathsLayer);
            if (floorRooms.length) layers.push(indoorRoomsLayer);
            if (floorEntrances.length) layers.push(indoorEntrancesLayer);

            if (layers.length) {
                const fg = L.featureGroup(layers);
                const bounds = fg.getBounds();
                if (bounds.isValid()) {
                    indoorMap.fitBounds(bounds.pad(0.18));
                }
            }
        }

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
        return getIndoorBuildingMaps(buildingId).length > 0;
    }

function openIndoorPanelForBuilding(buildingId) {
        const normalizedBuildingId = Number(buildingId);
        const buildingMaps = getIndoorBuildingMaps(normalizedBuildingId);

        /*
        |--------------------------------------------------------------------------
        | SILENT SKIP FOR BUILDINGS WITHOUT INDOOR MAP
        |--------------------------------------------------------------------------
        | Some buildings intentionally do not have indoor navigation.
        | If no active indoor map exists, do not show alert and do not open panel.
        */
        if (!buildingMaps.length) {
            setIndoorLoading(false);
            return false;
        }

        ensureIndoorMap();

        currentIndoorBuildingId = normalizedBuildingId;
        selectedDestinationBuildingId = normalizedBuildingId;

        const buildingName = getBuildingNameById(normalizedBuildingId);

        pendingIndoorOpenForBuildingId = normalizedBuildingId;

        indoorTitle.textContent = `${buildingName} Indoor Navigation`;
        indoorSubtitle.textContent = 'Choose room or office to compute full route';

        indoorFloorSelect.innerHTML = '<option value="">Select Floor</option>';

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

        openIndoorPanelModal();
        setIndoorLoading(true);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                try {
                    if (indoorMap) {
                        indoorMap.invalidateSize();
                    }

                    renderIndoorFloor();
                    renderIndoorRoomList();
                    restoreIndoorRouteIfAvailable();

                    if (typeof renderIndoorFloorButtonsFinal === 'function') {
                        renderIndoorFloorButtonsFinal();
                    }

                    if (typeof updateIndoorFloorButtonActiveFinal === 'function') {
                        updateIndoorFloorButtonActiveFinal();
                    }

                    setTimeout(() => {
                        if (indoorMap) {
                            indoorMap.invalidateSize();
                        }
                        setIndoorLoading(false);
                    }, 120);
                } catch (error) {
                    console.error('Indoor render failed:', error);
                    setIndoorLoading(false);
                    // No alert popup. Just keep the app quiet and prevent wrong panel behavior.
                    closeIndoorPanelFn();
                }
            });
        });

        return true;
    }

    function buildIndoorGraph(buildingId) {
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
                console.warn('[IndoorGraph] Room has no usable door entrance, route disabled for this room:', {
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

        return {
            graph,
            coords,
            entranceNodeById,
            roomNodeById
        };
    }

    function dijkstraIndoor(graph, startKey, endKey) {
        const dist = {};
        const prev = {};
        const visited = new Set();
        const queue = [];

        /*
        |--------------------------------------------------------------------------
        | INDOOR ROOM PASS-THROUGH FIX
        |--------------------------------------------------------------------------
        | Old problem:
        | - Every room center was added as a graph node.
        | - If a room has 2 or more doors, Dijkstra can use that room like a hallway:
        |   door A -> room center -> door B.
        | - That makes the route enter a classroom/office and exit another door.
        |
        | New behavior:
        | - Rooms are NOT allowed as transit nodes.
        | - Dijkstra may enter ONLY the selected destination room.
        | - So the route stays on indoor_paths / hallway first, then enters the
        |   destination room only at the end.
        */
        function isRoomNode(key) {
            return String(key || '').startsWith('r_');
        }

        Object.keys(graph).forEach(key => {
            dist[key] = Infinity;
            prev[key] = null;
        });

        if (!graph[startKey] || !graph[endKey]) return null;

        dist[startKey] = 0;
        queue.push({
            key: startKey,
            distance: 0
        });

        while (queue.length) {
            queue.sort((a, b) => a.distance - b.distance);
            const current = queue.shift();
            if (!current) break;
            if (visited.has(current.key)) continue;

            visited.add(current.key);
            if (current.key === endKey) break;

            // Safety: never expand from a room unless that room is the destination.
            if (isRoomNode(current.key) && current.key !== endKey) {
                continue;
            }

            (graph[current.key] || []).forEach(next => {
                if (visited.has(next.key)) return;

                // Do not enter any room except the chosen destination room.
                // This prevents classroom/office/CR from becoming shortcuts.
                if (isRoomNode(next.key) && next.key !== endKey) {
                    return;
                }

                const alt = dist[current.key] + next.weight;
                if (alt < dist[next.key]) {
                    dist[next.key] = alt;
                    prev[next.key] = current.key;
                    queue.push({
                        key: next.key,
                        distance: alt
                    });
                }
            });
        }

        if (dist[endKey] === Infinity) return null;

        const path = [];
        let cur = endKey;
        while (cur) {
            path.unshift(cur);
            cur = prev[cur];
        }

        return {
            path,
            totalCost: dist[endKey]
        };
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
                color: '#16a34a',
                weight: 8,
                opacity: 1,
                dashArray: null,
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

        if (currentFloorPoints.length >= 2) {
            indoorMap.fitBounds(L.latLngBounds(currentFloorPoints), {
                padding: [40, 40]
            });
        }

        lastIndoorRoutePackage = {
            indoorGraphData,
            indoorResult,
            entranceFeature,
            roomFeature
        };

        if (indoorFooter && currentFloorPoints.length >= 2) {
            indoorFooter.innerHTML = `
                <span class="indoor-badge badge-green">Indoor Route Ready</span>
                Follow the green route line to the destination.
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
                color: '#16a34a',
                weight: 8,
                opacity: 1,
                dashArray: null,
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

        if (currentFloorPoints.length >= 2) {
            indoorMap.fitBounds(L.latLngBounds(currentFloorPoints), {
                padding: [40, 40]
            });
        }

        if (indoorFooter && lastIndoorRoutePackage) {
            const entranceFloor = Number(entranceFeature?.properties?.floor_number ?? NaN);
            const roomFloor = Number(roomFeature?.properties?.floor_number ?? NaN);
            const currentFloorLabel = indoorFloorSelect?.selectedOptions?.[0]?.textContent || (`Floor ${currentIndoorFloor}`);

            let guideText = 'Follow the green route line on this floor.';

            if (Number(currentIndoorFloor) === entranceFloor && Number(currentIndoorFloor) !== roomFloor) {
                guideText = 'Start here at the entrance. Follow this floor route first, then use the floor buttons/stairs to continue.';
            } else if (Number(currentIndoorFloor) === roomFloor) {
                guideText = 'This is the destination floor. Follow the green route to the selected room or office.';
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

    function findRouteToLanduse(landuseId) {
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

        const result = dijkstra(startNodeKey, targetNodeKey);

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

    function findRouteToBuilding(buildingId) {
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

        const result = dijkstra(startNodeKey, gatewayNodeKey);

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
    }

    function findBestEntranceLinkForRoom(roomFeature) {
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
        const validCandidates = [];

        candidateLinks.forEach(link => {
            const outdoorEntrance = buildingEntrances.find(
                be => Number(be.id) === Number(link.building_entrance_id || link.building_entrance?.id)
            );

            const indoorEntranceId = Number(link.indoor_entrance_id || link.indoor_entrance?.id);
            const indoorEntranceFeature = findIndoorEntranceFeatureById(indoorEntranceId);

            if (!outdoorEntrance || !indoorEntranceFeature) return;

            const indoorEntranceFloor = Number(indoorEntranceFeature.properties?.floor_number || 0);
            const floorDiff = Math.abs(Number(roomFloor) - Number(indoorEntranceFloor));

            const outdoorNodeKey = nearestNodeKey(
                Number(outdoorEntrance.latitude),
                Number(outdoorEntrance.longitude)
            );

            if (!outdoorNodeKey) return;

            const indoorStartNodeKey = indoorGraphData.entranceNodeById[indoorEntranceId];
            if (!indoorStartNodeKey) return;

            const outdoorResult = dijkstra(startNodeKey, outdoorNodeKey);
            if (!outdoorResult) return;

            const indoorResult = dijkstraIndoor(
                indoorGraphData.graph,
                indoorStartNodeKey,
                roomNodeKey
            );
            if (!indoorResult) return;

            const directDoorMeters = startLatLng ? map.distance(
                [startLatLng.lat, startLatLng.lng],
                [Number(outdoorEntrance.latitude), Number(outdoorEntrance.longitude)]
            ) : Number(outdoorResult.totalCost || 0);

            const primaryOrMain = isPrimaryOrMain(outdoorEntrance, indoorEntranceFeature);
            const sideEntrance = isSideEntrance(outdoorEntrance, indoorEntranceFeature);
            const isSameFloorEntrance = indoorEntranceFloor === roomFloor;
            const usesCoveredStairs = routeUsesCoveredStairs(outdoorResult);

            validCandidates.push({
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
            });
        });

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

        function debugEntranceDecision(chosen, rule) {
            console.table(validCandidates.map(c => ({
                selected: c === chosen ? 'YES' : '',
                rule,
                outdoor_entrance: c.outdoorEntrance?.name || c.outdoorEntrance?.id,
                indoor_entrance: c.indoorEntranceFeature?.properties?.name || c.indoorEntranceFeature?.properties?.id,
                direct_m: Number(c.directDoorMeters.toFixed(2)),
                outdoor_cost: Number(c.outdoorCost.toFixed(2)),
                indoor_cost: Number(c.indoorCost.toFixed(2)),
                total_cost: Number(c.totalCost.toFixed(2)),
                indoor_floor: c.indoorEntranceFloor,
                room_floor: c.roomFloor,
                floor_diff: c.floorDiff,
                same_floor: c.isSameFloorEntrance,
                closest_floor_group: c.floorDiff === minFloorDiff,
                main_primary: c.primaryOrMain,
                side: c.sideEntrance,
                smart_score: Number(smartScore(c).toFixed(2))
            })));
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
            debugEntranceDecision(chosen, 'SAME_FLOOR_DOORWAY_LOCK');
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
                    debugEntranceDecision(bestClosestFloor, 'UPPER_CLOSEST_FLOOR_PRIORITY');
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
            debugEntranceDecision(chosen, 'NEAR_DOORWAY_LOCK');
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
                    debugEntranceDecision(bestSameFloor, 'ROOM_1F_SAME_FLOOR_PRACTICAL');
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
        debugEntranceDecision(chosen, 'SMART_SCORE_FALLBACK');
        return chosen;
    }


    function computeCompleteRouteToRoom(roomFeature) {
        if (!roomFeature) return;

        if (!startNodeKey) {
            alert('Please choose your starting point first.');
            return;
        }

        selectedIndoorRoomFeature = roomFeature;
        selectedDestinationBuildingId = Number(roomFeature.properties?.building_id);

        const bestRoute = findBestEntranceLinkForRoom(roomFeature);

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
            computeCompleteRouteToRoom(room);
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

    function getPathType(feature) {
        const props = feature.properties || {};
        let type = String(props.type || 'walkway').trim().toLowerCase();

        if (type === 'covered_stairs') return 'covered_stairs';
        if (type.includes('stairs')) return 'stairs';
        if (type === 'road' || type === 'roads') return 'road';
        return 'walkway';
    }

    const pathConfig = {
        road: {
            color: '#475569',
            weight: 6,
            dashArray: null
        },
        walkway: {
            color: '#0ea5e9',
            weight: 4.5,
            dashArray: null
        },
        stairs: {
            color: '#f59e0b',
            weight: 4.5,
            dashArray: '4, 6'
        },
        covered_stairs: {
            color: '#1e293b',
            weight: 10,
            dashArray: null,
            className: 'path-covered-stairs'
        }
    };

    function stylePath(feature) {
        const type = getPathType(feature);
        const config = pathConfig[type] || pathConfig.walkway;

        return {
            color: config.color,
            weight: config.weight,
            opacity: 0.9,
            lineCap: 'round',
            lineJoin: 'round',
            dashArray: config.dashArray || null,
            className: `path-interactive ${config.className || ''}`
        };
    }

    function renderLanduses() {
        if (landuseLayer) {
            map.removeLayer(landuseLayer);
            landuseLayer = null;
        }

        if (landuseLabelLayer) {
            map.removeLayer(landuseLabelLayer);
            landuseLabelLayer = null;
        }

        if (landuseImageLayer) {
            map.removeLayer(landuseImageLayer);
            landuseImageLayer = null;
        }

        landuseLabelLayer = L.layerGroup();
        landuseImageLayer = L.layerGroup();

        const featureCollection = {
            type: 'FeatureCollection',
            features: (landuseRecords || []).map(landuse => ({
                type: 'Feature',
                geometry: landuse.geometry,
                properties: {
                    ...(landuse.properties || {}),
                    id: landuse.id,
                    name: landuse.name,
                    type: landuse.type ?? landuse.landuse_type ?? landuse.properties?.type ?? null,
                    landuse_type: landuse.landuse_type ?? landuse.type ?? landuse.properties?.landuse_type ?? null,
                    image: landuse.image || null,

                    image_width: Number(landuse.image_width || 120),
                    image_height: Number(landuse.image_height || 120),
                    image_rotation: Number(landuse.image_rotation || 0),
                    image_offset_x: Number(landuse.image_offset_x || 0),
                    image_offset_y: Number(landuse.image_offset_y || 0),

                    image_scale_x: Number(landuse.image_scale_x ?? 1),
                    image_scale_y: Number(landuse.image_scale_y ?? 1),
                    image_offset_x_ratio: Number(landuse.image_offset_x_ratio ?? 0),
                    image_offset_y_ratio: Number(landuse.image_offset_y_ratio ?? 0),

                    polygon_base_angle: Number(landuse.polygon_base_angle ?? 0),
                    image_local_scale_x: Number(landuse.image_local_scale_x ?? 1),
                    image_local_scale_y: Number(landuse.image_local_scale_y ?? 1),
                    image_local_offset_u: Number(landuse.image_local_offset_u ?? 0),
                    image_local_offset_v: Number(landuse.image_local_offset_v ?? 0),
                    image_local_rotation: Number(landuse.image_local_rotation ?? 0),

                    image_tl_lat: landuse.image_tl_lat ?? null,
                    image_tl_lng: landuse.image_tl_lng ?? null,
                    image_tr_lat: landuse.image_tr_lat ?? null,
                    image_tr_lng: landuse.image_tr_lng ?? null,
                    image_bl_lat: landuse.image_bl_lat ?? null,
                    image_bl_lng: landuse.image_bl_lng ?? null,
                    image_br_lat: landuse.image_br_lat ?? null,
                    image_br_lng: landuse.image_br_lng ?? null,
                }
            }))
        };

        landuseLayer = L.geoJSON(featureCollection, {
            pane: 'pathsPane',
            interactive: false,
            style: function(feature) {
                const p = feature?.properties || {};
                const isField = isOpenFieldLanduse({
                    name: p.name,
                    properties: p
                });
                const isCourt = isMultipurposeCourtLanduse({
                    name: p.name,
                    properties: p
                });
                const hasImage = !!p.image;
                const isDesign = isDesignLanduse({
                    type: p.type,
                    name: p.name,
                    properties: p
                });

                if (isDesign) {
                    return {
                        color: '#a855f7',
                        weight: 1,
                        fillColor: hasImage ? '#ffffff' : '#f3e8ff',
                        fillOpacity: hasImage ? 0.03 : 0.22,
                        dashArray: '4, 6'
                    };
                }

                if (isCourt) {
                    return {
                        color: '#2563eb',
                        weight: 1.5,
                        fillColor: hasImage ? '#ffffff' : '#93c5fd',
                        fillOpacity: hasImage ? 0.03 : 0.32
                    };
                }

                if (isField) {
                    return {
                        color: '#2f7d32',
                        weight: 1.5,
                        fillColor: hasImage ? '#ffffff' : '#86efac',
                        fillOpacity: hasImage ? 0.03 : 0.38
                    };
                }

                return {
                    color: '#94a3b8',
                    weight: 1,
                    fillColor: hasImage ? '#ffffff' : '#e2e8f0',
                    fillOpacity: hasImage ? 0.03 : 0.20
                };
            },
            onEachFeature: function(feature, layer) {
                const p = feature.properties || {};

                /*
                |--------------------------------------------------------------------------
                | LANDUSE POPUP / MAP CLICK REMOVED
                |--------------------------------------------------------------------------
                | Landuse polygons are display-only on the map now.
                | No popup and no direct map-click route action.
                | Routing to landuse still works through Browse Destination / text search
                | because selectLanduseDestination() and findRouteToLanduse() are untouched.
                */
                if (p.image) {
                    addClippedLanduseOverlay(feature, p, landuseImageLayer);
                }
            }
        }).addTo(map);

        landuseImageLayer.addTo(map);
        landuseLabelLayer.addTo(map);
    }

    function renderBuildings() {
        let geojsonLayers = [];
        updateBuildingPerformanceMode();

        buildingRecords.forEach((building, index) => {
            const buildingName = building.name || building.properties?.name || 'Building';
            const baseColor = normalizeColor(building.color || building.properties?.color || '#2b82cc');

            const geojson = {
                type: 'Feature',
                geometry: building.geometry,
                properties: {
                    ...(building.properties || {}),
                    id: building.id,
                    name: buildingName,
                    color: baseColor
                }
            };

            const className = `fake-3d-building-${index}`;
            addDynamicBuildingStyle(className, baseColor);

            /*
            |--------------------------------------------------------------------------
            | BUILDING LAYER ONLY
            |--------------------------------------------------------------------------
            | No duplicate shadow polygons are drawn here.
            | Desktop/mobile shadows are handled by lightweight CSS drop-shadow.
            */
            const layer = L.geoJSON(geojson, {
                pane: 'buildingsPane',
                className: `fake-3d-building ${className}`,
                style: {
                    color: '#1f2937',
                    weight: 1.5,
                    fillColor: baseColor,
                    fillOpacity: 1,
                    lineJoin: 'round'
                },
                onEachFeature: function(feature, layer) {
                    const bId = feature.properties.id;

                    layer.bindPopup(`
                        <h3 class="custom-popup-title">🏢 ${buildingName}</h3>
                        <p class="custom-popup-subtitle">Click to open indoor rooms</p>
                    `);

                    layer.on('click', () => {
                        destinationBuildingSelect.value = String(bId);
                        selectedDestinationBuildingId = Number(bId);
                        updateRouteLabels();

                        // Buildings without indoor maps should do nothing silently.
                        if (hasIndoorMapForBuilding(bId)) {
                            openIndoorPanelForBuilding(bId);
                        }
                    });
                }
            }).addTo(map);

            applyBuildingDepthVariables(layer, baseColor);
            geojsonLayers.push(layer);
        });

        if (geojsonLayers.length > 0) {
            const group = L.featureGroup(geojsonLayers);
            const bounds = group.getBounds();

            if (bounds.isValid()) {
                map.fitBounds(bounds, {
                    padding: [50, 50],
                    maxZoom: 18.5
                });
                campusBounds = bounds.pad(0.08);
            }
        } else {
            map.setView([10.2925, 124.9985], IS_MOBILE_OUTDOOR_VIEW ? MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE : 18);
        }
    }

    function renderPaths() {
        L.geoJSON(pathGeojson, {
            pane: 'pathsPane',
            filter: (f) => getPathType(f) !== 'covered_stairs',
            style: (f) => {
                const isRoad = getPathType(f) === 'road';
                return {
                    color: '#e2e8f0',
                    weight: isRoad ? 10 : 8,
                    opacity: 0.8,
                    lineCap: 'round',
                    lineJoin: 'round',
                    interactive: false
                };
            }
        }).addTo(map);

        L.geoJSON(pathGeojson, {
            pane: 'pathsPane',
            style: stylePath,
            onEachFeature: function(feature, layer) {
                const props = feature.properties || {};
                const name = props.name || 'Unnamed Route';
                const typeLabel = String(props.type || 'Route').replaceAll('_', ' ').toUpperCase();

                layer.bindPopup(`
                    <div style="font-family:'Plus Jakarta Sans',sans-serif; text-align:left; min-width:180px;">
                        <div style="font-size:10px; color:#64748b; font-weight:800; letter-spacing:0.5px; margin-bottom:2px;">
                            ${typeLabel}
                        </div>
                        <div style="font-size:15px; font-weight:700; color:#1e293b;">
                            ${name}
                        </div>
                    </div>
                `);

                layer.on('click', function(e) {
                    if (placingStartMode) {
                        placePickStartAndClose(e.latlng.lat, e.latlng.lng, 'Start Point on Path');
                        L.DomEvent.stopPropagation(e);
                        return;
                    }
                });
            }
        }).addTo(map);

        L.geoJSON(pathGeojson, {
            pane: 'pathsPane',
            filter: (f) => getPathType(f) === 'covered_stairs',
            style: {
                color: '#f8fafc',
                weight: 6,
                opacity: 0.95,
                dashArray: '2, 10',
                className: 'path-canopy-frames'
            },
            interactive: false
        }).addTo(map);

        const legend = L.control({
            position: 'bottomright'
        });
        legend.onAdd = function() {
            const div = L.DomUtil.create('div', 'premium-legend');
            div.innerHTML = `
                <span class="legend-title">Campus Routes</span>

                <div class="legend-item">
                    <span class="legend-line" style="background:#475569"></span>
                    <span>Main Road</span>
                </div>

                <div class="legend-item">
                    <span class="legend-line" style="background:#0ea5e9"></span>
                    <span>Walkway</span>
                </div>

                <div class="legend-item">
                    <span class="legend-line" style="background:#f59e0b; border:1px dashed #fff"></span>
                    <span>Open Stairs</span>
                </div>

                <div class="legend-item">
                    <span class="legend-line" style="background:#1e293b; height:8px;"></span>
                    <span>Covered Stairs</span>
                </div>

                <div class="legend-item" style="margin-top:12px; padding-top:12px; border-top:1px solid #e2e8f0;">
                    <span class="legend-line" style="background:#2563eb"></span>
                    <span>Computed Route</span>
                </div>
            `;
            return div;
        };
        legend.addTo(map);
    }


    function setVoiceStatus(text) {
        if (voiceStatusLabel) {
            voiceStatusLabel.textContent = `Voice Status: ${text}`;
        }
    }

    function setHeardText(text = '') {
        if (!voiceHeardText || !voiceHeardValue) return;

        if (!text) {
            voiceHeardText.style.display = 'none';
            voiceHeardValue.textContent = '-';
            return;
        }

        voiceHeardText.style.display = 'block';
        voiceHeardValue.textContent = text;
    }

    function updateVoiceButtonUi() {
        if (!voiceCommandBtn) return;

        if (isVoiceListening) {
            voiceCommandBtn.classList.add('listening');
            voiceCommandBtn.innerHTML = '🛑 Stop';
        } else {
            voiceCommandBtn.classList.remove('listening');
            voiceCommandBtn.innerHTML = '🎤 Voice';
        }
    }

    function initVoiceRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (!SpeechRecognition) {
            voiceSupported = false;
            setVoiceStatus('Not supported in this browser');
            if (voiceCommandBtn) {
                voiceCommandBtn.disabled = true;
                voiceCommandBtn.style.opacity = '0.6';
                voiceCommandBtn.style.cursor = 'not-allowed';
            }
            return;
        }

        voiceSupported = true;
        speechRecognition = new SpeechRecognition();
        speechRecognition.lang = 'en-PH';
        speechRecognition.interimResults = false;
        speechRecognition.maxAlternatives = 1;
        speechRecognition.continuous = false;

        speechRecognition.onstart = function() {
            isVoiceListening = true;
            updateVoiceButtonUi();
            setVoiceStatus('Listening...');
        };

        speechRecognition.onend = function() {
            isVoiceListening = false;
            updateVoiceButtonUi();
            setVoiceStatus('Idle');
        };

        speechRecognition.onerror = function(event) {
            isVoiceListening = false;
            updateVoiceButtonUi();

            const message = event?.error ? String(event.error) : 'Unknown error';
            setVoiceStatus(`Error - ${message}`);

            if (message !== 'no-speech') {
                console.error('Voice recognition error:', message);
            }
        };

        speechRecognition.onresult = function(event) {
            const transcript = Array.from(event.results)
                .map(result => result[0]?.transcript || '')
                .join(' ')
                .trim();

            if (!transcript) {
                setVoiceStatus('No speech detected');
                return;
            }

            if (destinationSearchInput) {
                destinationSearchInput.value = transcript;
            }

            setHeardText(transcript);
            setVoiceStatus('Voice captured');

            searchTextDestination();
        };
    }

    function startVoiceCommand() {
        if (!voiceSupported || !speechRecognition) {
            alert('Voice recognition is not supported in this browser.');
            return;
        }

        setHeardText('');
        setVoiceStatus('Preparing microphone...');

        try {
            speechRecognition.start();
        } catch (error) {
            console.error(error);
        }
    }

    function stopVoiceCommand() {
        if (!speechRecognition) return;

        try {
            speechRecognition.stop();
        } catch (error) {
            console.error(error);
        }
    }

    function toggleVoiceCommand() {
        if (isVoiceListening) {
            stopVoiceCommand();
            return;
        }

        startVoiceCommand();
    }

    function populateLanduseSelect() {
        if (!destinationLanduseSelect) return;

        destinationLanduseSelect.innerHTML = '<option value="">Select Landuse Area</option>';

        const routableLanduses = getRoutableLanduses();

        routableLanduses.forEach(landuse => {
            destinationLanduseSelect.innerHTML += `
            <option value="${landuse.id}">
                ${landuse.name || `Landuse ${landuse.id}`}
            </option>
        `;
        });

        if (!routableLanduses.length) {
            destinationLanduseSelect.innerHTML += `
            <option value="" disabled>No routable landuse available</option>
        `;
        }
    }

    async function fetchJson(url) {
        const res = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });
        if (!res.ok) throw new Error(`Failed to load ${url}`);
        return await res.json();
    }


    /* =========================================================
       BUILDING-AWARE TEXT / VOICE ROOM MATCHING
       If the user's keyword contains a building + room, the selected room
       must come from that detected building even if another building has
       the same room name/code.
    ========================================================= */

    function normalizeDestinationSearchTextFinal(text) {
        return String(text || '')
            .toLowerCase()
            .replace(/[^a-z0-9\s]/gi, ' ')
            .replace(/\b(i|want|wanna|need|to|go|goto|navigate|route|take|bring|me|the|a|an|sa|ko|adto|moadto|asa|ang|dapit|room|office|kwarto|opisina)\b/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function getSearchWordsFinal(text) {
        return normalizeDestinationSearchTextFinal(text)
            .split(' ')
            .map(w => w.trim())
            .filter(Boolean);
    }

    function getBuildingSearchAliasesFinal(building) {
        const rawName = String(building?.name || '').trim();
        const normalizedName = normalizeDestinationSearchTextFinal(rawName);

        const aliases = new Set();

        if (normalizedName) aliases.add(normalizedName);

        /*
        |--------------------------------------------------------------------------
        | Make common building aliases searchable.
        | Examples:
        | "IT Building" => "it"
        | "B3" => "b3"
        | "SMB Building" => "smb"
        |--------------------------------------------------------------------------
        */
        normalizedName
            .replace(/\bbuilding\b/g, ' ')
            .replace(/\bhall\b/g, ' ')
            .replace(/\broom\b/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .split(' ')
            .filter(Boolean)
            .forEach(part => {
                if (part.length >= 2) aliases.add(part);
            });

        const p = building?.properties || {};
        [
            p.code,
            p.short_name,
            p.shortName,
            p.abbreviation,
            p.alias,
            p.aliases
        ].forEach(value => {
            if (Array.isArray(value)) {
                value.forEach(v => {
                    const n = normalizeDestinationSearchTextFinal(v);
                    if (n) aliases.add(n);
                });
            } else {
                const n = normalizeDestinationSearchTextFinal(value);
                if (n) aliases.add(n);
            }
        });

        return Array.from(aliases).filter(Boolean);
    }

    function detectBuildingFromTextFinal(message) {
        const normalized = normalizeDestinationSearchTextFinal(message);
        const words = getSearchWordsFinal(message);

        if (!normalized || !buildingRecords?.length) return null;

        let best = null;
        let bestScore = -1;

        (buildingRecords || []).forEach(building => {
            const aliases = getBuildingSearchAliasesFinal(building);
            let score = -1;

            aliases.forEach(alias => {
                if (!alias) return;

                if (normalized === alias) {
                    score = Math.max(score, 2000 + alias.length);
                    return;
                }

                const exactRegex = new RegExp(`(^|\\s)${alias.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(\\s|$)`, 'i');

                if (exactRegex.test(normalized)) {
                    score = Math.max(score, 1600 + alias.length);
                    return;
                }

                const aliasWords = alias.split(' ').filter(Boolean);
                const common = aliasWords.filter(w => words.includes(w));

                if (common.length) {
                    score = Math.max(score, (common.length * 180) + alias.length);
                }
            });

            if (score > bestScore) {
                bestScore = score;
                best = building;
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Require a meaningful match so random single letters will not win.
        |--------------------------------------------------------------------------
        */
        if (best && bestScore >= 180) {
            return best;
        }

        return null;
    }

    function scoreRoomAgainstTextFinal(roomFeature, message) {
        const normalized = normalizeDestinationSearchTextFinal(message);
        const words = getSearchWordsFinal(message);
        const p = roomFeature?.properties || {};

        const roomName = normalizeDestinationSearchTextFinal(p.name || '');
        const roomCode = normalizeDestinationSearchTextFinal(p.room_code || '');
        const roomType = normalizeDestinationSearchTextFinal(p.type || '');

        let score = -1;

        if (roomCode) {
            const codeRegex = new RegExp(`(^|\\s)${roomCode.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(\\s|$)`, 'i');

            if (normalized === roomCode) {
                score = Math.max(score, 2200 + roomCode.length);
            } else if (codeRegex.test(normalized)) {
                score = Math.max(score, 2000 + roomCode.length);
            } else if (normalized.includes(roomCode)) {
                score = Math.max(score, 1800 + roomCode.length);
            }
        }

        if (roomName) {
            const nameRegex = new RegExp(`(^|\\s)${roomName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(\\s|$)`, 'i');

            if (normalized === roomName) {
                score = Math.max(score, 1700 + roomName.length);
            } else if (nameRegex.test(normalized)) {
                score = Math.max(score, 1500 + roomName.length);
            } else if (normalized.includes(roomName)) {
                score = Math.max(score, 1400 + roomName.length);
            } else {
                const roomWords = roomName.split(' ').filter(Boolean);
                const common = roomWords.filter(w => words.includes(w));

                if (common.length) {
                    score = Math.max(score, (common.length * 120) + roomName.length);
                }
            }
        }

        if (roomType) {
            const typeWords = roomType.split(' ').filter(Boolean);
            const common = typeWords.filter(w => words.includes(w));

            if (common.length) {
                score = Math.max(score, (common.length * 70) + roomType.length);
            }
        }

        return score;
    }

    function findBestRoomInsideBuildingFromTextFinal(buildingId, message) {
        const candidateRooms = (allIndoorRooms.features || []).filter(room =>
            Number(room.properties?.building_id) === Number(buildingId)
        );

        let bestRoom = null;
        let bestScore = -1;

        candidateRooms.forEach(room => {
            const score = scoreRoomAgainstTextFinal(room, message);

            if (score > bestScore) {
                bestScore = score;
                bestRoom = room;
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Score threshold prevents auto-selecting a random room when the text only
        | contains a building and no room keyword.
        |--------------------------------------------------------------------------
        */
        if (bestRoom && bestScore >= 120) {
            return bestRoom;
        }

        return null;
    }

    function makeRoomDestinationResultFinal(roomFeature) {
        const p = roomFeature?.properties || {};

        return {
            destination_type: 'room',
            destination_id: Number(p.id),
            label: p.name || p.room_code || 'Room / Office',
            room_code: p.room_code || null,
            building_id: Number(p.building_id),
            building_name: p.building_name || getBuildingNameById(p.building_id),
            floor_number: p.floor_number ?? null,
            floor_label: p.floor_label || (hasIndoorFloorValue(p.floor_number) ? formatIndoorFloorLabel(p.floor_number) : null)
        };
    }

    function refineTextSearchResultByBuildingContextFinal(apiResult, message) {
        /*
        |--------------------------------------------------------------------------
        | Main rule:
        | If message contains a building, and message also matches a room inside
        | that building, force the selected room to be under that detected building.
        |--------------------------------------------------------------------------
        */
        const detectedBuilding = detectBuildingFromTextFinal(message);

        if (!detectedBuilding) {
            return {
                result: apiResult,
                buildingContextLabel: ''
            };
        }

        const detectedBuildingId = Number(detectedBuilding.id);
        const bestRoomInDetectedBuilding = findBestRoomInsideBuildingFromTextFinal(detectedBuildingId, message);

        if (bestRoomInDetectedBuilding) {
            return {
                result: makeRoomDestinationResultFinal(bestRoomInDetectedBuilding),
                buildingContextLabel: detectedBuilding.name || getBuildingNameById(detectedBuildingId)
            };
        }

        /*
        |--------------------------------------------------------------------------
        | If API returned a room from another building but the text explicitly
        | mentioned a building, do not keep that wrong-room match.
        | Fallback to routing to the detected building only.
        |--------------------------------------------------------------------------
        */
        if (
            apiResult?.destination_type === 'room' &&
            Number(apiResult?.building_id || 0) !== detectedBuildingId
        ) {
            return {
                result: {
                    destination_type: 'building',
                    destination_id: detectedBuildingId,
                    label: detectedBuilding.name || getBuildingNameById(detectedBuildingId)
                },
                buildingContextLabel: detectedBuilding.name || getBuildingNameById(detectedBuildingId)
            };
        }

        return {
            result: apiResult,
            buildingContextLabel: detectedBuilding.name || getBuildingNameById(detectedBuildingId)
        };
    }


    async function searchTextDestination() {
        const message = String(destinationSearchInput?.value || '').trim();

        if (!message) {
            alert('Please type your destination message first.');
            return;
        }

        if (!ensureDefaultStartBeforeRoute()) return;

        try {
            setRouteResultLabel('Checking destination keyword database...');

            const apiResponse = await fetchJson(`/api/search-destination?q=${encodeURIComponent(message)}`);

            if (!apiResponse || !apiResponse.success || !apiResponse.result) {
                const errorMessage = apiResponse?.message || 'No destination keyword matched your text.';
                alert(errorMessage);
                setRouteResultLabel(errorMessage);
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | STRICT DESTINATION KEYWORD RULE
            |--------------------------------------------------------------------------
            | Do NOT route if the destination was not matched from the
            | destination_keywords database table.
            |
            | The updated ApiController below returns:
            | is_keyword_match: true
            | source: "destination_keywords"
            |
            | This prevents direct/fallback routing to building/room names that are
            | not registered as destination keywords by the admin.
            |--------------------------------------------------------------------------
            */
            const isStrictKeywordMatch =
                apiResponse.is_keyword_match === true ||
                apiResponse.source === 'destination_keywords';

            if (!isStrictKeywordMatch) {
                const errorMessage = 'No active destination keyword matched your text. Please ask admin to add this keyword first.';
                alert(errorMessage);
                setRouteResultLabel(errorMessage);
                return;
            }

            const matchedText = [
                ...(Array.isArray(apiResponse.matched_keywords) ? apiResponse.matched_keywords : []),
                apiResponse.matched_keyword || ''
            ].filter(Boolean);

            closeTextSearchModal();
            applyTextSearchDestination(apiResponse.result, matchedText.length ? [...new Set(matchedText)].join(' + ') : '');
        } catch (error) {
            console.error(error);
            alert('Failed to search destination keyword.');
            setRouteResultLabel('Failed to search destination keyword.');
        }
    }

    function applyTextSearchDestination(result, matchedKeyword = '') {
        if (!result || !result.destination_type) {
            alert('Invalid destination result.');
            return;
        }

        const destinationType = String(result.destination_type);

        if (destinationType === 'building') {
            destinationTypeSelect.value = 'building';
            updateDestinationUi();

            selectedDestinationBuildingId = Number(result.destination_id);
            selectedDestinationLanduseId = null;
            selectedIndoorRoomFeature = null;
            selectedBuildingEntranceId = null;

            if (destinationBuildingSelect) {
                destinationBuildingSelect.value = String(result.destination_id);
            }

            if (destinationLanduseSelect) destinationLanduseSelect.value = '';
            if (destinationRoomSelect) destinationRoomSelect.value = '';

            updateRouteLabels();
            setRouteResultLabel(`Matched "${matchedKeyword}" → ${result.label}`);
            findRouteToBuilding(Number(result.destination_id));
            return;
        }

        if (destinationType === 'landuse') {
            destinationTypeSelect.value = 'landuse';
            updateDestinationUi();

            selectedDestinationLanduseId = Number(result.destination_id);
            selectedDestinationBuildingId = null;
            selectedIndoorRoomFeature = null;
            selectedBuildingEntranceId = null;

            if (destinationLanduseSelect) {
                destinationLanduseSelect.value = String(result.destination_id);
            }

            if (destinationBuildingSelect) destinationBuildingSelect.value = '';
            if (destinationRoomSelect) destinationRoomSelect.value = '';

            updateRouteLabels();
            setRouteResultLabel(`Matched "${matchedKeyword}" → ${result.label}`);
            findRouteToLanduse(Number(result.destination_id));
            return;
        }

        if (destinationType === 'room') {
            destinationTypeSelect.value = 'room';
            updateDestinationUi();

            const roomId = Number(result.destination_id);

            if (destinationRoomSelect) {
                destinationRoomSelect.value = String(roomId);
            }

            const room = (allIndoorRooms.features || []).find(
                f => Number(f.properties?.id) === roomId
            );

            if (!room) {
                alert('Matched room exists in database but not found in loaded indoor data.');
                setRouteResultLabel('Matched room not found in indoor data.');
                return;
            }

            selectedIndoorRoomFeature = room;
            selectedDestinationBuildingId = Number(room.properties?.building_id || result.building_id);
            selectedDestinationLanduseId = null;
            selectedBuildingEntranceId = null;

            if (destinationBuildingSelect && selectedDestinationBuildingId) {
                destinationBuildingSelect.value = String(selectedDestinationBuildingId);
            }

            if (destinationLanduseSelect) {
                destinationLanduseSelect.value = '';
            }

            updateRouteLabels();

            const labelParts = [
                result.building_name || room.properties?.building_name || 'Building',
                result.label || room.properties?.name || room.properties?.room_code || 'Room',
                result.floor_label || room.properties?.floor_label || ''
            ].filter(Boolean);

            setRouteResultLabel(`Matched "${matchedKeyword}" → ${labelParts.join(' - ')}`);

            computeCompleteRouteToRoom(room);
            return;
        }

        alert('Unsupported destination type.');
        setRouteResultLabel('Unsupported destination type.');
    }

    function populateDestinationRoomSelect() {
        if (!destinationRoomSelect) return;

        destinationRoomSelect.innerHTML = '<option value="">Select Room / Office</option>';

        const rooms = [...(allIndoorRooms.features || [])].sort((a, b) => {
            const af = getRoomFloorNumber(a) ?? 999;
            const bf = getRoomFloorNumber(b) ?? 999;
            if (af !== bf) return af - bf;

            const aName = String(a.properties?.name || '');
            const bName = String(b.properties?.name || '');
            return aName.localeCompare(bName);
        });

        rooms.forEach(room => {
            const p = room.properties || {};
            const label =
                `${p.name || 'Room'}${p.room_code ? ' (' + p.room_code + ')' : ''} - ${p.building_name || ('Building ' + p.building_id)} - ${formatIndoorFloorLabel(p.floor_number, p.floor_label)}`;

            destinationRoomSelect.innerHTML += `
                <option value="${p.id}">
                    ${escapeBrowseHtml(label)}
                </option>
            `;
        });

        populateRoomBuildingFilterSelect();
        renderBrowseRoomPicker();
    }



    /* =========================================================
       CAMPUS EVENTS ON USER MAP
       - Building event: display above building, route to building.
       - Room event: display above parent building, route to specific room.
       - Landuse event: display above landuse, route to area.
    ========================================================= */
    function escapeEventHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function getFeatureCenterFromGeometry(geometry) {
        if (!geometry) return null;

        try {
            const tempLayer = L.geoJSON({
                type: 'Feature',
                geometry,
                properties: {}
            });

            const bounds = tempLayer.getBounds();
            if (!bounds || !bounds.isValid()) return null;

            return bounds.getCenter();
        } catch (error) {
            return null;
        }
    }

    function getBuildingCenterById(buildingId) {
        const building = (buildingRecords || []).find(
            item => Number(item.id) === Number(buildingId)
        );

        if (!building) return null;

        return getFeatureCenterFromGeometry(building.geometry);
    }

    function getLanduseCenterById(landuseId) {
        const landuse = (landuseRecords || []).find(
            item => Number(item.id) === Number(landuseId)
        );

        if (!landuse) return null;

        return getFeatureCenterFromGeometry(landuse.geometry);
    }

    function getEventPlaceText(event) {
        if (!event) return 'Campus';

        if (event.event_target_type === 'building') {
            return event.building_name || event.location_label || 'Building';
        }

        if (event.event_target_type === 'room') {
            const parts = [];

            if (event.building_name) parts.push(event.building_name);
            if (event.floor_label) parts.push(event.floor_label);

            const roomText = `${event.room_code ? event.room_code : ''}${event.room_name ? ' / ' + event.room_name : ''}`.trim();
            if (roomText) parts.push(roomText);

            return parts.length ? parts.join(' — ') : (event.location_label || 'Indoor Room');
        }

        if (event.event_target_type === 'landuse') {
            return event.landuse_name || event.location_label || 'Landuse Area';
        }

        return event.location_label || 'Campus';
    }

    function getEventMarkerLatLng(event) {
        if (!event) return null;

        if (event.display_type === 'building') {
            return getBuildingCenterById(event.display_id);
        }

        if (event.display_type === 'landuse') {
            return getLanduseCenterById(event.display_id);
        }

        return null;
    }

    function formatEventButtonText(event) {
        if (!event) return 'Route';

        if (event.route_type === 'room') return 'Route to Room';
        if (event.route_type === 'landuse') return 'Route to Area';

        return 'Route to Building';
    }

    function createCampusEventCardHtml(event) {
        const isNow = event.status === 'happening_now';

        const statusClass = isNow ? 'now' : 'upcoming';
        const cardClass = isNow ? 'now-card' : 'upcoming-card';
        const dotClass = isNow ? '' : 'upcoming-dot';
        const statusText = isNow ? 'Now' : 'Upcoming';

        const title = escapeEventHtml(event.title || 'Campus Event');
        const place = escapeEventHtml(getEventPlaceText(event));

        const startsAt = escapeEventHtml(event.starts_at_display || event.starts_at || 'No start time');
        const endsAt = event.ends_at_display
            ? `<br><span>Ends: ${escapeEventHtml(event.ends_at_display)}</span>`
            : '';

        const targetText =
            event.route_type === 'room'
                ? 'Room'
                : event.route_type === 'landuse'
                    ? 'Area'
                    : 'Building';

        return `
            <div class="campus-event-card ${cardClass}">
                <div class="campus-event-top">
                    <div class="campus-event-status ${statusClass}">
                        <span class="campus-event-mini-dot ${dotClass}"></span>
                        ${statusText}
                    </div>
                    <div class="campus-event-target">${targetText}</div>
                </div>

                <div class="campus-event-title">${title}</div>
                <div class="campus-event-place">${place}</div>

                <div class="campus-event-time">
                    <span>🕒</span>
                    <span>
                        Starts: ${startsAt}
                        ${endsAt}
                    </span>
                </div>

                <button type="button"
                        class="campus-event-route-btn"
                        onclick="routeToCampusEvent(${Number(event.id)})">
                    ${formatEventButtonText(event)}
                </button>
            </div>
        `;
    }

    function ensureCampusEventPanel() {
        let wrap = document.getElementById('campus-event-notification-wrap');

        if (wrap) return wrap;

        wrap = document.createElement('div');
        wrap.id = 'campus-event-notification-wrap';
        wrap.className = 'campus-event-notification-wrap force-visible';
        wrap.innerHTML = `
            <button type="button"
                    class="campus-event-bell-btn"
                    id="campus-event-bell-btn"
                    onclick="toggleCampusEventPanel()"
                    aria-label="Open campus events">
                <span class="campus-event-bell-pulse" id="campus-event-bell-pulse" style="display:none;"></span>
                <span class="campus-event-bell-icon">🔔</span>
                <span class="campus-event-bell-count is-zero" id="campus-event-bell-count">0</span>
            </button>

            <div class="campus-event-panel" id="campus-event-panel">
                <div class="campus-event-panel-card">
                    <div class="campus-event-panel-head">
                        <div>
                            <div class="campus-event-panel-kicker">
                                <span class="campus-event-mini-dot upcoming-dot"></span>
                                Campus Events
                            </div>
                            <div class="campus-event-panel-title">Current & Upcoming</div>
                            <div class="campus-event-panel-subtitle">
                                Tap an event to route. Panel stays hidden until you open it.
                            </div>
                        </div>

                        <button type="button"
                                class="campus-event-panel-close"
                                onclick="closeCampusEventPanel()"
                                aria-label="Close campus events">
                            ×
                        </button>
                    </div>

                    <div class="campus-event-list" id="campus-event-list"></div>

                    <div class="campus-event-empty" id="campus-event-empty" style="display:none;">
                        <span class="campus-event-empty-icon">🔕</span>
                        No current or upcoming campus events.
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(wrap);

        document.addEventListener('click', function(event) {
            const currentWrap = document.getElementById('campus-event-notification-wrap');
            const panel = document.getElementById('campus-event-panel');

            if (!currentWrap || !panel || !panel.classList.contains('open')) return;
            if (currentWrap.contains(event.target)) return;

            closeCampusEventPanel();
        });

        return wrap;
    }

    function toggleCampusEventPanel() {
        const panel = document.getElementById('campus-event-panel');
        if (!panel) return;

        panel.classList.toggle('open');
    }

    function closeCampusEventPanel() {
        const panel = document.getElementById('campus-event-panel');
        if (!panel) return;

        panel.classList.remove('open');
    }

    function renderCampusEventMarkers() {
        /*
        |--------------------------------------------------------------------------
        | EVENT UI - ALWAYS VISIBLE NOTIFICATION BELL
        |--------------------------------------------------------------------------
        | The bell should stay visible even if there are no active events.
        | If no events exist, badge shows 0 and panel shows an empty message.
        |--------------------------------------------------------------------------
        */

        if (campusEventLayer) {
            campusEventLayer.clearLayers();
        }

        const activeEvents = (campusEvents || []).filter(event => {
            return event && event.id && event.route_type && event.route_id;
        });

        const wrap = ensureCampusEventPanel();
        const list = document.getElementById('campus-event-list');
        const empty = document.getElementById('campus-event-empty');
        const count = document.getElementById('campus-event-bell-count');
        const pulse = document.getElementById('campus-event-bell-pulse');
        const bell = document.getElementById('campus-event-bell-btn');

        /*
        | Important:
        | Previous code used wrap.style.display = 'none' when no events.
        | That is why the notification icon disappeared.
        */
        wrap.style.display = 'block';
        wrap.classList.add('force-visible');

        if (count) {
            count.textContent = activeEvents.length > 9 ? '9+' : String(activeEvents.length);
            count.classList.toggle('is-zero', activeEvents.length === 0);
        }

        if (pulse) {
            const hasNow = activeEvents.some(event => event.status === 'happening_now');
            pulse.style.display = hasNow ? 'block' : 'none';
        }

        if (bell) {
            bell.title = activeEvents.length
                ? `${activeEvents.length} current/upcoming campus event${activeEvents.length > 1 ? 's' : ''}`
                : 'No current or upcoming campus events';
        }

        if (list) {
            list.style.display = activeEvents.length ? 'block' : 'none';
            list.innerHTML = activeEvents.length
                ? activeEvents.map(event => createCampusEventCardHtml(event)).join('')
                : '';
        }

        if (empty) {
            empty.style.display = activeEvents.length ? 'none' : 'block';
        }
    }

    function routeToCampusEvent(eventId) {
        closeCampusEventPanel?.();

        const event = (campusEvents || []).find(
            item => Number(item.id) === Number(eventId)
        );

        if (!event) {
            alert('Event not found.');
            return;
        }

        if (typeof closeBrowseOptionsModal === 'function') closeBrowseOptionsModal();
        if (typeof closeAiTransformPanel === 'function') closeAiTransformPanel();

        if (event.route_type === 'building') {
            if (!destinationTypeSelect || !destinationBuildingSelect) return;

            destinationTypeSelect.value = 'building';
            updateDestinationUi();

            destinationBuildingSelect.value = String(event.route_id || '');
            selectedDestinationBuildingId = event.route_id ? Number(event.route_id) : null;
            selectedDestinationLanduseId = null;
            selectedIndoorRoomFeature = null;

            updateRouteLabels();
            findRouteByDestination();
            return;
        }

        if (event.route_type === 'room') {
            if (!destinationTypeSelect || !destinationRoomSelect) return;

            destinationTypeSelect.value = 'room';
            updateDestinationUi();

            destinationRoomSelect.value = String(event.route_id || '');

            const room = (allIndoorRooms.features || []).find(
                feature => Number(feature.properties?.id) === Number(event.route_id)
            );

            if (room) {
                selectedIndoorRoomFeature = room;
                selectedDestinationBuildingId = Number(room.properties?.building_id || event.building_id);
            } else {
                selectedIndoorRoomFeature = null;
                selectedDestinationBuildingId = event.building_id ? Number(event.building_id) : null;
            }

            selectedDestinationLanduseId = null;

            updateRouteLabels();
            findRouteByDestination();
            return;
        }

        if (event.route_type === 'landuse') {
            if (!destinationTypeSelect || !destinationLanduseSelect) return;

            const eventLanduse = (landuseRecords || []).find(item => Number(item.id) === Number(event.route_id));
            if (eventLanduse && isDesignLanduse(eventLanduse)) {
                setRouteResultLabel('This event is on a design-only landuse. Routing is disabled for design areas.');
                return;
            }

            destinationTypeSelect.value = 'landuse';
            updateDestinationUi();

            destinationLanduseSelect.value = String(event.route_id || '');
            selectedDestinationLanduseId = event.route_id ? Number(event.route_id) : null;
            selectedDestinationBuildingId = null;
            selectedIndoorRoomFeature = null;

            updateRouteLabels();
            findRouteByDestination();
            return;
        }
    }
    async function loadAllData() {
        setIndoorLoading(true);

        const [
            buildings,
            paths,
            entries,
            entrances,
            hazards,
            landuses,
            indoorMaps,
            indoorRooms,
            indoorPaths,
            indoorEntrances,
            buildingEntranceLinks,
            indoorStairsLinks,
            events
        ] = await Promise.all([
            fetchJson('/api/buildings'),
            fetchJson('/api/paths'),
            fetchJson('/api/entry-points'),
            fetchJson('/api/building-entrances'),
            fetchJson('/api/hazard-points'),
            fetchJson('/api/landuses'),
            fetchJson('/api/indoor-maps'),
            fetchJson('/api/indoor-rooms'),
            fetchJson('/api/indoor-paths'),
            fetchJson('/api/indoor-entrances'),
            fetchJson('/api/building-entrance-links'),
            fetchJson('/api/indoor-stairs-links'),
            fetchJson('/api/campus-events')
        ]);

        buildingRecords = buildings || [];
        landuseRecords = (landuses || []).map(landuse => ({
            ...landuse,
            type: landuse.type ?? landuse.landuse_type ?? landuse.properties?.type ?? null,
            landuse_type: landuse.landuse_type ?? landuse.type ?? landuse.properties?.landuse_type ?? null,
            properties: {
                ...(landuse.properties || {}),
                type: landuse.properties?.type ?? landuse.type ?? landuse.landuse_type ?? null,
                landuse_type: landuse.properties?.landuse_type ?? landuse.landuse_type ?? landuse.type ?? null,
            }
        }));
        campusEvents = events || [];
        pathGeojson = paths || {
            type: 'FeatureCollection',
            features: []
        };
        entryPoints = entries || [];
        buildingEntrances = entrances || [];
        hazardPoints = hazards || [];

        allIndoorMaps = (indoorMaps || []).map(normalizeIndoorMapRecord);
        allIndoorRooms = normalizeFeatureCollection(indoorRooms);
        allIndoorPaths = normalizeFeatureCollection(indoorPaths);
        allIndoorEntrances = normalizeFeatureCollection(indoorEntrances);
        allBuildingEntranceLinks = buildingEntranceLinks || [];
        allIndoorStairsLinks = indoorStairsLinks || [];

        buildOutdoorGraph(pathGeojson);
        drawHazardMarkers();
        renderBuildings();
        renderLanduses();
        renderPaths();
        renderCampusEventMarkers();

        defaultEntrySelect.innerHTML = '<option value="">Default Start</option>';
        entryPoints.forEach(point => {
            const nodeKey = nearestNodeKey(Number(point.latitude), Number(point.longitude));
            defaultEntrySelect.innerHTML += `
                <option
                    value="${nodeKey || ''}"
                    data-entry-id="${point.id}"
                    data-name="${point.name || ''}"
                    data-latitude="${point.latitude ?? ''}"
                    data-longitude="${point.longitude ?? ''}">
                    ${point.name || 'Entry Point'}
                </option>
            `;
        });

        destinationBuildingSelect.innerHTML = '<option value="">Select Destination Building</option>';
        buildingRecords.forEach(building => {
            destinationBuildingSelect.innerHTML += `
        <option value="${building.id}">
            ${building.name || 'Building'}
        </option>
    `;
        });

        populateLanduseSelect();
        populateDestinationRoomSelect();

        destinationBuildingSelect.addEventListener('change', function() {
            selectedDestinationBuildingId = this.value ? Number(this.value) : null;
            selectedDestinationLanduseId = null;
            selectedIndoorRoomFeature = null;
            selectedBuildingEntranceId = null;
            updateRouteLabels();
        });

        destinationLanduseSelect.addEventListener('change', function() {
            const selectedLanduse = (landuseRecords || []).find(item => Number(item.id) === Number(this.value));

            if (selectedLanduse && isDesignLanduse(selectedLanduse)) {
                alert('This landuse is Design only and cannot be used as a route destination.');
                this.value = '';
                selectedDestinationLanduseId = null;
                updateRouteLabels();
                setRouteResultLabel('Design landuse is display-only and not available for routing.');
                return;
            }

            selectedDestinationLanduseId = this.value ? Number(this.value) : null;
            selectedDestinationBuildingId = null;
            selectedIndoorRoomFeature = null;
            selectedBuildingEntranceId = null;
            updateRouteLabels();
        });

        destinationRoomSelect.addEventListener('change', function() {
            const roomId = Number(this.value);
            const room = (allIndoorRooms.features || []).find(
                f => Number(f.properties?.id) === Number(roomId)
            );

            if (room) {
                selectedIndoorRoomFeature = room;
                selectedDestinationBuildingId = Number(room.properties?.building_id);
                selectedDestinationLanduseId = null;

                if (roomBuildingFilterSelect) {
                    roomBuildingFilterSelect.value = String(selectedDestinationBuildingId || '');
                }
            } else {
                selectedIndoorRoomFeature = null;
                selectedDestinationBuildingId = null;
            }

            selectedBuildingEntranceId = null;
            renderBrowseRoomPicker();
            updateRouteLabels();
        });

        if (roomBuildingFilterSelect) {
            roomBuildingFilterSelect.addEventListener('change', function() {
                browseRoomSelectedFloor = 'all';
                selectedIndoorRoomFeature = null;
                selectedBuildingEntranceId = null;

                if (destinationRoomSelect) destinationRoomSelect.value = '';

                const buildingId = Number(this.value || 0);
                selectedDestinationBuildingId = buildingId || null;
                if (destinationBuildingSelect) {
                    destinationBuildingSelect.value = buildingId ? String(buildingId) : '';
                }

                renderBrowseRoomPicker();
                updateRouteLabels();
            });
        }

        if (roomOfficeSearchInput) {
            roomOfficeSearchInput.addEventListener('input', function() {
                renderBrowseRoomPicker();
            });
        }

        destinationTypeSelect.addEventListener('change', function() {
            if (this.value === 'building') {
                selectedDestinationLanduseId = null;
                selectedIndoorRoomFeature = null;
                selectedBuildingEntranceId = null;
            } else if (this.value === 'landuse') {
                selectedDestinationBuildingId = null;
                selectedIndoorRoomFeature = null;
                selectedBuildingEntranceId = null;
            } else if (this.value === 'room') {
                selectedDestinationLanduseId = null;
            }
            updateDestinationUi();
            updateRouteLabels();
        });

        ensureIndoorMap();
        updateDestinationUi();
        updateShadows();
        updateRouteLabels();
        setActiveStartModeButton('default');
        setRouteResultLabel('Ready');

        setTimeout(() => {
            document.getElementById('map').style.opacity = '1';
            if (indoorMap) indoorMap.invalidateSize();
            setIndoorLoading(false);
        }, 150);
    }

    closeIndoorPanel.addEventListener('click', closeIndoorPanelFn);
    indoorBackdrop.addEventListener('click', closeIndoorPanelFn);

    indoorFloorSelect.addEventListener('change', function() {
        if (this.value === '') {
            currentIndoorFloor = null;
            return;
        }

        currentIndoorFloor = Number(this.value);
        renderIndoorRoomList();
        renderIndoorFloor();

        if (lastIndoorRoutePackage) {
            redrawPersistentIndoorRouteForCurrentFloor();
        }
    });

    indoorRoomSearch.addEventListener('input', function() {
        renderIndoorRoomList();
    });

    if (destinationSearchInput) {
        destinationSearchInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchTextDestination();
            }
        });
    }


    /* =========================================================
       VOICE PANEL PERSIST RESULT PATCH
       The voice panel will NOT auto-close after recognition.
       It displays the spoken text and waits for manual close.
    ========================================================= */

    let aiVoiceFinalTranscript = '';
    let aiVoiceSearchInProgress = false;
    let aiVoiceAllowAutoClose = false;

    function getAiVoicePanel() {
        return document.getElementById('ai-voice-panel');
    }

    function getAiVoiceResultCard() {
        return document.getElementById('ai-voice-result-card');
    }

    function getAiVoiceResultText() {
        return document.getElementById('ai-voice-result-text');
    }

    function resetAiVoiceResultUi() {
        aiVoiceFinalTranscript = '';

        const panel = getAiVoicePanel();
        const card = getAiVoiceResultCard();
        const text = getAiVoiceResultText();

        if (panel) panel.classList.remove('voice-finished');

        if (card) card.style.display = 'none';
        if (text) text.textContent = '-';

        if (typeof setHeardText === 'function') {
            setHeardText('');
        }
    }

    function showAiVoiceResultUi(transcript = '') {
        const cleanTranscript = String(transcript || '').trim();
        const panel = getAiVoicePanel();
        const card = getAiVoiceResultCard();
        const text = getAiVoiceResultText();

        if (panel) panel.classList.add('voice-finished');

        if (cleanTranscript) {
            aiVoiceFinalTranscript = cleanTranscript;
        }

        if (card) card.style.display = 'block';
        if (text) text.textContent = aiVoiceFinalTranscript ||
            'No clear speech detected. You can try Voice Search again.';

        if (typeof setHeardText === 'function') {
            setHeardText(aiVoiceFinalTranscript || 'No clear speech detected.');
        }

        if (typeof setVoiceStatus === 'function') {
            setVoiceStatus('Stopped. Result is displayed below.');
        }

        if (typeof setRouteResultLabel === 'function') {
            setRouteResultLabel(aiVoiceFinalTranscript ? 'Voice captured. Review the detected text.' :
                'Voice stopped. No clear speech detected.');
        }
    }

    function keepVoicePanelOpenAfterStop(transcript = '') {
        const panel = getAiVoicePanel();
        const dock = document.getElementById('floating-route-ui');

        if (dock) dock.classList.add('transforming', 'voice-mode');
        if (panel) panel.style.display = 'block';

        showAiVoiceResultUi(transcript);
    }

    /*
       Override close behavior:
       - Manual close button closes panel.
       - Speech recognition ending does NOT close panel.
    */
    const __baseCloseAiTransformPanel = typeof closeAiTransformPanel === 'function' ? closeAiTransformPanel : null;
    closeAiTransformPanel = function() {
        aiVoiceAllowAutoClose = true;
        aiVoiceSearchInProgress = false;

        if (typeof stopVoiceCommand === 'function' && typeof isVoiceListening !== 'undefined' && isVoiceListening) {
            stopVoiceCommand();
        }

        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const dock = document.getElementById('floating-route-ui');

        if (searchPanel) searchPanel.style.display = 'none';
        if (voicePanel) {
            voicePanel.style.display = 'none';
            voicePanel.classList.remove('voice-finished');
        }

        if (dock) dock.classList.remove('transforming', 'search-mode', 'voice-mode');

        resetAiVoiceResultUi();
        setRouteResultLabel('Ready');
    };

    const __baseOpenInlineVoiceSearch = typeof openInlineVoiceSearch === 'function' ? openInlineVoiceSearch : null;
    openInlineVoiceSearch = function() {
        resetAiVoiceResultUi();

        aiVoiceAllowAutoClose = false;
        aiVoiceSearchInProgress = true;

        if (typeof showAiVoicePanel === 'function') {
            showAiVoicePanel();
        } else {
            const panel = getAiVoicePanel();
            const dock = document.getElementById('floating-route-ui');
            if (dock) dock.classList.add('transforming', 'voice-mode');
            if (panel) panel.style.display = 'block';
        }

        if (typeof ensureDefaultStartBeforeRoute === 'function') {
            if (!ensureDefaultStartBeforeRoute()) return;
        }

        if (typeof toggleVoiceCommand === 'function') {
            toggleVoiceCommand();
        } else {
            console.error('toggleVoiceCommand() not found.');
            alert('Voice command function not found.');
        }
    };

    const __baseStartVoiceSearchFlow = typeof startVoiceSearchFlow === 'function' ? startVoiceSearchFlow : null;
    startVoiceSearchFlow = function() {
        openInlineVoiceSearch();
    };

    const __baseStopInlineVoiceSearch = typeof stopInlineVoiceSearch === 'function' ? stopInlineVoiceSearch : null;
    stopInlineVoiceSearch = function() {
        aiVoiceSearchInProgress = false;

        if (typeof stopVoiceCommand === 'function') {
            stopVoiceCommand();
        }

        keepVoicePanelOpenAfterStop(aiVoiceFinalTranscript);
    };

    /*
       Wrap speech-recognition init so onresult/onend always leaves the panel open
       and displays the transcript.
    */
    const __baseInitVoiceRecognition = typeof initVoiceRecognition === 'function' ? initVoiceRecognition : null;
    if (__baseInitVoiceRecognition) {
        initVoiceRecognition = function() {
            __baseInitVoiceRecognition();

            if (!speechRecognition) return;

            const originalOnResult = speechRecognition.onresult;
            const originalOnEnd = speechRecognition.onend;
            const originalOnError = speechRecognition.onerror;

            speechRecognition.onresult = function(event) {
                let transcript = '';

                try {
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        transcript += event.results[i][0].transcript;
                    }
                } catch (e) {}

                transcript = String(transcript || '').trim();

                if (transcript) {
                    aiVoiceFinalTranscript = transcript;
                    showAiVoiceResultUi(transcript);
                }

                if (typeof originalOnResult === 'function') {
                    originalOnResult.call(this, event);
                }

                const input = document.getElementById('destination-search-input');
                if (input && aiVoiceFinalTranscript) {
                    input.value = aiVoiceFinalTranscript;
                }

                keepVoicePanelOpenAfterStop(aiVoiceFinalTranscript);
            };

            speechRecognition.onend = function(event) {
                if (typeof originalOnEnd === 'function') {
                    originalOnEnd.call(this, event);
                }

                if (!aiVoiceAllowAutoClose) {
                    isVoiceListening = false;
                    updateVoiceButtonUi();
                    keepVoicePanelOpenAfterStop(aiVoiceFinalTranscript);
                }
            };

            speechRecognition.onerror = function(event) {
                if (typeof originalOnError === 'function') {
                    originalOnError.call(this, event);
                }

                if (!aiVoiceAllowAutoClose) {
                    isVoiceListening = false;
                    updateVoiceButtonUi();
                    keepVoicePanelOpenAfterStop(aiVoiceFinalTranscript);
                }
            };
        };
    }

    /*
       If the original voice logic routes automatically after voice result,
       keep panel visible instead of closing.
    */
    const __baseSearchTextDestination = typeof searchTextDestination === 'function' ? searchTextDestination : null;
    if (__baseSearchTextDestination) {
        searchTextDestination = async function() {
            await __baseSearchTextDestination();

            const voicePanel = getAiVoicePanel();
            if (voicePanel && voicePanel.style.display === 'block') {
                keepVoicePanelOpenAfterStop(aiVoiceFinalTranscript || (document.getElementById(
                    'destination-search-input')?.value || ''));
            }
        };
    }

    window.openInlineVoiceSearch = openInlineVoiceSearch;
    window.startVoiceSearchFlow = startVoiceSearchFlow;
    window.stopInlineVoiceSearch = stopInlineVoiceSearch;
    window.closeAiTransformPanel = closeAiTransformPanel;
    window.searchTextDestination = searchTextDestination;


    initVoiceRecognition();
    updateVoiceButtonUi();
    setVoiceStatus(voiceSupported ? 'Idle' : 'Not supported in this browser');
    setHeardText('');

    window.enablePathStartPlacement = enablePathStartPlacement;
    window.useCurrentLocationAsStart = useCurrentLocationAsStart;
    window.useDefaultEntryPointAsStart = useDefaultEntryPointAsStart;
    window.findRouteByDestination = findRouteByDestination;
    window.resetRouteSelection = resetRouteSelection;
    window.openIndoorPanelForBuilding = openIndoorPanelForBuilding;
    window.closeIndoorPanelFn = closeIndoorPanelFn;
    window.searchTextDestination = searchTextDestination;
    window.toggleVoiceCommand = toggleVoiceCommand;
    window.startVoiceCommand = startVoiceCommand;
    window.stopVoiceCommand = stopVoiceCommand;

    window.toggleDestinationMenu = toggleDestinationMenu;

    window.toggleFloatingActionCard = toggleFloatingActionCard;
    window.closeFloatingActionCard = closeFloatingActionCard;
    window.openTextSearchModal = openTextSearchModal;
    window.closeTextSearchModal = closeTextSearchModal;
    window.openBrowseOptionsModal = openBrowseOptionsModal;
    window.closeBrowseOptionsModal = closeBrowseOptionsModal;
    window.startVoiceSearchFlow = startVoiceSearchFlow;

    window.selectPickPathMode = selectPickPathMode;
    window.selectGpsMode = selectGpsMode;
    window.selectDefaultMode = selectDefaultMode;

    loadAllData().catch(err => {
        console.error(err);
        alert('Failed to load map data.');
        setIndoorLoading(false);
    });

    window.selectLanduseDestination = function(landuseId) {
        const landuse = (landuseRecords || []).find(item => Number(item.id) === Number(landuseId));

        if (landuse && isDesignLanduse(landuse)) {
            selectedDestinationLanduseId = null;
            updateRouteLabels();
            setRouteResultLabel('Design landuse is display-only and not available for routing.');
            return;
        }

        destinationTypeSelect.value = 'landuse';
        updateDestinationUi();

        if (destinationLanduseSelect) {
            destinationLanduseSelect.value = String(landuseId);
        }

        selectedDestinationLanduseId = Number(landuseId);
        selectedDestinationBuildingId = null;
        selectedIndoorRoomFeature = null;
        selectedBuildingEntranceId = null;

        updateRouteLabels();
        setRouteResultLabel('Landuse selected. Click Find Route.');
    };

    /* =========================================================
       AI ORB TRANSFORM BEHAVIOR
       Search and Voice transform the main button area.
    ========================================================= */

    function hideAiPanels() {
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const dock = document.getElementById('floating-route-ui');

        if (searchPanel) searchPanel.style.display = 'none';
        if (voicePanel) voicePanel.style.display = 'none';
        if (dock) dock.classList.remove('transforming', 'search-mode', 'voice-mode');
    }

    function showAiSearchPanel() {
        const searchPanel = document.getElementById('ai-search-panel');
        const dock = document.getElementById('floating-route-ui');

        closeFloatingActionCard();
        hideAiPanels();

        if (dock) dock.classList.add('transforming', 'search-mode');
        if (searchPanel) searchPanel.style.display = 'block';

        setTimeout(() => {
            const input = document.getElementById('destination-search-input');
            if (input) input.focus();
        }, 80);
    }

    function showAiVoicePanel() {
        const voicePanel = document.getElementById('ai-voice-panel');
        const dock = document.getElementById('floating-route-ui');

        closeFloatingActionCard();
        hideAiPanels();

        if (dock) dock.classList.add('transforming', 'voice-mode');
        if (voicePanel) voicePanel.style.display = 'block';
    }

    function closeAiTransformPanel() {
        if (typeof stopVoiceCommand === 'function' && isVoiceListening) {
            stopVoiceCommand();
        }

        hideAiPanels();
        setRouteResultLabel('Ready');
    }

    function openInlineTextSearch() {
        showAiSearchPanel();
    }

    function openInlineVoiceSearch() {
        showAiVoicePanel();

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

    function stopInlineVoiceSearch() {
        if (typeof stopVoiceCommand === 'function') {
            stopVoiceCommand();
        }

        closeAiTransformPanel();
    }

    const __originalOpenTextSearchModal = typeof openTextSearchModal === 'function' ? openTextSearchModal : null;
    openTextSearchModal = function() {
        openInlineTextSearch();
    };

    const __originalCloseTextSearchModal = typeof closeTextSearchModal === 'function' ? closeTextSearchModal : null;
    closeTextSearchModal = function() {
        closeAiTransformPanel();
    };

    const __originalStartVoiceSearchFlow = typeof startVoiceSearchFlow === 'function' ? startVoiceSearchFlow : null;
    startVoiceSearchFlow = function() {
        openInlineVoiceSearch();
    };

    const __originalCloseBrowseOptionsModal = typeof closeBrowseOptionsModal === 'function' ? closeBrowseOptionsModal :
        null;
    closeBrowseOptionsModal = function() {
        const modal = document.getElementById('browseOptionsModal');
        if (modal) modal.style.display = 'none';
        hideAiPanels();
    };

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeAiTransformPanel();
        }
    });

    document.addEventListener('click', function(event) {
        const dock = document.getElementById('floating-route-ui');
        const browseModal = document.getElementById('browseOptionsModal');
        const clickedDock = dock && dock.contains(event.target);
        const clickedBrowse = browseModal && browseModal.contains(event.target);

        if (!clickedDock && !clickedBrowse) {
            const searchPanel = document.getElementById('ai-search-panel');
            const voicePanel = document.getElementById('ai-voice-panel');
            const hasTransformOpen =
                (searchPanel && searchPanel.style.display === 'block') ||
                (voicePanel && voicePanel.style.display === 'block');

            if (hasTransformOpen) {
                closeAiTransformPanel();
            }
        }
    });

    window.openInlineTextSearch = openInlineTextSearch;
    window.openInlineVoiceSearch = openInlineVoiceSearch;
    window.stopInlineVoiceSearch = stopInlineVoiceSearch;
    window.closeAiTransformPanel = closeAiTransformPanel;
    window.openTextSearchModal = openTextSearchModal;
    window.closeTextSearchModal = closeTextSearchModal;
    window.startVoiceSearchFlow = startVoiceSearchFlow;
    window.closeBrowseOptionsModal = closeBrowseOptionsModal;


    /* =========================================================
       FINAL NO AUTO-CLOSE PATCH
       - Voice panel stays open after result.
       - Text search panel stays open after result.
       - Only the user close button / ESC closes transform panel.
    ========================================================= */

    let aiLastTextSearchValue = '';
    let aiLastTextSearchResult = '';
    let aiLastVoiceTranscript = '';

    function getAiSearchPanelFinal() {
        return document.getElementById('ai-search-panel');
    }

    function getAiTextResultCardFinal() {
        return document.getElementById('ai-text-result-card');
    }

    function getAiTextResultTextFinal() {
        return document.getElementById('ai-text-result-text');
    }

    function showAiTextResultFinal(message) {
        const panel = getAiSearchPanelFinal();
        const card = getAiTextResultCardFinal();
        const text = getAiTextResultTextFinal();

        if (panel) {
            panel.style.display = 'block';
            panel.classList.add('search-finished');
        }

        const dock = document.getElementById('floating-route-ui');
        if (dock) {
            dock.classList.add('transforming', 'search-mode');
            dock.classList.remove('voice-mode');
        }

        if (card) card.style.display = 'block';
        if (text) text.textContent = message || 'Search completed.';

        if (typeof setRouteResultLabel === 'function') {
            setRouteResultLabel(message || 'Search completed. Review the result.');
        }
    }

    function showAiVoiceResultFinal(transcript) {
        const panel = document.getElementById('ai-voice-panel');
        const card = document.getElementById('ai-voice-result-card');
        const text = document.getElementById('ai-voice-result-text');
        const dock = document.getElementById('floating-route-ui');

        if (dock) {
            dock.classList.add('transforming', 'voice-mode');
            dock.classList.remove('search-mode');
        }

        if (panel) {
            panel.style.display = 'block';
            panel.classList.add('voice-finished');
        }

        if (card) card.style.display = 'block';

        const clean = String(transcript || aiLastVoiceTranscript || '').trim();
        aiLastVoiceTranscript = clean;

        if (text) {
            text.textContent = clean || 'No clear speech detected. You can try Voice Search again.';
        }

        if (typeof setHeardText === 'function') {
            setHeardText(clean || 'No clear speech detected.');
        }

        if (typeof setVoiceStatus === 'function') {
            setVoiceStatus('Stopped. Result is displayed below.');
        }

        if (typeof setRouteResultLabel === 'function') {
            setRouteResultLabel(clean ? 'Voice captured. Review the detected text.' :
                'Voice stopped. No clear speech detected.');
        }
    }

    /* Only manual close should hide the active transformed panel. */
    closeAiTransformPanel = function() {
        aiVoiceAllowAutoClose = true;
        aiVoiceSearchInProgress = false;

        if (typeof stopVoiceCommand === 'function' && typeof isVoiceListening !== 'undefined' && isVoiceListening) {
            stopVoiceCommand();
        }

        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const dock = document.getElementById('floating-route-ui');

        if (searchPanel) {
            searchPanel.style.display = 'none';
            searchPanel.classList.remove('search-finished');
        }

        if (voicePanel) {
            voicePanel.style.display = 'none';
            voicePanel.classList.remove('voice-finished');
        }

        if (dock) dock.classList.remove('transforming', 'search-mode', 'voice-mode');

        const textCard = document.getElementById('ai-text-result-card');
        const voiceCard = document.getElementById('ai-voice-result-card');

        if (textCard) textCard.style.display = 'none';
        if (voiceCard) voiceCard.style.display = 'none';

        aiLastTextSearchValue = '';
        aiLastTextSearchResult = '';
        aiLastVoiceTranscript = '';

        if (typeof setHeardText === 'function') setHeardText('');
        if (typeof setRouteResultLabel === 'function') setRouteResultLabel('Ready');
    };

    openInlineTextSearch = function() {
        closeFloatingActionCard();

        const dock = document.getElementById('floating-route-ui');
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');

        if (voicePanel) voicePanel.style.display = 'none';

        if (dock) {
            dock.classList.add('transforming', 'search-mode');
            dock.classList.remove('voice-mode');
        }

        if (searchPanel) {
            searchPanel.style.display = 'block';
            searchPanel.classList.remove('search-finished');
        }

        const card = document.getElementById('ai-text-result-card');
        if (card) card.style.display = 'none';

        setTimeout(() => {
            const input = document.getElementById('destination-search-input');
            if (input) input.focus();
        }, 80);
    };

    openInlineVoiceSearch = function() {
        closeFloatingActionCard();

        const dock = document.getElementById('floating-route-ui');
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');

        if (searchPanel) searchPanel.style.display = 'none';

        if (dock) {
            dock.classList.add('transforming', 'voice-mode');
            dock.classList.remove('search-mode');
        }

        if (voicePanel) {
            voicePanel.style.display = 'block';
            voicePanel.classList.remove('voice-finished');
        }

        const card = document.getElementById('ai-voice-result-card');
        const text = document.getElementById('ai-voice-result-text');
        if (card) card.style.display = 'none';
        if (text) text.textContent = '-';

        aiVoiceAllowAutoClose = false;
        aiVoiceSearchInProgress = true;
        aiLastVoiceTranscript = '';

        if (typeof ensureDefaultStartBeforeRoute === 'function') {
            if (!ensureDefaultStartBeforeRoute()) return;
        }

        if (typeof toggleVoiceCommand === 'function') {
            toggleVoiceCommand();
        } else {
            console.error('toggleVoiceCommand() not found.');
            alert('Voice command function not found.');
        }
    };

    startVoiceSearchFlow = function() {
        openInlineVoiceSearch();
    };

    stopInlineVoiceSearch = function() {
        aiVoiceSearchInProgress = false;

        if (typeof stopVoiceCommand === 'function') {
            stopVoiceCommand();
        }

        showAiVoiceResultFinal(aiLastVoiceTranscript);
    };

    /*
       Text search override:
       This intentionally does NOT close the search panel after route/search completes.
    */
    const __noCloseSearchTextDestinationBase = searchTextDestination;
    searchTextDestination = async function() {
        const input = document.getElementById('destination-search-input');
        aiLastTextSearchValue = String(input?.value || '').trim();

        await __noCloseSearchTextDestinationBase();

        const routeText = document.getElementById('route-result-label')?.textContent || '';
        const message = routeText && routeText !== 'Ready' ?
            routeText :
            (aiLastTextSearchValue ? `Search completed for: ${aiLastTextSearchValue}` : 'Search completed.');

        aiLastTextSearchResult = message;
        showAiTextResultFinal(message);
    };

    /*
       Re-wrap speech recognition after all previous patches.
       onend/result must NOT close the panel.
    */
    const __noCloseInitVoiceRecognitionBase = initVoiceRecognition;
    initVoiceRecognition = function() {
        __noCloseInitVoiceRecognitionBase();

        if (!speechRecognition) return;

        const previousResult = speechRecognition.onresult;
        const previousEnd = speechRecognition.onend;
        const previousError = speechRecognition.onerror;

        speechRecognition.onresult = function(event) {
            let transcript = '';

            try {
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    transcript += event.results[i][0].transcript;
                }
            } catch (e) {}

            transcript = String(transcript || '').trim();

            if (transcript) {
                aiLastVoiceTranscript = transcript;

                const input = document.getElementById('destination-search-input');
                if (input) input.value = transcript;
            }

            if (typeof previousResult === 'function') {
                previousResult.call(this, event);
            }

            showAiVoiceResultFinal(aiLastVoiceTranscript || transcript);
        };

        speechRecognition.onend = function(event) {
            if (typeof previousEnd === 'function') {
                previousEnd.call(this, event);
            }

            isVoiceListening = false;
            if (typeof updateVoiceButtonUi === 'function') updateVoiceButtonUi();

            if (!aiVoiceAllowAutoClose) {
                showAiVoiceResultFinal(aiLastVoiceTranscript);
            }
        };

        speechRecognition.onerror = function(event) {
            if (typeof previousError === 'function') {
                previousError.call(this, event);
            }

            isVoiceListening = false;
            if (typeof updateVoiceButtonUi === 'function') updateVoiceButtonUi();

            if (!aiVoiceAllowAutoClose) {
                showAiVoiceResultFinal(aiLastVoiceTranscript);
            }
        };
    };

    /*
       If voice was already initialized before this final patch,
       apply patched handlers immediately.
    */
    if (speechRecognition) {
        initVoiceRecognition();
    }

    window.openInlineTextSearch = openInlineTextSearch;
    window.openInlineVoiceSearch = openInlineVoiceSearch;
    window.startVoiceSearchFlow = startVoiceSearchFlow;
    window.stopInlineVoiceSearch = stopInlineVoiceSearch;
    window.closeAiTransformPanel = closeAiTransformPanel;
    window.searchTextDestination = searchTextDestination;


    /* =========================================================
       RECORD AGAIN + NO AUTOCLOSE FINAL PATCH
       - Record button beside Search transforms to voice recorder.
       - Record Again restarts speech recognition without closing panel.
       - Text and Voice result panels stay open until manual close.
    ========================================================= */

    function resetVoiceUiForNewRecordingFinal() {
        const voicePanel = document.getElementById('ai-voice-panel');
        const resultCard = document.getElementById('ai-voice-result-card');
        const resultText = document.getElementById('ai-voice-result-text');

        if (voicePanel) voicePanel.classList.remove('voice-finished');
        if (resultCard) resultCard.style.display = 'none';
        if (resultText) resultText.textContent = '-';

        if (typeof setHeardText === 'function') setHeardText('');
        if (typeof setVoiceStatus === 'function') setVoiceStatus('Listening...');
        if (typeof setRouteResultLabel === 'function') setRouteResultLabel('Listening for your destination...');
    }

    function keepVoicePanelOpenFinal(transcript = '') {
        const dock = document.getElementById('floating-route-ui');
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const resultCard = document.getElementById('ai-voice-result-card');
        const resultText = document.getElementById('ai-voice-result-text');

        if (dock) {
            dock.classList.add('transforming', 'voice-mode');
            dock.classList.remove('search-mode');
        }

        if (searchPanel) searchPanel.style.display = 'none';

        if (voicePanel) {
            voicePanel.style.display = 'block';
            voicePanel.classList.add('voice-finished');
        }

        const clean = String(transcript || window.aiLastVoiceTranscript || '').trim();
        window.aiLastVoiceTranscript = clean;

        if (resultCard) resultCard.style.display = 'block';
        if (resultText) {
            resultText.textContent = clean || 'No clear speech detected. Click Record Again to try again.';
        }

        if (typeof setHeardText === 'function') {
            setHeardText(clean || 'No clear speech detected.');
        }

        if (typeof setVoiceStatus === 'function') {
            setVoiceStatus('Stopped. Result is displayed below.');
        }

        if (typeof setRouteResultLabel === 'function') {
            setRouteResultLabel(clean ? 'Voice captured. You can record again or close this panel.' :
                'Voice stopped. You can record again.');
        }
    }

    function keepTextPanelOpenFinal(message = '') {
        const dock = document.getElementById('floating-route-ui');
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const resultCard = document.getElementById('ai-text-result-card');
        const resultText = document.getElementById('ai-text-result-text');

        if (dock) {
            dock.classList.add('transforming', 'search-mode');
            dock.classList.remove('voice-mode');
        }

        if (voicePanel) voicePanel.style.display = 'none';

        if (searchPanel) {
            searchPanel.style.display = 'block';
            searchPanel.classList.add('search-finished');
        }

        if (resultCard) resultCard.style.display = 'block';
        if (resultText) resultText.textContent = message || 'Search completed.';

        if (typeof setRouteResultLabel === 'function') {
            setRouteResultLabel(message || 'Search completed. You can record again or close this panel.');
        }
    }

    closeAiTransformPanel = function() {
        window.aiVoiceAllowAutoClose = true;
        window.aiVoiceSearchInProgress = false;

        if (typeof stopVoiceCommand === 'function' && typeof isVoiceListening !== 'undefined' && isVoiceListening) {
            stopVoiceCommand();
        }

        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const dock = document.getElementById('floating-route-ui');

        if (searchPanel) {
            searchPanel.style.display = 'none';
            searchPanel.classList.remove('search-finished');
        }

        if (voicePanel) {
            voicePanel.style.display = 'none';
            voicePanel.classList.remove('voice-finished');
        }

        const textCard = document.getElementById('ai-text-result-card');
        const voiceCard = document.getElementById('ai-voice-result-card');

        if (textCard) textCard.style.display = 'none';
        if (voiceCard) voiceCard.style.display = 'none';

        if (dock) dock.classList.remove('transforming', 'search-mode', 'voice-mode');

        window.aiLastVoiceTranscript = '';
        window.aiLastTextSearchValue = '';
        window.aiLastTextSearchResult = '';

        if (typeof setHeardText === 'function') setHeardText('');
        if (typeof setRouteResultLabel === 'function') setRouteResultLabel('Ready');
    };

    openInlineTextSearch = function() {
        if (typeof closeFloatingActionCard === 'function') closeFloatingActionCard();

        const dock = document.getElementById('floating-route-ui');
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const textCard = document.getElementById('ai-text-result-card');

        if (voicePanel) voicePanel.style.display = 'none';

        if (dock) {
            dock.classList.add('transforming', 'search-mode');
            dock.classList.remove('voice-mode');
        }

        if (searchPanel) {
            searchPanel.style.display = 'block';
            searchPanel.classList.remove('search-finished');
        }

        if (textCard) textCard.style.display = 'none';

        setTimeout(() => {
            const input = document.getElementById('destination-search-input');
            if (input) input.focus();
        }, 80);
    };

    openInlineVoiceSearch = function() {
        if (typeof closeFloatingActionCard === 'function') closeFloatingActionCard();

        const dock = document.getElementById('floating-route-ui');
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');

        if (searchPanel) searchPanel.style.display = 'none';

        if (dock) {
            dock.classList.add('transforming', 'voice-mode');
            dock.classList.remove('search-mode');
        }

        if (voicePanel) {
            voicePanel.style.display = 'block';
            voicePanel.classList.remove('voice-finished');
        }

        window.aiVoiceAllowAutoClose = false;
        window.aiVoiceSearchInProgress = true;
        window.aiLastVoiceTranscript = '';

        resetVoiceUiForNewRecordingFinal();

        if (typeof ensureDefaultStartBeforeRoute === 'function') {
            if (!ensureDefaultStartBeforeRoute()) return;
        }

        if (typeof toggleVoiceCommand === 'function') {
            toggleVoiceCommand();
        } else {
            console.error('toggleVoiceCommand() not found.');
            alert('Voice command function not found.');
        }
    };

    restartInlineVoiceSearch = function() {
        if (typeof stopVoiceCommand === 'function' && typeof isVoiceListening !== 'undefined' && isVoiceListening) {
            stopVoiceCommand();
        }

        setTimeout(() => {
            openInlineVoiceSearch();
        }, 180);
    };

    startVoiceSearchFlow = function() {
        openInlineVoiceSearch();
    };

    stopInlineVoiceSearch = function() {
        window.aiVoiceSearchInProgress = false;

        if (typeof stopVoiceCommand === 'function') {
            stopVoiceCommand();
        }

        keepVoicePanelOpenFinal(window.aiLastVoiceTranscript || '');
    };

    /* Override text search so it remains open and shows Record button/result */
    if (!window.__recordButtonSearchWrapped) {
        window.__recordButtonSearchWrapped = true;
        const __recordButtonBaseSearchTextDestination = searchTextDestination;

        searchTextDestination = async function() {
            const input = document.getElementById('destination-search-input');
            window.aiLastTextSearchValue = String(input?.value || '').trim();

            await __recordButtonBaseSearchTextDestination();

            const routeText = document.getElementById('route-result-label')?.textContent || '';
            const message = routeText && routeText !== 'Ready' ?
                routeText :
                (window.aiLastTextSearchValue ? `Search completed for: ${window.aiLastTextSearchValue}` :
                    'Search completed.');

            window.aiLastTextSearchResult = message;
            keepTextPanelOpenFinal(message);
        };
    }

    /* Re-wrap speech recognition to keep result visible and allow Record Again */
    if (typeof initVoiceRecognition === 'function' && !window.__recordButtonVoiceInitWrapped) {
        window.__recordButtonVoiceInitWrapped = true;
        const __recordButtonBaseInitVoiceRecognition = initVoiceRecognition;

        initVoiceRecognition = function() {
            __recordButtonBaseInitVoiceRecognition();

            if (!speechRecognition) return;

            const previousResult = speechRecognition.onresult;
            const previousEnd = speechRecognition.onend;
            const previousError = speechRecognition.onerror;

            speechRecognition.onresult = function(event) {
                let transcript = '';

                try {
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        transcript += event.results[i][0].transcript;
                    }
                } catch (e) {}

                transcript = String(transcript || '').trim();

                if (transcript) {
                    window.aiLastVoiceTranscript = transcript;

                    const input = document.getElementById('destination-search-input');
                    if (input) input.value = transcript;
                }

                if (typeof previousResult === 'function') {
                    previousResult.call(this, event);
                }

                keepVoicePanelOpenFinal(window.aiLastVoiceTranscript || transcript);
            };

            speechRecognition.onend = function(event) {
                if (typeof previousEnd === 'function') {
                    previousEnd.call(this, event);
                }

                isVoiceListening = false;
                if (typeof updateVoiceButtonUi === 'function') updateVoiceButtonUi();

                if (!window.aiVoiceAllowAutoClose) {
                    keepVoicePanelOpenFinal(window.aiLastVoiceTranscript || '');
                }
            };

            speechRecognition.onerror = function(event) {
                if (typeof previousError === 'function') {
                    previousError.call(this, event);
                }

                isVoiceListening = false;
                if (typeof updateVoiceButtonUi === 'function') updateVoiceButtonUi();

                if (!window.aiVoiceAllowAutoClose) {
                    keepVoicePanelOpenFinal(window.aiLastVoiceTranscript || '');
                }
            };
        };

        if (speechRecognition) {
            initVoiceRecognition();
        }
    }

    window.openInlineTextSearch = openInlineTextSearch;
    window.openInlineVoiceSearch = openInlineVoiceSearch;
    window.restartInlineVoiceSearch = restartInlineVoiceSearch;
    window.startVoiceSearchFlow = startVoiceSearchFlow;
    window.stopInlineVoiceSearch = stopInlineVoiceSearch;
    window.closeAiTransformPanel = closeAiTransformPanel;
    window.searchTextDestination = searchTextDestination;


    /* =========================================================
       ROUTE BUILDING POPUP PATCH
       - Shows popup after route to building / route to room building.
       - Clicking popup button opens indoor rooms.
       - Clicking the building also opens indoor rooms.
    ========================================================= */

    let routePopupBuildingId = null;
    let routePopupBuildingName = '';
    let routePopupLatLng = null;
    let routeBuildingLeafletPopup = null;

    function getBuildingRecordByIdFinal(buildingId) {
        return (buildingRecords || []).find(b => Number(b.id) === Number(buildingId)) || null;
    }

    function getBuildingFeatureCenterFinal(buildingRecord) {
        if (!buildingRecord || !buildingRecord.geometry) return null;

        try {
            const layer = L.geoJSON({
                type: 'Feature',
                geometry: buildingRecord.geometry,
                properties: buildingRecord.properties || {}
            });

            const bounds = layer.getBounds();
            if (!bounds || !bounds.isValid()) return null;

            return bounds.getCenter();
        } catch (e) {
            return null;
        }
    }

    function hasIndoorForBuildingFinal(buildingId) {
        return getIndoorBuildingMaps(buildingId).length > 0;
    }

    function positionRouteBuildingPopupFinal(latlng) {
        // Old fixed-screen popup positioning disabled.
        // The route popup is now handled by Leaflet and anchored to the building.
        return;
    }


    function escapePopupHtmlFinal(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }



    function makeRoutePopupDragFriendly() {
        const isMobile = window.matchMedia('(max-width: 768px)').matches;
        if (!isMobile) return;

        const popup = document.querySelector('.route-building-map-popup');
        if (!popup) return;

        const passThroughSelectors = [
            '.leaflet-popup-content-wrapper',
            '.leaflet-popup-content',
            '.route-building-map-popup-inner',
            '.route-building-map-popup-kicker',
            '.route-building-map-popup-head',
            '.route-building-map-popup-title-wrap',
            '.route-building-map-popup-title',
            '.route-building-map-popup-subtitle',
            '.route-building-map-popup-divider',
            '.route-building-map-popup-hint',
            '.route-building-map-popup-hint-icon',
            '.leaflet-popup-tip'
        ];

        popup.style.pointerEvents = 'none';
        popup.style.touchAction = 'pan-x pan-y';

        passThroughSelectors.forEach(selector => {
            popup.querySelectorAll(selector).forEach(el => {
                el.style.pointerEvents = 'none';
                el.style.touchAction = 'pan-x pan-y';
                el.style.userSelect = 'none';
                el.style.webkitUserSelect = 'none';
            });
        });

        const btn = popup.querySelector('.route-building-map-popup-btn');
        if (btn) {
            btn.style.pointerEvents = 'auto';
            btn.style.touchAction = 'manipulation';
            btn.querySelectorAll('*').forEach(child => {
                child.style.pointerEvents = 'none';
            });

            ['touchstart', 'pointerdown', 'mousedown', 'click'].forEach(eventName => {
                btn.addEventListener(eventName, function(e) {
                    e.stopPropagation();
                }, { passive: false });
            });
        }

        const closeBtn = popup.querySelector('.leaflet-popup-close-button');
        if (closeBtn) {
            closeBtn.style.pointerEvents = 'auto';
            closeBtn.style.touchAction = 'manipulation';

            ['touchstart', 'pointerdown', 'mousedown', 'click'].forEach(eventName => {
                closeBtn.addEventListener(eventName, function(e) {
                    e.stopPropagation();
                }, { passive: false });
            });
        }
    }

function showRouteBuildingPopup(buildingId, buildingName, center) {
        routePopupBuildingId = Number(buildingId);
        routePopupBuildingName = buildingName || getBuildingNameById(buildingId);
        routePopupLatLng = center;

        if (!center) return;

        const safeName = escapePopupHtmlFinal(routePopupBuildingName);
        const isMobileIndoorPopup = window.matchMedia('(max-width: 768px)').matches;
        const oldFloatingPopup = document.getElementById('route-building-popup');

        // Dili na gamiton ang bottom sheet. Ang popup dapat pirmi mo-display sa babaw sa building.
        if (oldFloatingPopup) {
            oldFloatingPopup.classList.remove('mobile-active');
            oldFloatingPopup.style.display = 'none';
        }

        const html = `
            <div class="route-building-map-popup-inner">
                <button type="button"
                    class="route-building-map-popup-custom-close"
                    aria-label="Close indoor popup"
                    onclick="closeRouteBuildingPopup()">
                    ×
                </button>
                <div class="route-building-map-popup-kicker">
                    <span class="route-building-map-popup-pulse-dot"></span>
                    Indoor available
                </div>

                <div class="route-building-map-popup-head">
                    <span class="route-building-map-popup-icon">🏢</span>

                    <span class="route-building-map-popup-title-wrap">
                        <span class="route-building-map-popup-title">${safeName}</span>
                        <span class="route-building-map-popup-subtitle">Tap below to view rooms</span>
                    </span>
                </div>

                <div class="route-building-map-popup-divider"></div>

                <button type="button"
                    class="route-building-map-popup-btn"
                    aria-label="Open indoor rooms for ${safeName}"
                    onclick="openIndoorFromRoutePopup()">

                    <span class="route-building-map-popup-btn-main">
                        <span class="route-building-map-popup-btn-icon">🚪</span>

                        <span class="route-building-map-popup-btn-text">
                            <strong>OPEN INDOOR ROOMS</strong>
                            <small>View rooms and indoor route</small>
                        </span>
                    </span>
                </button>

                <div class="route-building-map-popup-hint">
                    <span class="route-building-map-popup-hint-icon">👆</span>
                    Tap to open the indoor map.
                </div>
            </div>
        `;

        if (routeBuildingLeafletPopup) {
            map.closePopup(routeBuildingLeafletPopup);
            routeBuildingLeafletPopup = null;
        }

        routeBuildingLeafletPopup = L.popup({
            closeButton: false,
            autoClose: true,
            closeOnClick: false,
            autoPan: true,
            keepInView: false,
            className: 'route-building-map-popup',
            offset: isMobileIndoorPopup ? L.point(0, -14) : L.point(0, -28),

            /*
            | Base size ra ni. Ang visual size niya i-scale nato
            | depende sa current zoom para dili niya matabunan ang route.
            */
            maxWidth: isMobileIndoorPopup ? 230 : 285,
            minWidth: isMobileIndoorPopup ? 230 : 285,

            autoPanPaddingTopLeft: isMobileIndoorPopup ? L.point(12, 70) : L.point(20, 20),
            autoPanPaddingBottomRight: isMobileIndoorPopup ? L.point(12, 92) : L.point(20, 20)
        })
        .setLatLng(center)
        .setContent(html)
        .openOn(map);

        updateRouteBuildingPopupScale();

        makeRoutePopupDragFriendly();
        setTimeout(makeRoutePopupDragFriendly, 80);
        setTimeout(makeRoutePopupDragFriendly, 220);

        // Mobile: center gamay ang building/popup aron dili siya maputol sa kilid.
        if (isMobileIndoorPopup) {
            setTimeout(() => {
                try {
                    const targetPoint = map.latLngToContainerPoint(center);
                    const mapSize = map.getSize();
                    const safeLeft = 132;
                    const safeRight = mapSize.x - 132;
                    let dx = 0;

                    if (targetPoint.x < safeLeft) dx = targetPoint.x - safeLeft;
                    if (targetPoint.x > safeRight) dx = targetPoint.x - safeRight;

                    if (dx !== 0) {
                        map.panBy([dx, 0], { animate: true, duration: 0.25 });
                    }
                } catch (e) {}
            }, 80);
        }

        setTimeout(makeRoutePopupDragFriendly, 160);
        setTimeout(updateRouteBuildingPopupScale, 40);
        setTimeout(updateRouteBuildingPopupScale, 180);
    }


    /* =========================================================
       ROUTE BUILDING POPUP ZOOM-SCALE FIX
       - Mo gamay ang OPEN INDOOR ROOMS popup kung zoom out.
       - Dili na kaayo motabon sa route.
       - Button ug close button clickable gihapon sa mobile.
    ========================================================= */

    function getRouteBuildingPopupScaleByZoom() {
        if (!map) return 1;

        const zoom = Number(map.getZoom() || 18);
        const isMobile = window.matchMedia('(max-width: 768px)').matches;

        /*
        | Adjust diri kung gusto nimo usbon:
        | Lower return value = mas gamay ang popup.
        | Higher return value = mas dako ang popup.
        */
        if (isMobile) {
            if (zoom <= 17) return 0.58;
            if (zoom <= 17.25) return 0.62;
            if (zoom <= 17.5) return 0.68;
            if (zoom <= 17.75) return 0.74;
            if (zoom <= 18) return 0.82;
            if (zoom <= 18.5) return 0.92;
            return 1;
        }

        if (zoom <= 17) return 0.72;
        if (zoom <= 17.5) return 0.78;
        if (zoom <= 18) return 0.86;
        if (zoom <= 18.5) return 0.94;
        return 1;
    }

    function updateRouteBuildingPopupScale() {
        const popupEl = document.querySelector('.leaflet-popup-pane .route-building-map-popup');
        if (!popupEl) return;

        const scale = getRouteBuildingPopupScaleByZoom();
        popupEl.style.setProperty('--route-popup-scale', scale);

        if (scale <= 0.7) {
            popupEl.classList.add('zoom-out-small');
        } else {
            popupEl.classList.remove('zoom-out-small');
        }
    }

    function closeRouteBuildingPopup() {
        const popup = document.getElementById('route-building-popup');
        if (popup) {
            popup.style.display = 'none';
            popup.classList.remove('mobile-active');
        }

        if (routeBuildingLeafletPopup) {
            map.closePopup(routeBuildingLeafletPopup);
            routeBuildingLeafletPopup = null;
        }

        routePopupBuildingId = null;
        routePopupBuildingName = '';
        routePopupLatLng = null;
    }

    function openIndoorFromRoutePopup() {
        if (!routePopupBuildingId) return;

        // No alert. Buildings without indoor maps should do nothing.
        if (!hasIndoorForBuildingFinal(routePopupBuildingId)) {
            return;
        }

        if (typeof openIndoorPanelForBuilding === 'function') {
            openIndoorPanelForBuilding(routePopupBuildingId);
        }
    }

    function showRoutePopupForSelectedBuilding(buildingId) {
        const building = getBuildingRecordByIdFinal(buildingId);
        showRouteBuildingPopup(
            buildingId,
            building?.name || getBuildingNameById(buildingId),
            getBuildingFeatureCenterFinal(building)
        );
    }

    /*
       Wrap route functions so popup appears after successful route.
    */
    if (typeof findRouteToBuilding === 'function' && !window.__routePopupBuildingWrapped) {
        window.__routePopupBuildingWrapped = true;
        const __baseFindRouteToBuildingPopup = findRouteToBuilding;

        findRouteToBuilding = function(buildingId) {
            const result = __baseFindRouteToBuildingPopup.apply(this, arguments);

            setTimeout(() => {
                showRoutePopupForSelectedBuilding(buildingId);
            }, 280);

            return result;
        };
    }

    if (typeof computeCompleteRouteToRoom === 'function' && !window.__routePopupRoomWrapped) {
        window.__routePopupRoomWrapped = true;
        const __baseComputeCompleteRouteToRoomPopup = computeCompleteRouteToRoom;

        computeCompleteRouteToRoom = function(roomFeature) {
            const result = __baseComputeCompleteRouteToRoomPopup.apply(this, arguments);

            const buildingId = Number(roomFeature?.properties?.building_id || selectedDestinationBuildingId);
            if (buildingId) {
                setTimeout(() => {
                    showRoutePopupForSelectedBuilding(buildingId);
                }, 360);
            }

            return result;
        };
    }

    /*
       Make every building click open indoor rooms and show popup.
       This does not remove existing building click behavior.
    */
    function attachIndoorClickToBuildingLayersFinal() {
        if (!window.__routePopupBuildingClickHooked) {
            window.__routePopupBuildingClickHooked = true;
        }

        map.eachLayer(layer => {
            const feature = layer.feature;
            const props = feature?.properties || {};

            const buildingId =
                props.id ??
                props.building_id ??
                props.properties?.id ??
                null;

            if (!buildingId || !feature?.geometry) return;
            if (layer.__indoorClickAdded) return;

            layer.__indoorClickAdded = true;

            layer.on('click', function() {
                // If this building has no indoor map, do nothing silently.
                if (!hasIndoorForBuildingFinal(buildingId)) {
                    return;
                }

                showRoutePopupForSelectedBuilding(buildingId);

                if (typeof openIndoorPanelForBuilding === 'function') {
                    openIndoorPanelForBuilding(Number(buildingId));
                }
            });
        });
    }

    /*
       Try hooking after data/rendering, and after map layer changes.
    */
    setTimeout(attachIndoorClickToBuildingLayersFinal, 1200);
    setTimeout(attachIndoorClickToBuildingLayersFinal, 2500);

    map.on('layeradd', function() {
        setTimeout(attachIndoorClickToBuildingLayersFinal, 80);
    });

    // Popup is now a Leaflet popup anchored to the building.
    // No manual screen-follow positioning needed.

    window.showRouteBuildingPopup = showRouteBuildingPopup;
    window.closeRouteBuildingPopup = closeRouteBuildingPopup;
    window.openIndoorFromRoutePopup = openIndoorFromRoutePopup;


    /* =========================================================
       INDOOR FRONT MODE PATCH
       Adds body.indoor-open while indoor panel is active.
    ========================================================= */

    function setIndoorFrontModeFinal(isOpen = true) {
        const body = document.body;
        const panel = document.getElementById('indoorPanel');
        const backdrop = document.getElementById('indoorBackdrop');

        if (isOpen) {
            body.classList.add('indoor-open');

            if (panel) {
                panel.classList.add('active');
                panel.style.zIndex = '120010';
            }

            if (backdrop) {
                backdrop.classList.add('active');
                backdrop.style.zIndex = '120000';
            }

            if (typeof closeFloatingActionCard === 'function') closeFloatingActionCard();
            if (typeof closeRouteBuildingPopup === 'function') closeRouteBuildingPopup();

            const browseModal = document.getElementById('browseOptionsModal');
            if (browseModal) browseModal.style.display = 'none';

            const searchPanel = document.getElementById('ai-search-panel');
            const voicePanel = document.getElementById('ai-voice-panel');
            const dock = document.getElementById('floating-route-ui');

            if (searchPanel) searchPanel.style.display = 'none';
            if (voicePanel) voicePanel.style.display = 'none';
            if (dock) dock.classList.remove('transforming', 'search-mode', 'voice-mode');

            setTimeout(() => {
                if (typeof indoorMap !== 'undefined' && indoorMap) {
                    indoorMap.invalidateSize();
                }
            }, 180);
        } else {
            body.classList.remove('indoor-open');

            if (panel) panel.classList.remove('active');
            if (backdrop) backdrop.classList.remove('active');

            setTimeout(() => {
                if (typeof map !== 'undefined' && map) {
                    map.invalidateSize();
                }
            }, 120);
        }
    }

    /*
       Wrap indoor open/close functions so indoor is always front.
    */
    if (typeof openIndoorPanelForBuilding === 'function' && !window.__indoorFrontOpenWrapped) {
        window.__indoorFrontOpenWrapped = true;
        const __baseOpenIndoorPanelForBuildingFront = openIndoorPanelForBuilding;

        openIndoorPanelForBuilding = function() {
            const result = __baseOpenIndoorPanelForBuildingFront.apply(this, arguments);

            setTimeout(() => {
                setIndoorFrontModeFinal(true);
            }, 40);

            return result;
        };
    }

    if (typeof closeIndoorPanelFn === 'function' && !window.__indoorFrontCloseWrapped) {
        window.__indoorFrontCloseWrapped = true;
        const __baseCloseIndoorPanelFront = closeIndoorPanelFn;

        closeIndoorPanelFn = function() {
            const result = __baseCloseIndoorPanelFront.apply(this, arguments);
            setIndoorFrontModeFinal(false);
            return result;
        };
    }

    /*
       Direct button/backdrop safety.
    */
    document.addEventListener('DOMContentLoaded', function() {
        const closeBtn = document.getElementById('closeIndoorPanel');
        const backdrop = document.getElementById('indoorBackdrop');

        if (closeBtn && !closeBtn.__indoorFrontBound) {
            closeBtn.__indoorFrontBound = true;
            closeBtn.addEventListener('click', function() {
                setTimeout(() => setIndoorFrontModeFinal(false), 20);
            });
        }

        if (backdrop && !backdrop.__indoorFrontBound) {
            backdrop.__indoorFrontBound = true;
            backdrop.addEventListener('click', function() {
                setTimeout(() => setIndoorFrontModeFinal(false), 20);
            });
        }
    });

    /*
       Watch class changes in case another function opens/closes indoor panel.
    */
    (function watchIndoorPanelFrontMode() {
        const panel = document.getElementById('indoorPanel');
        if (!panel || panel.__frontModeObserver) return;

        panel.__frontModeObserver = true;

        const observer = new MutationObserver(() => {
            const isActive = panel.classList.contains('active') || panel.style.display === 'block';
            document.body.classList.toggle('indoor-open', isActive);

            if (isActive) {
                panel.style.zIndex = '120010';
                const backdrop = document.getElementById('indoorBackdrop');
                if (backdrop) backdrop.style.zIndex = '120000';
                setTimeout(() => {
                    if (typeof indoorMap !== 'undefined' && indoorMap) indoorMap.invalidateSize();
                }, 150);
            }
        });

        observer.observe(panel, {
            attributes: true,
            attributeFilter: ['class', 'style']
        });
    })();

    window.setIndoorFrontModeFinal = setIndoorFrontModeFinal;
    window.openIndoorPanelForBuilding = openIndoorPanelForBuilding;
    window.closeIndoorPanelFn = closeIndoorPanelFn;


    /* =========================================================
       INDOOR FLOOR BUTTONS + MAP FOCUS PATCH
       Hides room list/search and replaces floor select with large buttons.
    ========================================================= */

    function getIndoorFloorButtonsContainerFinal() {
        return document.getElementById('indoorFloorButtons');
    }

    function renderIndoorFloorButtonsFinal() {
        const container = getIndoorFloorButtonsContainerFinal();
        if (!container || !currentIndoorBuildingId) return;

        const maps = getIndoorBuildingMaps(currentIndoorBuildingId);
        container.innerHTML = '';

        maps.forEach(mapItem => {
            const floor = Number(mapItem.floor_number);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'indoor-floor-btn';
            btn.dataset.floor = String(floor);
            btn.textContent = formatIndoorFloorLabel(floor, mapItem.floor_label);

            if (Number(currentIndoorFloor) === floor) {
                btn.classList.add('active');
            }

            btn.addEventListener('click', function() {
                setIndoorFloorFromButtonFinal(floor);
            });

            container.appendChild(btn);
        });
    }

    function updateIndoorFloorButtonActiveFinal() {
        document.querySelectorAll('.indoor-floor-btn').forEach(btn => {
            btn.classList.toggle('active', Number(btn.dataset.floor) === Number(currentIndoorFloor));
        });
    }

    function setIndoorFloorFromButtonFinal(floor) {
        currentIndoorFloor = Number(floor);

        if (indoorFloorSelect) {
            indoorFloorSelect.value = String(currentIndoorFloor);
        }

        updateIndoorFloorButtonActiveFinal();
        renderIndoorRoomList();
        renderIndoorFloor();

        if (lastIndoorRoutePackage) {
            redrawPersistentIndoorRouteForCurrentFloor();
        }

        setTimeout(() => {
            if (indoorMap) {
                indoorMap.invalidateSize();

                const mapItem = allIndoorMaps.find(m =>
                    Number(m.building_id) === Number(currentIndoorBuildingId) &&
                    Number(m.floor_number) === Number(currentIndoorFloor)
                );

                const bounds = mapItem ? getIndoorMapBoundsFromGeometry(mapItem.geometry) : null;
                if (bounds && bounds.isValid()) {
                    indoorMap.fitBounds(bounds, {
                        padding: [24, 24],
                        animate: true
                    });
                }
            }
        }, 160);
    }

    /*
       Keep the old hidden select in sync if any old code changes it.
    */
    if (indoorFloorSelect && !indoorFloorSelect.__floorButtonSyncBound) {
        indoorFloorSelect.__floorButtonSyncBound = true;
        indoorFloorSelect.addEventListener('change', function() {
            setTimeout(updateIndoorFloorButtonActiveFinal, 30);
        });
    }

    /*
       Wrap indoor opening so floor buttons are created every time.
    */
    if (typeof openIndoorPanelForBuilding === 'function' && !window.__floorButtonsOpenWrapped) {
        window.__floorButtonsOpenWrapped = true;
        const __baseOpenIndoorPanelForBuildingFloorButtons = openIndoorPanelForBuilding;

        openIndoorPanelForBuilding = function() {
            const result = __baseOpenIndoorPanelForBuildingFloorButtons.apply(this, arguments);

            setTimeout(() => {
                renderIndoorFloorButtonsFinal();
                updateIndoorFloorButtonActiveFinal();

                if (indoorMap) {
                    indoorMap.invalidateSize();
                }
            }, 220);

            setTimeout(() => {
                if (indoorMap) {
                    indoorMap.invalidateSize();

                    const mapItem = allIndoorMaps.find(m =>
                        Number(m.building_id) === Number(currentIndoorBuildingId) &&
                        Number(m.floor_number) === Number(currentIndoorFloor)
                    );

                    const routePoints = (typeof persistentIndoorRouteByFloor !== 'undefined' && persistentIndoorRouteByFloor)
                        ? (persistentIndoorRouteByFloor[currentIndoorFloor] || [])
                        : [];

                    if (routePoints.length >= 2) {
                        indoorMap.fitBounds(L.latLngBounds(routePoints), {
                            padding: [44, 44],
                            animate: false
                        });
                    } else {
                        const bounds = mapItem ? getIndoorMapBoundsFromGeometry(mapItem.geometry) : null;
                        if (bounds && bounds.isValid()) {
                            indoorMap.fitBounds(bounds, {
                                padding: [24, 24],
                                animate: false
                            });
                        }
                    }
                }
            }, 420);

            return result;
        };

        window.openIndoorPanelForBuilding = openIndoorPanelForBuilding;
    }

    /*
       Wrap floor render so active button always follows current floor.
    */
    if (typeof renderIndoorFloor === 'function' && !window.__floorButtonsRenderWrapped) {
        window.__floorButtonsRenderWrapped = true;
        const __baseRenderIndoorFloorButtons = renderIndoorFloor;

        renderIndoorFloor = function() {
            const result = __baseRenderIndoorFloorButtons.apply(this, arguments);

            setTimeout(() => {
                renderIndoorFloorButtonsFinal();
                updateIndoorFloorButtonActiveFinal();

                if (indoorMap) {
                    indoorMap.invalidateSize();
                }
            }, 60);

            return result;
        };
    }

    window.renderIndoorFloorButtonsFinal = renderIndoorFloorButtonsFinal;
    window.setIndoorFloorFromButtonFinal = setIndoorFloorFromButtonFinal;


    /* =========================================================
       SAFE UI CLOSE HELPERS
       This replaces the removed duplicate second script block.
    ========================================================= */
    document.addEventListener('DOMContentLoaded', function() {
        const dockEl = document.getElementById('floating-route-ui');
        const actionCardEl = document.getElementById('floating-action-card');
        const browseModalEl = document.getElementById('browseOptionsModal');

        document.addEventListener('click', function(event) {
            const clickedInsideDock = dockEl && dockEl.contains(event.target);
            const clickedInsideBrowse = browseModalEl && browseModalEl.contains(event.target);

            if (!clickedInsideDock && !clickedInsideBrowse && actionCardEl && actionCardEl.style.display === 'block') {
                if (typeof closeFloatingActionCard === 'function') {
                    closeFloatingActionCard();
                }
            }
        });

        if (browseModalEl && !browseModalEl.__safeCloseBound) {
            browseModalEl.__safeCloseBound = true;
            browseModalEl.addEventListener('click', function(event) {
                if (event.target === browseModalEl && typeof closeBrowseOptionsModal === 'function') {
                    closeBrowseOptionsModal();
                }
            });
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (typeof closeFloatingActionCard === 'function') closeFloatingActionCard();
                if (typeof closeBrowseOptionsModal === 'function') closeBrowseOptionsModal();
                if (typeof closeAiTransformPanel === 'function') closeAiTransformPanel();
            }
        });
    });



/* =========================================================
   FINAL MOBILE FRIENDLY JS PATCH
   Keeps Leaflet maps correct after mobile resizing/orientation/UI changes.
========================================================= */

function isMobileViewportFinal() {
    return window.matchMedia('(max-width: 768px)').matches;
}

function invalidateMainAndIndoorMapsFinal(delay = 160) {
    setTimeout(() => {
        if (typeof map !== 'undefined' && map) {
            map.invalidateSize();
        }

        if (typeof indoorMap !== 'undefined' && indoorMap) {
            indoorMap.invalidateSize();
        }
    }, delay);
}

function updateMobileViewportClassFinal() {
    document.body.classList.toggle('is-mobile-view', isMobileViewportFinal());
    invalidateMainAndIndoorMapsFinal(180);
}

window.addEventListener('resize', updateMobileViewportClassFinal);
window.addEventListener('orientationchange', function () {
    setTimeout(updateMobileViewportClassFinal, 280);
});

document.addEventListener('DOMContentLoaded', function () {
    updateMobileViewportClassFinal();

    document.querySelectorAll(
        '.floating-mode-btn, .floating-action-btn, .ai-search-submit, .ai-record-inline-btn, .route-btn, .indoor-floor-btn, .indoor-close'
    ).forEach(btn => {
        btn.addEventListener('touchstart', function () {}, {
            passive: true
        });
    });
});

/*
|---------------------------------------------------------------------------
| Wrap indoor open/close and floor button changes so map always resizes
| correctly on mobile.
|---------------------------------------------------------------------------
*/
if (typeof openIndoorPanelForBuilding === 'function' && !window.__mobileOpenIndoorWrapped) {
    window.__mobileOpenIndoorWrapped = true;
    const __baseOpenIndoorPanelForMobile = openIndoorPanelForBuilding;

    openIndoorPanelForBuilding = function () {
        const result = __baseOpenIndoorPanelForMobile.apply(this, arguments);

        document.body.classList.add('indoor-open');
        invalidateMainAndIndoorMapsFinal(120);
        invalidateMainAndIndoorMapsFinal(360);
        invalidateMainAndIndoorMapsFinal(700);

        return result;
    };

    window.openIndoorPanelForBuilding = openIndoorPanelForBuilding;
}

if (typeof closeIndoorPanelFn === 'function' && !window.__mobileCloseIndoorWrapped) {
    window.__mobileCloseIndoorWrapped = true;
    const __baseCloseIndoorPanelForMobile = closeIndoorPanelFn;

    closeIndoorPanelFn = function () {
        const result = __baseCloseIndoorPanelForMobile.apply(this, arguments);

        document.body.classList.remove('indoor-open');
        invalidateMainAndIndoorMapsFinal(140);

        return result;
    };

    window.closeIndoorPanelFn = closeIndoorPanelFn;
}

if (typeof setIndoorFloorFromButtonFinal === 'function' && !window.__mobileFloorButtonWrapped) {
    window.__mobileFloorButtonWrapped = true;
    const __baseSetIndoorFloorFromButtonMobile = setIndoorFloorFromButtonFinal;

    setIndoorFloorFromButtonFinal = function () {
        const result = __baseSetIndoorFloorFromButtonMobile.apply(this, arguments);

        invalidateMainAndIndoorMapsFinal(120);
        invalidateMainAndIndoorMapsFinal(320);

        return result;
    };

    window.setIndoorFloorFromButtonFinal = setIndoorFloorFromButtonFinal;
}

/*
|---------------------------------------------------------------------------
| Modal open helpers: scroll to bottom panel correctly on phone.
|---------------------------------------------------------------------------
*/
if (typeof openBrowseOptionsModal === 'function' && !window.__mobileBrowseModalWrapped) {
    window.__mobileBrowseModalWrapped = true;
    const __baseOpenBrowseOptionsModalMobile = openBrowseOptionsModal;

    openBrowseOptionsModal = function () {
        const result = __baseOpenBrowseOptionsModalMobile.apply(this, arguments);
        invalidateMainAndIndoorMapsFinal(120);
        return result;
    };

    window.openBrowseOptionsModal = openBrowseOptionsModal;
}

if (typeof openInlineTextSearch === 'function' && !window.__mobileTextSearchWrapped) {
    window.__mobileTextSearchWrapped = true;
    const __baseOpenInlineTextSearchMobile = openInlineTextSearch;

    openInlineTextSearch = function () {
        const result = __baseOpenInlineTextSearchMobile.apply(this, arguments);

        setTimeout(() => {
            const input = document.getElementById('destination-search-input');
            if (input && isMobileViewportFinal()) {
                input.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }, 180);

        return result;
    };

    window.openInlineTextSearch = openInlineTextSearch;
}

invalidateMainAndIndoorMapsFinal(400);


/* =========================================================
   MOBILE INDOOR ZOOM IN PATCH
   Mobile view only:
   - smaller padding = more zoom in
   - no manual zoom-out
   - adds a little zoom-in after fitBounds
========================================================= */

function isIndoorMobileViewportFinal() {
    return window.matchMedia('(max-width: 768px)').matches;
}

function getCurrentIndoorMapItemFinal() {
    if (!currentIndoorBuildingId || !hasIndoorFloorValue(currentIndoorFloor)) return null;

    return (allIndoorMaps || []).find(m =>
        Number(m.building_id) === Number(currentIndoorBuildingId) &&
        Number(m.floor_number) === Number(currentIndoorFloor)
    ) || null;
}

function fitIndoorMapMobileZoomInFinal(delay = 120) {
    setTimeout(() => {
        if (!indoorMap || !currentIndoorBuildingId || !hasIndoorFloorValue(currentIndoorFloor)) return;

        const isMobile = isIndoorMobileViewportFinal();

        if (typeof indoorMap.setMinZoom === 'function') {
            indoorMap.setMinZoom(isMobile ? 15 : 17);
        }

        indoorMap.invalidateSize();

        const mapItem = getCurrentIndoorMapItemFinal();
        const bounds = mapItem ? getIndoorMapBoundsFromGeometry(mapItem.geometry) : null;

        if (bounds && bounds.isValid()) {
            /*
            |--------------------------------------------------------------------------
            | Adjust mobilePad diri:
            | 0.06 = mas zoom in
            | 0.10 = sakto/gamay zoom in
            | 0.18 = medyo zoom out
            |--------------------------------------------------------------------------
            */
            const mobilePad = 0.03;
            const desktopPad = 0.12;

            indoorMap.fitBounds(bounds.pad(isMobile ? mobilePad : desktopPad), {
                animate: false,
                padding: isMobile ? [8, 8] : [28, 28],
                maxZoom: isMobile ? 22 : 22
            });

            /*
            |--------------------------------------------------------------------------
            | Extra mobile zoom-in gamay after fitBounds.
            | +0.25 = gamay ra
            | +0.45 = recommended
            | +0.70 = mas duol / gamay zoom in pa
            |--------------------------------------------------------------------------
            */
            if (isMobile) {
                const currentZoom = indoorMap.getZoom();
                indoorMap.setZoom(Math.min(indoorMap.getMaxZoom(), currentZoom + 0.40), {
                    animate: false
                });
            }
        }
    }, delay);
}

if (typeof openIndoorPanelForBuilding === 'function' && !window.__mobileIndoorZoomInOpenWrapped) {
    window.__mobileIndoorZoomInOpenWrapped = true;
    const __baseOpenIndoorPanelZoomIn = openIndoorPanelForBuilding;

    openIndoorPanelForBuilding = function () {
        const result = __baseOpenIndoorPanelZoomIn.apply(this, arguments);

        fitIndoorMapMobileZoomInFinal(180);
        fitIndoorMapMobileZoomInFinal(480);
        fitIndoorMapMobileZoomInFinal(850);

        return result;
    };

    window.openIndoorPanelForBuilding = openIndoorPanelForBuilding;
}

if (typeof renderIndoorFloor === 'function' && !window.__mobileIndoorZoomInRenderWrapped) {
    window.__mobileIndoorZoomInRenderWrapped = true;
    const __baseRenderIndoorFloorZoomIn = renderIndoorFloor;

    renderIndoorFloor = function () {
        const result = __baseRenderIndoorFloorZoomIn.apply(this, arguments);

        fitIndoorMapMobileZoomInFinal(180);
        fitIndoorMapMobileZoomInFinal(420);

        return result;
    };

    window.renderIndoorFloor = renderIndoorFloor;
}

window.addEventListener('orientationchange', function () {
    if (isIndoorMobileViewportFinal()) {
        fitIndoorMapMobileZoomInFinal(360);
    }
});

window.addEventListener('resize', function () {
    if (isIndoorMobileViewportFinal()) {
        fitIndoorMapMobileZoomInFinal(220);
    }
});

window.fitIndoorMapMobileZoomInFinal = fitIndoorMapMobileZoomInFinal;

/* =========================================================
   MOBILE OUTDOOR ROUTE ZOOM PATCH - MANUAL ZOOM FRIENDLY
   Outdoor map only. Indoor map is not affected.

   Mobile behavior:
   - Normal/default outdoor view: zoom 18
   - After route/navigation: auto zoom out to 17 ONCE
   - User can still manually zoom in again up to maxZoom 19
========================================================= */
function isMobileOutdoorViewFinal() {
    return window.matchMedia('(max-width: 768px)').matches;
}

let mobileOutdoorRouteZoomMode = false;

function unlockMobileOutdoorManualZoomFinal() {
    if (!isMobileOutdoorViewFinal() || !map) return;

    if (typeof map.setMinZoom === 'function') {
        map.setMinZoom(MOBILE_OUTDOOR_MIN_ZOOM_VALUE);
    }

    if (typeof map.setMaxZoom === 'function') {
        map.setMaxZoom(MOBILE_OUTDOOR_MAX_ZOOM_VALUE);
    }
}

function applyMobileOutdoorDefaultZoomFinal(delay = 160) {
    if (!isMobileOutdoorViewFinal() || !map) return;

    setTimeout(() => {
        if (!map) return;

        map.invalidateSize();
        unlockMobileOutdoorManualZoomFinal();

        map.setZoom(MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE, {
            animate: false
        });
    }, delay);
}

function applyMobileOutdoorRouteZoomFinal(delay = 160) {
    if (!isMobileOutdoorViewFinal() || !map) return;

    mobileOutdoorRouteZoomMode = true;

    setTimeout(() => {
        if (!map) return;

        map.invalidateSize();
        unlockMobileOutdoorManualZoomFinal();

        /* Auto zoom-out after route only. DILI ni mo lock sa manual zoom. */
        map.setZoom(MOBILE_OUTDOOR_ROUTE_ZOOM_VALUE, {
            animate: false
        });
    }, delay);
}

/*
|--------------------------------------------------------------------------
| fitBounds wrapper
|--------------------------------------------------------------------------
| Automatic route fitting ra ang limitahan.
| Manual pinch zoom / plus zoom button pwede gihapon hangtod maxZoom 19.
*/
if (!window.__mobileOutdoorFitBoundsZoomPatchWrapped) {
    window.__mobileOutdoorFitBoundsZoomPatchWrapped = true;

    const __baseOutdoorFitBounds = map.fitBounds.bind(map);

    map.fitBounds = function(bounds, options = {}) {
        const finalOptions = {
            ...(options || {})
        };

        if (isMobileOutdoorViewFinal()) {
            finalOptions.maxZoom = mobileOutdoorRouteZoomMode
                ? MOBILE_OUTDOOR_ROUTE_ZOOM_VALUE
                : MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE;

            finalOptions.padding = finalOptions.padding || [80, 80];
            finalOptions.animate = false;
        }

        const result = __baseOutdoorFitBounds(bounds, finalOptions);

        if (isMobileOutdoorViewFinal()) {
            if (mobileOutdoorRouteZoomMode) {
                applyMobileOutdoorRouteZoomFinal(260);
            } else {
                applyMobileOutdoorDefaultZoomFinal(260);
            }
        }

        return result;
    };
}


if (typeof drawOutdoorRoute === 'function' && !window.__mobileOutdoorDrawRouteZoomPatchWrapped) {
    window.__mobileOutdoorDrawRouteZoomPatchWrapped = true;

    const __baseDrawOutdoorRouteZoomPatch = drawOutdoorRoute;

    drawOutdoorRoute = function() {
        mobileOutdoorRouteZoomMode = true;
        const result = __baseDrawOutdoorRouteZoomPatch.apply(this, arguments);
        applyMobileOutdoorRouteZoomFinal(260);
        return result;
    };

    window.drawOutdoorRoute = drawOutdoorRoute;
}

if (typeof findRouteByDestination === 'function' && !window.__mobileOutdoorFindRouteZoomPatchWrapped) {
    window.__mobileOutdoorFindRouteZoomPatchWrapped = true;

    const __baseFindRouteByDestinationZoomPatch = findRouteByDestination;

    findRouteByDestination = function() {
        mobileOutdoorRouteZoomMode = true;
        const result = __baseFindRouteByDestinationZoomPatch.apply(this, arguments);
        applyMobileOutdoorRouteZoomFinal(320);
        return result;
    };

    window.findRouteByDestination = findRouteByDestination;
}

if (typeof computeCompleteRouteToRoom === 'function' && !window.__mobileOutdoorRoomRouteZoomPatchWrapped) {
    window.__mobileOutdoorRoomRouteZoomPatchWrapped = true;

    const __baseComputeCompleteRouteToRoomZoomPatch = computeCompleteRouteToRoom;

    computeCompleteRouteToRoom = function() {
        mobileOutdoorRouteZoomMode = true;
        const result = __baseComputeCompleteRouteToRoomZoomPatch.apply(this, arguments);
        applyMobileOutdoorRouteZoomFinal(360);
        return result;
    };

    window.computeCompleteRouteToRoom = computeCompleteRouteToRoom;
}

if (typeof searchTextDestination === 'function' && !window.__mobileOutdoorTextSearchZoomPatchWrapped) {
    window.__mobileOutdoorTextSearchZoomPatchWrapped = true;

    const __baseSearchTextDestinationZoomPatch = searchTextDestination;

    searchTextDestination = function() {
        mobileOutdoorRouteZoomMode = true;
        const result = __baseSearchTextDestinationZoomPatch.apply(this, arguments);
        applyMobileOutdoorRouteZoomFinal(360);
        return result;
    };

    window.searchTextDestination = searchTextDestination;
}

if (typeof resetRouteSelection === 'function' && !window.__mobileOutdoorResetZoomPatchWrapped) {
    window.__mobileOutdoorResetZoomPatchWrapped = true;

    const __baseResetRouteSelectionZoomPatch = resetRouteSelection;

    resetRouteSelection = function() {
        mobileOutdoorRouteZoomMode = false;
        const result = __baseResetRouteSelectionZoomPatch.apply(this, arguments);
        applyMobileOutdoorDefaultZoomFinal(260);
        return result;
    };

    window.resetRouteSelection = resetRouteSelection;
}

window.addEventListener('resize', function() {
    unlockMobileOutdoorManualZoomFinal();
});

window.addEventListener('orientationchange', function() {
    unlockMobileOutdoorManualZoomFinal();
});

/* First mobile load = default zoom 18 */
applyMobileOutdoorDefaultZoomFinal(500);

window.applyMobileOutdoorDefaultZoomFinal = applyMobileOutdoorDefaultZoomFinal;
window.applyMobileOutdoorRouteZoomFinal = applyMobileOutdoorRouteZoomFinal;
window.unlockMobileOutdoorManualZoomFinal = unlockMobileOutdoorManualZoomFinal;

window.toggleCampusEventPanel = toggleCampusEventPanel;
window.closeCampusEventPanel = closeCampusEventPanel;
window.routeToCampusEvent = routeToCampusEvent;



    /* =========================================================
       USER PROFILE DROPDOWN
       - Opens when profile icon is clicked
       - Closes when clicking outside or pressing ESC
    ========================================================= */
    function toggleUserProfileMenu(event) {
        if (event) {
            event.stopPropagation();
        }

        const menu = document.getElementById('user-profile-menu');
        const btn = document.getElementById('user-profile-btn');

        if (!menu || !btn) return;

        const isOpen = menu.classList.contains('open');

        menu.classList.toggle('open', !isOpen);
        btn.classList.toggle('active', !isOpen);
    }

    function closeUserProfileMenu() {
        const menu = document.getElementById('user-profile-menu');
        const btn = document.getElementById('user-profile-btn');

        if (menu) {
            menu.classList.remove('open');
        }

        if (btn) {
            btn.classList.remove('active');
        }
    }

    document.addEventListener('click', function(event) {
        const wrap = document.getElementById('user-profile-wrap');

        if (!wrap) return;
        if (wrap.contains(event.target)) return;

        closeUserProfileMenu();
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeUserProfileMenu();
        }
    });

/* =========================================================
   SMOOTHNESS / PERFORMANCE PATCH
   UI + map interaction optimization only.
   No routing algorithm or building color changes.
========================================================= */
(function smoothCampusNavigationPatch() {
    if (window.__smoothCampusNavigationPatchApplied) return;
    window.__smoothCampusNavigationPatchApplied = true;

    const body = document.body;
    let movingTimer = null;

    function markMapMoving() {
        if (!body) return;
        body.classList.add('map-moving');
        if (movingTimer) clearTimeout(movingTimer);
    }

    function unmarkMapMovingSoon() {
        if (!body) return;
        if (movingTimer) clearTimeout(movingTimer);
        movingTimer = setTimeout(() => {
            body.classList.remove('map-moving');
        }, 140);
    }

    if (typeof map !== 'undefined' && map) {
        map.on('movestart zoomstart dragstart', markMapMoving);
        map.on('moveend zoomend dragend', unmarkMapMovingSoon);
    }

    /*
    | Debounce repeated indoor layer click attachment.
    | This prevents repeated scans when many Leaflet layers are added.
    */
    if (typeof attachIndoorClickToBuildingLayersFinal === 'function' && !window.__smoothIndoorAttachDebounced) {
        window.__smoothIndoorAttachDebounced = true;

        const baseAttachIndoorClickToBuildingLayersFinal = attachIndoorClickToBuildingLayersFinal;
        let attachPending = false;

        attachIndoorClickToBuildingLayersFinal = function smoothAttachIndoorClickDebounced() {
            if (attachPending) return;

            attachPending = true;

            requestAnimationFrame(() => {
                attachPending = false;
                baseAttachIndoorClickToBuildingLayersFinal();
            });
        };

        window.attachIndoorClickToBuildingLayersFinal = attachIndoorClickToBuildingLayersFinal;
    }

    /*
    | Throttle popup scale updates so map movement doesn't trigger too many layout updates.
    */
    if (typeof updateRouteBuildingPopupScale === 'function' && !window.__smoothPopupScaleThrottled) {
        window.__smoothPopupScaleThrottled = true;

        const baseUpdateRouteBuildingPopupScale = updateRouteBuildingPopupScale;
        let scalePending = false;

        updateRouteBuildingPopupScale = function smoothPopupScaleThrottled() {
            if (scalePending) return;

            scalePending = true;

            requestAnimationFrame(() => {
                scalePending = false;
                baseUpdateRouteBuildingPopupScale();
            });
        };

        window.updateRouteBuildingPopupScale = updateRouteBuildingPopupScale;
    }

    /*
    | Pause visual effects when tab is hidden.
    */
    document.addEventListener('visibilitychange', () => {
        if (!body) return;
        body.classList.toggle('page-hidden', document.hidden);
    });
})();

/* =========================================================
   CLEAN MOBILE BUILDING SHADOW RUNTIME PATCH
   Keeps map-moving class for transition control.
   Also hides any old duplicate shadow panes if cached.
========================================================= */
(function cleanMobileBuildingShadowFix() {
    if (window.__cleanMobileBuildingShadowFixApplied) return;
    window.__cleanMobileBuildingShadowFixApplied = true;

    document.documentElement.style.setProperty('--step', '1px');

    let movingTimer = null;

    function markMoving() {
        document.body.classList.add('map-moving');

        if (movingTimer) {
            clearTimeout(movingTimer);
        }
    }

    function markStoppedSoon() {
        if (movingTimer) {
            clearTimeout(movingTimer);
        }

        movingTimer = setTimeout(() => {
            document.body.classList.remove('map-moving');
        }, 180);
    }

    if (typeof map !== 'undefined' && map) {
        map.on('movestart zoomstart dragstart', markMoving);
        map.on('move zoom', markMoving);
        map.on('moveend zoomend dragend', markStoppedSoon);

        const oldShadowPane = map.getPane('buildingShadowPane');
        if (oldShadowPane) {
            oldShadowPane.style.display = 'none';
            oldShadowPane.style.opacity = '0';
            oldShadowPane.style.pointerEvents = 'none';
        }
    }

    const style = document.createElement('style');
    style.setAttribute('data-clean-mobile-building-shadow-fix', 'true');
    style.textContent = `
        .leaflet-buildingShadowPane-pane,
        .fake-3d-building-shadow,
        .mobile-fake-3d-shadow {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        @media (hover: none), (max-width: 768px) {
            .fake-3d-building,
            .fake-3d-building:hover,
            body.map-moving .fake-3d-building,
            body.map-moving .fake-3d-building:hover,
            .leaflet-buildingsPane-pane .leaflet-interactive,
            .leaflet-buildingsPane-pane .leaflet-interactive:hover {
                filter:
                    drop-shadow(3px 4px 1px rgba(15, 23, 42, 0.42))
                    drop-shadow(6px 7px 4px rgba(15, 23, 42, 0.18)) !important;
                transform: none !important;
            }
        }

        @media (max-width: 420px) {
            .fake-3d-building,
            .fake-3d-building:hover,
            body.map-moving .fake-3d-building,
            body.map-moving .fake-3d-building:hover,
            .leaflet-buildingsPane-pane .leaflet-interactive,
            .leaflet-buildingsPane-pane .leaflet-interactive:hover {
                filter:
                    drop-shadow(2px 3px 1px rgba(15, 23, 42, 0.44))
                    drop-shadow(4px 5px 3px rgba(15, 23, 42, 0.18)) !important;
            }
        }
    `;
    document.head.appendChild(style);
})();

/* =========================================================
   3-LAYER LIGHTWEIGHT FAKE 3D PERFORMANCE RUNTIME PATCH
   Purpose:
   - Keeps fake 3D visible but cheaper than heavy multi-shadow version.
   - Updates depth only after zoom settles.
   - No routing logic changes.
========================================================= */
(function threeLayerLightweightFake3DPerformancePatch() {
    if (window.__threeLayerLightweightFake3DPerformancePatchApplied) return;
    window.__threeLayerLightweightFake3DPerformancePatchApplied = true;

    const body = document.body;
    let movingTimer = null;
    let zoomTimer = null;
    let shadowFrame = null;

    function scheduleUpdateShadows() {
        if (shadowFrame) {
            cancelAnimationFrame(shadowFrame);
        }

        shadowFrame = requestAnimationFrame(() => {
            shadowFrame = null;

            if (typeof updateShadows === 'function') {
                updateShadows();
            }

            if (typeof updateBuildingPerformanceMode === 'function') {
                updateBuildingPerformanceMode();
            }
        });
    }

    function markMoving() {
        if (!body) return;
        body.classList.add('map-moving');
        if (movingTimer) clearTimeout(movingTimer);
    }

    function markStoppedSoon() {
        if (!body) return;
        if (movingTimer) clearTimeout(movingTimer);

        movingTimer = setTimeout(() => {
            body.classList.remove('map-moving');
        }, 150);
    }

    function markZooming() {
        if (!body) return;
        body.classList.add('map-zooming', 'map-moving');
        if (zoomTimer) clearTimeout(zoomTimer);
    }

    function markZoomStoppedSoon() {
        if (!body) return;
        if (zoomTimer) clearTimeout(zoomTimer);

        zoomTimer = setTimeout(() => {
            scheduleUpdateShadows();
            body.classList.remove('map-zooming');
            markStoppedSoon();
        }, 170);
    }

    if (typeof map !== 'undefined' && map) {
        map.on('zoomstart', markZooming);
        map.on('zoomend', markZoomStoppedSoon);
        map.on('movestart dragstart', markMoving);
        map.on('moveend dragend', markStoppedSoon);
        map.on('resize', scheduleUpdateShadows);
    }

    window.addEventListener('resize', scheduleUpdateShadows);
    window.addEventListener('orientationchange', scheduleUpdateShadows);

    if (typeof updateRouteBuildingPopupScale === 'function' && !window.__routePopupScaleThreeLayerFake3DPerfWrapped) {
        window.__routePopupScaleThreeLayerFake3DPerfWrapped = true;

        const baseUpdateRouteBuildingPopupScale = updateRouteBuildingPopupScale;
        let popupScaleFrame = null;

        updateRouteBuildingPopupScale = function updateRouteBuildingPopupScaleThrottled() {
            if (popupScaleFrame) return;

            popupScaleFrame = requestAnimationFrame(() => {
                popupScaleFrame = null;
                baseUpdateRouteBuildingPopupScale();
            });
        };

        window.updateRouteBuildingPopupScale = updateRouteBuildingPopupScale;
    }

    scheduleUpdateShadows();
})();

/* =========================================================
   NOTIFICATION BELL RESTORE RUNTIME PATCH
   Safe patch-only version:
   - Overrides renderCampusEventMarkers() so the bell is not hidden.
   - Ensures an empty-state panel exists.
   - Keeps the bell visible even when event count is 0.
========================================================= */
(function restoreCampusEventNotificationBell() {
    if (window.__restoreCampusEventNotificationBellApplied) return;
    window.__restoreCampusEventNotificationBellApplied = true;

    const originalEnsureCampusEventPanel =
        typeof ensureCampusEventPanel === 'function' ? ensureCampusEventPanel : null;

    const originalRenderCampusEventMarkers =
        typeof renderCampusEventMarkers === 'function' ? renderCampusEventMarkers : null;

    function makeBasicPanelIfMissing() {
        let wrap = document.getElementById('campus-event-notification-wrap');

        if (wrap) return wrap;

        wrap = document.createElement('div');
        wrap.id = 'campus-event-notification-wrap';
        wrap.className = 'campus-event-notification-wrap force-visible';
        wrap.innerHTML = `
            <button type="button"
                    class="campus-event-bell-btn"
                    id="campus-event-bell-btn"
                    onclick="toggleCampusEventPanel()"
                    aria-label="Open campus events">
                <span class="campus-event-bell-pulse" id="campus-event-bell-pulse" style="display:none;"></span>
                <span class="campus-event-bell-icon">🔔</span>
                <span class="campus-event-bell-count is-zero" id="campus-event-bell-count">0</span>
            </button>

            <div class="campus-event-panel" id="campus-event-panel">
                <div class="campus-event-panel-card">
                    <div class="campus-event-panel-head">
                        <div>
                            <div class="campus-event-panel-kicker">
                                <span class="campus-event-mini-dot upcoming-dot"></span>
                                Campus Events
                            </div>
                            <div class="campus-event-panel-title">Current & Upcoming</div>
                            <div class="campus-event-panel-subtitle">
                                Tap an event to route. Panel stays hidden until you open it.
                            </div>
                        </div>

                        <button type="button"
                                class="campus-event-panel-close"
                                onclick="closeCampusEventPanel()"
                                aria-label="Close campus events">
                            ×
                        </button>
                    </div>

                    <div class="campus-event-list" id="campus-event-list"></div>

                    <div class="campus-event-empty" id="campus-event-empty" style="display:none;">
                        <span class="campus-event-empty-icon">🔕</span>
                        No current or upcoming campus events.
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(wrap);
        return wrap;
    }

    window.ensureCampusEventPanel = function ensureCampusEventPanelRestored() {
        let wrap = null;

        if (originalEnsureCampusEventPanel) {
            wrap = originalEnsureCampusEventPanel();
        }

        if (!wrap) {
            wrap = makeBasicPanelIfMissing();
        }

        wrap.style.display = 'block';
        wrap.classList.add('force-visible');

        const panelCard = wrap.querySelector('.campus-event-panel-card');
        const empty = document.getElementById('campus-event-empty');

        if (panelCard && !empty) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'campus-event-empty';
            emptyDiv.id = 'campus-event-empty';
            emptyDiv.style.display = 'none';
            emptyDiv.innerHTML = '<span class="campus-event-empty-icon">🔕</span>No current or upcoming campus events.';
            panelCard.appendChild(emptyDiv);
        }

        return wrap;
    };

    window.renderCampusEventMarkers = function renderCampusEventMarkersRestored() {
        if (typeof campusEventLayer !== 'undefined' && campusEventLayer) {
            campusEventLayer.clearLayers();
        }

        const activeEvents = (typeof campusEvents !== 'undefined' ? (campusEvents || []) : []).filter(event => {
            return event && event.id && event.route_type && event.route_id;
        });

        const wrap = window.ensureCampusEventPanel();
        const list = document.getElementById('campus-event-list');
        const empty = document.getElementById('campus-event-empty');
        const count = document.getElementById('campus-event-bell-count');
        const pulse = document.getElementById('campus-event-bell-pulse');
        const bell = document.getElementById('campus-event-bell-btn');

        wrap.style.display = 'block';
        wrap.classList.add('force-visible');

        if (count) {
            count.textContent = activeEvents.length > 9 ? '9+' : String(activeEvents.length);
            count.classList.toggle('is-zero', activeEvents.length === 0);
        }

        if (pulse) {
            const hasNow = activeEvents.some(event => event.status === 'happening_now');
            pulse.style.display = hasNow ? 'block' : 'none';
        }

        if (bell) {
            bell.title = activeEvents.length
                ? `${activeEvents.length} current/upcoming campus event${activeEvents.length > 1 ? 's' : ''}`
                : 'No current or upcoming campus events';
        }

        if (list) {
            list.style.display = activeEvents.length ? 'block' : 'none';

            if (activeEvents.length && typeof createCampusEventCardHtml === 'function') {
                list.innerHTML = activeEvents.map(event => createCampusEventCardHtml(event)).join('');
            } else {
                list.innerHTML = '';
            }
        }

        if (empty) {
            empty.style.display = activeEvents.length ? 'none' : 'block';
        }
    };

    function syncBell() {
        if (typeof window.renderCampusEventMarkers === 'function') {
            window.renderCampusEventMarkers();
        } else if (typeof window.ensureCampusEventPanel === 'function') {
            window.ensureCampusEventPanel();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncBell);
    } else {
        syncBell();
    }

    setTimeout(syncBell, 300);
    setTimeout(syncBell, 1000);
})();

</script>

/* =========================================================
   FINAL FAKE 3D LAG REDUCER PATCH
   Purpose:
   - Keep fake 3D visible.
   - Use lighter shadow only while dragging/zooming.
   - Restore normal fake 3D after movement stops.
   - No geometry, route, landuse, or API logic changed.
========================================================= */
(function finalFake3DLagReducerPatch() {
    if (window.__finalFake3DLagReducerPatchApplied) return;
    window.__finalFake3DLagReducerPatchApplied = true;

    const body = document.body;
    let stopTimer = null;

    function movingOn() {
        if (!body) return;
        body.classList.add('map-moving-lite-3d');
        if (stopTimer) clearTimeout(stopTimer);
    }

    function movingOffSoon() {
        if (!body) return;
        if (stopTimer) clearTimeout(stopTimer);
        stopTimer = setTimeout(() => {
            body.classList.remove('map-moving-lite-3d');
        }, 220);
    }

    if (typeof map !== 'undefined' && map) {
        map.on('movestart dragstart zoomstart', movingOn);
        map.on('move zoom', movingOn);
        map.on('moveend dragend zoomend', movingOffSoon);
    }

    const oldStyle = document.getElementById('final-fake3d-lag-reducer-style');
    if (oldStyle) oldStyle.remove();

    const style = document.createElement('style');
    style.id = 'final-fake3d-lag-reducer-style';
    style.textContent = `
        /* Make SVG rendering cheaper but keep crisp borders */
        .leaflet-buildingsPane-pane svg,
        .leaflet-overlay-pane svg {
            shape-rendering: geometricPrecision;
        }

        .fake-3d-building,
        .leaflet-buildingsPane-pane .leaflet-interactive {
            will-change: auto !important;
            backface-visibility: hidden;
            transform: translateZ(0) !important;
            transition: fill-opacity 0.10s ease, stroke-width 0.10s ease !important;
            vector-effect: non-scaling-stroke !important;
        }

        /* While moving/zooming: keep 3D, but use cheaper 2-shadow depth */
        body.map-moving-lite-3d .fake-3d-building,
        body.map-moving-lite-3d .fake-3d-building:hover,
        body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive,
        body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive:hover {
            filter:
                drop-shadow(2px 3px 1px rgba(15, 23, 42, 0.34))
                drop-shadow(4px 5px 2px rgba(15, 23, 42, 0.14)) !important;
            transition: none !important;
            stroke-width: 1.25 !important;
            fill-opacity: 0.94 !important;
        }

        /* During zoom specifically: even lighter to reduce stutter */
        body.map-zooming .fake-3d-building,
        body.map-zooming .fake-3d-building:hover,
        body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive,
        body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive:hover {
            filter:
                drop-shadow(2px 2px 1px rgba(15, 23, 42, 0.30))
                drop-shadow(3px 4px 2px rgba(15, 23, 42, 0.12)) !important;
            transition: none !important;
            stroke-width: 1.2 !important;
        }

        /* Mobile: lighter shadows while interacting, still 3D */
        @media (hover: none), (max-width: 768px) {
            body.map-moving-lite-3d .fake-3d-building,
            body.map-moving-lite-3d .fake-3d-building:hover,
            body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive,
            body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive:hover {
                filter:
                    drop-shadow(1px 2px 1px rgba(15, 23, 42, 0.34))
                    drop-shadow(3px 4px 2px rgba(15, 23, 42, 0.14)) !important;
                stroke-width: 1.15 !important;
            }

            body.map-zooming .fake-3d-building,
            body.map-zooming .fake-3d-building:hover,
            body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive,
            body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive:hover {
                filter:
                    drop-shadow(1px 2px 1px rgba(15, 23, 42, 0.28))
                    drop-shadow(2px 3px 2px rgba(15, 23, 42, 0.12)) !important;
                stroke-width: 1.1 !important;
            }
        }
    `;

    document.head.appendChild(style);
})();


/* =========================================================
   OUTDOOR GPS SAFE MODE + QGIS PATH SNAPPING
   IMPORTANT:
   - GPS is used ONLY when it is reliable enough.
   - If GPS drifts to the field/building/wrong side, the system rejects it.
   - User is asked to use Tap My Location for exact current position.
   - Indoor navigation remains normal/static.
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
    const GPS_STRONG_ACCURACY_M = 15;          // <=15m = accepted as route start
    const GPS_PREVIEW_ACCURACY_M = 25;         // 16-25m = show snapped preview only, recommend Tap My Location
    const GPS_REJECT_ACCURACY_M = 25;          // >25m = too weak for campus navigation
    const GPS_MIN_SNAP_RADIUS_M = 12;          // minimum snap radius
    const GPS_MAX_SNAP_RADIUS_M = 30;          // strict cap to avoid snapping to wrong campus path
    const GPS_SNAP_GRACE_M = 4;                // small tolerance for QGIS/path offset
    const GPS_ACTIVE_ROUTE_EXTRA_M = 6;        // active route gets a little more allowance
    const GPS_MIN_SAMPLES = 4;                 // wait for stable readings before trusting
    const GPS_SAMPLE_SPREAD_M = 22;            // if samples are scattered, GPS is unstable
    const GPS_BAD_JUMP_DISTANCE_M = 30;        // reject sudden jump when tracking
    const GPS_SMOOTH_FACTOR = 0.30;

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
    let liveGpsMarker = null;
    let liveGpsAccuracyCircle = null;
    let liveGpsStatusEl = null;
    let liveGpsSamples = [];
    let lastRawGpsLatLng = null;
    let lastSmoothGpsLatLng = null;
    let lastSnappedGpsLatLng = null;
    let liveGpsFollow = true;
    let cachedCampusSegments = null;
    let cachedCampusSegmentCount = 0;
    let gpsAutoTapFallbackActive = false;

    const baseSelectGpsMode = typeof selectGpsMode === 'function' ? selectGpsMode : null;
    const baseSelectPickPathMode = typeof selectPickPathMode === 'function' ? selectPickPathMode : null;
    const baseSelectDefaultMode = typeof selectDefaultMode === 'function' ? selectDefaultMode : null;
    const baseResetRouteSelection = typeof resetRouteSelection === 'function' ? resetRouteSelection : null;

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
                    <div class="live-gps-dot"></div>
                    <div class="live-gps-direction-dot"></div>
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
            <div class="live-gps-status-actions">
                <button type="button" class="live-gps-mini-btn" onclick="toggleOutdoorLiveGpsFollow()" id="live-gps-follow-btn">Follow: ON</button>
                <button type="button" class="live-gps-mini-btn ghost" onclick="selectPickPathMode()">Tap My Location</button>
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
    }

    function hideLiveGpsStatus() {
        if (liveGpsStatusEl) {
            liveGpsStatusEl.remove();
            liveGpsStatusEl = null;
        }
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
                allowedDistance: activeSnapRadius,
                accuracy: Number(accuracy || 999)
            };
        }

        const campusPathSnap = snapToSegments(gpsLatLng, getCampusPathSegments(), pathSnapRadius);
        if (campusPathSnap.snapped) {
            return {
                ...campusPathSnap,
                allowedDistance: pathSnapRadius,
                accuracy: Number(accuracy || 999)
            };
        }

        const nearestDistance = Math.min(activeRouteSnap.distance || Infinity, campusPathSnap.distance || Infinity);
        return {
            point: gpsLatLng,
            distance: nearestDistance,
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

    function collectGpsSample(position) {
        const sample = {
            latLng: L.latLng(Number(position.coords.latitude), Number(position.coords.longitude)),
            accuracy: Number(position.coords.accuracy || 999),
            time: Date.now()
        };

        liveGpsSamples.push(sample);
        liveGpsSamples = liveGpsSamples.slice(-6);
        return sample;
    }

    function getBestGpsSample() {
        if (!liveGpsSamples.length) return null;
        return liveGpsSamples.reduce((best, current) => {
            return current.accuracy < best.accuracy ? current : best;
        }, liveGpsSamples[0]);
    }

    function getGpsSampleSpreadMeters() {
        if (liveGpsSamples.length < 2) return 0;
        const best = getBestGpsSample();
        if (!best) return 0;

        let maxDistance = 0;
        liveGpsSamples.forEach(sample => {
            const d = map.distance(best.latLng, sample.latLng);
            if (d > maxDistance) maxDistance = d;
        });
        return maxDistance;
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

    function updateLiveGpsVisual(rawLatLng, displayLatLng, accuracy) {
        if (!liveGpsMarker) {
            liveGpsMarker = L.marker(displayLatLng, {
                icon: makeLiveGpsIcon(),
                zIndexOffset: 99999,
                interactive: true
            }).addTo(map).bindPopup('Your live GPS location');
        } else {
            liveGpsMarker.setLatLng(displayLatLng);
        }

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
        }

        clearOutsideGuideLine();
        updateRouteLabels();
        return true;
    }

    function stopOutdoorLiveGpsWatchOnly() {
        if (liveGpsWatchId !== null) {
            navigator.geolocation.clearWatch(liveGpsWatchId);
            liveGpsWatchId = null;
        }

        gpsAutoTapFallbackActive = false;
        liveGpsSamples = [];
        lastRawGpsLatLng = null;
        lastSmoothGpsLatLng = null;
        lastSnappedGpsLatLng = null;
        liveGpsFollow = false;
    }

    function activateTapMyLocationFallback(title, message, rawLatLng = null, accuracy = null) {
        gpsAutoTapFallbackActive = true;

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

    function rejectGpsPoint(rawLatLng, accuracy, title, message) {
        const finalMessage = `${message} Tap your actual current walkway on the map now; your tap will snap to the nearest QGIS path.`;
        activateTapMyLocationFallback(title, finalMessage, rawLatLng, accuracy);
    }

    function handleLiveGpsPosition(position) {
        if (!position || !position.coords) return;

        const latestSample = collectGpsSample(position);
        const bestSample = getBestGpsSample() || latestSample;
        const rawLatLng = bestSample.latLng;
        const accuracy = Number(bestSample.accuracy || 999);
        const spread = getGpsSampleSpreadMeters();

        console.log('[Outdoor Live GPS]', {
            latestAccuracy: Math.round(latestSample.accuracy),
            bestAccuracy: Math.round(accuracy),
            sampleCount: liveGpsSamples.length,
            sampleSpread: Math.round(spread),
            snapRadius: Math.round(getGpsSnapRadiusMeters(accuracy)),
            lat: rawLatLng.lat,
            lng: rawLatLng.lng
        });

        if (liveGpsSamples.length < GPS_MIN_SAMPLES) {
            updateRawGpsAccuracyCircle(latestSample.latLng, latestSample.accuracy);
            removeLiveGpsMarkerOnly();
            setLiveGpsStatus(
                'loading',
                `Checking GPS (${Math.round(latestSample.accuracy)}m)`,
                'Waiting for 4 readings. If GPS is weak, Tap My Location will open automatically.'
            );
            return;
        }

        if (accuracy > GPS_REJECT_ACCURACY_M) {
            rejectGpsPoint(
                rawLatLng,
                accuracy,
                `GPS weak (${Math.round(accuracy)}m)`,
                'GPS is above 25m accuracy, which is too weak for campus routing. Please tap Tap My Location and select your exact walkway.'
            );
            return;
        }

        if (spread > GPS_SAMPLE_SPREAD_M && accuracy > GPS_STRONG_ACCURACY_M) {
            rejectGpsPoint(
                rawLatLng,
                accuracy,
                `GPS unstable (${Math.round(spread)}m drift)`,
                'GPS readings are jumping. Please use Tap My Location for the correct start point.'
            );
            return;
        }

        if (lastRawGpsLatLng) {
            const jumpDistance = map.distance(lastRawGpsLatLng, rawLatLng);
            if (jumpDistance > GPS_BAD_JUMP_DISTANCE_M && accuracy > GPS_STRONG_ACCURACY_M) {
                rejectGpsPoint(
                    rawLatLng,
                    accuracy,
                    `GPS jump detected (${Math.round(jumpDistance)}m)`,
                    'GPS suddenly jumped to another area. Keeping route safe; use Tap My Location if needed.'
                );
                return;
            }
        }

        lastRawGpsLatLng = rawLatLng;

        const snapResult = snapOutdoorGps(rawLatLng, accuracy);
        const snappedLatLng = snapResult.point || null;
        const snapDistanceText = Number.isFinite(snapResult.distance) ? `${Math.round(snapResult.distance)}m from path` : 'no nearby path';
        const snapRadiusText = `${Math.round(snapResult.allowedDistance || getGpsSnapRadiusMeters(accuracy))}m snap radius`;
        const snapText = snapResult.snapped
            ? (snapResult.source === 'active_route' ? `locked to active route (${snapDistanceText}, ${snapRadiusText})` : `snapped to QGIS path (${snapDistanceText}, ${snapRadiusText})`)
            : `not close to any QGIS path (${snapDistanceText}, ${snapRadiusText})`;

        if (!snapResult.snapped || !snappedLatLng) {
            rejectGpsPoint(
                rawLatLng,
                accuracy,
                `GPS off path (${Math.round(accuracy)}m)`,
                `Nearest path is ${snapDistanceText}. GPS snap radius is ${snapRadiusText}. Please move closer to a walkway or use Tap My Location.`
            );
            return;
        }

        const smoothLatLng = smoothGpsPoint(lastSmoothGpsLatLng, snappedLatLng);
        lastSmoothGpsLatLng = smoothLatLng;
        lastSnappedGpsLatLng = snappedLatLng;

        updateLiveGpsVisual(rawLatLng, smoothLatLng, accuracy);

        /*
        | IMPORTANT FOR WEAK CAMPUS GPS:
        | 16-25m accuracy can still be several buildings/paths away.
        | We show the snapped dot as an ESTIMATE only, but we do NOT use it
        | as the official route start. This prevents wrong routing when GPS
        | drifts to the open field even if the user is near the IT Building.
        */
        if (accuracy > GPS_STRONG_ACCURACY_M) {
            /*
            | Weak campus GPS fallback:
            | Do not keep following a misleading GPS dot. Automatically switch
            | to Tap My Location so the user can tap the real walkway once.
            */
            activateTapMyLocationFallback(
                `GPS weak (${Math.round(accuracy)}m)`,
                `GPS snapped as an estimate (${snapText}), but ${Math.round(accuracy)}m is not accurate enough for campus routing. Tap your actual current walkway on the map now; your tap will snap to the nearest QGIS path.`,
                rawLatLng,
                accuracy
            );
            return;
        }

        updateGpsRouteStart(snappedLatLng, 'live_gps_snapped');

        setLiveGpsStatus(
            'good',
            `GPS start accepted (${Math.round(accuracy)}m)`,
            `Location is ${snapText}. Accuracy circle is ${Math.round(accuracy)}m. If this is not your real position, tap Tap My Location.`
        );
        setRouteResultLabel(`GPS start accepted. Accuracy ${Math.round(accuracy)}m; ${snapText}.`);

        if (liveGpsFollow && smoothLatLng) {
            map.panTo(smoothLatLng, { animate: true, duration: 0.35 });
        }
    }

    function handleLiveGpsError(error) {
        console.error('Outdoor live GPS error:', error);

        let message = 'Unable to get GPS location.';
        if (error?.code === 1) message = 'GPS permission denied. Please allow location access.';
        if (error?.code === 2) message = 'GPS position unavailable. Move to an open area or use Tap My Location.';
        if (error?.code === 3) message = 'GPS timed out. Try again or use Tap My Location.';

        setLiveGpsStatus('bad', 'GPS unavailable', message);
        setRouteResultLabel(message);
    }

    function startOutdoorLiveGpsTracking() {
        selectedStartMode = 'gps';
        placingStartMode = false;
        hidePickPathHelper();
        setActiveStartModeButton('gps');

        if (!navigator.geolocation) {
            alert('Geolocation is not supported on this device/browser. Please use Tap My Location.');
            return;
        }

        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            alert('GPS requires HTTPS when deployed online. Please use HTTPS hosting.');
            return;
        }

        if (liveGpsWatchId !== null) {
            liveGpsFollow = true;
            setLiveGpsStatus('loading', 'Outdoor Live GPS already active', 'Follow mode enabled. If location is wrong, tap Tap My Location.');
            return;
        }

        liveGpsSamples = [];
        lastRawGpsLatLng = null;
        lastSmoothGpsLatLng = null;
        lastSnappedGpsLatLng = null;
        liveGpsFollow = true;

        clearCurrentLocationMarker();
        clearStartMarker();
        removeLiveGpsMarkerOnly();

        setLiveGpsStatus('loading', 'Starting live GPS tracking...', 'Live tracking is active. If GPS is weak, the system will automatically ask you to tap your real walkway.');
        setRouteResultLabel('Live GPS tracking started. Accuracy is checked first; weak GPS will automatically switch to Tap My Location.');

        liveGpsWatchId = navigator.geolocation.watchPosition(
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
        if (liveGpsWatchId !== null) {
            navigator.geolocation.clearWatch(liveGpsWatchId);
            liveGpsWatchId = null;
        }

        liveGpsSamples = [];
        lastRawGpsLatLng = null;
        lastSmoothGpsLatLng = null;
        lastSnappedGpsLatLng = null;
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
    }

    function toggleOutdoorLiveGpsFollow() {
        liveGpsFollow = !liveGpsFollow;
        setLiveGpsStatus(
            liveGpsFollow ? 'good' : 'loading',
            liveGpsFollow ? 'Follow mode enabled' : 'Follow mode paused',
            liveGpsFollow ? 'The map will follow your accepted/snapped GPS dot.' : 'The dot will update only when GPS is trusted.'
        );

        if (liveGpsFollow && lastSmoothGpsLatLng) {
            map.panTo(lastSmoothGpsLatLng, { animate: true, duration: 0.35 });
        }
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
})();
