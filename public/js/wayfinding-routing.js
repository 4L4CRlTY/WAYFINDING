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

    function normalizeBearing(degrees) {
        const normalized = Number(degrees) % 360;

        return normalized < 0 ? normalized + 360 : normalized;
    }

    function bearingBetween(from, to) {
        if (!from || !to) {
            return 0;
        }

        const fromLat = Number(from.lat ?? from[0]);
        const fromLng = Number(from.lng ?? from[1]);
        const toLat = Number(to.lat ?? to[0]);
        const toLng = Number(to.lng ?? to[1]);

        if (![fromLat, fromLng, toLat, toLng].every(Number.isFinite)) {
            return 0;
        }

        const fromLatRadians = fromLat * Math.PI / 180;
        const toLatRadians = toLat * Math.PI / 180;
        const longitudeDelta = (toLng - fromLng) * Math.PI / 180;
        const y = Math.sin(longitudeDelta) * Math.cos(toLatRadians);
        const x = (Math.cos(fromLatRadians) * Math.sin(toLatRadians))
            - (Math.sin(fromLatRadians) * Math.cos(toLatRadians) * Math.cos(longitudeDelta));

        return normalizeBearing(Math.atan2(y, x) * 180 / Math.PI);
    }

    function relativeTurn(fromBearing, toBearing) {
        const delta = ((normalizeBearing(toBearing) - normalizeBearing(fromBearing) + 540) % 360) - 180;
        const absoluteDelta = Math.abs(delta);

        if (absoluteDelta < 25) {
            return { type: 'straight', delta };
        }

        if (absoluteDelta >= 150) {
            return { type: 'u_turn', delta };
        }

        return {
            type: delta > 0 ? 'right' : 'left',
            delta,
        };
    }

    function cardinalDirection(bearing) {
        const directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        const index = Math.round(normalizeBearing(bearing) / 45) % directions.length;

        return directions[index];
    }

    global.WayfindingRouting = Object.freeze({
        isPathBlocked,
        shortestPath,
        outdoorShortestPath,
        indoorShortestPath,
        normalizeBearing,
        bearingBetween,
        relativeTurn,
        cardinalDirection,
    });
})(typeof window !== 'undefined' ? window : globalThis);
