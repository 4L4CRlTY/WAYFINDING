import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

test('dashboard uses the two production wayfinding entries', () => {
    const dashboard = readFileSync(
        new URL('../../resources/views/user/dashboard.blade.php', import.meta.url),
        'utf8',
    );

    assert.match(dashboard, /@vite\('resources\/css\/wayfinding\.css'\)/);
    assert.match(dashboard, /@vite\('resources\/js\/wayfinding-entry\.js'\)/);
    assert.doesNotMatch(dashboard, /@include\('user\.(?:style|script)\./);
    assert.doesNotMatch(dashboard, /js\/wayfinding\/(?:0[1-9]|1[0-2])-/);
    assert.doesNotMatch(dashboard, /unpkg\.com\/leaflet/);
    assert.doesNotMatch(dashboard, /fonts\.googleapis\.com/);
});

test('wayfinding core preserves routing order and account features are lazy chunks', () => {
    const viteConfig = readFileSync(
        new URL('../../vite.config.js', import.meta.url),
        'utf8',
    );
    const expectedOrder = [
        'public/js/wayfinding-routing.js',
        'public/js/wayfinding/01-map-core.js',
        'public/js/wayfinding/02-map-data-ui.js',
        'public/js/wayfinding/03-outdoor-routing.js',
        'public/js/wayfinding/04-indoor-routing.js',
        'public/js/wayfinding/05-map-rendering.js',
        'public/js/wayfinding/06-search-voice.js',
        'public/js/wayfinding/07-campus-events-data.js',
        'public/js/wayfinding/08-navigation-accessibility.js',
        'public/js/wayfinding/09-building-indoor-ui.js',
        'public/js/wayfinding/10-responsive-performance.js',
        'public/js/wayfinding/14-pwa-offline.js',
    ];

    let previousIndex = -1;
    for (const source of expectedOrder) {
        const index = viteConfig.indexOf(`'${source}'`);
        assert.ok(index > previousIndex, `${source} must keep its runtime order`);
        previousIndex = index;
    }

    assert.match(viteConfig, /import Leaflet from 'leaflet'/);
    assert.match(viteConfig, /window\.L = Leaflet/);
    assert.match(viteConfig, /'virtual:wayfinding-assistant'/);
    assert.match(viteConfig, /'virtual:wayfinding-gps'/);
    assert.match(viteConfig, /'virtual:wayfinding-gps-diagnostics'/);
    assert.doesNotMatch(viteConfig, /public\/js\/wayfinding\/11-map-performance\.js/);

    const entry = readFileSync(
        new URL('../../resources/js/wayfinding-entry.js', import.meta.url),
        'utf8',
    );
    assert.match(entry, /if \(!guestMode\)/);
    assert.match(entry, /import\('\.\/wayfinding-assistant-entry\.js'\)/);
    assert.match(entry, /import\('\.\/wayfinding-gps-entry\.js'\)/);
    assert.match(entry, /import\('\.\/wayfinding-gps-diagnostics-entry\.js'\)/);
    assert.match(entry, /window\.selectGpsMode = lazyGpsMode/);
    assert.match(entry, /\['openInlineTextSearch', 'openInlineVoiceSearch'\]/);
    assert.match(entry, /window\.preloadWayfindingSearchIndex\?\.\(\)/);
    assert.match(entry, /if \(constrainedConnection\) return/);
    assert.match(entry, /if \(isMobileViewport\)[\s\S]*setTimeout[\s\S]*requestIdleCallback/);
    assert.match(entry, /requestIdleCallback\(idlePreloadAssistant/);
    assert.doesNotMatch(
        entry,
        /requestIdleCallback\(idlePreloadSearch, \{ timeout: 6000 \}\)/,
    );
    assert.doesNotMatch(
        entry,
        /const assistantFunctions[\s\S]*searchTextDestination/,
    );

    const coreData = readFileSync(
        new URL('../../public/js/wayfinding/07-campus-events-data.js', import.meta.url),
        'utf8',
    );
    const assistant = readFileSync(
        new URL('../../public/js/wayfinding/08-assistant-ui.js', import.meta.url),
        'utf8',
    );
    assert.match(coreData, /loadAllData\(\)\.catch/);
    assert.doesNotMatch(assistant, /loadAllData\(\)\.catch/);
});

test('mobile assistant has one search wrapper and dismisses completed searches', () => {
    const assistant = readFileSync(
        new URL('../../public/js/wayfinding/08-assistant-ui.js', import.meta.url),
        'utf8',
    );
    const dashboard = readFileSync(
        new URL('../../resources/views/user/dashboard.blade.php', import.meta.url),
        'utf8',
    );

    assert.equal((assistant.match(/searchTextDestination\s*=\s*async function/g) || []).length, 1);
    assert.equal((assistant.match(/initVoiceRecognition\(\);/g) || []).length, 1);
    assert.doesNotMatch(assistant, /initVoiceRecognition\s*=\s*function/);
    assert.doesNotMatch(assistant, /keepTextPanelOpen|keepVoicePanelOpen|voice-finished|search-finished/);
    assert.match(assistant, /assistantSearchRunning/);
    assert.match(assistant, /showRouteReadyConfirmation/);
    assert.match(assistant, /window\.visualViewport\?\.addEventListener\('resize', updateAssistantKeyboardPosition/);
    assert.match(assistant, /assistant-keyboard-open/);
    assert.match(assistant, /const keyboardIsOpen = mobileInput && \([\s\S]*inputFocused/);
    assert.match(assistant, /visibleTop \+ visibleHeight - panelHeight - 8/);
    assert.match(assistant, /mountAssistantPanelsAtViewportRoot/);
    assert.match(assistant, /document\.body\.appendChild\(panel\)/);
    assert.match(assistant, /input\?\.blur\(\)/);
    assert.match(dashboard, /id="ai-route-confirmation"/);
    assert.match(dashboard, /id="ai-search-progress"/);
    assert.doesNotMatch(dashboard, /id="ai-(?:text|voice)-result-card"/);
});

test('final mobile GPU budget overrides theme blur and dormant animations', () => {
    const css = readFileSync(
        new URL('../../public/css/wayfinding/17-mobile-gpu-budget.css', import.meta.url),
        'utf8',
    );

    assert.match(css, /\.ai-transform-panel,[\s\S]*backdrop-filter:\s*none\s*!important/);
    assert.match(css, /\.floating-main-pin \.pin-disc,[\s\S]*animation:\s*none\s*!important/);
    assert.match(css, /\.ai-voice-orb span[\s\S]*animation:\s*none\s*!important/);
    assert.match(css, /#ai-voice-panel\.is-listening \.ai-voice-orb span[\s\S]*voiceWave/);
    assert.match(css, /body\.assistant-keyboard-open #ai-search-panel[\s\S]*--assistant-keyboard-top/);
    assert.match(css, /#floating-route-ui\s*\{[\s\S]*position:\s*fixed\s*!important[\s\S]*top:\s*auto\s*!important[\s\S]*bottom:\s*max\(8px,/);
    assert.match(css, /#floating-route-ui \.floating-start-bar[\s\S]*grid-template-columns:\s*repeat\(3,/);
    assert.match(css, /body\.assistant-keyboard-open #ai-search-panel[\s\S]*left:\s*10px\s*!important[\s\S]*transform:\s*none\s*!important/);
    assert.match(css, /#map \.leaflet-control-container,[\s\S]*#indoorMap \.leaflet-control-container[\s\S]*contain:\s*none\s*!important[\s\S]*transform:\s*none\s*!important/);
    assert.doesNotMatch(css, /map-moving #map \.leaflet-control-container[\s\S]*contain:\s*layout/);
    assert.match(css, /#cr-navigation-toggle\s*\{[\s\S]*right:\s*max\(7px,[\s\S]*left:\s*auto\s*!important/);
    assert.match(css, /#navigation-details-toggle,[\s\S]*#cr-navigation-toggle\s*\{[\s\S]*position:\s*fixed\s*!important[\s\S]*bottom:\s*calc\(92px/);
    assert.match(css, /body:not\(\.indoor-open\) #map \.leaflet-control-zoom[\s\S]*visibility:\s*visible\s*!important/);

    const entryCss = readFileSync(
        new URL('../../resources/css/wayfinding.css', import.meta.url),
        'utf8',
    );
    assert.ok(
        entryCss.trimEnd().endsWith('@import "../../public/css/wayfinding/17-mobile-gpu-budget.css";'),
        'mobile GPU budget must stay last so theme rules cannot restore expensive effects',
    );
});

test('mobile dragging uses one lightweight map interaction controller', () => {
    const mapCore = readFileSync(
        new URL('../../public/js/wayfinding/01-map-core.js', import.meta.url),
        'utf8',
    );
    const responsivePerformance = readFileSync(
        new URL('../../public/js/wayfinding/10-responsive-performance.js', import.meta.url),
        'utf8',
    );
    const mapDataUi = readFileSync(
        new URL('../../public/js/wayfinding/02-map-data-ui.js', import.meta.url),
        'utf8',
    );

    assert.match(mapCore, /function createWayfindingInteractionController\(mapInstance\)/);
    assert.match(mapCore, /mapInstance\.on\('movestart zoomstart dragstart', beginInteraction\)/);
    assert.match(mapCore, /mapInstance\.on\('moveend zoomend dragend', endInteraction\)/);
    assert.match(mapCore, /requestAnimationFrame/);
    assert.match(mapCore, /const activeInteractions = new Set\(\)/);
    assert.match(mapCore, /classList\.toggle\('map-moving', active\)/);
    assert.match(mapCore, /classList\.toggle\('map-zooming', activeInteractions\.has\('zoom'\)\)/);
    assert.doesNotMatch(mapCore, /settleTimer/);
    assert.doesNotMatch(responsivePerformance, /leaflet-buildingsPane-pane/);
    assert.doesNotMatch(mapDataUi, /mapInstance\.on\('zoom move', this\._queueUpdate\)/);
    assert.doesNotMatch(responsivePerformance, /map\.on\('(?:move|zoom|drag)/);
});

test('low-end indoor map uses a one-pixel canvas budget without changing geometry', () => {
    const indoorRouting = readFileSync(
        new URL('../../public/js/wayfinding/04-indoor-routing.js', import.meta.url),
        'utf8',
    );

    assert.match(indoorRouting, /function createIndoorVectorRenderer\(lowEndIndoorView\)/);
    assert.match(indoorRouting, /container\.width\s*=\s*Math\.max\(1, Math\.ceil\(size\.x\)\)/);
    assert.match(indoorRouting, /container\.height\s*=\s*Math\.max\(1, Math\.ceil\(size\.y\)\)/);
    assert.match(indoorRouting, /__wayfindingVectorPixelRatio\s*=\s*lowEndIndoorView \? 1/);
    assert.match(indoorRouting, /const indoorGraphCache = new Map\(\)/);
});

test('mobile search releases its panel before starting route work', () => {
    const searchVoice = readFileSync(
        new URL('../../public/js/wayfinding/06-search-voice.js', import.meta.url),
        'utf8',
    );

    assert.match(searchVoice, /closeTextSearchModal\(\);[\s\S]*requestAnimationFrame\(\(\) => window\.setTimeout\(resolve, 0\)\)[\s\S]*applyTextSearchDestination/);
    assert.match(searchVoice, /clearWayfindingIndoorGraphCache\?\.\(normalizedBuildingId\)/);
});

test('mobile route glow and tile churn are disabled only during interaction', () => {
    const performanceCss = readFileSync(
        new URL('../../public/css/wayfinding/09-map-performance.css', import.meta.url),
        'utf8',
    );
    const outdoorRouting = readFileSync(
        new URL('../../public/js/wayfinding/03-outdoor-routing.js', import.meta.url),
        'utf8',
    );
    const mapCore = readFileSync(
        new URL('../../public/js/wayfinding/01-map-core.js', import.meta.url),
        'utf8',
    );

    assert.match(performanceCss, /body\.map-moving \.route-line-live,/);
    assert.match(performanceCss, /body\.map-moving \.route-line-outline,/);
    assert.match(performanceCss, /body\.map-dragging \.leaflet-popup-pane,/);
    assert.match(performanceCss, /shape-rendering:\s*optimizeSpeed !important;/);
    assert.doesNotMatch(performanceCss, /body\.map-moving body\.map-moving/);
    assert.match(outdoorRouting, /wayfindingInteraction\.register\('pick-path-helper'/);
    assert.doesNotMatch(outdoorRouting, /map\.on\('moveend zoomend'/);
    assert.match(mapCore, /updateWhenIdle:\s*IS_MOBILE_OUTDOOR_VIEW/);
    assert.match(mapCore, /updateWhenZooming:\s*!IS_MOBILE_OUTDOOR_VIEW/);
    assert.match(mapCore, /const MOBILE_TILE_KEEP_BUFFER[\s\S]*?\? 2[\s\S]*?: 3/);
    assert.match(mapCore, /keepBuffer:\s*IS_MOBILE_OUTDOOR_VIEW\s*\?\s*MOBILE_TILE_KEEP_BUFFER\s*:\s*5/);
    assert.match(
        mapCore,
        /const MOBILE_ZOOM_SNAP\s*=\s*0/,
    );
    assert.match(mapCore, /zoomSnap:\s*IS_MOBILE_OUTDOOR_VIEW\s*\?\s*MOBILE_ZOOM_SNAP\s*:\s*1/);
    assert.match(mapCore, /zoomAnimation:\s*false/);
    assert.match(mapCore, /markerZoomAnimation:\s*false/);
});

test('low-powered phones avoid path hit-testing and GPS redraws during manual drag', () => {
    const mapCore = readFileSync(
        new URL('../../public/js/wayfinding/01-map-core.js', import.meta.url),
        'utf8',
    );
    const mapRendering = readFileSync(
        new URL('../../public/js/wayfinding/05-map-rendering.js', import.meta.url),
        'utf8',
    );
    const gpsTracking = readFileSync(
        new URL('../../public/js/wayfinding/12-gps-tracking.js', import.meta.url),
        'utf8',
    );
    const performanceCss = readFileSync(
        new URL('../../public/css/wayfinding/09-map-performance.css', import.meta.url),
        'utf8',
    );
    assert.match(mapRendering, /interactive:\s*!IS_MOBILE_OUTDOOR_VIEW/);
    assert.match(mapCore, /OUTDOOR_PATHS_RENDERER\s*=\s*IS_MOBILE_OUTDOOR_VIEW/);
    assert.match(mapCore, /createOutdoorCanvasRenderer\(\{[\s\S]*?pane:\s*'pathsPane'/);
    assert.match(mapCore, /const OUTDOOR_ROUTE_RENDERER\s*=\s*L\.svg/);
    assert.match(gpsTracking, /WayfindingInteraction\?\.registerLifecycle\('gps-manual-drag'/);
    assert.doesNotMatch(gpsTracking, /map\.on\('dragstart'/);
    assert.match(gpsTracking, /pendingGpsRouteRefreshPosition/);
    assert.match(gpsTracking, /if \(gpsMapDragActive && !force\)/);
    assert.match(gpsTracking, /liveGpsFollow && smoothLatLng && !gpsMapDragActive/);
    assert.match(performanceCss, /\.path-interactive,[\s\S]*?filter:\s*none !important;[\s\S]*?pointer-events:\s*none !important;/);
    assert.match(performanceCss, /body\.map-dragging \.leaflet-buildings-pane/);
    assert.doesNotMatch(performanceCss, /\.leaflet-buildingsPane-pane svg/);
});

test('adaptive low-end rendering keeps one solid building depth layer', () => {
    const mapCore = readFileSync(
        new URL('../../public/js/wayfinding/01-map-core.js', import.meta.url),
        'utf8',
    );
    const mapRendering = readFileSync(
        new URL('../../public/js/wayfinding/05-map-rendering.js', import.meta.url),
        'utf8',
    );
    const responsivePerformance = readFileSync(
        new URL('../../public/js/wayfinding/10-responsive-performance.js', import.meta.url),
        'utf8',
    );
    const performanceCss = readFileSync(
        new URL('../../public/css/wayfinding/09-map-performance.css', import.meta.url),
        'utf8',
    );
    const themeCss = readFileSync(
        new URL('../../public/css/wayfinding/12-futuristic-theme.css', import.meta.url),
        'utf8',
    );

    assert.match(mapCore, /function detectWayfindingRenderProfile\(\)/);
    assert.match(mapCore, /navigator\.deviceMemory/);
    assert.match(mapCore, /navigator\.hardwareConcurrency/);
    assert.match(mapCore, /androidMajor\s*>\s*0\s*&&\s*androidMajor\s*<=\s*11\)\s*score\s*\+=\s*2/);
    assert.match(
        mapCore,
        /const SHOULD_RENDER_FAR_BUILDING_DEPTH\s*=\s*WAYFINDING_RENDER_PROFILE\.mode\s*!==\s*'low'/,
    );
    assert.match(
        mapCore,
        /const MOBILE_STATIC_PATH_WIDTH_SCALE\s*=\s*WAYFINDING_RENDER_PROFILE\.mode\s*===\s*'low'\s*\?\s*0\.36\s*:\s*0\.42/,
    );
    assert.match(mapCore, /function updateMobileBuildingDepthScale\(\)/);
    assert.match(mapCore, /const depthScale\s*=\s*0\.45\s*\+\s*\(0\.55\s*\*\s*zoomProgress\)/);
    assert.match(mapCore, /const MOBILE_OUTDOOR_MIN_ZOOM_VALUE\s*=\s*17\.25/);
    assert.match(mapCore, /const MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE\s*=\s*17\.75/);
    assert.match(mapCore, /const MOBILE_OUTDOOR_ROUTE_ZOOM_VALUE\s*=\s*17\.25/);
    assert.match(mapCore, /--mobile-side-1',\s*`\$\{round2\(1\.2\s*\*\s*depthScale\)\}px`/);
    assert.match(mapCore, /--mobile-side-2',\s*`\$\{round2\(2\.5\s*\*\s*depthScale\)\}px`/);
    assert.doesNotMatch(mapCore, /mobileDepthZoomFrame/);
    assert.doesNotMatch(mapCore, /map\.on\('zoom',\s*\(\)\s*=>\s*\{/);
    assert.doesNotMatch(responsivePerformance, /applyMobileOutdoorRouteZoomFinal\(\d+\)/);
    assert.doesNotMatch(responsivePerformance, /applyMobileOutdoorDefaultZoomFinal\(\d+\)/);
    assert.match(mapRendering, /if \(SHOULD_RENDER_FAR_BUILDING_DEPTH\)/);
    assert.match(mapCore, /const OUTDOOR_BUILDING_DEPTH_RENDERER\s*=\s*IS_MOBILE_OUTDOOR_VIEW[\s\S]*?new WayfindingBuildingDepthCanvas/);
    assert.match(
        mapCore,
        /const OUTDOOR_BUILDINGS_RENDERER\s*=\s*WAYFINDING_RENDER_PROFILE\.mode\s*===\s*'low'/,
    );
    assert.match(mapCore, /createOutdoorCanvasRenderer\(\{[\s\S]{0,140}pane:\s*'buildingsPane'/);
    assert.match(mapCore, /:\s*L\.svg\(\{[\s\S]{0,140}pane:\s*'buildingsPane'/);
    assert.match(mapRendering, /buildingFarDepthLayerGroup/);
    assert.match(mapRendering, /buildingNearDepthLayerGroup/);
    assert.match(mapRendering, /wayfinding:render-profile/);
    assert.match(mapRendering, /building-depth-solid-near/);
    assert.match(mapRendering, /function scaleStaticPathWeight\(/);
    assert.match(mapRendering, /weight:\s*scaleStaticPathWeight\(config\.casingWeight/);
    assert.match(responsivePerformance, /adaptiveLowEndPhoneRendering/);
    assert.match(responsivePerformance, /entryTypes:\s*\['longtask'\]/);
    assert.match(performanceCss, /body\.render-quality-low \.building-depth-solid-far/);
    assert.match(performanceCss, /body\.render-quality-low \.building-depth-solid-near/);
    assert.match(
        performanceCss,
        /body\.render-quality-low\.map-moving \.building-depth-solid-near/,
    );
    assert.doesNotMatch(
        performanceCss,
        /body\.render-quality-low \.building-depth-solid-near\s*\{[^}]*display:\s*none/s,
    );
    assert.match(
        themeCss,
        /body:not\(\.render-quality-low\) \.building-depth-solid-far\s*\{[\s\S]*?display:\s*block !important;/,
    );
    assert.match(
        themeCss,
        /\.building-depth-solid-near\s*\{[\s\S]*?var\(--mobile-side-1,\s*1\.2px\)/,
    );
    assert.match(
        themeCss,
        /body\.render-quality-low \.building-depth-solid-far\s*\{[\s\S]*?display:\s*none !important;/,
    );
});

test('mobile Canvas building depth keeps two fixed-pixel visual layers', () => {
    const mapCore = readFileSync(
        new URL('../../public/js/wayfinding/01-map-core.js', import.meta.url),
        'utf8',
    );
    const mapRendering = readFileSync(
        new URL('../../public/js/wayfinding/05-map-rendering.js', import.meta.url),
        'utf8',
    );

    assert.match(mapCore, /WayfindingBuildingDepthCanvas = WayfindingBuildingDepthCanvasBase\.extend/);
    assert.match(mapCore, /point\.x \+ offset/);
    assert.match(mapCore, /point\.y \+ offset/);
    assert.match(mapCore, /const offset = baseOffset \* \(0\.45 \+ \(0\.55 \* zoomProgress\)\)/);
    assert.match(mapRendering, /depthPixelOffset: IS_MOBILE_OUTDOOR_VIEW \? 2 : 0/);
    assert.match(mapRendering, /depthPixelOffset: IS_MOBILE_OUTDOOR_VIEW \? 1 : 0/);
});

test('mobile route legend stays hidden before indoor resources load', () => {
    const performanceCss = readFileSync(
        new URL('../../public/css/wayfinding/09-map-performance.css', import.meta.url),
        'utf8',
    );
    const indoorRouting = readFileSync(
        new URL('../../public/js/wayfinding/04-indoor-routing.js', import.meta.url),
        'utf8',
    );

    assert.match(performanceCss, /\.premium-legend[\s\S]*\.leaflet-control-attribution[\s\S]*display: none !important/);
    assert.match(
        performanceCss,
        /@media \(hover:\s*none\), \(pointer:\s*coarse\), \(max-width:\s*768px\)/,
    );
    assert.match(indoorRouting, /insertBefore\([\s\S]*stylesheet,[\s\S]*mainWayfindingStylesheet/);
});

test('indoor graph diagnostics stay silent unless explicit debug mode is enabled', () => {
    const indoorRouting = readFileSync(
        new URL('../../public/js/wayfinding/04-indoor-routing.js', import.meta.url),
        'utf8',
    );

    assert.match(indoorRouting, /window\.WAYFINDING_DEBUG !== true/);
    assert.match(indoorRouting, /debugIndoorGraphWarning\('\[IndoorGraph\]/);
    assert.equal((indoorRouting.match(/console\.warn\(/g) || []).length, 1);
});

test('stale empty indoor snapshots fall back to the building API immediately', () => {
    const transport = readFileSync(
        new URL('../../public/js/wayfinding-indoor-data.js', import.meta.url),
        'utf8',
    );
    const searchVoice = readFileSync(
        new URL('../../public/js/wayfinding/06-search-voice.js', import.meta.url),
        'utf8',
    );

    assert.match(transport, /indoorPaths\.features\.length === 0/);
    assert.match(transport, /indoorEntrances\.features\.length === 0/);
    assert.match(transport, /Indoor snapshot graph is incomplete/);
    assert.match(transport, /fetchJson\(`\/api\/indoor-paths\$\{suffix\}`\)/);
    assert.match(searchVoice, /wayfinding-indoor-data\.js\?v=20260815\.2/);
});

test('building depth has one post-interaction update owner', () => {
    const mapCore = readFileSync(
        new URL('../../public/js/wayfinding/01-map-core.js', import.meta.url),
        'utf8',
    );
    const responsivePerformance = readFileSync(
        new URL('../../public/js/wayfinding/10-responsive-performance.js', import.meta.url),
        'utf8',
    );

    assert.match(mapCore, /register\('building-depth-and-route-popup'/);
    assert.doesNotMatch(responsivePerformance, /three-layer-building-depth/);
    assert.doesNotMatch(responsivePerformance, /cleanMobileBuildingShadowFix/);
});

test('mobile camera has one explicit route fit and one ResizeObserver indoor fit owner', () => {
    const indoorRouting = readFileSync(
        new URL('../../public/js/wayfinding/04-indoor-routing.js', import.meta.url),
        'utf8',
    );
    const outdoorRouting = readFileSync(
        new URL('../../public/js/wayfinding/03-outdoor-routing.js', import.meta.url),
        'utf8',
    );
    const buildingUi = readFileSync(
        new URL('../../public/js/wayfinding/09-building-indoor-ui.js', import.meta.url),
        'utf8',
    );
    const responsivePerformance = readFileSync(
        new URL('../../public/js/wayfinding/10-responsive-performance.js', import.meta.url),
        'utf8',
    );

    assert.match(indoorRouting, /new ResizeObserver\(/);
    assert.match(indoorRouting, /function scheduleIndoorViewportFit\(/);
    assert.match(indoorRouting, /indoorMap\.fitBounds\(paddedBounds/);
    assert.doesNotMatch(indoorRouting, /setTimeout\([\s\S]{0,180}(?:fitBounds|invalidateSize)/);
    assert.match(outdoorRouting, /const routeAlreadyVisible\s*=\s*IS_MOBILE_OUTDOOR_VIEW/);
    assert.match(outdoorRouting, /map\.getBounds\?\.\(\)\.contains\(routeBounds\)/);
    assert.match(outdoorRouting, /map\.fitBounds\(routeBounds/);
    assert.match(outdoorRouting, /animate:\s*!IS_MOBILE_OUTDOOR_VIEW/);
    assert.doesNotMatch(responsivePerformance, /map\.fitBounds\s*=/);
    assert.doesNotMatch(buildingUi, /keepRouteBuildingPopupOnScreen/);
    assert.match(buildingUi, /autoPan:\s*true/);
    assert.doesNotMatch(buildingUi, /keepRoutePopupInsideMapViewport|style\.marginLeft/);
    assert.doesNotMatch(buildingUi, /setTimeout\([\s\S]{0,160}(?:fitBounds|panBy|invalidateSize)/);
});

test('building top remains clickable while Canvas depth cannot intercept clicks', () => {
    const mapRendering = readFileSync(
        new URL('../../public/js/wayfinding/05-map-rendering.js', import.meta.url),
        'utf8',
    );
    const performanceCss = readFileSync(
        new URL('../../public/css/wayfinding/09-map-performance.css', import.meta.url),
        'utf8',
    );

    assert.match(mapRendering, /renderer:\s*OUTDOOR_BUILDINGS_RENDERER/);
    assert.match(mapRendering, /interactive:\s*true/);
    assert.match(mapRendering, /renderer:\s*OUTDOOR_BUILDING_DEPTH_RENDERER/);
    assert.match(mapRendering, /interactive:\s*false/);
    assert.match(performanceCss, /\.fake-3d-building,[\s\S]*?filter:\s*none !important;/);
});

test('indoor graph and styles stay deferred until a building is opened', () => {
    const coreCss = readFileSync(
        new URL('../../resources/css/wayfinding.css', import.meta.url),
        'utf8',
    );
    const indoorRouting = readFileSync(
        new URL('../../public/js/wayfinding/04-indoor-routing.js', import.meta.url),
        'utf8',
    );
    const dataLoader = readFileSync(
        new URL('../../public/js/wayfinding/07-campus-events-data.js', import.meta.url),
        'utf8',
    );

    assert.doesNotMatch(coreCss, /04-indoor-navigation\.css/);
    assert.match(indoorRouting, /function ensureIndoorStyles\(\)/);
    assert.match(indoorRouting, /ensureIndoorBuildingData\(normalizedBuildingId\)/);
    assert.match(indoorRouting, /stylesheet\.href = '\/css\/wayfinding\/04-indoor-navigation\.css\?v=/);
    assert.doesNotMatch(dataLoader, /fetchDataset\('\/api\/indoor-paths'/);
    assert.doesNotMatch(dataLoader, /fetchDataset\('\/api\/indoor-entrances'/);
    assert.doesNotMatch(dataLoader, /fetchDataset\('\/api\/indoor-stairs-links'/);
    assert.doesNotMatch(dataLoader, /ensureIndoorMap\(\)/);

    const indoorDataTransport = readFileSync(
        new URL('../../public/js/wayfinding-indoor-data.js', import.meta.url),
        'utf8',
    );
    assert.match(indoorDataTransport, /WayfindingIndoorDataLoader/);
    assert.match(indoorDataTransport, /\/data\/indoor\/\{building\}\.json/);
});

test('indoor opening responds immediately and caches versioned building data', () => {
    const indoorRouting = readFileSync(
        new URL('../../public/js/wayfinding/04-indoor-routing.js', import.meta.url),
        'utf8',
    );
    const mapUi = readFileSync(
        new URL('../../public/js/wayfinding/02-map-data-ui.js', import.meta.url),
        'utf8',
    );
    const searchVoice = readFileSync(
        new URL('../../public/js/wayfinding/06-search-voice.js', import.meta.url),
        'utf8',
    );
    const indoorDataTransport = readFileSync(
        new URL('../../public/js/wayfinding-indoor-data.js', import.meta.url),
        'utf8',
    );

    assert.match(
        indoorRouting,
        /openIndoorPanelModal\(\);[\s\S]{0,160}setIndoorLoading\(true\);[\s\S]{0,260}await Promise\.all/,
    );
    assert.match(indoorRouting, /latestIndoorOpenRequestId/);
    assert.match(indoorRouting, /Loading rooms and indoor map/);
    assert.match(mapUi, /wayfinding:indoor-panel-closed/);
    assert.match(searchVoice, /wayfinding-indoor-data\.js\?v=/);
    assert.match(indoorDataTransport, /cacheVersion/);
    assert.match(indoorDataTransport, /cache: 'force-cache'/);
    assert.doesNotMatch(indoorDataTransport, /cache: 'no-cache'/);
});

test('indoor vectors use one shared Canvas renderer and room selection does not rebuild the floor', () => {
    const indoorRouting = readFileSync(
        new URL('../../public/js/wayfinding/04-indoor-routing.js', import.meta.url),
        'utf8',
    );

    assert.match(indoorRouting, /preferCanvas:\s*true/);
    assert.match(indoorRouting, /__wayfindingVectorRenderer\s*=\s*createIndoorVectorRenderer\(lowEndIndoorView\)/);
    assert.match(indoorRouting, /padding:\s*lowEndIndoorView\s*\?\s*0\.02\s*:\s*0\.1/);
    assert.match(indoorRouting, /return L\.canvas\(options\)/);
    assert.match(indoorRouting, /__wayfindingVectorRendererMode\s*=\s*['"]canvas['"]/);
    assert.doesNotMatch(indoorRouting, /lowEndIndoorView[\s\S]{0,160}L\.svg\(/);
    assert.match(indoorRouting, /renderer:\s*indoorMap\.__wayfindingVectorRenderer/);
    assert.match(indoorRouting, /interactive:\s*false/);
    assert.match(indoorRouting, /indoorRoomsLayer\?\.eachLayer/);
    assert.doesNotMatch(indoorRouting, /selectedIndoorRoomFeature = feature;[\s\S]{0,180}renderIndoorFloor\(\)/);
});

test('low-end indoor gestures transform one cached surface while room geometry stays interactive', () => {
    const indoorRouting = readFileSync(
        new URL('../../public/js/wayfinding/04-indoor-routing.js', import.meta.url),
        'utf8',
    );
    const searchVoice = readFileSync(
        new URL('../../public/js/wayfinding/06-search-voice.js', import.meta.url),
        'utf8',
    );

    assert.match(indoorRouting, /indoorLowEndSurfaceCache\s*=\s*new Map/);
    assert.match(indoorRouting, /createLowEndIndoorSurface/);
    assert.match(indoorRouting, /canvas\.toBlob\(resolve, 'image\/webp'/);
    assert.match(indoorRouting, /if \(!lowEndSurfaceMode\) \{/);
    assert.match(indoorRouting, /indoorFeatureContainsLatLng/);
    assert.match(indoorRouting, /window\.routeToIndoorRoom/);
    assert.match(searchVoice, /prefetchWayfindingIndoorBuilding/);
    assert.match(searchVoice, /await indoorDataReady/);
});

test('low-end outdoor static layers use one-pixel Canvas backing stores', () => {
    const mapCore = readFileSync(
        new URL('../../public/js/wayfinding/01-map-core.js', import.meta.url),
        'utf8',
    );

    assert.match(mapCore, /WayfindingLowResolutionCanvas\s*=\s*L\.Canvas\.extend/);
    assert.match(mapCore, /createOutdoorCanvasRenderer/);
    assert.match(mapCore, /container\.width\s*=\s*Math\.max\(1, Math\.ceil\(size\.x\)\)/);
    assert.match(mapCore, /WayfindingBuildingDepthCanvasBase/);
});

test('indoor entrance scoring runs off-thread and stale room routes cannot redraw the map', () => {
    const indoorRouting = readFileSync(
        new URL('../../public/js/wayfinding/04-indoor-routing.js', import.meta.url),
        'utf8',
    );

    assert.match(indoorRouting, /async function findBestEntranceLinkForRoom\(/);
    assert.match(indoorRouting, /Promise\.all\(candidateLinks\.map\(async link/);
    assert.match(
        indoorRouting,
        /dijkstraAsync\(startNodeKey,\s*outdoorNodeKey,\s*\{\s*latestOnly:\s*false\s*\}\)/,
    );
    assert.match(indoorRouting, /const bestRoute\s*=\s*await findBestEntranceLinkForRoom/);
    assert.match(indoorRouting, /const routeRequestId\s*=\s*\+\+completeRoomRouteRequestSequence/);
    assert.match(
        indoorRouting,
        /if \(routeRequestId !== completeRoomRouteRequestSequence\) return;/,
    );
});

test('a runtime low-end downgrade stays active for the browser session', () => {
    const mapCore = readFileSync(
        new URL('../../public/js/wayfinding/01-map-core.js', import.meta.url),
        'utf8',
    );
    const responsivePerformance = readFileSync(
        new URL('../../public/js/wayfinding/10-responsive-performance.js', import.meta.url),
        'utf8',
    );

    assert.match(mapCore, /sessionStorage\.getItem\('wayfinding-render-quality'\)/);
    assert.match(responsivePerformance, /sessionStorage\.setItem\('wayfinding-render-quality', 'low'\)/);
});

test('campus event cards hydrate only when the notification bell is opened', () => {
    const campusEvents = readFileSync(
        new URL('../../public/js/wayfinding/07-campus-events-data.js', import.meta.url),
        'utf8',
    );
    const responsivePerformance = readFileSync(
        new URL('../../public/js/wayfinding/10-responsive-performance.js', import.meta.url),
        'utf8',
    );

    assert.match(campusEvents, /ensureCampusEventPanelContents/);
    assert.match(campusEvents, /panel\.dataset\.hydrated === 'true'/);
    assert.match(campusEvents, /if \(shouldOpen\) \{/);
    assert.doesNotMatch(responsivePerformance, /setTimeout\(syncBell/);
    assert.doesNotMatch(responsivePerformance, /restoreCampusEventNotificationBell/);
});

test('outdoor route worker rejects stale results and keeps synchronous fallback', () => {
    const outdoorRouting = readFileSync(
        new URL('../../public/js/wayfinding/03-outdoor-routing.js', import.meta.url),
        'utf8',
    );
    const worker = readFileSync(
        new URL('../../public/js/wayfinding-route-worker.js', import.meta.url),
        'utf8',
    );

    assert.match(outdoorRouting, /latestOutdoorRouteRequestId/);
    assert.match(outdoorRouting, /STALE_ROUTE_REQUEST/);
    assert.match(outdoorRouting, /resolve\(dijkstra\(startKey, endKey\)\)/);
    assert.match(outdoorRouting, /new Worker\('\/js\/wayfinding-route-worker\.js'\)/);
    assert.match(worker, /importScripts\('\/js\/wayfinding-routing\.js'\)/);
    assert.match(worker, /message\.type === 'init'/);
    assert.match(worker, /message\.type !== 'route'/);
});

test('full capacity tester covers public and authenticated journeys safely', () => {
    const scriptUrl = new URL(
        '../../scripts/load-test-wayfinding.mjs',
        import.meta.url,
    );
    const script = readFileSync(scriptUrl, 'utf8');
    const result = spawnSync(
        process.execPath,
        [fileURLToPath(scriptUrl)],
        {
            encoding: 'utf8',
            env: {
                ...process.env,
                WAYFINDING_LOAD_BASE_URL: 'http://wayfinding.test',
                WAYFINDING_LOAD_USERS: '25',
                WAYFINDING_LOAD_CONCURRENCY: '10',
                WAYFINDING_LOAD_DRY_RUN: '1',
                WAYFINDING_LOAD_CONFIRM: '',
            },
        },
    );

    assert.equal(result.status, 0, result.stderr);
    const config = JSON.parse(result.stdout);
    assert.equal(config.virtual_users, 25);
    assert.equal(config.concurrency, 10);
    assert.deepEqual(config.public_scenarios, [
        'guest_dashboard',
        'campus_snapshot',
        'campus_events',
        'destination_search',
    ]);
    assert.equal(config.authenticated_dashboard, false);
    assert.match(script, /authenticated_dashboard/);
    assert.match(script, /WAYFINDING_LOAD_USERS_FILE/);
    assert.match(script, /I_HAVE_PERMISSION/);
    assert.match(script, /p95/);
    assert.match(script, /HTTP 429|response\.status/);
});

test('local capacity runner accepts staged user and concurrency arguments', () => {
    const runner = readFileSync(
        new URL('../../run-local-capacity-test.cmd', import.meta.url),
        'utf8',
    );

    assert.match(runner, /set "WAYFINDING_LOAD_USERS=%~1"/);
    assert.match(runner, /set "WAYFINDING_LOAD_CONCURRENCY=%~2"/);
    assert.match(
        runner,
        /Running %WAYFINDING_LOAD_USERS% virtual users with %WAYFINDING_LOAD_CONCURRENCY% users at the same time/,
    );
    assert.doesNotMatch(runner, /set "WAYFINDING_LOAD_USERS=500"/);
    assert.doesNotMatch(runner, /set "WAYFINDING_LOAD_CONCURRENCY=50"/);
});

test('full capacity tester refuses an unconfirmed remote target', () => {
    const scriptUrl = new URL(
        '../../scripts/load-test-wayfinding.mjs',
        import.meta.url,
    );
    const result = spawnSync(
        process.execPath,
        [fileURLToPath(scriptUrl)],
        {
            encoding: 'utf8',
            env: {
                ...process.env,
                WAYFINDING_LOAD_BASE_URL: 'https://example.com',
                WAYFINDING_LOAD_DRY_RUN: '1',
                WAYFINDING_LOAD_CONFIRM: '',
            },
        },
    );

    assert.notEqual(result.status, 0);
    assert.match(result.stderr, /Remote load testing is blocked/);
});
