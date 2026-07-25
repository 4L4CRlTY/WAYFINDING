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
    '08-assistant-ui.js',
    '09-building-indoor-ui.js',
    '10-responsive-performance.js',
    '11-map-performance.js',
    '12-gps-tracking.js',
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
    const mapRendering = readFileSync(mapRenderingPath, 'utf8');
    const outdoorRouting = readFileSync(outdoorRoutingPath, 'utf8');

    assert.match(mapCore, /OUTDOOR_VECTOR_RENDER_PADDING\s*=\s*IS_MOBILE_OUTDOOR_VIEW\s*\?\s*1\s*:\s*0\.5/);
    assert.match(mapCore, /pane:\s*'pathsPane',\s*padding:\s*OUTDOOR_VECTOR_RENDER_PADDING/);
    assert.match(mapCore, /pane:\s*'buildingsPane',\s*padding:\s*OUTDOOR_VECTOR_RENDER_PADDING/);
    assert.match(mapRendering, /pane:\s*'pathsPane',\s*renderer:\s*OUTDOOR_PATHS_RENDERER/);
    assert.match(mapRendering, /pane:\s*'buildingsPane',\s*renderer:\s*OUTDOOR_BUILDINGS_RENDERER/);
    assert.match(outdoorRouting, /polylineOptions\.renderer\s*=\s*OUTDOOR_PATHS_RENDERER/);
    assert.match(mapCore, /updateInterval:\s*IS_MOBILE_OUTDOOR_VIEW\s*\?\s*80\s*:\s*120/);
    assert.match(mapCore, /keepBuffer:\s*IS_MOBILE_OUTDOOR_VIEW\s*\?\s*4\s*:\s*5/);
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
    assert.match(source, /navigator\.geolocation\.watchPosition/);
    assert.match(source, /const rawLatLng = latestSample\.latLng/);
    assert.match(source, /function refreshActiveRouteFromGps/);
    assert.match(source, /fitBounds: false/);
    assert.match(source, /function buildNavigationInstruction/);
});
