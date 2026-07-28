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
    const OUTDOOR_VECTOR_RENDER_PADDING = IS_MOBILE_OUTDOOR_VIEW ? 1 : 0.5;
    const MOBILE_PATH_CANVAS_PADDING = 0.35;
    const OUTDOOR_VECTOR_RENDERER = L.svg({
        /*
        | Keep GeoJSON buildings and paths rendered beyond the visible viewport.
        | Leaflet's small default SVG padding can expose a temporary empty strip
        | during a fast mobile swipe before the renderer catches up.
        */
        padding: OUTDOOR_VECTOR_RENDER_PADDING
    });
    /*
    | Mobile draws the many static campus path segments in one canvas instead
    | of maintaining more than one hundred SVG DOM nodes. The computed route
    | keeps its own SVG renderer below so its color and outline remain crisp.
    */
    const OUTDOOR_PATHS_RENDERER = IS_MOBILE_OUTDOOR_VIEW
        ? L.canvas({
            pane: 'pathsPane',
            padding: MOBILE_PATH_CANVAS_PADDING,
            tolerance: 0
        })
        : L.svg({
            pane: 'pathsPane',
            padding: OUTDOOR_VECTOR_RENDER_PADDING
        });
    const OUTDOOR_ROUTE_RENDERER = L.svg({
        pane: 'pathsPane',
        padding: OUTDOOR_VECTOR_RENDER_PADDING
    });
    const OUTDOOR_BUILDING_DEPTH_RENDERER = L.svg({
        pane: 'buildingDepthPane',
        padding: OUTDOOR_VECTOR_RENDER_PADDING
    });
    const OUTDOOR_BUILDINGS_RENDERER = L.svg({
        pane: 'buildingsPane',
        padding: OUTDOOR_VECTOR_RENDER_PADDING
    });

    const map = L.map('map', {
        zoomControl: true,
        renderer: OUTDOOR_VECTOR_RENDERER,

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
        | Mobile keeps a large tile buffer and waits until interaction settles
        | before requesting more tiles. This prevents network/image decoding work
        | from competing with finger dragging.
        */
        zoomAnimation: false,
        fadeAnimation: false,
        markerZoomAnimation: false,
        inertia: true
    });

    map.createPane('pathsPane');
    map.getPane('pathsPane').style.zIndex = 400;

    map.createPane('buildingDepthPane');
    map.getPane('buildingDepthPane').style.zIndex = 445;
    map.getPane('buildingDepthPane').style.pointerEvents = 'none';

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
        updateWhenIdle: IS_MOBILE_OUTDOOR_VIEW,
        updateWhenZooming: !IS_MOBILE_OUTDOOR_VIEW,
        updateInterval: IS_MOBILE_OUTDOOR_VIEW ? 180 : 120,
        keepBuffer: 5
    }).addTo(map);

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
    map.on('zoomend', () => {
        updateShadows();
        updateRouteBuildingPopupScale();
    });

    map.on('moveend viewreset resize', () => {
        updateRouteBuildingPopupScale();
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
