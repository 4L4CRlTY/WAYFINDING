    /* =========================================================
       ROUTE BUILDING POPUP PATCH
       - Shows popup after route to building / route to room building.
       - Clicking popup button opens indoor rooms.
       - Clicking the building also opens indoor rooms.
    ========================================================= */

    let routePopupBuildingId = null;
    let routePopupBuildingName = '';
    let routePopupLatLng = null;
    let routeBuildingLeafletPopup = null;

    function getBuildingRecordByIdFinal(buildingId) {
        return (buildingRecords || []).find(b => Number(b.id) === Number(buildingId)) || null;
    }

    function getBuildingFeatureCenterFinal(buildingRecord) {
        if (!buildingRecord || !buildingRecord.geometry) return null;

        try {
            const layer = L.geoJSON({
                type: 'Feature',
                geometry: buildingRecord.geometry,
                properties: buildingRecord.properties || {}
            });

            const bounds = layer.getBounds();
            if (!bounds || !bounds.isValid()) return null;

            return bounds.getCenter();
        } catch (e) {
            return null;
        }
    }

    function hasIndoorForBuildingFinal(buildingId) {
        return getIndoorBuildingMaps(buildingId)
            .some(mapItem => Boolean(String(mapItem?.floorplan_image || '').trim()));
    }

    function positionRouteBuildingPopupFinal(latlng) {
        // Old fixed-screen popup positioning disabled.
        // The route popup is now handled by Leaflet and anchored to the building.
        return;
    }


    function escapePopupHtmlFinal(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }



    function makeRoutePopupDragFriendly() {
        const isMobile = window.matchMedia('(max-width: 768px)').matches;
        if (!isMobile) return;

        const popup = document.querySelector('.route-building-map-popup');
        if (!popup) return;

        const passThroughSelectors = [
            '.leaflet-popup-content-wrapper',
            '.leaflet-popup-content',
            '.route-building-map-popup-inner',
            '.route-building-map-popup-kicker',
            '.route-building-map-popup-head',
            '.route-building-map-popup-title-wrap',
            '.route-building-map-popup-title',
            '.route-building-map-popup-subtitle',
            '.route-building-map-popup-divider',
            '.route-building-map-popup-hint',
            '.route-building-map-popup-hint-icon',
            '.leaflet-popup-tip'
        ];

        popup.style.pointerEvents = 'none';
        popup.style.touchAction = 'pan-x pan-y';

        passThroughSelectors.forEach(selector => {
            popup.querySelectorAll(selector).forEach(el => {
                el.style.pointerEvents = 'none';
                el.style.touchAction = 'pan-x pan-y';
                el.style.userSelect = 'none';
                el.style.webkitUserSelect = 'none';
            });
        });

        const btn = popup.querySelector('.route-building-map-popup-btn');
        if (btn) {
            btn.style.pointerEvents = 'auto';
            btn.style.touchAction = 'manipulation';
            btn.querySelectorAll('*').forEach(child => {
                child.style.pointerEvents = 'none';
            });

            ['touchstart', 'pointerdown', 'mousedown', 'click'].forEach(eventName => {
                btn.addEventListener(eventName, function(e) {
                    e.stopPropagation();
                }, { passive: false });
            });
        }

        const closeBtn = popup.querySelector(
            '.route-building-map-popup-custom-close, .leaflet-popup-close-button'
        );
        if (closeBtn) {
            closeBtn.style.pointerEvents = 'auto';
            closeBtn.style.touchAction = 'manipulation';

            ['touchstart', 'pointerdown', 'mousedown', 'click'].forEach(eventName => {
                closeBtn.addEventListener(eventName, function(e) {
                    e.stopPropagation();
                }, { passive: false });
            });
        }
    }

function showRouteBuildingPopup(buildingId, buildingName, center) {
        routePopupBuildingId = Number(buildingId);
        routePopupBuildingName = buildingName || getBuildingNameById(buildingId);
        routePopupLatLng = center;

        if (!center) return;

        const safeName = escapePopupHtmlFinal(routePopupBuildingName);
        const hasIndoorMap = hasIndoorForBuildingFinal(routePopupBuildingId);
        const availabilityClass = hasIndoorMap ? 'is-available' : 'is-unavailable';
        const availabilityLabel = hasIndoorMap
            ? 'Indoor available'
            : 'Indoor not available';
        const isMobileIndoorPopup = window.matchMedia('(max-width: 768px)').matches;
        const isCompactMobilePopup = window.matchMedia('(max-width: 480px)').matches;
        const oldFloatingPopup = document.getElementById('route-building-popup');

        // Dili na gamiton ang bottom sheet. Ang popup dapat pirmi mo-display sa babaw sa building.
        if (oldFloatingPopup) {
            oldFloatingPopup.classList.remove('mobile-active');
            oldFloatingPopup.style.display = 'none';
        }

        const html = `
            <div class="route-building-map-popup-inner ${availabilityClass}">
                <button type="button"
                    class="route-building-map-popup-custom-close"
                    aria-label="Close indoor popup"
                    onclick="closeRouteBuildingPopup()">
                    ×
                </button>
                <div class="route-building-map-popup-kicker">
                    <span class="route-building-map-popup-pulse-dot"></span>
                    ${availabilityLabel}
                </div>

                ${hasIndoorMap ? `
                    <div class="route-building-map-popup-building">
                        <span class="route-building-map-popup-icon" aria-hidden="true">🏢</span>
                        <span class="route-building-map-popup-title">${safeName}</span>
                    </div>
                    <button type="button"
                        class="route-building-map-popup-btn"
                        aria-label="Open indoor rooms for ${safeName}"
                        onclick="openIndoorFromRoutePopup()">
                        <span class="route-building-map-popup-btn-main">
                            <span class="route-building-map-popup-btn-icon" aria-hidden="true">🚪</span>
                            <span class="route-building-map-popup-btn-text">
                                <strong>OPEN INDOOR ROOMS</strong>
                                <small>View rooms and indoor route</small>
                            </span>
                        </span>
                    </button>
                ` : `
                    <div class="route-building-map-popup-building">
                        <span class="route-building-map-popup-icon" aria-hidden="true">🏢</span>
                        <span class="route-building-map-popup-title">${safeName}</span>
                    </div>
                `}
            </div>
        `;

        if (routeBuildingLeafletPopup) {
            map.closePopup(routeBuildingLeafletPopup);
            routeBuildingLeafletPopup = null;
        }

        routeBuildingLeafletPopup = L.popup({
            closeButton: false,
            autoClose: true,
            closeOnClick: false,
            // Let Leaflet make one small pan only when a newly opened popup
            // would be clipped. The popup itself must never be translated
            // relative to its anchor, otherwise it appears to follow the
            // viewport instead of pointing at the building.
            autoPan: true,
            keepInView: false,
            className: 'route-building-map-popup',
            offset: isMobileIndoorPopup ? L.point(0, -14) : L.point(0, -28),

            /*
            | Base size ra ni. Ang visual size niya i-scale nato
            | depende sa current zoom para dili niya matabunan ang route.
            */
            maxWidth: isCompactMobilePopup ? 220 : (isMobileIndoorPopup ? 236 : 250),
            minWidth: isCompactMobilePopup ? 190 : (isMobileIndoorPopup ? 210 : 220),

            autoPanPaddingTopLeft: isMobileIndoorPopup ? L.point(12, 72) : L.point(20, 20),
            autoPanPaddingBottomRight: isMobileIndoorPopup ? L.point(12, 92) : L.point(20, 20)
        })
        .setLatLng(center)
        .setContent(html)
        .openOn(map);

        requestAnimationFrame(() => {
            updateRouteBuildingPopupScale();
            makeRoutePopupDragFriendly();
        });
    }


    /* =========================================================
       ROUTE BUILDING POPUP ZOOM-SCALE FIX
       - Mo gamay ang OPEN INDOOR ROOMS popup kung zoom out.
       - Dili na kaayo motabon sa route.
       - Button ug close button clickable gihapon sa mobile.
    ========================================================= */

    function getRouteBuildingPopupScaleByZoom() {
        if (!map) return 1;

        const zoom = Number(map.getZoom() || 18);
        const isMobile = window.matchMedia('(max-width: 768px)').matches;

        /*
        | Adjust diri kung gusto nimo usbon:
        | Lower return value = mas gamay ang popup.
        | Higher return value = mas dako ang popup.
        */
        if (isMobile) {
            if (zoom <= 17) return 0.58;
            if (zoom <= 17.25) return 0.62;
            if (zoom <= 17.5) return 0.68;
            if (zoom <= 17.75) return 0.74;
            if (zoom <= 18) return 0.82;
            if (zoom <= 18.5) return 0.92;
            return 1;
        }

        if (zoom <= 17) return 0.72;
        if (zoom <= 17.5) return 0.78;
        if (zoom <= 18) return 0.86;
        if (zoom <= 18.5) return 0.94;
        return 1;
    }

    function updateRouteBuildingPopupScale() {
        const popupEl = document.querySelector('.leaflet-popup-pane .route-building-map-popup');
        if (!popupEl) return;

        const scale = getRouteBuildingPopupScaleByZoom();
        popupEl.style.setProperty('--route-popup-scale', scale);

        if (scale <= 0.7) {
            popupEl.classList.add('zoom-out-small');
        } else {
            popupEl.classList.remove('zoom-out-small');
        }
    }

    window.WayfindingInteraction?.registerLifecycle('route-popup-settled-ui', {
        end: () => requestAnimationFrame(() => {
            updateRouteBuildingPopupScale();
        })
    });

    function closeRouteBuildingPopup() {
        const popup = document.getElementById('route-building-popup');
        if (popup) {
            popup.style.display = 'none';
            popup.classList.remove('mobile-active');
        }

        if (routeBuildingLeafletPopup) {
            map.closePopup(routeBuildingLeafletPopup);
            routeBuildingLeafletPopup = null;
        }

        routePopupBuildingId = null;
        routePopupBuildingName = '';
        routePopupLatLng = null;
    }

    function openIndoorFromRoutePopup() {
        if (!routePopupBuildingId) return;

        // No alert. Buildings without indoor maps should do nothing.
        if (!hasIndoorForBuildingFinal(routePopupBuildingId)) {
            return;
        }

        if (typeof openIndoorPanelForBuilding === 'function') {
            openIndoorPanelForBuilding(routePopupBuildingId);
        }
    }

    function showRoutePopupForSelectedBuilding(buildingId) {
        if (typeof setSelectedBuildingVisual === 'function') {
            setSelectedBuildingVisual(buildingId);
        }

        const building = getBuildingRecordByIdFinal(buildingId);
        showRouteBuildingPopup(
            buildingId,
            building?.name || getBuildingNameById(buildingId),
            getBuildingFeatureCenterFinal(building)
        );
    }

    // Route completion invokes this popup directly. Building clicks remain
    // owned by the original interactive SVG top layer in 05-map-rendering.js.

    window.showRouteBuildingPopup = showRouteBuildingPopup;
    window.closeRouteBuildingPopup = closeRouteBuildingPopup;
    window.openIndoorFromRoutePopup = openIndoorFromRoutePopup;


    /* Indoor stacking is owned by openIndoorPanelModal()/closeIndoorPanelFn(). */
    window.showRoutePopupForSelectedBuilding = showRoutePopupForSelectedBuilding;
    window.openIndoorPanelForBuilding = openIndoorPanelForBuilding;
    window.closeIndoorPanelFn = closeIndoorPanelFn;


    /* =========================================================
       INDOOR FLOOR BUTTONS + MAP FOCUS PATCH
       Hides room list/search and replaces floor select with large buttons.
    ========================================================= */

    function getIndoorFloorButtonsContainerFinal() {
        return document.getElementById('indoorFloorButtons');
    }

    function formatIndoorFloorNameFinal(floorNumber) {
        const floor = Number(floorNumber);
        if (floor === 0) return 'Ground Floor';

        const mod100 = Math.abs(floor) % 100;
        const mod10 = Math.abs(floor) % 10;
        let suffix = 'th';
        if (mod100 < 11 || mod100 > 13) {
            if (mod10 === 1) suffix = 'st';
            else if (mod10 === 2) suffix = 'nd';
            else if (mod10 === 3) suffix = 'rd';
        }

        return `${floor}${suffix} Floor`;
    }

    function getIndoorFloorRouteStateFinal() {
        const currentFloor = Number(currentIndoorFloor);
        const routeMatchesCurrentBuilding = typeof window.hasIndoorRouteForBuilding === 'function'
            ? window.hasIndoorRouteForBuilding(currentIndoorBuildingId)
            : Number(lastIndoorRoutePackage?.roomFeature?.properties?.building_id) === Number(currentIndoorBuildingId);
        const destinationFloor = Number(
            lastIndoorRoutePackage?.roomFeature?.properties?.floor_number ?? NaN
        );
        const entranceFloor = Number(
            lastIndoorRoutePackage?.entranceFeature?.properties?.floor_number ?? NaN
        );
        const routeFloors = Object.keys(persistentIndoorRouteByFloor || {})
            .map(Number)
            .filter(Number.isFinite);

        if (
            !routeMatchesCurrentBuilding ||
            !lastIndoorRoutePackage ||
            !routeFloors.length ||
            !Number.isFinite(destinationFloor)
        ) {
            return {
                active: false,
                currentFloor,
                destinationFloor: null,
                entranceFloor: null,
                routeFloors: [],
                orderedFloors: [],
                nextFloor: null,
                step: null,
                totalSteps: 0,
                onRouteFloor: false
            };
        }

        const direction = destinationFloor >= entranceFloor ? 1 : -1;
        const orderedFloors = [...new Set(routeFloors)]
            .sort((a, b) => direction > 0 ? a - b : b - a);
        const currentIndex = orderedFloors.indexOf(currentFloor);
        const onRouteFloor = currentIndex >= 0;
        const nextFloor = currentFloor === destinationFloor
            ? null
            : (onRouteFloor
                ? (orderedFloors[currentIndex + 1] ?? destinationFloor)
                : (orderedFloors[0] ?? destinationFloor));

        return {
            active: true,
            currentFloor,
            destinationFloor,
            entranceFloor,
            routeFloors,
            orderedFloors,
            nextFloor,
            step: onRouteFloor ? currentIndex + 1 : null,
            totalSteps: orderedFloors.length,
            onRouteFloor
        };
    }

    function renderIndoorNavigationGuideFinal() {
        const guide = document.getElementById('indoorFloorGuide');
        const state = getIndoorFloorRouteStateFinal();
        if (!guide || !indoorFooter) return;

        let guideKicker = 'FLOOR ROUTE';
        let guideTitle = 'Select a floor';
        let guideStep = 'READY';
        let footerKicker = 'INDOOR GUIDE';
        let footerTitle = 'Choose a room';
        let footerDetail = 'Tap a room to create a route';
        let guideState = 'idle';

        if (state.active && state.currentFloor === state.destinationFloor) {
            guideKicker = 'DESTINATION FLOOR';
            guideTitle = formatIndoorFloorNameFinal(state.currentFloor);
            guideStep = `${state.totalSteps || 1}/${state.totalSteps || 1}`;
            footerKicker = 'FINAL STEP';
            footerTitle = 'Destination floor reached';
            footerDetail = 'Follow the cyan line to your room';
            guideState = 'destination';
        } else if (state.active && state.onRouteFloor && Number.isFinite(state.nextFloor)) {
            const directionArrow = state.nextFloor > state.currentFloor ? '↑' : '↓';
            guideKicker = 'NEXT FLOOR';
            guideTitle = `${formatIndoorFloorNameFinal(state.nextFloor)} ${directionArrow}`;
            guideStep = `${state.step || 1}/${state.totalSteps}`;
            footerKicker = 'NEXT MOVE';
            footerTitle = `Go to ${formatIndoorFloorNameFinal(state.nextFloor)}`;
            footerDetail = 'Follow cyan line to orange stairs';
            guideState = 'next';
        } else if (state.active && Number.isFinite(state.nextFloor)) {
            guideKicker = 'RETURN TO ROUTE';
            guideTitle = formatIndoorFloorNameFinal(state.nextFloor);
            guideStep = 'ROUTE';
            footerKicker = 'FLOOR GUIDE';
            footerTitle = `Open ${formatIndoorFloorNameFinal(state.nextFloor)}`;
            footerDetail = 'The highlighted floor continues your route';
            guideState = 'next';
        }

        guide.dataset.state = guideState;
        guide.innerHTML = `
            <span class="indoor-floor-guide-signal" aria-hidden="true"></span>
            <span class="indoor-floor-guide-copy">
                <small>${guideKicker}</small>
                <strong>${guideTitle}</strong>
            </span>
            <span class="indoor-floor-guide-step">${guideStep}</span>
        `;

        indoorFooter.dataset.state = guideState;
        indoorFooter.innerHTML = `
            <span class="indoor-guide-symbol" aria-hidden="true">${guideState === 'destination' ? '◆' : (guideState === 'next' ? '↟' : '⌖')}</span>
            <span class="indoor-guide-message">
                <small>${footerKicker}</small>
                <strong>${footerTitle}</strong>
                <span>${footerDetail}</span>
            </span>
        `;
    }

    function renderIndoorFloorButtonsFinal() {
        const container = getIndoorFloorButtonsContainerFinal();
        if (!container || !currentIndoorBuildingId) return;

        const maps = getIndoorBuildingMaps(currentIndoorBuildingId);
        container.innerHTML = '';

        maps.forEach(mapItem => {
            const floor = Number(mapItem.floor_number);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'indoor-floor-btn';
            btn.dataset.floor = String(floor);
            btn.innerHTML = `
                <span class="indoor-floor-btn-name">${formatIndoorFloorNameFinal(floor)}</span>
                <span class="indoor-floor-btn-state">FLOOR</span>
            `;

            if (Number(currentIndoorFloor) === floor) {
                btn.classList.add('active');
            }

            btn.addEventListener('click', function() {
                setIndoorFloorFromButtonFinal(floor);
            });

            container.appendChild(btn);
        });

        updateIndoorFloorButtonActiveFinal();
    }

    function updateIndoorFloorButtonActiveFinal() {
        const state = getIndoorFloorRouteStateFinal();
        let recommendedButton = null;

        document.querySelectorAll('.indoor-floor-btn').forEach(btn => {
            const floor = Number(btn.dataset.floor);
            const isCurrent = floor === Number(currentIndoorFloor);
            const isDestination = state.active && floor === state.destinationFloor;
            const isNext = state.active && Number.isFinite(state.nextFloor) && floor === state.nextFloor;
            const stateLabel = btn.querySelector('.indoor-floor-btn-state');

            btn.classList.toggle('active', isCurrent);
            btn.classList.toggle('is-current', isCurrent);
            btn.classList.toggle('is-next', isNext && !isCurrent);
            btn.classList.toggle('is-destination', isDestination);
            btn.setAttribute('aria-pressed', isCurrent ? 'true' : 'false');

            if (stateLabel) {
                stateLabel.textContent = isCurrent && isDestination
                    ? 'DESTINATION'
                    : (isCurrent ? 'CURRENT' : (isNext ? 'NEXT' : (isDestination ? 'DEST' : 'FLOOR')));
            }

            const floorName = formatIndoorFloorNameFinal(floor);
            btn.setAttribute(
                'aria-label',
                `${floorName}${isCurrent ? ', current floor' : ''}${isNext ? ', next floor on route' : ''}${isDestination ? ', destination floor' : ''}`
            );

            if (isNext && !isCurrent) recommendedButton = btn;
        });

        renderIndoorNavigationGuideFinal();

        if (recommendedButton) {
            const floorRail = recommendedButton.closest('.indoor-floor-buttons');
            const revealRecommendedFloor = () => {
                if (!floorRail) return;
                floorRail.scrollLeft = Math.max(
                    0,
                    recommendedButton.offsetLeft
                        - ((floorRail.clientWidth - recommendedButton.offsetWidth) / 2)
                );
            };

            revealRecommendedFloor();
            requestAnimationFrame(revealRecommendedFloor);
        }
    }

    function setIndoorFloorFromButtonFinal(floor) {
        currentIndoorFloor = Number(floor);

        if (indoorFloorSelect) {
            indoorFloorSelect.value = String(currentIndoorFloor);
        }

        updateIndoorFloorButtonActiveFinal();
        renderIndoorRoomList();
        renderIndoorFloor();

        const hasCurrentBuildingRoute = typeof window.hasIndoorRouteForBuilding === 'function'
            && window.hasIndoorRouteForBuilding(currentIndoorBuildingId);

        if (hasCurrentBuildingRoute) {
            redrawPersistentIndoorRouteForCurrentFloor();
        }

        scheduleIndoorViewportFit({
            reason: 'floor-button',
            preferRoute: hasCurrentBuildingRoute
        });
    }

    /*
       Keep the old hidden select in sync if any old code changes it.
    */
    if (indoorFloorSelect && !indoorFloorSelect.__floorButtonSyncBound) {
        indoorFloorSelect.__floorButtonSyncBound = true;
        indoorFloorSelect.addEventListener('change', function() {
            requestAnimationFrame(updateIndoorFloorButtonActiveFinal);
        });
    }

    window.renderIndoorFloorButtonsFinal = renderIndoorFloorButtonsFinal;
    window.setIndoorFloorFromButtonFinal = setIndoorFloorFromButtonFinal;
    window.renderIndoorNavigationGuideFinal = renderIndoorNavigationGuideFinal;


    /* =========================================================
       SAFE UI CLOSE HELPERS
       This replaces the removed duplicate second script block.
    ========================================================= */
    document.addEventListener('DOMContentLoaded', function() {
        const dockEl = document.getElementById('floating-route-ui');
        const actionCardEl = document.getElementById('floating-action-card');
        const browseModalEl = document.getElementById('browseOptionsModal');

        document.addEventListener('click', function(event) {
            const clickedInsideDock = dockEl && dockEl.contains(event.target);
            const clickedInsideBrowse = browseModalEl && browseModalEl.contains(event.target);

            if (!clickedInsideDock && !clickedInsideBrowse && actionCardEl && actionCardEl.style.display === 'block') {
                if (typeof closeFloatingActionCard === 'function') {
                    closeFloatingActionCard();
                }
            }
        });

        if (browseModalEl && !browseModalEl.__safeCloseBound) {
            browseModalEl.__safeCloseBound = true;
            browseModalEl.addEventListener('click', function(event) {
                if (event.target === browseModalEl && typeof closeBrowseOptionsModal === 'function') {
                    closeBrowseOptionsModal();
                }
            });
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (typeof closeFloatingActionCard === 'function') closeFloatingActionCard();
                if (typeof closeBrowseOptionsModal === 'function') closeBrowseOptionsModal();
                if (typeof closeAiTransformPanel === 'function') closeAiTransformPanel();
            }
        });
    });



/* Keep the responsive class current without forcing Leaflet camera work. */
function updateMobileViewportClassFinal() {
    document.body.classList.toggle(
        'is-mobile-view',
        window.matchMedia('(max-width: 768px)').matches
    );
}

window.addEventListener('resize', updateMobileViewportClassFinal, { passive: true });
window.addEventListener('orientationchange', updateMobileViewportClassFinal, { passive: true });
document.addEventListener('DOMContentLoaded', updateMobileViewportClassFinal, { once: true });
