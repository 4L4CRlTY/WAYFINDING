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

test('GPS tracking code is contained in an executable JavaScript module', () => {
    const path = new URL(
        '../../public/js/wayfinding/12-gps-tracking.js',
        import.meta.url,
    );
    const source = readFileSync(path, 'utf8');

    assert.match(source, /function startOutdoorLiveGpsTracking\(\)/);
    assert.match(source, /window\.startOutdoorLiveGpsTracking/);
});
