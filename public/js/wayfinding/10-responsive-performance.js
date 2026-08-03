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

function applyMobileOutdoorDefaultZoomFinal() {
    if (!isMobileOutdoorViewFinal() || !map) return;

    map.invalidateSize();
    unlockMobileOutdoorManualZoomFinal();

    if (campusBounds && typeof campusBounds.isValid === 'function' && campusBounds.isValid()) {
        map.fitBounds(campusBounds, {
            padding: [50, 50],
            maxZoom: MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE,
            animate: false
        });
    } else {
        map.setZoom(MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE, {
            animate: false
        });
    }
}

function applyMobileOutdoorRouteZoomFinal() {
    if (!isMobileOutdoorViewFinal() || !map) return;

    mobileOutdoorRouteZoomMode = true;
    map.invalidateSize();
    unlockMobileOutdoorManualZoomFinal();

    /* Immediate overview fallback. Normal route drawing frames through fitBounds. */
    map.setZoom(MOBILE_OUTDOOR_ROUTE_ZOOM_VALUE, {
        animate: false
    });
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

        return __baseOutdoorFitBounds(bounds, finalOptions);
    };
}


if (typeof drawOutdoorRoute === 'function' && !window.__mobileOutdoorDrawRouteZoomPatchWrapped) {
    window.__mobileOutdoorDrawRouteZoomPatchWrapped = true;

    const __baseDrawOutdoorRouteZoomPatch = drawOutdoorRoute;

    drawOutdoorRoute = function(result, options = {}) {
        mobileOutdoorRouteZoomMode = true;
        const rendered = __baseDrawOutdoorRouteZoomPatch.call(this, result, options);

        return rendered;
    };

    window.drawOutdoorRoute = drawOutdoorRoute;
}

if (typeof findRouteByDestination === 'function' && !window.__mobileOutdoorFindRouteZoomPatchWrapped) {
    window.__mobileOutdoorFindRouteZoomPatchWrapped = true;

    const __baseFindRouteByDestinationZoomPatch = findRouteByDestination;

    findRouteByDestination = function() {
        mobileOutdoorRouteZoomMode = true;
        return __baseFindRouteByDestinationZoomPatch.apply(this, arguments);
    };

    window.findRouteByDestination = findRouteByDestination;
}

if (typeof computeCompleteRouteToRoom === 'function' && !window.__mobileOutdoorRoomRouteZoomPatchWrapped) {
    window.__mobileOutdoorRoomRouteZoomPatchWrapped = true;

    const __baseComputeCompleteRouteToRoomZoomPatch = computeCompleteRouteToRoom;

    computeCompleteRouteToRoom = function() {
        mobileOutdoorRouteZoomMode = true;
        return __baseComputeCompleteRouteToRoomZoomPatch.apply(this, arguments);
    };

    window.computeCompleteRouteToRoom = computeCompleteRouteToRoom;
}

if (typeof searchTextDestination === 'function' && !window.__mobileOutdoorTextSearchZoomPatchWrapped) {
    window.__mobileOutdoorTextSearchZoomPatchWrapped = true;

    const __baseSearchTextDestinationZoomPatch = searchTextDestination;

    searchTextDestination = function() {
        mobileOutdoorRouteZoomMode = true;
        return __baseSearchTextDestinationZoomPatch.apply(this, arguments);
    };

    window.searchTextDestination = searchTextDestination;
}

if (typeof resetRouteSelection === 'function' && !window.__mobileOutdoorResetZoomPatchWrapped) {
    window.__mobileOutdoorResetZoomPatchWrapped = true;

    const __baseResetRouteSelectionZoomPatch = resetRouteSelection;

    resetRouteSelection = function() {
        mobileOutdoorRouteZoomMode = false;
        const result = __baseResetRouteSelectionZoomPatch.apply(this, arguments);
        applyMobileOutdoorDefaultZoomFinal();
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

/*
| Initial framing is owned by renderBuildings()->fitBounds(). Avoid a delayed
| forced zoom that can fight the user's first pinch gesture.
*/

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
   ADAPTIVE LOW-END PHONE RENDERING
   - Device capability chooses full, balanced, or low mode.
   - Runtime long-task pressure may safely downgrade mobile to low.
   - Building geometry, color, labels, markers, and routing never change.
========================================================= */
(function adaptiveLowEndPhoneRendering() {
    if (window.__adaptiveLowEndPhoneRenderingApplied) return;
    window.__adaptiveLowEndPhoneRenderingApplied = true;

    const body = document.body;
    if (!body || typeof window.applyWayfindingRenderProfile !== 'function') return;

    let pressureObserver = null;
    let pressureDuration = 0;

    function applyCurrentDeviceProfile() {
        if (typeof window.detectWayfindingRenderProfile !== 'function') return;

        const nextProfile = window.detectWayfindingRenderProfile();
        const currentMode = window.wayfindingRenderProfile?.mode;

        /*
        | Never automatically upgrade a runtime-downgraded phone during resize.
        | Orientation changes should not bring expensive layers back mid-session.
        */
        if (currentMode === 'low' && nextProfile.mode !== 'low') return;

        window.applyWayfindingRenderProfile(nextProfile, 'device');
    }

    function startRuntimePressureMonitor() {
        if (
            window.wayfindingRenderProfile?.mode !== 'balanced'
            || typeof PerformanceObserver !== 'function'
        ) {
            return;
        }

        try {
            pressureObserver = new PerformanceObserver(list => {
                list.getEntries().forEach(entry => {
                    if (entry.duration >= 80) {
                        pressureDuration += entry.duration;
                    }
                });

                if (pressureDuration < 480) return;

                window.applyWayfindingRenderProfile({
                    ...(window.wayfindingRenderProfile || {}),
                    mode: 'low',
                    mobile: true
                }, 'runtime-pressure');

                pressureObserver.disconnect();
                pressureObserver = null;
            });

            pressureObserver.observe({
                entryTypes: ['longtask']
            });

            setTimeout(() => {
                if (!pressureObserver) return;
                pressureObserver.disconnect();
                pressureObserver = null;
            }, 10000);
        } catch (error) {
            pressureObserver = null;
        }
    }

    window.addEventListener('resize', applyCurrentDeviceProfile, {
        passive: true
    });
    window.addEventListener('orientationchange', applyCurrentDeviceProfile, {
        passive: true
    });

    if (document.readyState === 'complete') {
        setTimeout(startRuntimePressureMonitor, 1200);
    } else {
        window.addEventListener('load', () => {
            setTimeout(startRuntimePressureMonitor, 1200);
        }, {
            once: true
        });
    }

    window.getWayfindingRenderQuality = function getWayfindingRenderQuality() {
        return window.wayfindingRenderProfile?.mode || 'full';
    };
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
