/* =========================================================
   LOCAL-DEVELOPMENT GPS WALK SIMULATOR
   Loaded only when local debug mode and ?gps_simulator=1 are active.
========================================================= */
(function () {
    if (window.WAYFINDING_GPS_SIMULATOR_ENABLED !== true) return;
    if (window.WayfindingGpsSimulator) return;

    const simulatorBridge = window.WayfindingSimulatorBridge;
    if (!simulatorBridge) return;

    function getSimulatorMap() {
        return simulatorBridge.map || null;
    }

    const ROUTE_WAYPOINTS = [
        { lat: 10.251270992849824, lng: 124.9855543594516 },
        { lat: 10.251188169056912, lng: 124.98544341155898 },
        { lat: 10.251120404119328, lng: 124.98526359945716 },
        { lat: 10.251052639167254, lng: 124.98509335182888 },
        { lat: 10.25101687432559, lng: 124.98501301025148 },
        { lat: 10.25097546239862, lng: 124.98492119130586 },
        { lat: 10.250930285744843, lng: 124.98483893683378 },
        { lat: 10.250821108804956, lng: 124.98471459867828 },
        { lat: 10.250754804983591, lng: 124.98467609621363 },
        { lat: 10.250597107724309, lng: 124.98460365078566 },
        { lat: 10.250512401392038, lng: 124.98453096078704 },
        { lat: 10.250406989035808, lng: 124.98442957736796 },
        { lat: 10.25031368497161, lng: 124.98427400951788 },
        { lat: 10.25026440760041, lng: 124.984095076902 },
        { lat: 10.25019428185306, lng: 124.98374858685472 },
        { lat: 10.250103928316287, lng: 124.98334114028364 },
        { lat: 10.249996633457917, lng: 124.983077160815 },
        { lat: 10.249851691223112, lng: 124.98287056818742 },
        { lat: 10.249599453929145, lng: 124.9827117979273 },
        { lat: 10.249426275969268, lng: 124.9827117979273 },
        { lat: 10.249254980285407, lng: 124.982809355557 },
        { lat: 10.249165530308732, lng: 124.9829622519361 },
        { lat: 10.249081555227129, lng: 124.98310386978396 },
        { lat: 10.249023448609508, lng: 124.98337748528292 },
        { lat: 10.248968859815752, lng: 124.9837103289607 },
        { lat: 10.24884115421276, lng: 124.98368417347172 },
        { lat: 10.248461084198969, lng: 124.98360607501046 },
    ];

    const STEP_DISTANCE_METERS = 4;
    const BASE_INTERVAL_MS = 1800;
    const ACCURACY_METERS = 5;
    const SIGNAL_PROFILES = Object.freeze({
        stable: { label: 'Stable · 5m' },
        fair: { label: 'Fair + jitter · 32m' },
        weak: { label: 'Weak · 75m' },
        drift: { label: 'Post-lock drift · 38m' },
        false_jump: { label: 'False strong jump · 8m' },
        no_heading: { label: 'No device heading · 8m' },
    });
    const watchers = new Map();
    let nextWatchId = 1;
    let currentIndex = 0;
    let speedMultiplier = 1;
    let timerId = null;
    let running = false;
    let panel = null;
    let signalProfile = 'stable';
    let emittedReadingCount = 0;

    function distanceMeters(a, b) {
        const earthRadius = 6371000;
        const toRadians = (degrees) => Number(degrees) * Math.PI / 180;
        const lat1 = toRadians(a.lat);
        const lat2 = toRadians(b.lat);
        const deltaLat = toRadians(b.lat - a.lat);
        const deltaLng = toRadians(b.lng - a.lng);
        const h = Math.sin(deltaLat / 2) ** 2
            + Math.cos(lat1) * Math.cos(lat2) * Math.sin(deltaLng / 2) ** 2;

        return earthRadius * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
    }

    function bearingBetween(a, b) {
        const toRadians = (degrees) => Number(degrees) * Math.PI / 180;
        const toDegrees = (radians) => Number(radians) * 180 / Math.PI;
        const lat1 = toRadians(a.lat);
        const lat2 = toRadians(b.lat);
        const deltaLng = toRadians(b.lng - a.lng);
        const y = Math.sin(deltaLng) * Math.cos(lat2);
        const x = Math.cos(lat1) * Math.sin(lat2)
            - Math.sin(lat1) * Math.cos(lat2) * Math.cos(deltaLng);

        return (toDegrees(Math.atan2(y, x)) + 360) % 360;
    }

    function buildWalkingRoute(waypoints) {
        const route = [];

        waypoints.forEach((start, waypointIndex) => {
            const end = waypoints[waypointIndex + 1];
            if (!end) {
                route.push({ ...start });
                return;
            }

            const segmentDistance = distanceMeters(start, end);
            const steps = Math.max(1, Math.ceil(segmentDistance / STEP_DISTANCE_METERS));

            for (let step = 0; step < steps; step += 1) {
                const progress = step / steps;
                route.push({
                    lat: start.lat + ((end.lat - start.lat) * progress),
                    lng: start.lng + ((end.lng - start.lng) * progress),
                });
            }
        });

        return route;
    }

    const presetWalkingRoute = buildWalkingRoute(ROUTE_WAYPOINTS);
    let walkingRoute = presetWalkingRoute;
    let routeMode = 'preset';
    let selectingCustomStart = false;
    let customStartPoint = null;
    let customStartMarker = null;
    let preparedDestinationKey = null;

    function currentPoint() {
        return walkingRoute[Math.min(currentIndex, walkingRoute.length - 1)];
    }

    function offsetPointMeters(point, northMeters = 0, eastMeters = 0) {
        const latitudeOffset = Number(northMeters) / 111320;
        const longitudeScale = 111320 * Math.cos(Number(point.lat) * Math.PI / 180);
        return {
            lat: Number(point.lat) + latitudeOffset,
            lng: Number(point.lng) + (Number(eastMeters) / longitudeScale),
        };
    }

    function simulatedSignal(point) {
        const reading = emittedReadingCount;
        if (signalProfile === 'fair') {
            const jitter = [4, -5, 7, -3][reading % 4];
            return { point: offsetPointMeters(point, jitter * 0.45, jitter), accuracy: 32 };
        }
        if (signalProfile === 'weak') {
            return { point: offsetPointMeters(point, 18, -14), accuracy: 75 };
        }
        if (signalProfile === 'drift' && reading > 5) {
            const driftMeters = Math.min(28, (reading - 5) * 3.5);
            return { point: offsetPointMeters(point, driftMeters * 0.25, driftMeters), accuracy: 38 };
        }
        if (signalProfile === 'false_jump' && reading === 14) {
            return { point: offsetPointMeters(point, 120, 0), accuracy: 8 };
        }
        return {
            point,
            accuracy: signalProfile === 'no_heading' ? 8 : ACCURACY_METERS,
        };
    }

    function createPosition() {
        const point = currentPoint();
        const nextPoint = walkingRoute[Math.min(currentIndex + 1, walkingRoute.length - 1)];
        const intervalSeconds = (BASE_INTERVAL_MS / speedMultiplier) / 1000;
        const signal = simulatedSignal(point);
        emittedReadingCount += 1;

        return {
            coords: {
                latitude: signal.point.lat,
                longitude: signal.point.lng,
                accuracy: signal.accuracy,
                altitude: null,
                altitudeAccuracy: null,
                heading: signalProfile !== 'no_heading' && distanceMeters(point, nextPoint) > 0.2
                    ? bearingBetween(point, nextPoint)
                    : null,
                speed: STEP_DISTANCE_METERS / intervalSeconds,
            },
            timestamp: Date.now(),
        };
    }

    function notifyWatchers() {
        const position = createPosition();

        watchers.forEach(({ success }) => {
            try {
                success(position);
            } catch (error) {
                console.error('GPS simulator watcher failed:', error);
            }
        });

        updatePanel();
    }

    function progressPercent() {
        if (walkingRoute.length <= 1) return 0;
        return (currentIndex / (walkingRoute.length - 1)) * 100;
    }

    function setStatus(message) {
        const status = panel?.querySelector('[data-gps-sim-status]');
        if (status) status.textContent = message;
    }

    function updatePanel() {
        if (!panel) return;

        const progress = Math.max(0, Math.min(100, progressPercent()));
        const progressBar = panel.querySelector('[data-gps-sim-progress]');
        const progressText = panel.querySelector('[data-gps-sim-progress-text]');
        const pointText = panel.querySelector('[data-gps-sim-point]');
        const startButton = panel.querySelector('[data-gps-sim-start]');
        const pauseButton = panel.querySelector('[data-gps-sim-pause]');
        const presetButton = panel.querySelector('[data-gps-sim-preset]');
        const chooseStartButton = panel.querySelector('[data-gps-sim-choose-start]');
        const sourceText = panel.querySelector('[data-gps-sim-source]');

        if (progressBar) progressBar.style.width = `${progress}%`;
        if (progressText) progressText.textContent = `${Math.round(progress)}% route`;
        if (pointText) pointText.textContent = `Point ${currentIndex + 1} / ${walkingRoute.length}`;
        if (startButton) startButton.disabled = running;
        if (pauseButton) {
            pauseButton.disabled = watchers.size === 0;
            pauseButton.textContent = running ? 'Pause' : 'Resume';
        }
        if (presetButton) {
            presetButton.classList.toggle('is-active', routeMode === 'preset');
        }
        if (chooseStartButton) {
            chooseStartButton.classList.toggle('is-active', routeMode === 'custom');
            chooseStartButton.classList.toggle('is-picking', selectingCustomStart);
            chooseStartButton.textContent = selectingCustomStart ? 'Cancel Pick' : 'Choose Start';
        }
        if (sourceText) {
            sourceText.textContent = routeMode === 'custom'
                ? 'Source: Custom map location'
                : 'Source: Preset campus walk';
        }
    }

    function clearTimer() {
        if (timerId !== null) {
            clearInterval(timerId);
            timerId = null;
        }
    }

    function scheduleWalking() {
        clearTimer();
        if (!running) return;

        timerId = setInterval(() => {
            if (currentIndex >= walkingRoute.length - 1) {
                running = false;
                clearTimer();
                setStatus('Route complete. Press Reset to run the campus walk again.');
                updatePanel();
                return;
            }

            currentIndex += 1;
            notifyWatchers();
        }, Math.max(250, BASE_INTERVAL_MS / speedMultiplier));
    }

    function ensureGpsTracking() {
        if (watchers.size > 0) return true;

        if (typeof window.startOutdoorLiveGpsTracking !== 'function') {
            setStatus('Live GPS module is not ready. Reload the page and try again.');
            return false;
        }

        window.startOutdoorLiveGpsTracking();
        return watchers.size > 0;
    }

    function deduplicateRoutePoints(points) {
        return points.filter((point, index) => (
            index === 0 || distanceMeters(points[index - 1], point) > 0.4
        ));
    }

    function prepareCustomWalkingRoute() {
        if (routeMode !== 'custom' || !customStartPoint) return true;

        if (typeof window.getWayfindingGpsSimulatorRoute !== 'function') {
            setStatus('Route data is not ready. Choose a destination and try again.');
            return false;
        }

        const routeSnapshot = window.getWayfindingGpsSimulatorRoute();
        if (!routeSnapshot?.destinationKey || !Array.isArray(routeSnapshot.points)) {
            setStatus('Custom start saved. Choose a destination before pressing Start Walk.');
            return false;
        }

        if (
            preparedDestinationKey !== routeSnapshot.destinationKey
            || walkingRoute.length <= 1
        ) {
            const routePoints = deduplicateRoutePoints([
                customStartPoint,
                ...routeSnapshot.points,
            ]);
            walkingRoute = buildWalkingRoute(routePoints);
            currentIndex = 0;
            preparedDestinationKey = routeSnapshot.destinationKey;
        }

        return walkingRoute.length > 1;
    }

    function start() {
        if (!ensureGpsTracking()) return;
        if (!prepareCustomWalkingRoute()) {
            running = false;
            updatePanel();
            return;
        }

        if (currentIndex >= walkingRoute.length - 1) {
            currentIndex = 0;
        }

        running = true;
        notifyWatchers();
        scheduleWalking();
        setStatus('Walking simulation active. The GPS dot and guidance should update automatically.');
        updatePanel();
    }

    function pause() {
        running = false;
        clearTimer();
        setStatus('Simulation paused. Press Resume when you are ready.');
        updatePanel();
    }

    function togglePause() {
        if (running) {
            pause();
        } else {
            start();
        }
    }

    function reset() {
        running = false;
        clearTimer();
        currentIndex = 0;
        emittedReadingCount = 0;

        if (watchers.size > 0) {
            notifyWatchers();
        }

        setStatus(
            routeMode === 'custom'
                ? 'Reset to your custom starting point. Press Start Walk when ready.'
                : 'Reset to the north campus entrance. Choose a destination, then press Start Walk.'
        );
        updatePanel();
    }

    function removeCustomStartMarker() {
        const campusMap = getSimulatorMap();
        if (customStartMarker && campusMap?.hasLayer(customStartMarker)) {
            campusMap.removeLayer(customStartMarker);
        }

        customStartMarker = null;
    }

    function cancelCustomStartSelection() {
        selectingCustomStart = false;

        const campusMap = getSimulatorMap();
        if (campusMap) {
            campusMap.off('click', handleCustomStartMapClick);
            campusMap.getContainer()?.classList.remove('gps-simulator-picking-start');
        }

        updatePanel();
    }

    function setCustomStartPoint(point) {
        routeMode = 'custom';
        customStartPoint = {
            lat: Number(point.lat),
            lng: Number(point.lng),
        };
        preparedDestinationKey = null;
        walkingRoute = [customStartPoint];
        currentIndex = 0;
        running = false;
        clearTimer();
        removeCustomStartMarker();

        customStartMarker = L.marker(customStartPoint, {
            icon: L.divIcon({
                className: 'gps-simulator-start-marker',
                html: '<div class="gps-simulator-start-pin"><span>SIM</span></div>',
                iconSize: [34, 34],
                iconAnchor: [17, 31],
            }),
            interactive: false,
            zIndexOffset: 100000,
        }).addTo(getSimulatorMap());

        ensureGpsTracking();
        notifyWatchers();
        [120, 240, 360].forEach(delay => {
            window.setTimeout(() => {
                if (routeMode === 'custom' && watchers.size > 0) {
                    notifyWatchers();
                }
            }, delay);
        });

        setStatus('Custom start selected and snapped to a campus path. Choose a destination, then press Start Walk.');
        updatePanel();
    }

    function handleCustomStartMapClick(event) {
        cancelCustomStartSelection();

        if (
            !event?.latlng
            || typeof simulatorBridge.nearestNodeKey !== 'function'
            || !simulatorBridge.outdoorNodeCoords
        ) {
            setStatus('Campus routing data is still loading. Try Choose Start again in a moment.');
            return;
        }

        if (
            typeof simulatorBridge.isInsideCampus === 'function'
            && !simulatorBridge.isInsideCampus(event.latlng.lat, event.latlng.lng)
        ) {
            setStatus('Choose a starting location inside the campus boundary.');
            return;
        }

        const nearestKey = simulatorBridge.nearestNodeKey(
            event.latlng.lat,
            event.latlng.lng
        );
        const snappedCoordinate = nearestKey
            ? simulatorBridge.outdoorNodeCoords[nearestKey]
            : null;

        if (!snappedCoordinate) {
            setStatus('No valid campus path was found near that point. Choose another location.');
            return;
        }

        setCustomStartPoint({
            lat: Number(snappedCoordinate[0]),
            lng: Number(snappedCoordinate[1]),
        });
    }

    function chooseCustomStart() {
        if (selectingCustomStart) {
            cancelCustomStartSelection();
            setStatus('Custom start selection cancelled. Your current simulator route was kept.');
            return;
        }

        const campusMap = getSimulatorMap();
        if (!campusMap?.on) {
            setStatus('Campus map is still loading. Try again in a moment.');
            return;
        }

        pause();
        selectingCustomStart = true;
        campusMap.getContainer()?.classList.add('gps-simulator-picking-start');
        campusMap.on('click', handleCustomStartMapClick);
        setStatus('Click any location on the campus map. It will snap to the nearest valid path.');
        updatePanel();
    }

    function usePresetRoute() {
        cancelCustomStartSelection();
        routeMode = 'preset';
        customStartPoint = null;
        preparedDestinationKey = null;
        walkingRoute = presetWalkingRoute;
        currentIndex = 0;
        running = false;
        clearTimer();
        removeCustomStartMarker();

        if (watchers.size > 0) {
            notifyWatchers();
        }

        setStatus('Preset campus walk selected. Choose a destination, then press Start Walk.');
        updatePanel();
    }

    function setSpeed(multiplier) {
        const parsed = Number(multiplier);
        speedMultiplier = [1, 2, 4].includes(parsed) ? parsed : 1;

        if (running) {
            scheduleWalking();
        }

        setStatus(`Simulation speed set to ${speedMultiplier}×.`);
        updatePanel();
    }

    function setSignalProfile(profile) {
        signalProfile = Object.hasOwn(SIGNAL_PROFILES, profile) ? profile : 'stable';
        emittedReadingCount = 0;
        setStatus(`Signal profile: ${SIGNAL_PROFILES[signalProfile].label}.`);

        if (watchers.size > 0) notifyWatchers();
        updatePanel();
    }

    const geolocation = {
        getCurrentPosition(success) {
            setTimeout(() => success(createPosition()), 0);
        },

        watchPosition(success, error, options) {
            const watchId = nextWatchId;
            nextWatchId += 1;
            watchers.set(watchId, { success, error, options });
            setTimeout(() => {
                if (watchers.has(watchId)) success(createPosition());
            }, 0);
            updatePanel();
            return watchId;
        },

        clearWatch(watchId) {
            watchers.delete(watchId);

            if (watchers.size === 0) {
                running = false;
                clearTimer();
                setStatus('GPS tracking stopped. Press Start Walk to activate it again.');
            }

            updatePanel();
        },
    };

    function mountPanel() {
        panel = document.createElement('section');
        panel.id = 'gps-simulator-panel';
        panel.className = 'gps-simulator-panel';
        panel.setAttribute('aria-label', 'GPS walk simulator');
        panel.innerHTML = `
            <div class="gps-simulator-head">
                <div>
                    <div class="gps-simulator-kicker">Local Test Mode</div>
                    <div class="gps-simulator-title">Campus GPS Simulator</div>
                </div>
                <button type="button" class="gps-simulator-collapse" data-gps-sim-collapse
                        aria-label="Collapse GPS simulator" aria-expanded="true">−</button>
            </div>
            <div class="gps-simulator-body">
                <div class="gps-simulator-status" data-gps-sim-status>
                    Choose a destination, then press Start Walk. GPS starts automatically.
                </div>
                <div class="gps-simulator-progress-track" aria-hidden="true">
                    <div class="gps-simulator-progress-bar" data-gps-sim-progress></div>
                </div>
                <div class="gps-simulator-meta">
                    <span data-gps-sim-progress-text>0% route</span>
                    <span data-gps-sim-point>Point 1 / ${walkingRoute.length}</span>
                </div>
                <div class="gps-simulator-mode-row">
                    <button type="button" class="gps-simulator-mode-btn is-active"
                            data-gps-sim-preset>Preset Walk</button>
                    <button type="button" class="gps-simulator-mode-btn"
                            data-gps-sim-choose-start>Choose Start</button>
                </div>
                <div class="gps-simulator-source" data-gps-sim-source>Source: Preset campus walk</div>
                <div class="gps-simulator-controls">
                    <button type="button" class="gps-simulator-btn primary" data-gps-sim-start>Start Walk</button>
                    <button type="button" class="gps-simulator-btn" data-gps-sim-pause disabled>Pause</button>
                    <button type="button" class="gps-simulator-btn" data-gps-sim-reset>Reset</button>
                </div>
                <div class="gps-simulator-speed-row">
                    <label class="gps-simulator-speed-label" for="gps-simulator-speed">Walking speed</label>
                    <select class="gps-simulator-speed" id="gps-simulator-speed" data-gps-sim-speed>
                        <option value="1">1× Normal</option>
                        <option value="2">2× Fast</option>
                        <option value="4">4× Demo</option>
                    </select>
                </div>
                <div class="gps-simulator-speed-row">
                    <label class="gps-simulator-speed-label" for="gps-simulator-signal">GPS signal</label>
                    <select class="gps-simulator-speed" id="gps-simulator-signal" data-gps-sim-signal>
                        ${Object.entries(SIGNAL_PROFILES).map(([value, profile]) => (
                            `<option value="${value}">${profile.label}</option>`
                        )).join('')}
                    </select>
                </div>
                <div class="gps-simulator-dev-note">
                    Developer-only tool. It is never loaded for regular users or when debug mode is disabled.
                </div>
            </div>
        `;

        panel.querySelector('[data-gps-sim-start]').addEventListener('click', start);
        panel.querySelector('[data-gps-sim-pause]').addEventListener('click', togglePause);
        panel.querySelector('[data-gps-sim-reset]').addEventListener('click', reset);
        panel.querySelector('[data-gps-sim-preset]').addEventListener('click', usePresetRoute);
        panel.querySelector('[data-gps-sim-choose-start]').addEventListener('click', chooseCustomStart);
        panel.querySelector('[data-gps-sim-speed]').addEventListener('change', (event) => {
            setSpeed(event.target.value);
        });
        panel.querySelector('[data-gps-sim-signal]').addEventListener('change', (event) => {
            setSignalProfile(event.target.value);
        });
        panel.querySelector('[data-gps-sim-collapse]').addEventListener('click', (event) => {
            const collapsed = panel.classList.toggle('is-collapsed');
            event.currentTarget.textContent = collapsed ? '+' : '−';
            event.currentTarget.setAttribute('aria-expanded', String(!collapsed));
            event.currentTarget.setAttribute(
                'aria-label',
                collapsed ? 'Expand GPS simulator' : 'Collapse GPS simulator'
            );
        });

        document.body.appendChild(panel);
        updatePanel();
    }

    window.WayfindingGpsSimulator = {
        geolocation,
        start,
        pause,
        reset,
        setSpeed,
        setSignalProfile,
        chooseCustomStart,
        usePresetRoute,
        getState() {
            return {
                currentIndex,
                pointCount: walkingRoute.length,
                routeMode,
                signalProfile,
                emittedReadingCount,
                selectingCustomStart,
                running,
                speedMultiplier,
                watcherCount: watchers.size,
                progress: progressPercent(),
            };
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountPanel, { once: true });
    } else {
        mountPanel();
    }
})();
