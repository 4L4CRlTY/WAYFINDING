(function (global) {
    'use strict';

    function toBoolean(value) {
        return value === true
            || value === 1
            || value === '1'
            || String(value || '').toLowerCase() === 'true'
            || String(value || '').toLowerCase() === 'yes';
    }

    function isPathBlocked(properties = {}) {
        return toBoolean(properties.is_blocked)
            || toBoolean(properties.blocked)
            || String(properties.status || '').toLowerCase() === 'blocked';
    }

    function shortestPath(graph, startKey, endKey, options = {}) {
        const distances = {};
        const previous = {};
        const previousMeta = {};
        const visited = new Set();
        const queue = [];
        const canEnterNode = options.canEnterNode || (() => true);
        const canExpandNode = options.canExpandNode || (() => true);

        Object.keys(graph).forEach((key) => {
            distances[key] = Infinity;
            previous[key] = null;
            previousMeta[key] = null;
        });

        if (!graph[startKey] || !graph[endKey]) {
            return null;
        }

        distances[startKey] = 0;
        queue.push({ key: startKey, distance: 0 });

        while (queue.length > 0) {
            queue.sort((a, b) => a.distance - b.distance);

            const current = queue.shift();

            if (!current) {
                break;
            }

            if (visited.has(current.key)) {
                continue;
            }

            visited.add(current.key);

            if (current.key === endKey) {
                break;
            }

            if (!canExpandNode(current.key, endKey)) {
                continue;
            }

            (graph[current.key] || []).forEach((neighbor) => {
                if (visited.has(neighbor.key) || !canEnterNode(neighbor.key, endKey)) {
                    return;
                }

                const alternative = distances[current.key] + neighbor.weight;

                if (alternative < distances[neighbor.key]) {
                    distances[neighbor.key] = alternative;
                    previous[neighbor.key] = current.key;
                    previousMeta[neighbor.key] = neighbor.meta || null;
                    queue.push({
                        key: neighbor.key,
                        distance: alternative,
                    });
                }
            });
        }

        if (distances[endKey] === Infinity) {
            return null;
        }

        const path = [];
        const metas = [];
        let currentKey = endKey;

        while (currentKey) {
            path.unshift(currentKey);

            if (previousMeta[currentKey]) {
                metas.unshift(previousMeta[currentKey]);
            }

            currentKey = previous[currentKey];
        }

        return {
            path,
            totalCost: distances[endKey],
            metas,
        };
    }

    function outdoorShortestPath(graph, startKey, endKey) {
        const result = shortestPath(graph, startKey, endKey);

        if (!result) {
            return null;
        }

        const maxSeverityOnRoute = result.metas.length
            ? Math.max(...result.metas.map((meta) => Number(meta?.maxSeverity || 0)))
            : 0;

        return {
            ...result,
            maxSeverityOnRoute,
            hasAnyHazard: result.metas.some((meta) => Boolean(meta?.hasHazard)),
        };
    }

    function indoorShortestPath(graph, startKey, endKey) {
        const isRoomNode = (key) => String(key || '').startsWith('r_');
        const mayUseNode = (key, destinationKey) => !isRoomNode(key) || key === destinationKey;

        const result = shortestPath(graph, startKey, endKey, {
            canEnterNode: mayUseNode,
            canExpandNode: mayUseNode,
        });

        if (!result) {
            return null;
        }

        return {
            path: result.path,
            totalCost: result.totalCost,
        };
    }

    global.WayfindingRouting = Object.freeze({
        isPathBlocked,
        shortestPath,
        outdoorShortestPath,
        indoorShortestPath,
    });
})(typeof window !== 'undefined' ? window : globalThis);
