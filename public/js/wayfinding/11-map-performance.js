/* =========================================================
   CONSISTENT FAKE 3D MOVEMENT PATCH
   Purpose:
   - Keep the same fake 3D depth while stationary, dragging, and zooming.
   - Disable transitions during movement so the shadow never pops in late.
   - No geometry, route, landuse, or API logic changed.
========================================================= */
(function finalFake3DLagReducerPatch() {
    if (window.__finalFake3DLagReducerPatchApplied) return;
    window.__finalFake3DLagReducerPatchApplied = true;

    const body = document.body;
    let stopTimer = null;

    function movingOn() {
        if (!body) return;
        body.classList.add('map-moving-lite-3d');
        if (stopTimer) clearTimeout(stopTimer);
    }

    function movingOffSoon() {
        if (!body) return;
        if (stopTimer) clearTimeout(stopTimer);
        stopTimer = setTimeout(() => {
            body.classList.remove('map-moving-lite-3d');
        }, 220);
    }

    if (typeof map !== 'undefined' && map) {
        map.on('movestart dragstart zoomstart', movingOn);
        map.on('move zoom', movingOn);
        map.on('moveend dragend zoomend', movingOffSoon);
    }

    const oldStyle = document.getElementById('final-fake3d-lag-reducer-style');
    if (oldStyle) oldStyle.remove();

    const style = document.createElement('style');
    style.id = 'final-fake3d-lag-reducer-style';
    style.textContent = `
        /* Make SVG rendering cheaper but keep crisp borders */
        .leaflet-buildingsPane-pane svg,
        .leaflet-overlay-pane svg {
            shape-rendering: geometricPrecision;
        }

        .fake-3d-building,
        .leaflet-buildingsPane-pane .leaflet-interactive {
            will-change: auto !important;
            backface-visibility: hidden;
            transform: translateZ(0) !important;
            transition: fill-opacity 0.10s ease, stroke-width 0.10s ease !important;
            vector-effect: non-scaling-stroke !important;
        }

        /* Static depth polygons stay visible; top building filters stay disabled. */
        body.map-moving-lite-3d .fake-3d-building,
        body.map-moving-lite-3d .fake-3d-building:hover,
        body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive,
        body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive:hover {
            filter: none !important;
            transition: none !important;
            stroke-width: 1.25 !important;
            fill-opacity: 0.98 !important;
        }

        /* Zoom never activates a CSS filter, preventing a late repaint. */
        body.map-zooming .fake-3d-building,
        body.map-zooming .fake-3d-building:hover,
        body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive,
        body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive:hover {
            filter: none !important;
            transition: none !important;
            stroke-width: 1.25 !important;
        }

        /* Mobile uses the same static depth polygons without CSS filters. */
        @media (hover: none), (max-width: 768px) {
            body.map-moving-lite-3d .fake-3d-building,
            body.map-moving-lite-3d .fake-3d-building:hover,
            body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive,
            body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive:hover {
                filter: none !important;
                stroke-width: 1.15 !important;
                fill-opacity: 0.98 !important;
            }

            body.map-zooming .fake-3d-building,
            body.map-zooming .fake-3d-building:hover,
            body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive,
            body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive:hover {
                filter: none !important;
                stroke-width: 1.15 !important;
            }
        }

        @media (max-width: 420px) {
            body.map-moving-lite-3d .fake-3d-building,
            body.map-moving-lite-3d .fake-3d-building:hover,
            body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive,
            body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive:hover,
            body.map-zooming .fake-3d-building,
            body.map-zooming .fake-3d-building:hover,
            body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive,
            body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive:hover {
                filter: none !important;
                stroke-width: 1.1 !important;
            }
        }
    `;

    document.head.appendChild(style);
})();
