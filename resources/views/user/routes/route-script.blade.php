<script>
    let campusGraph = {};
    let nodeCoords = {};
    let entryPoints = [];
    let buildingEntrances = [];
    let hazardPoints = [];
    let pathFeatures = [];
    let edgeMeta = {};
    let routingPathGeojson = null;

    let startNodeKey = null;
    let endNodeKey = null;
    let gatewayNodeKey = null;

    let startMarker = null;
    let destinationMarker = null;
    let currentLocationMarker = null;
    let routeLayer = null;
    let outsideGuideLine = null;
    let hazardLayer = null;

    let startSourceType = null;
    let usingOutsideCampusFlow = false;

    window.placingStartMode = false;

    function setRouteResultLabel(text) {
        const el = document.getElementById('route-result-label');
        if (el) el.textContent = text;
    }
    window.setRouteResultLabel = setRouteResultLabel;

    function updateRouteLabels() {
        const modeLabel = document.getElementById('route-mode-label');
        const sourceLabel = document.getElementById('route-source-label');
        const startLabel = document.getElementById('route-start-label');
        const gatewayLabel = document.getElementById('route-gateway-label');
        const endLabel = document.getElementById('route-end-label');

        if (modeLabel) modeLabel.textContent = window.placingStartMode ? 'Select Start Mode' : 'Idle';
        if (sourceLabel) sourceLabel.textContent = startSourceType ? startSourceType.replaceAll('_', ' ') : 'None';
        if (startLabel) startLabel.textContent = startNodeKey || 'Not selected';
        if (gatewayLabel) gatewayLabel.textContent = gatewayNodeKey || 'None';

        if (endLabel) {
            const selectedText =
                document.getElementById('destination-building-select')?.selectedOptions?.[0]?.text || 'Not selected';
            endLabel.textContent = endNodeKey ? selectedText : 'Not selected';
        }
    }
    window.updateRouteLabels = updateRouteLabels;

    function createDivIcon(html, size = [22, 22], anchor = [11, 11]) {
        return L.divIcon({
            html,
            className: '',
            iconSize: size,
            iconAnchor: anchor
        });
    }

    function formatCoordKey(lng, lat) {
        return `${Number(lng).toFixed(12)},${Number(lat).toFixed(12)}`;
    }

    function parseCoordKey(key) {
        const [lng, lat] = key.split(',').map(Number);
        return [lat, lng];
    }

    function edgeKey(a, b) {
        return [a, b].sort().join('__');
    }

    function getHazardsForPath(pathId) {
        return hazardPoints.filter(h =>
            Number(h.path_id) === Number(pathId) &&
            Boolean(h.is_active) === true &&
            Boolean(h.affects_routing) === true
        );
    }

    function getPathHazardProfile(pathId, mode = 'safe') {
        const hazards = getHazardsForPath(pathId);

        if (!hazards.length) {
            return {
                maxSeverity: 0,
                hasHazard: false,
                colorHint: 'safe',
                penalty: 1
            };
        }

        const maxSeverity = Math.max(...hazards.map(h => Number(h.severity_level || 1)));

        if (mode === 'preferred') {
            if (maxSeverity >= 3) {
                return {
                    maxSeverity: 3,
                    hasHazard: true,
                    colorHint: 'danger',
                    penalty: 1.15
                };
            }

            if (maxSeverity === 2) {
                return {
                    maxSeverity: 2,
                    hasHazard: true,
                    colorHint: 'caution',
                    penalty: 1.08
                };
            }

            return {
                maxSeverity: 1,
                hasHazard: true,
                colorHint: 'caution',
                penalty: 1.03
            };
        }

        if (maxSeverity >= 3) {
            return {
                maxSeverity: 3,
                hasHazard: true,
                colorHint: 'danger',
                penalty: 200
            };
        }

        if (maxSeverity === 2) {
            return {
                maxSeverity: 2,
                hasHazard: true,
                colorHint: 'caution',
                penalty: 4
            };
        }

        return {
            maxSeverity: 1,
            hasHazard: true,
            colorHint: 'caution',
            penalty: 1.7
        };
    }

    function addGraphEdge(keyA, latA, lngA, keyB, latB, lngB, weight, meta = {}) {
        if (!campusGraph[keyA]) campusGraph[keyA] = [];
        if (!campusGraph[keyB]) campusGraph[keyB] = [];

        nodeCoords[keyA] = [latA, lngA];
        nodeCoords[keyB] = [latB, lngB];

        campusGraph[keyA].push({
            key: keyB,
            weight,
            meta
        });

        campusGraph[keyB].push({
            key: keyA,
            weight,
            meta
        });

        edgeMeta[edgeKey(keyA, keyB)] = meta;
    }

    function buildGraphFromPaths(featureCollection, mode = 'safe') {
        campusGraph = {};
        nodeCoords = {};
        edgeMeta = {};

        const features = featureCollection?.features || [];
        pathFeatures = features;

        features.forEach(feature => {
            if (!feature.geometry) return;

            const props = feature.properties || {};
            if (props.is_blocked) return;

            const pathId = props.id;
            const pathName = props.name || 'Path';
            const rawType = String(props.type || '').trim().toLowerCase();

            let typeMultiplier = 1;
            if (rawType.includes('stairs')) typeMultiplier = 1.25;
            if (rawType === 'covered_stairs') typeMultiplier = 1.15;

            const hazardProfile = getPathHazardProfile(pathId, mode);

            const lines = feature.geometry.type === 'MultiLineString'
                ? feature.geometry.coordinates
                : [feature.geometry.coordinates];

            lines.forEach(line => {
                if (!Array.isArray(line) || line.length < 2) return;

                for (let i = 0; i < line.length - 1; i++) {
                    const a = line[i];
                    const b = line[i + 1];

                    const lngA = Number(a[0]);
                    const latA = Number(a[1]);
                    const lngB = Number(b[0]);
                    const latB = Number(b[1]);

                    const keyA = formatCoordKey(lngA, latA);
                    const keyB = formatCoordKey(lngB, latB);

                    const distance = map.distance([latA, lngA], [latB, lngB]);
                    const finalWeight = distance * typeMultiplier * hazardProfile.penalty;

                    addGraphEdge(
                        keyA,
                        latA,
                        lngA,
                        keyB,
                        latB,
                        lngB,
                        finalWeight,
                        {
                            pathId,
                            pathName,
                            pathType: rawType,
                            baseDistance: distance,
                            maxSeverity: hazardProfile.maxSeverity,
                            hasHazard: hazardProfile.hasHazard,
                            colorHint: hazardProfile.colorHint
                        }
                    );
                }
            });
        });
    }

    function nearestNodeKey(lat, lng) {
        let bestKey = null;
        let bestDistance = Infinity;

        Object.entries(nodeCoords).forEach(([key, coord]) => {
            const d = map.distance([lat, lng], coord);
            if (d < bestDistance) {
                bestDistance = d;
                bestKey = key;
            }
        });

        return bestKey;
    }

    function dijkstra(startKey, endKey) {
        const distances = {};
        const previous = {};
        const previousMeta = {};
        const visited = new Set();
        const queue = [];

        Object.keys(campusGraph).forEach(key => {
            distances[key] = Infinity;
            previous[key] = null;
            previousMeta[key] = null;
        });

        if (!campusGraph[startKey] || !campusGraph[endKey]) return null;

        distances[startKey] = 0;
        queue.push({ key: startKey, distance: 0 });

        while (queue.length > 0) {
            queue.sort((a, b) => a.distance - b.distance);
            const current = queue.shift();
            const currentKey = current.key;

            if (!current) break;
            if (visited.has(currentKey)) continue;

            visited.add(currentKey);

            if (currentKey === endKey) break;

            const neighbors = campusGraph[currentKey] || [];

            neighbors.forEach(neighbor => {
                if (visited.has(neighbor.key)) return;

                const alt = distances[currentKey] + neighbor.weight;

                if (alt < distances[neighbor.key]) {
                    distances[neighbor.key] = alt;
                    previous[neighbor.key] = currentKey;
                    previousMeta[neighbor.key] = neighbor.meta || null;
                    queue.push({ key: neighbor.key, distance: alt });
                }
            });
        }

        if (distances[endKey] === Infinity) return null;

        const path = [];
        const metas = [];
        let current = endKey;

        while (current) {
            path.unshift(current);
            if (previousMeta[current]) metas.unshift(previousMeta[current]);
            current = previous[current];
        }

        const maxSeverityOnRoute = metas.length
            ? Math.max(...metas.map(m => Number(m?.maxSeverity || 0)))
            : 0;

        const hasAnyHazard = metas.some(m => Boolean(m?.hasHazard));

        return {
            path,
            totalCost: distances[endKey],
            maxSeverityOnRoute,
            hasAnyHazard,
            metas
        };
    }

    function cloneGraphData(featureCollection, mode) {
        buildGraphFromPaths(featureCollection, mode);

        return {
            graph: JSON.parse(JSON.stringify(campusGraph)),
            coords: JSON.parse(JSON.stringify(nodeCoords))
        };
    }

    function dijkstraOnGraph(graphData, startKey, endKey) {
        const graph = graphData.graph || {};

        const distances = {};
        const previous = {};
        const previousMeta = {};
        const visited = new Set();
        const queue = [];

        Object.keys(graph).forEach(key => {
            distances[key] = Infinity;
            previous[key] = null;
            previousMeta[key] = null;
        });

        if (!graph[startKey] || !graph[endKey]) return null;

        distances[startKey] = 0;
        queue.push({ key: startKey, distance: 0 });

        while (queue.length > 0) {
            queue.sort((a, b) => a.distance - b.distance);
            const current = queue.shift();

            if (!current) break;

            const currentKey = current.key;

            if (visited.has(currentKey)) continue;
            visited.add(currentKey);

            if (currentKey === endKey) break;

            const neighbors = graph[currentKey] || [];

            neighbors.forEach(neighbor => {
                if (visited.has(neighbor.key)) return;

                const alt = distances[currentKey] + neighbor.weight;

                if (alt < distances[neighbor.key]) {
                    distances[neighbor.key] = alt;
                    previous[neighbor.key] = currentKey;
                    previousMeta[neighbor.key] = neighbor.meta || null;
                    queue.push({ key: neighbor.key, distance: alt });
                }
            });
        }

        if (distances[endKey] === Infinity) return null;

        const path = [];
        const metas = [];
        let current = endKey;

        while (current) {
            path.unshift(current);
            if (previousMeta[current]) metas.unshift(previousMeta[current]);
            current = previous[current];
        }

        const maxSeverityOnRoute = metas.length
            ? Math.max(...metas.map(m => Number(m?.maxSeverity || 0)))
            : 0;

        const hasAnyHazard = metas.some(m => Boolean(m?.hasHazard));

        return {
            path,
            totalCost: distances[endKey],
            maxSeverityOnRoute,
            hasAnyHazard,
            metas
        };
    }

    function sameRoutePath(a, b) {
        if (!a || !b || !a.path || !b.path) return false;
        if (a.path.length !== b.path.length) return false;

        for (let i = 0; i < a.path.length; i++) {
            if (a.path[i] !== b.path[i]) return false;
        }

        return true;
    }

    function clearRouteLayer() {
        if (routeLayer) {
            map.removeLayer(routeLayer);
            routeLayer = null;
        }
    }

    function clearStartMarker() {
        if (startMarker) {
            map.removeLayer(startMarker);
            startMarker = null;
        }
    }

    function clearDestinationMarker() {
        if (destinationMarker) {
            map.removeLayer(destinationMarker);
            destinationMarker = null;
        }
    }

    function clearCurrentLocationMarker() {
        if (currentLocationMarker) {
            map.removeLayer(currentLocationMarker);
            currentLocationMarker = null;
        }
    }

    function clearOutsideGuideLine() {
        if (outsideGuideLine) {
            map.removeLayer(outsideGuideLine);
            outsideGuideLine = null;
        }
    }

    function drawOutsideGuideLine(fromLat, fromLng, toLat, toLng) {
        clearOutsideGuideLine();

        outsideGuideLine = L.polyline([[fromLat, fromLng], [toLat, toLng]], {
            color: '#7c3aed',
            weight: 4,
            opacity: 0.85,
            dashArray: '10, 10'
        }).addTo(map);
    }

    function clearHazardLayer() {
        if (hazardLayer) {
            map.removeLayer(hazardLayer);
            hazardLayer = null;
        }
    }

    function drawHazardMarkers() {
        clearHazardLayer();

        hazardLayer = L.layerGroup().addTo(map);

        hazardPoints
            .filter(h => Boolean(h.is_active))
            .forEach(hazard => {
                const severity = Number(hazard.severity_level || 1);

                let color = '#facc15';
                if (severity === 2) color = '#f59e0b';
                if (severity >= 3) color = '#dc2626';

                L.circleMarker([hazard.latitude, hazard.longitude], {
                    radius: severity >= 3 ? 8 : 6,
                    color: '#ffffff',
                    weight: 2,
                    fillColor: color,
                    fillOpacity: 1
                }).bindPopup(`
                    <div style="font-family: 'Plus Jakarta Sans', sans-serif; min-width: 180px;">
                        <div style="font-weight: 800; margin-bottom: 4px;">${hazard.title || 'Hazard'}</div>
                        <div style="font-size: 12px; color: #475569; margin-bottom: 6px;">
                            ${hazard.description || ''}
                        </div>
                        <div style="font-size: 12px;">
                            <strong>Type:</strong> ${hazard.warning_type || 'Unknown'}<br>
                            <strong>Severity:</strong> ${severity}
                        </div>
                    </div>
                `).addTo(hazardLayer);
            });
    }

    function isInsideCampus(lat, lng) {
        if (!window.campusBounds) return false;
        return window.campusBounds.contains([lat, lng]);
    }

    async function loadHazardPoints() {
        const res = await fetch('/api/hazard-points', {
            headers: { 'Accept': 'application/json' }
        });

        hazardPoints = await res.json();
        window.hazardPoints = hazardPoints;
        drawHazardMarkers();
    }

    async function loadRoutingPaths() {
        const res = await fetch('/api/paths', {
            headers: { 'Accept': 'application/json' }
        });

        routingPathGeojson = await res.json();

        buildGraphFromPaths(routingPathGeojson, 'safe');
    }

    async function loadEntryPoints() {
        const res = await fetch('/api/entry-points', {
            headers: { 'Accept': 'application/json' }
        });

        entryPoints = await res.json();

        const select = document.getElementById('default-entry-select');
        if (!select) return;

        select.innerHTML = '<option value="">Select Default Starting Point</option>';

        entryPoints.forEach(point => {
            const nodeKey = nearestNodeKey(Number(point.latitude), Number(point.longitude));

            select.innerHTML += `
                <option
                    value="${nodeKey || ''}"
                    data-entry-id="${point.id}"
                    data-name="${point.name || ''}"
                    data-type="${point.type || ''}"
                    data-latitude="${point.latitude ?? ''}"
                    data-longitude="${point.longitude ?? ''}">
                    ${point.name}
                </option>
            `;
        });
    }

    async function loadDestinationBuildings() {
        const res = await fetch('/api/building-entrances', {
            headers: { 'Accept': 'application/json' }
        });

        buildingEntrances = await res.json();

        const select = document.getElementById('destination-building-select');
        if (!select) return;

        select.innerHTML = '<option value="">Select Destination Building</option>';

        buildingEntrances.forEach(entrance => {
            const nodeKey = nearestNodeKey(Number(entrance.latitude), Number(entrance.longitude));
            if (!nodeKey) return;

            select.innerHTML += `
                <option
                    value="${nodeKey}"
                    data-building-id="${entrance.building_id}"
                    data-entrance-id="${entrance.id}"
                    data-latitude="${entrance.latitude}"
                    data-longitude="${entrance.longitude}">
                    ${entrance.building_name || entrance.name || 'Destination'}
                </option>
            `;
        });
    }

    function enablePathStartPlacement() {
        window.placingStartMode = true;
        startSourceType = 'path';
        updateRouteLabels();
        setRouteResultLabel('Click only on a safe campus path to place your start point.');
    }
    window.enablePathStartPlacement = enablePathStartPlacement;

    window.setStartFromLatLng = function (lat, lng, label = 'Start Point', source = 'path') {
        const nearestKey = nearestNodeKey(lat, lng);

        if (!nearestKey) {
            alert('No nearest routing node found from path data.');
            return;
        }

        startNodeKey = nearestKey;
        gatewayNodeKey = null;
        usingOutsideCampusFlow = false;
        startSourceType = source;

        clearOutsideGuideLine();
        clearRouteLayer();
        clearStartMarker();

        startMarker = L.marker([lat, lng], {
            draggable: source === 'path',
            icon: createDivIcon('<div class="route-start-arrow"></div>')
        }).addTo(map).bindPopup(label);

        if (source === 'path') {
            startMarker.on('dragend', function (e) {
                const newLatLng = e.target.getLatLng();

                if (!isInsideCampus(newLatLng.lat, newLatLng.lng)) {
                    alert('Start point must remain inside the campus area.');
                    e.target.setLatLng([lat, lng]);
                    return;
                }

                const newKey = nearestNodeKey(newLatLng.lat, newLatLng.lng);
                if (!newKey) {
                    e.target.setLatLng([lat, lng]);
                    return;
                }

                startNodeKey = newKey;
                updateRouteLabels();
                setRouteResultLabel('Start point moved.');
            });
        }

        updateRouteLabels();
    };

    function useDefaultEntryPointAsStart() {
        const select = document.getElementById('default-entry-select');
        const option = select?.selectedOptions?.[0];

        if (!option || !option.value) {
            alert('Please select a default starting point first.');
            return;
        }

        const lat = Number(option.dataset.latitude);
        const lng = Number(option.dataset.longitude);

        startNodeKey = option.value;
        gatewayNodeKey = null;
        usingOutsideCampusFlow = false;
        startSourceType = 'default';

        clearOutsideGuideLine();
        clearRouteLayer();
        clearStartMarker();

        startMarker = L.marker([lat, lng], {
            draggable: false,
            icon: createDivIcon('<div class="route-start-arrow"></div>')
        }).addTo(map).bindPopup(option.text);

        map.setView([lat, lng], 19);
        updateRouteLabels();
        setRouteResultLabel('Default starting point selected.');
    }
    window.useDefaultEntryPointAsStart = useDefaultEntryPointAsStart;

    function setDestinationFromDropdown() {
        const select = document.getElementById('destination-building-select');
        const option = select?.selectedOptions?.[0];

        if (!option || !option.value) {
            endNodeKey = null;
            clearDestinationMarker();
            updateRouteLabels();
            return;
        }

        endNodeKey = option.value;

        const lat = Number(option.dataset.latitude);
        const lng = Number(option.dataset.longitude);

        clearDestinationMarker();

        destinationMarker = L.marker([lat, lng], {
            icon: createDivIcon('<div class="route-destination-dot"></div>', [18, 18], [9, 9])
        }).addTo(map).bindPopup(option.text);

        updateRouteLabels();
        setRouteResultLabel('Destination selected.');
    }

    function findNearestEntryPoint(lat, lng) {
        let nearest = null;
        let bestDistance = Infinity;

        entryPoints.forEach(point => {
            const d = map.distance([lat, lng], [Number(point.latitude), Number(point.longitude)]);
            if (d < bestDistance) {
                bestDistance = d;
                nearest = point;
            }
        });

        return nearest;
    }

    function useCurrentLocationAsStart() {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (position) {
                const lat = Number(position.coords.latitude);
                const lng = Number(position.coords.longitude);

                clearCurrentLocationMarker();
                clearRouteLayer();

                currentLocationMarker = L.marker([lat, lng], {
                    icon: createDivIcon('<div class="route-gps-dot"></div>', [18, 18], [9, 9])
                }).addTo(map).bindPopup('Your Current Location').openPopup();

                if (isInsideCampus(lat, lng)) {
                    const nearestKey = nearestNodeKey(lat, lng);

                    if (!nearestKey) {
                        alert('No path node found near your GPS location.');
                        return;
                    }

                    startNodeKey = nearestKey;
                    gatewayNodeKey = null;
                    usingOutsideCampusFlow = false;
                    startSourceType = 'gps_inside';

                    clearOutsideGuideLine();
                    clearStartMarker();

                    startMarker = L.marker([lat, lng], {
                        draggable: false,
                        icon: createDivIcon('<div class="route-start-arrow"></div>')
                    }).addTo(map).bindPopup('Start from GPS (Inside Campus)');

                    map.setView([lat, lng], 19);
                    updateRouteLabels();
                    setRouteResultLabel('GPS detected inside campus. Direct campus route will be used.');
                    return;
                }

                const nearestEntry = findNearestEntryPoint(lat, lng);

                if (!nearestEntry) {
                    alert('No entry point found for outside-campus routing.');
                    return;
                }

                const entryLat = Number(nearestEntry.latitude);
                const entryLng = Number(nearestEntry.longitude);
                const entryNodeKey = nearestNodeKey(entryLat, entryLng);

                if (!entryNodeKey) {
                    alert('No path node found near the nearest campus entry.');
                    return;
                }

                startNodeKey = entryNodeKey;
                gatewayNodeKey = entryNodeKey;
                usingOutsideCampusFlow = true;
                startSourceType = 'gps_outside';

                clearStartMarker();
                drawOutsideGuideLine(lat, lng, entryLat, entryLng);

                startMarker = L.marker([entryLat, entryLng], {
                    draggable: false,
                    icon: createDivIcon('<div class="route-start-arrow"></div>')
                }).addTo(map).bindPopup(`Campus Gateway: ${nearestEntry.name}`);

                map.fitBounds(L.latLngBounds([
                    [lat, lng],
                    [entryLat, entryLng]
                ]), { padding: [60, 60] });

                updateRouteLabels();
                setRouteResultLabel(`GPS is outside campus. Go first to ${nearestEntry.name}, then follow the campus route.`);
            },
            function () {
                alert('Unable to get your current location.');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }
    window.useCurrentLocationAsStart = useCurrentLocationAsStart;

    function resetRouteSelection() {
        window.placingStartMode = false;
        startNodeKey = null;
        endNodeKey = null;
        gatewayNodeKey = null;
        usingOutsideCampusFlow = false;
        startSourceType = null;

        clearStartMarker();
        clearDestinationMarker();
        clearCurrentLocationMarker();
        clearRouteLayer();
        clearOutsideGuideLine();

        const destinationSelect = document.getElementById('destination-building-select');
        if (destinationSelect) destinationSelect.value = '';

        const entrySelect = document.getElementById('default-entry-select');
        if (entrySelect) entrySelect.value = '';

        updateRouteLabels();
        setRouteResultLabel('No route yet');
    }
    window.resetRouteSelection = resetRouteSelection;

    function drawSingleRoute(result, color, className, label) {
        if (!result || !result.path || result.path.length < 2) return [];

        const bounds = [];

        for (let i = 0; i < result.path.length - 1; i++) {
            const fromKey = result.path[i];
            const toKey = result.path[i + 1];
            const meta = result.metas[i] || {};

            const segment = L.polyline([
                parseCoordKey(fromKey),
                parseCoordKey(toKey)
            ], {
                pane: 'pathsPane',
                color: color,
                weight: 8,
                opacity: 1,
                lineCap: 'round',
                lineJoin: 'round',
                className: className
            }).bindPopup(`
                <div style="font-family: 'Plus Jakarta Sans', sans-serif; min-width: 190px;">
                    <div style="font-size: 14px; font-weight: 800; margin-bottom: 6px;">
                        ${label}
                    </div>
                    <div style="font-size: 12px; color: #475569;">
                        <strong>Path:</strong> ${meta.pathName || 'Route Segment'}<br>
                        <strong>Type:</strong> ${(meta.pathType || 'path').replaceAll('_', ' ')}<br>
                        <strong>Severity:</strong> ${Number(meta.maxSeverity || 0)}
                    </div>
                </div>
            `);

            segment.addTo(routeLayer);

            if (segment.getBounds().isValid()) {
                bounds.push(segment.getBounds());
            }
        }

        return bounds;
    }

    function buildRouteSummary(preferredResult, safeResult) {
        if (preferredResult && safeResult) {
            if (sameRoutePath(preferredResult, safeResult)) {
                if (preferredResult.maxSeverityOnRoute >= 3) {
                    return 'Only one route is available. It passes through severity level 3 hazard areas, so the route is shown in red.';
                }

                if (preferredResult.maxSeverityOnRoute >= 1) {
                    return 'Only one route is available. It includes severity level 1 or 2 hazard areas, so the route is shown in yellow.';
                }

                return 'One safe route found.';
            }

            return 'Two routes found: colored route = faster or preferred route, green route = safe alternative route.';
        }

        if (preferredResult && !safeResult) {
            if (preferredResult.maxSeverityOnRoute >= 3) {
                return 'No safe alternative found. Only the faster or available severity level 3 route is shown in red.';
            }

            if (preferredResult.maxSeverityOnRoute >= 1) {
                return 'No fully safe alternative found. Available route contains severity level 1 or 2 hazard areas and is shown in yellow.';
            }

            return 'One available route found.';
        }

        if (!preferredResult && safeResult) {
            return 'Safe route found.';
        }

        return 'Route not found.';
    }

    function findRouteFromSelection() {
        if (!startNodeKey) {
            alert('Please choose your starting point first.');
            return;
        }

        if (!endNodeKey) {
            alert('Please select a destination building.');
            return;
        }

        if (startNodeKey === endNodeKey) {
            alert('Start and destination cannot be the same.');
            return;
        }

        if (!routingPathGeojson) {
            alert('Routing path data is not ready yet.');
            return;
        }

        setRouteResultLabel('Computing preferred and safe routes...');

        const preferredGraph = cloneGraphData(routingPathGeojson, 'preferred');
        const preferredResult = dijkstraOnGraph(preferredGraph, startNodeKey, endNodeKey);

        const safeGraph = cloneGraphData(routingPathGeojson, 'safe');
        const safeResultRaw = dijkstraOnGraph(safeGraph, startNodeKey, endNodeKey);

        let safeResult = safeResultRaw;
        if (preferredResult && safeResult && sameRoutePath(preferredResult, safeResult)) {
            safeResult = null;
        }

        if (!preferredResult && !safeResultRaw) {
            alert('No route found.');
            setRouteResultLabel('Route not found.');
            return;
        }

        clearRouteLayer();
        routeLayer = L.layerGroup().addTo(map);

        let bounds = [];

        if (preferredResult) {
            let preferredColor = '#22c55e';
            let preferredClass = 'route-line-safe';
            let preferredLabel = 'Preferred Route';

            if (preferredResult.maxSeverityOnRoute >= 3) {
                preferredColor = '#dc2626';
                preferredClass = 'route-line-danger';
                preferredLabel = 'Preferred / Faster Route (Danger)';
            } else if (preferredResult.maxSeverityOnRoute >= 1) {
                preferredColor = '#facc15';
                preferredClass = 'route-line-caution';
                preferredLabel = 'Preferred / Faster Route (Caution)';
            } else {
                preferredColor = '#22c55e';
                preferredClass = 'route-line-safe';
                preferredLabel = 'Preferred Route (Safe)';
            }

            bounds = bounds.concat(
                drawSingleRoute(
                    preferredResult,
                    preferredColor,
                    preferredClass,
                    preferredLabel
                )
            );
        }

        if (safeResult) {
            bounds = bounds.concat(
                drawSingleRoute(
                    safeResult,
                    '#22c55e',
                    'route-line-safe',
                    'Safe Alternative Route'
                )
            );
        }

        if (outsideGuideLine && outsideGuideLine.getBounds().isValid()) {
            bounds.push(outsideGuideLine.getBounds());
        }

        if (bounds.length > 0) {
            let finalBounds = bounds[0];
            for (let i = 1; i < bounds.length; i++) {
                finalBounds.extend(bounds[i]);
            }
            map.fitBounds(finalBounds, { padding: [60, 60] });
        }

        let message = buildRouteSummary(preferredResult, safeResult);

        if (usingOutsideCampusFlow) {
            message = `Outside campus detected. Go first to campus entry, then follow the route. ${message}`;
        }

        setRouteResultLabel(message);
    }
    window.findRouteFromSelection = findRouteFromSelection;

    document.addEventListener('DOMContentLoaded', async function () {
        updateRouteLabels();
        setRouteResultLabel('Loading routing and hazard data...');

        await loadHazardPoints();
        await loadRoutingPaths();
        await loadEntryPoints();
        await loadDestinationBuildings();

        const destinationSelect = document.getElementById('destination-building-select');
        if (destinationSelect) {
            destinationSelect.addEventListener('change', setDestinationFromDropdown);
        }

        setRouteResultLabel('Routing is ready.');
    });
</script>
