    /* =========================================================
       CAMPUS EVENTS ON USER MAP
       - Building event: display above building, route to building.
       - Room event: display above parent building, route to specific room.
       - Landuse event: display above landuse, route to area.
    ========================================================= */
    function escapeEventHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function getFeatureCenterFromGeometry(geometry) {
        if (!geometry) return null;

        try {
            const tempLayer = L.geoJSON({
                type: 'Feature',
                geometry,
                properties: {}
            });

            const bounds = tempLayer.getBounds();
            if (!bounds || !bounds.isValid()) return null;

            return bounds.getCenter();
        } catch (error) {
            return null;
        }
    }

    function getBuildingCenterById(buildingId) {
        const building = (buildingRecords || []).find(
            item => Number(item.id) === Number(buildingId)
        );

        if (!building) return null;

        return getFeatureCenterFromGeometry(building.geometry);
    }

    function getLanduseCenterById(landuseId) {
        const landuse = (landuseRecords || []).find(
            item => Number(item.id) === Number(landuseId)
        );

        if (!landuse) return null;

        return getFeatureCenterFromGeometry(landuse.geometry);
    }

    function getEventPlaceText(event) {
        if (!event) return 'Campus';

        if (event.event_target_type === 'building') {
            return event.building_name || event.location_label || 'Building';
        }

        if (event.event_target_type === 'room') {
            const parts = [];

            if (event.building_name) parts.push(event.building_name);
            if (event.floor_label) parts.push(event.floor_label);

            const roomText = `${event.room_code ? event.room_code : ''}${event.room_name ? ' / ' + event.room_name : ''}`.trim();
            if (roomText) parts.push(roomText);

            return parts.length ? parts.join(' — ') : (event.location_label || 'Indoor Room');
        }

        if (event.event_target_type === 'landuse') {
            return event.landuse_name || event.location_label || 'Landuse Area';
        }

        return event.location_label || 'Campus';
    }

    function getEventMarkerLatLng(event) {
        if (!event) return null;

        if (event.display_type === 'building') {
            return getBuildingCenterById(event.display_id);
        }

        if (event.display_type === 'landuse') {
            return getLanduseCenterById(event.display_id);
        }

        return null;
    }

    function formatEventButtonText(event) {
        if (!event) return 'Route';

        if (event.route_type === 'room') return 'Route to Room';
        if (event.route_type === 'landuse') return 'Route to Area';

        return 'Route to Building';
    }

    function createCampusEventCardHtml(event) {
        const isNow = event.status === 'happening_now';

        const statusClass = isNow ? 'now' : 'upcoming';
        const cardClass = isNow ? 'now-card' : 'upcoming-card';
        const dotClass = isNow ? '' : 'upcoming-dot';
        const statusText = isNow ? 'Now' : 'Upcoming';

        const title = escapeEventHtml(event.title || 'Campus Event');
        const place = escapeEventHtml(getEventPlaceText(event));

        const startsAt = escapeEventHtml(event.starts_at_display || event.starts_at || 'No start time');
        const endsAt = event.ends_at_display
            ? `<br><span>Ends: ${escapeEventHtml(event.ends_at_display)}</span>`
            : '';

        const targetText =
            event.route_type === 'room'
                ? 'Room'
                : event.route_type === 'landuse'
                    ? 'Area'
                    : 'Building';

        return `
            <div class="campus-event-card ${cardClass}">
                <div class="campus-event-top">
                    <div class="campus-event-status ${statusClass}">
                        <span class="campus-event-mini-dot ${dotClass}"></span>
                        ${statusText}
                    </div>
                    <div class="campus-event-target">${targetText}</div>
                </div>

                <div class="campus-event-title">${title}</div>
                <div class="campus-event-place">${place}</div>

                <div class="campus-event-time">
                    <span>🕒</span>
                    <span>
                        Starts: ${startsAt}
                        ${endsAt}
                    </span>
                </div>

                <button type="button"
                        class="campus-event-route-btn"
                        onclick="routeToCampusEvent(${Number(event.id)})">
                    ${formatEventButtonText(event)}
                </button>
            </div>
        `;
    }

    function ensureCampusEventPanel() {
        if (window.WAYFINDING_GUEST_MODE === true) return null;

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
                <span class="campus-event-bell-icon" aria-hidden="true">
                    <svg class="wf-line-icon" viewBox="0 0 24 24">
                        <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z" />
                        <path class="wf-icon-accent" d="M10 21h4M9 4.2A4.8 4.8 0 0 1 12 3" />
                    </svg>
                </span>
                <span class="campus-event-bell-count is-zero" id="campus-event-bell-count">0</span>
            </button>

            <div class="campus-event-panel" id="campus-event-panel"></div>
        `;

        document.body.appendChild(wrap);

        document.addEventListener('click', function(event) {
            const currentWrap = document.getElementById('campus-event-notification-wrap');
            const panel = document.getElementById('campus-event-panel');

            if (!currentWrap || !panel || !panel.classList.contains('open')) return;
            if (currentWrap.contains(event.target)) return;

            closeCampusEventPanel();
        });

        return wrap;
    }

    function ensureCampusEventPanelContents() {
        const panel = document.getElementById('campus-event-panel');
        if (!panel || panel.dataset.hydrated === 'true') return panel;

        panel.innerHTML = `
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
        `;
        panel.dataset.hydrated = 'true';
        return panel;
    }

    function renderCampusEventPanelContents(activeEvents) {
        const panel = document.getElementById('campus-event-panel');
        if (!panel || panel.dataset.hydrated !== 'true') return;

        const list = document.getElementById('campus-event-list');
        const empty = document.getElementById('campus-event-empty');
        if (list) {
            list.style.display = activeEvents.length ? 'block' : 'none';
            list.innerHTML = activeEvents.length
                ? activeEvents.map(event => createCampusEventCardHtml(event)).join('')
                : '';
        }
        if (empty) empty.style.display = activeEvents.length ? 'none' : 'block';
    }

    function toggleCampusEventPanel() {
        const panel = document.getElementById('campus-event-panel');
        if (!panel) return;

        const shouldOpen = !panel.classList.contains('open');
        if (shouldOpen) {
            ensureCampusEventPanelContents();
            renderCampusEventPanelContents((campusEvents || []).filter(event => {
                return event && event.id && event.route_type && event.route_id;
            }));
        }
        panel.classList.toggle('open', shouldOpen);
    }

    function closeCampusEventPanel() {
        const panel = document.getElementById('campus-event-panel');
        if (!panel) return;

        panel.classList.remove('open');
    }

    function renderCampusEventMarkers() {
        /*
        |--------------------------------------------------------------------------
        | EVENT UI - ALWAYS VISIBLE NOTIFICATION BELL
        |--------------------------------------------------------------------------
        | The bell should stay visible even if there are no active events.
        | If no events exist, badge shows 0 and panel shows an empty message.
        |--------------------------------------------------------------------------
        */

        if (campusEventLayer) {
            campusEventLayer.clearLayers();
        }

        if (window.WAYFINDING_GUEST_MODE === true) return;

        const activeEvents = (campusEvents || []).filter(event => {
            return event && event.id && event.route_type && event.route_id;
        });

        const wrap = ensureCampusEventPanel();
        const count = document.getElementById('campus-event-bell-count');
        const pulse = document.getElementById('campus-event-bell-pulse');
        const bell = document.getElementById('campus-event-bell-btn');

        /*
        | Important:
        | Previous code used wrap.style.display = 'none' when no events.
        | That is why the notification icon disappeared.
        */
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

        renderCampusEventPanelContents(activeEvents);
    }

    function routeToCampusEvent(eventId) {
        closeCampusEventPanel?.();

        const event = (campusEvents || []).find(
            item => Number(item.id) === Number(eventId)
        );

        if (!event) {
            alert('Event not found.');
            return;
        }

        if (typeof closeBrowseOptionsModal === 'function') closeBrowseOptionsModal();
        if (typeof closeAiTransformPanel === 'function') closeAiTransformPanel();

        if (event.route_type === 'building') {
            if (!destinationTypeSelect || !destinationBuildingSelect) return;

            destinationTypeSelect.value = 'building';
            updateDestinationUi();

            destinationBuildingSelect.value = String(event.route_id || '');
            selectedDestinationBuildingId = event.route_id ? Number(event.route_id) : null;
            selectedDestinationLanduseId = null;
            selectedIndoorRoomFeature = null;

            updateRouteLabels();
            findRouteByDestination();
            return;
        }

        if (event.route_type === 'room') {
            if (!destinationTypeSelect || !destinationRoomSelect) return;

            destinationTypeSelect.value = 'room';
            updateDestinationUi();

            destinationRoomSelect.value = String(event.route_id || '');

            const room = (allIndoorRooms.features || []).find(
                feature => Number(feature.properties?.id) === Number(event.route_id)
            );

            if (room) {
                selectedIndoorRoomFeature = room;
                selectedDestinationBuildingId = Number(room.properties?.building_id || event.building_id);
            } else {
                selectedIndoorRoomFeature = null;
                selectedDestinationBuildingId = event.building_id ? Number(event.building_id) : null;
            }

            selectedDestinationLanduseId = null;

            updateRouteLabels();
            findRouteByDestination();
            return;
        }

        if (event.route_type === 'landuse') {
            if (!destinationTypeSelect || !destinationLanduseSelect) return;

            const eventLanduse = (landuseRecords || []).find(item => Number(item.id) === Number(event.route_id));
            if (eventLanduse && isDesignLanduse(eventLanduse)) {
                setRouteResultLabel('This event is on a design-only landuse. Routing is disabled for design areas.');
                return;
            }

            destinationTypeSelect.value = 'landuse';
            updateDestinationUi();

            destinationLanduseSelect.value = String(event.route_id || '');
            selectedDestinationLanduseId = event.route_id ? Number(event.route_id) : null;
            selectedDestinationBuildingId = null;
            selectedIndoorRoomFeature = null;

            updateRouteLabels();
            findRouteByDestination();
            return;
        }
    }
    let wayfindingDataInteractionsBound = false;
    let wayfindingDataLoadSequence = 0;
    let sharedDestinationRouteApplied = false;

    const EMPTY_WAYFINDING_FEATURE_COLLECTION = {
        type: 'FeatureCollection',
        features: []
    };

    function settledValue(results, index, fallback) {
        return results[index]?.status === 'fulfilled'
            ? results[index].value
            : fallback;
    }

    function failedDataLabels(definitions, results) {
        return definitions
            .filter((definition, index) => results[index]?.status === 'rejected')
            .map(definition => definition.label);
    }

    function staleDataLabels(definitions) {
        const staleUrls = window.__wayfindingStaleDataUrls || new Set();
        return definitions
            .filter(definition => staleUrls.has(definition.url))
            .map(definition => `${definition.label} (saved copy)`);
    }

    function bindWayfindingDataInteractionsOnce() {
        if (wayfindingDataInteractionsBound) return;
        wayfindingDataInteractionsBound = true;

        destinationBuildingSelect.addEventListener('change', function() {
            selectedDestinationBuildingId = this.value ? Number(this.value) : null;
            selectedDestinationLanduseId = null;
            selectedIndoorRoomFeature = null;
            selectedBuildingEntranceId = null;
            updateRouteLabels();
        });

        destinationLanduseSelect.addEventListener('change', function() {
            const selectedLanduse = (landuseRecords || []).find(item => Number(item.id) === Number(this.value));

            if (selectedLanduse && isDesignLanduse(selectedLanduse)) {
                alert('This landuse is Design only and cannot be used as a route destination.');
                this.value = '';
                selectedDestinationLanduseId = null;
                updateRouteLabels();
                setRouteResultLabel('Design landuse is display-only and not available for routing.');
                return;
            }

            selectedDestinationLanduseId = this.value ? Number(this.value) : null;
            selectedDestinationBuildingId = null;
            selectedIndoorRoomFeature = null;
            selectedBuildingEntranceId = null;
            updateRouteLabels();
        });

        destinationRoomSelect.addEventListener('change', function() {
            const roomId = Number(this.value);
            const room = (allIndoorRooms.features || []).find(
                f => Number(f.properties?.id) === Number(roomId)
            );

            if (room) {
                selectedIndoorRoomFeature = room;
                selectedDestinationBuildingId = Number(room.properties?.building_id);
                selectedDestinationLanduseId = null;

                if (roomBuildingFilterSelect) {
                    roomBuildingFilterSelect.value = String(selectedDestinationBuildingId || '');
                }
            } else {
                selectedIndoorRoomFeature = null;
                selectedDestinationBuildingId = null;
            }

            selectedBuildingEntranceId = null;
            renderBrowseRoomPicker();
            updateRouteLabels();
        });

        if (roomBuildingFilterSelect) {
            roomBuildingFilterSelect.addEventListener('change', function() {
                browseRoomSelectedFloor = 'all';
                selectedIndoorRoomFeature = null;
                selectedBuildingEntranceId = null;

                if (destinationRoomSelect) destinationRoomSelect.value = '';

                const buildingId = Number(this.value || 0);
                selectedDestinationBuildingId = buildingId || null;
                if (destinationBuildingSelect) {
                    destinationBuildingSelect.value = buildingId ? String(buildingId) : '';
                }

                renderBrowseRoomPicker();
                updateRouteLabels();
            });
        }

        if (roomOfficeSearchInput) {
            roomOfficeSearchInput.addEventListener('input', function() {
                renderBrowseRoomPicker();
            });
        }

        destinationTypeSelect.addEventListener('change', function() {
            if (this.value === 'building') {
                selectedDestinationLanduseId = null;
                selectedIndoorRoomFeature = null;
                selectedBuildingEntranceId = null;
            } else if (this.value === 'landuse') {
                selectedDestinationBuildingId = null;
                selectedIndoorRoomFeature = null;
                selectedBuildingEntranceId = null;
            } else if (this.value === 'room') {
                selectedDestinationLanduseId = null;
            }
            updateDestinationUi();
            updateRouteLabels();
        });
    }

    async function applySharedDestinationRoute() {
        if (sharedDestinationRouteApplied) return;

        const destination = window.WAYFINDING_SHARED_DESTINATION;
        const destinationId = Number(destination?.id || 0);

        if (!destinationId || !['building', 'room', 'landuse'].includes(destination?.type)) return;

        sharedDestinationRouteApplied = true;
        selectDefaultMode();

        if (destination.type === 'building') {
            setBrowseDestinationType('building');
            destinationBuildingSelect.value = String(destinationId);
            selectedDestinationBuildingId = destinationId;
            selectedDestinationLanduseId = null;
            selectedIndoorRoomFeature = null;
        } else if (destination.type === 'room') {
            const room = (allIndoorRooms.features || []).find(
                feature => Number(feature.properties?.id) === destinationId
            );

            if (!room) {
                sharedDestinationRouteApplied = false;
                window.showWayfindingToast?.(
                    'The shared room is currently unavailable.',
                    { kind: 'error' }
                );
                return;
            }

            setBrowseDestinationType('room');
            destinationRoomSelect.value = String(destinationId);
            selectedIndoorRoomFeature = room;
            selectedDestinationBuildingId = Number(room.properties?.building_id || 0);
            selectedDestinationLanduseId = null;
        } else {
            setBrowseDestinationType('landuse');
            destinationLanduseSelect.value = String(destinationId);
            selectedDestinationLanduseId = destinationId;
            selectedDestinationBuildingId = null;
            selectedIndoorRoomFeature = null;
        }

        updateDestinationUi();
        updateRouteLabels();
        await findRouteByDestination();
        window.showWayfindingToast?.(
            `Route created for ${destination.label || 'the shared destination'}.`,
            { kind: 'success' }
        );
    }

    function revealOutdoorMap() {
        const mapElement = document.getElementById('map');
        if (mapElement) mapElement.style.opacity = '1';
        setIndoorLoading(false);
    }

    async function loadAllData() {
        const loadSequence = ++wayfindingDataLoadSequence;
        setIndoorLoading(true);
        window.__wayfindingStaleDataUrls?.clear?.();
        window.WayfindingDataStatus?.loading(
            'Loading paths, buildings, entrances, and safety data first…'
        );

        const essentialDefinitions = [
            { key: 'buildings', label: 'Buildings', url: '/api/buildings', fallback: [] },
            { key: 'paths', label: 'Campus paths', url: '/api/paths', fallback: EMPTY_WAYFINDING_FEATURE_COLLECTION },
            { key: 'entries', label: 'Entry points', url: '/api/entry-points', fallback: [] },
            { key: 'entrances', label: 'Building entrances', url: '/api/building-entrances', fallback: [] },
            { key: 'hazards', label: 'Hazard updates', url: '/api/hazard-points', fallback: [] },
            { key: 'landuses', label: 'Campus areas', url: '/api/landuses', fallback: [] },
        ];

        const essentialResults = await Promise.allSettled(
            essentialDefinitions.map(definition => fetchJson(definition.url))
        );

        if (loadSequence !== wayfindingDataLoadSequence) return;

        const buildings = settledValue(essentialResults, 0, []);
        const paths = settledValue(
            essentialResults,
            1,
            EMPTY_WAYFINDING_FEATURE_COLLECTION
        );
        const entries = settledValue(essentialResults, 2, []);
        const entrances = settledValue(essentialResults, 3, []);
        const hazards = settledValue(essentialResults, 4, []);
        const landuses = settledValue(essentialResults, 5, []);

        buildingRecords = buildings || [];
        landuseRecords = (landuses || []).map(landuse => ({
            ...landuse,
            type: landuse.type ?? landuse.landuse_type ?? landuse.properties?.type ?? null,
            landuse_type: landuse.landuse_type ?? landuse.type ?? landuse.properties?.landuse_type ?? null,
            properties: {
                ...(landuse.properties || {}),
                type: landuse.properties?.type ?? landuse.type ?? landuse.landuse_type ?? null,
                landuse_type: landuse.properties?.landuse_type ?? landuse.landuse_type ?? landuse.type ?? null,
            }
        }));
        pathGeojson = paths || EMPTY_WAYFINDING_FEATURE_COLLECTION;
        entryPoints = entries || [];
        buildingEntrances = entrances || [];
        hazardPoints = hazards || [];

        buildOutdoorGraph(pathGeojson);
        drawHazardMarkers();
        renderBuildings();
        renderLanduses();
        renderPaths();

        defaultEntrySelect.innerHTML = '<option value="">Default Start</option>';
        entryPoints.forEach(point => {
            const nodeKey = nearestNodeKey(Number(point.latitude), Number(point.longitude));
            defaultEntrySelect.innerHTML += `
                <option
                    value="${nodeKey || ''}"
                    data-entry-id="${point.id}"
                    data-name="${point.name || ''}"
                    data-latitude="${point.latitude ?? ''}"
                    data-longitude="${point.longitude ?? ''}">
                    ${point.name || 'Entry Point'}
                </option>
            `;
        });

        destinationBuildingSelect.innerHTML = '<option value="">Select Destination Building</option>';
        buildingRecords.forEach(building => {
            destinationBuildingSelect.innerHTML += `
                <option value="${building.id}">
                    ${building.name || 'Building'}
                </option>
            `;
        });

        populateLanduseSelect();
        if (destinationRoomSelect) {
            destinationRoomSelect.innerHTML = '<option value="">Loading rooms and offices…</option>';
        }

        bindWayfindingDataInteractionsOnce();
        updateDestinationUi();
        updateShadows();
        updateRouteLabels();
        setActiveStartModeButton('default');
        setRouteResultLabel('Campus outdoor map ready.');
        revealOutdoorMap();

        const failedEssential = failedDataLabels(essentialDefinitions, essentialResults);
        const staleEssential = staleDataLabels(essentialDefinitions);

        if (failedEssential.length || staleEssential.length) {
            window.WayfindingDataStatus?.partial(
                [...failedEssential, ...staleEssential],
                {
                    critical: failedEssential.includes('Campus paths'),
                    usingCache: staleEssential.length > 0,
                }
            );
        } else {
            window.WayfindingDataStatus?.coreReady();
        }

        const optionalDefinitions = [
            { key: 'indoorMaps', label: 'Indoor maps', url: '/api/indoor-maps', fallback: [] },
            { key: 'indoorRooms', label: 'Indoor rooms', url: '/api/indoor-rooms', fallback: EMPTY_WAYFINDING_FEATURE_COLLECTION },
            { key: 'buildingEntranceLinks', label: 'Entrance links', url: '/api/building-entrance-links', fallback: [] },
            { key: 'events', label: 'Campus events', url: '/api/campus-events', fallback: [] },
        ];

        const optionalResults = await Promise.allSettled(
            optionalDefinitions.map(definition => fetchJson(definition.url))
        );

        if (loadSequence !== wayfindingDataLoadSequence) return;

        allIndoorMaps = (settledValue(optionalResults, 0, []) || [])
            .map(normalizeIndoorMapRecord);
        allIndoorRooms = normalizeFeatureCollection(
            settledValue(optionalResults, 1, EMPTY_WAYFINDING_FEATURE_COLLECTION)
        );
        allIndoorPaths = EMPTY_WAYFINDING_FEATURE_COLLECTION;
        allIndoorEntrances = EMPTY_WAYFINDING_FEATURE_COLLECTION;
        allBuildingEntranceLinks = settledValue(optionalResults, 2, []) || [];
        allIndoorStairsLinks = [];
        campusEvents = settledValue(optionalResults, 3, []) || [];

        populateDestinationRoomSelect();
        renderCampusEventMarkers();
        updateDestinationUi();
        updateRouteLabels();
        if (indoorMap) indoorMap.invalidateSize();

        await applySharedDestinationRoute();

        const failedOptional = failedDataLabels(optionalDefinitions, optionalResults);
        const staleOptional = staleDataLabels(optionalDefinitions);
        const allUnavailable = [...failedEssential, ...failedOptional];
        const allStale = [...staleEssential, ...staleOptional];
        const hasActiveRoute = window.WayfindingNavigationUi
            ?.getState?.()
            ?.hasRoute === true;

        if (allUnavailable.length || allStale.length) {
            window.WayfindingDataStatus?.partial(
                [...allUnavailable, ...allStale],
                {
                    critical: allUnavailable.includes('Campus paths'),
                    usingCache: allStale.length > 0,
                }
            );
            if (!hasActiveRoute) {
                setRouteResultLabel(
                    allUnavailable.includes('Campus paths')
                        ? 'Campus paths are unavailable. Retry data loading before creating a route.'
                        : 'Campus map ready with limited optional data.'
                );
            }
        } else {
            window.WayfindingDataStatus?.ready();
            if (!hasActiveRoute) {
                setRouteResultLabel('Ready');
            }
        }
    }

    window.retryWayfindingData = function () {
        return loadAllData().catch(error => {
            console.error('Wayfinding data retry failed:', error);
            revealOutdoorMap();
            window.WayfindingDataStatus?.partial(
                ['Campus navigation data'],
                { critical: true, usingCache: false }
            );
            window.showWayfindingToast?.(
                'Campus data could not be refreshed. Check your connection and retry.',
                { kind: 'error' }
            );
        });
    };

    closeIndoorPanel.addEventListener('click', closeIndoorPanelFn);
    indoorBackdrop.addEventListener('click', closeIndoorPanelFn);

    indoorFloorSelect.addEventListener('change', function() {
        if (this.value === '') {
            currentIndoorFloor = null;
            return;
        }

        currentIndoorFloor = Number(this.value);
        renderIndoorRoomList();
        renderIndoorFloor();

        if (lastIndoorRoutePackage) {
            redrawPersistentIndoorRouteForCurrentFloor();
        }

        scheduleIndoorViewportFit({
            reason: 'floor-select',
            preferRoute: Boolean(lastIndoorRoutePackage)
        });
    });

    indoorRoomSearch.addEventListener('input', function() {
        renderIndoorRoomList();
    });

    if (destinationSearchInput) {
        destinationSearchInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchTextDestination();
            }
        });
    }

    /*
     * Campus geometry is part of the initial navigation core. Keep its startup
     * independent from the optional search/voice assistant chunk.
     */
    loadAllData().catch(error => {
        console.error(error);
        window.WayfindingDataStatus?.partial(
            ['Campus navigation data'],
            { critical: true, usingCache: false }
        );
        window.showWayfindingToast?.(
            'Campus map data could not be loaded. Check your connection, then use Retry.',
            { kind: 'error' }
        );
        revealOutdoorMap();
    });
