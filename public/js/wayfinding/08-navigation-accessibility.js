/* =========================================================
   UNIFIED NAVIGATION + ACCESSIBLE FEEDBACK
   - Route preview, distance, ETA, hazards, GPS, and guidance.
   - Non-blocking toasts instead of native alert dialogs.
   - Connection/data status with retry.
   - Dialog focus and expanded/pressed accessibility state.
========================================================= */
(function () {
    if (window.__wayfindingNavigationAccessibilityInstalled) return;
    window.__wayfindingNavigationAccessibilityInstalled = true;

    const sheet = document.getElementById('navigation-sheet');
    const sheetBody = document.getElementById('navigation-sheet-body');
    const kicker = document.getElementById('navigation-kicker');
    const destination = document.getElementById('navigation-destination');
    const guidanceArrow = document.getElementById('navigation-guidance-arrow');
    const guidanceTitle = document.getElementById('navigation-guidance-title');
    const guidanceMeta = document.getElementById('navigation-guidance-meta');
    const distanceValue = document.getElementById('navigation-distance');
    const etaValue = document.getElementById('navigation-eta');
    const gpsQualityValue = document.getElementById('navigation-gps-quality');
    const safetyValue = document.getElementById('navigation-safety');
    const recenterButton = document.getElementById('navigation-recenter-btn');
    const pauseButton = document.getElementById('navigation-pause-btn');
    const endButton = document.getElementById('navigation-end-btn');
    const collapseButton = document.getElementById('navigation-collapse-btn');
    const detailsToggle = document.getElementById('navigation-details-toggle');
    const toastRegion = document.getElementById('wayfinding-toast-region');

    const connectionBanner = document.getElementById('wayfinding-connection-banner');
    const connectionTitle = document.getElementById('wayfinding-connection-title');
    const connectionMessage = document.getElementById('wayfinding-connection-message');
    const retryButton = document.getElementById('wayfinding-retry-btn');
    const connectionClose = document.getElementById('wayfinding-connection-close');

    const routeUiState = {
        hasRoute: false,
        started: false,
        paused: false,
        distanceMeters: null,
        remainingMeters: null,
        destination: 'Choose a destination',
        safety: 'Checking',
        gpsQuality: 'Not active',
        lastGuidance: null,
    };

    let readyBannerTimer = null;
    let lastFocusedBeforeDialog = null;
    let connectionHasRecoverableFailure = false;

    function formatDistance(meters) {
        const value = Math.max(0, Number(meters || 0));

        if (value >= 1000) {
            return `${(value / 1000).toFixed(value >= 10000 ? 0 : 1)} km`;
        }

        return `${Math.round(value)} m`;
    }

    function formatWalkTime(meters) {
        const value = Math.max(0, Number(meters || 0));
        const minutes = Math.max(1, Math.ceil(value / 80));
        return minutes === 1 ? '1 min' : `${minutes} min`;
    }

    function calculateRouteDistance(latlngs) {
        if (!Array.isArray(latlngs) || latlngs.length < 2 || typeof map === 'undefined') {
            return 0;
        }

        let total = 0;
        for (let index = 0; index < latlngs.length - 1; index += 1) {
            total += map.distance(latlngs[index], latlngs[index + 1]);
        }
        return total;
    }

    function selectedOptionText(select) {
        const option = select?.selectedOptions?.[0];
        if (!option || !option.value) return '';
        return String(option.textContent || '').trim();
    }

    function resolveDestinationLabel() {
        try {
            const type = typeof getDestinationType === 'function'
                ? getDestinationType()
                : document.getElementById('destination-type-select')?.value;

            if (type === 'room') {
                const room = typeof selectedIndoorRoomFeature !== 'undefined'
                    ? selectedIndoorRoomFeature
                    : null;
                const roomProps = room?.properties || {};
                const roomName = [
                    roomProps.room_code,
                    roomProps.name || roomProps.room_name,
                ].filter(Boolean).join(' · ');

                const building = typeof buildingRecords !== 'undefined'
                    ? (buildingRecords || []).find(item =>
                        Number(item.id) === Number(roomProps.building_id)
                    )
                    : null;

                return [roomName, building?.name].filter(Boolean).join(' — ')
                    || selectedOptionText(document.getElementById('destination-room-select'))
                    || 'Selected room or office';
            }

            if (type === 'landuse') {
                return selectedOptionText(document.getElementById('destination-landuse-select'))
                    || 'Selected campus area';
            }

            return selectedOptionText(document.getElementById('destination-building-select'))
                || 'Selected campus building';
        } catch (error) {
            return 'Selected destination';
        }
    }

    function setSheetTone(tone = 'normal') {
        if (!sheet) return;
        sheet.classList.remove('is-warning', 'is-danger', 'is-arrived');
        if (tone === 'warning') sheet.classList.add('is-warning');
        if (tone === 'danger') sheet.classList.add('is-danger');
        if (tone === 'arrived') sheet.classList.add('is-arrived');
    }

    function syncDetailsToggle() {
        if (!sheet || !detailsToggle) return;

        const collapsed = !sheet.hidden && sheet.classList.contains('is-collapsed');
        detailsToggle.hidden = !collapsed;
        detailsToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        detailsToggle.setAttribute(
            'aria-label',
            routeUiState.hasRoute ? 'Show route details' : 'Show GPS status'
        );
    }

    function collapseRouteDetails() {
        if (!sheet || sheet.hidden) return;

        sheet.classList.add('is-collapsed');
        collapseButton?.setAttribute('aria-expanded', 'false');
        collapseButton?.setAttribute('aria-label', 'Hide route details');
        syncDetailsToggle();
    }

    function expandRouteDetails() {
        if (!sheet || sheet.hidden) return;

        sheet.classList.remove('is-collapsed');
        collapseButton?.setAttribute('aria-expanded', 'true');
        collapseButton?.setAttribute('aria-label', 'Hide route details');
        syncDetailsToggle();
    }

    function showSheet() {
        if (!sheet) return;
        sheet.hidden = false;
        document.body.classList.toggle(
            'navigation-route-visible',
            routeUiState.hasRoute
        );
        syncDetailsToggle();
    }

    function hideSheet() {
        if (!sheet) return;
        sheet.hidden = true;
        sheet.classList.remove(
            'is-collapsed',
            'is-location-only',
            'is-started',
            'is-warning',
            'is-danger',
            'is-arrived'
        );
        document.body.classList.remove('navigation-route-visible');
        collapseButton?.setAttribute('aria-expanded', 'true');
        collapseButton?.setAttribute('aria-label', 'Hide route details');
        if (detailsToggle) detailsToggle.hidden = true;
    }

    function renderRouteState() {
        if (!sheet) return;

        if (destination) destination.textContent = routeUiState.destination;
        if (distanceValue) {
            distanceValue.textContent = Number.isFinite(routeUiState.remainingMeters)
                ? formatDistance(routeUiState.remainingMeters)
                : Number.isFinite(routeUiState.distanceMeters)
                    ? formatDistance(routeUiState.distanceMeters)
                    : '--';
        }
        if (etaValue) {
            const etaMeters = Number.isFinite(routeUiState.remainingMeters)
                ? routeUiState.remainingMeters
                : routeUiState.distanceMeters;
            etaValue.textContent = Number.isFinite(etaMeters) ? formatWalkTime(etaMeters) : '--';
        }
        if (gpsQualityValue) gpsQualityValue.textContent = routeUiState.gpsQuality;
        if (safetyValue) safetyValue.textContent = routeUiState.safety;

        if (routeUiState.hasRoute) {
            showSheet();
            if (pauseButton) pauseButton.hidden = !routeUiState.started;
            if (recenterButton) recenterButton.hidden = false;
            if (endButton) endButton.textContent = 'End';
        } else {
            if (pauseButton) pauseButton.hidden = true;
            if (recenterButton) recenterButton.hidden = true;
            if (endButton) endButton.textContent = 'Close';
        }
        sheet.classList.toggle('is-started', routeUiState.started);
        syncDetailsToggle();

        if (kicker) {
            kicker.textContent = routeUiState.started
                ? routeUiState.paused ? 'Navigation Paused' : 'Live Navigation'
                : routeUiState.hasRoute ? 'Route Preview' : 'Location Status';
        }
    }

    function getRouteSafety(result) {
        const metas = Array.isArray(result?.metas) ? result.metas : [];
        const maxSeverity = metas.reduce(
            (highest, meta) => Math.max(highest, Number(meta?.maxSeverity || 0)),
            0
        );
        const hasHazard = metas.some(meta => Boolean(meta?.hasHazard));

        if (maxSeverity >= 3) {
            return { label: 'High caution', tone: 'danger' };
        }
        if (hasHazard || maxSeverity > 0) {
            return { label: 'Use caution', tone: 'warning' };
        }
        return { label: 'Clear route', tone: 'normal' };
    }

    function setRoute(result, options = {}) {
        if (!result?.path || result.path.length < 2) return;

        const latlngs = result.path
            .map(key => {
                try {
                    return typeof parseCoordKey === 'function' ? parseCoordKey(key) : null;
                } catch (error) {
                    return null;
                }
            })
            .filter(Boolean);

        const replacingRoute = routeUiState.hasRoute && options.liveUpdate !== true;
        const wasCollapsed = sheet?.classList.contains('is-collapsed') === true;
        const liveUpdate = options.liveUpdate === true;
        const safety = getRouteSafety(result);

        routeUiState.hasRoute = true;
        routeUiState.started = true;
        routeUiState.paused = false;
        routeUiState.distanceMeters = calculateRouteDistance(latlngs);
        routeUiState.remainingMeters = null;
        if (routeUiState.hasRoute || routeUiState.gpsQuality === 'Not active') {
            routeUiState.destination = resolveDestinationLabel();
        }
        routeUiState.safety = safety.label;
        routeUiState.lastGuidance = null;

        sheet?.classList.remove('is-location-only');
        if (!liveUpdate || wasCollapsed) {
            sheet?.classList.add('is-collapsed');
        } else {
            sheet?.classList.remove('is-collapsed');
        }
        setSheetTone(safety.tone);

        if (guidanceArrow) {
            guidanceArrow.textContent = '↑';
            guidanceArrow.style.transform = 'rotate(0deg)';
        }
        if (guidanceTitle) {
            guidanceTitle.textContent = liveUpdate
                ? 'Route updated'
                : replacingRoute ? 'New route active' : 'Navigation active';
        }
        if (guidanceMeta) {
            guidanceMeta.textContent = liveUpdate
                ? 'Continue following the highlighted campus route.'
                : routeUiState.gpsQuality === 'Not active'
                    ? 'Follow the highlighted campus path to your destination.'
                    : 'Live GPS guidance will update as you walk.';
        }

        renderRouteState();
        if (!liveUpdate) {
            collapseRouteDetails();
            showToast(
                replacingRoute
                    ? 'New route selected. Navigation updated automatically.'
                    : 'Route ready. Navigation started automatically.',
                { kind: 'success', duration: 3600 }
            );
        }
    }

    function inferMessageTone(message) {
        const text = String(message || '').toLowerCase();

        if (/arrived|destination reached|ready|selected|computed|matched/.test(text)) {
            return 'success';
        }
        if (/failed|unable|unsupported|invalid|no route|not found|cannot|unavailable/.test(text)) {
            return 'error';
        }
        if (/please|weak|warning|hazard|outside|permission|calibrat|waiting/.test(text)) {
            return 'warning';
        }
        return 'info';
    }

    function updateStatus(message) {
        const text = String(message || '').trim();
        if (!text) return;

        if (routeUiState.hasRoute || routeUiState.gpsQuality === 'Not active') {
            routeUiState.destination = resolveDestinationLabel();
        }

        if (/^no route yet$/i.test(text)) {
            routeUiState.hasRoute = false;
            routeUiState.started = false;
            routeUiState.paused = false;
            routeUiState.distanceMeters = null;
            routeUiState.remainingMeters = null;
            routeUiState.safety = 'Checking';
            routeUiState.gpsQuality = 'Not active';
            hideSheet();
            return;
        }

        if (routeUiState.hasRoute || /gps|location/i.test(text)) {
            showSheet();
            renderRouteState();
        }

        const tone = inferMessageTone(text);
        if (tone === 'error') setSheetTone('danger');
        if (tone === 'warning' && !sheet?.classList.contains('is-danger')) {
            setSheetTone('warning');
        }
        if (/destination reached|arrived/i.test(text)) {
            setSheetTone('arrived');
            if (kicker) kicker.textContent = 'Destination Reached';
        }
    }

    function updateGuidance(instruction) {
        if (!instruction) return;

        routeUiState.lastGuidance = instruction;
        routeUiState.destination = resolveDestinationLabel();

        if (!routeUiState.started) {
            renderRouteState();
            return;
        }

        if (routeUiState.paused) return;

        if (guidanceTitle) guidanceTitle.textContent = instruction.title || 'Continue on route';
        if (guidanceMeta) guidanceMeta.textContent = instruction.meta || '';
        if (guidanceArrow) {
            guidanceArrow.textContent = instruction.symbol || '↑';
            guidanceArrow.style.transform = `rotate(${Number(instruction.arrowBearing || 0)}deg)`;
        }

        if (Number.isFinite(Number(instruction.remainingDistance))) {
            routeUiState.remainingMeters = Number(instruction.remainingDistance);
        }

        if (instruction.kind === 'arrived') {
            routeUiState.remainingMeters = 0;
            routeUiState.paused = false;
            setSheetTone('arrived');
            if (kicker) kicker.textContent = 'Destination Reached';
            if (pauseButton) pauseButton.hidden = true;
        }

        renderRouteState();
    }

    function updateGpsStatus(kind, title, text) {
        const combined = `${title || ''} ${text || ''}`;
        const accuracyMatch = combined.match(/\((\d+)m\)|accuracy(?:\s+is)?\s+(\d+)m/i);
        const accuracy = accuracyMatch ? Number(accuracyMatch[1] || accuracyMatch[2]) : null;
        const firstLocationStatus = !routeUiState.hasRoute && sheet?.hidden;

        routeUiState.gpsQuality = Number.isFinite(accuracy)
            ? accuracy <= 20 ? `${accuracy}m · Strong`
                : accuracy <= 45 ? `${accuracy}m · Fair`
                    : `${accuracy}m · Weak`
            : kind === 'loading' ? 'Calibrating'
            : kind === 'bad' ? 'Unavailable'
                    : 'Active';

        if (!routeUiState.hasRoute) {
            routeUiState.destination = title || 'Outdoor Live GPS';
            sheet?.classList.add('is-location-only');
        }

        showSheet();
        renderRouteState();

        if (!routeUiState.hasRoute) {
            if (guidanceTitle) guidanceTitle.textContent = title || 'Getting location';
            if (guidanceMeta) guidanceMeta.textContent = text || 'Waiting for a reliable GPS reading.';
            if (kicker) kicker.textContent = 'Location Status';

            if (firstLocationStatus) {
                collapseRouteDetails();
            }
        }

        if (kind === 'bad') setSheetTone('danger');
        if (kind === 'weak' || kind === 'loading') setSheetTone('warning');
        if (kind === 'good' && !routeUiState.hasRoute) setSheetTone('normal');
    }

    function clearGpsStatus() {
        routeUiState.gpsQuality = 'Not active';
        if (!routeUiState.hasRoute) {
            sheet?.classList.remove('is-location-only');
            hideSheet();
        }
        else renderRouteState();
    }

    function recenterRoute() {
        if (typeof map === 'undefined' || typeof routeLayer === 'undefined' || !routeLayer) {
            showToast('No active route is available to recenter.', { kind: 'warning' });
            return;
        }

        const points = [];
        routeLayer.eachLayer(layer => {
            if (typeof layer.getLatLngs !== 'function') return;
            const latlngs = layer.getLatLngs();
            const flat = Array.isArray(latlngs?.[0]) ? latlngs.flat(Infinity) : latlngs;
            (flat || []).forEach(point => {
                if (point?.lat !== undefined && point?.lng !== undefined) points.push(point);
            });
        });

        if (!points.length) {
            showToast('The route is still preparing. Please try again.', { kind: 'warning' });
            return;
        }

        map.fitBounds(L.latLngBounds(points), {
            paddingTopLeft: [40, 80],
            paddingBottomRight: [40, 260],
            animate: true,
        });
    }

    function togglePause() {
        if (!routeUiState.started) return;

        routeUiState.paused = !routeUiState.paused;
        if (pauseButton) pauseButton.textContent = routeUiState.paused ? 'Resume' : 'Pause';
        if (guidanceTitle) {
            guidanceTitle.textContent = routeUiState.paused
                ? 'Guidance paused'
                : routeUiState.lastGuidance?.title || 'Guidance resumed';
        }
        if (guidanceMeta) {
            guidanceMeta.textContent = routeUiState.paused
                ? 'Your route remains visible. Resume whenever you are ready.'
                : routeUiState.lastGuidance?.meta || 'Continue following the highlighted route.';
        }

        if (
            typeof selectedStartMode !== 'undefined'
            && selectedStartMode === 'gps'
            && typeof window.toggleOutdoorLiveGpsFollow === 'function'
        ) {
            window.toggleOutdoorLiveGpsFollow();
        }

        renderRouteState();
    }

    function endNavigation() {
        if (typeof window.stopOutdoorLiveGpsTracking === 'function') {
            window.stopOutdoorLiveGpsTracking({ keepStart: false });
        }

        if (routeUiState.hasRoute && typeof window.resetRouteSelection === 'function') {
            window.resetRouteSelection();
        } else {
            hideSheet();
        }

        routeUiState.hasRoute = false;
        routeUiState.started = false;
        routeUiState.paused = false;
        routeUiState.distanceMeters = null;
        routeUiState.remainingMeters = null;
        routeUiState.lastGuidance = null;
        hideSheet();
    }

    function showToast(message, options = {}) {
        if (!toastRegion) return null;

        const text = String(message || '').trim();
        if (!text) return null;

        const kind = options.kind || inferMessageTone(text);
        const toast = document.createElement('div');
        toast.className = `wayfinding-toast is-${kind}`;
        toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');

        const icon = document.createElement('span');
        icon.className = 'wayfinding-toast-icon';
        icon.setAttribute('aria-hidden', 'true');

        const copy = document.createElement('div');
        copy.className = 'wayfinding-toast-copy';
        copy.textContent = text;

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'wayfinding-toast-close';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.textContent = '×';

        const dismiss = () => {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        };

        close.addEventListener('click', dismiss);
        toast.append(icon, copy, close);
        toastRegion.prepend(toast);

        while (toastRegion.children.length > 3) {
            toastRegion.lastElementChild?.remove();
        }

        window.setTimeout(dismiss, Number(options.duration || (kind === 'error' ? 8500 : 5600)));
        return toast;
    }

    function setConnectionBanner(kind, title, message, options = {}) {
        if (!connectionBanner) return;

        if (readyBannerTimer) {
            window.clearTimeout(readyBannerTimer);
            readyBannerTimer = null;
        }

        connectionBanner.hidden = false;
        connectionBanner.className = `wayfinding-connection-banner is-${kind}`;
        if (connectionTitle) connectionTitle.textContent = title;
        if (connectionMessage) connectionMessage.textContent = message;

        connectionHasRecoverableFailure = options.retry === true;
        if (retryButton) {
            retryButton.hidden = !connectionHasRecoverableFailure;
            retryButton.disabled = kind === 'loading';
            retryButton.textContent = kind === 'loading' ? 'Loading…' : 'Retry';
        }

        if (options.autoHide) {
            readyBannerTimer = window.setTimeout(() => {
                if (connectionBanner) connectionBanner.hidden = true;
            }, Number(options.autoHide));
        }
    }

    const dataStatus = {
        loading(message = 'Loading essential campus map data…') {
            setConnectionBanner('loading', 'Preparing campus map', message, { retry: false });
        },
        coreReady(message = 'The outdoor campus map is ready. Indoor details are loading in the background.') {
            setConnectionBanner('ready', 'Campus map ready', message, { retry: false, autoHide: 2600 });
        },
        ready(message = 'All campus navigation data is available.') {
            setConnectionBanner('ready', 'Navigation data ready', message, { retry: false, autoHide: 2200 });
        },
        partial(failedLabels = [], options = {}) {
            const labels = failedLabels.filter(Boolean);
            const cacheNote = options.usingCache
                ? ' Saved campus data is being used where available.'
                : '';
            const detail = labels.length
                ? `Unavailable: ${labels.join(', ')}.${cacheNote}`
                : `Some live data is unavailable.${cacheNote}`;
            setConnectionBanner(
                options.critical ? 'error' : 'partial',
                options.critical ? 'Navigation data incomplete' : 'Limited campus data',
                detail,
                { retry: true }
            );
        },
        offline() {
            setConnectionBanner(
                'offline',
                'You are offline',
                'Saved campus data will be used when available. Reconnect to refresh changes.',
                { retry: true }
            );
        },
    };

    function syncExpandedControls() {
        const destinationToggle = document.getElementById('destination-menu-toggle');
        const actionCard = document.getElementById('floating-action-card');
        if (destinationToggle && actionCard) {
            const expanded = actionCard.style.display !== 'none' ? 'true' : 'false';
            if (destinationToggle.getAttribute('aria-expanded') !== expanded) {
                destinationToggle.setAttribute('aria-expanded', expanded);
            }
        }

        const profileButton = document.getElementById('user-profile-btn');
        const profileMenu = document.getElementById('user-profile-menu');
        if (profileButton && profileMenu) {
            const expanded = profileMenu.classList.contains('open') ? 'true' : 'false';
            if (profileButton.getAttribute('aria-expanded') !== expanded) {
                profileButton.setAttribute('aria-expanded', expanded);
            }
        }

        document.querySelectorAll('.floating-mode-btn').forEach(button => {
            button.setAttribute('aria-pressed', button.classList.contains('active') ? 'true' : 'false');
        });

        const browseBackdrop = document.getElementById('browseOptionsModal');
        if (browseBackdrop) {
            const isOpen = browseBackdrop.style.display !== 'none';
            browseBackdrop.inert = !isOpen;
            browseBackdrop.removeAttribute('aria-hidden');
        }
    }

    function isDialogVisible(dialog) {
        if (!dialog) return false;
        if (dialog.id === 'indoorPanel') return dialog.classList.contains('active');
        const style = window.getComputedStyle(dialog);
        return style.display !== 'none' && style.visibility !== 'hidden';
    }

    function focusOpenedDialog(dialog) {
        if (!dialog || !isDialogVisible(dialog)) return;

        lastFocusedBeforeDialog = document.activeElement;
        const focusTarget = dialog.querySelector(
            'button:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex="0"]'
        ) || dialog;
        window.setTimeout(() => focusTarget.focus({ preventScroll: true }), 40);
    }

    function installDialogAccessibility() {
        const browseBackdrop = document.getElementById('browseOptionsModal');
        const browseDialog = browseBackdrop?.querySelector('[role="dialog"]');
        const indoorDialog = document.getElementById('indoorPanel');
        const actionCard = document.getElementById('floating-action-card');
        const profileMenu = document.getElementById('user-profile-menu');

        [browseBackdrop, indoorDialog, actionCard, profileMenu].filter(Boolean).forEach(element => {
            const observer = new MutationObserver(() => {
                const browseIsVisible = element === browseBackdrop && isDialogVisible(browseBackdrop);
                if (
                    element === browseBackdrop
                    && browseDialog
                    && !browseIsVisible
                    && browseDialog.contains(document.activeElement)
                ) {
                    if (lastFocusedBeforeDialog?.focus) {
                        lastFocusedBeforeDialog.focus({ preventScroll: true });
                    } else if (document.activeElement instanceof HTMLElement) {
                        document.activeElement.blur();
                    }
                }

                syncExpandedControls();

                if (element === browseBackdrop && browseDialog && browseIsVisible) {
                    focusOpenedDialog(browseDialog);
                }
                if (element === indoorDialog && isDialogVisible(indoorDialog)) {
                    focusOpenedDialog(indoorDialog);
                } else if (
                    element === indoorDialog
                    && indoorDialog.contains(document.activeElement)
                    && lastFocusedBeforeDialog?.focus
                ) {
                    lastFocusedBeforeDialog.focus({ preventScroll: true });
                }
            });
            observer.observe(element, {
                attributes: true,
                attributeFilter: ['style', 'class'],
            });
        });

        document.addEventListener('keydown', event => {
            if (event.key !== 'Tab') return;

            const visibleDialog = [browseDialog, indoorDialog].find(isDialogVisible);
            if (!visibleDialog) return;

            const focusable = Array.from(visibleDialog.querySelectorAll(
                'button:not([disabled]), input:not([disabled]), select:not([disabled]), [href], [tabindex]:not([tabindex="-1"])'
            )).filter(element => element.offsetParent !== null);

            if (!focusable.length) {
                event.preventDefault();
                visibleDialog.focus();
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key !== 'Escape') return;
            window.setTimeout(() => {
                if (lastFocusedBeforeDialog?.focus) {
                    lastFocusedBeforeDialog.focus({ preventScroll: true });
                }
            }, 50);
        });
    }

    const baseSetRouteResultLabel = typeof setRouteResultLabel === 'function'
        ? setRouteResultLabel
        : null;
    if (baseSetRouteResultLabel) {
        setRouteResultLabel = function (message) {
            const result = baseSetRouteResultLabel.apply(this, arguments);
            updateStatus(message);
            return result;
        };
    }

    const baseDrawOutdoorRoute = typeof drawOutdoorRoute === 'function'
        ? drawOutdoorRoute
        : null;
    if (baseDrawOutdoorRoute) {
        drawOutdoorRoute = function (result, options = {}) {
            const rendered = baseDrawOutdoorRoute.apply(this, arguments);
            setRoute(result, options);
            return rendered;
        };
    }

    const baseUpdateRouteLabels = typeof updateRouteLabels === 'function'
        ? updateRouteLabels
        : null;
    if (baseUpdateRouteLabels) {
        updateRouteLabels = function () {
            const result = baseUpdateRouteLabels.apply(this, arguments);
            routeUiState.destination = resolveDestinationLabel();
            if (routeUiState.hasRoute) renderRouteState();
            return result;
        };
    }

    /* Existing calls remain safe, but native blocking dialogs become toasts. */
    if (!window.__wayfindingNativeAlert) {
        window.__wayfindingNativeAlert = window.alert.bind(window);
    }
    window.alert = function (message) {
        showToast(message, { kind: inferMessageTone(message) });
    };

    recenterButton?.addEventListener('click', recenterRoute);
    pauseButton?.addEventListener('click', togglePause);
    endButton?.addEventListener('click', endNavigation);
    collapseButton?.addEventListener('click', collapseRouteDetails);
    detailsToggle?.addEventListener('click', expandRouteDetails);

    retryButton?.addEventListener('click', () => {
        if (typeof window.retryWayfindingData === 'function') {
            retryButton.disabled = true;
            window.retryWayfindingData();
        } else {
            showToast('Retry is not ready yet. Please reload the page.', { kind: 'warning' });
        }
    });
    connectionClose?.addEventListener('click', () => {
        if (connectionBanner) connectionBanner.hidden = true;
    });

    window.addEventListener('offline', dataStatus.offline);
    window.addEventListener('online', () => {
        if (connectionHasRecoverableFailure && typeof window.retryWayfindingData === 'function') {
            setConnectionBanner(
                'ready',
                'Connection restored',
                'Refresh campus data when you are ready.',
                { retry: true }
            );
        } else {
            dataStatus.ready('Connection restored. Campus navigation is online.');
        }
    });

    installDialogAccessibility();
    syncExpandedControls();
    document.body.classList.add('wayfinding-navigation-enhanced');

    window.WayfindingNavigationUi = {
        setRoute,
        updateStatus,
        updateGuidance,
        updateGpsStatus,
        clearGpsStatus,
        showToast,
        isNavigationStarted() {
            return routeUiState.started;
        },
        isNavigationPaused() {
            return routeUiState.paused;
        },
        endNavigation,
        getState() {
            return { ...routeUiState };
        },
    };
    window.WayfindingDataStatus = dataStatus;
    window.showWayfindingToast = showToast;
})();
