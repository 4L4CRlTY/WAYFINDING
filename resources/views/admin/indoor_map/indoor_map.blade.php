@extends('admin.dashboard')

@section('admin')

<style>
    .indoor-wrapper {
        padding: 24px;
    }

    .indoor-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .indoor-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .indoor-card-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .indoor-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .indoor-form-body {
        padding: 24px;
    }

    .indoor-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .indoor-form-control,
    .indoor-form-select {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }

    .indoor-form-control:focus,
    .indoor-form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .indoor-upload-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .indoor-upload-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .indoor-reset-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: #fee2e2;
        color: #b91c1c;
    }

    .indoor-reset-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .indoor-reset-box {
        margin-top: 22px;
        padding-top: 22px;
        border-top: 1px solid #eef2f7;
    }

    .indoor-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .indoor-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .indoor-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .indoor-table {
        margin-bottom: 0;
    }

    .indoor-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .indoor-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .indoor-id-badge {
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

    .indoor-name-text {
        font-weight: 800;
        color: #0f172a;
    }

    .indoor-building-text {
        font-weight: 700;
        color: #334155;
    }

    .indoor-floor-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        background: #ede9fe;
        color: #6d28d9;
        white-space: nowrap;
    }

    .indoor-label-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        background: #dbeafe;
        color: #1d4ed8;
        white-space: nowrap;
    }

    .indoor-preview-img {
        width: 120px;
        height: 80px;
        object-fit: cover;
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        background: white;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    }

    .indoor-no-image {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        background: #f1f5f9;
        color: #475569;
    }

    .geometry-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .geometry-yes {
        background: #dcfce7;
        color: #15803d;
    }

    .geometry-no {
        background: #f1f5f9;
        color: #475569;
    }

    .indoor-edit-btn {
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
        background: #fef3c7;
        color: #92400e;
    }

    .indoor-edit-btn:hover {
        background: #fde68a;
        color: #78350f;
    }

    .empty-indoor-box {
        padding: 36px;
        text-align: center;
        color: #64748b;
    }

    .custom-pagination {
        display: flex;
        gap: 8px;
        list-style: none;
        padding: 0;
        margin: 0;
        flex-wrap: wrap;
    }

    .custom-pagination li a,
    .custom-pagination li span {
        min-width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 14px;
        border-radius: 12px;
        background: #f1f5f9;
        text-decoration: none;
        color: #334155;
        font-weight: 800;
        transition: 0.2s;
    }

    .custom-pagination li a:hover {
        background: #2563eb;
        color: white;
        transform: translateY(-2px);
    }

    .custom-pagination .active span {
        background: #2563eb;
        color: white;
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.22);
    }

    .custom-pagination .disabled span {
        background: #e2e8f0;
        color: #94a3b8;
    }

    .indoor-modal .modal-content {
        border: none;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }

    .indoor-modal .modal-header {
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
        border: none;
        padding: 20px 24px;
    }

    .indoor-modal .modal-title {
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .indoor-modal .btn-close {
        filter: invert(1);
        opacity: 0.9;
    }

    .indoor-modal .modal-body {
        padding: 24px;
    }

    .indoor-modal .modal-footer {
        border-top: 1px solid #eef2f7;
        padding: 16px 24px;
    }

    .modal-update-btn {
        border: none;
        border-radius: 14px;
        padding: 10px 16px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
    }

    .modal-update-btn:hover {
        opacity: 0.94;
        color: white;
    }

    @media (max-width: 768px) {
        .indoor-wrapper {
            padding: 14px;
        }

        .indoor-card-header,
        .indoor-form-body,
        .indoor-table-header {
            padding: 18px;
        }

        .indoor-table {
            min-width: 1080px;
        }

        .indoor-upload-btn,
        .indoor-reset-btn {
            width: 100%;
        }

        .indoor-preview-img {
            width: 100px;
            height: 70px;
        }
    }
</style>

<div class="indoor-wrapper">

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

    {{-- Upload / Reset Card --}}
    <div class="indoor-card">
        <div class="indoor-card-header">
            <h4>Indoor Maps Manager</h4>
            <p>Upload floorplan images and floor extent GeoJSON for indoor navigation maps.</p>
        </div>

        <div class="indoor-form-body">
            {{-- Upload --}}
            <form action="{{ route('admin.indoor-map.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="indoor-form-label">Building</label>
                        <select name="building_id" class="form-select indoor-form-select" required>
                            <option value="">Select Building</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}">{{ $building->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="indoor-form-label">Floor #</label>
                        <input type="number" name="floor_number" class="form-control indoor-form-control" required min="0">
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="indoor-form-label">Label</label>
                        <input type="text" name="floor_label" class="form-control indoor-form-control" placeholder="1F">
                    </div>

                    <div class="col-lg-4 col-md-4">
                        <label class="indoor-form-label">Name</label>
                        <input type="text" name="name" class="form-control indoor-form-control" placeholder="IT Building 1F">
                    </div>

                    <div class="col-lg-6">
                        <label class="indoor-form-label">Floorplan Image</label>
                        <input
                            type="file"
                            name="floorplan_image"
                            class="form-control indoor-form-control"
                            accept=".jpg,.jpeg,.png,.webp"
                            required
                        >
                    </div>

                    <div class="col-lg-6">
                        <label class="indoor-form-label">Floor Extent GeoJSON Polygon</label>
                        <input
                            type="file"
                            name="geometry_file"
                            class="form-control indoor-form-control"
                            accept=".geojson,.json"
                            required
                        >
                        <div class="muted-small mt-1">
                            Upload your rectangle floor extent GeoJSON gikan sa QGIS.
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="indoor-upload-btn">
                            <i class="ri-upload-cloud-2-line me-1"></i>
                            Upload Indoor Map
                        </button>
                    </div>
                </div>
            </form>

            {{-- Reset --}}
            <div class="indoor-reset-box">
                <form
                    action="{{ route('admin.indoor-map.reset') }}"
                    method="POST"
                    onsubmit="return confirm('Restore previous upload?')"
                >
                    @csrf
                    @method('DELETE')

                    <div class="row g-3 align-items-end">
                        <div class="col-lg-4">
                            <label class="indoor-form-label">Building</label>
                            <select name="building_id" class="form-select indoor-form-select" required>
                                <option value="">Select Building</option>
                                @foreach($buildings as $building)
                                    <option value="{{ $building->id }}">{{ $building->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <label class="indoor-form-label">Floor</label>
                            <input type="number" name="floor_number" class="form-control indoor-form-control" required min="0">
                        </div>

                        <div class="col-lg-6 d-flex justify-content-end">
                            <button type="submit" class="indoor-reset-btn">
                                <i class="ri-refresh-line me-1"></i>
                                Reset Indoor Map
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="indoor-table-card">
        <div class="indoor-table-header">
            <div>
                <h5>Indoor Maps List</h5>
                <span class="muted-small">
                    Use the Edit button to update details, replace image, or replace GeoJSON.
                </span>
            </div>

            <span class="muted-small">
                Total Indoor Maps: {{ $maps->total() ?? $maps->count() }}
            </span>
        </div>

        <div class="table-responsive">
            @if($maps->count())
                <table class="table indoor-table align-middle">
                    <thead>
                        <tr>
                            <th width="70">ID</th>
                            <th>Building</th>
                            <th width="90">Floor</th>
                            <th width="100">Label</th>
                            <th>Name</th>
                            <th width="180">Preview</th>
                            <th width="130">Geometry</th>
                            <th width="120">Action</th>
                            <th width="200">Created</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($maps as $map)
                            <tr>
                                <td>
                                    <span class="indoor-id-badge">
                                        #{{ $map->id }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-building-text">
                                        {{ $map->building->name ?? '' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-floor-pill">
                                        Floor {{ $map->floor_number }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-label-pill">
                                        {{ $map->floor_label ?: '—' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-name-text">
                                        {{ $map->name ?: '—' }}
                                    </span>
                                </td>

                                <td>
                                    @if($map->floorplan_image)
                                        <img
                                            src="{{ asset('floorplan_image/' . $map->floorplan_image) }}"
                                            alt="Floorplan"
                                            class="indoor-preview-img"
                                        >
                                    @else
                                        <span class="indoor-no-image">No Image</span>
                                    @endif
                                </td>

                                <td>
                                    @if($map->geometry)
                                        <span class="geometry-pill geometry-yes">Has Geometry</span>
                                    @else
                                        <span class="geometry-pill geometry-no">No Geometry</span>
                                    @endif
                                </td>

                                <td>
                                    <button
                                        type="button"
                                        class="indoor-edit-btn edit-map-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editIndoorMapModal"
                                        data-id="{{ $map->id }}"
                                        data-building_id="{{ $map->building_id }}"
                                        data-floor_number="{{ $map->floor_number }}"
                                        data-floor_label="{{ $map->floor_label }}"
                                        data-name="{{ $map->name }}"
                                    >
                                        Edit
                                    </button>
                                </td>

                                <td>
                                    <div class="indoor-name-text">
                                        {{ optional($map->created_at)->format('M d, Y') }}
                                    </div>
                                    <div class="muted-small">
                                        {{ optional($map->created_at)->format('h:i A') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-indoor-box">
                    No indoor maps uploaded yet.
                </div>
            @endif
        </div>

        @if($maps->count())
            <div class="px-4 py-3 border-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="muted-small">
                        Showing {{ $maps->firstItem() }} to {{ $maps->lastItem() }}
                        of {{ $maps->total() }} indoor maps
                    </div>

                    @if ($maps->hasPages())
                        <ul class="custom-pagination">
                            @if ($maps->onFirstPage())
                                <li class="disabled"><span>«</span></li>
                            @else
                                <li><a href="{{ $maps->previousPageUrl() }}">«</a></li>
                            @endif

                            @foreach ($maps->getUrlRange(1, $maps->lastPage()) as $page => $url)
                                @if ($page == $maps->currentPage())
                                    <li class="active"><span>{{ $page }}</span></li>
                                @else
                                    <li><a href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach

                            @if ($maps->hasMorePages())
                                <li><a href="{{ $maps->nextPageUrl() }}">»</a></li>
                            @else
                                <li class="disabled"><span>»</span></li>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade indoor-modal" id="editIndoorMapModal" tabindex="-1" aria-labelledby="editIndoorMapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editIndoorMapForm" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="editIndoorMapModalLabel">Edit Indoor Map</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="indoor-form-label">Building</label>
                            <select name="building_id" id="edit_building_id" class="form-select indoor-form-select" required>
                                <option value="">Select Building</option>
                                @foreach($buildings as $building)
                                    <option value="{{ $building->id }}">{{ $building->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <label class="indoor-form-label">Floor #</label>
                            <input
                                type="number"
                                name="floor_number"
                                id="edit_floor_number"
                                class="form-control indoor-form-control"
                                required
                                min="0"
                            >
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <label class="indoor-form-label">Label</label>
                            <input
                                type="text"
                                name="floor_label"
                                id="edit_floor_label"
                                class="form-control indoor-form-control"
                                placeholder="1F"
                            >
                        </div>

                        <div class="col-lg-4 col-md-4">
                            <label class="indoor-form-label">Name</label>
                            <input
                                type="text"
                                name="name"
                                id="edit_name"
                                class="form-control indoor-form-control"
                                placeholder="IT Building 1F"
                            >
                        </div>

                        <div class="col-lg-6">
                            <label class="indoor-form-label">Replace Floorplan Image</label>
                            <input
                                type="file"
                                name="floorplan_image"
                                class="form-control indoor-form-control"
                                accept=".jpg,.jpeg,.png,.webp"
                            >
                            <div class="muted-small mt-1">
                                Leave blank if you do not want to change the image.
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <label class="indoor-form-label">Replace Floor Extent GeoJSON</label>
                            <input
                                type="file"
                                name="geometry_file"
                                class="form-control indoor-form-control"
                                accept=".geojson,.json"
                            >
                            <div class="muted-small mt-1">
                                Leave blank if you do not want to change the GeoJSON.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="modal-update-btn">
                        Update Indoor Map
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-map-btn').forEach(button => {
    button.addEventListener('click', function () {
        const id = this.dataset.id;
        const buildingId = this.dataset.building_id;
        const floorNumber = this.dataset.floor_number;
        const floorLabel = this.dataset.floor_label;
        const name = this.dataset.name;

        const form = document.getElementById('editIndoorMapForm');
        form.action = `/admin/indoor-map/${id}/update`;

        document.getElementById('edit_building_id').value = buildingId;
        document.getElementById('edit_floor_number').value = floorNumber;
        document.getElementById('edit_floor_label').value = floorLabel ?? '';
        document.getElementById('edit_name').value = name ?? '';
    });
});
</script>

@endsection
