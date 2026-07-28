import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { Script } from 'node:vm';
import test from 'node:test';
import postcss from 'postcss';

const javascriptModules = [
    '01-map-core.js',
    '02-map-data-ui.js',
    '03-outdoor-routing.js',
    '04-indoor-routing.js',
    '05-map-rendering.js',
    '06-search-voice.js',
    '07-campus-events-data.js',
    '08-navigation-accessibility.js',
    '08-assistant-ui.js',
    '09-building-indoor-ui.js',
    '10-responsive-performance.js',
    '11-map-performance.js',
    '12-gps-tracking.js',
    '13-gps-diagnostics.js',
    '13-gps-simulator.js',
    '14-pwa-offline.js',
];

const cssComponents = [
    '01-foundation-map.css',
    '02-route-controls.css',
    '03-ai-search-voice.css',
    '04-indoor-navigation.css',
    '05-route-popup-effects.css',
    '06-path-picker-events-profile.css',
    '07-campus-theme.css',
    '08-panel-positioning.css',
    '09-map-performance.css',
    '10-campus-brand-route.css',
    '11-gps-rotation.css',
    '12-futuristic-theme.css',
    '13-navigation-accessibility.css',
    '13-gps-simulator.css',
    '14-gps-diagnostics.css',
    '15-pwa-offline.css',
    '16-user-friendly.css',
    '17-cr-navigation.css',
];

test('all wayfinding JavaScript modules have valid syntax', () => {
    javascriptModules.forEach((filename) => {
        const path = new URL(`../../public/js/wayfinding/${filename}`, import.meta.url);
        const source = readFileSync(path, 'utf8');

        assert.doesNotThrow(
            () => new Script(source, { filename }),
            `${filename} should contain valid JavaScript`,
        );
    });
});

test('all wayfinding CSS components parse successfully', () => {
    cssComponents.forEach((filename) => {
        const path = new URL(`../../public/css/wayfinding/${filename}`, import.meta.url);
        const source = readFileSync(path, 'utf8');

        assert.doesNotThrow(
            () => postcss.parse(source, { from: filename }),
            `${filename} should contain valid CSS`,
        );
        assert.equal(source.includes('<style>'), false);
        assert.equal(source.includes('</style>'), false);
    });
});

test('public futuristic theme parses successfully', () => {
    const path = new URL('../../public/css/futuristic-public.css', import.meta.url);
    const source = readFileSync(path, 'utf8');

    assert.doesNotThrow(
        () => postcss.parse(source, { from: 'futuristic-public.css' }),
        'futuristic-public.css should contain valid CSS',
    );
    assert.equal(source.includes('<style>'), false);
    assert.equal(source.includes('</style>'), false);
});

test('admin futuristic theme parses successfully', () => {
    const path = new URL(
        '../../public/admin/assets/css/futuristic-admin.css',
        import.meta.url,
    );
    const source = readFileSync(path, 'utf8');

    assert.doesNotThrow(
        () => postcss.parse(source, { from: 'futuristic-admin.css' }),
        'futuristic-admin.css should contain valid CSS',
    );
    assert.match(source, /--admin-primary:\s*#18375d/);
    assert.equal(source.includes('<style>'), false);
    assert.equal(source.includes('</style>'), false);
});

test('mobile outdoor map keeps a buffered render area during panning', () => {
    const mapCorePath = new URL(
        '../../public/js/wayfinding/01-map-core.js',
        import.meta.url,
    );
    const themePath = new URL(
        '../../public/css/wayfinding/12-futuristic-theme.css',
        import.meta.url,
    );
    const mapCore = readFileSync(mapCorePath, 'utf8');
    const theme = readFileSync(themePath, 'utf8');

    const mapRenderingPath = new URL(
        '../../public/js/wayfinding/05-map-rendering.js',
        import.meta.url,
    );
    const outdoorRoutingPath = new URL(
        '../../public/js/wayfinding/03-outdoor-routing.js',
        import.meta.url,
    );
    const mapDataUiPath = new URL(
        '../../public/js/wayfinding/02-map-data-ui.js',
        import.meta.url,
    );
    const mapPerformancePath = new URL(
        '../../public/js/wayfinding/11-map-performance.js',
        import.meta.url,
    );
    const mapRendering = readFileSync(mapRenderingPath, 'utf8');
    const outdoorRouting = readFileSync(outdoorRoutingPath, 'utf8');
    const mapDataUi = readFileSync(mapDataUiPath, 'utf8');
    const mapPerformance = readFileSync(mapPerformancePath, 'utf8');

    assert.match(mapCore, /OUTDOOR_VECTOR_RENDER_PADDING\s*=\s*IS_MOBILE_OUTDOOR_VIEW\s*\?\s*1\s*:\s*0\.5/);
    assert.match(mapCore, /pane:\s*'pathsPane',\s*padding:\s*OUTDOOR_VECTOR_RENDER_PADDING/);
    assert.match(mapCore, /pane:\s*'buildingDepthPane',\s*padding:\s*OUTDOOR_VECTOR_RENDER_PADDING/);
    assert.match(mapCore, /pane:\s*'buildingsPane',\s*padding:\s*OUTDOOR_VECTOR_RENDER_PADDING/);
    assert.match(mapRendering, /pane:\s*'pathsPane',\s*renderer:\s*OUTDOOR_PATHS_RENDERER/);
    assert.match(mapRendering, /pane:\s*'buildingsPane',\s*renderer:\s*OUTDOOR_BUILDINGS_RENDERER/);
    assert.match(mapRendering, /casingColor:\s*'#b8c1ca'/);
    assert.match(mapRendering, /map-road-swatch/);
    assert.match(mapRendering, /mixColors\(sourceColor,\s*'#a1b9c9',\s*0\.36\)/);
    assert.match(mapRendering, /building-selected/);
    assert.match(mapRendering, /renderer:\s*OUTDOOR_BUILDING_DEPTH_RENDERER/);
    assert.match(mapRendering, /building-depth-solid-far/);
    assert.match(mapDataUi, /\.fake-3d-building\s*\{\s*filter:\s*none !important;/);
    assert.match(mapPerformance, /Static depth polygons stay visible/);
    assert.match(mapPerformance, /Zoom never activates a CSS filter/);
    assert.match(outdoorRouting, /polylineOptions\.renderer\s*=\s*OUTDOOR_ROUTE_RENDERER/);
    assert.match(outdoorRouting, /className:\s*'route-line-outline'/);
    assert.match(outdoorRouting, /dashArray:\s*null/);
    assert.match(mapCore, /updateWhenIdle:\s*IS_MOBILE_OUTDOOR_VIEW/);
    assert.match(mapCore, /updateWhenZooming:\s*!IS_MOBILE_OUTDOOR_VIEW/);
    assert.match(mapCore, /updateInterval:\s*IS_MOBILE_OUTDOOR_VIEW\s*\?\s*180\s*:\s*120/);
    assert.match(mapCore, /keepBuffer:\s*5/);
    assert.match(theme, /#map \.leaflet-tile-pane\s*\{\s*filter:\s*none !important;/);
});

test('GPS tracking code is contained in an executable JavaScript module', () => {
    const path = new URL(
        '../../public/js/wayfinding/12-gps-tracking.js',
        import.meta.url,
    );
    const source = readFileSync(path, 'utf8');

    assert.match(source, /function startOutdoorLiveGpsTracking\(\)/);
    assert.match(source, /window\.startOutdoorLiveGpsTracking/);
    assert.match(source, /function getOutdoorGpsProvider\(\)/);
    assert.match(source, /gpsProvider\.watchPosition/);
    assert.match(source, /GPS_QUALITY_LOCK_REQUIRED_SAMPLES\s*=\s*4/);
    assert.match(source, /GPS_QUALITY_LOCK_MAX_ACCURACY_M\s*=\s*20/);
    assert.match(source, /GPS_QUALITY_LOCK_MAX_SPREAD_M\s*=\s*10/);
    assert.match(source, /GPS_OFF_ROUTE_CONFIRMATION_SAMPLES\s*=\s*3/);
    assert.match(source, /function evaluateGpsQualityLock\(sample\)/);
    assert.match(source, /evaluateGpsQualitySamples/);
    assert.match(source, /function showGpsQualityLockStatus\(lockResult\)/);
    assert.match(source, /function emitGpsDiagnostic\(type, detail = \{\}\)/);
    assert.match(source, /wayfinding:gps-diagnostic/);
    assert.match(source, /GPS_CALIBRATION_THRESHOLDS/);
    assert.match(source, /window\.WayfindingGpsCalibration/);
    assert.match(source, /nextGpsOffRouteConfirmation/);
    assert.match(source, /let rawLatLng = qualityLock\.point \|\| latestSample\.latLng/);
    assert.match(source, /function refreshActiveRouteFromGps/);
    assert.match(source, /fitBounds: false/);
    assert.match(source, /function buildNavigationInstruction/);
    assert.match(source, /window\.getWayfindingGpsSimulatorRoute/);
    assert.match(source, /WayfindingNavigationUi\?\.updateGpsStatus/);
    assert.match(source, /WayfindingNavigationUi\?\.updateGuidance/);
});

test('unified navigation UI exposes route summary and non-blocking recovery feedback', () => {
    const navigationPath = new URL(
        '../../public/js/wayfinding/08-navigation-accessibility.js',
        import.meta.url,
    );
    const dashboardPath = new URL(
        '../../resources/views/user/dashboard.blade.php',
        import.meta.url,
    );
    const navigation = readFileSync(navigationPath, 'utf8');
    const dashboard = readFileSync(dashboardPath, 'utf8');

    assert.match(navigation, /function calculateRouteDistance\(latlngs\)/);
    assert.match(navigation, /function formatWalkTime\(meters\)/);
    assert.match(navigation, /function getRouteSafety\(result\)/);
    assert.match(navigation, /routeUiState\.started = true/);
    assert.match(navigation, /function togglePause\(\)/);
    assert.match(navigation, /window\.alert = function \(message\)/);
    assert.match(navigation, /window\.WayfindingDataStatus = dataStatus/);
    assert.match(dashboard, /id="navigation-sheet"/);
    assert.match(dashboard, /id="navigation-details-toggle"/);
    assert.match(dashboard, /id="navigation-details-toggle-label">Route</);
    assert.match(dashboard, /id="route-result-label"/);
    assert.match(dashboard, /id="wayfinding-toast-region"/);
    assert.match(dashboard, /aria-modal="true"/);
});

test('campus data loading is partial, retryable, and backed by saved responses', () => {
    const dataPath = new URL(
        '../../public/js/wayfinding/07-campus-events-data.js',
        import.meta.url,
    );
    const fetchPath = new URL(
        '../../public/js/wayfinding/06-search-voice.js',
        import.meta.url,
    );
    const dataSource = readFileSync(dataPath, 'utf8');
    const fetchSource = readFileSync(fetchPath, 'utf8');

    assert.match(dataSource, /Promise\.allSettled/);
    assert.match(dataSource, /essentialDefinitions/);
    assert.match(dataSource, /optionalDefinitions/);
    assert.match(dataSource, /window\.retryWayfindingData/);
    assert.match(dataSource, /WayfindingDataStatus\?\.partial/);
    assert.match(dataSource, /const hasActiveRoute = window\.WayfindingNavigationUi/);
    assert.match(dataSource, /if \(!hasActiveRoute\)/);
    assert.match(fetchSource, /WAYFINDING_RESPONSE_CACHE_PREFIX/);
    assert.match(fetchSource, /readLastKnownWayfindingResponse/);
    assert.match(fetchSource, /saveLastKnownWayfindingResponse/);
    assert.match(fetchSource, /__wayfindingStaleDataUrls\.add\(url\)/);
});

test('development GPS simulator drives the live GPS provider with campus route points', () => {
    const path = new URL(
        '../../public/js/wayfinding/13-gps-simulator.js',
        import.meta.url,
    );
    const source = readFileSync(path, 'utf8');

    assert.match(source, /WAYFINDING_GPS_SIMULATOR_ENABLED/);
    assert.match(source, /const ROUTE_WAYPOINTS = \[/);
    assert.match(source, /watchPosition\(success, error, options\)/);
    assert.match(source, /window\.startOutdoorLiveGpsTracking\(\)/);
    assert.match(source, /function chooseCustomStart\(\)/);
    assert.match(source, /WayfindingSimulatorBridge/);
    assert.match(source, /simulatorBridge\.isInsideCampus\(event\.latlng\.lat,\s*event\.latlng\.lng\)/);
    assert.match(source, /simulatorBridge\.nearestNodeKey\(\s*event\.latlng\.lat,\s*event\.latlng\.lng/);
    assert.match(source, /getWayfindingGpsSimulatorRoute\(\)/);
    assert.match(source, /Preset Walk/);
    assert.match(source, /Choose Start/);
    assert.match(source, /Start Walk/);
    assert.match(source, /Pause/);
    assert.match(source, /Reset/);
    assert.match(source, /1× Normal/);
    assert.match(source, /4× Demo/);
});

test('GPS diagnostics records locally and exports field-test measurements', () => {
    const path = new URL(
        '../../public/js/wayfinding/13-gps-diagnostics.js',
        import.meta.url,
    );
    const stylesPath = new URL(
        '../../public/css/wayfinding/14-gps-diagnostics.css',
        import.meta.url,
    );
    const source = readFileSync(path, 'utf8');
    const styles = readFileSync(stylesPath, 'utf8');

    assert.match(source, /wayfinding:gps-calibration-session:v1/);
    assert.match(source, /wayfinding:gps-diagnostic/);
    assert.match(source, /summarizeGpsCalibration/);
    assert.match(source, /function startRecording\(\)/);
    assert.match(source, /function exportCsv\(\)/);
    assert.match(source, /new Blob\(\[csv\]/);
    assert.match(source, /window\.WayfindingGpsDiagnostics/);
    assert.match(styles, /\.gps-diagnostics-panel/);
    assert.match(styles, /min-height:\s*44px/);
});

test('user map stays north-up without rotation controls or listeners', () => {
    const dashboardPath = new URL(
        '../../resources/views/user/dashboard.blade.php',
        import.meta.url,
    );
    const mapCorePath = new URL(
        '../../public/js/wayfinding/01-map-core.js',
        import.meta.url,
    );
    const rotationStylesPath = new URL(
        '../../public/css/wayfinding/11-gps-rotation.css',
        import.meta.url,
    );
    const dashboard = readFileSync(dashboardPath, 'utf8');
    const mapCore = readFileSync(mapCorePath, 'utf8');
    const rotationStyles = readFileSync(rotationStylesPath, 'utf8');
    const removedRotationIdentifiers = /campus-rotate|campus-rotated|rotateMapLeft|rotateMapRight|resetMapRotation|currentCampusMapBearing|normalizeCampusBearing/;

    assert.doesNotMatch(dashboard, removedRotationIdentifiers);
    assert.doesNotMatch(mapCore, removedRotationIdentifiers);
    assert.doesNotMatch(rotationStyles, removedRotationIdentifiers);
});

test('PWA assets provide installable metadata and privacy-safe caching', () => {
    const serviceWorkerPath = new URL('../../public/sw.js', import.meta.url);
    const manifestPath = new URL('../../public/manifest.webmanifest', import.meta.url);
    const registrationPath = new URL(
        '../../public/js/wayfinding/14-pwa-offline.js',
        import.meta.url,
    );
    const serviceWorker = readFileSync(serviceWorkerPath, 'utf8');
    const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
    const registration = readFileSync(registrationPath, 'utf8');

    assert.doesNotThrow(
        () => new Script(serviceWorker, { filename: 'sw.js' }),
        'sw.js should contain valid JavaScript',
    );
    assert.equal(manifest.start_url, '/user/dashboard?source=pwa');
    assert.equal(manifest.display, 'standalone');
    assert.equal(manifest.theme_color, '#18375d');
    assert.deepEqual(
        manifest.icons.map(icon => icon.sizes),
        ['192x192', '512x512'],
    );
    assert.match(serviceWorker, /CACHEABLE_DATA_PATHS/);
    assert.match(serviceWorker, /fetch\('\/build\/manifest\.json'/);
    assert.match(serviceWorker, /function collectViteAssetUrls\(manifest\)/);
    assert.match(serviceWorker, /assetUrls\.map\(url => cache\.add\(url\)/);
    assert.match(serviceWorker, /\/css\/wayfinding\/17-cr-navigation\.css/);
    assert.match(serviceWorker, /\/js\/wayfinding\/15-cr-navigation\.js/);
    assert.match(serviceWorker, /request\.mode === 'navigate'/);
    assert.match(serviceWorker, /caches\.match\(OFFLINE_URL\)/);
    assert.match(serviceWorker, /url\.origin !== self\.location\.origin/);
    assert.doesNotMatch(serviceWorker, /tile\.openstreetmap\.org/);
    assert.doesNotMatch(serviceWorker, /PRECACHE_URLS[\s\S]*\/user\/dashboard/);
    assert.match(registration, /beforeinstallprompt/);
    assert.match(registration, /navigator\.serviceWorker\.register/);
    assert.match(registration, /updateViaCache:\s*'none'/);
    assert.match(registration, /window\.WayfindingPwa/);
});

test('CR navigator ranks reachable restrooms and supports every start mode', () => {
    const dashboardPath = new URL(
        '../../resources/views/user/dashboard.blade.php',
        import.meta.url,
    );
    const modulePath = new URL(
        '../../public/js/wayfinding/15-cr-navigation.js',
        import.meta.url,
    );
    const stylesPath = new URL(
        '../../public/css/wayfinding/17-cr-navigation.css',
        import.meta.url,
    );
    const indoorRoutingPath = new URL(
        '../../public/js/wayfinding/04-indoor-routing.js',
        import.meta.url,
    );
    const vitePath = new URL('../../vite.config.js', import.meta.url);
    const dashboard = readFileSync(dashboardPath, 'utf8');
    const module = readFileSync(modulePath, 'utf8');
    const styles = readFileSync(stylesPath, 'utf8');
    const indoorRouting = readFileSync(indoorRoutingPath, 'utf8');
    const vite = readFileSync(vitePath, 'utf8');

    assert.match(dashboard, /id="cr-navigation-toggle"/);
    assert.match(dashboard, /id="cr-navigation-modal"/);
    assert.match(dashboard, /data-cr-mode="gps"/);
    assert.match(dashboard, /data-cr-mode="path"/);
    assert.match(dashboard, /data-cr-mode="default"/);
    assert.match(dashboard, /asset\('js\/wayfinding\/15-cr-navigation\.js'\)/);
    assert.match(dashboard, /filemtime\(public_path\('js\/wayfinding\/15-cr-navigation\.js'\)\)/);
    assert.match(module, /function normalizeRoomClassification\(value\)/);
    assert.match(module, /function isComfortRoom\(room\)/);
    assert.match(module, /roomType\.includes\('restroom'\)/);
    assert.match(module, /Type is authoritative\. Room codes are labels only/);
    assert.match(module, /bridge\?\.estimateRoom\?\.\(room\)/);
    assert.match(module, /ranked\.push\(\{ room, estimate \}\)/);
    assert.match(module, /const CR_INDOOR_DISTANCE_SCALE = 0\.16/);
    assert.match(module, /const CR_CLOSEST_RANGE_METERS = 25/);
    assert.match(module, /const CR_NEARBY_RANGE_METERS = 100/);
    assert.match(module, /function estimatedWalkingDistance\(item\)/);
    assert.match(module, /function distanceTier\(item, index, nearestDistance\)/);
    assert.match(module, /estimatedWalkingDistance\(a\) - estimatedWalkingDistance\(b\)/);
    assert.doesNotMatch(module, /\.slice\(0,\s*6\)/);
    assert.match(module, /badge\.textContent[\s\S]*\? 'Nearest CR'/);
    assert.match(module, /Route unavailable/);
    assert.match(module, /Code \$\{roomCode\(item\.room\)\}/);
    assert.match(module, /bridge\?\.routeToRoom\?\.\(item\.room\)/);
    assert.match(vite, /window\.WayfindingCrBridge = Object\.freeze/);
    assert.match(vite, /findBestEntranceLinkForRoom\(room\)/);
    assert.match(vite, /computeCompleteRouteToRoom\(room\)/);
    assert.match(indoorRouting, /MAIN_ENTRANCE_TIE_METERS = 15/);
    assert.match(indoorRouting, /roomFloor <= 1/);
    assert.match(indoorRouting, /selectBestEntranceCandidate/);
    assert.match(indoorRouting, /INDOOR_WEIGHT,\s*MAIN_ENTRANCE_TIE_METERS/);
    assert.match(styles, /\.cr-navigation-item\.is-nearest/);
    assert.match(styles, /\.cr-navigation-item\.is-close-range/);
    assert.match(styles, /\.cr-navigation-item\.is-nearby-range/);
    assert.match(styles, /\.cr-navigation-close[\s\S]*place-items:\s*center/);
});

test('simple user mode keeps route cards readable and technical GPS tools optional', () => {
    const stylesPath = new URL(
        '../../public/css/wayfinding/16-user-friendly.css',
        import.meta.url,
    );
    const dashboardPath = new URL(
        '../../resources/views/user/dashboard.blade.php',
        import.meta.url,
    );
    const navigationPath = new URL(
        '../../public/js/wayfinding/08-navigation-accessibility.js',
        import.meta.url,
    );
    const indoorPath = new URL(
        '../../public/js/wayfinding/09-building-indoor-ui.js',
        import.meta.url,
    );
    const styles = readFileSync(stylesPath, 'utf8');
    const dashboard = readFileSync(dashboardPath, 'utf8');
    const navigation = readFileSync(navigationPath, 'utf8');
    const indoor = readFileSync(indoorPath, 'utf8');

    assert.match(dashboard, /\$gpsDiagnosticsEnabled/);
    assert.match(dashboard, /Tap Use GPS when ready/);
    assert.match(dashboard, /@if\(\$gpsDiagnosticsEnabled\)/);
    assert.match(navigation, /classList\.toggle\('is-started'/);
    assert.match(navigation, /routeUiState\.started = true/);
    assert.match(navigation, /function collapseRouteDetails\(\)/);
    assert.match(navigation, /New route selected\. Navigation updated automatically\./);
    assert.match(indoor, /route-building-map-popup-custom-close, \.leaflet-popup-close-button/);
    assert.match(indoor, /function keepRouteBuildingPopupOnScreen\(\)/);
    assert.match(indoor, /popupRect\.left < safeLeft/);
    assert.match(indoor, /maxWidth:\s*isMobileIndoorPopup \? 286 : 292/);
    assert.match(styles, /\.navigation-sheet-body[\s\S]*overflow-y:\s*auto/);
    assert.match(styles, /--route-popup-scale:\s*1\s*!important/);
    assert.match(styles, /route-building-map-popup-custom-close[\s\S]*width:\s*36px\s*!important/);
    assert.match(styles, /\.navigation-destination[\s\S]*-webkit-line-clamp:\s*2/);
    assert.match(styles, /\.navigation-details-toggle[\s\S]*left:\s*14px/);
    assert.match(styles, /\.navigation-sheet\.is-collapsed[\s\S]*display:\s*none\s*!important/);
});
