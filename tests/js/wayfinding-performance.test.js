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
