@php
    $selectorKey = $selectorKey ?? 'building';
    $mapId = $selectorKey . '_building_map';
    $panelId = $selectorKey . '_building_map_panel';
    $dropdownId = $selectorKey . '_building_dropdown_panel';
    $selectedBuildingId = (string) ($selectedBuildingId ?? '');
@endphp

@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        .building-map-selector {
            margin-bottom: 20px;
            padding: 16px;
            border: 1px solid #c9def5;
            border-radius: 18px;
            background: linear-gradient(145deg, #f9fcff, #eef6ff);
        }

        .building-map-selector-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 14px;
        }

        .building-map-selector-kicker {
            display: block;
            margin-bottom: 3px;
            color: #3978ba;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .11em;
            text-transform: uppercase;
        }

        .admin-future .content-page .building-map-selector h5 {
            margin: 0;
            color: #18375d !important;
            font-size: 16px;
            font-weight: 800;
        }

        .building-map-selector-head p {
            margin: 5px 0 0;
            color: #587492;
            font-size: 12px;
        }

        .building-map-selector-methods {
            display: inline-flex;
            flex: 0 0 auto;
            gap: 5px;
            padding: 5px;
            border: 1px solid #c5dcf3;
            border-radius: 13px;
            background: #fff;
        }

        .building-map-selector-method {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 38px;
            padding: 8px 13px;
            border: 0;
            border-radius: 9px;
            color: #335779;
            background: transparent;
            font-size: 12px;
            font-weight: 800;
        }

        .building-map-selector-method.active {
            color: #fff;
            background: linear-gradient(135deg, #18375d, #68a7ee);
            box-shadow: 0 7px 18px rgba(24, 55, 93, .2);
        }

        .building-map-selector-layout {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(230px, .72fr);
            min-height: 350px;
            overflow: hidden;
            border: 1px solid #bad6f1;
            border-radius: 15px;
            background: #fff;
        }

        .building-map-selector-map {
            z-index: 1;
            min-height: 350px;
            border-right: 1px solid #c7dcf1;
            background: #edf5fd;
        }

        .building-map-selector-status {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            padding: 22px;
            text-align: center;
            background:
                radial-gradient(circle at 50% 15%, rgba(104, 167, 238, .18), transparent 42%),
                #f8fbff;
        }

        .building-map-selector-empty,
        .building-map-selector-selected {
            width: 100%;
        }

        .building-map-selector-empty i {
            display: block;
            margin-bottom: 10px;
            color: #68a7ee;
            font-size: 38px;
        }

        .building-map-selector-empty strong,
        .building-map-selector-selected strong {
            display: block;
            color: #18375d;
            font-size: 16px;
            font-weight: 800;
        }

        .building-map-selector-empty span,
        .building-map-selector-selected small {
            display: block;
            margin-top: 6px;
            color: #5c7692;
            font-size: 12px;
        }

        .building-map-selector-empty.has-error {
            padding: 15px;
            border: 1px solid #e8a2aa;
            border-radius: 13px;
            background: #fff4f5;
        }

        .building-map-selector-empty.has-error i,
        .building-map-selector-empty.has-error strong {
            color: #b32e3b;
        }

        .building-map-selector-selected > span {
            display: block;
            margin-bottom: 7px;
            color: #3978ba;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .building-map-selector-change {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 15px;
            padding: 9px 12px;
            border: 1px solid #b8d3ed;
            border-radius: 10px;
            color: #18375d;
            background: #fff;
            font-size: 11px;
            font-weight: 800;
        }

        .building-map-selector-dropdown p {
            margin: 0;
            padding: 11px 13px;
            border: 1px solid #c7dcf1;
            border-radius: 11px;
            color: #365b7e;
            background: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .building-map-selector-head {
                flex-direction: column;
            }

            .building-map-selector-methods {
                width: 100%;
            }

            .building-map-selector-method {
                flex: 1;
            }

            .building-map-selector-layout {
                grid-template-columns: 1fr;
            }

            .building-map-selector-map {
                min-height: 320px;
                border-right: 0;
                border-bottom: 1px solid #c7dcf1;
            }
        }

        @media (max-width: 520px) {
            .building-map-selector {
                padding: 11px;
            }

            .building-map-selector-method {
                padding-inline: 8px;
                font-size: 10px;
            }

            .building-map-selector-map {
                min-height: 280px;
            }
        }
    </style>
@endonce

<section
    class="building-map-selector"
    id="{{ $selectorKey }}_building_selector"
    data-building-map-selector
    data-select-id="{{ $selectId }}"
>
    <div class="building-map-selector-head">
        <div>
            <span class="building-map-selector-kicker">Building Selection</span>
            <h5>Choose a building from the campus map</h5>
            <p>Click a solid building polygon, or switch to the dropdown when preferred.</p>
        </div>

        <div class="building-map-selector-methods" role="group" aria-label="Building selection method">
            <button
                type="button"
                class="building-map-selector-method active"
                data-building-selector-method="map"
                aria-pressed="true"
            >
                <i class="ri-map-2-line"></i>
                Select on Map
            </button>
            <button
                type="button"
                class="building-map-selector-method"
                data-building-selector-method="dropdown"
                aria-pressed="false"
            >
                <i class="ri-list-check-2"></i>
                Use Dropdown
            </button>
        </div>
    </div>

    <div class="building-map-selector-panel" id="{{ $panelId }}">
        <div class="building-map-selector-layout">
            <div
                class="building-map-selector-map"
                id="{{ $mapId }}"
                aria-label="Campus buildings selection map"
            ></div>

            <aside class="building-map-selector-status" aria-live="polite">
                <div class="building-map-selector-empty">
                    <i class="ri-building-2-line"></i>
                    <strong>Select a building</strong>
                    <span>Click any solid building shown on the map.</span>
                </div>
                <div class="building-map-selector-selected" hidden>
                    <span>Selected Building</span>
                    <strong></strong>
                    <small>The related entrance choices are now ready.</small>
                    <button type="button" class="building-map-selector-change">
                        <i class="ri-map-pin-line"></i>
                        Choose Another Building
                    </button>
                </div>
            </aside>
        </div>
    </div>

    <div class="building-map-selector-dropdown" id="{{ $dropdownId }}" hidden>
        <p><i class="ri-information-line"></i> Use the Building dropdown below to select manually.</p>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById(@json($selectorKey . '_building_selector'));
    const select = document.getElementById(@json($selectId));
    const dropdownField = document.getElementById(@json($dropdownFieldId ?? ''));
    const mapPanel = document.getElementById(@json($panelId));
    const dropdownPanel = document.getElementById(@json($dropdownId));
    const mapElement = document.getElementById(@json($mapId));
    const buildings = @json($buildingMapData);

    if (!root || !select || !mapPanel || !dropdownPanel || !mapElement) {
        return;
    }

    const methodButtons = Array.from(root.querySelectorAll('[data-building-selector-method]'));
    const emptyState = root.querySelector('.building-map-selector-empty');
    const emptyTitle = emptyState?.querySelector('strong');
    const emptyMessage = emptyState?.querySelector('span');
    const selectedState = root.querySelector('.building-map-selector-selected');
    const selectedName = selectedState?.querySelector('strong');
    const changeButton = root.querySelector('.building-map-selector-change');
    const buildingLayers = new Map();
    let map = null;

    function buildingById(id) {
        return buildings.find(building => Number(building.id) === Number(id));
    }

    function updateSelection(buildingId, notify = true) {
        const building = buildingById(buildingId);

        buildingLayers.forEach((layer, layerId) => {
            const isSelected = building && Number(layerId) === Number(building.id);
            const originalBuilding = buildingById(layerId);
            layer.setStyle({
                color: isSelected ? '#0d2d53' : '#245a86',
                weight: isSelected ? 5 : 2,
                fillColor: isSelected ? '#68a7ee' : (originalBuilding?.color || '#4f94d4'),
                fillOpacity: isSelected ? 1 : .92,
            });
        });

        if (!building) {
            emptyState.hidden = false;
            emptyState.classList.remove('has-error');
            emptyTitle.textContent = 'Select a building';
            emptyMessage.textContent = 'Click any solid building shown on the map.';
            selectedState.hidden = true;
            return;
        }

        select.value = String(building.id);
        emptyState.hidden = true;
        emptyState.classList.remove('has-error');
        selectedState.hidden = false;
        selectedName.textContent = building.name || 'Selected Building';

        if (notify) {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function initializeMap() {
        if (map || typeof L === 'undefined') {
            map?.invalidateSize();
            return;
        }

        map = L.map(mapElement, {
            zoomControl: true,
            preferCanvas: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 21,
        }).addTo(map);

        const visibleLayers = [];

        buildings.forEach(building => {
            if (!building.geometry) return;

            const baseFillColor = building.color || '#4f94d4';
            const layer = L.geoJSON(
                { type: 'Feature', geometry: building.geometry },
                {
                    style: {
                        color: '#245a86',
                        weight: 2,
                        fillColor: baseFillColor,
                        fillOpacity: .92,
                        baseFillColor,
                    },
                }
            ).addTo(map);

            layer.bindTooltip(building.name || 'Building', {
                sticky: true,
                direction: 'top',
            });
            layer.on('click', event => {
                L.DomEvent.stopPropagation(event);
                updateSelection(building.id);
            });

            buildingLayers.set(Number(building.id), layer);
            visibleLayers.push(layer);
        });

        if (visibleLayers.length) {
            map.fitBounds(L.featureGroup(visibleLayers).getBounds(), {
                padding: [24, 24],
            });
        } else {
            map.setView([10.2925, 124.9985], 18);
        }

        updateSelection(select.value || @json($selectedBuildingId), false);
    }

    function setMethod(method) {
        const showMap = method === 'map';
        mapPanel.hidden = !showMap;
        dropdownPanel.hidden = showMap;
        if (dropdownField) {
            dropdownField.hidden = showMap;
        }

        methodButtons.forEach(button => {
            const active = button.dataset.buildingSelectorMethod === method;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        if (showMap) {
            requestAnimationFrame(initializeMap);
        }
    }

    methodButtons.forEach(button => {
        button.addEventListener('click', () => setMethod(button.dataset.buildingSelectorMethod));
    });

    select.addEventListener('change', () => updateSelection(select.value, false));
    select.addEventListener('invalid', event => {
        event.preventDefault();
        setMethod('map');
        emptyState.hidden = false;
        emptyState.classList.add('has-error');
        emptyTitle.textContent = 'Select a building first';
        emptyMessage.textContent = 'Click a building on the map before saving this link.';
        selectedState.hidden = true;
        root.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    changeButton?.addEventListener('click', () => {
        select.value = '';
        select.dispatchEvent(new Event('change', { bubbles: true }));
        map?.getContainer().focus();
    });

    setMethod('map');
});
</script>
