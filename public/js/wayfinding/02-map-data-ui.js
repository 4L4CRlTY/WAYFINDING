    /*
    |--------------------------------------------------------------------------
    | EVENT / DESTINATION ROUTE START MODE FIX
    |--------------------------------------------------------------------------
    | If the user taps Route to Room from Campus Events, the route should not
    | always force Default Start. It now respects the currently selected start
    | mode: Default Route, Use GPS, or Pick Path.
    */
    let pendingRouteAfterPickPath = false;

    const routeModeLabel = document.getElementById('route-mode-label');
    const routeSourceLabel = document.getElementById('route-source-label');
    const routeStartLabel = document.getElementById('route-start-label');
    const routeDestinationTypeLabel = document.getElementById('route-destination-type-label');
    const routeEndLabel = document.getElementById('route-end-label');
    const routeGatewayLabel = document.getElementById('route-gateway-label');
    const routeResultLabel = document.getElementById('route-result-label');

    const indoorPanel = document.getElementById('indoorPanel');
    const indoorBackdrop = document.getElementById('indoorBackdrop');
    const indoorTitle = document.getElementById('indoorTitle');
    const indoorSubtitle = document.getElementById('indoorSubtitle');
    const indoorFloorSelect = document.getElementById('indoorFloorSelect');
    const indoorRoomSearch = document.getElementById('indoorRoomSearch');
    const roomList = document.getElementById('roomList');
    const indoorFooter = document.getElementById('indoorFooter');
    const closeIndoorPanel = document.getElementById('closeIndoorPanel');
    const indoorLoading = document.getElementById('indoorLoading');

    function setIndoorLoading(show = true) {
        indoorLoading.style.display = show ? 'flex' : 'none';
    }

    function safeSetText(element, text) {
        if (element) {
            element.textContent = text;
        }
    }

    function hasIndoorFloorValue(value) {
        return value !== null && value !== undefined && value !== '';
    }

    function formatIndoorFloorLabel(floorNumber, floorLabel = null) {
        if (floorLabel !== null && floorLabel !== undefined && String(floorLabel).trim() !== '') {
            return String(floorLabel);
        }

        if (!hasIndoorFloorValue(floorNumber)) {
            return '-';
        }

        const floor = Number(floorNumber);

        if (floor === 0) {
            return '0F / Basement';
        }

        return `Floor ${floor}`;
    }

    function setRouteResultLabel(text) {
        safeSetText(routeResultLabel, text);
    }

    function getDestinationType() {
        return destinationTypeSelect?.value || 'building';
    }

    function updateDestinationUi() {
        const type = getDestinationType();

        if (buildingDestinationWrap) {
            buildingDestinationWrap.style.display = type === 'building' ? 'block' : 'none';
        }

        if (landuseDestinationWrap) {
            landuseDestinationWrap.style.display = type === 'landuse' ? 'block' : 'none';
        }

        if (roomDestinationWrap) {
            roomDestinationWrap.style.display = type === 'room' ? 'block' : 'none';
        }

        document.querySelectorAll('.browse-type-card').forEach(card => {
            card.classList.toggle('active', card.dataset.destinationType === type);
        });

        if (type === 'room') {
            syncRoomBuildingFilterFromSelectedRoomOrBuilding();
            renderBrowseRoomPicker();
        }

        if (routeDestinationTypeLabel) {
            routeDestinationTypeLabel.textContent =
                type === 'room' ?
                'Room / Office' :
                type === 'landuse' ?
                'Landuse Area' :
                'Building';
        }
    }

    function setBrowseDestinationType(type) {
        if (!destinationTypeSelect) return;

        destinationTypeSelect.value = type;

        if (type === 'building') {
            selectedDestinationLanduseId = null;
            selectedIndoorRoomFeature = null;
            selectedBuildingEntranceId = null;
            if (destinationRoomSelect) destinationRoomSelect.value = '';
            if (destinationLanduseSelect) destinationLanduseSelect.value = '';
        } else if (type === 'landuse') {
            selectedDestinationBuildingId = null;
            selectedIndoorRoomFeature = null;
            selectedBuildingEntranceId = null;
            if (destinationBuildingSelect) destinationBuildingSelect.value = '';
            if (destinationRoomSelect) destinationRoomSelect.value = '';
        } else if (type === 'room') {
            selectedDestinationLanduseId = null;
            selectedBuildingEntranceId = null;
            if (destinationLanduseSelect) destinationLanduseSelect.value = '';
        }

        updateDestinationUi();
        updateRouteLabels();
    }

    function normalizeBrowseSearchText(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, ' ')
            .trim();
    }

    function escapeBrowseHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function getRoomBuildingId(room) {
        return Number(room?.properties?.building_id || 0);
    }

    function getRoomFloorNumber(room) {
        const raw = room?.properties?.floor_number;
        if (raw === null || raw === undefined || raw === '') return null;
        const floor = Number(raw);
        return Number.isFinite(floor) ? floor : null;
    }

    function getRoomDisplayName(room) {
        const p = room?.properties || {};
        return p.name || p.room_code || 'Room / Office';
    }

    function getRoomDisplayCode(room) {
        const p = room?.properties || {};
        return p.room_code ? String(p.room_code) : '';
    }

    function getFilteredBrowseRooms() {
        let rooms = [...(allIndoorRooms.features || [])];
        const selectedBuildingId = Number(roomBuildingFilterSelect?.value || 0);
        const searchText = normalizeBrowseSearchText(roomOfficeSearchInput?.value || '');

        if (selectedBuildingId) {
            rooms = rooms.filter(room => getRoomBuildingId(room) === selectedBuildingId);
        }

        if (browseRoomSelectedFloor !== 'all') {
            const selectedFloor = Number(browseRoomSelectedFloor);
            rooms = rooms.filter(room => getRoomFloorNumber(room) === selectedFloor);
        }

        if (searchText) {
            rooms = rooms.filter(room => {
                const p = room.properties || {};
                const haystack = normalizeBrowseSearchText([
                    p.name,
                    p.room_code,
                    p.type,
                    p.building_name,
                    p.floor_label,
                    formatIndoorFloorLabel(p.floor_number, p.floor_label)
                ].filter(Boolean).join(' '));

                return haystack.includes(searchText);
            });
        }

        return rooms.sort((a, b) => {
            const af = getRoomFloorNumber(a) ?? 999;
            const bf = getRoomFloorNumber(b) ?? 999;
            if (af !== bf) return af - bf;

            const ab = String(a.properties?.building_name || '').localeCompare(String(b.properties?.building_name || ''));
            if (ab !== 0) return ab;

            return String(getRoomDisplayName(a)).localeCompare(String(getRoomDisplayName(b)));
        });
    }

    function populateRoomBuildingFilterSelect() {
        if (!roomBuildingFilterSelect) return;

        const existingValue = roomBuildingFilterSelect.value;
        const buildingMap = new Map();

        (allIndoorRooms.features || []).forEach(room => {
            const p = room.properties || {};
            const buildingId = Number(p.building_id || 0);
            if (!buildingId) return;

            buildingMap.set(buildingId, p.building_name || getBuildingNameById(buildingId) || `Building ${buildingId}`);
        });

        const buildings = Array.from(buildingMap.entries())
            .sort((a, b) => String(a[1]).localeCompare(String(b[1])));

        roomBuildingFilterSelect.innerHTML = '<option value="">All Buildings</option>';
        buildings.forEach(([id, name]) => {
            roomBuildingFilterSelect.innerHTML += `<option value="${id}">${escapeBrowseHtml(name)}</option>`;
        });

        if (existingValue && Array.from(roomBuildingFilterSelect.options).some(opt => opt.value === existingValue)) {
            roomBuildingFilterSelect.value = existingValue;
        }
    }

    function syncRoomBuildingFilterFromSelectedRoomOrBuilding() {
        if (!roomBuildingFilterSelect) return;

        if (selectedIndoorRoomFeature?.properties?.building_id) {
            roomBuildingFilterSelect.value = String(selectedIndoorRoomFeature.properties.building_id);
            return;
        }

        if (selectedDestinationBuildingId) {
            const exists = Array.from(roomBuildingFilterSelect.options || []).some(
                opt => Number(opt.value) === Number(selectedDestinationBuildingId)
            );
            if (exists) roomBuildingFilterSelect.value = String(selectedDestinationBuildingId);
        }
    }

    function renderRoomFloorFilterChips() {
        if (!roomFloorFilterChips) return;

        const selectedBuildingId = Number(roomBuildingFilterSelect?.value || 0);
        let rooms = [...(allIndoorRooms.features || [])];

        if (selectedBuildingId) {
            rooms = rooms.filter(room => getRoomBuildingId(room) === selectedBuildingId);
        }

        const floorMap = new Map();
        rooms.forEach(room => {
            const floor = getRoomFloorNumber(room);
            if (floor === null) return;
            floorMap.set(floor, formatIndoorFloorLabel(floor, room.properties?.floor_label));
        });

        const floors = Array.from(floorMap.entries()).sort((a, b) => a[0] - b[0]);

        if (browseRoomSelectedFloor !== 'all' && !floorMap.has(Number(browseRoomSelectedFloor))) {
            browseRoomSelectedFloor = 'all';
        }

        let html = `
            <button type="button" class="room-floor-chip ${browseRoomSelectedFloor === 'all' ? 'active' : ''}" data-floor="all">
                All Floors
            </button>
        `;

        floors.forEach(([floor, label]) => {
            html += `
                <button type="button" class="room-floor-chip ${Number(browseRoomSelectedFloor) === Number(floor) ? 'active' : ''}" data-floor="${floor}">
                    ${escapeBrowseHtml(label)}
                </button>
            `;
        });

        roomFloorFilterChips.innerHTML = html;

        roomFloorFilterChips.querySelectorAll('.room-floor-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                browseRoomSelectedFloor = this.dataset.floor || 'all';
                renderBrowseRoomPicker();
            });
        });
    }

    function selectBrowseRoom(roomId) {
        const room = (allIndoorRooms.features || []).find(
            f => Number(f.properties?.id) === Number(roomId)
        );

        if (!room) return;

        selectedIndoorRoomFeature = room;
        selectedDestinationBuildingId = Number(room.properties?.building_id || 0) || null;
        selectedDestinationLanduseId = null;
        selectedBuildingEntranceId = null;

        if (destinationRoomSelect) {
            destinationRoomSelect.value = String(roomId);
        }

        if (roomBuildingFilterSelect && selectedDestinationBuildingId) {
            roomBuildingFilterSelect.value = String(selectedDestinationBuildingId);
        }

        if (destinationBuildingSelect && selectedDestinationBuildingId) {
            destinationBuildingSelect.value = String(selectedDestinationBuildingId);
        }

        renderBrowseRoomPicker();
        updateRouteLabels();
    }

    function renderBrowseRoomPicker() {
        populateRoomBuildingFilterSelect();
        renderRoomFloorFilterChips();

        if (!roomOfficeResultsList) return;

        const rooms = getFilteredBrowseRooms();
        const selectedId = Number(destinationRoomSelect?.value || selectedIndoorRoomFeature?.properties?.id || 0);

        if (roomResultCount) {
            roomResultCount.textContent = `${rooms.length} ${rooms.length === 1 ? 'room' : 'rooms'}`;
        }

        if (!rooms.length) {
            roomOfficeResultsList.innerHTML = `
                <div class="room-empty-state">
                    <div class="room-empty-icon">🔎</div>
                    <div class="room-empty-title">No room or office found</div>
                    <div class="room-empty-text">Try another building, floor, or search keyword.</div>
                </div>
            `;
            return;
        }

        const grouped = new Map();
        rooms.forEach(room => {
            const floor = getRoomFloorNumber(room);
            const floorLabel = formatIndoorFloorLabel(floor, room.properties?.floor_label);
            if (!grouped.has(floorLabel)) grouped.set(floorLabel, []);
            grouped.get(floorLabel).push(room);
        });

        let html = '';
        grouped.forEach((floorRooms, floorLabel) => {
            html += `
                <div class="room-floor-group">
                    <div class="room-floor-group-title">
                        <span>${escapeBrowseHtml(floorLabel)}</span>
                        <small>${floorRooms.length} ${floorRooms.length === 1 ? 'choice' : 'choices'}</small>
                    </div>
            `;

            floorRooms.slice(0, 80).forEach(room => {
                const p = room.properties || {};
                const roomId = Number(p.id);
                const active = selectedId === roomId;
                const code = getRoomDisplayCode(room);
                const type = p.type ? String(p.type).replaceAll('_', ' ') : 'Room / Office';
                const buildingName = p.building_name || getBuildingNameById(p.building_id) || 'Building';

                html += `
                    <button type="button" class="room-office-card ${active ? 'active' : ''}" onclick="selectBrowseRoom(${roomId})">
                        <span class="room-office-card-icon">${active ? '✅' : '🚪'}</span>
                        <span class="room-office-card-main">
                            <strong>${escapeBrowseHtml(getRoomDisplayName(room))}</strong>
                            <small>${escapeBrowseHtml([code, type].filter(Boolean).join(' · '))}</small>
                            <em>${escapeBrowseHtml(buildingName)}</em>
                        </span>
                    </button>
                `;
            });

            html += '</div>';
        });

        roomOfficeResultsList.innerHTML = html;
    }

    function updateRouteLabels() {
        safeSetText(routeModeLabel, placingStartMode ? 'Pick On Path Mode' : 'Idle');
        safeSetText(routeSourceLabel, startSourceType ? startSourceType.replaceAll('_', ' ') : 'None');
        safeSetText(routeStartLabel, startNodeKey || 'Not selected');

        const destinationType = getDestinationType();

        safeSetText(
            routeDestinationTypeLabel,
            destinationType === 'room' ?
            'Room / Office' :
            destinationType === 'landuse' ?
            'Landuse Area' :
            'Building'
        );

        if (destinationType === 'building') {
            const selectedText = destinationBuildingSelect?.selectedOptions?.[0]?.text || 'Not selected';
            safeSetText(routeEndLabel, selectedDestinationBuildingId ? selectedText : 'Not selected');
        } else if (destinationType === 'landuse') {
            const selectedText = destinationLanduseSelect?.selectedOptions?.[0]?.text || 'Not selected';
            safeSetText(routeEndLabel, selectedDestinationLanduseId ? selectedText : 'Not selected');
        } else {
            const roomText = destinationRoomSelect?.selectedOptions?.[0]?.text || 'Not selected';
            safeSetText(routeEndLabel, roomText);
        }

        const selectedEntrance = buildingEntrances.find(e => Number(e.id) === Number(selectedBuildingEntranceId));
        safeSetText(routeGatewayLabel, selectedEntrance ? (selectedEntrance.name || 'Entrance') : 'None');
    }

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
        return '#' + [r, g, b].map(v => {
            v = Math.max(0, Math.min(255, Math.round(v)));
            return v.toString(16).padStart(2, '0');
        }).join('');
    }

    function darkenColor(hex, percent) {
        const {
            r,
            g,
            b
        } = hexToRgb(hex);
        return rgbToHex(r * (1 - percent), g * (1 - percent), b * (1 - percent));
    }

    /*
    |--------------------------------------------------------------------------
    | 3-LAYER LIGHTWEIGHT FAKE 3D BUILDING HELPERS
    |--------------------------------------------------------------------------
    | One global CSS style only. Each building SVG path gets color variables.
    | Result: visible fake 3D, but much lighter than one style tag per building.
    */
    function ensureBuildingDepthGlobalStyle() {
        if (document.getElementById('building-depth-global-style')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'building-depth-global-style';
        style.textContent = `
            .fake-3d-building {
                filter:
                    drop-shadow(1px 1px 0 var(--building-side-1, rgba(15, 23, 42, 0.28)))
                    drop-shadow(2px 2px 0 var(--building-side-2, rgba(15, 23, 42, 0.20)))
                    drop-shadow(3px 3px 1px var(--building-side-3, rgba(15, 23, 42, 0.14)));
                transform: none !important;
                transition: fill-opacity 0.12s ease, stroke-width 0.12s ease, filter 0.12s ease !important;
                cursor: pointer;
                vector-effect: non-scaling-stroke;
                shape-rendering: geometricPrecision;
            }

            .fake-3d-building:hover {
                filter:
                    drop-shadow(1px 1px 0 var(--building-side-1, rgba(15, 23, 42, 0.28)))
                    drop-shadow(2px 2px 0 var(--building-side-2, rgba(15, 23, 42, 0.20)))
                    drop-shadow(3px 3px 1px var(--building-side-3, rgba(15, 23, 42, 0.14)));
                transform: none !important;
                stroke: var(--building-border-color, #1f2937) !important;
                stroke-width: 2 !important;
                fill-opacity: 0.96 !important;
            }

            body.many-buildings-mode .fake-3d-building,
            body.many-buildings-mode .fake-3d-building:hover {
                filter:
                    drop-shadow(1px 1px 0 var(--building-side-1, rgba(15, 23, 42, 0.26)))
                    drop-shadow(2px 2px 0 var(--building-side-2, rgba(15, 23, 42, 0.18)))
                    drop-shadow(3px 3px 1px var(--building-side-3, rgba(15, 23, 42, 0.10))) !important;
                transition: none !important;
            }

            body.map-zooming .fake-3d-building,
            body.map-zooming .fake-3d-building:hover {
                filter:
                    drop-shadow(1px 1px 0 var(--building-side-1, rgba(15, 23, 42, 0.24)))
                    drop-shadow(2px 2px 0 var(--building-side-2, rgba(15, 23, 42, 0.16)))
                    drop-shadow(3px 3px 1px var(--building-side-3, rgba(15, 23, 42, 0.08))) !important;
                transition: none !important;
                transform: none !important;
            }

            @media (hover: none), (max-width: 768px) {
                .fake-3d-building,
                .fake-3d-building:hover,
                body.map-moving .fake-3d-building,
                body.map-moving .fake-3d-building:hover {
                    filter:
                        drop-shadow(var(--mobile-side-1, 1px) var(--mobile-side-1, 1px) 0 var(--building-side-1, rgba(15, 23, 42, 0.26)))
                        drop-shadow(var(--mobile-side-2, 2px) var(--mobile-side-2, 2px) 0 var(--building-side-2, rgba(15, 23, 42, 0.18)))
                        drop-shadow(var(--mobile-side-3, 3px) var(--mobile-side-3, 3px) 1px var(--building-side-3, rgba(15, 23, 42, 0.10))) !important;
                    transform: none !important;
                    transition: none !important;
                    stroke-width: var(--mobile-edge-width, 1.35) !important;
                    vector-effect: non-scaling-stroke !important;
                }

                body.many-buildings-mode .fake-3d-building,
                body.many-buildings-mode .fake-3d-building:hover,
                body.map-zooming .fake-3d-building,
                body.map-zooming .fake-3d-building:hover {
                    filter:
                        drop-shadow(var(--mobile-side-1, 1px) var(--mobile-side-1, 1px) 0 var(--building-side-1, rgba(15, 23, 42, 0.24)))
                        drop-shadow(var(--mobile-side-2, 2px) var(--mobile-side-2, 2px) 0 var(--building-side-2, rgba(15, 23, 42, 0.15)))
                        drop-shadow(var(--mobile-side-3, 3px) var(--mobile-side-3, 3px) 1px var(--building-side-3, rgba(15, 23, 42, 0.08))) !important;
                }
            }
        `;

        document.head.appendChild(style);
    }

    function addDynamicBuildingStyle(className, baseColor) {
        ensureBuildingDepthGlobalStyle();
    }

    function applyBuildingDepthVariables(geojsonLayer, baseColor) {
        ensureBuildingDepthGlobalStyle();

        const borderColor = darkenColor(baseColor, 0.28);
        const sideColor1 = darkenColor(baseColor, 0.18);
        const sideColor2 = darkenColor(baseColor, 0.32);
        const sideColor3 = darkenColor(baseColor, 0.46);

        function applyToPath(pathLayer) {
            const el = pathLayer?.getElement ? pathLayer.getElement() : null;
            if (!el) return;

            el.style.setProperty('--building-border-color', borderColor);
            el.style.setProperty('--building-side-1', sideColor1);
            el.style.setProperty('--building-side-2', sideColor2);
            el.style.setProperty('--building-side-3', sideColor3);
        }

        if (geojsonLayer && typeof geojsonLayer.eachLayer === 'function') {
            geojsonLayer.eachLayer(layer => {
                applyToPath(layer);
                if (layer && typeof layer.once === 'function') {
                    layer.once('add', () => applyToPath(layer));
                }
            });
        }
    }

    function updateBuildingPerformanceMode() {
        const count = Array.isArray(buildingRecords) ? buildingRecords.length : 0;
        const mobileView = window.matchMedia('(hover: none), (max-width: 768px)').matches;
        const manyBuildings = count >= 24;

        document.body.classList.toggle('many-buildings-mode', manyBuildings || mobileView);
    }

    function createDivIcon(html, size = [22, 22], anchor = [11, 11]) {
        return L.divIcon({
            html,
            className: '',
            iconSize: size,
            iconAnchor: anchor
        });
    }



    function getHazardSeverityClass(severity) {
        severity = Number(severity || 1);

        if (severity >= 3) return 'severity-3';
        if (severity === 2) return 'severity-2';

        return 'severity-1';
    }

    function getHazardSeverityColor(severity) {
        severity = Number(severity || 1);

        if (severity >= 3) return '#dc2626';
        if (severity === 2) return '#facc15';

        return '#ffffff';
    }

    function getHazardSymbolByType(warningType) {
        const type = String(warningType || '').toLowerCase();

        if (type.includes('construction')) return '!';
        if (type.includes('broken')) return '!';
        if (type.includes('slippery')) return '!';
        if (type.includes('flood')) return '!';
        if (type.includes('stairs')) return '!';
        if (type.includes('caution')) return '!';

        return '!';
    }

    function createHazardIcon(hazard) {
        const severity = Number(hazard?.severity_level || 1);
        const severityClass = getHazardSeverityClass(severity);
        const symbol = getHazardSymbolByType(hazard?.warning_type);

        return L.divIcon({
            className: '',
            html: `
                <div class="hazard-pin-wrap">
                    <div class="hazard-pin ${severityClass}">
                        <span class="hazard-pin-symbol">${symbol}</span>
                    </div>
                </div>
            `,
            iconSize: [34, 42],
            iconAnchor: [17, 38],
            popupAnchor: [0, -32]
        });
    }

function formatCoordKey(lng, lat) {
        return `${Number(lng).toFixed(12)},${Number(lat).toFixed(12)}`;
    }

    function parseCoordKey(key) {
        const [lng, lat] = key.split(',').map(Number);
        return [lat, lng];
    }

    function edgeKey(a, b) {
        return [a, b].sort().join('__');
    }

    function nearestNodeKey(lat, lng) {
        let bestKey = null;
        let bestDistance = Infinity;

        Object.entries(outdoorNodeCoords).forEach(([key, coord]) => {
            const d = map.distance([lat, lng], coord);
            if (d < bestDistance) {
                bestDistance = d;
                bestKey = key;
            }
        });

        return bestKey;
    }

    function getBuildingNameById(buildingId) {
        const option = Array.from(destinationBuildingSelect.options).find(
            opt => Number(opt.value) === Number(buildingId)
        );
        return option ? option.textContent.trim() : 'Building';
    }

    function getLanduseNameById(landuseId) {
        const option = Array.from(destinationLanduseSelect?.options || []).find(
            opt => Number(opt.value) === Number(landuseId)
        );
        return option ? option.textContent.trim() : 'Landuse Area';
    }

    function getLanduseCenter(landuse) {
        if (!landuse || !landuse.geometry) return null;

        try {
            const layer = L.geoJSON({
                type: 'Feature',
                geometry: landuse.geometry,
                properties: landuse.properties || {}
            });

            const bounds = layer.getBounds();
            if (!bounds.isValid()) return null;

            return bounds.getCenter();
        } catch (e) {
            return null;
        }
    }

    function getLanduseNearestNodeKey(landuse) {
        if (!landuse || !landuse.geometry) return null;
        if (isDesignLanduse(landuse)) return null;

        const center = getLanduseCenter(landuse);
        if (!center) return null;

        return nearestNodeKey(center.lat, center.lng);
    }

    function isOpenFieldLanduse(landuse) {
        const rawName = String(landuse?.name || landuse?.properties?.name || '').toLowerCase().trim();
        const p = landuse?.properties || {};

        return rawName.includes('field') ||
            rawName.includes('garden') ||
            rawName.includes('park') ||
            rawName.includes('open') ||
            rawName.includes('grass') ||
            String(p.type || '').toLowerCase().includes('field') ||
            String(p.category || '').toLowerCase().includes('field');
    }

    function isMultipurposeCourtLanduse(landuse) {
        const rawName = String(landuse?.name || landuse?.properties?.name || '').toLowerCase().trim();
        const p = landuse?.properties || {};

        return rawName.includes('court') ||
            rawName.includes('multipurpose') ||
            rawName.includes('multi purpose') ||
            String(p.type || '').toLowerCase().includes('court') ||
            String(p.category || '').toLowerCase().includes('court');
    }

    function getLandusePopupSubtitle(landuse) {
        if (isDesignLanduse(landuse)) return 'Design / Display Only';
        if (isMultipurposeCourtLanduse(landuse)) return 'Multi Purpose Court';
        if (isOpenFieldLanduse(landuse)) return 'Open Field Area';
        return 'Campus Landuse';
    }

    function normalizeLanduseTypeValue(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, ' ')
            .trim();
    }

    function getLanduseTypeValue(landuse) {
        const p = landuse?.properties || {};

        return normalizeLanduseTypeValue(
            landuse?.type ??
            landuse?.landuse_type ??
            landuse?.land_use_type ??
            p.type ??
            p.landuse_type ??
            p.land_use_type ??
            p.category ??
            p.kind ??
            ''
        );
    }

    function isDesignLanduse(landuse) {
        const typeValue = getLanduseTypeValue(landuse);

        return typeValue === 'design' ||
            typeValue === 'display design' ||
            typeValue === 'decorative design' ||
            typeValue.includes('design only');
    }

    function getRoutableLanduses() {
        return (landuseRecords || []).filter(landuse => !isDesignLanduse(landuse));
    }

    function getGeometryBounds(geometry) {
        if (!geometry) return null;

        try {
            const layer = L.geoJSON({
                type: 'Feature',
                geometry: geometry,
                properties: {}
            });

            const bounds = layer.getBounds();
            return bounds.isValid() ? bounds : null;
        } catch (e) {
            return null;
        }
    }

    function getPolygonRingsFromGeometry(geometry) {
        if (!geometry || !geometry.type || !geometry.coordinates) return [];

        if (geometry.type === 'Polygon') {
            return geometry.coordinates || [];
        }

        if (geometry.type === 'MultiPolygon') {
            return geometry.coordinates[0] || [];
        }

        return [];
    }

    function projectRingToBounds(ring, bounds, viewWidth, viewHeight) {
        const west = bounds.getWest();
        const east = bounds.getEast();
        const north = bounds.getNorth();
        const south = bounds.getSouth();

        const lngSpan = east - west || 1;
        const latSpan = north - south || 1;

        return ring.map(coord => {
            const lng = Number(coord[0]);
            const lat = Number(coord[1]);

            const x = ((lng - west) / lngSpan) * viewWidth;
            const y = ((north - lat) / latSpan) * viewHeight;

            return `${x},${y}`;
        }).join(' ');
    }

    function latLngToMapPoint(lat, lng) {
        return map.latLngToLayerPoint([Number(lat), Number(lng)]);
    }

    function ringToMapPath(ring) {
        if (!Array.isArray(ring) || !ring.length) return '';

        return ring.map((coord, index) => {
            const pt = latLngToMapPoint(coord[1], coord[0]);
            return `${index === 0 ? 'M' : 'L'} ${pt.x} ${pt.y}`;
        }).join(' ') + ' Z';
    }

    function buildClipPathDFromGeometry(geometry) {
        const rings = getPolygonRingsFromGeometry(geometry);
        if (!rings.length) return '';

        return rings.map(ring => ringToMapPath(ring)).join(' ');
    }

    function distance2D(a, b) {
        const dx = b.x - a.x;
        const dy = b.y - a.y;
        return Math.sqrt((dx * dx) + (dy * dy));
    }

    function hasExactImageCorners(p) {
        return [
            p.image_tl_lat, p.image_tl_lng,
            p.image_tr_lat, p.image_tr_lng,
            p.image_bl_lat, p.image_bl_lng,
            p.image_br_lat, p.image_br_lng
        ].every(v => v !== null && v !== undefined && v !== '');
    }

    function createExactLanduseSvgForCurrentMap(feature, p) {
        if (!feature || !feature.geometry || !p.image) return null;

        const mapSize = map.getSize();
        const widthPx = mapSize.x;
        const heightPx = mapSize.y;

        const clipPathId = `landuse-clip-${Number(p.id)}-${Date.now()}`;
        const polygonPathD = buildClipPathDFromGeometry(feature.geometry);

        if (!polygonPathD) return null;

        let imageMarkup = '';

        if (hasExactImageCorners(p)) {
            const tl = latLngToMapPoint(p.image_tl_lat, p.image_tl_lng);
            const tr = latLngToMapPoint(p.image_tr_lat, p.image_tr_lng);
            const bl = latLngToMapPoint(p.image_bl_lat, p.image_bl_lng);
            const br = latLngToMapPoint(p.image_br_lat, p.image_br_lng);

            const imageWidth = distance2D(tl, tr);
            const imageHeight = distance2D(tl, bl);

            const centerX = (tl.x + tr.x + bl.x + br.x) / 4;
            const centerY = (tl.y + tr.y + bl.y + br.y) / 4;

            const angle = Math.atan2(tr.y - tl.y, tr.x - tl.x) * (180 / Math.PI);

            const imageX = centerX - (imageWidth / 2);
            const imageY = centerY - (imageHeight / 2);

            imageMarkup = `
            <image
                href="/landuse_images/${p.image}"
                x="${imageX}"
                y="${imageY}"
                width="${imageWidth}"
                height="${imageHeight}"
                preserveAspectRatio="none"
                transform="rotate(${angle} ${centerX} ${centerY})"
            />
        `;
        } else {
            const bounds = getGeometryBounds(feature.geometry);
            if (!bounds) return null;

            const viewWidth = 1000;
            const viewHeight = 1000;

            const rings = getPolygonRingsFromGeometry(feature.geometry);
            const outerRing = projectRingToBounds(rings[0], bounds, viewWidth, viewHeight);
            const holeRings = rings.slice(1).map(ring => projectRingToBounds(ring, bounds, viewWidth, viewHeight));
            const fallbackClipId = `landuse-fallback-clip-${Number(p.id)}-${Date.now()}`;

            const polygonBaseAngle = Number(p.polygon_base_angle ?? 0);
            const localScaleX = Math.max(0.01, Number(p.image_local_scale_x ?? 1));
            const localScaleY = Math.max(0.01, Number(p.image_local_scale_y ?? 1));
            const localOffsetU = Number(p.image_local_offset_u ?? 0);
            const localOffsetV = Number(p.image_local_offset_v ?? 0);
            const localRotation = Number(p.image_local_rotation ?? 0);

            const finalRotation = polygonBaseAngle + localRotation;
            const imageWidth = viewWidth * localScaleX;
            const imageHeight = viewHeight * localScaleY;

            const theta = polygonBaseAngle * Math.PI / 180;
            const offsetXRatio = (localOffsetU * Math.cos(theta)) - (localOffsetV * Math.sin(theta));
            const offsetYRatio = (localOffsetU * Math.sin(theta)) + (localOffsetV * Math.cos(theta));

            const imageX = ((viewWidth - imageWidth) / 2) + (offsetXRatio * viewWidth);
            const imageY = ((viewHeight - imageHeight) / 2) + (offsetYRatio * viewHeight);

            const holesMarkup = holeRings.map(points => `<polygon points="${points}" />`).join('');

            return `
            <svg xmlns="http://www.w3.org/2000/svg"
                 viewBox="0 0 ${viewWidth} ${viewHeight}"
                 width="${viewWidth}"
                 height="${viewHeight}"
                 style="position:absolute;left:0;top:0;pointer-events:none;overflow:hidden;">
                <defs>
                    <clipPath id="${fallbackClipId}" clipPathUnits="userSpaceOnUse">
                        <polygon points="${outerRing}" />
                        ${holesMarkup}
                    </clipPath>
                </defs>
                <g clip-path="url(#${fallbackClipId})">
                    <image
                        href="/landuse_images/${p.image}"
                        x="${imageX}"
                        y="${imageY}"
                        width="${imageWidth}"
                        height="${imageHeight}"
                        preserveAspectRatio="none"
                        transform="rotate(${finalRotation} ${viewWidth / 2} ${viewHeight / 2})"
                    />
                </g>
            </svg>
        `;
        }

        return `
        <svg xmlns="http://www.w3.org/2000/svg"
             width="${widthPx}"
             height="${heightPx}"
             viewBox="0 0 ${widthPx} ${heightPx}"
             style="position:absolute;left:0;top:0;pointer-events:none;overflow:visible;">
            <defs>
                <clipPath id="${clipPathId}" clipPathUnits="userSpaceOnUse">
                    <path d="${polygonPathD}" fill-rule="evenodd" clip-rule="evenodd"></path>
                </clipPath>
            </defs>
            <g clip-path="url(#${clipPathId})">
                ${imageMarkup}
            </g>
        </svg>
    `;
    }

    const ExactLanduseOverlay = L.Layer.extend({
        initialize: function(feature, props, options = {}) {
            this._feature = feature;
            this._props = props;
            L.setOptions(this, options);
        },

        onAdd: function(mapInstance) {
            this._map = mapInstance;
            this._pane = mapInstance.getPane(this.options.pane || 'landuseImagePane');

            this._container = L.DomUtil.create('div', 'exact-landuse-overlay');
            this._container.style.position = 'absolute';
            this._container.style.left = '0';
            this._container.style.top = '0';
            this._container.style.width = '100%';
            this._container.style.height = '100%';
            this._container.style.pointerEvents = 'none';
            this._pane.appendChild(this._container);

            this._queuedUpdateFrame = null;
            this._update = this._update.bind(this);
            this._queueUpdate = this._queueUpdate.bind(this);

            mapInstance.on('zoomend moveend viewreset resize', this._queueUpdate);

            /*
            |--------------------------------------------------------------------------
            | REAL PHONE PINCH-ZOOM LANDUSE PATCH
            |--------------------------------------------------------------------------
            | Do NOT convert the landuse image to another overlay type.
            | Keep the original exact-corner SVG logic, but update it during real
            | mobile pinch zoom through requestAnimationFrame so it follows the map
            | instead of waiting only for zoomend.
            |
            | This runs only on touch/mobile devices.
            */
            const isRealTouchPhone = window.matchMedia('(pointer: coarse), (hover: none)').matches;
            if (isRealTouchPhone) {
                mapInstance.on('zoom move', this._queueUpdate);
            }
            this._queueUpdate();
        },

        onRemove: function(mapInstance) {
            mapInstance.off('zoomend moveend viewreset resize', this._queueUpdate);
            mapInstance.off('zoom move', this._queueUpdate);

            if (this._queuedUpdateFrame) {
                cancelAnimationFrame(this._queuedUpdateFrame);
                this._queuedUpdateFrame = null;
            }

            if (this._container && this._container.parentNode) {
                this._container.parentNode.removeChild(this._container);
            }

            this._container = null;
            this._map = null;
        },

        _queueUpdate: function() {
            if (this._queuedUpdateFrame) {
                cancelAnimationFrame(this._queuedUpdateFrame);
            }

            this._queuedUpdateFrame = requestAnimationFrame(() => {
                this._queuedUpdateFrame = null;
                this._update();
            });
        },

        _update: function() {
            if (!this._map || !this._container) return;

            const svgMarkup = createExactLanduseSvgForCurrentMap(this._feature, this._props);
            this._container.innerHTML = svgMarkup || '';
        }
    });

    function projectLatLngToBoundsLocal(lat, lng, bounds, viewWidth, viewHeight) {
        const west = bounds.getWest();
        const east = bounds.getEast();
        const north = bounds.getNorth();
        const south = bounds.getSouth();

        const lngSpan = east - west || 1;
        const latSpan = north - south || 1;

        return {
            x: ((Number(lng) - west) / lngSpan) * viewWidth,
            y: ((north - Number(lat)) / latSpan) * viewHeight
        };
    }

    function distance2D(a, b) {
        const dx = b.x - a.x;
        const dy = b.y - a.y;
        return Math.sqrt((dx * dx) + (dy * dy));
    }

    function hasExactImageCorners(p) {
        return [
            p.image_tl_lat, p.image_tl_lng,
            p.image_tr_lat, p.image_tr_lng,
            p.image_bl_lat, p.image_bl_lng,
            p.image_br_lat, p.image_br_lng
        ].every(v => v !== null && v !== undefined && v !== '');
    }

    function createClippedLanduseSvg(geometry, p, bounds) {
        const rings = getPolygonRingsFromGeometry(geometry);
        if (!rings.length) return null;

        const viewWidth = 1000;
        const viewHeight = 1000;

        const outerRing = projectRingToBounds(rings[0], bounds, viewWidth, viewHeight);
        const holeRings = rings.slice(1).map(ring =>
            projectRingToBounds(ring, bounds, viewWidth, viewHeight)
        );

        const imageUrl = `/landuse_images/${p.image}`;
        const clipId = `landuse-clip-${Number(p.id)}-${Date.now()}`;

        let imageMarkup = '';

        if (hasExactImageCorners(p)) {
            const tl = projectLatLngToBoundsLocal(p.image_tl_lat, p.image_tl_lng, bounds, viewWidth, viewHeight);
            const tr = projectLatLngToBoundsLocal(p.image_tr_lat, p.image_tr_lng, bounds, viewWidth, viewHeight);
            const bl = projectLatLngToBoundsLocal(p.image_bl_lat, p.image_bl_lng, bounds, viewWidth, viewHeight);
            const br = projectLatLngToBoundsLocal(p.image_br_lat, p.image_br_lng, bounds, viewWidth, viewHeight);

            const width = distance2D(tl, tr);
            const height = distance2D(tl, bl);

            const centerX = (tl.x + tr.x + bl.x + br.x) / 4;
            const centerY = (tl.y + tr.y + bl.y + br.y) / 4;

            const angle = Math.atan2(tr.y - tl.y, tr.x - tl.x) * (180 / Math.PI);

            const imageX = centerX - (width / 2);
            const imageY = centerY - (height / 2);

            imageMarkup = `
            <image
                href="${imageUrl}"
                x="${imageX}"
                y="${imageY}"
                width="${width}"
                height="${height}"
                preserveAspectRatio="none"
                transform="rotate(${angle} ${centerX} ${centerY})"
            />
        `;
        } else {
            const polygonBaseAngle = Number(p.polygon_base_angle ?? 0);
            const localScaleX = Math.max(0.01, Number(p.image_local_scale_x ?? 1));
            const localScaleY = Math.max(0.01, Number(p.image_local_scale_y ?? 1));
            const localOffsetU = Number(p.image_local_offset_u ?? 0);
            const localOffsetV = Number(p.image_local_offset_v ?? 0);
            const localRotation = Number(p.image_local_rotation ?? 0);

            const finalRotation = polygonBaseAngle + localRotation;

            const imageWidth = viewWidth * localScaleX;
            const imageHeight = viewHeight * localScaleY;

            const theta = polygonBaseAngle * Math.PI / 180;
            const offsetXRatio =
                (localOffsetU * Math.cos(theta)) -
                (localOffsetV * Math.sin(theta));

            const offsetYRatio =
                (localOffsetU * Math.sin(theta)) +
                (localOffsetV * Math.cos(theta));

            const imageX = ((viewWidth - imageWidth) / 2) + (offsetXRatio * viewWidth);
            const imageY = ((viewHeight - imageHeight) / 2) + (offsetYRatio * viewHeight);

            imageMarkup = `
            <image
                href="${imageUrl}"
                x="${imageX}"
                y="${imageY}"
                width="${imageWidth}"
                height="${imageHeight}"
                preserveAspectRatio="none"
                transform="rotate(${finalRotation} ${viewWidth / 2} ${viewHeight / 2})"
            />
        `;
        }

        const holesMarkup = holeRings.map(points => `<polygon points="${points}" />`).join('');

        return `
        <svg xmlns="http://www.w3.org/2000/svg"
             viewBox="0 0 ${viewWidth} ${viewHeight}"
             preserveAspectRatio="none"
             style="width:100%;height:100%;display:block;pointer-events:none;overflow:hidden;">
            <defs>
                <clipPath id="${clipId}" clipPathUnits="userSpaceOnUse">
                    <polygon points="${outerRing}" />
                    ${holesMarkup}
                </clipPath>
            </defs>

            <g clip-path="url(#${clipId})">
                ${imageMarkup}
            </g>
        </svg>
    `;
    }

    function addClippedLanduseOverlay(feature, p, layerGroup) {
        if (!feature || !feature.geometry || !p.image || !layerGroup) return null;

        /*
        |--------------------------------------------------------------------------
        | LANDUSE IMAGE ZOOM FIX
        |--------------------------------------------------------------------------
        | Keep using the exact SVG/clip overlay.
        | Important: the overlay is NOT recalculated during active zoom.
        | Leaflet scales the pane while zooming, then we update after zoomend.
        */
        const overlay = new ExactLanduseOverlay(feature, p, {
            pane: 'landuseImagePane'
        });

        layerGroup.addLayer(overlay);
        return overlay;
    }

    function normalizeIndoorFeature(feature) {
        const p = feature.properties || {};
        const extra = p.properties || {};

        return {
            ...feature,
            properties: {
                ...p,
                ...extra,
                id: p.id ?? extra.id ?? null,
                indoor_map_id: p.indoor_map_id ?? extra.indoor_map_id ?? null,
                building_id: p.building_id ?? extra.building_id ?? null,
                building_name: p.building_name ?? extra.building_name ?? extra.building ?? p.building ?? null,
                floor_number: p.floor_number ?? extra.floor_number ?? extra.floor ?? p.floor ?? null,
                floor_label: p.floor_label ?? extra.floor_label ?? (
                    hasIndoorFloorValue(p.floor_number ?? extra.floor_number ?? extra.floor ?? p.floor) ?
                    formatIndoorFloorLabel(p.floor_number ?? extra.floor_number ?? extra.floor ?? p.floor) :
                    null
                ),
                name: p.name ?? extra.name ?? 'Unnamed',
                room_code: p.room_code ?? extra.room_code ?? null,
                type: p.type ?? extra.type ?? null,
                path_type: p.path_type ?? extra.path_type ?? null,
                ent_type: p.ent_type ?? extra.ent_type ?? null,
                is_blocked: Boolean(p.is_blocked ?? extra.is_blocked ?? false),
            }
        };
    }

    function normalizeIndoorMapRecord(mapItem) {
        if (!mapItem) return null;

        let geometry = mapItem.geometry ?? null;

        if (typeof geometry === 'string') {
            try {
                geometry = JSON.parse(geometry);
            } catch (e) {
                geometry = null;
            }
        }

        return {
            ...mapItem,
            floor_number: Number(mapItem.floor_number ?? 0),
            geometry
        };
    }

    function getIndoorMapBoundsFromGeometry(geometry) {
        if (!geometry || !geometry.type || !geometry.coordinates) return null;

        let latlngs = [];

        if (geometry.type === 'Polygon') {
            const ring = geometry.coordinates[0] || [];
            latlngs = ring.map(coord => [Number(coord[1]), Number(coord[0])]);
        } else if (geometry.type === 'MultiPolygon') {
            const firstPolygon = geometry.coordinates[0] || [];
            const ring = firstPolygon[0] || [];
            latlngs = ring.map(coord => [Number(coord[1]), Number(coord[0])]);
        } else {
            return null;
        }

        if (!latlngs.length) return null;

        const bounds = L.latLngBounds(latlngs);
        return bounds.isValid() ? bounds : null;
    }

    function getIndoorMapGeometryLayer(geometry, options = {}) {
        if (!geometry) return null;

        try {
            return L.geoJSON({
                    type: 'Feature',
                    geometry,
                    properties: {}
                },
                options
            );
        } catch (e) {
            return null;
        }
    }

    function normalizeFeatureCollection(fc) {
        return {
            type: 'FeatureCollection',
            features: (fc?.features || []).map(normalizeIndoorFeature)
        };
    }

    function getFeatureCenter(feature) {
        const bounds = L.geoJSON(feature).getBounds();
        return bounds.getCenter();
    }

    function getPointLatLng(feature) {
        const coords = feature?.geometry?.coordinates || [];
        if (!Array.isArray(coords) || coords.length < 2) return null;
        return L.latLng(Number(coords[1]), Number(coords[0]));
    }

    function getFloorFromNodeKey(key) {
        const match = String(key).match(/_f(\d+)$/);
        return match ? Number(match[1]) : null;
    }

    function clearRouteLayer() {
        if (outdoorRouteAnimationTimer) {
            clearInterval(outdoorRouteAnimationTimer);
            outdoorRouteAnimationTimer = null;
        }

        if (routeLayer) {
            map.removeLayer(routeLayer);
            routeLayer = null;
        }
    }

    function clearStartMarker() {
        if (startMarker) {
            map.removeLayer(startMarker);
            startMarker = null;
        }
    }

    function clearDestinationMarker() {
        if (destinationMarker) {
            map.removeLayer(destinationMarker);
            destinationMarker = null;
        }
    }

    function clearCurrentLocationMarker() {
        if (currentLocationMarker) {
            map.removeLayer(currentLocationMarker);
            currentLocationMarker = null;
        }
    }

    function clearOutsideGuideLine() {
        if (outsideGuideLine) {
            map.removeLayer(outsideGuideLine);
            outsideGuideLine = null;
        }
    }

    function clearHazardLayer() {
        if (hazardLayer) {
            map.removeLayer(hazardLayer);
            hazardLayer = null;
        }
    }


    function clearIndoorRoute() {
        if (indoorRouteAnimationTimer) {
            clearInterval(indoorRouteAnimationTimer);
            indoorRouteAnimationTimer = null;
        }

        if (indoorRouteLayer && indoorMap) {
            indoorMap.removeLayer(indoorRouteLayer);
            indoorRouteLayer = null;
        }

        if (indoorStartMarker && indoorMap) {
            indoorMap.removeLayer(indoorStartMarker);
            indoorStartMarker = null;
        }

        if (indoorEndMarker && indoorMap) {
            indoorMap.removeLayer(indoorEndMarker);
            indoorEndMarker = null;
        }
    }

    function clearIndoorRoutingStateOnly() {
        /*
        |--------------------------------------------------------------------------
        | OUTDOOR-ONLY ROUTE CLEANUP
        |--------------------------------------------------------------------------
        | Use this before routing to Building-only or Landuse-only destination.
        | This removes any old indoor room route so it will not remain when user
        | switches from indoor room routing to building routing.
        */
        selectedIndoorRoomFeature = null;

        lastIndoorRoutePackage = null;
        persistentIndoorRouteByFloor = {};
        pendingIndoorOpenForBuildingId = null;
        pendingIndoorFocusFloor = null;

        if (indoorMap) {
            clearIndoorRoute();

            if (typeof clearIndoorRoomCenterLabelsSmart === 'function') {
                clearIndoorRoomCenterLabelsSmart();
            }

            if (typeof clearIndoorRoomCenterLabelsFinal === 'function') {
                clearIndoorRoomCenterLabelsFinal();
            }

            if (typeof clearIndoorRoomCenterLabels === 'function') {
                clearIndoorRoomCenterLabels();
            }

            if (typeof clearIndoorRoomCenterLabelsFinal === 'function') {
                clearIndoorRoomCenterLabelsFinal();
            }
        }

        closeIndoorPanelFn();

        if (destinationRoomSelect) {
            destinationRoomSelect.value = '';
        }

        if (indoorFooter) {
            indoorFooter.innerHTML = `
                <span class="indoor-badge badge-blue">Select Building</span>
                Choose a room or office to compute the route.
            `;
        }
    }

    function clearIndoorGeometryDebug() {
        if (indoorGeometryDebugLayer && indoorMap) {
            indoorMap.removeLayer(indoorGeometryDebugLayer);
            indoorGeometryDebugLayer = null;
        }
    }

    function drawOutsideGuideLine(fromLat, fromLng, toLat, toLng) {
        clearOutsideGuideLine();
        outsideGuideLine = L.polyline([
            [fromLat, fromLng],
            [toLat, toLng]
        ], {
            color: '#7c3aed',
            weight: 4,
            opacity: 0.85,
            dashArray: '10, 10'
        }).addTo(map);
    }

    function isInsideCampus(lat, lng) {
        if (!campusBounds) return false;
        return campusBounds.contains([lat, lng]);
    }

    function openIndoorPanelModal() {
        if (indoorBackdrop) indoorBackdrop.classList.add('active');
        if (indoorPanel) indoorPanel.classList.add('active');

        setTimeout(() => {
            if (indoorMap) indoorMap.invalidateSize();
        }, 120);
    }

    function closeIndoorPanelFn() {
        if (indoorBackdrop) indoorBackdrop.classList.remove('active');
        if (indoorPanel) indoorPanel.classList.remove('active');
    }

    function getHazardsForPath(pathId) {
        return hazardPoints.filter(h =>
            Number(h.path_id) === Number(pathId) &&
            Boolean(h.is_active) &&
            Boolean(h.affects_routing)
        );
    }

    function getPathHazardProfile(pathId) {
        const hazards = getHazardsForPath(pathId);

        if (!hazards.length) {
            return {
                maxSeverity: 0,
                hasHazard: false,
                colorHint: 'safe',
                penalty: 1
            };
        }

        const maxSeverity = Math.max(...hazards.map(h => Number(h.severity_level || 1)));

        if (maxSeverity >= 3) {
            return {
                maxSeverity: 3,
                hasHazard: true,
                colorHint: 'danger',
                penalty: 200
            };
        }

        if (maxSeverity === 2) {
            return {
                maxSeverity: 2,
                hasHazard: true,
                colorHint: 'caution',
                penalty: 4
            };
        }

        return {
            maxSeverity: 1,
            hasHazard: true,
            colorHint: 'caution',
            penalty: 1.7
        };
    }
