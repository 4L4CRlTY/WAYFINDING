    const buildingVisualLayers = new Map();
    let selectedBuildingVisualId = null;
    let buildingDepthLayerGroup = null;
    let buildingFarDepthLayerGroup = null;
    let buildingNearDepthLayerGroup = null;

    function syncAdaptiveBuildingDepth() {
        if (!buildingDepthLayerGroup || !buildingFarDepthLayerGroup) return;

        const showFarDepth = window.wayfindingRenderProfile?.mode !== 'low';
        const farDepthVisible = buildingDepthLayerGroup.hasLayer(buildingFarDepthLayerGroup);

        if (showFarDepth && !farDepthVisible) {
            buildingDepthLayerGroup.addLayer(buildingFarDepthLayerGroup);
        } else if (!showFarDepth && farDepthVisible) {
            buildingDepthLayerGroup.removeLayer(buildingFarDepthLayerGroup);
        }
    }

    window.addEventListener('wayfinding:render-profile', syncAdaptiveBuildingDepth);

    function setSelectedBuildingVisual(buildingId) {
        selectedBuildingVisualId = Number(buildingId) || null;

        buildingVisualLayers.forEach((geojsonLayer, layerBuildingId) => {
            if (!geojsonLayer || typeof geojsonLayer.eachLayer !== 'function') return;

            geojsonLayer.eachLayer(pathLayer => {
                const element = pathLayer?.getElement ? pathLayer.getElement() : null;
                if (!element) return;

                element.classList.toggle(
                    'building-selected',
                    Number(layerBuildingId) === selectedBuildingVisualId
                );
            });
        });
    }

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
            color: '#fffdfa',
            weight: 6.5,
            dashArray: null,
            casingColor: '#b8c1ca',
            casingWeight: 9.5
        },
        walkway: {
            color: '#a9d5ee',
            weight: 4,
            dashArray: null,
            casingColor: '#d1d9e0',
            casingWeight: 6.5
        },
        stairs: {
            color: '#d99627',
            weight: 3.5,
            dashArray: '4, 5',
            casingColor: '#ffffff',
            casingWeight: 6.5
        },
        covered_stairs: {
            color: '#687b8e',
            weight: 7.5,
            dashArray: null,
            casingColor: '#c5cdd5',
            casingWeight: 10,
            className: 'path-covered-stairs'
        }
    };

    function scaleStaticPathWeight(weight, minimum = 1.2) {
        if (!IS_MOBILE_OUTDOOR_VIEW) {
            return weight;
        }

        return Math.max(
            minimum,
            Math.round(weight * MOBILE_STATIC_PATH_WIDTH_SCALE * 10) / 10
        );
    }

    function stylePath(feature) {
        const type = getPathType(feature);
        const config = pathConfig[type] || pathConfig.walkway;

        return {
            color: config.color,
            weight: scaleStaticPathWeight(config.weight),
            opacity: 1,
            lineCap: 'round',
            lineJoin: 'round',
            dashArray: config.dashArray || null,
            interactive: !IS_MOBILE_OUTDOOR_VIEW,
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
        buildingVisualLayers.clear();

        if (buildingDepthLayerGroup) {
            map.removeLayer(buildingDepthLayerGroup);
        }

        buildingDepthLayerGroup = L.layerGroup().addTo(map);
        buildingFarDepthLayerGroup = L.layerGroup();
        buildingNearDepthLayerGroup = L.layerGroup().addTo(buildingDepthLayerGroup);

        if (SHOULD_RENDER_FAR_BUILDING_DEPTH) {
            buildingFarDepthLayerGroup.addTo(buildingDepthLayerGroup);
        }
        updateBuildingPerformanceMode();

        buildingRecords.forEach((building, index) => {
            const buildingName = building.name || building.properties?.name || 'Building';
            const sourceColor = normalizeColor(building.color || building.properties?.color || '#2b82cc');
            const baseColor = mixColors(sourceColor, '#a1b9c9', 0.36);

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

            const farDepthColor = darkenColor(baseColor, 0.46);
            const nearDepthColor = darkenColor(baseColor, 0.28);

            /*
            | Low-end phones retain the solid near-depth polygon, so buildings
            | never become flat. Only the second, farther depth polygon is omitted.
            */
            if (SHOULD_RENDER_FAR_BUILDING_DEPTH) {
                L.geoJSON(geojson, {
                    pane: 'buildingDepthPane',
                    renderer: OUTDOOR_BUILDING_DEPTH_RENDERER,
                    interactive: false,
                    className: 'building-depth-solid building-depth-solid-far',
                    style: {
                        color: farDepthColor,
                        weight: 1,
                        fillColor: farDepthColor,
                        fillOpacity: 0.40,
                        lineJoin: 'round',
                        depthPixelOffset: IS_MOBILE_OUTDOOR_VIEW ? 2 : 0
                    }
                }).addTo(buildingFarDepthLayerGroup);
            }

            L.geoJSON(geojson, {
                pane: 'buildingDepthPane',
                renderer: OUTDOOR_BUILDING_DEPTH_RENDERER,
                interactive: false,
                className: 'building-depth-solid building-depth-solid-near',
                style: {
                    color: nearDepthColor,
                    weight: IS_MOBILE_OUTDOOR_VIEW ? 0.7 : 1,
                    fillColor: nearDepthColor,
                    fillOpacity: IS_MOBILE_OUTDOOR_VIEW ? 0.68 : 0.88,
                    lineJoin: 'round',
                    depthPixelOffset: IS_MOBILE_OUTDOOR_VIEW ? 1 : 0
                }
            }).addTo(buildingNearDepthLayerGroup);

            /*
            |--------------------------------------------------------------------------
            | BUILDING LAYER ONLY
            |--------------------------------------------------------------------------
            | The top polygon remains interactive. Two non-interactive depth layers
            | live in their own pane so their appearance never waits for a zoom repaint.
            */
            const layer = L.geoJSON(geojson, {
                pane: 'buildingsPane',
                renderer: OUTDOOR_BUILDINGS_RENDERER,
                interactive: true,
                className: `fake-3d-building ${className}`,
                style: {
                    color: darkenColor(baseColor, 0.32),
                    weight: WAYFINDING_RENDER_PROFILE.mode === 'low'
                        ? 1.1
                        : (IS_MOBILE_OUTDOOR_VIEW ? 0.9 : 1.25),
                    fillColor: baseColor,
                    fillOpacity: WAYFINDING_RENDER_PROFILE.mode === 'low'
                        ? 1
                        : (IS_MOBILE_OUTDOOR_VIEW ? 0.96 : 0.98),
                    lineJoin: 'round'
                },
                onEachFeature: function(feature, layer) {
                    const bId = feature.properties.id;

                    layer.bindPopup(() => {
                        const hasIndoorMap = hasIndoorMapForBuilding(bId);
                        const indoorStatus = hasIndoorMap
                            ? 'INDOOR AVAILABLE'
                            : 'INDOOR NOT AVAILABLE';
                        const indoorStatusClass = hasIndoorMap
                            ? 'is-available'
                            : 'is-unavailable';

                        return `
                            <div class="building-map-summary ${indoorStatusClass}">
                                <span class="building-map-summary-status">
                                    <span class="building-map-summary-dot" aria-hidden="true"></span>
                                    ${indoorStatus}
                                </span>
                                <h3 class="building-map-summary-name">
                                    <span aria-hidden="true">🏢</span>
                                    <span>${buildingName}</span>
                                </h3>
                            </div>
                        `;
                    }, {
                        className: 'building-summary-leaflet-popup',
                        maxWidth: 250,
                        minWidth: 190
                    });

                    layer.on('click', () => {
                        setSelectedBuildingVisual(bId);
                        destinationBuildingSelect.value = String(bId);
                        selectedDestinationBuildingId = Number(bId);
                        updateRouteLabels();

                        // Buildings without indoor maps should do nothing silently.
                        if (hasIndoorMapForBuilding(bId)) {
                            window.prefetchWayfindingIndoorBuilding?.(bId);
                            openIndoorPanelForBuilding(bId);
                        }
                    });
                }
            }).addTo(map);

            applyBuildingDepthVariables(layer, baseColor);
            buildingVisualLayers.set(Number(building.id), layer);
            geojsonLayers.push(layer);
        });

        if (selectedBuildingVisualId) {
            setSelectedBuildingVisual(selectedBuildingVisualId);
        }

        syncAdaptiveBuildingDepth();

        if (geojsonLayers.length > 0) {
            const group = L.featureGroup(geojsonLayers);
            const bounds = group.getBounds();

            if (bounds.isValid()) {
                map.fitBounds(bounds, {
                    padding: [50, 50],
                    maxZoom: IS_MOBILE_OUTDOOR_VIEW
                        ? MOBILE_OUTDOOR_DEFAULT_ZOOM_VALUE
                        : 18.5,
                    animate: !IS_MOBILE_OUTDOOR_VIEW
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
            style: (f) => {
                const config = pathConfig[getPathType(f)] || pathConfig.walkway;
                return {
                    color: config.casingColor,
                    weight: scaleStaticPathWeight(config.casingWeight, 1.8),
                    opacity: 0.96,
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
                color: '#f4f7fa',
                weight: scaleStaticPathWeight(3.5, 1.25),
                opacity: 0.95,
                dashArray: '2, 7',
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
                    <span class="legend-line map-road-swatch"></span>
                    <span>Main Road</span>
                </div>

                <div class="legend-item">
                    <span class="legend-line map-walkway-swatch"></span>
                    <span>Walkway</span>
                </div>

                <div class="legend-item">
                    <span class="legend-line map-stairs-swatch"></span>
                    <span>Open Stairs</span>
                </div>

                <div class="legend-item">
                    <span class="legend-line map-covered-stairs-swatch"></span>
                    <span>Covered Stairs</span>
                </div>

                <div class="legend-item" style="margin-top:12px; padding-top:12px; border-top:1px solid #e2e8f0;">
                    <span class="legend-line computed-route-swatch"></span>
                    <span>Computed Route</span>
                </div>
            `;
            return div;
        };
        legend.addTo(map);
    }
