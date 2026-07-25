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

        document.addEventListener('click', function(event) {
            const currentWrap = document.getElementById('campus-event-notification-wrap');
            const panel = document.getElementById('campus-event-panel');

            if (!currentWrap || !panel || !panel.classList.contains('open')) return;
            if (currentWrap.contains(event.target)) return;

            closeCampusEventPanel();
        });

        return wrap;
    }

    function toggleCampusEventPanel() {
        const panel = document.getElementById('campus-event-panel');
        if (!panel) return;

        panel.classList.toggle('open');
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

        const activeEvents = (campusEvents || []).filter(event => {
            return event && event.id && event.route_type && event.route_id;
        });

        const wrap = ensureCampusEventPanel();
        const list = document.getElementById('campus-event-list');
        const empty = document.getElementById('campus-event-empty');
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

        if (list) {
            list.style.display = activeEvents.length ? 'block' : 'none';
            list.innerHTML = activeEvents.length
                ? activeEvents.map(event => createCampusEventCardHtml(event)).join('')
                : '';
        }

        if (empty) {
            empty.style.display = activeEvents.length ? 'none' : 'block';
        }
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
    async function loadAllData() {
        setIndoorLoading(true);

        const [
            buildings,
            paths,
            entries,
            entrances,
            hazards,
            landuses,
            indoorMaps,
            indoorRooms,
            indoorPaths,
            indoorEntrances,
            buildingEntranceLinks,
            indoorStairsLinks,
            events
        ] = await Promise.all([
            fetchJson('/api/buildings'),
            fetchJson('/api/paths'),
            fetchJson('/api/entry-points'),
            fetchJson('/api/building-entrances'),
            fetchJson('/api/hazard-points'),
            fetchJson('/api/landuses'),
            fetchJson('/api/indoor-maps'),
            fetchJson('/api/indoor-rooms'),
            fetchJson('/api/indoor-paths'),
            fetchJson('/api/indoor-entrances'),
            fetchJson('/api/building-entrance-links'),
            fetchJson('/api/indoor-stairs-links'),
            fetchJson('/api/campus-events')
        ]);

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
        campusEvents = events || [];
        pathGeojson = paths || {
            type: 'FeatureCollection',
            features: []
        };
        entryPoints = entries || [];
        buildingEntrances = entrances || [];
        hazardPoints = hazards || [];

        allIndoorMaps = (indoorMaps || []).map(normalizeIndoorMapRecord);
        allIndoorRooms = normalizeFeatureCollection(indoorRooms);
        allIndoorPaths = normalizeFeatureCollection(indoorPaths);
        allIndoorEntrances = normalizeFeatureCollection(indoorEntrances);
        allBuildingEntranceLinks = buildingEntranceLinks || [];
        allIndoorStairsLinks = indoorStairsLinks || [];

        buildOutdoorGraph(pathGeojson);
        drawHazardMarkers();
        renderBuildings();
        renderLanduses();
        renderPaths();
        renderCampusEventMarkers();

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
        populateDestinationRoomSelect();

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

        ensureIndoorMap();
        updateDestinationUi();
        updateShadows();
        updateRouteLabels();
        setActiveStartModeButton('default');
        setRouteResultLabel('Ready');

        setTimeout(() => {
            document.getElementById('map').style.opacity = '1';
            if (indoorMap) indoorMap.invalidateSize();
            setIndoorLoading(false);
        }, 150);
    }

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
