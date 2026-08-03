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

    class MinPriorityQueue {
        constructor() {
            this.items = [];
        }

        get size() {
            return this.items.length;
        }

        push(item) {
            this.items.push(item);
            let index = this.items.length - 1;

            while (index > 0) {
                const parentIndex = Math.floor((index - 1) / 2);
                if (this.items[parentIndex].distance <= item.distance) break;

                this.items[index] = this.items[parentIndex];
                index = parentIndex;
            }

            this.items[index] = item;
        }

        pop() {
            if (!this.items.length) return null;

            const first = this.items[0];
            const last = this.items.pop();

            if (this.items.length && last) {
                let index = 0;

                while (true) {
                    const leftIndex = (index * 2) + 1;
                    const rightIndex = leftIndex + 1;
                    let smallestIndex = index;

                    if (
                        leftIndex < this.items.length
                        && this.items[leftIndex].distance < last.distance
                    ) {
                        smallestIndex = leftIndex;
                    }

                    if (
                        rightIndex < this.items.length
                        && this.items[rightIndex].distance < (
                            smallestIndex === index
                                ? last.distance
                                : this.items[leftIndex].distance
                        )
                    ) {
                        smallestIndex = rightIndex;
                    }

                    if (smallestIndex === index) break;

                    this.items[index] = this.items[smallestIndex];
                    index = smallestIndex;
                }

                this.items[index] = last;
            }

            return first;
        }
    }

    function shortestPath(graph, startKey, endKey, options = {}) {
        const distances = {};
        const previous = {};
        const previousMeta = {};
        const visited = new Set();
        const queue = new MinPriorityQueue();
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

        while (queue.size > 0) {
            const current = queue.pop();

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

    function entranceWalkingCost(candidate, indoorScale) {
        return Number(candidate.outdoorCost || 0) +
            (Number(candidate.indoorCost || 0) * indoorScale);
    }

    function selectBestEntranceCandidate(
        candidates,
        indoorScale,
        mainTieMeters,
    ) {
        if (!candidates?.length) {
            return null;
        }

        const sameFloorCandidates = candidates.filter(
            candidate => candidate.isSameFloorEntrance,
        );
        const eligible = sameFloorCandidates.length
            ? sameFloorCandidates
            : candidates;
        const byCompleteRoute = [...eligible].sort((a, b) => (
            entranceWalkingCost(a, indoorScale) -
            entranceWalkingCost(b, indoorScale) ||
            Number(b.primaryOrMain) - Number(a.primaryOrMain)
        ));
        const shortest = byCompleteRoute[0];
        const bestMain = byCompleteRoute.find(
            candidate => candidate.primaryOrMain && !candidate.sideEntrance,
        );
        const passedMain = shortest.sideEntrance && byCompleteRoute.find(candidate => (
            candidate.primaryOrMain &&
            !candidate.sideEntrance &&
            shortest.outdoorResult?.path?.includes(
                candidate.outdoorResult?.path?.at(-1),
            )
        ));

        if (passedMain) {
            return passedMain;
        }

        /*
         * Prefer the official main entrance only when its complete walking route
         * is effectively tied with the shortest option. A meaningfully shorter
         * side entrance remains available for users and rooms near that end.
         */
        if (
            bestMain &&
            entranceWalkingCost(bestMain, indoorScale) <=
                entranceWalkingCost(shortest, indoorScale) + mainTieMeters
        ) {
            return bestMain;
        }

        return shortest;
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

    function distanceMeters(from, to) {
        if (!from || !to) {
            return Infinity;
        }

        const fromLat = Number(from.lat ?? from[0]);
        const fromLng = Number(from.lng ?? from[1]);
        const toLat = Number(to.lat ?? to[0]);
        const toLng = Number(to.lng ?? to[1]);

        if (![fromLat, fromLng, toLat, toLng].every(Number.isFinite)) {
            return Infinity;
        }

        const earthRadius = 6371000;
        const toRadians = (degrees) => Number(degrees) * Math.PI / 180;
        const fromLatRadians = toRadians(fromLat);
        const toLatRadians = toRadians(toLat);
        const deltaLat = toRadians(toLat - fromLat);
        const deltaLng = toRadians(toLng - fromLng);
        const haversine = Math.sin(deltaLat / 2) ** 2
            + Math.cos(fromLatRadians)
            * Math.cos(toLatRadians)
            * Math.sin(deltaLng / 2) ** 2;

        return earthRadius * 2 * Math.atan2(
            Math.sqrt(haversine),
            Math.sqrt(1 - haversine),
        );
    }

    function medianNumber(values) {
        const sorted = values
            .map(Number)
            .filter(Number.isFinite)
            .sort((a, b) => a - b);

        if (!sorted.length) return null;

        const middle = Math.floor(sorted.length / 2);
        return sorted.length % 2
            ? sorted[middle]
            : (sorted[middle - 1] + sorted[middle]) / 2;
    }

    function evaluateGpsQualitySamples(samples, options = {}) {
        const requiredSamples = Math.max(1, Number(options.requiredSamples || 4));
        const maxAccuracy = Math.max(1, Number(options.maxAccuracy || 20));
        const maxSpread = Math.max(1, Number(options.maxSpread || 10));
        const normalized = (Array.isArray(samples) ? samples : [])
            .map((sample) => ({
                lat: Number(sample?.lat ?? sample?.latLng?.lat),
                lng: Number(sample?.lng ?? sample?.latLng?.lng),
                accuracy: Number(sample?.accuracy),
            }))
            .filter(sample => (
                Number.isFinite(sample.lat)
                && Number.isFinite(sample.lng)
                && Number.isFinite(sample.accuracy)
            ));
        const consecutive = [];

        for (let index = normalized.length - 1; index >= 0; index -= 1) {
            if (normalized[index].accuracy > maxAccuracy) break;
            consecutive.unshift(normalized[index]);
        }

        const candidates = consecutive.slice(-requiredSamples);
        const latest = normalized[normalized.length - 1] || null;
        let spread = 0;

        for (let first = 0; first < candidates.length; first += 1) {
            for (let second = first + 1; second < candidates.length; second += 1) {
                spread = Math.max(
                    spread,
                    distanceMeters(candidates[first], candidates[second]),
                );
            }
        }

        const sampleCount = candidates.length;
        const accuracy = candidates.length
            ? Math.max(...candidates.map(sample => sample.accuracy))
            : Number(latest?.accuracy || 999);
        const point = candidates.length
            ? {
                lat: medianNumber(candidates.map(sample => sample.lat)),
                lng: medianNumber(candidates.map(sample => sample.lng)),
            }
            : (latest ? { lat: latest.lat, lng: latest.lng } : null);
        const locked = sampleCount >= requiredSamples && spread <= maxSpread;
        let reason = null;

        if (!locked) {
            if (latest && latest.accuracy > maxAccuracy) {
                reason = 'accuracy';
            } else if (sampleCount < requiredSamples) {
                reason = 'samples';
            } else {
                reason = 'spread';
            }
        }

        return {
            locked,
            reason,
            point,
            accuracy,
            sampleCount,
            spread,
        };
    }

    function nextGpsOffRouteConfirmation(
        currentCount,
        isOnActiveRoute,
        requiredSamples = 3,
    ) {
        const required = Math.max(1, Number(requiredSamples || 3));

        if (isOnActiveRoute) {
            return {
                count: 0,
                confirmed: false,
            };
        }

        const count = Math.min(
            required,
            Math.max(0, Number(currentCount || 0)) + 1,
        );

        return {
            count,
            confirmed: count >= required,
        };
    }

    function classifyGpsSignal(accuracy, options = {}) {
        const meters = Number(accuracy);
        const strong = Math.max(1, Number(options.strong || 20));
        const fair = Math.max(strong, Number(options.fair || 45));
        const reject = Math.max(fair, Number(options.reject || 60));

        if (!Number.isFinite(meters) || meters <= 0) return 'unknown';
        if (meters <= strong) return 'strong';
        if (meters <= fair) return 'fair';
        if (meters <= reject) return 'weak';
        return 'rejected';
    }

    function percentileNumber(values, percentile) {
        const sorted = (Array.isArray(values) ? values : [])
            .map(Number)
            .filter(Number.isFinite)
            .sort((a, b) => a - b);

        if (!sorted.length) return null;
        if (sorted.length === 1) return sorted[0];

        const position = Math.max(0, Math.min(1, Number(percentile || 0)))
            * (sorted.length - 1);
        const lower = Math.floor(position);
        const upper = Math.ceil(position);
        const weight = position - lower;

        return sorted[lower] + ((sorted[upper] - sorted[lower]) * weight);
    }

    function summarizeGpsCalibration(samples, options = {}) {
        const normalized = (Array.isArray(samples) ? samples : [])
            .map(sample => ({
                timestamp: Number(sample?.timestamp),
                accuracy: Number(sample?.accuracy),
                snapDistance: Number(sample?.snapDistance),
                accepted: sample?.accepted === true,
                status: String(sample?.status || ''),
            }))
            .filter(sample => Number.isFinite(sample.accuracy) && sample.accuracy > 0);
        const accuracies = normalized.map(sample => sample.accuracy);
        const snapDistances = normalized
            .map(sample => sample.snapDistance)
            .filter(Number.isFinite);
        const acceptedCount = normalized.filter(sample => sample.accepted).length;
        const rejectedCount = normalized.filter(sample => (
            !sample.accepted
            && !['calibrating', 'tracking_started'].includes(sample.status)
        )).length;
        const weakCount = normalized.filter(sample => (
            classifyGpsSignal(sample.accuracy, options) === 'weak'
            || classifyGpsSignal(sample.accuracy, options) === 'rejected'
        )).length;
        const firstTimestamp = normalized
            .map(sample => sample.timestamp)
            .filter(Number.isFinite)
            .sort((a, b) => a - b)[0] || null;
        const lastTimestamp = normalized
            .map(sample => sample.timestamp)
            .filter(Number.isFinite)
            .sort((a, b) => b - a)[0] || null;
        const p95Accuracy = percentileNumber(accuracies, 0.95);
        let grade = 'not-ready';
        let recommendation = 'Collect at least four GPS readings while walking a campus route.';

        if (normalized.length >= 4) {
            if (p95Accuracy <= 20 && acceptedCount / normalized.length >= 0.8) {
                grade = 'excellent';
                recommendation = 'GPS quality is field-ready with the current safe routing thresholds.';
            } else if (p95Accuracy <= 35 && acceptedCount / normalized.length >= 0.6) {
                grade = 'good';
                recommendation = 'GPS is usable. Repeat the walk near buildings to confirm signal stability.';
            } else if (p95Accuracy <= 60) {
                grade = 'fair';
                recommendation = 'Signal is inconsistent. Test in a more open area and keep Tap My Location available.';
            } else {
                grade = 'poor';
                recommendation = 'GPS is too weak for reliable routing in this area. Use Tap My Location as the fallback.';
            }
        }

        return {
            sampleCount: normalized.length,
            acceptedCount,
            rejectedCount,
            weakCount,
            acceptedRate: normalized.length ? acceptedCount / normalized.length : 0,
            averageAccuracy: accuracies.length
                ? accuracies.reduce((total, value) => total + value, 0) / accuracies.length
                : null,
            medianAccuracy: percentileNumber(accuracies, 0.5),
            p95Accuracy,
            maxAccuracy: accuracies.length ? Math.max(...accuracies) : null,
            averageSnapDistance: snapDistances.length
                ? snapDistances.reduce((total, value) => total + value, 0) / snapDistances.length
                : null,
            maxSnapDistance: snapDistances.length ? Math.max(...snapDistances) : null,
            durationMs: firstTimestamp !== null && lastTimestamp !== null
                ? Math.max(0, lastTimestamp - firstTimestamp)
                : 0,
            grade,
            recommendation,
        };
    }

    global.WayfindingRouting = Object.freeze({
        isPathBlocked,
        shortestPath,
        outdoorShortestPath,
        indoorShortestPath,
        selectBestEntranceCandidate,
        normalizeBearing,
        bearingBetween,
        relativeTurn,
        cardinalDirection,
        distanceMeters,
        evaluateGpsQualitySamples,
        nextGpsOffRouteConfirmation,
        classifyGpsSignal,
        summarizeGpsCalibration,
    });
})(typeof window !== 'undefined' ? window : globalThis);
