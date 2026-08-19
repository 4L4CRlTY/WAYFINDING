    /*
    |--------------------------------------------------------------------------
    | MOBILE OUTDOOR ZOOM SETTINGS
    |--------------------------------------------------------------------------
    | Mobile only ni siya.
    | Default outdoor map zoom = 18
    | Route / navigation outdoor zoom = 17
    | Lower value = mas zoom out.
    */
    function detectWayfindingRenderProfile() {
        const mobileView = window.matchMedia('(hover: none), (max-width: 768px)').matches;

        if (!mobileView) {
            return {
                mode: 'full',
                score: 0,
                mobile: false
            };
        }

        const memory = Number(navigator.deviceMemory || 0);
        const cores = Number(navigator.hardwareConcurrency || 0);
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection || null;
        const effectiveType = String(connection?.effectiveType || '').toLowerCase();
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const userAgent = String(navigator.userAgent || '');
        const androidMajor = Number(userAgent.match(/Android\s+(\d+)/i)?.[1] || 0);
        /* Some Android browsers do not expose deviceMemory. Recognize the
           tested Oppo A16k family before the first map frame; faster phones,
           including the tested Vivo Y36, remain on balanced rendering. */
        const knownLowEndPhone = /(?:CPH2349|CPH2351|OPPO\s*A16K)/i.test(userAgent);
        let score = 0;

        try {
            if (window.sessionStorage.getItem('wayfinding-render-quality') === 'low') {
                score += 3;
            }
        } catch (error) {
            // Storage may be disabled in private/restricted browsers.
        }

        if (memory > 0 && memory <= 2) {
            score += 3;
        } else if (memory > 0 && memory <= 4) {
            score += 2;
        }

        if (cores > 0 && cores <= 2) {
            score += 3;
        } else if (cores > 0 && cores <= 4) {
            score += 2;
        }

        if (connection?.saveData) score += 2;
        if (['slow-2g', '2g'].includes(effectiveType)) score += 2;
        if (reducedMotion) score += 1;
        if (window.innerWidth <= 390) score += 1;
        if (knownLowEndPhone) score += 3;
        /* Chrome may omit both the phone model and deviceMemory. Android 11
           itself is therefore a useful conservative signal. Together with a
           narrow phone viewport it selects the low profile immediately,
           before the first indoor map is created. */
        if (androidMajor > 0 && androidMajor <= 11) score += 2;

        return {
            mode: score >= 3 ? 'low' : 'balanced',
            score,
            mobile: true
        };
    }

    function applyWayfindingRenderProfile(profile, reason = 'device') {
        const safeProfile = profile && ['full', 'balanced', 'low'].includes(profile.mode)
            ? profile
            : detectWayfindingRenderProfile();
        const body = document.body;

        if (body) {
            body.classList.remove(
                'render-quality-full',
                'render-quality-balanced',
                'render-quality-low'
            );
            body.classList.add(`render-quality-${safeProfile.mode}`);
            body.dataset.renderQuality = safeProfile.mode;
            body.dataset.renderQualityReason = reason;
        }

        window.wayfindingRenderProfile = {
            ...safeProfile,
            reason
        };

        window.dispatchEvent(new CustomEvent('wayfinding:render-profile', {
            detail: window.wayfindingRenderProfile
        }));

        return window.wayfindingRenderProfile;
    }

    const WAYFINDING_RENDER_PROFILE = applyWayfindingRenderProfile(
        detectWayfindingRenderProfile()
    );
    const IS_MOBILE_OUTDOOR_VIEW = WAYFINDING_RENDER_PROFILE.mobile;
    /*
    | Balanced mobile keeps both subtle depth polygons for a consistent 3D
    | silhouette. Only genuinely low-end devices omit the farther layer.
    */
    const SHOULD_RENDER_FAR_BUILDING_DEPTH = WAYFINDING_RENDER_PROFILE.mode !== 'low';
    /*
    | Do not force a fractional snap after a phone pinch. Leaflet can keep the
    | exact pinch scale, so releasing both fingers does not cause a small,
    | perceptible second zoom step.
    */
    const MOBILE_ZOOM_SNAP = 0;
    const MOBILE_STATIC_PATH_WIDTH_SCALE = WAYFINDING_RENDER_PROFILE.mode === 'low'
        ? 0.36
        : 0.42;
    /*
    | Retaining five tile rings can keep well over a hundred raster tiles alive
    | on a portrait phone. Two rings are enough for low-end devices and three
    | keep balanced phones protected from white edges during a normal swipe.
    */
    const MOBILE_TILE_KEEP_BUFFER = WAYFINDING_RENDER_PROFILE.mode === 'low'
        ? 2
        : 3;

    window.detectWayfindingRenderProfile = detectWayfindingRenderProfile;
    window.applyWayfindingRenderProfile = applyWayfindingRenderProfile;
    const MOBILE_OUTDOOR_MIN_ZOOM_VALUE = 17.25;
    const MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE = 17.75;
    const MOBILE_OUTDOOR_ROUTE_ZOOM_VALUE = 17.25;
    const MOBILE_OUTDOOR_MAX_ZOOM_VALUE = 19;
    /*
    | A full-screen padding value of 1 created SVG surfaces around 3x the
    | viewport width and height on phones. A 0.65 buffer still covers a fast
    | swipe beyond the screen while substantially reducing mobile GPU work.
    */
    const OUTDOOR_VECTOR_RENDER_PADDING = IS_MOBILE_OUTDOOR_VIEW
        ? (WAYFINDING_RENDER_PROFILE.mode === 'low' ? 0.28 : 0.65)
        : 0.5;
    const MOBILE_PATH_CANVAS_PADDING = WAYFINDING_RENDER_PROFILE.mode === 'low'
        ? 0.16
        : 0.35;
    /* A 1x Canvas is fast but visibly soft when Android stretches it across a
       2x-3x DPR display. Only the clickable building tops receive a bounded
       1.5x backing store; paths and depth remain at the original 1x budget. */
    const LOW_END_BUILDING_CANVAS_RATIO = Math.min(
        1.5,
        Math.max(1, Number(window.devicePixelRatio || 1))
    );
    const WayfindingLowResolutionCanvas = L.Canvas.extend({
        _update: function() {
            if (this._map._animatingZoom && this._bounds) return;
            L.Renderer.prototype._update.call(this);
            const bounds = this._bounds;
            const container = this._container;
            const size = bounds.getSize();
            const pixelRatio = Math.max(
                1,
                Number(this.options.wayfindingPixelRatio || 1)
            );
            L.DomUtil.setPosition(container, bounds.min);
            container.width = Math.max(1, Math.ceil(size.x * pixelRatio));
            container.height = Math.max(1, Math.ceil(size.y * pixelRatio));
            container.style.width = `${size.x}px`;
            container.style.height = `${size.y}px`;
            if (pixelRatio !== 1) this._ctx.scale(pixelRatio, pixelRatio);
            this._ctx.translate(-bounds.min.x, -bounds.min.y);
            this.fire('update');
        }
    });
    const createOutdoorCanvasRenderer = options => WAYFINDING_RENDER_PROFILE.mode === 'low'
        ? new WayfindingLowResolutionCanvas(options)
        : L.canvas(options);
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
        ? createOutdoorCanvasRenderer({
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
    /*
    | Canvas paths do not respond to the SVG `transform` rules that previously
    | created the fixed-pixel building extrusion. Draw every depth polygon with
    | its own lightweight pixel offset inside one shared Canvas instead. This
    | preserves the two-layer futuristic silhouette without CSS filters or a
    | large SVG DOM, and the apparent depth stays stable after every zoom.
    */
    const WayfindingBuildingDepthCanvasBase = WAYFINDING_RENDER_PROFILE.mode === 'low'
        ? WayfindingLowResolutionCanvas
        : L.Canvas;
    const WayfindingBuildingDepthCanvas = WayfindingBuildingDepthCanvasBase.extend({
        _updatePoly(layer, closed) {
            if (!this._drawing) return;

            const parts = layer._parts || [];
            if (!parts.length) return;

            const ctx = this._ctx;
            const baseOffset = Number(layer.options?.depthPixelOffset || 0);
            const zoom = Number(this._map?.getZoom?.() || MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE);
            const zoomRange = Math.max(
                0.01,
                MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE - MOBILE_OUTDOOR_MIN_ZOOM_VALUE
            );
            const zoomProgress = Math.max(
                0,
                Math.min(
                    1,
                    (zoom - MOBILE_OUTDOOR_MIN_ZOOM_VALUE) / zoomRange
                )
            );
            /*
             * At the farthest campus overview, depth is only 45% so small
             * buildings never look bulky. It reaches the normal restrained
             * depth at the default zoom and stays stable during close zooms.
             */
            const offset = baseOffset * (0.45 + (0.55 * zoomProgress));
            ctx.beginPath();

            parts.forEach(part => {
                part.forEach((point, index) => {
                    ctx[index ? 'lineTo' : 'moveTo'](
                        point.x + offset,
                        point.y + offset
                    );
                });
                if (closed) ctx.closePath();
            });

            this._fillStroke(ctx, layer);
        }
    });
    const OUTDOOR_BUILDING_DEPTH_RENDERER = IS_MOBILE_OUTDOOR_VIEW
        ? new WayfindingBuildingDepthCanvas({
            pane: 'buildingDepthPane',
            padding: MOBILE_PATH_CANVAS_PADDING,
            tolerance: 0
        })
        : L.svg({
            pane: 'buildingDepthPane',
            padding: OUTDOOR_VECTOR_RENDER_PADDING
        });
    /* The clickable top polygons used to remain SVG on every phone. That is
       useful on desktop, but an Oppo-class GPU must transform the complete SVG
       tree on every pinch frame. Low-end phones keep the exact polygons and
       Leaflet hit detection in one Canvas; balanced phones retain crisp SVG. */
    const OUTDOOR_BUILDINGS_RENDERER = WAYFINDING_RENDER_PROFILE.mode === 'low'
        ? createOutdoorCanvasRenderer({
            pane: 'buildingsPane',
            padding: MOBILE_PATH_CANVAS_PADDING,
            tolerance: 5,
            wayfindingPixelRatio: LOW_END_BUILDING_CANVAS_RATIO
        })
        : L.svg({
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
        zoomSnap: IS_MOBILE_OUTDOOR_VIEW ? MOBILE_ZOOM_SNAP : 1,
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
        | Pinch geometry and building depth update live. Keep Leaflet's final
        | settle animation off so releasing both fingers responds immediately;
        | the small fractional zoom snap already prevents a large visual jump.
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
        keepBuffer: IS_MOBILE_OUTDOOR_VIEW ? MOBILE_TILE_KEEP_BUFFER : 5
    }).addTo(map);

    function createWayfindingInteractionController(mapInstance) {
        const callbacks = new Map();
        const lifecycleCallbacks = new Map();
        const body = document.body;
        let settleFrame = null;
        let pendingFlush = false;
        const activeInteractions = new Set();

        function interactionName(type = '') {
            return String(type).replace(/(?:start|end)$/, '');
        }

        function syncInteractionClasses() {
            const active = activeInteractions.size > 0;
            body?.classList.toggle('map-moving', active);
            body?.classList.toggle('map-zooming', activeInteractions.has('zoom'));
            body?.classList.toggle('map-dragging', activeInteractions.has('drag'));
        }

        function beginInteraction(event) {
            const wasActive = activeInteractions.size > 0;
            const name = interactionName(event?.type);
            if (name) activeInteractions.add(name);
            if (settleFrame) {
                cancelAnimationFrame(settleFrame);
                settleFrame = null;
            }
            syncInteractionClasses();
            if (!wasActive && activeInteractions.size > 0) {
                lifecycleCallbacks.forEach(callbacksForKey => {
                    callbacksForKey.start?.(event);
                });
            }
        }

        function flushCallbacks() {
            if (activeInteractions.size > 0) {
                pendingFlush = true;
                return;
            }
            if (settleFrame) cancelAnimationFrame(settleFrame);
            settleFrame = requestAnimationFrame(() => {
                settleFrame = null;
                pendingFlush = false;
                callbacks.forEach(callback => callback());
            });
        }

        function endInteraction(event) {
            const name = interactionName(event?.type);
            if (name) activeInteractions.delete(name);
            syncInteractionClasses();
            if (activeInteractions.size > 0) return;

            lifecycleCallbacks.forEach(callbacksForKey => {
                callbacksForKey.end?.(event);
            });

            // One paint after the final Leaflet gesture event. No delayed
            // timeout means releasing a pinch cannot create a second jump.
            if (pendingFlush || callbacks.size > 0) flushCallbacks();
        }

        mapInstance.on('movestart zoomstart dragstart', beginInteraction);
        mapInstance.on('moveend zoomend dragend', endInteraction);
        mapInstance.on('viewreset resize', flushCallbacks);

        return Object.freeze({
            register(key, callback) {
                if (!key || typeof callback !== 'function') return;
                callbacks.set(String(key), callback);
            },
            unregister(key) {
                callbacks.delete(String(key));
            },
            registerLifecycle(key, lifecycle = {}) {
                if (!key || (!lifecycle.start && !lifecycle.end)) return;
                lifecycleCallbacks.set(String(key), lifecycle);
            },
            unregisterLifecycle(key) {
                lifecycleCallbacks.delete(String(key));
            },
            schedule: flushCallbacks,
            isActive() {
                return activeInteractions.size > 0;
            },
            activeTypes() {
                return new Set(activeInteractions);
            }
        });
    }

    const wayfindingInteraction = createWayfindingInteractionController(map);
    window.WayfindingInteraction = wayfindingInteraction;

    function updateMobileBuildingDepthScale() {
        if (!IS_MOBILE_OUTDOOR_VIEW || typeof map === 'undefined' || !map) {
            return;
        }

        const zoomRange = Math.max(
            0.01,
            MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE - MOBILE_OUTDOOR_ROUTE_ZOOM_VALUE
        );
        const zoomProgress = Math.max(
            0,
            Math.min(
                1,
                (map.getZoom() - MOBILE_OUTDOOR_ROUTE_ZOOM_VALUE) / zoomRange
            )
        );

        /*
        | At the campus overview the depth is 45% of its normal mobile
        | size. It grows gradually to full depth by the default zoom, preventing
        | small buildings from looking bulky without flattening close-up views.
        */
        const depthScale = 0.45 + (0.55 * zoomProgress);
        const round2 = (value) => Math.round(value * 100) / 100;

        document.documentElement.style.setProperty('--mobile-side-1', `${round2(1.2 * depthScale)}px`);
        document.documentElement.style.setProperty('--mobile-side-2', `${round2(2.5 * depthScale)}px`);
        document.documentElement.style.setProperty('--mobile-side-3', `${round2(2.5 * depthScale)}px`);
        document.documentElement.style.setProperty(
            '--mobile-edge-width',
            `${round2(0.78 + (0.32 * zoomProgress))}`
        );
    }

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

        updateMobileBuildingDepthScale();

        if (typeof updateBuildingPerformanceMode === 'function') {
            updateBuildingPerformanceMode();
        }
    }
    wayfindingInteraction.register('building-depth-and-route-popup', () => {
        updateShadows();
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
