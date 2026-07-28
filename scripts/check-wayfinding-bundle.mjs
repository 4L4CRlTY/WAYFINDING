import assert from 'node:assert/strict';
import { readFileSync, statSync } from 'node:fs';
import { resolve } from 'node:path';

const manifestPath = resolve('public/build/manifest.json');
const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
const entries = [
    'resources/css/wayfinding.css',
    'resources/js/wayfinding-entry.js',
];

const budgets = {
    'resources/css/wayfinding.css': 200 * 1024,
    'resources/js/wayfinding-entry.js': 305 * 1024,
};

const lazyBudgets = {
    'resources/js/wayfinding-assistant-entry.js': 20 * 1024,
    'resources/js/wayfinding-gps-entry.js': 24 * 1024,
    'resources/js/wayfinding-gps-diagnostics-entry.js': 11 * 1024,
};

for (const entry of entries) {
    const output = manifest[entry];
    assert.ok(output?.isEntry, `Missing production entry: ${entry}`);

    const outputPath = resolve('public/build', output.file);
    const bytes = statSync(outputPath).size;
    assert.ok(
        bytes <= budgets[entry],
        `${entry} is ${(bytes / 1024).toFixed(1)} KiB; budget is ${budgets[entry] / 1024} KiB`,
    );

    console.log(`${entry}: ${(bytes / 1024).toFixed(1)} KiB`);
}

assert.equal(entries.length, 2, 'The user map must require only one CSS and one JS wayfinding entry.');

for (const [entry, budget] of Object.entries(lazyBudgets)) {
    const output = manifest[entry];
    assert.ok(output?.isDynamicEntry, `Missing lazy production chunk: ${entry}`);

    const bytes = statSync(resolve('public/build', output.file)).size;
    assert.ok(
        bytes <= budget,
        `${entry} is ${(bytes / 1024).toFixed(1)} KiB; budget is ${budget / 1024} KiB`,
    );
    console.log(`${entry} (lazy): ${(bytes / 1024).toFixed(1)} KiB`);
}

console.log('Initial and lazy wayfinding bundles are within their performance budgets.');
