@extends('admin.dashboard')

@section('admin')

<style>
    .indoor-room-wrapper {
        padding: 24px;
    }

    .indoor-room-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .indoor-room-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .indoor-room-card-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .indoor-room-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .indoor-room-form-body {
        padding: 24px;
    }

    .indoor-room-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .indoor-room-form-control,
    .indoor-room-form-select {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }

    .indoor-room-form-control:focus,
    .indoor-room-form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .indoor-room-upload-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .indoor-room-upload-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .indoor-room-reset-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: #fee2e2;
        color: #b91c1c;
    }

    .indoor-room-reset-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .indoor-room-reset-box {
        margin-top: 22px;
        padding-top: 22px;
        border-top: 1px solid #eef2f7;
    }

    .indoor-room-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .indoor-room-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .indoor-room-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .indoor-room-table {
        margin-bottom: 0;
    }

    .indoor-room-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .indoor-room-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .indoor-room-id-badge {
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

    .indoor-room-name-text {
        font-weight: 800;
        color: #0f172a;
    }

    .indoor-room-building-text {
        font-weight: 700;
        color: #334155;
    }

    .indoor-room-floor-pill,
    .indoor-room-code-pill,
    .indoor-room-type-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .indoor-room-floor-pill {
        background: #ede9fe;
        color: #6d28d9;
    }

    .indoor-room-code-pill {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .indoor-room-type-pill {
        background: #dcfce7;
        color: #15803d;
        text-transform: capitalize;
    }

    .indoor-room-edit-btn {
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
        background: #fef3c7;
        color: #92400e;
    }

    .indoor-room-edit-btn:hover {
        background: #fde68a;
        color: #78350f;
    }

    .empty-indoor-room-box {
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

    .indoor-room-modal .modal-content {
        border: none;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }

    .indoor-room-modal .modal-header {
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
        border: none;
        padding: 20px 24px;
    }

    .indoor-room-modal .modal-title {
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .indoor-room-modal .btn-close {
        filter: invert(1);
        opacity: 0.9;
    }

    .indoor-room-modal .modal-body {
        padding: 24px;
    }

    .indoor-room-modal .modal-footer {
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
        .indoor-room-wrapper {
            padding: 14px;
        }

        .indoor-room-card-header,
        .indoor-room-form-body,
        .indoor-room-table-header {
            padding: 18px;
        }

        .indoor-room-table {
            min-width: 980px;
        }

        .indoor-room-upload-btn,
        .indoor-room-reset-btn {
            width: 100%;
        }
    }
</style>

<div class="indoor-room-wrapper">

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
    <div class="indoor-room-card">
        <div class="indoor-room-card-header">
            <h4>Indoor Rooms Manager</h4>
            <p>Upload room polygons for indoor maps and manage room names, codes, and types.</p>
        </div>

        <div class="indoor-room-form-body">
            {{-- Upload --}}
            <form action="{{ route('admin.indoor-room.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label class="indoor-room-form-label">Indoor Map</label>
                        <select name="indoor_map_id" class="form-select indoor-room-form-select" required>
                            <option value="">Select Indoor Map</option>
                            @foreach($indoorMaps as $map)
                                <option value="{{ $map->id }}">
                                    {{ $map->building->name ?? 'N/A' }} - {{ $map->floor_label ?? ($map->floor_number . 'F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-6">
                        <label class="indoor-room-form-label">Upload Indoor Rooms GeoJSON</label>
                        <input
                            type="file"
                            name="geojson"
                            class="form-control indoor-room-form-control"
                            accept=".json,.geojson,.txt"
                            required
                        >
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="indoor-room-upload-btn">
                            <i class="ri-upload-cloud-2-line me-1"></i>
                            Upload Indoor Rooms
                        </button>
                    </div>
                </div>
            </form>

            {{-- Reset --}}
            <div class="indoor-room-reset-box">
                <form
                    action="{{ route('admin.indoor-room.reset') }}"
                    method="POST"
                    onsubmit="return confirm('Restore previous indoor rooms upload?')"
                >
                    @csrf
                    @method('DELETE')

                    <div class="row g-3 align-items-end">
                        <div class="col-lg-6">
                            <label class="indoor-room-form-label">Indoor Map</label>
                            <select name="indoor_map_id" class="form-select indoor-room-form-select" required>
                                <option value="">Select Indoor Map</option>
                                @foreach($indoorMaps as $map)
                                    <option value="{{ $map->id }}">
                                        {{ $map->building->name ?? 'N/A' }} - {{ $map->floor_label ?? ($map->floor_number . 'F') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-6 d-flex justify-content-end">
                            <button type="submit" class="indoor-room-reset-btn">
                                <i class="ri-refresh-line me-1"></i>
                                Reset Indoor Rooms
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="indoor-room-table-card">
        <div class="indoor-room-table-header">
            <div>
                <h5>Uploaded Indoor Rooms</h5>
                <span class="muted-small">
                    Use Edit button to update room name, room code, and room type.
                </span>
            </div>

            <span class="muted-small">
                Total Indoor Rooms: {{ $rooms->total() ?? $rooms->count() }}
            </span>
        </div>

        <div class="table-responsive">
            @if($rooms->count())
                <table class="table indoor-room-table align-middle">
                    <thead>
                        <tr>
                            <th width="70">ID</th>
                            <th>Building</th>
                            <th width="100">Floor</th>
                            <th>Name</th>
                            <th width="130">Room Code</th>
                            <th width="130">Type</th>
                            <th width="110">Action</th>
                            <th width="200">Created</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($rooms as $room)
                            <tr>
                                <td>
                                    <span class="indoor-room-id-badge">
                                        #{{ $room->id }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-room-building-text">
                                        {{ $room->indoorMap->building->name ?? 'N/A' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-room-floor-pill">
                                        {{ $room->indoorMap->floor_label ?? ($room->indoorMap->floor_number . 'F') }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-room-name-text">
                                        {{ $room->name ?: '—' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-room-code-pill">
                                        {{ $room->room_code ?: '—' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="indoor-room-type-pill">
                                        {{ str_replace('_', ' ', $room->type ?: 'room') }}
                                    </span>
                                </td>

                                <td>
                                    <button
                                        type="button"
                                        class="indoor-room-edit-btn edit-room-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editIndoorRoomModal"
                                        data-id="{{ $room->id }}"
                                        data-name="{{ $room->name }}"
                                        data-room_code="{{ $room->room_code }}"
                                        data-type="{{ $room->type }}"
                                    >
                                        Edit
                                    </button>
                                </td>

                                <td>
                                    <div class="indoor-room-name-text">
                                        {{ optional($room->created_at)->format('M d, Y') }}
                                    </div>
                                    <div class="muted-small">
                                        {{ optional($room->created_at)->format('h:i A') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-indoor-room-box">
                    No indoor rooms uploaded yet.
                </div>
            @endif
        </div>

        @if($rooms->count())
            <div class="px-4 py-3 border-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="muted-small">
                        Showing {{ $rooms->firstItem() }} to {{ $rooms->lastItem() }}
                        of {{ $rooms->total() }} indoor rooms
                    </div>

                    @if ($rooms->hasPages())
                        <ul class="custom-pagination">
                            @if ($rooms->onFirstPage())
                                <li class="disabled"><span>«</span></li>
                            @else
                                <li><a href="{{ $rooms->previousPageUrl() }}">«</a></li>
                            @endif

                            @foreach ($rooms->getUrlRange(1, $rooms->lastPage()) as $page => $url)
                                @if ($page == $rooms->currentPage())
                                    <li class="active"><span>{{ $page }}</span></li>
                                @else
                                    <li><a href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach

                            @if ($rooms->hasMorePages())
                                <li><a href="{{ $rooms->nextPageUrl() }}">»</a></li>
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
<div class="modal fade indoor-room-modal" id="editIndoorRoomModal" tabindex="-1" aria-labelledby="editIndoorRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editIndoorRoomForm" method="POST">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="editIndoorRoomModalLabel">Edit Indoor Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="indoor-room-form-label">Room Name</label>
                        <input type="text" name="name" id="edit_room_name" class="form-control indoor-room-form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="indoor-room-form-label">Room Code</label>
                        <input type="text" name="room_code" id="edit_room_code" class="form-control indoor-room-form-control">
                    </div>

                    <div class="mb-3">
                        <label class="indoor-room-form-label">Type</label>
                        <input
                            type="text"
                            name="type"
                            id="edit_room_type"
                            class="form-control indoor-room-form-control"
                            placeholder="classroom / office / restroom"
                        >
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="modal-update-btn">
                        Update Indoor Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-room-btn').forEach(button => {
    button.addEventListener('click', function () {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const roomCode = this.dataset.room_code;
        const type = this.dataset.type;

        const form = document.getElementById('editIndoorRoomForm');
        form.action = `/admin/indoor-room/${id}/update`;

        document.getElementById('edit_room_name').value = name ?? '';
        document.getElementById('edit_room_code').value = roomCode ?? '';
        document.getElementById('edit_room_type').value = type ?? '';
    });
});
</script>

@endsection
