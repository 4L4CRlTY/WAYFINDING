@extends('admin.dashboard')

@section('admin')

<style>
    .indoor-path-wrapper {
        padding: 24px;
    }

    .indoor-path-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .indoor-path-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .indoor-path-card-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .indoor-path-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .indoor-path-form-body {
        padding: 24px;
    }

    .indoor-path-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .indoor-path-form-control,
    .indoor-path-form-select {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }

    .indoor-path-form-control:focus,
    .indoor-path-form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .indoor-path-upload-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .indoor-path-upload-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .indoor-path-delete-building-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: #fee2e2;
        color: #b91c1c;
    }

    .indoor-path-delete-building-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .indoor-path-danger-box {
        margin-top: 22px;
        padding-top: 22px;
        border-top: 1px solid #eef2f7;
    }

    .indoor-path-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .indoor-path-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .indoor-path-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .indoor-path-table {
        margin-bottom: 0;
    }

    .indoor-path-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .indoor-path-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .indoor-path-id-badge {
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

    .indoor-path-name-text {
        font-weight: 800;
        color: #0f172a;
    }

    .indoor-path-building-text {
        font-weight: 700;
        color: #334155;
    }

    .indoor-path-floor-pill,
    .indoor-path-type-pill,
    .indoor-path-status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .indoor-path-floor-pill {
        background: #ede9fe;
        color: #6d28d9;
    }

    .indoor-path-type-pill {
        background: #dbeafe;
        color: #1d4ed8;
        text-transform: capitalize;
    }

    .status-open {
        background: #dcfce7;
        color: #15803d;
    }

    .status-blocked {
        background: #fee2e2;
        color: #b91c1c;
    }

    .indoor-path-action-btn {
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
    }

    .indoor-path-edit-btn {
        background: #fef3c7;
        color: #92400e;
    }

    .indoor-path-edit-btn:hover {
        background: #fde68a;
        color: #78350f;
    }

    .indoor-path-delete-btn {
        background: #fee2e2;
        color: #b91c1c;
    }

    .indoor-path-delete-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .empty-indoor-path-box {
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

    .indoor-path-modal .modal-content {
        border: none;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }

    .indoor-path-modal .modal-header {
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
        border: none;
        padding: 20px 24px;
    }

    .indoor-path-modal .modal-title {
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .indoor-path-modal .btn-close {
        filter: invert(1);
        opacity: 0.9;
    }

    .indoor-path-modal .modal-body {
        padding: 24px;
    }

    .indoor-path-modal .modal-footer {
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
        .indoor-path-wrapper {
            padding: 14px;
        }

        .indoor-path-card-header,
        .indoor-path-form-body,
        .indoor-path-table-header {
            padding: 18px;
        }

        .indoor-path-table {
            min-width: 980px;
        }

        .indoor-path-upload-btn,
        .indoor-path-delete-building-btn {
            width: 100%;
        }
    }
</style>

<div class="indoor-path-wrapper">

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

    {{-- Upload / Delete Card --}}
    <div class="indoor-path-card">
        <div class="indoor-path-card-header">
            <h4>Indoor Paths Manager</h4>
            <p>Upload indoor hallway/stair path GeoJSON and manage path routing status.</p>
        </div>

        <div class="indoor-path-form-body">
            {{-- Upload --}}
            <form action="{{ route('admin.indoor-path.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label class="indoor-path-form-label">Indoor Map</label>
                        <select name="indoor_map_id" class="form-select indoor-path-form-select" required>
                            <option value="">Select Indoor Map</option>
                            @foreach($indoorMaps as $map)
                                <option value="{{ $map->id }}">
                                    {{ $map->building->name ?? 'N/A' }} - {{ $map->floor_label ?? ($map->floor_number . 'F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-6">
                        <label class="indoor-path-form-label">Upload Indoor Paths GeoJSON</label>
                        <input
                            type="file"
                            name="geojson"
                            class="form-control indoor-path-form-control"
                            accept=".json,.geojson,.txt"
                            required
                        >
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="indoor-path-upload-btn">
                            <i class="ri-upload-cloud-2-line me-1"></i>
                            Upload Indoor Paths
                        </button>
                    </div>
                </div>
            </form>

            {{-- Delete All By Building --}}
            <div class="indoor-path-danger-box">
                <form
                    action="{{ route('admin.indoor-path.delete-building') }}"
                    method="POST"
                    onsubmit="return confirm('Delete ALL indoor paths for this building? This cannot be undone.')"
                >
                    @csrf
                    @method('DELETE')

                    <div class="row g-3 align-items-end">
                        <div class="col-lg-6">
                            <label class="indoor-path-form-label">Specific Building</label>
                            <select name="building_id" class="form-select indoor-path-form-select" required>
                                <option value="">Select Building</option>

                                @php
                                    $shownBuildings = [];
                                @endphp

                                @foreach($indoorMaps as $map)
                                    @if($map->building && !in_array($map->building->id, $shownBuildings))
                                        <option value="{{ $map->building->id }}">
                                            {{ $map->building->name }}
                                        </option>

                                        @php
                                            $shownBuildings[] = $map->building->id;
                                        @endphp
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-6 d-flex justify-content-end">
                            <button type="submit" class="indoor-path-delete-building-btn">
                                <i class="ri-delete-bin-6-line me-1"></i>
                                Delete All for Selected Building
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="indoor-path-table-card">
        <div class="indoor-path-table-header">
            <div>
                <h5>Uploaded Indoor Paths</h5>
                <span class="muted-small">
                    Use Edit button to update path name, type, and blocked status.
                </span>
            </div>

            <span class="muted-small">
                Total Indoor Paths: {{ $paths->total() ?? $paths->count() }}
            </span>
        </div>

        <div class="table-responsive">
            @if($paths->count())
                <table class="table indoor-path-table align-middle">
                    <thead>
                        <tr>
                            <th width="70">ID</th>
                            <th>Building</th>
                            <th width="100">Floor</th>
                            <th>Name</th>
                            <th width="130">Path Type</th>
                            <th width="120">Blocked</th>
                            <th width="190">Action</th>
                            <th width="200">Created</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($paths as $path)
                            <tr>
                                <td>
                                    <span class="indoor-path-id-badge">
                                        #{{ $path->id }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-path-building-text">
                                        {{ $path->indoorMap->building->name ?? 'N/A' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-path-floor-pill">
                                        {{ $path->indoorMap->floor_label ?? ($path->indoorMap->floor_number . 'F') }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-path-name-text">
                                        {{ $path->name ?: '—' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-path-type-pill">
                                        {{ str_replace('_', ' ', $path->path_type ?: 'hallway') }}
                                    </span>
                                </td>

                                <td>
                                    @if($path->is_blocked)
                                        <span class="indoor-path-status-pill status-blocked">Yes</span>
                                    @else
                                        <span class="indoor-path-status-pill status-open">No</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button
                                            type="button"
                                            class="indoor-path-action-btn indoor-path-edit-btn edit-path-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editIndoorPathModal"
                                            data-id="{{ $path->id }}"
                                            data-name="{{ $path->name }}"
                                            data-path_type="{{ $path->path_type }}"
                                            data-is_blocked="{{ $path->is_blocked ? 1 : 0 }}"
                                        >
                                            Edit
                                        </button>

                                        <form
                                            action="{{ route('admin.indoor-path.delete', $path->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete this indoor path?')"
                                            style="display:inline;"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="indoor-path-action-btn indoor-path-delete-btn">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                <td>
                                    <div class="indoor-path-name-text">
                                        {{ optional($path->created_at)->format('M d, Y') }}
                                    </div>
                                    <div class="muted-small">
                                        {{ optional($path->created_at)->format('h:i A') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-indoor-path-box">
                    No indoor paths uploaded yet.
                </div>
            @endif
        </div>

        @if($paths->count())
            <div class="px-4 py-3 border-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="muted-small">
                        Showing {{ $paths->firstItem() }} to {{ $paths->lastItem() }}
                        of {{ $paths->total() }} indoor paths
                    </div>

                    @if ($paths->hasPages())
                        <ul class="custom-pagination">
                            @if ($paths->onFirstPage())
                                <li class="disabled"><span>«</span></li>
                            @else
                                <li><a href="{{ $paths->previousPageUrl() }}">«</a></li>
                            @endif

                            @foreach ($paths->getUrlRange(1, $paths->lastPage()) as $page => $url)
                                @if ($page == $paths->currentPage())
                                    <li class="active"><span>{{ $page }}</span></li>
                                @else
                                    <li><a href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach

                            @if ($paths->hasMorePages())
                                <li><a href="{{ $paths->nextPageUrl() }}">»</a></li>
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
<div class="modal fade indoor-path-modal" id="editIndoorPathModal" tabindex="-1" aria-labelledby="editIndoorPathModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editIndoorPathForm" method="POST">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="editIndoorPathModalLabel">Edit Indoor Path</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="indoor-path-form-label">Path Name</label>
                        <input type="text" name="name" id="edit_path_name" class="form-control indoor-path-form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="indoor-path-form-label">Path Type</label>
                        <input type="text" name="path_type" id="edit_path_type" class="form-control indoor-path-form-control" placeholder="hallway / stairs_access">
                    </div>

                    <div class="mb-3">
                        <label class="indoor-path-form-label">Blocked</label>
                        <select name="is_blocked" id="edit_is_blocked" class="form-select indoor-path-form-select" required>
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="modal-update-btn">
                        Update Indoor Path
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-path-btn').forEach(button => {
    button.addEventListener('click', function () {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const pathType = this.dataset.path_type;
        const isBlocked = this.dataset.is_blocked;

        const form = document.getElementById('editIndoorPathForm');
        form.action = `/admin/indoor-path/${id}/update`;

        document.getElementById('edit_path_name').value = name ?? '';
        document.getElementById('edit_path_type').value = pathType ?? '';
        document.getElementById('edit_is_blocked').value = isBlocked ?? '0';
    });
});
</script>

@endsection
