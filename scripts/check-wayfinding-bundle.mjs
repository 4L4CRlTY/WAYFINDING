import assert from 'node:assert/strict';
import { readFileSync, statSync } from 'node:fs';
import { resolve } from 'node:path';
import { gzipSync } from 'node:zlib';

const manifestPath = resolve('public/build/manifest.json');
const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
const entries = [
    'resources/css/wayfinding.css',
    'resources/js/wayfinding-entry.js',
];

const budgets = {
    'resources/css/wayfinding.css': 190 * 1024,
    'resources/js/wayfinding-entry.js': 305 * 1024,
};

const gzipBudgets = {
    'resources/css/wayfinding.css': 32 * 1024,
    'resources/js/wayfinding-entry.js': 88 * 1024,
};

const lazyBudgets = {
    'resources/js/wayfinding-assistant-entry.js': 20 * 1024,
    'resources/js/wayfinding-gps-entry.js': 24 * 1024,
    'resources/js/wayfinding-gps-diagnostics-entry.js': 11 * 1024,
};

const lazyGzipBudgets = {
    'resources/js/wayfinding-assistant-entry.js': 4 * 1024,
    'resources/js/wayfinding-gps-entry.js': 8 * 1024,
    'resources/js/wayfinding-gps-diagnostics-entry.js': 4 * 1024,
};

const lazyCssBudgets = {
    'resources/js/wayfinding-gps-entry.js': {
        raw: 6 * 1024,
        gzip: 2 * 1024,
    },
    'resources/js/wayfinding-gps-diagnostics-entry.js': {
        raw: 10 * 1024,
        gzip: 3 * 1024,
    },
};

function assertBundleBudget(entry, outputPath, rawBudget, gzipBudget) {
    const contents = readFileSync(outputPath);
    const bytes = contents.length;
    const gzipBytes = gzipSync(contents, { level: 9 }).length;

    assert.ok(
        bytes <= rawBudget,
        `${entry} is ${(bytes / 1024).toFixed(1)} KiB; budget is ${rawBudget / 1024} KiB`,
    );
    assert.ok(
        gzipBytes <= gzipBudget,
        `${entry} transfers as ${(gzipBytes / 1024).toFixed(1)} KiB gzip; budget is ${gzipBudget / 1024} KiB`,
    );

    return { bytes, gzipBytes };
}

for (const entry of entries) {
    const output = manifest[entry];
    assert.ok(output?.isEntry, `Missing production entry: ${entry}`);

    const outputPath = resolve('public/build', output.file);
    const { bytes, gzipBytes } = assertBundleBudget(
        entry,
        outputPath,
        budgets[entry],
        gzipBudgets[entry],
    );

    assert.equal(bytes, statSync(outputPath).size, `Could not measure output: ${entry}`);
    console.log(
        `${entry}: ${(bytes / 1024).toFixed(1)} KiB raw, ${(gzipBytes / 1024).toFixed(1)} KiB gzip`,
    );
}

assert.equal(entries.length, 2, 'The user map must require only one CSS and one JS wayfinding entry.');

for (const [entry, budget] of Object.entries(lazyBudgets)) {
    const output = manifest[entry];
    assert.ok(output?.isDynamicEntry, `Missing lazy production chunk: ${entry}`);

    const { bytes, gzipBytes } = assertBundleBudget(
        entry,
        resolve('public/build', output.file),
        budget,
        lazyGzipBudgets[entry],
    );
    console.log(
        `${entry} (lazy): ${(bytes / 1024).toFixed(1)} KiB raw, ${(gzipBytes / 1024).toFixed(1)} KiB gzip`,
    );

    const cssBudget = lazyCssBudgets[entry];
    if (cssBudget) {
        assert.equal(output.css?.length, 1, `${entry} must own one lazy CSS chunk.`);
        const cssPath = resolve('public/build', output.css[0]);
        const cssSizes = assertBundleBudget(
            `${entry} CSS`,
            cssPath,
            cssBudget.raw,
            cssBudget.gzip,
        );
        console.log(
            `${entry} CSS (lazy): ${(cssSizes.bytes / 1024).toFixed(1)} KiB raw, ${(cssSizes.gzipBytes / 1024).toFixed(1)} KiB gzip`,
        );
    }
}

console.log('Initial and lazy wayfinding bundles are within their performance budgets.');
