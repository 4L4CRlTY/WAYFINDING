import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

function createSearchWorker() {
    const source = readFileSync(
        new URL('../../public/js/wayfinding-search-worker.js', import.meta.url),
        'utf8',
    );
    const postedMessages = [];
    let messageHandler = null;
    const workerSelf = {
        addEventListener(type, handler) {
            if (type === 'message') messageHandler = handler;
        },
        postMessage(message) {
            postedMessages.push(message);
        },
    };

    runInNewContext(source, { self: workerSelf });
    return {
        send(data) {
            messageHandler({ data });
        },
        postedMessages,
    };
}

const entries = [
    {
        keyword: 'information technology',
        destination_type: 'building',
        priority: 2,
        result: { id: 1 },
    },
    {
        keyword: 'it',
        destination_type: 'building',
        priority: 5,
        result: { id: 1 },
    },
    {
        keyword: 'laboratory one',
        destination_type: 'room',
        priority: 3,
        result: { id: 10 },
    },
    {
        keyword: 'inactive destination',
        destination_type: 'room',
        priority: 100,
        result: null,
    },
];

test('search worker initializes one normalized keyword index', () => {
    const worker = createSearchWorker();
    worker.send({ type: 'init', version: 77, entries });

    assert.deepEqual(
        JSON.parse(JSON.stringify(worker.postedMessages[0])),
        { type: 'ready', version: 77 },
    );
});

test('search worker preserves exact, room, priority, and inactive matching rules', () => {
    const worker = createSearchWorker();
    worker.send({ type: 'init', version: 77, entries });
    worker.send({ type: 'search', requestId: 9, query: 'please take me to laboratory one' });
    worker.send({ type: 'search', requestId: 10, query: 'IT' });
    worker.send({ type: 'search', requestId: 11, query: 'inactive destination' });

    const laboratoryResult = worker.postedMessages[1];
    const exactItResult = worker.postedMessages[2];
    const inactiveResult = worker.postedMessages[3];

    assert.equal(laboratoryResult.type, 'result');
    assert.equal(laboratoryResult.requestId, 9);
    assert.equal(laboratoryResult.matches[0].index, 2);
    assert.equal(exactItResult.matches[0].index, 1);
    assert.deepEqual(JSON.parse(JSON.stringify(inactiveResult.matches)), []);
});

test('search worker ranks compact rows without expanding destination results', () => {
    const worker = createSearchWorker();
    worker.send({
        type: 'init',
        version: 78,
        document: {
            schema_version: 2,
            format: 'compact-v1',
            destinations: [
                [0, 1, 'Information Technology'],
                [1, 10, 'Laboratory 1', 'LAB-1', 1, 'Information Technology', 1, '1F'],
            ],
            search_index: [
                [1, 'information technology', 0, 2],
                [2, 'it', 0, 5],
                [3, 'laboratory one', 1, 3],
            ],
        },
    });
    worker.send({ type: 'search', requestId: 12, query: 'laboratory one' });

    assert.deepEqual(
        JSON.parse(JSON.stringify(worker.postedMessages[0])),
        { type: 'ready', version: 78 },
    );
    assert.equal(worker.postedMessages[1].matches[0].index, 2);
});

test('search UI rejects stale results and falls back when Worker is unavailable', () => {
    const source = readFileSync(
        new URL('../../public/js/wayfinding/06-search-voice.js', import.meta.url),
        'utf8',
    );

    assert.match(source, /message\.requestId !== latestWayfindingSearchWorkerRequestId/);
    assert.match(source, /if \(response\.stale\) return \[\]/);
    assert.match(source, /typeof Worker !== 'function'/);
    assert.match(source, /rankSearchIndexSynchronously/);
    assert.match(source, /activeDestinationSearchController\?\.abort/);
    assert.match(source, /searchRequestId !== latestDestinationSearchRequestId/);
});

test('search UI keeps compact cached destinations compressed and preserves legacy compatibility', () => {
    const source = readFileSync(
        new URL('../../public/js/wayfinding/06-search-voice.js', import.meta.url),
        'utf8',
    );

    assert.match(source, /function createWayfindingSearchStore\(document\)/);
    assert.match(source, /document\?\.format !== 'compact-v1'/);
    assert.match(source, /function getWayfindingSearchEntry\(searchStore, index\)/);
    assert.match(source, /document: searchStore\?\.compact \? searchStore\.document/);
    assert.match(source, /Number\(document\?\.schema_version\) === 1/);
    assert.match(source, /searchProgress\.textContent = 'Preparing search…'/);
    assert.doesNotMatch(source, /preferCompactServerSearch/);
});
