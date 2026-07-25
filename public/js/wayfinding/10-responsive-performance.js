/* =========================================================
   MOBILE INDOOR ZOOM IN PATCH
   Mobile view only:
   - smaller padding = more zoom in
   - no manual zoom-out
   - adds a little zoom-in after fitBounds
========================================================= */

function isIndoorMobileViewportFinal() {
    return window.matchMedia('(max-width: 768px)').matches;
}

function getCurrentIndoorMapItemFinal() {
    if (!currentIndoorBuildingId || !hasIndoorFloorValue(currentIndoorFloor)) return null;

    return (allIndoorMaps || []).find(m =>
        Number(m.building_id) === Number(currentIndoorBuildingId) &&
        Number(m.floor_number) === Number(currentIndoorFloor)
    ) || null;
}

function fitIndoorMapMobileZoomInFinal(delay = 120) {
    setTimeout(() => {
        if (!indoorMap || !currentIndoorBuildingId || !hasIndoorFloorValue(currentIndoorFloor)) return;

        const isMobile = isIndoorMobileViewportFinal();

        if (typeof indoorMap.setMinZoom === 'function') {
            indoorMap.setMinZoom(isMobile ? 15 : 17);
        }

        indoorMap.invalidateSize();

        const mapItem = getCurrentIndoorMapItemFinal();
        const bounds = mapItem ? getIndoorMapBoundsFromGeometry(mapItem.geometry) : null;

        if (bounds && bounds.isValid()) {
            /*
            |--------------------------------------------------------------------------
            | Adjust mobilePad diri:
            | 0.06 = mas zoom in
            | 0.10 = sakto/gamay zoom in
            | 0.18 = medyo zoom out
            |--------------------------------------------------------------------------
            */
            const mobilePad = 0.03;
            const desktopPad = 0.12;

            indoorMap.fitBounds(bounds.pad(isMobile ? mobilePad : desktopPad), {
                animate: false,
                padding: isMobile ? [8, 8] : [28, 28],
                maxZoom: isMobile ? 22 : 22
            });

            /*
            |--------------------------------------------------------------------------
            | Extra mobile zoom-in gamay after fitBounds.
            | +0.25 = gamay ra
            | +0.45 = recommended
            | +0.70 = mas duol / gamay zoom in pa
            |--------------------------------------------------------------------------
            */
            if (isMobile) {
                const currentZoom = indoorMap.getZoom();
                indoorMap.setZoom(Math.min(indoorMap.getMaxZoom(), currentZoom + 0.40), {
                    animate: false
                });
            }
        }
    }, delay);
}

if (typeof openIndoorPanelForBuilding === 'function' && !window.__mobileIndoorZoomInOpenWrapped) {
    window.__mobileIndoorZoomInOpenWrapped = true;
    const __baseOpenIndoorPanelZoomIn = openIndoorPanelForBuilding;

    openIndoorPanelForBuilding = function () {
        const result = __baseOpenIndoorPanelZoomIn.apply(this, arguments);

        fitIndoorMapMobileZoomInFinal(180);
        fitIndoorMapMobileZoomInFinal(480);
        fitIndoorMapMobileZoomInFinal(850);

        return result;
    };

    window.openIndoorPanelForBuilding = openIndoorPanelForBuilding;
}

if (typeof renderIndoorFloor === 'function' && !window.__mobileIndoorZoomInRenderWrapped) {
    window.__mobileIndoorZoomInRenderWrapped = true;
    const __baseRenderIndoorFloorZoomIn = renderIndoorFloor;

    renderIndoorFloor = function () {
        const result = __baseRenderIndoorFloorZoomIn.apply(this, arguments);

        fitIndoorMapMobileZoomInFinal(180);
        fitIndoorMapMobileZoomInFinal(420);

        return result;
    };

    window.renderIndoorFloor = renderIndoorFloor;
}

window.addEventListener('orientationchange', function () {
    if (isIndoorMobileViewportFinal()) {
        fitIndoorMapMobileZoomInFinal(360);
    }
});

window.addEventListener('resize', function () {
    if (isIndoorMobileViewportFinal()) {
        fitIndoorMapMobileZoomInFinal(220);
    }
});

window.fitIndoorMapMobileZoomInFinal = fitIndoorMapMobileZoomInFinal;

/* =========================================================
   MOBILE OUTDOOR ROUTE ZOOM PATCH - MANUAL ZOOM FRIENDLY
   Outdoor map only. Indoor map is not affected.

   Mobile behavior:
   - Normal/default outdoor view: zoom 18
   - After route/navigation: auto zoom out to 17 ONCE
   - User can still manually zoom in again up to maxZoom 19
========================================================= */
function isMobileOutdoorViewFinal() {
    return window.matchMedia('(max-width: 768px)').matches;
}

let mobileOutdoorRouteZoomMode = false;

function unlockMobileOutdoorManualZoomFinal() {
    if (!isMobileOutdoorViewFinal() || !map) return;

    if (typeof map.setMinZoom === 'function') {
        map.setMinZoom(MOBILE_OUTDOOR_MIN_ZOOM_VALUE);
    }

    if (typeof map.setMaxZoom === 'function') {
        map.setMaxZoom(MOBILE_OUTDOOR_MAX_ZOOM_VALUE);
    }
}

function applyMobileOutdoorDefaultZoomFinal(delay = 160) {
    if (!isMobileOutdoorViewFinal() || !map) return;

    setTimeout(() => {
        if (!map) return;

        map.invalidateSize();
        unlockMobileOutdoorManualZoomFinal();

        map.setZoom(MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE, {
            animate: false
        });
    }, delay);
}

function applyMobileOutdoorRouteZoomFinal(delay = 160) {
    if (!isMobileOutdoorViewFinal() || !map) return;

    mobileOutdoorRouteZoomMode = true;

    setTimeout(() => {
        if (!map) return;

        map.invalidateSize();
        unlockMobileOutdoorManualZoomFinal();

        /* Auto zoom-out after route only. DILI ni mo lock sa manual zoom. */
        map.setZoom(MOBILE_OUTDOOR_ROUTE_ZOOM_VALUE, {
            animate: false
        });
    }, delay);
}

/*
|--------------------------------------------------------------------------
| fitBounds wrapper
|--------------------------------------------------------------------------
| Automatic route fitting ra ang limitahan.
| Manual pinch zoom / plus zoom button pwede gihapon hangtod maxZoom 19.
*/
if (!window.__mobileOutdoorFitBoundsZoomPatchWrapped) {
    window.__mobileOutdoorFitBoundsZoomPatchWrapped = true;

    const __baseOutdoorFitBounds = map.fitBounds.bind(map);

    map.fitBounds = function(bounds, options = {}) {
        const finalOptions = {
            ...(options || {})
        };

        if (isMobileOutdoorViewFinal()) {
            finalOptions.maxZoom = mobileOutdoorRouteZoomMode
                ? MOBILE_OUTDOOR_ROUTE_ZOOM_VALUE
                : MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE;

            finalOptions.padding = finalOptions.padding || [80, 80];
            finalOptions.animate = false;
        }

        const result = __baseOutdoorFitBounds(bounds, finalOptions);

        if (isMobileOutdoorViewFinal()) {
            if (mobileOutdoorRouteZoomMode) {
                applyMobileOutdoorRouteZoomFinal(260);
            } else {
                applyMobileOutdoorDefaultZoomFinal(260);
            }
        }

        return result;
    };
}


if (typeof drawOutdoorRoute === 'function' && !window.__mobileOutdoorDrawRouteZoomPatchWrapped) {
    window.__mobileOutdoorDrawRouteZoomPatchWrapped = true;

    const __baseDrawOutdoorRouteZoomPatch = drawOutdoorRoute;

    drawOutdoorRoute = function(result, options = {}) {
        mobileOutdoorRouteZoomMode = true;
        const rendered = __baseDrawOutdoorRouteZoomPatch.call(this, result, options);

        if (!options.liveUpdate) {
            applyMobileOutdoorRouteZoomFinal(260);
        }

        return rendered;
    };

    window.drawOutdoorRoute = drawOutdoorRoute;
}

if (typeof findRouteByDestination === 'function' && !window.__mobileOutdoorFindRouteZoomPatchWrapped) {
    window.__mobileOutdoorFindRouteZoomPatchWrapped = true;

    const __baseFindRouteByDestinationZoomPatch = findRouteByDestination;

    findRouteByDestination = function() {
        mobileOutdoorRouteZoomMode = true;
        const result = __baseFindRouteByDestinationZoomPatch.apply(this, arguments);
        applyMobileOutdoorRouteZoomFinal(320);
        return result;
    };

    window.findRouteByDestination = findRouteByDestination;
}

if (typeof computeCompleteRouteToRoom === 'function' && !window.__mobileOutdoorRoomRouteZoomPatchWrapped) {
    window.__mobileOutdoorRoomRouteZoomPatchWrapped = true;

    const __baseComputeCompleteRouteToRoomZoomPatch = computeCompleteRouteToRoom;

    computeCompleteRouteToRoom = function() {
        mobileOutdoorRouteZoomMode = true;
        const result = __baseComputeCompleteRouteToRoomZoomPatch.apply(this, arguments);
        applyMobileOutdoorRouteZoomFinal(360);
        return result;
    };

    window.computeCompleteRouteToRoom = computeCompleteRouteToRoom;
}

if (typeof searchTextDestination === 'function' && !window.__mobileOutdoorTextSearchZoomPatchWrapped) {
    window.__mobileOutdoorTextSearchZoomPatchWrapped = true;

    const __baseSearchTextDestinationZoomPatch = searchTextDestination;

    searchTextDestination = function() {
        mobileOutdoorRouteZoomMode = true;
        const result = __baseSearchTextDestinationZoomPatch.apply(this, arguments);
        applyMobileOutdoorRouteZoomFinal(360);
        return result;
    };

    window.searchTextDestination = searchTextDestination;
}

if (typeof resetRouteSelection === 'function' && !window.__mobileOutdoorResetZoomPatchWrapped) {
    window.__mobileOutdoorResetZoomPatchWrapped = true;

    const __baseResetRouteSelectionZoomPatch = resetRouteSelection;

    resetRouteSelection = function() {
        mobileOutdoorRouteZoomMode = false;
        const result = __baseResetRouteSelectionZoomPatch.apply(this, arguments);
        applyMobileOutdoorDefaultZoomFinal(260);
        return result;
    };

    window.resetRouteSelection = resetRouteSelection;
}

window.addEventListener('resize', function() {
    unlockMobileOutdoorManualZoomFinal();
});

window.addEventListener('orientationchange', function() {
    unlockMobileOutdoorManualZoomFinal();
});

/* First mobile load = default zoom 18 */
applyMobileOutdoorDefaultZoomFinal(500);

window.applyMobileOutdoorDefaultZoomFinal = applyMobileOutdoorDefaultZoomFinal;
window.applyMobileOutdoorRouteZoomFinal = applyMobileOutdoorRouteZoomFinal;
window.unlockMobileOutdoorManualZoomFinal = unlockMobileOutdoorManualZoomFinal;

window.toggleCampusEventPanel = toggleCampusEventPanel;
window.closeCampusEventPanel = closeCampusEventPanel;
window.routeToCampusEvent = routeToCampusEvent;



    /* =========================================================
       USER PROFILE DROPDOWN
       - Opens when profile icon is clicked
       - Closes when clicking outside or pressing ESC
    ========================================================= */
    function toggleUserProfileMenu(event) {
        if (event) {
            event.stopPropagation();
        }

        const menu = document.getElementById('user-profile-menu');
        const btn = document.getElementById('user-profile-btn');

        if (!menu || !btn) return;

        const isOpen = menu.classList.contains('open');

        menu.classList.toggle('open', !isOpen);
        btn.classList.toggle('active', !isOpen);
    }

    function closeUserProfileMenu() {
        const menu = document.getElementById('user-profile-menu');
        const btn = document.getElementById('user-profile-btn');

        if (menu) {
            menu.classList.remove('open');
        }

        if (btn) {
            btn.classList.remove('active');
        }
    }

    document.addEventListener('click', function(event) {
        const wrap = document.getElementById('user-profile-wrap');

        if (!wrap) return;
        if (wrap.contains(event.target)) return;

        closeUserProfileMenu();
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeUserProfileMenu();
        }
    });

/* =========================================================
   SMOOTHNESS / PERFORMANCE PATCH
   UI + map interaction optimization only.
   No routing algorithm or building color changes.
========================================================= */
(function smoothCampusNavigationPatch() {
    if (window.__smoothCampusNavigationPatchApplied) return;
    window.__smoothCampusNavigationPatchApplied = true;

    const body = document.body;
    let movingTimer = null;

    function markMapMoving() {
        if (!body) return;
        body.classList.add('map-moving');
        if (movingTimer) clearTimeout(movingTimer);
    }

    function unmarkMapMovingSoon() {
        if (!body) return;
        if (movingTimer) clearTimeout(movingTimer);
        movingTimer = setTimeout(() => {
            body.classList.remove('map-moving');
        }, 140);
    }

    if (typeof map !== 'undefined' && map) {
        map.on('movestart zoomstart dragstart', markMapMoving);
        map.on('moveend zoomend dragend', unmarkMapMovingSoon);
    }

    /*
    | Debounce repeated indoor layer click attachment.
    | This prevents repeated scans when many Leaflet layers are added.
    */
    if (typeof attachIndoorClickToBuildingLayersFinal === 'function' && !window.__smoothIndoorAttachDebounced) {
        window.__smoothIndoorAttachDebounced = true;

        const baseAttachIndoorClickToBuildingLayersFinal = attachIndoorClickToBuildingLayersFinal;
        let attachPending = false;

        attachIndoorClickToBuildingLayersFinal = function smoothAttachIndoorClickDebounced() {
            if (attachPending) return;

            attachPending = true;

            requestAnimationFrame(() => {
                attachPending = false;
                baseAttachIndoorClickToBuildingLayersFinal();
            });
        };

        window.attachIndoorClickToBuildingLayersFinal = attachIndoorClickToBuildingLayersFinal;
    }

    /*
    | Throttle popup scale updates so map movement doesn't trigger too many layout updates.
    */
    if (typeof updateRouteBuildingPopupScale === 'function' && !window.__smoothPopupScaleThrottled) {
        window.__smoothPopupScaleThrottled = true;

        const baseUpdateRouteBuildingPopupScale = updateRouteBuildingPopupScale;
        let scalePending = false;

        updateRouteBuildingPopupScale = function smoothPopupScaleThrottled() {
            if (scalePending) return;

            scalePending = true;

            requestAnimationFrame(() => {
                scalePending = false;
                baseUpdateRouteBuildingPopupScale();
            });
        };

        window.updateRouteBuildingPopupScale = updateRouteBuildingPopupScale;
    }

    /*
    | Pause visual effects when tab is hidden.
    */
    document.addEventListener('visibilitychange', () => {
        if (!body) return;
        body.classList.toggle('page-hidden', document.hidden);
    });
})();

/* =========================================================
   CLEAN MOBILE BUILDING SHADOW RUNTIME PATCH
   Keeps map-moving class for transition control.
   Also hides any old duplicate shadow panes if cached.
========================================================= */
(function cleanMobileBuildingShadowFix() {
    if (window.__cleanMobileBuildingShadowFixApplied) return;
    window.__cleanMobileBuildingShadowFixApplied = true;

    document.documentElement.style.setProperty('--step', '1px');

    let movingTimer = null;

    function markMoving() {
        document.body.classList.add('map-moving');

        if (movingTimer) {
            clearTimeout(movingTimer);
        }
    }

    function markStoppedSoon() {
        if (movingTimer) {
            clearTimeout(movingTimer);
        }

        movingTimer = setTimeout(() => {
            document.body.classList.remove('map-moving');
        }, 180);
    }

    if (typeof map !== 'undefined' && map) {
        map.on('movestart zoomstart dragstart', markMoving);
        map.on('move zoom', markMoving);
        map.on('moveend zoomend dragend', markStoppedSoon);

        const oldShadowPane = map.getPane('buildingShadowPane');
        if (oldShadowPane) {
            oldShadowPane.style.display = 'none';
            oldShadowPane.style.opacity = '0';
            oldShadowPane.style.pointerEvents = 'none';
        }
    }

    const style = document.createElement('style');
    style.setAttribute('data-clean-mobile-building-shadow-fix', 'true');
    style.textContent = `
        .leaflet-buildingShadowPane-pane,
        .fake-3d-building-shadow,
        .mobile-fake-3d-shadow {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        @media (hover: none), (max-width: 768px) {
            .fake-3d-building,
            .fake-3d-building:hover,
            body.map-moving .fake-3d-building,
            body.map-moving .fake-3d-building:hover,
            .leaflet-buildingsPane-pane .leaflet-interactive,
            .leaflet-buildingsPane-pane .leaflet-interactive:hover {
                filter:
                    drop-shadow(3px 4px 1px rgba(15, 23, 42, 0.42))
                    drop-shadow(6px 7px 4px rgba(15, 23, 42, 0.18)) !important;
                transform: none !important;
            }
        }

        @media (max-width: 420px) {
            .fake-3d-building,
            .fake-3d-building:hover,
            body.map-moving .fake-3d-building,
            body.map-moving .fake-3d-building:hover,
            .leaflet-buildingsPane-pane .leaflet-interactive,
            .leaflet-buildingsPane-pane .leaflet-interactive:hover {
                filter:
                    drop-shadow(2px 3px 1px rgba(15, 23, 42, 0.44))
                    drop-shadow(4px 5px 3px rgba(15, 23, 42, 0.18)) !important;
            }
        }
    `;
    document.head.appendChild(style);
})();

/* =========================================================
   3-LAYER LIGHTWEIGHT FAKE 3D PERFORMANCE RUNTIME PATCH
   Purpose:
   - Keeps fake 3D visible but cheaper than heavy multi-shadow version.
   - Updates depth only after zoom settles.
   - No routing logic changes.
========================================================= */
(function threeLayerLightweightFake3DPerformancePatch() {
    if (window.__threeLayerLightweightFake3DPerformancePatchApplied) return;
    window.__threeLayerLightweightFake3DPerformancePatchApplied = true;

    const body = document.body;
    let movingTimer = null;
    let zoomTimer = null;
    let shadowFrame = null;

    function scheduleUpdateShadows() {
        if (shadowFrame) {
            cancelAnimationFrame(shadowFrame);
        }

        shadowFrame = requestAnimationFrame(() => {
            shadowFrame = null;

            if (typeof updateShadows === 'function') {
                updateShadows();
            }

            if (typeof updateBuildingPerformanceMode === 'function') {
                updateBuildingPerformanceMode();
            }
        });
    }

    function markMoving() {
        if (!body) return;
        body.classList.add('map-moving');
        if (movingTimer) clearTimeout(movingTimer);
    }

    function markStoppedSoon() {
        if (!body) return;
        if (movingTimer) clearTimeout(movingTimer);

        movingTimer = setTimeout(() => {
            body.classList.remove('map-moving');
        }, 150);
    }

    function markZooming() {
        if (!body) return;
        body.classList.add('map-zooming', 'map-moving');
        if (zoomTimer) clearTimeout(zoomTimer);
    }

    function markZoomStoppedSoon() {
        if (!body) return;
        if (zoomTimer) clearTimeout(zoomTimer);

        zoomTimer = setTimeout(() => {
            scheduleUpdateShadows();
            body.classList.remove('map-zooming');
            markStoppedSoon();
        }, 170);
    }

    if (typeof map !== 'undefined' && map) {
        map.on('zoomstart', markZooming);
        map.on('zoomend', markZoomStoppedSoon);
        map.on('movestart dragstart', markMoving);
        map.on('moveend dragend', markStoppedSoon);
        map.on('resize', scheduleUpdateShadows);
    }

    window.addEventListener('resize', scheduleUpdateShadows);
    window.addEventListener('orientationchange', scheduleUpdateShadows);

    if (typeof updateRouteBuildingPopupScale === 'function' && !window.__routePopupScaleThreeLayerFake3DPerfWrapped) {
        window.__routePopupScaleThreeLayerFake3DPerfWrapped = true;

        const baseUpdateRouteBuildingPopupScale = updateRouteBuildingPopupScale;
        let popupScaleFrame = null;

        updateRouteBuildingPopupScale = function updateRouteBuildingPopupScaleThrottled() {
            if (popupScaleFrame) return;

            popupScaleFrame = requestAnimationFrame(() => {
                popupScaleFrame = null;
                baseUpdateRouteBuildingPopupScale();
            });
        };

        window.updateRouteBuildingPopupScale = updateRouteBuildingPopupScale;
    }

    scheduleUpdateShadows();
})();

/* =========================================================
   NOTIFICATION BELL RESTORE RUNTIME PATCH
   Safe patch-only version:
   - Overrides renderCampusEventMarkers() so the bell is not hidden.
   - Ensures an empty-state panel exists.
   - Keeps the bell visible even when event count is 0.
========================================================= */
(function restoreCampusEventNotificationBell() {
    if (window.__restoreCampusEventNotificationBellApplied) return;
    window.__restoreCampusEventNotificationBellApplied = true;

    const originalEnsureCampusEventPanel =
        typeof ensureCampusEventPanel === 'function' ? ensureCampusEventPanel : null;

    const originalRenderCampusEventMarkers =
        typeof renderCampusEventMarkers === 'function' ? renderCampusEventMarkers : null;

    function makeBasicPanelIfMissing() {
        let wrap = document.getElementById('campus-event-notification-wrap');

        if (wrap) return wrap;

        wrap = document.createElement('div');
        wrap.id = 'campus-event-notification-wrap';
        wrap.className = 'campus-event-notification-wrap force-visible';
        wrap.innerHTML = `
            <button type="button"
                    class="campus-event-bell-btn"
                    id="campus-event-bell-btn"
                    onclick="toggleCampusEventPanel()"
                    aria-label="Open campus events">
                <span class="campus-event-bell-pulse" id="campus-event-bell-pulse" style="display:none;"></span>
                <span class="campus-event-bell-icon">🔔</span>
                <span class="campus-event-bell-count is-zero" id="campus-event-bell-count">0</span>
            </button>

            <div class="campus-event-panel" id="campus-event-panel">
                <div class="campus-event-panel-card">
                    <div class="campus-event-panel-head">
                        <div>
                            <div class="campus-event-panel-kicker">
                                <span class="campus-event-mini-dot upcoming-dot"></span>
                                Campus Events
                            </div>
                            <div class="campus-event-panel-title">Current & Upcoming</div>
                            <div class="campus-event-panel-subtitle">
                                Tap an event to route. Panel stays hidden until you open it.
                            </div>
                        </div>

                        <button type="button"
                                class="campus-event-panel-close"
                                onclick="closeCampusEventPanel()"
                                aria-label="Close campus events">
                            ×
                        </button>
                    </div>

                    <div class="campus-event-list" id="campus-event-list"></div>

                    <div class="campus-event-empty" id="campus-event-empty" style="display:none;">
                        <span class="campus-event-empty-icon">🔕</span>
                        No current or upcoming campus events.
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(wrap);
        return wrap;
    }

    window.ensureCampusEventPanel = function ensureCampusEventPanelRestored() {
        let wrap = null;

        if (originalEnsureCampusEventPanel) {
            wrap = originalEnsureCampusEventPanel();
        }

        if (!wrap) {
            wrap = makeBasicPanelIfMissing();
        }

        wrap.style.display = 'block';
        wrap.classList.add('force-visible');

        const panelCard = wrap.querySelector('.campus-event-panel-card');
        const empty = document.getElementById('campus-event-empty');

        if (panelCard && !empty) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'campus-event-empty';
            emptyDiv.id = 'campus-event-empty';
            emptyDiv.style.display = 'none';
            emptyDiv.innerHTML = '<span class="campus-event-empty-icon">🔕</span>No current or upcoming campus events.';
            panelCard.appendChild(emptyDiv);
        }

        return wrap;
    };

    window.renderCampusEventMarkers = function renderCampusEventMarkersRestored() {
        if (typeof campusEventLayer !== 'undefined' && campusEventLayer) {
            campusEventLayer.clearLayers();
        }

        const activeEvents = (typeof campusEvents !== 'undefined' ? (campusEvents || []) : []).filter(event => {
            return event && event.id && event.route_type && event.route_id;
        });

        const wrap = window.ensureCampusEventPanel();
        const list = document.getElementById('campus-event-list');
        const empty = document.getElementById('campus-event-empty');
        const count = document.getElementById('campus-event-bell-count');
        const pulse = document.getElementById('campus-event-bell-pulse');
        const bell = document.getElementById('campus-event-bell-btn');

        wrap.style.display = 'block';
        wrap.classList.add('force-visible');

        if (count) {
            count.textContent = activeEvents.length > 9 ? '9+' : String(activeEvents.length);
            count.classList.toggle('is-zero', activeEvents.length === 0);
        }

        if (pulse) {
            const hasNow = activeEvents.some(event => event.status === 'happening_now');
            pulse.style.display = hasNow ? 'block' : 'none';
        }

        if (bell) {
            bell.title = activeEvents.length
                ? `${activeEvents.length} current/upcoming campus event${activeEvents.length > 1 ? 's' : ''}`
                : 'No current or upcoming campus events';
        }

        if (list) {
            list.style.display = activeEvents.length ? 'block' : 'none';

            if (activeEvents.length && typeof createCampusEventCardHtml === 'function') {
                list.innerHTML = activeEvents.map(event => createCampusEventCardHtml(event)).join('');
            } else {
                list.innerHTML = '';
            }
        }

        if (empty) {
            empty.style.display = activeEvents.length ? 'none' : 'block';
        }
    };

    function syncBell() {
        if (typeof window.renderCampusEventMarkers === 'function') {
            window.renderCampusEventMarkers();
        } else if (typeof window.ensureCampusEventPanel === 'function') {
            window.ensureCampusEventPanel();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncBell);
    } else {
        syncBell();
    }

    setTimeout(syncBell, 300);
    setTimeout(syncBell, 1000);
})();
