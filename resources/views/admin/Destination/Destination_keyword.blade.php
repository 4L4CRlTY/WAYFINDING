@extends('admin.dashboard')

@section('admin')

<style>
    .dk-page {
        padding: 24px;
    }

    .dk-grid {
        display: grid;
        grid-template-columns: 420px 1fr;
        gap: 24px;
        align-items: start;
    }

    .dk-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .dk-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .dk-card-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: white;
    }

    .dk-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
        color: white;
    }

    .dk-card-body {
        padding: 24px;
    }

    .dk-form-group {
        margin-bottom: 16px;
    }

    .dk-label {
        display: block;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
    }

    .dk-input,
    .dk-select,
    .dk-textarea {
        width: 100%;
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        padding: 11px 14px;
        font-size: 14px;
        outline: none;
        transition: 0.2s ease;
        background: #ffffff;
        color: #0f172a;
    }

    .dk-input:focus,
    .dk-select:focus,
    .dk-textarea:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .dk-textarea {
        min-height: 120px;
        resize: vertical;
    }

    .dk-help {
        margin-top: 6px;
        font-size: 12px;
        color: #64748b;
        line-height: 1.5;
    }

    .dk-btn {
        border: none;
        border-radius: 14px;
        padding: 11px 16px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .dk-btn-primary {
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .dk-btn-primary:hover {
        opacity: 0.94;
        color: white;
        transform: translateY(-1px);
    }

    .dk-btn-sync {
        width: 100%;
        margin-bottom: 18px;
        background: linear-gradient(135deg, #18375d, #68a7ee);
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(24, 55, 93, 0.18);
    }

    .dk-btn-sync:hover {
        color: #ffffff;
        transform: translateY(-1px);
    }

    .dk-sync-note {
        margin: -8px 0 18px;
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }

    .dk-btn-danger {
        background: #fee2e2;
        color: #b91c1c;
        padding: 8px 12px;
        font-size: 12px;
    }

    .dk-btn-danger:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .dk-alert {
        padding: 14px 16px;
        border-radius: 14px;
        margin-bottom: 16px;
        font-size: 14px;
        font-weight: 700;
    }

    .dk-alert-success {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    .dk-alert-error {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .dk-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .dk-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    .dk-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
        font-weight: 800;
    }

    .dk-table td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
        border-bottom: 1px solid #eef2f7;
    }

    .dk-table tbody tr:last-child td {
        border-bottom: none;
    }

    .dk-id-badge {
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

    .dk-keyword-text {
        font-weight: 800;
        color: #0f172a;
    }

    .dk-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .dk-badge-building {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .dk-badge-landuse {
        background: #dcfce7;
        color: #15803d;
    }

    .dk-badge-room {
        background: #ede9fe;
        color: #6d28d9;
    }

    .dk-badge-low {
        background: #f1f5f9;
        color: #475569;
    }

    .dk-badge-medium {
        background: #fef3c7;
        color: #92400e;
    }

    .dk-badge-high {
        background: #fee2e2;
        color: #b91c1c;
    }

    .dk-destination-name {
        font-weight: 800;
        color: #0f172a;
    }

    .dk-destination-sub {
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
    }

    .dk-empty {
        text-align: center;
        padding: 36px 20px;
        color: #64748b;
    }

    .dk-pagination {
        margin-top: 18px;
        display: flex;
        justify-content: center;
    }

    .dk-custom-pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        list-style: none;
        padding: 0;
        margin: 0;
        flex-wrap: wrap;
    }

    .dk-custom-pagination li a,
    .dk-custom-pagination li span {
        min-width: 40px;
        height: 40px;
        padding: 8px 14px;
        border-radius: 12px;
        background: #f1f5f9;
        color: #334155;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        transition: 0.2s ease;
    }

    .dk-custom-pagination li a:hover {
        background: #2563eb;
        color: white;
        transform: translateY(-2px);
    }

    .dk-custom-pagination li.active span {
        background: #2563eb;
        color: white;
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.22);
    }

    .dk-custom-pagination li.disabled span {
        background: #e2e8f0;
        color: #94a3b8;
        cursor: not-allowed;
    }

    .dk-target-box {
        animation: dkFadeIn 0.18s ease both;
    }

    @keyframes dkFadeIn {
        from {
            opacity: 0;
            transform: translateY(5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 992px) {
        .dk-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .dk-page {
            padding: 14px;
        }

        .dk-card-header,
        .dk-card-body {
            padding: 18px;
        }

        .dk-table {
            min-width: 780px;
        }

        .dk-btn-primary {
            width: 100%;
        }
    }
</style>

<div class="dk-page">
    @if(session('success'))
        <div class="dk-alert dk-alert-success">
            <strong>Success!</strong> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="dk-alert dk-alert-error">
            <strong>Error!</strong> {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="dk-alert dk-alert-error">
            <strong>Please check the form:</strong>
            <ul class="mb-0 mt-2" style="padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="dk-grid">
        <div class="dk-card">
            <div class="dk-card-header">
                <h3>Add Destination Keyword</h3>
                <p>Bind multiple search keywords to a building, landuse, or room.</p>
            </div>

            <div class="dk-card-body">
                <form action="{{ route('admin.destination-keyword.sync') }}" method="POST">
                    @csrf
                    <button type="submit" class="dk-btn dk-btn-sync">
                        <i class="ri-magic-line me-1"></i>
                        Generate Missing Building & Room Keywords
                    </button>
                    <p class="dk-sync-note">
                        Adds names, room codes, acronyms, and common aliases without deleting your manual keywords.
                    </p>
                </form>

                <form action="{{ route('admin.destination-keyword.store') }}" method="POST">
                    @csrf

                    <div class="dk-form-group">
                        <label class="dk-label">Destination Type</label>
                        <select name="destination_type" id="destination_type" class="dk-select" required>
                            <option value="">Select Destination Type</option>
                            <option value="building" {{ old('destination_type') == 'building' ? 'selected' : '' }}>
                                Building
                            </option>
                            <option value="landuse" {{ old('destination_type') == 'landuse' ? 'selected' : '' }}>
                                Landuse
                            </option>
                            <option value="room" {{ old('destination_type') == 'room' ? 'selected' : '' }}>
                                Room / Office
                            </option>
                        </select>
                    </div>

                    <div class="dk-form-group dk-target-box" id="building_wrap" style="display:none;">
                        <label class="dk-label">Select Building</label>
                        <select id="building_select" class="dk-select">
                            <option value="">Select Building</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}">
                                    {{ $building->name ?: 'Unnamed Building' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dk-form-group dk-target-box" id="landuse_wrap" style="display:none;">
                        <label class="dk-label">Select Landuse</label>
                        <select id="landuse_select" class="dk-select">
                            <option value="">Select Landuse</option>
                            @foreach($landuses as $landuse)
                                <option value="{{ $landuse->id }}">
                                    {{ $landuse->name ?: 'Unnamed Landuse' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dk-form-group dk-target-box" id="room_wrap" style="display:none;">
                        <label class="dk-label">Select Room / Office</label>
                        <select id="room_select" class="dk-select">
                            <option value="">Select Room / Office</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">
                                    {{ $room->name ?: ($room->room_code ?: 'Unnamed Room') }}

                                    @if(optional($room->indoorMap)->building)
                                        - {{ optional(optional($room->indoorMap)->building)->name }}
                                    @endif

                                    @if(optional($room->indoorMap)->floor_label)
                                        ({{ optional($room->indoorMap)->floor_label }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="destination_id" id="destination_id" value="{{ old('destination_id') }}">

                    <div class="dk-form-group">
                        <label class="dk-label">Keywords</label>
                        <textarea
                            name="keywords"
                            class="dk-textarea"
                            placeholder="Example: registrar, registrar office, office of registrar"
                            required>{{ old('keywords') }}</textarea>

                        <div class="dk-help">
                            You can add single or multiple keywords separated by comma.
                        </div>
                    </div>

                    <div class="dk-form-group">
                        <label class="dk-label">Priority</label>
                        <select name="priority" class="dk-select">
                            <option value="1" {{ old('priority', 1) == 1 ? 'selected' : '' }}>
                                Low
                            </option>
                            <option value="2" {{ old('priority') == 2 ? 'selected' : '' }}>
                                Medium
                            </option>
                            <option value="3" {{ old('priority') == 3 ? 'selected' : '' }}>
                                High
                            </option>
                        </select>

                        <div class="dk-help">
                            Higher priority means mas una siya i-match.
                        </div>
                    </div>

                    <button type="submit" class="dk-btn dk-btn-primary">
                        <i class="ri-save-3-line me-1"></i>
                        Save Keyword
                    </button>
                </form>
            </div>
        </div>

        <div class="dk-card">
            <div class="dk-card-header">
                <h3>Destination Keywords List</h3>
                <p>Manage all mapped aliases for your destinations.</p>
            </div>

            <div class="dk-card-body">
                @if($keywords->count())
                    <div class="dk-table-wrap">
                        <table class="dk-table">
                            <thead>
                                <tr>
                                    <th width="70">ID</th>
                                    <th>Keyword</th>
                                    <th width="120">Type</th>
                                    <th width="150">Destination ID</th>
                                    <th width="110">Priority</th>
                                    <th width="110">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($keywords as $item)
                                    <tr>
                                        <td>
                                            <span class="dk-id-badge">
                                                #{{ $item->id }}
                                            </span>
                                        </td>

                                        <td>
                                            <span class="dk-keyword-text">
                                                {{ $item->keyword }}
                                            </span>
                                        </td>

                                        <td>
                                            @if($item->destination_type === 'building')
                                                <span class="dk-badge dk-badge-building">Building</span>
                                            @elseif($item->destination_type === 'landuse')
                                                <span class="dk-badge dk-badge-landuse">Landuse</span>
                                            @else
                                                <span class="dk-badge dk-badge-room">Room</span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="dk-destination-name">#{{ $item->destination_id }}</div>
                                            <div class="dk-destination-sub">
                                                Linked {{ ucfirst($item->destination_type) }}
                                            </div>
                                        </td>

                                        <td>
                                            @if($item->priority == 3)
                                                <span class="dk-badge dk-badge-high">High</span>
                                            @elseif($item->priority == 2)
                                                <span class="dk-badge dk-badge-medium">Medium</span>
                                            @else
                                                <span class="dk-badge dk-badge-low">Low</span>
                                            @endif
                                        </td>

                                        <td>
                                            <form
                                                action="{{ route('admin.destination-keyword.destroy', $item->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Delete this keyword?');"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="dk-btn dk-btn-danger">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @include('admin.partials.pagination', [
                        'paginator' => $keywords,
                        'label' => 'destination keywords',
                    ])
                @else
                    <div class="dk-empty">
                        No destination keywords found yet.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    const destinationType = document.getElementById('destination_type');
    const destinationId = document.getElementById('destination_id');

    const buildingWrap = document.getElementById('building_wrap');
    const landuseWrap = document.getElementById('landuse_wrap');
    const roomWrap = document.getElementById('room_wrap');

    const buildingSelect = document.getElementById('building_select');
    const landuseSelect = document.getElementById('landuse_select');
    const roomSelect = document.getElementById('room_select');

    function updateDestinationInput() {
        const type = destinationType.value;

        buildingWrap.style.display = 'none';
        landuseWrap.style.display = 'none';
        roomWrap.style.display = 'none';

        destinationId.value = '';

        if (type === 'building') {
            buildingWrap.style.display = 'block';
            destinationId.value = buildingSelect.value || '';
        } else if (type === 'landuse') {
            landuseWrap.style.display = 'block';
            destinationId.value = landuseSelect.value || '';
        } else if (type === 'room') {
            roomWrap.style.display = 'block';
            destinationId.value = roomSelect.value || '';
        }
    }

    destinationType.addEventListener('change', updateDestinationInput);

    buildingSelect.addEventListener('change', function () {
        if (destinationType.value === 'building') {
            destinationId.value = this.value;
        }
    });

    landuseSelect.addEventListener('change', function () {
        if (destinationType.value === 'landuse') {
            destinationId.value = this.value;
        }
    });

    roomSelect.addEventListener('change', function () {
        if (destinationType.value === 'room') {
            destinationId.value = this.value;
        }
    });

    window.addEventListener('load', function () {
        updateDestinationInput();

        const oldType = "{{ old('destination_type') }}";
        const oldId = "{{ old('destination_id') }}";

        if (oldType === 'building') {
            buildingSelect.value = oldId;
        } else if (oldType === 'landuse') {
            landuseSelect.value = oldId;
        } else if (oldType === 'room') {
            roomSelect.value = oldId;
        }

        updateDestinationInput();
    });
</script>

@endsection
