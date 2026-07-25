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
