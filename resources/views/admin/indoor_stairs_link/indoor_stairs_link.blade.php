@extends('admin.dashboard')

@section('admin')

<style>
    .stairs-link-wrapper {
        padding: 24px;
    }

    .stairs-link-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .stairs-link-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .stairs-link-card-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .stairs-link-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .stairs-link-form-body {
        padding: 24px;
    }

    .stairs-link-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .stairs-link-form-control,
    .stairs-link-form-select {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }



    .stairs-link-select-hint {
        margin-top: 7px;
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
    }

    .stairs-link-form-select option[hidden] {
        display: none;
    }

    .stairs-link-form-control:focus,
    .stairs-link-form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .stairs-link-submit-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .stairs-link-submit-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .stairs-link-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .stairs-link-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .stairs-link-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .stairs-link-table {
        margin-bottom: 0;
    }

    .stairs-link-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .stairs-link-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .stairs-link-id-badge {
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

    .stairs-link-name-text {
        font-weight: 800;
        color: #0f172a;
    }

    .stairs-link-building-text {
        font-weight: 700;
        color: #334155;
    }

    .stairs-link-entrance-box {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .stairs-link-entrance-name {
        font-weight: 800;
        color: #0f172a;
    }

    .stairs-link-floor-pill {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        border-radius: 999px;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 800;
        background: #ede9fe;
        color: #6d28d9;
    }

    .stairs-link-action-btn {
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
    }

    .stairs-link-edit-btn {
        background: #fef3c7;
        color: #92400e;
    }

    .stairs-link-edit-btn:hover {
        background: #fde68a;
        color: #78350f;
    }

    .stairs-link-delete-btn {
        background: #fee2e2;
        color: #b91c1c;
    }

    .stairs-link-delete-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .empty-stairs-link-box {
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

    .stairs-link-modal .modal-content {
        border: none;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }

    .stairs-link-modal .modal-header {
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
        border: none;
        padding: 20px 24px;
    }

    .stairs-link-modal .modal-title {
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .stairs-link-modal .btn-close {
        filter: invert(1);
        opacity: 0.9;
    }

    .stairs-link-modal .modal-body {
        padding: 24px;
    }

    .stairs-link-modal .modal-footer {
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
        .stairs-link-wrapper {
            padding: 14px;
        }

        .stairs-link-card-header,
        .stairs-link-form-body,
        .stairs-link-table-header {
            padding: 18px;
        }

        .stairs-link-table {
            min-width: 1050px;
        }

        .stairs-link-submit-btn {
            width: 100%;
        }
    }
</style>

<div class="stairs-link-wrapper">

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

    {{-- Add Form --}}
    <div class="stairs-link-card">
        <div class="stairs-link-card-header">
            <h4>Indoor Stairs Links Manager</h4>
            <p>Create stair connections between indoor entrances across floors.</p>
        </div>

        <div class="stairs-link-form-body">
            <form action="{{ route('admin.indoor-stairs-link.store') }}" method="POST">
                @csrf

                @include('admin.partials.building_map_selector', [
                    'selectorKey' => 'stairs_link_add',
                    'selectId' => 'add_building_id',
                    'dropdownFieldId' => 'stairs_link_add_building_field',
                    'buildingMapData' => $buildingMapData,
                    'selectedBuildingId' => old('building_id'),
                ])

                <div class="row g-3">
                    <div class="col-lg-4" id="stairs_link_add_building_field">
                        <label class="stairs-link-form-label">Building</label>
                        <select name="building_id" id="add_building_id" class="form-select stairs-link-form-select js-building-select" data-target-from="add_from_entrance_id" data-target-to="add_to_entrance_id" required>
                            <option value="">Select Building</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}">{{ $building->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <label class="stairs-link-form-label">From Stairs Entrance</label>
                        <select name="from_entrance_id" id="add_from_entrance_id" class="form-select stairs-link-form-select js-stairs-entrance-select" required>
                            <option value="">Select From Entrance</option>
                            @foreach($stairsEntrances as $entrance)
                                <option value="{{ $entrance->id }}" data-building-id="{{ $entrance->indoorMap->building_id ?? '' }}">
                                    {{ $entrance->name }}
                                    -
                                    {{ $entrance->indoorMap->building->name ?? 'N/A' }}
                                    -
                                    {{ $entrance->indoorMap->floor_label ?? ($entrance->indoorMap->floor_number . 'F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <label class="stairs-link-form-label">To Stairs Entrance</label>
                        <select name="to_entrance_id" id="add_to_entrance_id" class="form-select stairs-link-form-select js-stairs-entrance-select" required>
                            <option value="">Select To Entrance</option>
                            @foreach($stairsEntrances as $entrance)
                                <option value="{{ $entrance->id }}" data-building-id="{{ $entrance->indoorMap->building_id ?? '' }}">
                                    {{ $entrance->name }}
                                    -
                                    {{ $entrance->indoorMap->building->name ?? 'N/A' }}
                                    -
                                    {{ $entrance->indoorMap->floor_label ?? ($entrance->indoorMap->floor_number . 'F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-6">
                        <label class="stairs-link-form-label">Link Name</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control stairs-link-form-control"
                            placeholder="Main Stairs Link"
                        >
                    </div>

                    <div class="col-lg-6 d-flex align-items-end justify-content-end">
                        <button type="submit" class="stairs-link-submit-btn">
                            <i class="ri-links-line me-1"></i>
                            Add Stairs Link
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="stairs-link-table-card">
        <div class="stairs-link-table-header">
            <div>
                <h5>Stairs Links List</h5>
                <span class="muted-small">
                    Use Edit button to update stairs connection.
                </span>
            </div>

            <span class="muted-small">
                Total Stairs Links: {{ $links->total() ?? $links->count() }}
            </span>
        </div>

        <div class="table-responsive">
            @if($links->count())
                <table class="table stairs-link-table align-middle">
                    <thead>
                        <tr>
                            <th width="70">ID</th>
                            <th>Building</th>
                            <th>Name</th>
                            <th>From Entrance</th>
                            <th>To Entrance</th>
                            <th width="140">Action</th>
                            <th width="200">Created</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($links as $link)
                            <tr>
                                <td>
                                    <span class="stairs-link-id-badge">
                                        #{{ $link->id }}
                                    </span>
                                </td>

                                <td>
                                    <span class="stairs-link-building-text">
                                        {{ $link->building->name ?? 'N/A' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="stairs-link-name-text">
                                        {{ $link->name ?: '—' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="stairs-link-entrance-box">
                                        <span class="stairs-link-entrance-name">
                                            {{ $link->fromEntrance->name ?? 'N/A' }}
                                        </span>

                                        @if($link->fromEntrance && $link->fromEntrance->indoorMap)
                                            <span class="stairs-link-floor-pill">
                                                {{ $link->fromEntrance->indoorMap->floor_label ?? ($link->fromEntrance->indoorMap->floor_number . 'F') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="stairs-link-entrance-box">
                                        <span class="stairs-link-entrance-name">
                                            {{ $link->toEntrance->name ?? 'N/A' }}
                                        </span>

                                        @if($link->toEntrance && $link->toEntrance->indoorMap)
                                            <span class="stairs-link-floor-pill">
                                                {{ $link->toEntrance->indoorMap->floor_label ?? ($link->toEntrance->indoorMap->floor_number . 'F') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button
                                            type="button"
                                            class="stairs-link-action-btn stairs-link-edit-btn edit-link-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editIndoorStairsLinkModal"
                                            data-id="{{ $link->id }}"
                                            data-building_id="{{ $link->building_id }}"
                                            data-from_entrance_id="{{ $link->from_entrance_id }}"
                                            data-to_entrance_id="{{ $link->to_entrance_id }}"
                                            data-name="{{ $link->name }}"
                                        >
                                            Edit
                                        </button>

                                        <form
                                            action="{{ route('admin.indoor-stairs-link.delete', $link->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete this stairs link?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="stairs-link-action-btn stairs-link-delete-btn">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                <td>
                                    <div class="stairs-link-name-text">
                                        {{ optional($link->created_at)->format('M d, Y') }}
                                    </div>
                                    <div class="muted-small">
                                        {{ optional($link->created_at)->format('h:i A') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-stairs-link-box">
                    No indoor stairs links created yet.
                </div>
            @endif
        </div>

        @include('admin.partials.pagination', [
            'paginator' => $links,
            'label' => 'stairs links',
        ])
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade stairs-link-modal" id="editIndoorStairsLinkModal" tabindex="-1" aria-labelledby="editIndoorStairsLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editIndoorStairsLinkForm" method="POST">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="editIndoorStairsLinkModalLabel">Edit Indoor Stairs Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="stairs-link-form-label">Building</label>
                            <select name="building_id" id="edit_building_id" class="form-select stairs-link-form-select js-building-select" data-target-from="edit_from_entrance_id" data-target-to="edit_to_entrance_id" required>
                                <option value="">Select Building</option>
                                @foreach($buildings as $building)
                                    <option value="{{ $building->id }}">{{ $building->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-4">
                            <label class="stairs-link-form-label">From Stairs Entrance</label>
                            <select name="from_entrance_id" id="edit_from_entrance_id" class="form-select stairs-link-form-select js-stairs-entrance-select" required>
                                <option value="">Select From Entrance</option>
                                @foreach($stairsEntrances as $entrance)
                                    <option value="{{ $entrance->id }}" data-building-id="{{ $entrance->indoorMap->building_id ?? '' }}">
                                        {{ $entrance->name }}
                                        -
                                        {{ $entrance->indoorMap->building->name ?? 'N/A' }}
                                        -
                                        {{ $entrance->indoorMap->floor_label ?? ($entrance->indoorMap->floor_number . 'F') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-4">
                            <label class="stairs-link-form-label">To Stairs Entrance</label>
                            <select name="to_entrance_id" id="edit_to_entrance_id" class="form-select stairs-link-form-select js-stairs-entrance-select" required>
                                <option value="">Select To Entrance</option>
                                @foreach($stairsEntrances as $entrance)
                                    <option value="{{ $entrance->id }}" data-building-id="{{ $entrance->indoorMap->building_id ?? '' }}">
                                        {{ $entrance->name }}
                                        -
                                        {{ $entrance->indoorMap->building->name ?? 'N/A' }}
                                        -
                                        {{ $entrance->indoorMap->floor_label ?? ($entrance->indoorMap->floor_number . 'F') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-6">
                            <label class="stairs-link-form-label">Link Name</label>
                            <input
                                type="text"
                                name="name"
                                id="edit_link_name"
                                class="form-control stairs-link-form-control"
                                placeholder="Main Stairs Link"
                            >
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="modal-update-btn">
                        Update Stairs Link
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterStairsEntrancesByBuilding(buildingSelect, keepValues = {}) {
    const buildingId = buildingSelect.value;
    const fromSelect = document.getElementById(buildingSelect.dataset.targetFrom);
    const toSelect = document.getElementById(buildingSelect.dataset.targetTo);

    if (!fromSelect || !toSelect) return;

    [fromSelect, toSelect].forEach(select => {
        const keepValue = keepValues[select.id] ?? select.value;
        let hasSelectedOption = false;

        Array.from(select.options).forEach(option => {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const optionBuildingId = option.dataset.buildingId;
            const shouldShow = buildingId && optionBuildingId === buildingId;

            option.hidden = !shouldShow;
            option.disabled = !shouldShow;

            if (shouldShow && option.value === keepValue) {
                hasSelectedOption = true;
            }
        });

        select.value = hasSelectedOption ? keepValue : '';
        select.disabled = !buildingId;
    });
}

document.querySelectorAll('.js-building-select').forEach(select => {
    filterStairsEntrancesByBuilding(select);

    select.addEventListener('change', function () {
        filterStairsEntrancesByBuilding(this);
    });
});

document.querySelectorAll('.edit-link-btn').forEach(button => {
    button.addEventListener('click', function () {
        const id = this.dataset.id;
        const buildingId = this.dataset.building_id;
        const fromEntranceId = this.dataset.from_entrance_id;
        const toEntranceId = this.dataset.to_entrance_id;
        const name = this.dataset.name;

        const form = document.getElementById('editIndoorStairsLinkForm');
        form.action = `/admin/indoor-stairs-link/${id}/update`;

        const editBuildingSelect = document.getElementById('edit_building_id');
        editBuildingSelect.value = buildingId ?? '';

        filterStairsEntrancesByBuilding(editBuildingSelect, {
            edit_from_entrance_id: fromEntranceId ?? '',
            edit_to_entrance_id: toEntranceId ?? '',
        });

        document.getElementById('edit_link_name').value = name ?? '';
    });
});
</script>

@endsection
