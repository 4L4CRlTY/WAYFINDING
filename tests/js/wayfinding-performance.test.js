import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
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

test('wayfinding entry preserves the legacy runtime order in one bundle', () => {
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
        'public/js/wayfinding/08-assistant-ui.js',
        'public/js/wayfinding/09-building-indoor-ui.js',
        'public/js/wayfinding/10-responsive-performance.js',
        'public/js/wayfinding/11-map-performance.js',
        'public/js/wayfinding/12-gps-tracking.js',
        'public/js/wayfinding/13-gps-diagnostics.js',
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
});

test('mobile dragging uses one lightweight map interaction controller', () => {
    const responsivePerformance = readFileSync(
        new URL('../../public/js/wayfinding/10-responsive-performance.js', import.meta.url),
        'utf8',
    );
    const finalMapPerformance = readFileSync(
        new URL('../../public/js/wayfinding/11-map-performance.js', import.meta.url),
        'utf8',
    );

    assert.match(responsivePerformance, /map\.on\('dragstart', markMapDragging\)/);
    assert.match(responsivePerformance, /body\.classList\.add\('map-dragging'\)/);
    assert.match(responsivePerformance, /body\.classList\.remove\('map-dragging'\)/);
    assert.doesNotMatch(responsivePerformance, /map\.on\('move zoom', markMoving\)/);
    assert.doesNotMatch(finalMapPerformance, /map-moving-lite-3d/);
    assert.doesNotMatch(finalMapPerformance, /map\.on\('move zoom'/);
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
    assert.match(outdoorRouting, /map\.on\('moveend zoomend'/);
    assert.doesNotMatch(outdoorRouting, /map\.on\('move zoomend'/);
    assert.match(mapCore, /updateWhenIdle:\s*IS_MOBILE_OUTDOOR_VIEW/);
    assert.match(mapCore, /updateWhenZooming:\s*!IS_MOBILE_OUTDOOR_VIEW/);
    assert.match(mapCore, /keepBuffer:\s*5/);
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
    assert.match(mapCore, /L\.canvas\(\{[\s\S]*?pane:\s*'pathsPane'/);
    assert.match(mapCore, /const OUTDOOR_ROUTE_RENDERER\s*=\s*L\.svg/);
    assert.match(gpsTracking, /map\.on\('dragstart', pauseGpsFollowForManualDrag\)/);
    assert.match(gpsTracking, /pendingGpsRouteRefreshPosition/);
    assert.match(gpsTracking, /if \(gpsMapDragActive && !force\)/);
    assert.match(gpsTracking, /liveGpsFollow && smoothLatLng && !gpsMapDragActive/);
    assert.match(performanceCss, /\.path-interactive,[\s\S]*?filter:\s*none !important;[\s\S]*?pointer-events:\s*none !important;/);
    assert.match(performanceCss, /body\.map-dragging \.leaflet-buildings-pane/);
    assert.doesNotMatch(performanceCss, /\.leaflet-buildingsPane-pane svg/);
});
