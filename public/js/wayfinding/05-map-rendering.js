    function getPathType(feature) {
        const props = feature.properties || {};
        let type = String(props.type || 'walkway').trim().toLowerCase();

        if (type === 'covered_stairs') return 'covered_stairs';
        if (type.includes('stairs')) return 'stairs';
        if (type === 'road' || type === 'roads') return 'road';
        return 'walkway';
    }

    const pathConfig = {
        road: {
            color: '#475569',
            weight: 6,
            dashArray: null
        },
        walkway: {
            color: '#0ea5e9',
            weight: 4.5,
            dashArray: null
        },
        stairs: {
            color: '#f59e0b',
            weight: 4.5,
            dashArray: '4, 6'
        },
        covered_stairs: {
            color: '#1e293b',
            weight: 10,
            dashArray: null,
            className: 'path-covered-stairs'
        }
    };

    function stylePath(feature) {
        const type = getPathType(feature);
        const config = pathConfig[type] || pathConfig.walkway;

        return {
            color: config.color,
            weight: config.weight,
            opacity: 0.9,
            lineCap: 'round',
            lineJoin: 'round',
            dashArray: config.dashArray || null,
            className: `path-interactive ${config.className || ''}`
        };
    }

    function renderLanduses() {
        if (landuseLayer) {
            map.removeLayer(landuseLayer);
            landuseLayer = null;
        }

        if (landuseLabelLayer) {
            map.removeLayer(landuseLabelLayer);
            landuseLabelLayer = null;
        }

        if (landuseImageLayer) {
            map.removeLayer(landuseImageLayer);
            landuseImageLayer = null;
        }

        landuseLabelLayer = L.layerGroup();
        landuseImageLayer = L.layerGroup();

        const featureCollection = {
            type: 'FeatureCollection',
            features: (landuseRecords || []).map(landuse => ({
                type: 'Feature',
                geometry: landuse.geometry,
                properties: {
                    ...(landuse.properties || {}),
                    id: landuse.id,
                    name: landuse.name,
                    type: landuse.type ?? landuse.landuse_type ?? landuse.properties?.type ?? null,
                    landuse_type: landuse.landuse_type ?? landuse.type ?? landuse.properties?.landuse_type ?? null,
                    image: landuse.image || null,

                    image_width: Number(landuse.image_width || 120),
                    image_height: Number(landuse.image_height || 120),
                    image_rotation: Number(landuse.image_rotation || 0),
                    image_offset_x: Number(landuse.image_offset_x || 0),
                    image_offset_y: Number(landuse.image_offset_y || 0),

                    image_scale_x: Number(landuse.image_scale_x ?? 1),
                    image_scale_y: Number(landuse.image_scale_y ?? 1),
                    image_offset_x_ratio: Number(landuse.image_offset_x_ratio ?? 0),
                    image_offset_y_ratio: Number(landuse.image_offset_y_ratio ?? 0),

                    polygon_base_angle: Number(landuse.polygon_base_angle ?? 0),
                    image_local_scale_x: Number(landuse.image_local_scale_x ?? 1),
                    image_local_scale_y: Number(landuse.image_local_scale_y ?? 1),
                    image_local_offset_u: Number(landuse.image_local_offset_u ?? 0),
                    image_local_offset_v: Number(landuse.image_local_offset_v ?? 0),
                    image_local_rotation: Number(landuse.image_local_rotation ?? 0),

                    image_tl_lat: landuse.image_tl_lat ?? null,
                    image_tl_lng: landuse.image_tl_lng ?? null,
                    image_tr_lat: landuse.image_tr_lat ?? null,
                    image_tr_lng: landuse.image_tr_lng ?? null,
                    image_bl_lat: landuse.image_bl_lat ?? null,
                    image_bl_lng: landuse.image_bl_lng ?? null,
                    image_br_lat: landuse.image_br_lat ?? null,
                    image_br_lng: landuse.image_br_lng ?? null,
                }
            }))
        };

        landuseLayer = L.geoJSON(featureCollection, {
            pane: 'pathsPane',
            renderer: OUTDOOR_PATHS_RENDERER,
            interactive: false,
            style: function(feature) {
                const p = feature?.properties || {};
                const isField = isOpenFieldLanduse({
                    name: p.name,
                    properties: p
                });
                const isCourt = isMultipurposeCourtLanduse({
                    name: p.name,
                    properties: p
                });
                const hasImage = !!p.image;
                const isDesign = isDesignLanduse({
                    type: p.type,
                    name: p.name,
                    properties: p
                });

                if (isDesign) {
                    return {
                        color: '#a855f7',
                        weight: 1,
                        fillColor: hasImage ? '#ffffff' : '#f3e8ff',
                        fillOpacity: hasImage ? 0.03 : 0.22,
                        dashArray: '4, 6'
                    };
                }

                if (isCourt) {
                    return {
                        color: '#2563eb',
                        weight: 1.5,
                        fillColor: hasImage ? '#ffffff' : '#93c5fd',
                        fillOpacity: hasImage ? 0.03 : 0.32
                    };
                }

                if (isField) {
                    return {
                        color: '#2f7d32',
                        weight: 1.5,
                        fillColor: hasImage ? '#ffffff' : '#86efac',
                        fillOpacity: hasImage ? 0.03 : 0.38
                    };
                }

                return {
                    color: '#94a3b8',
                    weight: 1,
                    fillColor: hasImage ? '#ffffff' : '#e2e8f0',
                    fillOpacity: hasImage ? 0.03 : 0.20
                };
            },
            onEachFeature: function(feature, layer) {
                const p = feature.properties || {};

                /*
                |--------------------------------------------------------------------------
                | LANDUSE POPUP / MAP CLICK REMOVED
                |--------------------------------------------------------------------------
                | Landuse polygons are display-only on the map now.
                | No popup and no direct map-click route action.
                | Routing to landuse still works through Browse Destination / text search
                | because selectLanduseDestination() and findRouteToLanduse() are untouched.
                */
                if (p.image) {
                    addClippedLanduseOverlay(feature, p, landuseImageLayer);
                }
            }
        }).addTo(map);

        landuseImageLayer.addTo(map);
        landuseLabelLayer.addTo(map);
    }

    function renderBuildings() {
        let geojsonLayers = [];
        updateBuildingPerformanceMode();

        buildingRecords.forEach((building, index) => {
            const buildingName = building.name || building.properties?.name || 'Building';
            const baseColor = normalizeColor(building.color || building.properties?.color || '#2b82cc');

            const geojson = {
                type: 'Feature',
                geometry: building.geometry,
                properties: {
                    ...(building.properties || {}),
                    id: building.id,
                    name: buildingName,
                    color: baseColor
                }
            };

            const className = `fake-3d-building-${index}`;
            addDynamicBuildingStyle(className, baseColor);

            /*
            |--------------------------------------------------------------------------
            | BUILDING LAYER ONLY
            |--------------------------------------------------------------------------
            | No duplicate shadow polygons are drawn here.
            | Desktop/mobile shadows are handled by lightweight CSS drop-shadow.
            */
            const layer = L.geoJSON(geojson, {
                pane: 'buildingsPane',
                renderer: OUTDOOR_BUILDINGS_RENDERER,
                className: `fake-3d-building ${className}`,
                style: {
                    color: '#1f2937',
                    weight: 1.5,
                    fillColor: baseColor,
                    fillOpacity: 1,
                    lineJoin: 'round'
                },
                onEachFeature: function(feature, layer) {
                    const bId = feature.properties.id;

                    layer.bindPopup(`
                        <h3 class="custom-popup-title">🏢 ${buildingName}</h3>
                        <p class="custom-popup-subtitle">Click to open indoor rooms</p>
                    `);

                    layer.on('click', () => {
                        destinationBuildingSelect.value = String(bId);
                        selectedDestinationBuildingId = Number(bId);
                        updateRouteLabels();

                        // Buildings without indoor maps should do nothing silently.
                        if (hasIndoorMapForBuilding(bId)) {
                            openIndoorPanelForBuilding(bId);
                        }
                    });
                }
            }).addTo(map);

            applyBuildingDepthVariables(layer, baseColor);
            geojsonLayers.push(layer);
        });

        if (geojsonLayers.length > 0) {
            const group = L.featureGroup(geojsonLayers);
            const bounds = group.getBounds();

            if (bounds.isValid()) {
                map.fitBounds(bounds, {
                    padding: [50, 50],
                    maxZoom: 18.5
                });
                campusBounds = bounds.pad(0.08);
            }
        } else {
            map.setView([10.2925, 124.9985], IS_MOBILE_OUTDOOR_VIEW ? MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE : 18);
        }
    }

    function renderPaths() {
        L.geoJSON(pathGeojson, {
            pane: 'pathsPane',
            renderer: OUTDOOR_PATHS_RENDERER,
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

        L.geoJSON(pathGeojson, {
            pane: 'pathsPane',
            renderer: OUTDOOR_PATHS_RENDERER,
            style: stylePath,
            onEachFeature: function(feature, layer) {
                const props = feature.properties || {};
                const name = props.name || 'Unnamed Route';
                const typeLabel = String(props.type || 'Route').replaceAll('_', ' ').toUpperCase();

                layer.bindPopup(`
                    <div style="font-family:'Plus Jakarta Sans',sans-serif; text-align:left; min-width:180px;">
                        <div style="font-size:10px; color:#64748b; font-weight:800; letter-spacing:0.5px; margin-bottom:2px;">
                            ${typeLabel}
                        </div>
                        <div style="font-size:15px; font-weight:700; color:#1e293b;">
                            ${name}
                        </div>
                    </div>
                `);

                layer.on('click', function(e) {
                    if (placingStartMode) {
                        placePickStartAndClose(e.latlng.lat, e.latlng.lng, 'Start Point on Path');
                        L.DomEvent.stopPropagation(e);
                        return;
                    }
                });
            }
        }).addTo(map);

        L.geoJSON(pathGeojson, {
            pane: 'pathsPane',
            renderer: OUTDOOR_PATHS_RENDERER,
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

        const legend = L.control({
            position: 'bottomright'
        });
        legend.onAdd = function() {
            const div = L.DomUtil.create('div', 'premium-legend');
            div.innerHTML = `
                <span class="legend-title">Campus Routes</span>

                <div class="legend-item">
                    <span class="legend-line" style="background:#475569"></span>
                    <span>Main Road</span>
                </div>

                <div class="legend-item">
                    <span class="legend-line" style="background:#0ea5e9"></span>
                    <span>Walkway</span>
                </div>

                <div class="legend-item">
                    <span class="legend-line" style="background:#f59e0b; border:1px dashed #fff"></span>
                    <span>Open Stairs</span>
                </div>

                <div class="legend-item">
                    <span class="legend-line" style="background:#1e293b; height:8px;"></span>
                    <span>Covered Stairs</span>
                </div>

                <div class="legend-item" style="margin-top:12px; padding-top:12px; border-top:1px solid #e2e8f0;">
                    <span class="legend-line" style="background:#2563eb"></span>
                    <span>Computed Route</span>
                </div>
            `;
            return div;
        };
        legend.addTo(map);
    }
