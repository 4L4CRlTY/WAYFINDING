<script>
    window.campusBounds = null;
    window.buildingLayersReady = false;
    window.buildingFeatureGroup = null;

    function normalizeColor(color) {
        if (!color || typeof color !== 'string') return '#2b82cc';

        color = color.trim();

        if (/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/.test(color)) {
            if (color.length === 4) {
                return '#' + color[1] + color[1] + color[2] + color[2] + color[3] + color[3];
            }
            return color;
        }

        return '#2b82cc';
    }

    function hexToRgb(hex) {
        hex = normalizeColor(hex).replace('#', '');

        return {
            r: parseInt(hex.substring(0, 2), 16),
            g: parseInt(hex.substring(2, 4), 16),
            b: parseInt(hex.substring(4, 6), 16)
        };
    }

    function rgbToHex(r, g, b) {
        return '#' + [r, g, b]
            .map(v => {
                v = Math.max(0, Math.min(255, Math.round(v)));
                return v.toString(16).padStart(2, '0');
            })
            .join('');
    }

    function darkenColor(hex, percent) {
        const { r, g, b } = hexToRgb(hex);

        return rgbToHex(
            r * (1 - percent),
            g * (1 - percent),
            b * (1 - percent)
        );
    }

    function addDynamicBuildingStyle(className, baseColor) {
        const c1 = baseColor;
        const c2 = darkenColor(baseColor, 0.15);
        const c3 = darkenColor(baseColor, 0.25);
        const c4 = darkenColor(baseColor, 0.35);
        const c5 = darkenColor(baseColor, 0.45);

        const style = document.createElement('style');
        style.innerHTML = `
            .${className} {
                filter: drop-shadow(calc(var(--step) * 1) calc(var(--step) * 1) 0px ${c1})
                        drop-shadow(calc(var(--step) * 2) calc(var(--step) * 2) 0px ${c2})
                        drop-shadow(calc(var(--step) * 3) calc(var(--step) * 3) 0px ${c3})
                        drop-shadow(calc(var(--step) * 4) calc(var(--step) * 4) 0px ${c4})
                        drop-shadow(calc(var(--step) * 6) calc(var(--step) * 6) 5px rgba(0,0,0,0.3));
                transition: transform 0.2s ease, filter 0.2s ease;
                cursor: pointer;
            }

            .${className}:hover {
                filter: drop-shadow(calc(var(--step) * 1) calc(var(--step) * 1) 0px ${c1})
                        drop-shadow(calc(var(--step) * 2) calc(var(--step) * 2) 0px ${c2})
                        drop-shadow(calc(var(--step) * 3) calc(var(--step) * 3) 0px ${c3})
                        drop-shadow(calc(var(--step) * 4) calc(var(--step) * 4) 0px ${c4})
                        drop-shadow(calc(var(--step) * 5) calc(var(--step) * 5) 0px ${c5})
                        drop-shadow(calc(var(--step) * 6) calc(var(--step) * 6) 0px ${c5})
                        drop-shadow(calc(var(--step) * 9) calc(var(--step) * 9) 8px rgba(0,0,0,0.4));
                transform: translate(-2px, -2px);
            }
        `;
        document.head.appendChild(style);
    }

    fetch('/api/buildings')
        .then(res => res.json())
        .then(data => {
            let geojsonLayers = [];

            data.forEach((building, index) => {
                let buildingName = building.name || (building.properties && building.properties.name) || "Building";

                let baseColor = normalizeColor(
                    building.color ||
                    (building.properties && building.properties.color) ||
                    '#2b82cc'
                );

                let geojson = {
                    type: "Feature",
                    geometry: building.geometry,
                    properties: {
                        ...(building.properties || {}),
                        id: building.id,
                        name: buildingName,
                        color: baseColor
                    }
                };

                let className = `fake-3d-building-${index}`;
                addDynamicBuildingStyle(className, baseColor);

                let layer = L.geoJSON(geojson, {
                    pane: 'buildingsPane',
                    className: `fake-3d-building ${className}`,
                    style: {
                        color: "#1f2937",
                        weight: 1.5,
                        fillColor: baseColor,
                        fillOpacity: 1,
                        lineJoin: 'round'
                    },
                    onEachFeature: function(feature, layer) {
                        layer.bindPopup(`
                            <h3 class="custom-popup-title">🏢 ${buildingName}</h3>
                            <p class="custom-popup-subtitle">SLSU Campus Facility</p>
                        `);
                    }
                }).addTo(map);

                geojsonLayers.push(layer);
            });

            if (geojsonLayers.length > 0) {
                let group = L.featureGroup(geojsonLayers);
                window.buildingFeatureGroup = group;

                const bounds = group.getBounds();
                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 18.5 });
                    window.campusBounds = bounds.pad(0.08);
                }

                window.buildingLayersReady = true;
            } else {
                map.setView([10.2925, 124.9985], 18);
            }

            updateShadows();

            setTimeout(() => {
                document.getElementById('map').style.opacity = '1';
            }, 100);
        })
        .catch(err => {
            console.error("Error loading buildings:", err);
            map.setView([10.2925, 124.9985], 18);
            document.getElementById('map').style.opacity = '1';
        });
</script>
