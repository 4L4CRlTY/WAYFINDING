@extends('admin.dashboard')

@section('admin')

<style>
    .campus-event-wrapper {
        padding: 24px;
    }

    .campus-event-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .campus-event-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .campus-event-card-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .campus-event-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .campus-event-form-body {
        padding: 24px;
    }

    .event-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .event-form-control,
    .event-form-select {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }

    .event-form-control:focus,
    .event-form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .event-target-box {
        display: none;
        animation: fadeInEventBox 0.18s ease both;
    }

    .event-target-box.active {
        display: block;
    }

    @keyframes fadeInEventBox {
        from {
            opacity: 0;
            transform: translateY(5px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .event-submit-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .event-submit-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .campus-event-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .event-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .event-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .event-table {
        margin-bottom: 0;
    }

    .event-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .event-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .event-title {
        font-weight: 800;
        color: #0f172a;
    }

    .event-description {
        color: #64748b;
        font-size: 13px;
        margin-top: 3px;
        max-width: 360px;
    }

    .target-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
    }

    .target-building {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .target-room {
        background: #ede9fe;
        color: #6d28d9;
    }

    .target-landuse {
        background: #dcfce7;
        color: #15803d;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
    }

    .status-active {
        background: #dcfce7;
        color: #15803d;
    }

    .status-inactive {
        background: #fee2e2;
        color: #b91c1c;
    }

    .time-text {
        font-weight: 700;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .event-action-btn {
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
    }

    .toggle-btn {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .delete-btn {
        background: #fee2e2;
        color: #b91c1c;
    }

    .empty-event-box {
        padding: 36px;
        text-align: center;
        color: #64748b;
    }

    @media (max-width: 768px) {
        .campus-event-wrapper {
            padding: 14px;
        }

        .campus-event-card-header,
        .campus-event-form-body,
        .event-table-header {
            padding: 18px;
        }

        .event-table {
            min-width: 980px;
        }
    }
</style>

<div class="campus-event-wrapper">

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

    <div class="campus-event-card">
        <div class="campus-event-card-header">
            <h4>Campus Event Manager</h4>
            <p>Create events for buildings, indoor rooms, and landuse areas.</p>
        </div>

        <div class="campus-event-form-body">
            <form action="{{ route('admin.campus-event.store') }}" method="POST">
                @csrf

                <div class="row g-3">

                    <div class="col-lg-4">
                        <label class="event-form-label">Event Target Type</label>
                        <select name="event_target_type" id="eventTargetType" class="form-select event-form-select" required>
                            <option value="">Select target type</option>
                            <option value="building" {{ old('event_target_type') == 'building' ? 'selected' : '' }}>
                                Building
                            </option>
                            <option value="room" {{ old('event_target_type') == 'room' ? 'selected' : '' }}>
                                Indoor Room
                            </option>
                            <option value="landuse" {{ old('event_target_type') == 'landuse' ? 'selected' : '' }}>
                                Landuse / Open Area
                            </option>
                        </select>
                    </div>

                    <div class="col-lg-8">
                        <div id="buildingTargetBox" class="event-target-box">
                            <label class="event-form-label">Select Building</label>
                            <select name="building_id" class="form-select event-form-select">
                                <option value="">Choose building</option>
                                @foreach($buildings as $building)
                                    <option value="{{ $building->id }}" {{ old('building_id') == $building->id ? 'selected' : '' }}>
                                        {{ $building->name ?? 'Unnamed Building' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="roomTargetBox" class="event-target-box">
                            <label class="event-form-label">Select Indoor Room</label>
                            <select name="indoor_room_id" class="form-select event-form-select">
                                <option value="">Choose indoor room</option>
                                @foreach($indoorRooms as $room)
                                    @php
                                        $roomBuilding = optional(optional($room->indoorMap)->building)->name;
                                        $floorLabel = optional($room->indoorMap)->floor_label;
                                    @endphp

                                    <option value="{{ $room->id }}" {{ old('indoor_room_id') == $room->id ? 'selected' : '' }}>
                                        {{ $roomBuilding ?? 'No Building' }}
                                        —
                                        {{ $floorLabel ?? 'No Floor' }}
                                        —
                                        {{ $room->room_code ?? 'No Code' }}
                                        {{ $room->name ? ' / '.$room->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="landuseTargetBox" class="event-target-box">
                            <label class="event-form-label">Select Landuse / Open Area</label>
                            <select name="landuse_id" class="form-select event-form-select">
                                <option value="">Choose landuse area</option>
                                @foreach($landuses as $landuse)
                                    <option value="{{ $landuse->id }}" {{ old('landuse_id') == $landuse->id ? 'selected' : '' }}>
                                        {{ $landuse->name ?? 'Unnamed Landuse' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <label class="event-form-label">Event Title</label>
                        <input
                            type="text"
                            name="title"
                            class="form-control event-form-control"
                            value="{{ old('title') }}"
                            placeholder="Example: IT Seminar, Campus Program, Orientation"
                            required
                        >
                    </div>

                    <div class="col-lg-6">
                        <label class="event-form-label">Location Label Optional</label>
                        <input
                            type="text"
                            name="location_label"
                            class="form-control event-form-control"
                            value="{{ old('location_label') }}"
                            placeholder="Example: Room 202, IT Building Lobby, Open Field Stage"
                        >
                    </div>

                    <div class="col-lg-6">
                        <label class="event-form-label">Start Date & Time</label>
                        <input
                            type="datetime-local"
                            name="starts_at"
                            class="form-control event-form-control"
                            value="{{ old('starts_at') }}"
                            required
                        >
                    </div>

                    <div class="col-lg-6">
                        <label class="event-form-label">End Date & Time Optional</label>
                        <input
                            type="datetime-local"
                            name="ends_at"
                            class="form-control event-form-control"
                            value="{{ old('ends_at') }}"
                        >
                    </div>

                    <div class="col-lg-9">
                        <label class="event-form-label">Description Optional</label>
                        <textarea
                            name="description"
                            class="form-control event-form-control"
                            rows="3"
                            placeholder="Short details about the event..."
                        >{{ old('description') }}</textarea>
                    </div>

                    <div class="col-lg-3">
                        <label class="event-form-label">Priority</label>
                        <input
                            type="number"
                            name="priority"
                            class="form-control event-form-control"
                            value="{{ old('priority', 0) }}"
                        >

                        <div class="form-check mt-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="is_active"
                                id="isActive"
                                checked
                            >
                            <label class="form-check-label fw-bold" for="isActive">
                                Active Event
                            </label>
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="event-submit-btn">
                            <i class="ri-calendar-event-line me-1"></i>
                            Save Campus Event
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <div class="campus-event-table-card">
        <div class="event-table-header">
            <h5>Campus Events List</h5>
            <span class="muted-small">
                Total Events: {{ $campusEvents->total() }}
            </span>
        </div>

        <div class="table-responsive">
            @if($campusEvents->count())
                <table class="table event-table align-middle">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Target</th>
                            <th>Location</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th width="170">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($campusEvents as $event)
                            @php
                                $targetClass = 'target-building';
                                $targetLabel = 'Building';
                                $locationName = 'No location';

                                if ($event->event_target_type === 'building') {
                                    $targetClass = 'target-building';
                                    $targetLabel = 'Building';
                                    $locationName = optional($event->building)->name ?? 'Deleted Building';
                                }

                                if ($event->event_target_type === 'room') {
                                    $targetClass = 'target-room';
                                    $targetLabel = 'Indoor Room';

                                    $room = $event->indoorRoom;
                                    $roomCode = optional($room)->room_code;
                                    $roomName = optional($room)->name;
                                    $floor = optional(optional($room)->indoorMap)->floor_label;
                                    $building = optional(optional(optional($room)->indoorMap)->building)->name;

                                    $locationName = trim(
                                        ($building ? $building.' — ' : '') .
                                        ($floor ? $floor.' — ' : '') .
                                        ($roomCode ? $roomCode : '') .
                                        ($roomName ? ' / '.$roomName : '')
                                    );

                                    if (!$locationName) {
                                        $locationName = 'Deleted Room';
                                    }
                                }

                                if ($event->event_target_type === 'landuse') {
                                    $targetClass = 'target-landuse';
                                    $targetLabel = 'Landuse';
                                    $locationName = optional($event->landuse)->name ?? 'Deleted Landuse';
                                }
                            @endphp

                            <tr>
                                <td>
                                    <div class="event-title">{{ $event->title }}</div>

                                    @if($event->description)
                                        <div class="event-description">
                                            {{ Str::limit($event->description, 90) }}
                                        </div>
                                    @endif

                                    @if($event->location_label)
                                        <div class="muted-small mt-1">
                                            Label: {{ $event->location_label }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <span class="target-badge {{ $targetClass }}">
                                        {{ $targetLabel }}
                                    </span>
                                </td>

                                <td>
                                    <div class="time-text">{{ $locationName }}</div>
                                </td>

                                <td>
                                    <div class="time-text">
                                        {{ optional($event->starts_at)->format('M d, Y') }}
                                    </div>
                                    <div class="muted-small">
                                        {{ optional($event->starts_at)->format('h:i A') }}
                                    </div>
                                </td>

                                <td>
                                    @if($event->ends_at)
                                        <div class="time-text">
                                            {{ optional($event->ends_at)->format('M d, Y') }}
                                        </div>
                                        <div class="muted-small">
                                            {{ optional($event->ends_at)->format('h:i A') }}
                                        </div>
                                    @else
                                        <span class="muted-small">No end time</span>
                                    @endif
                                </td>

                                <td>
                                    @if($event->is_active)
                                        <span class="status-pill status-active">Active</span>
                                    @else
                                        <span class="status-pill status-inactive">Inactive</span>
                                    @endif
                                </td>

                                <td>
                                    <strong>{{ $event->priority }}</strong>
                                </td>

                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <form action="{{ route('admin.campus-event.toggle-status', $event->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="event-action-btn toggle-btn">
                                                {{ $event->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <form
                                            action="{{ route('admin.campus-event.destroy', $event->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Delete this campus event?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="event-action-btn delete-btn">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-event-box">
                    No campus events created yet.
                </div>
            @endif
        </div>

        @include('admin.partials.pagination', [
            'paginator' => $campusEvents,
            'label' => 'campus events',
        ])
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const targetType = document.getElementById('eventTargetType');

        const buildingBox = document.getElementById('buildingTargetBox');
        const roomBox = document.getElementById('roomTargetBox');
        const landuseBox = document.getElementById('landuseTargetBox');

        const buildingSelect = document.querySelector('[name="building_id"]');
        const roomSelect = document.querySelector('[name="indoor_room_id"]');
        const landuseSelect = document.querySelector('[name="landuse_id"]');

        function resetRequiredFields() {
            buildingSelect.required = false;
            roomSelect.required = false;
            landuseSelect.required = false;
        }

        function hideAllBoxes() {
            buildingBox.classList.remove('active');
            roomBox.classList.remove('active');
            landuseBox.classList.remove('active');
        }

        function toggleTargetBox() {
            const value = targetType.value;

            hideAllBoxes();
            resetRequiredFields();

            if (value === 'building') {
                buildingBox.classList.add('active');
                buildingSelect.required = true;
            }

            if (value === 'room') {
                roomBox.classList.add('active');
                roomSelect.required = true;
            }

            if (value === 'landuse') {
                landuseBox.classList.add('active');
                landuseSelect.required = true;
            }
        }

        targetType.addEventListener('change', toggleTargetBox);

        toggleTargetBox();
    });
</script>

@endsection
