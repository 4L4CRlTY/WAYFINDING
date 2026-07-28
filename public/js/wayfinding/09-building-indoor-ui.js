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
        return getIndoorBuildingMaps(buildingId).length > 0;
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

    function keepRouteBuildingPopupOnScreen() {
        const popup = document.querySelector('.leaflet-popup-pane .route-building-map-popup');
        if (!popup || !map) return;

        const popupRect = popup.getBoundingClientRect();
        const mapRect = map.getContainer().getBoundingClientRect();
        const isMobile = window.matchMedia('(max-width: 768px)').matches;
        const safeLeft = mapRect.left + 12;
        const safeRight = mapRect.right - 12;
        const safeTop = mapRect.top + (isMobile ? 72 : 18);
        const safeBottom = mapRect.bottom - (isMobile ? 92 : 18);
        let dx = 0;
        let dy = 0;

        if (popupRect.left < safeLeft) {
            dx = popupRect.left - safeLeft;
        } else if (popupRect.right > safeRight) {
            dx = popupRect.right - safeRight;
        }

        if (popupRect.top < safeTop) {
            dy = popupRect.top - safeTop;
        } else if (popupRect.bottom > safeBottom) {
            dy = popupRect.bottom - safeBottom;
        }

        if (dx || dy) {
            map.panBy([dx, dy], { animate: true, duration: 0.22 });
        }
    }

function showRouteBuildingPopup(buildingId, buildingName, center) {
        routePopupBuildingId = Number(buildingId);
        routePopupBuildingName = buildingName || getBuildingNameById(buildingId);
        routePopupLatLng = center;

        if (!center) return;

        const safeName = escapePopupHtmlFinal(routePopupBuildingName);
        const isMobileIndoorPopup = window.matchMedia('(max-width: 768px)').matches;
        const isCompactMobilePopup = window.matchMedia('(max-width: 480px)').matches;
        const oldFloatingPopup = document.getElementById('route-building-popup');

        // Dili na gamiton ang bottom sheet. Ang popup dapat pirmi mo-display sa babaw sa building.
        if (oldFloatingPopup) {
            oldFloatingPopup.classList.remove('mobile-active');
            oldFloatingPopup.style.display = 'none';
        }

        const html = `
            <div class="route-building-map-popup-inner">
                <button type="button"
                    class="route-building-map-popup-custom-close"
                    aria-label="Close indoor popup"
                    onclick="closeRouteBuildingPopup()">
                    ×
                </button>
                <div class="route-building-map-popup-kicker">
                    <span class="route-building-map-popup-pulse-dot"></span>
                    Indoor available
                </div>

                <div class="route-building-map-popup-head">
                    <span class="route-building-map-popup-icon">🏢</span>

                    <span class="route-building-map-popup-title-wrap">
                        <span class="route-building-map-popup-title">${safeName}</span>
                        <span class="route-building-map-popup-subtitle">Tap below to view rooms</span>
                    </span>
                </div>

                <div class="route-building-map-popup-divider"></div>

                <button type="button"
                    class="route-building-map-popup-btn"
                    aria-label="Open indoor rooms for ${safeName}"
                    onclick="openIndoorFromRoutePopup()">

                    <span class="route-building-map-popup-btn-main">
                        <span class="route-building-map-popup-btn-icon">🚪</span>

                        <span class="route-building-map-popup-btn-text">
                            <strong>OPEN INDOOR ROOMS</strong>
                            <small>View rooms and indoor route</small>
                        </span>
                    </span>
                </button>

                <div class="route-building-map-popup-hint">
                    <span class="route-building-map-popup-hint-icon">👆</span>
                    Tap to open the indoor map.
                </div>
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
            autoPan: true,
            keepInView: false,
            className: 'route-building-map-popup',
            offset: isMobileIndoorPopup ? L.point(0, -14) : L.point(0, -28),

            /*
            | Base size ra ni. Ang visual size niya i-scale nato
            | depende sa current zoom para dili niya matabunan ang route.
            */
            maxWidth: isCompactMobilePopup ? 242 : (isMobileIndoorPopup ? 286 : 292),
            minWidth: isCompactMobilePopup ? 242 : (isMobileIndoorPopup ? 286 : 292),

            autoPanPaddingTopLeft: isMobileIndoorPopup ? L.point(12, 72) : L.point(20, 20),
            autoPanPaddingBottomRight: isMobileIndoorPopup ? L.point(12, 92) : L.point(20, 20)
        })
        .setLatLng(center)
        .setContent(html)
        .openOn(map);

        updateRouteBuildingPopupScale();

        makeRoutePopupDragFriendly();
        setTimeout(makeRoutePopupDragFriendly, 80);
        setTimeout(makeRoutePopupDragFriendly, 220);

        setTimeout(makeRoutePopupDragFriendly, 160);
        setTimeout(updateRouteBuildingPopupScale, 40);
        setTimeout(updateRouteBuildingPopupScale, 180);
        setTimeout(keepRouteBuildingPopupOnScreen, 90);
        setTimeout(keepRouteBuildingPopupOnScreen, 360);
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

    /*
       Wrap route functions so popup appears after successful route.
    */
    if (typeof findRouteToBuilding === 'function' && !window.__routePopupBuildingWrapped) {
        window.__routePopupBuildingWrapped = true;
        const __baseFindRouteToBuildingPopup = findRouteToBuilding;

        findRouteToBuilding = function(buildingId) {
            const result = __baseFindRouteToBuildingPopup.apply(this, arguments);

            setTimeout(() => {
                showRoutePopupForSelectedBuilding(buildingId);
            }, 280);

            return result;
        };
    }

    if (typeof computeCompleteRouteToRoom === 'function' && !window.__routePopupRoomWrapped) {
        window.__routePopupRoomWrapped = true;
        const __baseComputeCompleteRouteToRoomPopup = computeCompleteRouteToRoom;

        computeCompleteRouteToRoom = function(roomFeature) {
            const result = __baseComputeCompleteRouteToRoomPopup.apply(this, arguments);

            const buildingId = Number(roomFeature?.properties?.building_id || selectedDestinationBuildingId);
            if (buildingId) {
                setTimeout(() => {
                    showRoutePopupForSelectedBuilding(buildingId);
                }, 360);
            }

            return result;
        };
    }

    /*
       Make every building click open indoor rooms and show popup.
       This does not remove existing building click behavior.
    */
    function attachIndoorClickToBuildingLayersFinal() {
        if (!window.__routePopupBuildingClickHooked) {
            window.__routePopupBuildingClickHooked = true;
        }

        map.eachLayer(layer => {
            const feature = layer.feature;
            const props = feature?.properties || {};

            const buildingId =
                props.id ??
                props.building_id ??
                props.properties?.id ??
                null;

            if (!buildingId || !feature?.geometry) return;
            if (layer.__indoorClickAdded) return;

            layer.__indoorClickAdded = true;

            layer.on('click', function() {
                // If this building has no indoor map, do nothing silently.
                if (!hasIndoorForBuildingFinal(buildingId)) {
                    return;
                }

                showRoutePopupForSelectedBuilding(buildingId);

                if (typeof openIndoorPanelForBuilding === 'function') {
                    openIndoorPanelForBuilding(Number(buildingId));
                }
            });
        });
    }

    /*
       Try hooking after data/rendering, and after map layer changes.
    */
    setTimeout(attachIndoorClickToBuildingLayersFinal, 1200);
    setTimeout(attachIndoorClickToBuildingLayersFinal, 2500);

    map.on('layeradd', function() {
        setTimeout(attachIndoorClickToBuildingLayersFinal, 80);
    });

    // Popup is now a Leaflet popup anchored to the building.
    // No manual screen-follow positioning needed.

    window.showRouteBuildingPopup = showRouteBuildingPopup;
    window.closeRouteBuildingPopup = closeRouteBuildingPopup;
    window.openIndoorFromRoutePopup = openIndoorFromRoutePopup;


    /* =========================================================
       INDOOR FRONT MODE PATCH
       Adds body.indoor-open while indoor panel is active.
    ========================================================= */

    function setIndoorFrontModeFinal(isOpen = true) {
        const body = document.body;
        const panel = document.getElementById('indoorPanel');
        const backdrop = document.getElementById('indoorBackdrop');

        if (isOpen) {
            body.classList.add('indoor-open');

            if (panel) {
                panel.classList.add('active');
                panel.style.zIndex = '120010';
            }

            if (backdrop) {
                backdrop.classList.add('active');
                backdrop.style.zIndex = '120000';
            }

            if (typeof closeFloatingActionCard === 'function') closeFloatingActionCard();
            if (typeof closeRouteBuildingPopup === 'function') closeRouteBuildingPopup();

            const browseModal = document.getElementById('browseOptionsModal');
            if (browseModal) browseModal.style.display = 'none';

            const searchPanel = document.getElementById('ai-search-panel');
            const voicePanel = document.getElementById('ai-voice-panel');
            const dock = document.getElementById('floating-route-ui');

            if (searchPanel) searchPanel.style.display = 'none';
            if (voicePanel) voicePanel.style.display = 'none';
            if (dock) dock.classList.remove('transforming', 'search-mode', 'voice-mode');

            setTimeout(() => {
                if (typeof indoorMap !== 'undefined' && indoorMap) {
                    indoorMap.invalidateSize();
                }
            }, 180);
        } else {
            body.classList.remove('indoor-open');

            if (panel) panel.classList.remove('active');
            if (backdrop) backdrop.classList.remove('active');

            setTimeout(() => {
                if (typeof map !== 'undefined' && map) {
                    map.invalidateSize();
                }
            }, 120);
        }
    }

    /*
       Wrap indoor open/close functions so indoor is always front.
    */
    if (typeof openIndoorPanelForBuilding === 'function' && !window.__indoorFrontOpenWrapped) {
        window.__indoorFrontOpenWrapped = true;
        const __baseOpenIndoorPanelForBuildingFront = openIndoorPanelForBuilding;

        openIndoorPanelForBuilding = function() {
            const result = __baseOpenIndoorPanelForBuildingFront.apply(this, arguments);

            setTimeout(() => {
                setIndoorFrontModeFinal(true);
            }, 40);

            return result;
        };
    }

    if (typeof closeIndoorPanelFn === 'function' && !window.__indoorFrontCloseWrapped) {
        window.__indoorFrontCloseWrapped = true;
        const __baseCloseIndoorPanelFront = closeIndoorPanelFn;

        closeIndoorPanelFn = function() {
            const result = __baseCloseIndoorPanelFront.apply(this, arguments);
            setIndoorFrontModeFinal(false);
            return result;
        };
    }

    /*
       Direct button/backdrop safety.
    */
    document.addEventListener('DOMContentLoaded', function() {
        const closeBtn = document.getElementById('closeIndoorPanel');
        const backdrop = document.getElementById('indoorBackdrop');

        if (closeBtn && !closeBtn.__indoorFrontBound) {
            closeBtn.__indoorFrontBound = true;
            closeBtn.addEventListener('click', function() {
                setTimeout(() => setIndoorFrontModeFinal(false), 20);
            });
        }

        if (backdrop && !backdrop.__indoorFrontBound) {
            backdrop.__indoorFrontBound = true;
            backdrop.addEventListener('click', function() {
                setTimeout(() => setIndoorFrontModeFinal(false), 20);
            });
        }
    });

    /*
       Watch class changes in case another function opens/closes indoor panel.
    */
    (function watchIndoorPanelFrontMode() {
        const panel = document.getElementById('indoorPanel');
        if (!panel || panel.__frontModeObserver) return;

        panel.__frontModeObserver = true;

        const observer = new MutationObserver(() => {
            const isActive = panel.classList.contains('active') || panel.style.display === 'block';
            document.body.classList.toggle('indoor-open', isActive);

            if (isActive) {
                panel.style.zIndex = '120010';
                const backdrop = document.getElementById('indoorBackdrop');
                if (backdrop) backdrop.style.zIndex = '120000';
                setTimeout(() => {
                    if (typeof indoorMap !== 'undefined' && indoorMap) indoorMap.invalidateSize();
                }, 150);
            }
        });

        observer.observe(panel, {
            attributes: true,
            attributeFilter: ['class', 'style']
        });
    })();

    window.setIndoorFrontModeFinal = setIndoorFrontModeFinal;
    window.openIndoorPanelForBuilding = openIndoorPanelForBuilding;
    window.closeIndoorPanelFn = closeIndoorPanelFn;


    /* =========================================================
       INDOOR FLOOR BUTTONS + MAP FOCUS PATCH
       Hides room list/search and replaces floor select with large buttons.
    ========================================================= */

    function getIndoorFloorButtonsContainerFinal() {
        return document.getElementById('indoorFloorButtons');
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
            btn.textContent = formatIndoorFloorLabel(floor, mapItem.floor_label);

            if (Number(currentIndoorFloor) === floor) {
                btn.classList.add('active');
            }

            btn.addEventListener('click', function() {
                setIndoorFloorFromButtonFinal(floor);
            });

            container.appendChild(btn);
        });
    }

    function updateIndoorFloorButtonActiveFinal() {
        document.querySelectorAll('.indoor-floor-btn').forEach(btn => {
            btn.classList.toggle('active', Number(btn.dataset.floor) === Number(currentIndoorFloor));
        });
    }

    function setIndoorFloorFromButtonFinal(floor) {
        currentIndoorFloor = Number(floor);

        if (indoorFloorSelect) {
            indoorFloorSelect.value = String(currentIndoorFloor);
        }

        updateIndoorFloorButtonActiveFinal();
        renderIndoorRoomList();
        renderIndoorFloor();

        if (lastIndoorRoutePackage) {
            redrawPersistentIndoorRouteForCurrentFloor();
        }

        setTimeout(() => {
            if (indoorMap) {
                indoorMap.invalidateSize();

                const mapItem = allIndoorMaps.find(m =>
                    Number(m.building_id) === Number(currentIndoorBuildingId) &&
                    Number(m.floor_number) === Number(currentIndoorFloor)
                );

                const bounds = mapItem ? getIndoorMapBoundsFromGeometry(mapItem.geometry) : null;
                if (bounds && bounds.isValid()) {
                    indoorMap.fitBounds(bounds, {
                        padding: [24, 24],
                        animate: true
                    });
                }
            }
        }, 160);
    }

    /*
       Keep the old hidden select in sync if any old code changes it.
    */
    if (indoorFloorSelect && !indoorFloorSelect.__floorButtonSyncBound) {
        indoorFloorSelect.__floorButtonSyncBound = true;
        indoorFloorSelect.addEventListener('change', function() {
            setTimeout(updateIndoorFloorButtonActiveFinal, 30);
        });
    }

    /*
       Wrap indoor opening so floor buttons are created every time.
    */
    if (typeof openIndoorPanelForBuilding === 'function' && !window.__floorButtonsOpenWrapped) {
        window.__floorButtonsOpenWrapped = true;
        const __baseOpenIndoorPanelForBuildingFloorButtons = openIndoorPanelForBuilding;

        openIndoorPanelForBuilding = function() {
            const result = __baseOpenIndoorPanelForBuildingFloorButtons.apply(this, arguments);

            setTimeout(() => {
                renderIndoorFloorButtonsFinal();
                updateIndoorFloorButtonActiveFinal();

                if (indoorMap) {
                    indoorMap.invalidateSize();
                }
            }, 220);

            setTimeout(() => {
                if (indoorMap) {
                    indoorMap.invalidateSize();

                    const mapItem = allIndoorMaps.find(m =>
                        Number(m.building_id) === Number(currentIndoorBuildingId) &&
                        Number(m.floor_number) === Number(currentIndoorFloor)
                    );

                    const routePoints = (typeof persistentIndoorRouteByFloor !== 'undefined' && persistentIndoorRouteByFloor)
                        ? (persistentIndoorRouteByFloor[currentIndoorFloor] || [])
                        : [];

                    if (routePoints.length >= 2) {
                        indoorMap.fitBounds(L.latLngBounds(routePoints), {
                            padding: [44, 44],
                            animate: false
                        });
                    } else {
                        const bounds = mapItem ? getIndoorMapBoundsFromGeometry(mapItem.geometry) : null;
                        if (bounds && bounds.isValid()) {
                            indoorMap.fitBounds(bounds, {
                                padding: [24, 24],
                                animate: false
                            });
                        }
                    }
                }
            }, 420);

            return result;
        };

        window.openIndoorPanelForBuilding = openIndoorPanelForBuilding;
    }

    /*
       Wrap floor render so active button always follows current floor.
    */
    if (typeof renderIndoorFloor === 'function' && !window.__floorButtonsRenderWrapped) {
        window.__floorButtonsRenderWrapped = true;
        const __baseRenderIndoorFloorButtons = renderIndoorFloor;

        renderIndoorFloor = function() {
            const result = __baseRenderIndoorFloorButtons.apply(this, arguments);

            setTimeout(() => {
                renderIndoorFloorButtonsFinal();
                updateIndoorFloorButtonActiveFinal();

                if (indoorMap) {
                    indoorMap.invalidateSize();
                }
            }, 60);

            return result;
        };
    }

    window.renderIndoorFloorButtonsFinal = renderIndoorFloorButtonsFinal;
    window.setIndoorFloorFromButtonFinal = setIndoorFloorFromButtonFinal;


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



/* =========================================================
   FINAL MOBILE FRIENDLY JS PATCH
   Keeps Leaflet maps correct after mobile resizing/orientation/UI changes.
========================================================= */

function isMobileViewportFinal() {
    return window.matchMedia('(max-width: 768px)').matches;
}

function invalidateMainAndIndoorMapsFinal(delay = 160) {
    setTimeout(() => {
        if (typeof map !== 'undefined' && map) {
            map.invalidateSize();
        }

        if (typeof indoorMap !== 'undefined' && indoorMap) {
            indoorMap.invalidateSize();
        }
    }, delay);
}

function updateMobileViewportClassFinal() {
    document.body.classList.toggle('is-mobile-view', isMobileViewportFinal());
    invalidateMainAndIndoorMapsFinal(180);
}

window.addEventListener('resize', updateMobileViewportClassFinal);
window.addEventListener('orientationchange', function () {
    setTimeout(updateMobileViewportClassFinal, 280);
});

document.addEventListener('DOMContentLoaded', function () {
    updateMobileViewportClassFinal();

    document.querySelectorAll(
        '.floating-mode-btn, .floating-action-btn, .ai-search-submit, .ai-record-inline-btn, .route-btn, .indoor-floor-btn, .indoor-close'
    ).forEach(btn => {
        btn.addEventListener('touchstart', function () {}, {
            passive: true
        });
    });
});

/*
|---------------------------------------------------------------------------
| Wrap indoor open/close and floor button changes so map always resizes
| correctly on mobile.
|---------------------------------------------------------------------------
*/
if (typeof openIndoorPanelForBuilding === 'function' && !window.__mobileOpenIndoorWrapped) {
    window.__mobileOpenIndoorWrapped = true;
    const __baseOpenIndoorPanelForMobile = openIndoorPanelForBuilding;

    openIndoorPanelForBuilding = function () {
        const result = __baseOpenIndoorPanelForMobile.apply(this, arguments);

        document.body.classList.add('indoor-open');
        invalidateMainAndIndoorMapsFinal(120);
        invalidateMainAndIndoorMapsFinal(360);
        invalidateMainAndIndoorMapsFinal(700);

        return result;
    };

    window.openIndoorPanelForBuilding = openIndoorPanelForBuilding;
}

if (typeof closeIndoorPanelFn === 'function' && !window.__mobileCloseIndoorWrapped) {
    window.__mobileCloseIndoorWrapped = true;
    const __baseCloseIndoorPanelForMobile = closeIndoorPanelFn;

    closeIndoorPanelFn = function () {
        const result = __baseCloseIndoorPanelForMobile.apply(this, arguments);

        document.body.classList.remove('indoor-open');
        invalidateMainAndIndoorMapsFinal(140);

        return result;
    };

    window.closeIndoorPanelFn = closeIndoorPanelFn;
}

if (typeof setIndoorFloorFromButtonFinal === 'function' && !window.__mobileFloorButtonWrapped) {
    window.__mobileFloorButtonWrapped = true;
    const __baseSetIndoorFloorFromButtonMobile = setIndoorFloorFromButtonFinal;

    setIndoorFloorFromButtonFinal = function () {
        const result = __baseSetIndoorFloorFromButtonMobile.apply(this, arguments);

        invalidateMainAndIndoorMapsFinal(120);
        invalidateMainAndIndoorMapsFinal(320);

        return result;
    };

    window.setIndoorFloorFromButtonFinal = setIndoorFloorFromButtonFinal;
}

/*
|---------------------------------------------------------------------------
| Modal open helpers: scroll to bottom panel correctly on phone.
|---------------------------------------------------------------------------
*/
if (typeof openBrowseOptionsModal === 'function' && !window.__mobileBrowseModalWrapped) {
    window.__mobileBrowseModalWrapped = true;
    const __baseOpenBrowseOptionsModalMobile = openBrowseOptionsModal;

    openBrowseOptionsModal = function () {
        const result = __baseOpenBrowseOptionsModalMobile.apply(this, arguments);
        invalidateMainAndIndoorMapsFinal(120);
        return result;
    };

    window.openBrowseOptionsModal = openBrowseOptionsModal;
}

if (typeof openInlineTextSearch === 'function' && !window.__mobileTextSearchWrapped) {
    window.__mobileTextSearchWrapped = true;
    const __baseOpenInlineTextSearchMobile = openInlineTextSearch;

    openInlineTextSearch = function () {
        const result = __baseOpenInlineTextSearchMobile.apply(this, arguments);

        setTimeout(() => {
            const input = document.getElementById('destination-search-input');
            if (input && isMobileViewportFinal()) {
                input.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }, 180);

        return result;
    };

    window.openInlineTextSearch = openInlineTextSearch;
}

invalidateMainAndIndoorMapsFinal(400);
