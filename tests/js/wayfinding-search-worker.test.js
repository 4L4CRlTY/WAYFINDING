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
