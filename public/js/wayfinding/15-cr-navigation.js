const bridge = window.WayfindingCrBridge;
const modal = document.getElementById('cr-navigation-modal');
const dialog = modal?.querySelector('.cr-navigation-dialog');
const closeButton = document.getElementById('cr-navigation-close');
const modes = document.getElementById('cr-navigation-modes');
const modeButtons = Array.from(document.querySelectorAll('[data-cr-mode]'));
const status = document.getElementById('cr-navigation-status');
const statusText = document.getElementById('cr-navigation-status-text');
const results = document.getElementById('cr-navigation-results');
const context = document.getElementById('cr-navigation-context');
const list = document.getElementById('cr-navigation-list');
const changeStartButton = document.getElementById('cr-navigation-change-start');

let operationId = 0;
let lastFocusedElement = null;
let rankedRooms = [];
let stylesPromise = null;

// Indoor maps use a drawing coordinate scale that is larger than outdoor
// meters. This matches the indoor weighting already used by entrance routing.
const CR_INDOOR_DISTANCE_SCALE = 0.16;
const CR_CLOSEST_RANGE_METERS = 25;
const CR_NEARBY_RANGE_METERS = 100;

function ensureStyles() {
    if (stylesPromise) return stylesPromise;

    stylesPromise = new Promise(resolve => {
        const existing = document.getElementById('cr-navigation-styles');
        if (existing) {
            resolve();
            return;
        }

        const moduleUrl = new URL(import.meta.url);
        const cssUrl = new URL('../../css/wayfinding/17-cr-navigation.css', moduleUrl);
        const version = moduleUrl.searchParams.get('v');
        if (version) cssUrl.searchParams.set('v', version);

        const link = document.createElement('link');
        link.id = 'cr-navigation-styles';
        link.rel = 'stylesheet';
        link.href = cssUrl.href;
        link.addEventListener('load', resolve, { once: true });
        link.addEventListener('error', resolve, { once: true });
        document.head.appendChild(link);
    });

    return stylesPromise;
}

function normalizeRoomClassification(value) {
    return String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[_-]+/g, ' ')
        .replace(/\s+/g, ' ');
}

function isComfortRoom(room) {
    const props = room?.properties || {};
    const importedProps = props.properties || {};
    const roomType = normalizeRoomClassification(
        props.type || importedProps.type
    );

    // Type is authoritative. Room codes are labels only and may use any format.
    if (roomType.includes('restroom') || roomType.includes('rest room')) {
        return true;
    }

    const descriptiveText = [
        props.name,
        props.room_name,
        importedProps.name,
        importedProps.room_name,
    ].filter(Boolean).join(' ').toLowerCase();
    const roomCode = String(
        props.room_code || importedProps.room_code || ''
    ).trim();

    return descriptiveText.includes('comfort room')
        || descriptiveText.includes('toilet')
        || descriptiveText.includes('washroom')
        || /^(mcr|fcr|cr[\w-]*)$/i.test(roomCode);
}

function comfortRoomName(room) {
    const props = room?.properties || {};
    const code = String(props.room_code || '').trim().toUpperCase();

    if (code === 'MCR') return 'Male Comfort Room';
    if (code === 'FCR') return 'Female Comfort Room';
    return String(props.name || props.room_name || code || 'Comfort Room');
}

function roomBuilding(room) {
    const props = room?.properties || {};
    return String(props.building_name || 'Campus Building');
}

function roomFloor(room) {
    const props = room?.properties || {};
    return String(
        props.floor_label
        || `${Number(props.floor_number || 1)}F`
    );
}

function roomCode(room) {
    const props = room?.properties || {};
    return String(
        props.room_code || props.properties?.room_code || ''
    ).trim();
}

function formatRouteDistance(cost) {
    const meters = Math.max(0, Number(cost || 0));
    if (!Number.isFinite(meters)) return '';
    if (meters >= 1000) return `≈ ${(meters / 1000).toFixed(1)} km route`;
    return `≈ ${Math.max(1, Math.round(meters))} m route`;
}

function estimatedWalkingDistance(item) {
    if (!Number.isFinite(item?.estimate?.outdoorCost)) return Infinity;

    const indoorCost = Number.isFinite(item.estimate.indoorCost)
        ? item.estimate.indoorCost
        : 0;

    return item.estimate.outdoorCost
        + (indoorCost * CR_INDOOR_DISTANCE_SCALE);
}

function distanceTier(item, index, nearestDistance) {
    const distance = estimatedWalkingDistance(item);
    if (!Number.isFinite(distance)) return 'unavailable';
    if (index === 0) return 'nearest';

    const extraDistance = Math.max(0, distance - nearestDistance);
    if (extraDistance <= CR_CLOSEST_RANGE_METERS) return 'close-range';
    if (extraDistance <= CR_NEARBY_RANGE_METERS) return 'nearby-range';
    return 'standard';
}

function showStatus(message, kind = 'loading') {
    if (!status || !statusText) return;
    status.hidden = false;
    status.dataset.kind = kind;
    statusText.textContent = message;
}

function hideStatus() {
    if (status) status.hidden = true;
}

function setModeButtonsDisabled(disabled) {
    modeButtons.forEach(button => {
        button.disabled = disabled;
        button.setAttribute('aria-busy', disabled ? 'true' : 'false');
    });
}

function resetChooser() {
    rankedRooms = [];
    if (modes) modes.hidden = false;
    if (results) results.hidden = true;
    if (list) list.replaceChildren();
    hideStatus();
    setModeButtonsDisabled(false);
}

function showModal({ focus = true } = {}) {
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('cr-navigation-open');
    if (focus) {
        window.setTimeout(() => dialog?.focus({ preventScroll: true }), 20);
    }
}

function hideModal({ cancel = true, restoreFocus = true } = {}) {
    if (!modal) return;
    if (cancel) operationId += 1;
    modal.hidden = true;
    document.body.classList.remove('cr-navigation-open');

    if (restoreFocus && lastFocusedElement?.focus) {
        lastFocusedElement.focus({ preventScroll: true });
    }
}

function nextFrame() {
    return new Promise(resolve => window.requestAnimationFrame(resolve));
}

function startIsReady(mode, state) {
    const source = String(state?.source || '').toLowerCase();
    if (!state?.key) return false;
    if (mode === 'gps') return source.includes('gps');
    if (mode === 'path') return source === 'path' && !state.placing;
    return source.includes('default');
}

function waitForStart(mode, currentOperation) {
    const deadline = Date.now() + (mode === 'gps' ? 32000 : 20000);
    let waitingForGpsFallbackTap = false;

    return new Promise((resolve, reject) => {
        const check = () => {
            if (currentOperation !== operationId) {
                reject(new DOMException('Cancelled', 'AbortError'));
                return;
            }

            const state = bridge?.getStartState?.() || {};
            if (startIsReady(mode, state)) {
                resolve({ state, actualMode: mode });
                return;
            }

            if (
                mode === 'gps'
                && String(state.source || '').toLowerCase() === 'path'
                && state.placing
            ) {
                if (!waitingForGpsFallbackTap) {
                    waitingForGpsFallbackTap = true;
                    hideModal({ cancel: false, restoreFocus: false });
                    window.showWayfindingToast?.(
                        'GPS needs help. Tap your actual position on the campus path.',
                        { kind: 'warning', duration: 6500 }
                    );
                }

                if (startIsReady('path', state)) {
                    resolve({ state, actualMode: 'path' });
                    return;
                }
            }

            if (waitingForGpsFallbackTap && startIsReady('path', state)) {
                resolve({ state, actualMode: 'path' });
                return;
            }

            if (Date.now() >= deadline) {
                reject(new Error(
                    mode === 'gps'
                        ? 'GPS is taking too long. Try Pick Path or Default instead.'
                        : 'No start point was selected. Please try again.'
                ));
                return;
            }

            window.setTimeout(check, 300);
        };

        check();
    });
}

async function rankComfortRooms(currentOperation) {
    const rooms = (bridge?.getRooms?.() || []).filter(isComfortRoom);
    const ranked = [];

    await bridge?.prepareRooms?.(rooms);

    for (let index = 0; index < rooms.length; index += 1) {
        if (currentOperation !== operationId) {
            throw new DOMException('Cancelled', 'AbortError');
        }

        const room = rooms[index];
        const estimate = bridge?.estimateRoom?.(room);
        ranked.push({ room, estimate });

        if ((index + 1) % 3 === 0) await nextFrame();
    }

    return ranked.sort((a, b) => {
        const aReachable = Number.isFinite(a.estimate?.totalCost);
        const bReachable = Number.isFinite(b.estimate?.totalCost);

        if (aReachable !== bReachable) return aReachable ? -1 : 1;
        if (aReachable) {
            return estimatedWalkingDistance(a) - estimatedWalkingDistance(b);
        }

        return comfortRoomName(a.room).localeCompare(
            comfortRoomName(b.room)
        );
    });
}

function createSuggestion(item, index, nearestDistance) {
    const isReachable = Number.isFinite(item.estimate?.totalCost);
    const tier = distanceTier(item, index, nearestDistance);
    const suggestion = document.createElement('li');
    const button = document.createElement('button');
    const leading = document.createElement('span');
    const copy = document.createElement('span');
    const titleRow = document.createElement('span');
    const title = document.createElement('strong');
    const badge = document.createElement('small');
    const location = document.createElement('span');
    const distance = document.createElement('span');
    const arrow = document.createElement('span');

    suggestion.className = `cr-navigation-item is-${tier}`;
    button.type = 'button';
    button.className = 'cr-navigation-suggestion';
    button.dataset.crSuggestionIndex = String(index);
    button.dataset.crRoomId = String(item.room?.properties?.id || '');
    button.dataset.crBuilding = roomBuilding(item.room);
    button.dataset.crDistanceTier = tier;
    if (isReachable) {
        button.dataset.crOutdoorCost = String(item.estimate.outdoorCost);
        button.dataset.crIndoorCost = String(item.estimate.indoorCost);
        button.dataset.crTotalCost = String(item.estimate.totalCost);
        button.dataset.crWalkingEstimate = String(
            estimatedWalkingDistance(item)
        );
    }
    button.disabled = !isReachable;
    button.setAttribute(
        'aria-label',
        isReachable
            ? `Route to ${comfortRoomName(item.room)} in ${roomBuilding(item.room)}`
            : `${comfortRoomName(item.room)} in ${roomBuilding(item.room)} — route unavailable`
    );

    leading.className = 'cr-navigation-suggestion-icon';
    leading.textContent = '🚻';
    copy.className = 'cr-navigation-suggestion-copy';
    titleRow.className = 'cr-navigation-suggestion-title';
    title.textContent = comfortRoomName(item.room);
    badge.textContent = tier === 'unavailable'
        ? 'Route unavailable'
        : tier === 'nearest'
            ? 'Nearest CR'
            : tier === 'close-range'
                ? 'Closest Range'
                : tier === 'nearby-range'
                    ? 'Nearby'
                    : `Option ${index + 1}`;
    location.className = 'cr-navigation-suggestion-location';
    location.textContent = [
        roomBuilding(item.room),
        roomFloor(item.room),
        roomCode(item.room) ? `Code ${roomCode(item.room)}` : null,
    ].filter(Boolean).join(' · ');
    distance.className = 'cr-navigation-suggestion-distance';
    distance.textContent = isReachable
        ? formatRouteDistance(estimatedWalkingDistance(item))
        : 'A room door or indoor path link must be added';
    arrow.className = 'cr-navigation-suggestion-arrow';
    arrow.setAttribute('aria-hidden', 'true');
    arrow.textContent = '→';

    titleRow.append(title, badge);
    copy.append(titleRow, location, distance);
    button.append(leading, copy, arrow);
    suggestion.appendChild(button);
    return suggestion;
}

function renderSuggestions(items, mode) {
    rankedRooms = items;
    if (!results || !list || !context) return;

    list.replaceChildren();
    const nearestDistance = estimatedWalkingDistance(
        items.find(item => Number.isFinite(item.estimate?.totalCost))
    );
    items.forEach((item, index) => {
        list.appendChild(createSuggestion(item, index, nearestDistance));
    });

    const modeLabel = mode === 'gps'
        ? 'your GPS position'
        : mode === 'path'
            ? 'your selected map position'
            : 'the default campus entrance';
    const reachableCount = items.filter(item => (
        Number.isFinite(item.estimate?.totalCost)
    )).length;
    context.textContent = `${reachableCount} reachable of ${items.length} comfort room${items.length === 1 ? '' : 's'}, ranked using the complete outdoor and indoor route from ${modeLabel}.`;

    if (modes) modes.hidden = true;
    hideStatus();
    results.hidden = false;
    results.querySelector(
        '.cr-navigation-suggestion:not(:disabled)'
    )?.focus({ preventScroll: true });
}

async function chooseMode(mode) {
    const currentOperation = ++operationId;
    setModeButtonsDisabled(true);
    if (results) results.hidden = true;

    try {
        if (mode === 'path') {
            showStatus('Tap your current position on a campus path.');
            hideModal({ cancel: false, restoreFocus: false });
            bridge?.chooseStartMode?.('path');
            window.showWayfindingToast?.(
                'Tap your position on the map. CR suggestions will open automatically.',
                { kind: 'info', duration: 6000 }
            );
        } else {
            showStatus(
                mode === 'gps'
                    ? 'Waiting for an accurate GPS position…'
                    : 'Using the default campus entrance…'
            );
            bridge?.chooseStartMode?.(mode);
        }

        const ready = await waitForStart(mode, currentOperation);
        if (currentOperation !== operationId) return;

        showModal({ focus: false });
        if (modes) modes.hidden = true;
        showStatus('Checking all reachable comfort rooms…');
        const suggestions = await rankComfortRooms(currentOperation);

        if (!suggestions.length) {
            throw new Error('No reachable comfort room is available from this starting point.');
        }

        renderSuggestions(suggestions, ready.actualMode);
    } catch (error) {
        if (error?.name === 'AbortError') return;
        showModal({ focus: false });
        if (modes) modes.hidden = false;
        if (results) results.hidden = true;
        showStatus(error?.message || 'Unable to find a nearby comfort room.', 'error');
        setModeButtonsDisabled(false);
    }
}

async function open(trigger = null) {
    if (!bridge || !modal) {
        throw new Error('CR navigation is not ready yet. Please reload the page.');
    }

    await ensureStyles();
    operationId += 1;
    lastFocusedElement = trigger || document.activeElement;
    resetChooser();
    showModal();
}

modeButtons.forEach(button => {
    button.addEventListener('click', () => chooseMode(button.dataset.crMode));
});

list?.addEventListener('click', async event => {
    const button = event.target.closest('[data-cr-suggestion-index]');
    if (!button) return;

    const item = rankedRooms[Number(button.dataset.crSuggestionIndex)];
    if (!item || !await bridge?.routeToRoom?.(item.room)) {
        showStatus('This comfort room route is unavailable. Choose another suggestion.', 'error');
        return;
    }

    hideModal();
    window.showWayfindingToast?.(
        `Routing to ${comfortRoomName(item.room)} in ${roomBuilding(item.room)}.`,
        { kind: 'success', duration: 5200 }
    );
});

changeStartButton?.addEventListener('click', () => {
    operationId += 1;
    resetChooser();
    modes?.querySelector('button')?.focus({ preventScroll: true });
});
closeButton?.addEventListener('click', () => hideModal());
modal?.addEventListener('click', event => {
    if (event.target === modal) hideModal();
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && modal && !modal.hidden) hideModal();
});

window.WayfindingCrNavigation = Object.freeze({ open });
