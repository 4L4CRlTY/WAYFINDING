@extends('admin.dashboard')

@section('admin')

<style>
    .entrance-link-wrapper {
        padding: 24px;
    }

    .entrance-link-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .entrance-link-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .entrance-link-card-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .entrance-link-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .entrance-link-form-body {
        padding: 24px;
    }

    .entrance-link-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .entrance-link-form-control,
    .entrance-link-form-select {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }

    .entrance-link-form-control:focus,
    .entrance-link-form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .entrance-link-submit-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .entrance-link-submit-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .entrance-link-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .entrance-link-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .entrance-link-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .entrance-link-table {
        margin-bottom: 0;
    }

    .entrance-link-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .entrance-link-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .entrance-link-id-badge {
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

    .entrance-link-name-text {
        font-weight: 800;
        color: #0f172a;
    }

    .entrance-link-building-text {
        font-weight: 700;
        color: #334155;
    }

    .entrance-link-point-box {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .entrance-link-point-name {
        font-weight: 800;
        color: #0f172a;
    }

    .entrance-link-pill {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        border-radius: 999px;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .outdoor-pill {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .indoor-pill {
        background: #ede9fe;
        color: #6d28d9;
    }

    .entrance-link-action-btn {
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
    }

    .entrance-link-edit-btn {
        background: #fef3c7;
        color: #92400e;
    }

    .entrance-link-edit-btn:hover {
        background: #fde68a;
        color: #78350f;
    }

    .entrance-link-delete-btn {
        background: #fee2e2;
        color: #b91c1c;
    }

    .entrance-link-delete-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .empty-entrance-link-box {
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

    .entrance-link-modal .modal-content {
        border: none;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        overflow: hidden;
    }

    .entrance-link-modal .modal-header {
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
        border: none;
        padding: 20px 24px;
    }

    .entrance-link-modal .modal-title {
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .entrance-link-modal .btn-close {
        filter: invert(1);
        opacity: 0.9;
    }

    .entrance-link-modal .modal-body {
        padding: 24px;
    }

    .entrance-link-modal .modal-footer {
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
        .entrance-link-wrapper {
            padding: 14px;
        }

        .entrance-link-card-header,
        .entrance-link-form-body,
        .entrance-link-table-header {
            padding: 18px;
        }

        .entrance-link-table {
            min-width: 1080px;
        }

        .entrance-link-submit-btn {
            width: 100%;
        }
    }
</style>

<div class="entrance-link-wrapper">

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
    <div class="entrance-link-card">
        <div class="entrance-link-card-header">
            <h4>Building Entrance Links Manager</h4>
            <p>Connect outdoor building entrances to indoor entrances for outdoor-to-indoor routing.</p>
        </div>

        <div class="entrance-link-form-body">
            <form action="{{ route('admin.building-entrance-link.store') }}" method="POST">
                @csrf

                @include('admin.partials.building_map_selector', [
                    'selectorKey' => 'entrance_link_add',
                    'selectId' => 'add_building_id',
                    'dropdownFieldId' => 'entrance_link_add_building_field',
                    'buildingMapData' => $buildingMapData,
                    'selectedBuildingId' => old('building_id'),
                ])

                <div class="row g-3">
                    <div class="col-lg-4" id="entrance_link_add_building_field">
                        <label class="entrance-link-form-label">Building</label>
                        <select name="building_id" id="add_building_id" class="form-select entrance-link-form-select" required>
                            <option value="">Select Building</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}" {{ old('building_id') == $building->id ? 'selected' : '' }}>
                                    {{ $building->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <label class="entrance-link-form-label">Outdoor Entrance</label>
                        <select name="building_entrance_id" id="add_building_entrance_id" class="form-select entrance-link-form-select" required disabled>
                            <option value="">Select Building First</option>
                            @foreach($buildingEntrances as $entrance)
                                <option
                                    value="{{ $entrance->id }}"
                                    data-building-id="{{ $entrance->building_id }}"
                                    {{ old('building_entrance_id') == $entrance->id ? 'selected' : '' }}
                                >
                                    {{ $entrance->name }}
                                    -
                                    {{ $entrance->building->name ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4">
                        <label class="entrance-link-form-label">Indoor Entrance</label>
                        <select name="indoor_entrance_id" id="add_indoor_entrance_id" class="form-select entrance-link-form-select" required disabled>
                            <option value="">Select Building First</option>
                            @foreach($indoorEntrances as $entrance)
                                <option
                                    value="{{ $entrance->id }}"
                                    data-building-id="{{ $entrance->indoorMap->building_id ?? '' }}"
                                    {{ old('indoor_entrance_id') == $entrance->id ? 'selected' : '' }}
                                >
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
                        <label class="entrance-link-form-label">Link Name</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control entrance-link-form-control"
                            placeholder="Main Entrance → 1F Link"
                            value="{{ old('name') }}"
                        >
                    </div>

                    <div class="col-lg-6 d-flex align-items-end justify-content-end">
                        <button type="submit" class="entrance-link-submit-btn">
                            <i class="ri-links-line me-1"></i>
                            Add Entrance Link
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="entrance-link-table-card">
        <div class="entrance-link-table-header">
            <div>
                <h5>Entrance Links List</h5>
                <span class="muted-small">
                    Use Edit button to update outdoor → indoor mapping.
                </span>
            </div>

            <span class="muted-small">
                Total Entrance Links: {{ $links->total() ?? $links->count() }}
            </span>
        </div>

        <div class="table-responsive">
            @if($links->count())
                <table class="table entrance-link-table align-middle">
                    <thead>
                        <tr>
                            <th width="70">ID</th>
                            <th>Building</th>
                            <th>Name</th>
                            <th>Outdoor Entrance</th>
                            <th>Indoor Entrance</th>
                            <th width="140">Action</th>
                            <th width="200">Created</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($links as $link)
                            <tr>
                                <td>
                                    <span class="entrance-link-id-badge">
                                        #{{ $link->id }}
                                    </span>
                                </td>

                                <td>
                                    <span class="entrance-link-building-text">
                                        {{ $link->building->name ?? 'N/A' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="entrance-link-name-text">
                                        {{ $link->name ?: '—' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="entrance-link-point-box">
                                        <span class="entrance-link-point-name">
                                            {{ $link->buildingEntrance->name ?? 'N/A' }}
                                        </span>

                                        @if($link->buildingEntrance && $link->buildingEntrance->building)
                                            <span class="entrance-link-pill outdoor-pill">
                                                {{ $link->buildingEntrance->building->name }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="entrance-link-point-box">
                                        <span class="entrance-link-point-name">
                                            {{ $link->indoorEntrance->name ?? 'N/A' }}
                                        </span>

                                        @if($link->indoorEntrance && $link->indoorEntrance->indoorMap)
                                            <span class="entrance-link-pill indoor-pill">
                                                {{ $link->indoorEntrance->indoorMap->floor_label ?? ($link->indoorEntrance->indoorMap->floor_number . 'F') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button
                                            type="button"
                                            class="entrance-link-action-btn entrance-link-edit-btn edit-link-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editBuildingEntranceLinkModal"
                                            data-id="{{ $link->id }}"
                                            data-building_id="{{ $link->building_id }}"
                                            data-building_entrance_id="{{ $link->building_entrance_id }}"
                                            data-indoor_entrance_id="{{ $link->indoor_entrance_id }}"
                                            data-name="{{ $link->name }}"
                                        >
                                            Edit
                                        </button>

                                        <form
                                            action="{{ route('admin.building-entrance-link.delete', $link->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete this entrance link?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="entrance-link-action-btn entrance-link-delete-btn">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>

                                <td>
                                    <div class="entrance-link-name-text">
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
                <div class="empty-entrance-link-box">
                    No building entrance links created yet.
                </div>
            @endif
        </div>

        @include('admin.partials.pagination', [
            'paginator' => $links,
            'label' => 'entrance links',
        ])
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade entrance-link-modal" id="editBuildingEntranceLinkModal" tabindex="-1" aria-labelledby="editBuildingEntranceLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editBuildingEntranceLinkForm" method="POST">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title" id="editBuildingEntranceLinkModalLabel">Edit Building Entrance Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="entrance-link-form-label">Building</label>
                            <select name="building_id" id="edit_building_id" class="form-select entrance-link-form-select" required>
                                <option value="">Select Building</option>
                                @foreach($buildings as $building)
                                    <option value="{{ $building->id }}">{{ $building->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-4">
                            <label class="entrance-link-form-label">Outdoor Entrance</label>
                            <select name="building_entrance_id" id="edit_building_entrance_id" class="form-select entrance-link-form-select" required disabled>
                                <option value="">Select Building First</option>
                                @foreach($buildingEntrances as $entrance)
                                    <option value="{{ $entrance->id }}" data-building-id="{{ $entrance->building_id }}">
                                        {{ $entrance->name }}
                                        -
                                        {{ $entrance->building->name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-4">
                            <label class="entrance-link-form-label">Indoor Entrance</label>
                            <select name="indoor_entrance_id" id="edit_indoor_entrance_id" class="form-select entrance-link-form-select" required disabled>
                                <option value="">Select Building First</option>
                                @foreach($indoorEntrances as $entrance)
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
                            <label class="entrance-link-form-label">Link Name</label>
                            <input
                                type="text"
                                name="name"
                                id="edit_link_name"
                                class="form-control entrance-link-form-control"
                                placeholder="Main Entrance → 1F Link"
                            >
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="modal-update-btn">
                        Update Entrance Link
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function setupBuildingFilteredSelects(config) {
        const buildingSelect = document.getElementById(config.buildingSelectId);
        const outdoorSelect = document.getElementById(config.outdoorSelectId);
        const indoorSelect = document.getElementById(config.indoorSelectId);

        if (!buildingSelect || !outdoorSelect || !indoorSelect) {
            return;
        }

        const outdoorOptions = Array.from(outdoorSelect.querySelectorAll('option[data-building-id]')).map(option => option.cloneNode(true));
        const indoorOptions = Array.from(indoorSelect.querySelectorAll('option[data-building-id]')).map(option => option.cloneNode(true));

        function rebuildSelect(select, sourceOptions, buildingId, selectedValue, placeholderText) {
            select.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = buildingId ? placeholderText : 'Select Building First';
            select.appendChild(placeholder);

            if (!buildingId) {
                select.value = '';
                select.disabled = true;
                return;
            }

            const matchingOptions = sourceOptions.filter(option => String(option.dataset.buildingId) === String(buildingId));

            matchingOptions.forEach(option => {
                const clonedOption = option.cloneNode(true);
                clonedOption.selected = String(clonedOption.value) === String(selectedValue || '');
                select.appendChild(clonedOption);
            });

            select.disabled = matchingOptions.length === 0;

            if (matchingOptions.length === 0) {
                placeholder.textContent = 'No entrance found for this building';
                select.value = '';
                return;
            }

            const hasSelectedValue = matchingOptions.some(option => String(option.value) === String(selectedValue || ''));
            select.value = hasSelectedValue ? selectedValue : '';
        }

        function filterSelections(selectedOutdoor = '', selectedIndoor = '') {
            const buildingId = buildingSelect.value;

            rebuildSelect(
                outdoorSelect,
                outdoorOptions,
                buildingId,
                selectedOutdoor,
                'Select Outdoor Entrance'
            );

            rebuildSelect(
                indoorSelect,
                indoorOptions,
                buildingId,
                selectedIndoor,
                'Select Indoor Entrance'
            );
        }

        buildingSelect.addEventListener('change', function () {
            filterSelections('', '');
        });

        filterSelections(config.initialOutdoorId || '', config.initialIndoorId || '');

        return { filterSelections };
    }

    const addFilter = setupBuildingFilteredSelects({
        buildingSelectId: 'add_building_id',
        outdoorSelectId: 'add_building_entrance_id',
        indoorSelectId: 'add_indoor_entrance_id',
        initialOutdoorId: @json(old('building_entrance_id')),
        initialIndoorId: @json(old('indoor_entrance_id'))
    });

    const editFilter = setupBuildingFilteredSelects({
        buildingSelectId: 'edit_building_id',
        outdoorSelectId: 'edit_building_entrance_id',
        indoorSelectId: 'edit_indoor_entrance_id'
    });

    document.querySelectorAll('.edit-link-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const buildingId = this.dataset.building_id;
            const buildingEntranceId = this.dataset.building_entrance_id;
            const indoorEntranceId = this.dataset.indoor_entrance_id;
            const name = this.dataset.name;

            const form = document.getElementById('editBuildingEntranceLinkForm');
            form.action = `/admin/building-entrance-link/${id}/update`;

            document.getElementById('edit_building_id').value = buildingId ?? '';
            document.getElementById('edit_link_name').value = name ?? '';

            if (editFilter) {
                editFilter.filterSelections(buildingEntranceId ?? '', indoorEntranceId ?? '');
            }
        });
    });
});
</script>

@endsection
