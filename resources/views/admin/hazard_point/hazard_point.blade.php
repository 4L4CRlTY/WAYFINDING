@extends('admin.dashboard')

@section('admin')

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<style>
    .hazard-wrapper {
        padding: 24px;
    }

    .hazard-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .hazard-card + .hazard-card {
        margin-top: 24px;
    }

    .hazard-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .hazard-card-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .hazard-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .hazard-card-body {
        padding: 24px;
    }

    .hazard-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .hazard-form-control,
    .hazard-form-select {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }

    .hazard-form-control:focus,
    .hazard-form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .hazard-submit-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .hazard-submit-btn:hover {
        opacity: 0.94;
        color: white;
    }

    #hazardMap {
        width: 100%;
        height: 550px;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid #dbe3ef;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.65);
    }

    .hazard-map-note {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        border-radius: 16px;
        padding: 12px 14px;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 16px;
    }

    .hazard-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .hazard-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .hazard-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .hazard-table {
        margin-bottom: 0;
    }

    .hazard-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .hazard-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .hazard-id-badge {
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

    .hazard-title {
        font-weight: 800;
        color: #0f172a;
    }

    .hazard-description {
        color: #64748b;
        font-size: 13px;
        margin-top: 3px;
        max-width: 360px;
    }

    .hazard-path-text {
        font-weight: 700;
        color: #334155;
    }

    .hazard-coordinate {
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }

    .hazard-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .type-pill {
        background: #dbeafe;
        color: #1d4ed8;
        text-transform: capitalize;
    }

    .severity-low {
        background: #dcfce7;
        color: #15803d;
    }

    .severity-medium {
        background: #fef3c7;
        color: #92400e;
    }

    .severity-high {
        background: #fee2e2;
        color: #b91c1c;
    }

    .routing-yes {
        background: #fee2e2;
        color: #b91c1c;
    }

    .routing-no {
        background: #f1f5f9;
        color: #475569;
    }

    .status-active {
        background: #dcfce7;
        color: #15803d;
    }

    .status-inactive {
        background: #f1f5f9;
        color: #475569;
    }

    .hazard-delete-btn {
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
        background: #fee2e2;
        color: #b91c1c;
    }

    .hazard-delete-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .empty-hazard-box {
        padding: 36px;
        text-align: center;
        color: #64748b;
    }

    .editable-hazard {
        border-radius: 12px;
        transition: 0.18s ease;
    }

    .editable-hazard:hover {
        background: #f8fafc;
        cursor: pointer;
    }

    .editable-hazard input,
    .editable-hazard textarea,
    .editable-hazard select {
        border-radius: 12px;
        border: 1px solid #dbe3ef;
        font-size: 13px;
    }

    .hazard-pagination-wrap .pagination {
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 0;
    }

    .hazard-pagination-wrap .page-link {
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

    .hazard-pagination-wrap .page-item.active .page-link {
        background: #2563eb;
        color: white;
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.22);
    }

    .hazard-pagination-wrap .page-item.disabled .page-link {
        background: #e2e8f0;
        color: #94a3b8;
    }

    @media (max-width: 768px) {
        .hazard-wrapper {
            padding: 14px;
        }

        .hazard-card-header,
        .hazard-card-body,
        .hazard-table-header {
            padding: 18px;
        }

        #hazardMap {
            height: 420px;
        }

        .hazard-table {
            min-width: 1120px;
        }

        .hazard-submit-btn {
            width: 100%;
        }
    }
</style>

<div class="hazard-wrapper">

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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please check the form:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Map Card --}}
    <div class="hazard-card">
        <div class="hazard-card-header">
            <h4>Hazard Point Map</h4>
            <p>Select a campus path directly on the map before saving a hazard point.</p>
        </div>

        <div class="hazard-card-body">
            <div class="hazard-map-note">
                <i class="ri-map-pin-line me-1"></i>
                Click directly on a path to place a hazard point.
            </div>

            <div id="hazardMap"></div>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="hazard-card">
        <div class="hazard-card-header">
            <h4>Add Hazard Point</h4>
            <p>Save hazard details, severity level, routing effect, and active status.</p>
        </div>

        <div class="hazard-card-body">
            <form action="{{ route('admin.hazard-point.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="hazard-form-label">Selected Path ID</label>
                        <input type="text" id="selected_path_id_text" class="form-control hazard-form-control" readonly>
                        <input type="hidden" name="path_id" id="path_id" required>
                    </div>

                    <div class="col-lg-4">
                        <label class="hazard-form-label">Latitude</label>
                        <input type="text" name="latitude" id="latitude" class="form-control hazard-form-control" readonly required>
                    </div>

                    <div class="col-lg-4">
                        <label class="hazard-form-label">Longitude</label>
                        <input type="text" name="longitude" id="longitude" class="form-control hazard-form-control" readonly required>
                    </div>

                    <div class="col-lg-12">
                        <label class="hazard-form-label">Selected Path Name</label>
                        <input type="text" id="selected_path_name" class="form-control hazard-form-control" readonly>
                    </div>

                    <div class="col-lg-6">
                        <label class="hazard-form-label">Title</label>
                        <input
                            type="text"
                            name="title"
                            class="form-control hazard-form-control"
                            placeholder="Example: Broken Pavement"
                            required
                        >
                    </div>

                    <div class="col-lg-3">
                        <label class="hazard-form-label">Warning Type</label>
                        <select name="warning_type" class="form-select hazard-form-select" required>
                            <option value="hazard">Hazard</option>
                            <option value="broken_road">Broken Road</option>
                            <option value="slippery">Slippery</option>
                            <option value="stairs">Stairs</option>
                            <option value="construction">Construction</option>
                            <option value="flood">Flood</option>
                            <option value="caution">Caution</option>
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <label class="hazard-form-label">Severity Level</label>
                        <select name="severity_level" class="form-select hazard-form-select" required>
                            <option value="1">1 - Low</option>
                            <option value="2">2 - Medium</option>
                            <option value="3">3 - High</option>
                        </select>
                    </div>

                    <div class="col-lg-8">
                        <label class="hazard-form-label">Description / Note</label>
                        <textarea
                            name="description"
                            class="form-control hazard-form-control"
                            rows="3"
                            placeholder="Example: Uneven surface near this area"
                        ></textarea>
                    </div>

                    <div class="col-lg-2">
                        <label class="hazard-form-label">Affects Routing?</label>
                        <select name="affects_routing" class="form-select hazard-form-select" required>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <label class="hazard-form-label">Active?</label>
                        <select name="is_active" class="form-select hazard-form-select" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="hazard-submit-btn">
                            <i class="ri-alert-line me-1"></i>
                            Save Hazard Point
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="hazard-table-card">
        <div class="hazard-table-header">
            <div>
                <h5>Saved Hazard Points</h5>
                <span class="muted-small">
                    Double click / tap title to edit hazard details.
                </span>
            </div>

            <span class="muted-small">
                Total Hazards: {{ $hazardPoints->total() ?? $hazardPoints->count() }}
            </span>
        </div>

        <div class="table-responsive">
            @if($hazardPoints->count())
                <table class="table hazard-table align-middle">
                    <thead>
                        <tr>
                            <th width="70">ID</th>
                            <th>Title</th>
                            <th>Path</th>
                            <th>Type</th>
                            <th width="100">Severity</th>
                            <th width="120">Routing</th>
                            <th width="100">Status</th>
                            <th width="180">Coordinates</th>
                            <th width="110">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($hazardPoints as $hazard)
                            @php
                                $severityLabel = 'Low';
                                $severityClass = 'severity-low';

                                if ($hazard->severity_level == 2) {
                                    $severityLabel = 'Medium';
                                    $severityClass = 'severity-medium';
                                }

                                if ($hazard->severity_level == 3) {
                                    $severityLabel = 'High';
                                    $severityClass = 'severity-high';
                                }
                            @endphp

                            <tr>
                                <td>
                                    <span class="hazard-id-badge">
                                        #{{ $hazard->id }}
                                    </span>
                                </td>

                                <td
                                    class="editable-hazard"
                                    data-url="{{ route('admin.hazard-point.update', $hazard->id) }}"
                                    data-title="{{ $hazard->title }}"
                                    data-description="{{ $hazard->description }}"
                                    data-warning_type="{{ $hazard->warning_type }}"
                                    data-severity="{{ $hazard->severity_level }}"
                                    data-routing="{{ $hazard->affects_routing ? 1 : 0 }}"
                                    data-active="{{ $hazard->is_active ? 1 : 0 }}"
                                >
                                    <div class="hazard-title">{{ $hazard->title }}</div>
                                    <div class="hazard-description">
                                        {{ $hazard->description ?: '—' }}
                                    </div>
                                </td>

                                <td>
                                    <span class="hazard-path-text">
                                        {{ optional($hazard->path)->name ?: 'No Path' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="hazard-pill type-pill">
                                        {{ str_replace('_', ' ', $hazard->warning_type) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="hazard-pill {{ $severityClass }}">
                                        {{ $severityLabel }}
                                    </span>
                                </td>

                                <td>
                                    @if($hazard->affects_routing)
                                        <span class="hazard-pill routing-yes">Yes</span>
                                    @else
                                        <span class="hazard-pill routing-no">No</span>
                                    @endif
                                </td>

                                <td>
                                    @if($hazard->is_active)
                                        <span class="hazard-pill status-active">Active</span>
                                    @else
                                        <span class="hazard-pill status-inactive">Inactive</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="hazard-coordinate">
                                        {{ $hazard->latitude }}<br>
                                        {{ $hazard->longitude }}
                                    </div>
                                </td>

                                <td>
                                    <form
                                        action="{{ route('admin.hazard-point.destroy', $hazard->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Delete this hazard point?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="hazard-delete-btn">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-hazard-box">
                    No hazard points yet.
                </div>
            @endif
        </div>

        @if($hazardPoints->count())
            <div class="px-4 py-3 border-top hazard-pagination-wrap d-flex justify-content-center">
                {{ $hazardPoints->links() }}
            </div>
        @endif
    </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    const pathFeatures = @json($pathFeatures);
    const hazardPointsMap = @json($hazardPointsMap);

    const map = L.map('hazardMap', {
        zoomControl: true
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let selectedMarker = null;
    let pathLayers = [];

    function getPathStyle(feature) {
        const type = feature.properties?.type || 'walkway';

        if (type === 'stairs') {
            return { color: '#f59e0b', weight: 5, dashArray: '4,6' };
        }

        if (type === 'covered_stairs') {
            return { color: '#1e293b', weight: 8 };
        }

        if (type === 'road') {
            return { color: '#475569', weight: 6 };
        }

        return { color: '#0ea5e9', weight: 5 };
    }

    function warningColor(type) {
        switch (type) {
            case 'broken_road': return 'red';
            case 'slippery': return 'orange';
            case 'stairs': return 'gold';
            case 'construction': return 'black';
            case 'flood': return 'blue';
            case 'caution': return 'violet';
            default: return 'red';
        }
    }

    pathFeatures.forEach(feature => {
        const layer = L.geoJSON(feature, {
            style: getPathStyle(feature),
            onEachFeature: function (feature, layer) {
                const pathId = feature.properties.id;
                const pathName = feature.properties.name || 'Unnamed Path';

                layer.bindPopup(`
                    <strong>${pathName}</strong><br>
                    Type: ${feature.properties.type || 'walkway'}<br>
                    <small>Click this path to place a hazard point.</small>
                `);

                layer.on('click', function (e) {
                    const lat = e.latlng.lat.toFixed(7);
                    const lng = e.latlng.lng.toFixed(7);

                    document.getElementById('path_id').value = pathId;
                    document.getElementById('selected_path_id_text').value = pathId;
                    document.getElementById('selected_path_name').value = pathName;
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;

                    if (selectedMarker) {
                        map.removeLayer(selectedMarker);
                    }

                    selectedMarker = L.marker([lat, lng]).addTo(map)
                        .bindPopup(`<strong>Selected Point</strong><br>${pathName}`)
                        .openPopup();
                });
            }
        }).addTo(map);

        pathLayers.push(layer);
    });

    if (pathLayers.length > 0) {
        const group = L.featureGroup(pathLayers);
        map.fitBounds(group.getBounds(), { padding: [30, 30] });
    } else {
        map.setView([10.2925, 124.9985], 18);
    }

    hazardPointsMap.forEach(point => {
        const marker = L.circleMarker([point.latitude, point.longitude], {
            radius: 8,
            color: warningColor(point.warning_type),
            fillColor: warningColor(point.warning_type),
            fillOpacity: 0.85,
            weight: 2
        }).addTo(map);

        marker.bindPopup(`
            <strong>${point.title}</strong><br>
            Type: ${point.warning_type}<br>
            Severity: ${point.severity_level}<br>
            Path: ${point.path_name || 'No Path'}<br>
            ${point.description ? `<small>${point.description}</small>` : ''}
        `);
    });

    document.querySelectorAll('.editable-hazard').forEach(cell => {
        const isMobile = window.innerWidth <= 768;
        const trigger = isMobile ? 'click' : 'dblclick';

        cell.addEventListener(trigger, () => {
            if (cell.querySelector('.edit-box')) return;

            const url = cell.dataset.url;
            const originalTitle = cell.dataset.title || '';
            const originalDescription = cell.dataset.description || '';
            const originalType = cell.dataset.warning_type || 'hazard';
            const originalSeverity = cell.dataset.severity || '1';
            const originalRouting = cell.dataset.routing || '1';
            const originalActive = cell.dataset.active || '1';

            cell.innerHTML = `
                <div class="edit-box">
                    <input type="text" class="form-control form-control-sm mb-1 edit-title" value="${originalTitle}">
                    <textarea class="form-control form-control-sm mb-1 edit-description" rows="2">${originalDescription}</textarea>

                    <select class="form-select form-select-sm mb-1 edit-type">
                        <option value="hazard">Hazard</option>
                        <option value="broken_road">Broken Road</option>
                        <option value="slippery">Slippery</option>
                        <option value="stairs">Stairs</option>
                        <option value="construction">Construction</option>
                        <option value="flood">Flood</option>
                        <option value="caution">Caution</option>
                    </select>

                    <select class="form-select form-select-sm mb-1 edit-severity">
                        <option value="1">Severity: Low</option>
                        <option value="2">Severity: Medium</option>
                        <option value="3">Severity: High</option>
                    </select>

                    <select class="form-select form-select-sm mb-1 edit-routing">
                        <option value="1">Affects Routing: Yes</option>
                        <option value="0">Affects Routing: No</option>
                    </select>

                    <select class="form-select form-select-sm mb-1 edit-active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            `;

            const titleInput = cell.querySelector('.edit-title');
            const descriptionInput = cell.querySelector('.edit-description');
            const typeSelect = cell.querySelector('.edit-type');
            const severitySelect = cell.querySelector('.edit-severity');
            const routingSelect = cell.querySelector('.edit-routing');
            const activeSelect = cell.querySelector('.edit-active');

            typeSelect.value = originalType;
            severitySelect.value = originalSeverity;
            routingSelect.value = originalRouting;
            activeSelect.value = originalActive;

            titleInput.focus();
            titleInput.select();

            const renderOriginal = () => {
                cell.innerHTML = `
                    <div class="hazard-title">${cell.dataset.title}</div>
                    <div class="hazard-description">${cell.dataset.description || '—'}</div>
                `;
            };

            const save = async () => {
                const payload = {
                    title: titleInput.value.trim(),
                    description: descriptionInput.value.trim(),
                    warning_type: typeSelect.value,
                    severity_level: Number(severitySelect.value),
                    affects_routing: Number(routingSelect.value),
                    is_active: Number(activeSelect.value),
                };

                if (!payload.title) {
                    alert('Title is required.');
                    renderOriginal();
                    return;
                }

                try {
                    const res = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await res.json();

                    if (!res.ok) {
                        throw new Error(data.message || 'Update failed.');
                    }

                    cell.dataset.title = data.title;
                    cell.dataset.description = data.description ?? '';
                    cell.dataset.warning_type = data.warning_type;
                    cell.dataset.severity = data.severity_level;
                    cell.dataset.routing = data.affects_routing ? '1' : '0';
                    cell.dataset.active = data.is_active ? '1' : '0';

                    renderOriginal();
                    window.location.reload();
                } catch (error) {
                    alert(error.message || 'Update failed.');
                    renderOriginal();
                }
            };

            [typeSelect, severitySelect, routingSelect, activeSelect].forEach(el => {
                el.addEventListener('change', save);
            });

            titleInput.addEventListener('keydown', e => {
                if (e.key === 'Enter') save();
                if (e.key === 'Escape') renderOriginal();
            });

            descriptionInput.addEventListener('keydown', e => {
                if (e.key === 'Escape') renderOriginal();
            });

            descriptionInput.addEventListener('blur', save);
        });
    });
</script>

@endsection
