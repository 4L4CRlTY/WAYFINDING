@extends('admin.dashboard')

@section('admin')

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<style>
    .entrance-wrapper {
        padding: 24px;
    }

    .entrance-page-title {
        margin-bottom: 18px;
    }

    .entrance-page-title h3 {
        margin: 0;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.03em;
    }

    .entrance-page-title p {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 14px;
    }

    .entrance-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .entrance-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .entrance-card-header h4,
    .entrance-card-header h5 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .entrance-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .entrance-card-body {
        padding: 24px;
    }

    .entrance-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .entrance-form-control,
    .entrance-form-select {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }

    .entrance-form-control:focus,
    .entrance-form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .entrance-check {
        padding: 12px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #f8fafc;
    }

    .entrance-check .form-check-input {
        cursor: pointer;
    }

    .entrance-check .form-check-label {
        font-weight: 800;
        color: #334155;
        cursor: pointer;
    }

    .entrance-submit-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .entrance-submit-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .entrance-info-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        border-radius: 16px;
        padding: 14px;
        font-size: 13px;
        line-height: 1.6;
    }

    .entrance-info-box strong {
        color: #0f172a;
    }

    .entrance-map-header {
        padding: 20px 24px;
        background: linear-gradient(135deg, #0f172a, #334155);
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .entrance-map-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    #map {
        height: 650px;
        width: 100%;
    }

    .map-mode-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .map-mode-add {
        background: #fef3c7;
        color: #92400e;
    }

    .map-mode-edit {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .entrance-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .entrance-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .entrance-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .entrance-table {
        margin-bottom: 0;
    }

    .entrance-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .entrance-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .entrance-id-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        height: 32px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 800;
        font-size: 12px;
    }

    .entrance-name-text {
        font-weight: 800;
        color: #0f172a;
    }

    .entrance-building-text {
        font-weight: 700;
        color: #334155;
    }

    .entrance-coordinate {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .entrance-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .primary-yes {
        background: #dcfce7;
        color: #15803d;
    }

    .primary-no {
        background: #f1f5f9;
        color: #475569;
    }

    .entrance-action-btn {
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
    }

    .entrance-edit-btn {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .entrance-edit-btn:hover {
        background: #dbeafe;
        color: #1e40af;
    }

    .entrance-delete-btn {
        background: #fee2e2;
        color: #b91c1c;
    }

    .entrance-delete-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .empty-entrance-box {
        padding: 36px;
        text-align: center;
        color: #64748b;
    }

    .entrance-pagination-wrap .pagination {
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 0;
    }

    .entrance-pagination-wrap .page-link {
        border: none;
        border-radius: 12px;
        background: #f1f5f9;
        color: #334155;
        font-weight: 800;
        min-width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .entrance-pagination-wrap .page-item.active .page-link {
        background: #2563eb;
        color: white;
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.22);
    }

    .entrance-pagination-wrap .page-item.disabled .page-link {
        background: #e2e8f0;
        color: #94a3b8;
    }

    .entrance-modal .modal-content {
        border: none;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }

    .entrance-modal .modal-header {
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
        border: none;
        padding: 20px 24px;
    }

    .entrance-modal .modal-title {
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .entrance-modal .btn-close {
        filter: invert(1);
        opacity: 0.9;
    }

    .entrance-modal .modal-body {
        padding: 24px;
    }

    .entrance-modal .modal-footer {
        border-top: 1px solid #eef2f7;
        padding: 16px 24px;
    }

    .use-map-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 16px;
        font-weight: 800;
        background: #fef3c7;
        color: #92400e;
    }

    .use-map-btn:hover {
        background: #fde68a;
        color: #78350f;
    }

    .modal-close-btn {
        border: none;
        border-radius: 14px;
        padding: 10px 16px;
        font-weight: 800;
        background: #f1f5f9;
        color: #475569;
    }

    .modal-update-btn {
        border: none;
        border-radius: 14px;
        padding: 10px 16px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
    }

    @media (max-width: 768px) {
        .entrance-wrapper {
            padding: 14px;
        }

        .entrance-card-header,
        .entrance-card-body,
        .entrance-map-header,
        .entrance-table-header {
            padding: 18px;
        }

        #map {
            height: 430px;
        }

        .entrance-table {
            min-width: 980px;
        }

        .entrance-submit-btn {
            width: 100%;
        }
    }
</style>

<div class="entrance-wrapper">

    <div class="entrance-page-title">
        <h3>Building Entrances</h3>
        <p>Manage building entrance points for outdoor-to-indoor navigation routing.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please check the form:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- LEFT SIDE --}}
        <div class="col-lg-4">
            <div class="entrance-card">
                <div class="entrance-card-header">
                    <h4>Add Building Entrance</h4>
                    <p>Select a building and place the entrance point on the map.</p>
                </div>

                <div class="entrance-card-body">
                    <form action="{{ route('admin.building-entrances.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="entrance-form-label">Selected Building</label>
                            <input type="hidden" name="building_id" id="building_id" value="{{ old('building_id') }}">
                            <input
                                type="text"
                                id="building_name"
                                class="form-control entrance-form-control"
                                placeholder="Click a building on the map"
                                readonly
                            >
                        </div>

                        <div class="mb-3">
                            <label class="entrance-form-label">Entrance Name</label>
                            <input
                                type="text"
                                name="name"
                                class="form-control entrance-form-control"
                                value="{{ old('name') }}"
                                placeholder="Example: Main Entrance / Side Entrance / Back Entrance"
                            >
                        </div>

                        <div class="form-check entrance-check mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="is_primary"
                                id="is_primary"
                                value="1"
                                {{ old('is_primary') ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="is_primary">
                                Primary Entrance
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="entrance-form-label">Latitude</label>
                            <input
                                type="text"
                                name="latitude"
                                id="latitude"
                                class="form-control entrance-form-control"
                                value="{{ old('latitude') }}"
                                placeholder="Click map to set entrance point"
                                readonly
                            >
                        </div>

                        <div class="mb-3">
                            <label class="entrance-form-label">Longitude</label>
                            <input
                                type="text"
                                name="longitude"
                                id="longitude"
                                class="form-control entrance-form-control"
                                value="{{ old('longitude') }}"
                                placeholder="Click map to set entrance point"
                                readonly
                            >
                        </div>

                        <button type="submit" class="entrance-submit-btn w-100">
                            <i class="ri-door-open-line me-1"></i>
                            Save Entrance
                        </button>
                    </form>

                    <div class="entrance-info-box mt-4">
                        <strong>Add mode:</strong><br>
                        1. Click a building polygon on the map.<br>
                        2. Click the exact entrance point on the map.<br>
                        3. Fill entrance name if needed.<br>
                        4. Click <strong>Save Entrance</strong>.
                    </div>

                    <div class="entrance-info-box mt-3">
                        <strong>Edit mode:</strong><br>
                        1. Click <strong>Edit</strong> in the table.<br>
                        2. Modal opens with current data.<br>
                        3. Click <strong>Use Map</strong> then click map to update location.<br>
                        4. Click <strong>Update Entrance</strong>.
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT SIDE --}}
        <div class="col-lg-8">
            <div class="entrance-card">
                <div class="entrance-map-header">
                    <div>
                        <h4>Entrance Placement Map</h4>
                        <p class="mb-0 mt-1" style="opacity: .85; font-size: 14px;">
                            Click a building first, then click the exact entrance point.
                        </p>
                    </div>

                    <span class="map-mode-pill map-mode-add" id="mapModeBadge">ADD MODE</span>
                </div>

                <div class="p-0">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="entrance-table-card">
        <div class="entrance-table-header">
            <div>
                <h5>Existing Building Entrances</h5>
                <span class="muted-small">
                    Edit entrance information or update its map location.
                </span>
            </div>

            <span class="muted-small">
                Total Entrances: {{ $entrances->total() ?? $entrances->count() }}
            </span>
        </div>

        <div class="table-responsive">
            <table class="table entrance-table align-middle">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Building</th>
                        <th>Entrance Name</th>
                        <th width="110">Primary</th>
                        <th width="150">Latitude</th>
                        <th width="150">Longitude</th>
                        <th width="180">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($entrances as $entrance)
                        <tr>
                            <td>
                                <span class="entrance-id-badge">
                                    #{{ $entrance->id }}
                                </span>
                            </td>

                            <td>
                                <span class="entrance-building-text">
                                    {{ $entrance->building->name ?? 'N/A' }}
                                </span>
                            </td>

                            <td>
                                <span class="entrance-name-text">
                                    {{ $entrance->name ?? '—' }}
                                </span>
                            </td>

                            <td>
                                @if($entrance->is_primary)
                                    <span class="entrance-pill primary-yes">Yes</span>
                                @else
                                    <span class="entrance-pill primary-no">No</span>
                                @endif
                            </td>

                            <td>
                                <span class="entrance-coordinate">
                                    {{ $entrance->latitude }}
                                </span>
                            </td>

                            <td>
                                <span class="entrance-coordinate">
                                    {{ $entrance->longitude }}
                                </span>
                            </td>

                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button
                                        type="button"
                                        class="entrance-action-btn entrance-edit-btn edit-entrance-btn"
                                        data-id="{{ $entrance->id }}"
                                        data-building-id="{{ $entrance->building_id }}"
                                        data-building-name="{{ $entrance->building->name ?? 'N/A' }}"
                                        data-name="{{ $entrance->name }}"
                                        data-is-primary="{{ $entrance->is_primary ? 1 : 0 }}"
                                        data-latitude="{{ $entrance->latitude }}"
                                        data-longitude="{{ $entrance->longitude }}"
                                    >
                                        Edit
                                    </button>

                                    <form
                                        action="{{ route('admin.building-entrances.destroy', $entrance->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Delete this entrance?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="entrance-action-btn entrance-delete-btn">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-entrance-box">
                                    No building entrances found.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', [
            'paginator' => $entrances,
            'label' => 'building entrances',
        ])
    </div>
</div>

{{-- EDIT MODAL --}}
<div class="modal fade entrance-modal" id="editEntranceModal" tabindex="-1" aria-labelledby="editEntranceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="editEntranceForm">
            @csrf
            @method('PUT')

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEntranceModalLabel">Edit Building Entrance</h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                        onclick="closeEditMode()"
                    ></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="entrance-form-label">Building</label>
                        <select name="building_id" id="edit_building_id" class="form-select entrance-form-select" required>
                            <option value="">Select building</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}">{{ $building->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="entrance-form-label">Entrance Name</label>
                        <input
                            type="text"
                            name="name"
                            id="edit_name"
                            class="form-control entrance-form-control"
                            placeholder="Main Entrance / Side Entrance / Back Entrance"
                        >
                    </div>

                    <div class="form-check entrance-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_primary" id="edit_is_primary" value="1">
                        <label class="form-check-label" for="edit_is_primary">
                            Primary Entrance
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="entrance-form-label">Latitude</label>
                        <input type="text" name="latitude" id="edit_latitude" class="form-control entrance-form-control" readonly required>
                    </div>

                    <div class="mb-3">
                        <label class="entrance-form-label">Longitude</label>
                        <input type="text" name="longitude" id="edit_longitude" class="form-control entrance-form-control" readonly required>
                    </div>

                    <button type="button" class="use-map-btn w-100" id="useMapForEditBtn">
                        <i class="ri-map-pin-line me-1"></i>
                        Use Map To Update Location
                    </button>

                    <div class="muted-small mt-2">
                        After clicking the button above, click on the map to set a new entrance location.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="modal-close-btn" data-bs-dismiss="modal" onclick="closeEditMode()">
                        Close
                    </button>

                    <button type="submit" class="modal-update-btn">
                        Update Entrance
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    const buildingGeoJson = @json($buildingGeoJson);
    const entrancesMap = @json($entrancesMap);

    const map = L.map('map', {
        zoomControl: true
    });

    map.setView([10.2950, 125.0160], 19);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 22,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let selectedBuildingId = null;
    let selectedBuildingLayer = null;
    let clickMarker = null;
    let buildingLayerGroup = null;
    let entranceLayerGroup = L.layerGroup().addTo(map);

    let currentMode = 'add';
    let editTargetId = null;
    let editMarker = null;

    const mapModeBadge = document.getElementById('mapModeBadge');

    function updateModeBadge() {
        if (currentMode === 'edit') {
            mapModeBadge.textContent = 'EDIT MODE';
            mapModeBadge.className = 'map-mode-pill map-mode-edit';
        } else {
            mapModeBadge.textContent = 'ADD MODE';
            mapModeBadge.className = 'map-mode-pill map-mode-add';
        }
    }

    function defaultBuildingStyle(feature) {
        return {
            color: '#1b1b1b',
            weight: 1.2,
            fillColor: feature.properties.color || '#2b82cc',
            fillOpacity: 0.75
        };
    }

    function selectedBuildingStyle(feature) {
        return {
            color: '#ffcc00',
            weight: 3,
            fillColor: feature.properties.color || '#2b82cc',
            fillOpacity: 0.9
        };
    }

    function resetBuildingStyles() {
        if (!buildingLayerGroup) return;

        buildingLayerGroup.eachLayer(function(layer) {
            if (layer.feature) {
                layer.setStyle(defaultBuildingStyle(layer.feature));
            }
        });
    }

    buildingLayerGroup = L.geoJSON(buildingGeoJson, {
        style: defaultBuildingStyle,
        onEachFeature: function(feature, layer) {
            const buildingName = feature.properties.name ?? ('Building #' + feature.properties.id);

            layer.bindTooltip(buildingName);

            layer.on('click', function(e) {
                L.DomEvent.stopPropagation(e);

                selectedBuildingId = feature.properties.id;
                selectedBuildingLayer = layer;

                if (currentMode === 'add') {
                    document.getElementById('building_id').value = feature.properties.id;
                    document.getElementById('building_name').value = buildingName;
                }

                if (currentMode === 'edit') {
                    document.getElementById('edit_building_id').value = feature.properties.id;
                }

                resetBuildingStyles();
                layer.setStyle(selectedBuildingStyle(feature));
            });
        }
    }).addTo(map);

    function renderEntranceMarkers() {
        entranceLayerGroup.clearLayers();

        entrancesMap.forEach(function(entrance) {
            const marker = L.circleMarker([entrance.latitude, entrance.longitude], {
                radius: 7,
                color: '#ffffff',
                weight: 2,
                fillColor: entrance.is_primary ? '#16a34a' : '#dc3545',
                fillOpacity: 1
            });

            marker.bindPopup(`
                <div style="min-width:180px">
                    <strong>${entrance.name ?? 'Unnamed Entrance'}</strong><br>
                    Building: ${entrance.building_name ?? 'N/A'}<br>
                    Lat: ${entrance.latitude}<br>
                    Lng: ${entrance.longitude}
                </div>
            `);

            marker.addTo(entranceLayerGroup);
        });
    }

    renderEntranceMarkers();

    try {
        const bounds = buildingLayerGroup.getBounds();

        if (bounds.isValid()) {
            map.fitBounds(bounds.pad(0.05));
        }
    } catch (e) {}

    function placeAddMarker(lat, lng) {
        document.getElementById('latitude').value = Number(lat).toFixed(7);
        document.getElementById('longitude').value = Number(lng).toFixed(7);

        if (clickMarker) {
            map.removeLayer(clickMarker);
        }

        clickMarker = L.marker([lat, lng], {
            draggable: true
        }).addTo(map);

        clickMarker.bindPopup('Selected Entrance Point').openPopup();

        clickMarker.on('dragend', function(ev) {
            const position = ev.target.getLatLng();

            document.getElementById('latitude').value = position.lat.toFixed(7);
            document.getElementById('longitude').value = position.lng.toFixed(7);
        });
    }

    function placeEditMarker(lat, lng) {
        document.getElementById('edit_latitude').value = Number(lat).toFixed(7);
        document.getElementById('edit_longitude').value = Number(lng).toFixed(7);

        if (editMarker) {
            map.removeLayer(editMarker);
        }

        editMarker = L.marker([lat, lng], {
            draggable: true
        }).addTo(map);

        editMarker.bindPopup('Editing Entrance Location').openPopup();

        editMarker.on('dragend', function(ev) {
            const position = ev.target.getLatLng();

            document.getElementById('edit_latitude').value = position.lat.toFixed(7);
            document.getElementById('edit_longitude').value = position.lng.toFixed(7);
        });
    }

    map.on('click', function(e) {
        if (currentMode === 'add') {
            if (!selectedBuildingId) {
                alert('Please click a building first.');
                return;
            }

            placeAddMarker(e.latlng.lat, e.latlng.lng);
            return;
        }

        if (currentMode === 'edit') {
            placeEditMarker(e.latlng.lat, e.latlng.lng);
        }
    });

    function closeEditMode() {
        currentMode = 'add';
        editTargetId = null;
        updateModeBadge();

        if (editMarker) {
            map.removeLayer(editMarker);
            editMarker = null;
        }
    }

    document.querySelectorAll('.edit-entrance-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const buildingId = this.dataset.buildingId;
            const name = this.dataset.name ?? '';
            const isPrimary = parseInt(this.dataset.isPrimary) === 1;
            const latitude = this.dataset.latitude;
            const longitude = this.dataset.longitude;

            currentMode = 'edit';
            editTargetId = id;
            updateModeBadge();

            document.getElementById('editEntranceForm').action = `/admin/building-entrances/${id}`;
            document.getElementById('edit_building_id').value = buildingId;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_is_primary').checked = isPrimary;
            document.getElementById('edit_latitude').value = latitude;
            document.getElementById('edit_longitude').value = longitude;

            placeEditMarker(latitude, longitude);
            map.setView([latitude, longitude], 20);

            resetBuildingStyles();

            buildingLayerGroup.eachLayer(function(layer) {
                if (layer.feature && String(layer.feature.properties.id) === String(buildingId)) {
                    layer.setStyle(selectedBuildingStyle(layer.feature));
                }
            });

            const modal = new bootstrap.Modal(document.getElementById('editEntranceModal'));
            modal.show();
        });
    });

    document.getElementById('useMapForEditBtn').addEventListener('click', function() {
        currentMode = 'edit';
        updateModeBadge();
        alert('Edit mode enabled. Click on the map to choose the new entrance location.');
    });

    updateModeBadge();
</script>

@endsection
