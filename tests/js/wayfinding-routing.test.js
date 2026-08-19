import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

const source = readFileSync(
    new URL('../../public/js/wayfinding-routing.js', import.meta.url),
    'utf8',
);
const context = {};

runInNewContext(source, context);

const routing = context.WayfindingRouting;

function undirectedGraph(edges) {
    const graph = {};

    edges.forEach(([from, to, weight, meta = {}]) => {
        graph[from] ??= [];
        graph[to] ??= [];
        graph[from].push({ key: to, weight, meta });
        graph[to].push({ key: from, weight, meta });
    });

    return graph;
}

test('outdoor routing selects the lowest-cost path', () => {
    const graph = undirectedGraph([
        ['start', 'short-a', 2],
        ['short-a', 'destination', 2],
        ['start', 'long-a', 1],
        ['long-a', 'long-b', 5],
        ['long-b', 'destination', 1],
    ]);

    const result = routing.outdoorShortestPath(graph, 'start', 'destination');

    assert.deepEqual([...result.path], ['start', 'short-a', 'destination']);
    assert.equal(result.totalCost, 4);
});

test('route worker returns the exact same outdoor route as the synchronous router', () => {
    const workerSource = readFileSync(
        new URL('../../public/js/wayfinding-route-worker.js', import.meta.url),
        'utf8',
    );
    const postedMessages = [];
    let messageHandler = null;
    const workerSelf = {
        WayfindingRouting: routing,
        addEventListener(type, handler) {
            if (type === 'message') messageHandler = handler;
        },
        postMessage(message) {
            postedMessages.push(message);
        },
    };
    runInNewContext(workerSource, {
        self: workerSelf,
        importScripts() {},
    });

    const graph = undirectedGraph([
        ['start', 'safe', 2, { hasHazard: false, maxSeverity: 0 }],
        ['safe', 'destination', 3, { hasHazard: true, maxSeverity: 2 }],
        ['start', 'expensive', 20],
        ['expensive', 'destination', 20],
    ]);
    const expected = routing.outdoorShortestPath(graph, 'start', 'destination');

    messageHandler({ data: { type: 'init', graph, snapshotVersion: 77 } });
    messageHandler({
        data: {
            type: 'route',
            requestId: 9,
            snapshotVersion: 77,
            startKey: 'start',
            endKey: 'destination',
        },
    });

    assert.equal(postedMessages[0].type, 'ready');
    assert.equal(postedMessages[1].type, 'result');
    assert.deepEqual(
        JSON.parse(JSON.stringify(postedMessages[1].result)),
        JSON.parse(JSON.stringify(expected)),
    );
});

test('outdoor routing returns null for a disconnected destination', () => {
    const graph = {
        start: [{ key: 'nearby', weight: 1, meta: {} }],
        nearby: [{ key: 'start', weight: 1, meta: {} }],
        destination: [],
    };

    assert.equal(
        routing.outdoorShortestPath(graph, 'start', 'destination'),
        null,
    );
});

test('blocked path metadata is recognized before graph edges are built', () => {
    assert.equal(routing.isPathBlocked({ is_blocked: true }), true);
    assert.equal(routing.isPathBlocked({ blocked: '1' }), true);
    assert.equal(routing.isPathBlocked({ status: 'BLOCKED' }), true);
    assert.equal(routing.isPathBlocked({ is_blocked: false, status: 'open' }), false);
});

test('outdoor routing preserves hazard information from selected edges', () => {
    const graph = undirectedGraph([
        ['start', 'middle', 2, { hasHazard: true, maxSeverity: 2 }],
        ['middle', 'destination', 2, { hasHazard: true, maxSeverity: 3 }],
    ]);

    const result = routing.outdoorShortestPath(graph, 'start', 'destination');

    assert.equal(result.hasAnyHazard, true);
    assert.equal(result.maxSeverityOnRoute, 3);
    assert.equal(result.metas.length, 2);
});

test('indoor routing never uses another room as a shortcut', () => {
    const graph = undirectedGraph([
        ['e_start', 'r_shortcut', 1],
        ['r_shortcut', 'e_finish', 1],
        ['e_start', 'p_hall_a', 2],
        ['p_hall_a', 'p_hall_b', 2],
        ['p_hall_b', 'e_finish', 2],
    ]);

    const result = routing.indoorShortestPath(graph, 'e_start', 'e_finish');

    assert.deepEqual(
        [...result.path],
        ['e_start', 'p_hall_a', 'p_hall_b', 'e_finish'],
    );
    assert.equal(result.totalCost, 6);
});

test('indoor routing may enter the selected destination room', () => {
    const graph = undirectedGraph([
        ['e_start', 'p_hall', 2],
        ['p_hall', 'e_room_door', 2],
        ['e_room_door', 'r_destination', 1],
    ]);

    const result = routing.indoorShortestPath(
        graph,
        'e_start',
        'r_destination',
    );

    assert.deepEqual(
        [...result.path],
        ['e_start', 'p_hall', 'e_room_door', 'r_destination'],
    );
    assert.equal(result.totalCost, 5);
});

test('an indoor route belongs only to its destination building', () => {
    const routePackage = {
        buildingId: 5,
        roomFeature: {
            properties: {
                building_id: 5,
                floor_number: 2,
            },
        },
    };

    assert.equal(routing.indoorRouteBelongsToBuilding(routePackage, 5), true);
    assert.equal(routing.indoorRouteBelongsToBuilding(routePackage, 1), false);
    assert.equal(routing.indoorRouteBelongsToBuilding(routePackage, 12), false);
    assert.equal(routing.indoorRouteBelongsToBuilding(null, 5), false);
});

test('entrance selection uses a shorter side entrance for the complete route', () => {
    const mainEntrance = {
        name: 'Main Entrance',
        primaryOrMain: true,
        isSameFloorEntrance: true,
        directDoorMeters: 70,
        outdoorCost: 92,
        indoorCost: 310,
        outdoorResult: { path: ['start', 'main-node'] },
    };
    const sideEntrance = {
        name: 'Side Entrance',
        primaryOrMain: false,
        sideEntrance: true,
        isSameFloorEntrance: true,
        directDoorMeters: 18,
        outdoorCost: 24,
        indoorCost: 40,
        outdoorResult: { path: ['start', 'side-branch', 'side-node'] },
    };

    const selected = routing.selectBestEntranceCandidate(
        [mainEntrance, sideEntrance],
        0.16,
        15,
    );

    assert.equal(selected.name, 'Side Entrance');
});

test('entrance selection stops at main when the side route passes it first', () => {
    const mainEntrance = {
        name: 'Main Entrance',
        primaryOrMain: true,
        sideEntrance: false,
        isSameFloorEntrance: true,
        outdoorCost: 20,
        indoorCost: 300,
        outdoorResult: { path: ['start', 'main-node'] },
    };
    const sideEntrance = {
        name: 'Side Entrance',
        primaryOrMain: false,
        sideEntrance: true,
        isSameFloorEntrance: true,
        outdoorCost: 100,
        indoorCost: 20,
        outdoorResult: { path: ['start', 'main-node', 'side-node'] },
    };

    const selected = routing.selectBestEntranceCandidate(
        [sideEntrance, mainEntrance],
        0.16,
        15,
    );

    assert.equal(selected.name, 'Main Entrance');
});

test('entrance selection keeps main when complete routes are effectively tied', () => {
    const mainEntrance = {
        name: 'Main Entrance',
        primaryOrMain: true,
        isSameFloorEntrance: true,
        directDoorMeters: 36,
        outdoorCost: 44,
        indoorCost: 80,
    };
    const sideEntrance = {
        name: 'Side Entrance',
        primaryOrMain: false,
        isSameFloorEntrance: true,
        directDoorMeters: 32,
        outdoorCost: 39,
        indoorCost: 74,
    };

    const selected = routing.selectBestEntranceCandidate(
        [sideEntrance, mainEntrance],
        0.16,
        15,
    );

    assert.equal(selected.name, 'Main Entrance');
});

test('entrance selection does not trade a same-floor entrance for a shorter wrong-floor route', () => {
    const sameFloorMain = {
        name: 'Main Entrance',
        primaryOrMain: true,
        isSameFloorEntrance: true,
        outdoorCost: 80,
        indoorCost: 120,
    };
    const wrongFloorSide = {
        name: 'Side Entrance',
        primaryOrMain: false,
        isSameFloorEntrance: false,
        outdoorCost: 5,
        indoorCost: 10,
    };

    const selected = routing.selectBestEntranceCandidate(
        [wrongFloorSide, sameFloorMain],
        0.16,
        15,
    );

    assert.equal(selected.name, 'Main Entrance');
});

test('navigation bearings and turn directions are calculated consistently', () => {
    assert.equal(Math.round(routing.bearingBetween([10, 124], [11, 124])), 0);
    assert.equal(Math.round(routing.bearingBetween([10, 124], [10, 125])), 90);
    assert.equal(routing.cardinalDirection(225), 'SW');

    assert.equal(routing.relativeTurn(0, 8).type, 'straight');
    assert.equal(routing.relativeTurn(0, 90).type, 'right');
    assert.equal(routing.relativeTurn(90, 0).type, 'left');
    assert.equal(routing.relativeTurn(0, 180).type, 'u_turn');
});

test('GPS quality lock requires four stable accurate readings', () => {
    const stableSamples = [
        { lat: 10.25118810, lng: 124.98544340, accuracy: 8 },
        { lat: 10.25118812, lng: 124.98544342, accuracy: 7 },
        { lat: 10.25118809, lng: 124.98544339, accuracy: 9 },
        { lat: 10.25118811, lng: 124.98544341, accuracy: 6 },
    ];

    const waiting = routing.evaluateGpsQualitySamples(stableSamples.slice(0, 3));
    const locked = routing.evaluateGpsQualitySamples(stableSamples);

    assert.equal(waiting.locked, false);
    assert.equal(waiting.reason, 'samples');
    assert.equal(waiting.sampleCount, 3);
    assert.equal(locked.locked, true);
    assert.equal(locked.sampleCount, 4);
    assert.ok(locked.spread < 10);
    assert.ok(Math.abs(locked.point.lat - 10.251188105) < 0.000000001);
});

test('GPS quality lock rejects weak accuracy and unstable sample spread', () => {
    const weakAccuracy = routing.evaluateGpsQualitySamples([
        { lat: 10.25118, lng: 124.98544, accuracy: 8 },
        { lat: 10.25118, lng: 124.98544, accuracy: 7 },
        { lat: 10.25118, lng: 124.98544, accuracy: 35 },
    ]);
    const unstable = routing.evaluateGpsQualitySamples([
        { lat: 10.25118, lng: 124.98544, accuracy: 8 },
        { lat: 10.25128, lng: 124.98544, accuracy: 8 },
        { lat: 10.25138, lng: 124.98544, accuracy: 8 },
        { lat: 10.25148, lng: 124.98544, accuracy: 8 },
    ]);

    assert.equal(weakAccuracy.locked, false);
    assert.equal(weakAccuracy.reason, 'accuracy');
    assert.equal(weakAccuracy.sampleCount, 0);
    assert.equal(unstable.locked, false);
    assert.equal(unstable.reason, 'spread');
    assert.ok(unstable.spread > 10);
});

test('off-route rerouting requires three consecutive confirmations', () => {
    const first = routing.nextGpsOffRouteConfirmation(0, false, 3);
    const second = routing.nextGpsOffRouteConfirmation(first.count, false, 3);
    const recovered = routing.nextGpsOffRouteConfirmation(second.count, true, 3);
    const third = routing.nextGpsOffRouteConfirmation(second.count, false, 3);

    assert.deepEqual({ ...first }, { count: 1, confirmed: false });
    assert.deepEqual({ ...second }, { count: 2, confirmed: false });
    assert.deepEqual({ ...recovered }, { count: 0, confirmed: false });
    assert.deepEqual({ ...third }, { count: 3, confirmed: true });
});

test('GPS signal classification follows the safe field thresholds', () => {
    assert.equal(routing.classifyGpsSignal(null), 'unknown');
    assert.equal(routing.classifyGpsSignal(8), 'strong');
    assert.equal(routing.classifyGpsSignal(30), 'fair');
    assert.equal(routing.classifyGpsSignal(52), 'weak');
    assert.equal(routing.classifyGpsSignal(75), 'rejected');
});

test('GPS calibration summary reports field quality and acceptance rate', () => {
    const samples = [
        { timestamp: 1000, accuracy: 6, snapDistance: 2, accepted: true, status: 'accepted' },
        { timestamp: 2000, accuracy: 8, snapDistance: 3, accepted: true, status: 'accepted' },
        { timestamp: 3000, accuracy: 9, snapDistance: 2, accepted: true, status: 'accepted' },
        { timestamp: 4000, accuracy: 10, snapDistance: 4, accepted: true, status: 'accepted' },
        { timestamp: 5000, accuracy: 12, snapDistance: 3, accepted: true, status: 'accepted' },
    ];
    const summary = routing.summarizeGpsCalibration(samples);

    assert.equal(summary.sampleCount, 5);
    assert.equal(summary.acceptedCount, 5);
    assert.equal(summary.acceptedRate, 1);
    assert.equal(summary.durationMs, 4000);
    assert.equal(summary.grade, 'excellent');
    assert.ok(summary.p95Accuracy > 10);
    assert.ok(summary.averageSnapDistance < 4);
});

test('GPS calibration summary warns when field readings are unreliable', () => {
    const summary = routing.summarizeGpsCalibration([
        { timestamp: 1000, accuracy: 70, accepted: false, status: 'weak_accuracy' },
        { timestamp: 2000, accuracy: 85, accepted: false, status: 'weak_accuracy' },
        { timestamp: 3000, accuracy: 90, accepted: false, status: 'off_path' },
        { timestamp: 4000, accuracy: 75, accepted: false, status: 'off_path' },
    ]);

    assert.equal(summary.grade, 'poor');
    assert.equal(summary.acceptedRate, 0);
    assert.equal(summary.rejectedCount, 4);
    assert.equal(summary.weakCount, 4);
    assert.match(summary.recommendation, /Tap My Location/);
});
