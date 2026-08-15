/* Indoor camera sizing is owned by the single ResizeObserver controller in
   04-indoor-routing.js. This file must never add delayed camera corrections. */

/* Outdoor framing is explicit at initial render, route drawing, and reset.
   Manual gestures are never wrapped or corrected from this file. */

window.toggleCampusEventPanel = toggleCampusEventPanel;
window.closeCampusEventPanel = closeCampusEventPanel;
window.routeToCampusEvent = routeToCampusEvent;
window.ensureCampusEventPanel = ensureCampusEventPanel;
window.renderCampusEventMarkers = renderCampusEventMarkers;



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
    let gestureFrame = null;
    let gestureStartedAt = 0;
    let gestureFrames = 0;
    let sustainedLowFpsGestures = 0;

    function downgradeToLow(reason) {
        if (window.wayfindingRenderProfile?.mode !== 'balanced') return;

        try {
            window.sessionStorage.setItem('wayfinding-render-quality', 'low');
        } catch (error) {
            // Restricted browsers may block storage; the current session still downgrades.
        }

        window.applyWayfindingRenderProfile({
            ...(window.wayfindingRenderProfile || {}),
            mode: 'low',
            mobile: true
        }, reason);
    }

    function sampleGestureFrame(timestamp) {
        if (!window.WayfindingInteraction?.isActive?.()) {
            gestureFrame = null;
            return;
        }
        if (!gestureStartedAt) gestureStartedAt = timestamp;
        gestureFrames += 1;
        gestureFrame = requestAnimationFrame(sampleGestureFrame);
    }

    function beginGestureFpsSample() {
        if (
            document.hidden
            || window.wayfindingRenderProfile?.mode !== 'balanced'
            || gestureFrame
        ) return;
        gestureStartedAt = performance.now();
        gestureFrames = 0;
        gestureFrame = requestAnimationFrame(sampleGestureFrame);
    }

    function finishGestureFpsSample() {
        if (gestureFrame) cancelAnimationFrame(gestureFrame);
        gestureFrame = null;
        const duration = performance.now() - gestureStartedAt;
        if (document.hidden || duration < 350 || gestureFrames < 2) return;

        const fps = (gestureFrames * 1000) / duration;
        sustainedLowFpsGestures = fps < 44
            ? sustainedLowFpsGestures + 1
            : Math.max(0, sustainedLowFpsGestures - 1);

        if (sustainedLowFpsGestures >= 1) {
            downgradeToLow('sustained-low-fps');
        }
    }

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

                if (pressureDuration < 260) return;

                downgradeToLow('runtime-pressure');

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

    window.WayfindingInteraction?.registerLifecycle?.('adaptive-fps', {
        start: beginGestureFpsSample,
        end: finishGestureFpsSample
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
