@extends('admin.dashboard')

@section('admin')

<style>
    .indoor-entrance-wrapper {
        padding: 24px;
    }

    .indoor-entrance-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .indoor-entrance-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .indoor-entrance-card-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .indoor-entrance-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .indoor-entrance-form-body {
        padding: 24px;
    }

    .indoor-entrance-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .indoor-entrance-form-control,
    .indoor-entrance-form-select {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }

    .indoor-entrance-form-control:focus,
    .indoor-entrance-form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .indoor-entrance-upload-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .indoor-entrance-upload-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .indoor-entrance-delete-building-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: #fee2e2;
        color: #b91c1c;
    }

    .indoor-entrance-delete-building-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .indoor-entrance-danger-box {
        margin-top: 22px;
        padding-top: 22px;
        border-top: 1px solid #eef2f7;
    }

    .indoor-entrance-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .indoor-entrance-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .indoor-entrance-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .indoor-entrance-table {
        margin-bottom: 0;
    }

    .indoor-entrance-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .indoor-entrance-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .indoor-entrance-id-badge {
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

    .indoor-entrance-name-text {
        font-weight: 800;
        color: #0f172a;
    }

    .indoor-entrance-building-text {
        font-weight: 700;
        color: #334155;
    }

    .indoor-entrance-floor-pill,
    .indoor-entrance-type-pill,
    .indoor-entrance-room-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .indoor-entrance-floor-pill {
        background: #ede9fe;
        color: #6d28d9;
    }

    .indoor-entrance-type-pill {
        background: #dbeafe;
        color: #1d4ed8;
        text-transform: capitalize;
    }

    .indoor-entrance-room-pill {
        background: #dcfce7;
        color: #15803d;
    }

    .indoor-entrance-action-btn {
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
    }

    .indoor-entrance-edit-btn {
        background: #fef3c7;
        color: #92400e;
    }

    .indoor-entrance-edit-btn:hover {
        background: #fde68a;
        color: #78350f;
    }

    .indoor-entrance-delete-btn {
        background: #fee2e2;
        color: #b91c1c;
    }

    .indoor-entrance-delete-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .empty-indoor-entrance-box {
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

    .indoor-entrance-modal .modal-content {
        border: none;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }

    .indoor-entrance-modal .modal-header {
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
        border: none;
        padding: 20px 24px;
    }

    .indoor-entrance-modal .modal-title {
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .indoor-entrance-modal .btn-close {
        filter: invert(1);
        opacity: 0.9;
    }

    .indoor-entrance-modal .modal-body {
        padding: 24px;
    }

    .indoor-entrance-modal .modal-footer {
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
        .indoor-entrance-wrapper {
            padding: 14px;
        }

        .indoor-entrance-card-header,
        .indoor-entrance-form-body,
        .indoor-entrance-table-header {
            padding: 18px;
        }

        .indoor-entrance-table {
            min-width: 1000px;
        }

        .indoor-entrance-upload-btn,
        .indoor-entrance-delete-building-btn {
            width: 100%;
        }
    }
</style>

<div class="indoor-entrance-wrapper">

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
    <div class="indoor-entrance-card">
        <div class="indoor-entrance-card-header">
            <h4>Indoor Entrances Manager</h4>
            <p>Upload indoor entrance points and manage doors, stairs, main entrances, and room links.</p>
        </div>

        <div class="indoor-entrance-form-body">
            {{-- Upload --}}
            <form action="{{ route('admin.indoor-entrances.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label class="indoor-entrance-form-label">Indoor Map</label>
                        <select name="indoor_map_id" class="form-select indoor-entrance-form-select" required>
                            <option value="">Select Indoor Map</option>
                            @foreach($indoorMaps as $map)
                                <option value="{{ $map->id }}">
                                    {{ $map->building->name ?? 'N/A' }} - {{ $map->floor_label ?? ($map->floor_number . 'F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-6">
                        <label class="indoor-entrance-form-label">Upload Indoor Entrances GeoJSON</label>
                        <input
                            type="file"
                            name="geojson"
                            class="form-control indoor-entrance-form-control"
                            accept=".json,.geojson,.txt"
                            required
                        >
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="indoor-entrance-upload-btn">
                            <i class="ri-upload-cloud-2-line me-1"></i>
                            Upload Indoor Entrances
                        </button>
                    </div>
                </div>
            </form>

            {{-- Delete All By Building --}}
            <div class="indoor-entrance-danger-box">
                <form
                    action="{{ route('admin.indoor-entrances.delete-building') }}"
                    method="POST"
                    onsubmit="return confirm('Delete ALL indoor entrances for this building? This cannot be undone.')"
                >
                    @csrf
                    @method('DELETE')

                    <div class="row g-3 align-items-end">
                        <div class="col-lg-6">
                            <label class="indoor-entrance-form-label">Specific Building</label>
                            <select name="building_id" class="form-select indoor-entrance-form-select" required>
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
                            <button type="submit" class="indoor-entrance-delete-building-btn">
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
    <div class="indoor-entrance-table-card">
        <div class="indoor-entrance-table-header">
            <div>
                <h5>Uploaded Indoor Entrances</h5>
                <span class="muted-small">
                    Use Edit button to update entrance name, type, and room code.
                </span>
            </div>

            <span class="muted-small">
                Total Indoor Entrances: {{ $entrances->total() ?? $entrances->count() }}
            </span>
        </div>

        <div class="table-responsive">
            @if($entrances->count())
                <table class="table indoor-entrance-table align-middle">
                    <thead>
                        <tr>
                            <th width="70">ID</th>
                            <th>Building</th>
                            <th width="100">Floor</th>
                            <th>Name</th>
                            <th width="140">Entrance Type</th>
                            <th width="130">Room Code</th>
                            <th width="190">Action</th>
                            <th width="200">Created</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($entrances as $entrance)
                            <tr>
                                <td>
                                    <span class="indoor-entrance-id-badge">
                                        #{{ $entrance->id }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-entrance-building-text">
                                        {{ $entrance->indoorMap->building->name ?? 'N/A' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-entrance-floor-pill">
                                        {{ $entrance->indoorMap->floor_label ?? ($entrance->indoorMap->floor_number . 'F') }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-entrance-name-text">
                                        {{ $entrance->name ?: '—' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-entrance-type-pill">
                                        {{ str_replace('_', ' ', $entrance->ent_type ?: 'door') }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-entrance-room-pill">
                                        {{ $entrance->room_code ?: '—' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button
                                            type="button"
                                            class="indoor-entrance-action-btn indoor-entrance-edit-btn edit-entrance-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editIndoorEntranceModal"
                                            data-id="{{ $entrance->id }}"
                                            data-name="{{ $entrance->name }}"
                                            data-ent_type="{{ $entrance->ent_type }}"
                                            data-room_code="{{ $entrance->room_code }}"
                                        >
                                            Edit
                                        </button>

                                        <form
                                            action="{{ route('admin.indoor-entrances.delete', $entrance->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete this indoor entrance?')"
                                            style="display:inline;"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="indoor-entrance-action-btn indoor-entrance-delete-btn">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                <td>
                                    <div class="indoor-entrance-name-text">
                                        {{ optional($entrance->created_at)->format('M d, Y') }}
                                    </div>
                                    <div class="muted-small">
                                        {{ optional($entrance->created_at)->format('h:i A') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-indoor-entrance-box">
                    No indoor entrances uploaded yet.
                </div>
            @endif
        </div>

        @if($entrances->count())
            <div class="px-4 py-3 border-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="muted-small">
                        Showing {{ $entrances->firstItem() }} to {{ $entrances->lastItem() }}
                        of {{ $entrances->total() }} indoor entrances
                    </div>

                    @if ($entrances->hasPages())
                        <ul class="custom-pagination">
                            @if ($entrances->onFirstPage())
                                <li class="disabled"><span>«</span></li>
                            @else
                                <li><a href="{{ $entrances->previousPageUrl() }}">«</a></li>
                            @endif

                            @foreach ($entrances->getUrlRange(1, $entrances->lastPage()) as $page => $url)
                                @if ($page == $entrances->currentPage())
                                    <li class="active"><span>{{ $page }}</span></li>
                                @else
                                    <li><a href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach

                            @if ($entrances->hasMorePages())
                                <li><a href="{{ $entrances->nextPageUrl() }}">»</a></li>
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
<div class="modal fade indoor-entrance-modal" id="editIndoorEntranceModal" tabindex="-1" aria-labelledby="editIndoorEntranceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editIndoorEntranceForm" method="POST">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="editIndoorEntranceModalLabel">Edit Indoor Entrance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="indoor-entrance-form-label">Entrance Name</label>
                        <input type="text" name="name" id="edit_entrance_name" class="form-control indoor-entrance-form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="indoor-entrance-form-label">Entrance Type</label>
                        <input
                            type="text"
                            name="ent_type"
                            id="edit_ent_type"
                            class="form-control indoor-entrance-form-control"
                            placeholder="door / stairs / main / side"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="indoor-entrance-form-label">Room Code</label>
                        <input
                            type="text"
                            name="room_code"
                            id="edit_room_code"
                            class="form-control indoor-entrance-form-control"
                            placeholder="101 / STAIRS_1F / ENTRANCE"
                        >
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="modal-update-btn">
                        Update Indoor Entrance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-entrance-btn').forEach(button => {
    button.addEventListener('click', function () {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const entType = this.dataset.ent_type;
        const roomCode = this.dataset.room_code;

        const form = document.getElementById('editIndoorEntranceForm');
        form.action = `/admin/indoor-entrances/${id}/update`;

        document.getElementById('edit_entrance_name').value = name ?? '';
        document.getElementById('edit_ent_type').value = entType ?? '';
        document.getElementById('edit_room_code').value = roomCode ?? '';
    });
});
</script>

@endsection
