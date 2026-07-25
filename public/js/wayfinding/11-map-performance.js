/* =========================================================
   FINAL FAKE 3D LAG REDUCER PATCH
   Purpose:
   - Keep fake 3D visible.
   - Use lighter shadow only while dragging/zooming.
   - Restore normal fake 3D after movement stops.
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

        /* While moving/zooming: keep 3D, but use cheaper 2-shadow depth */
        body.map-moving-lite-3d .fake-3d-building,
        body.map-moving-lite-3d .fake-3d-building:hover,
        body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive,
        body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive:hover {
            filter:
                drop-shadow(2px 3px 1px rgba(15, 23, 42, 0.34))
                drop-shadow(4px 5px 2px rgba(15, 23, 42, 0.14)) !important;
            transition: none !important;
            stroke-width: 1.25 !important;
            fill-opacity: 0.94 !important;
        }

        /* During zoom specifically: even lighter to reduce stutter */
        body.map-zooming .fake-3d-building,
        body.map-zooming .fake-3d-building:hover,
        body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive,
        body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive:hover {
            filter:
                drop-shadow(2px 2px 1px rgba(15, 23, 42, 0.30))
                drop-shadow(3px 4px 2px rgba(15, 23, 42, 0.12)) !important;
            transition: none !important;
            stroke-width: 1.2 !important;
        }

        /* Mobile: lighter shadows while interacting, still 3D */
        @media (hover: none), (max-width: 768px) {
            body.map-moving-lite-3d .fake-3d-building,
            body.map-moving-lite-3d .fake-3d-building:hover,
            body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive,
            body.map-moving-lite-3d .leaflet-buildingsPane-pane .leaflet-interactive:hover {
                filter:
                    drop-shadow(1px 2px 1px rgba(15, 23, 42, 0.34))
                    drop-shadow(3px 4px 2px rgba(15, 23, 42, 0.14)) !important;
                stroke-width: 1.15 !important;
            }

            body.map-zooming .fake-3d-building,
            body.map-zooming .fake-3d-building:hover,
            body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive,
            body.map-zooming .leaflet-buildingsPane-pane .leaflet-interactive:hover {
                filter:
                    drop-shadow(1px 2px 1px rgba(15, 23, 42, 0.28))
                    drop-shadow(2px 3px 2px rgba(15, 23, 42, 0.12)) !important;
                stroke-width: 1.1 !important;
            }
        }
    `;

    document.head.appendChild(style);
})();
