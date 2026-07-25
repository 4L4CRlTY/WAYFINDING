<script>
    let pathLayer;
    let pathOutlineLayer;
    let pathFramesLayer;

    const pathConfig = {
        road: { color: '#475569', weight: 6, dashArray: null },
        walkway: { color: '#0ea5e9', weight: 4.5, dashArray: null },
        stairs: { color: '#f59e0b', weight: 4.5, dashArray: '4, 6' },
        covered_stairs: { color: '#1e293b', weight: 10, dashArray: null, className: 'path-covered-stairs' },
        hazard: { color: '#e11d48', weight: 5, dashArray: '8, 8', className: 'path-hazard-flow' }
    };

    function getPathType(feature) {
        const props = feature.properties || {};
        let type = String(props.type || 'walkway').trim().toLowerCase();

        if (props.is_hazard) return 'hazard';
        if (type === 'covered_stairs') return 'covered_stairs';
        if (type.includes('stairs')) return 'stairs';
        if (type === 'path' || type === 'walkway') return 'walkway';
        if (type === 'road' || type === 'roads') return 'road';

        return 'walkway';
    }

    function stylePath(feature) {
        const type = getPathType(feature);
        const config = pathConfig[type] || pathConfig.walkway;

        return {
            color: config.color,
            weight: config.weight,
            opacity: 0.9,
            lineCap: 'round',
            lineJoin: 'round',
            dashArray: config.dashArray,
            className: `path-interactive ${config.className || ''}`
        };
    }

    function onEachPath(feature, layer) {
        const props = feature.properties || {};
        const name = props.name || 'Unnamed Route';
        const typeLabel = String(props.type || 'Route').replaceAll('_', ' ').toUpperCase();

        const pathHazards = (window.hazardPoints || []).filter(h =>
            Number(h.path_id) === Number(props.id) &&
            Boolean(h.is_active) === true
        );

        let badgeColor = '#059669';
        let badgeBg = '#d1fae5';
        let badgeText = 'SAFE ROUTE';

        if (pathHazards.length) {
            const maxSeverity = Math.max(...pathHazards.map(h => Number(h.severity_level || 1)));

            if (maxSeverity >= 3) {
                badgeColor = '#dc2626';
                badgeBg = '#fee2e2';
                badgeText = 'SEVERITY 3 HAZARD';
            } else {
                badgeColor = '#ca8a04';
                badgeBg = '#fef3c7';
                badgeText = `SEVERITY ${maxSeverity} CAUTION`;
            }
        }

        layer.bindPopup(`
            <div style="font-family: 'Plus Jakarta Sans', sans-serif; text-align: left; min-width: 180px;">
                <div style="font-size: 10px; color: #64748b; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 2px;">
                    ${typeLabel}
                </div>
                <div style="font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 10px;">
                    ${name}
                </div>
                <div style="background: ${badgeBg}; color: ${badgeColor}; padding: 5px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: ${badgeColor}; box-shadow: 0 0 4px ${badgeColor};"></span>
                    ${badgeText}
                </div>
            </div>
        `);

        layer.on({
            click: function (e) {
                if (window.placingStartMode) {
                    const maxSeverity = pathHazards.length
                        ? Math.max(...pathHazards.map(h => Number(h.severity_level || 1)))
                        : 0;

                    if (maxSeverity >= 3) {
                        alert('Severity level 3 hazard path cannot be used as starting point.');
                        return;
                    }

                    if (typeof window.setStartFromLatLng === 'function') {
                        window.setStartFromLatLng(
                            e.latlng.lat,
                            e.latlng.lng,
                            'Start Point on Path',
                            'path'
                        );
                    }

                    window.placingStartMode = false;

                    if (typeof window.updateRouteLabels === 'function') {
                        window.updateRouteLabels();
                    }

                    if (typeof window.setRouteResultLabel === 'function') {
                        window.setRouteResultLabel('Start point placed on path.');
                    }

                    return;
                }

                map.fitBounds(e.target.getBounds(), {
                    padding: [60, 60],
                    animate: true,
                    duration: 1
                });
            }
        });
    }

    function loadPathsVisual() {
        fetch('/api/paths')
            .then(res => res.json())
            .then(data => {
                pathOutlineLayer = L.geoJSON(data, {
                    pane: 'pathsPane',
                    filter: (f) => getPathType(f) !== 'covered_stairs',
                    style: (f) => {
                        const isRoad = getPathType(f) === 'road';

                        return {
                            color: '#e2e8f0',
                            weight: isRoad ? 10 : 8,
                            opacity: 0.8,
                            lineCap: 'round',
                            lineJoin: 'round',
                            interactive: false
                        };
                    }
                }).addTo(map);

                pathLayer = L.geoJSON(data, {
                    pane: 'pathsPane',
                    style: stylePath,
                    onEachFeature: onEachPath
                }).addTo(map);

                pathFramesLayer = L.geoJSON(data, {
                    pane: 'pathsPane',
                    filter: (f) => getPathType(f) === 'covered_stairs',
                    style: {
                        color: '#f8fafc',
                        weight: 6,
                        opacity: 0.95,
                        dashArray: '2, 10',
                        className: 'path-canopy-frames'
                    },
                    interactive: false
                }).addTo(map);
            })
            .catch(err => console.error('Error loading paths:', err));
    }

    const legend = L.control({ position: 'bottomright' });

    legend.onAdd = function () {
        const div = L.DomUtil.create('div', 'premium-legend');
        div.innerHTML = `
            <span class="legend-title">Campus Routes</span>

            <div class="legend-item">
                <span class="legend-line" style="background: ${pathConfig.road.color}"></span>
                <span>Main Road</span>
            </div>

            <div class="legend-item">
                <span class="legend-line" style="background: ${pathConfig.walkway.color}"></span>
                <span>Walkway</span>
            </div>

            <div class="legend-item">
                <span class="legend-line" style="background: ${pathConfig.stairs.color}; border: 1px dashed #fff"></span>
                <span>Open Stairs</span>
            </div>

            <div class="legend-item">
                <span class="legend-line" style="background: ${pathConfig.covered_stairs.color}; height: 8px; border-radius: 2px; position: relative;">
                    <span style="position: absolute; top:0; left:0; width:100%; height:100%; border-left: 2px solid white; border-right: 2px solid white; border-top: 2px dotted white; box-sizing: border-box;"></span>
                </span>
                <span>Covered Stairs</span>
            </div>

            <div class="legend-item" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                <span class="legend-line" style="background: #22c55e;"></span>
                <span style="color: #16a34a; font-weight: 700;">Safe Route</span>
            </div>

            <div class="legend-item">
                <span class="legend-line" style="background: #facc15;"></span>
                <span style="color: #ca8a04; font-weight: 700;">Severity 1-2 Route</span>
            </div>

            <div class="legend-item">
                <span class="legend-line" style="background: #dc2626;"></span>
                <span style="color: #dc2626; font-weight: 700;">Severity 3 Route</span>
            </div>
        `;
        return div;
    };

    legend.addTo(map);
    loadPathsVisual();
</script>
