@extends('admin.dashboard')

@section('admin')

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

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

    .share-link-btn {
        background: #eaf4ff;
        color: #18375d;
    }

    .empty-event-box {
        padding: 36px;
        text-align: center;
        color: #64748b;
    }

    .event-target-selector {
        margin-bottom: 22px;
        border: 1px solid #cfe0f3;
        border-radius: 20px;
        overflow: hidden;
        background: #f7fbff;
    }

    .event-target-selector-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 18px;
        border-bottom: 1px solid #d8e7f7;
        background: linear-gradient(135deg, rgba(24, 55, 93, .06), rgba(104, 167, 238, .12));
    }

    .event-target-selector-head h5 {
        margin: 0;
        color: #18375d;
        font-weight: 800;
    }

    .event-target-selector-head p {
        margin: 3px 0 0;
        color: #58728f;
        font-size: 13px;
    }

    .event-target-methods {
        display: inline-flex;
        padding: 4px;
        border: 1px solid #bfd6ee;
        border-radius: 12px;
        background: #fff;
    }

    .event-target-method {
        border: 0;
        border-radius: 9px;
        padding: 8px 13px;
        color: #58728f;
        background: transparent;
        font-size: 12px;
        font-weight: 800;
    }

    .event-target-method.active {
        color: #fff;
        background: #18375d;
        box-shadow: 0 6px 14px rgba(24, 55, 93, .2);
    }

    .event-map-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.55fr) minmax(280px, .75fr);
        min-height: 440px;
    }

    #campusEventTargetMap {
        width: 100%;
        min-height: 440px;
        background: #eaf3fc;
    }

    .event-map-picker {
        padding: 18px;
        border-left: 1px solid #d8e7f7;
        background: #fff;
        overflow-y: auto;
        max-height: 440px;
    }

    .event-map-empty {
        min-height: 260px;
        display: grid;
        place-items: center;
        padding: 22px;
        text-align: center;
        color: #58728f;
    }

    .event-map-empty i {
        display: block;
        margin-bottom: 10px;
        color: #68a7ee;
        font-size: 34px;
    }

    .event-map-selection-kicker {
        color: #5080b5;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .event-map-selection-title {
        margin: 4px 0 2px;
        color: #18375d;
        font-size: 19px;
        font-weight: 800;
    }

    .event-map-selection-meta {
        color: #6a8098;
        font-size: 12px;
    }

    .event-map-select-building {
        width: 100%;
        margin-top: 14px;
        border: 0;
        border-radius: 12px;
        padding: 11px 13px;
        color: #fff;
        background: linear-gradient(135deg, #18375d, #4f8fd5);
        font-weight: 800;
    }

    .event-room-heading {
        margin: 18px 0 9px;
        color: #18375d;
        font-size: 13px;
        font-weight: 800;
    }

    .event-room-filter {
        width: 100%;
        margin-bottom: 10px;
        border: 1px solid #c9ddf2;
        border-radius: 11px;
        padding: 9px 11px;
        color: #18375d;
        background: #f8fbff;
    }

    .event-room-list {
        display: grid;
        gap: 8px;
    }

    .event-room-choice {
        width: 100%;
        border: 1px solid #d7e5f4;
        border-radius: 12px;
        padding: 10px 11px;
        text-align: left;
        color: #284866;
        background: #f9fcff;
    }

    .event-room-choice:hover,
    .event-room-choice.active {
        border-color: #68a7ee;
        color: #18375d;
        background: #eaf4ff;
        box-shadow: 0 6px 16px rgba(24, 55, 93, .09);
    }

    .event-room-choice strong,
    .event-room-choice small {
        display: block;
    }

    .event-room-choice small {
        margin-top: 2px;
        color: #6a8098;
    }

    .event-selected-summary {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 14px 18px 18px;
        padding: 11px 13px;
        border: 1px solid #bcd8f4;
        border-radius: 13px;
        color: #18375d;
        background: #eaf4ff;
        font-size: 13px;
        font-weight: 700;
    }

    .event-selected-summary i {
        color: #1e78c8;
        font-size: 20px;
    }

    .event-selected-summary.empty {
        color: #6a8098;
        background: #f7faff;
    }

    .event-map-building {
        transition: fill-opacity .15s ease, stroke-width .15s ease;
    }

    .event-dropdown-picker[hidden],
    .event-map-picker-wrap[hidden] {
        display: none !important;
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

        .event-target-selector-head {
            align-items: stretch;
            flex-direction: column;
        }

        .event-target-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .event-map-layout {
            grid-template-columns: 1fr;
        }

        #campusEventTargetMap {
            min-height: 330px;
        }

        .event-map-picker {
            border-top: 1px solid #d8e7f7;
            border-left: 0;
            max-height: 360px;
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
                    <div class="col-12">
                        <section class="event-target-selector" aria-labelledby="event-target-selector-title">
                            <header class="event-target-selector-head">
                                <div>
                                    <h5 id="event-target-selector-title">Choose Event Destination</h5>
                                    <p>Click a building or open area on the map, or switch to the dropdown selector.</p>
                                </div>
                                <div class="event-target-methods" role="group" aria-label="Destination selection method">
                                    <button type="button" class="event-target-method active" data-event-target-method="map">
                                        <i class="ri-map-pin-line me-1"></i> Select on Map
                                    </button>
                                    <button type="button" class="event-target-method" data-event-target-method="dropdown">
                                        <i class="ri-list-check-2 me-1"></i> Use Dropdown
                                    </button>
                                </div>
                            </header>

                            <div class="event-map-picker-wrap" id="eventMapPickerWrap">
                                <div class="event-map-layout">
                                    <div id="campusEventTargetMap" aria-label="Campus event destination map"></div>
                                    <aside class="event-map-picker" id="eventMapPicker">
                                        <div class="event-map-empty" id="eventMapEmpty">
                                            <div>
                                                <i class="ri-building-2-line"></i>
                                                <strong>Tap a destination on the map</strong>
                                                <div>Buildings and land-use areas are selectable. Campus paths are shown as a guide.</div>
                                            </div>
                                        </div>
                                        <div id="eventMapSelection" hidden></div>
                                    </aside>
                                </div>

                                <div class="event-selected-summary empty" id="eventSelectedSummary" role="status">
                                    <i class="ri-map-pin-line"></i>
                                    <span>No event destination selected yet.</span>
                                </div>
                            </div>

                            <div class="event-dropdown-picker" id="eventDropdownPicker" hidden>
                                <div class="row g-3 p-3">

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

                                </div>
                            </div>
                        </section>
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
                                        @if(auth()->user()?->role === 'admin' && in_array($event->event_target_type, ['building', 'room', 'landuse'], true))
                                            @if($event->destinationLink)
                                                <button
                                                    type="button"
                                                    class="event-action-btn share-link-btn"
                                                    data-copy-event-link="{{ route('destination-links.open', $event->destinationLink) }}"
                                                >
                                                    Copy Route Link
                                                </button>
                                            @endif
                                        @endif

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

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-copy-event-link]').forEach(function (button) {
            button.addEventListener('click', async function () {
                try {
                    await navigator.clipboard.writeText(button.dataset.copyEventLink);
                    const label = button.textContent;
                    button.textContent = 'Copied!';
                    setTimeout(function () {
                        button.textContent = label;
                    }, 1400);
                } catch (error) {
                    window.FuturisticDialog.copy(
                        'Copy this event route link and share it with campus visitors.',
                        button.dataset.copyEventLink
                    );
                }
            });
        });

        const targetType = document.getElementById('eventTargetType');

        const buildingBox = document.getElementById('buildingTargetBox');
        const roomBox = document.getElementById('roomTargetBox');
        const landuseBox = document.getElementById('landuseTargetBox');

        const buildingSelect = document.querySelector('[name="building_id"]');
        const roomSelect = document.querySelector('[name="indoor_room_id"]');
        const landuseSelect = document.querySelector('[name="landuse_id"]');
        const eventMapData = @json($eventMapData);
        const mapPickerWrap = document.getElementById('eventMapPickerWrap');
        const dropdownPicker = document.getElementById('eventDropdownPicker');
        const mapSelection = document.getElementById('eventMapSelection');
        const mapEmpty = document.getElementById('eventMapEmpty');
        const selectedSummary = document.getElementById('eventSelectedSummary');
        const methodButtons = document.querySelectorAll('[data-event-target-method]');
        const buildingLayers = new Map();
        const landuseLayers = new Map();
        let campusEventMap = null;
        let previewedBuildingId = null;

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

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function buildingStyle(feature, selected = false) {
            return {
                color: selected ? '#ffffff' : '#18375d',
                weight: selected ? 4 : 2,
                fillColor: feature?.properties?.color || '#4f94d4',
                fillOpacity: selected ? 0.94 : 0.72,
                className: 'event-map-building',
            };
        }

        function landuseStyle(selected = false) {
            return {
                color: selected ? '#ffffff' : '#25805a',
                weight: selected ? 4 : 2,
                fillColor: '#74c995',
                fillOpacity: selected ? 0.82 : 0.42,
                dashArray: selected ? null : '6,4',
            };
        }

        function pathStyle(feature) {
            const type = feature?.properties?.type || 'walkway';

            if (type === 'stairs') {
                return { color: '#e49a17', weight: 4, dashArray: '4,5', opacity: .86 };
            }

            if (type === 'covered_stairs') {
                return { color: '#263c58', weight: 6, opacity: .9 };
            }

            if (type === 'road' || type === 'main_road') {
                return { color: '#60758e', weight: 5, opacity: .75 };
            }

            return { color: '#4aa9dd', weight: 3, opacity: .7 };
        }

        function highlightMapTarget(type, id) {
            buildingLayers.forEach((layer, buildingId) => {
                layer.setStyle(candidate => buildingStyle(candidate, type === 'building' && Number(buildingId) === Number(id)));
            });

            landuseLayers.forEach((layer, landuseId) => {
                layer.setStyle(landuseStyle(type === 'landuse' && Number(landuseId) === Number(id)));
            });
        }

        function roomLabel(room) {
            const primary = room.room_code || room.name || `Room ${room.id}`;
            return room.room_code && room.name ? `${primary} — ${room.name}` : primary;
        }

        function updateSelectionSummary() {
            const type = targetType.value;
            let label = '';
            let icon = 'ri-map-pin-line';

            if (type === 'building' && buildingSelect.value) {
                label = buildingSelect.selectedOptions[0]?.textContent?.trim() || 'Selected building';
                icon = 'ri-building-2-line';
            }

            if (type === 'room' && roomSelect.value) {
                label = roomSelect.selectedOptions[0]?.textContent?.replace(/\s+/g, ' ')?.trim() || 'Selected room';
                icon = 'ri-door-open-line';
            }

            if (type === 'landuse' && landuseSelect.value) {
                label = landuseSelect.selectedOptions[0]?.textContent?.trim() || 'Selected open area';
                icon = 'ri-landscape-line';
            }

            selectedSummary.classList.toggle('empty', !label);
            selectedSummary.innerHTML = label
                ? `<i class="${icon}"></i><span><strong>${escapeHtml(type === 'room' ? 'Indoor Room' : type === 'landuse' ? 'Land-use Area' : 'Building')}:</strong> ${escapeHtml(label)}</span>`
                : '<i class="ri-map-pin-line"></i><span>No event destination selected yet.</span>';

            let mapType = type;
            let mapId = type === 'building' ? buildingSelect.value : landuseSelect.value;

            if (type === 'room' && roomSelect.value) {
                const room = eventMapData.rooms.find(item => Number(item.id) === Number(roomSelect.value));
                mapType = 'building';
                mapId = room?.building_id;
            }

            highlightMapTarget(mapType, mapId);
        }

        function chooseDestination(type, id) {
            targetType.value = type;
            buildingSelect.value = type === 'building' ? String(id) : '';
            roomSelect.value = type === 'room' ? String(id) : '';
            landuseSelect.value = type === 'landuse' ? String(id) : '';
            toggleTargetBox();
            updateSelectionSummary();
        }

        function renderBuildingPicker(building) {
            previewedBuildingId = Number(building.id);
            const rooms = eventMapData.rooms.filter(room => Number(room.building_id) === Number(building.id));

            mapEmpty.hidden = true;
            mapSelection.hidden = false;
            mapSelection.innerHTML = `
                <div class="event-map-selection-kicker">Selected Building</div>
                <div class="event-map-selection-title">${escapeHtml(building.name || 'Building')}</div>
                <div class="event-map-selection-meta">${rooms.length} room${rooms.length === 1 ? '' : 's'} available</div>
                <button type="button" class="event-map-select-building" data-select-map-building="${Number(building.id)}">
                    <i class="ri-building-2-line me-1"></i> Use Entire Building
                </button>
                <div class="event-room-heading">Or choose a room inside</div>
                ${rooms.length ? '<input type="search" class="event-room-filter" id="eventRoomFilter" placeholder="Filter room name or code">' : ''}
                <div class="event-room-list" id="eventRoomList"></div>
            `;

            const renderRooms = search => {
                const normalized = String(search || '').trim().toLowerCase();
                const filtered = rooms.filter(room => {
                    const text = [room.room_code, room.name, room.floor_label, room.type].filter(Boolean).join(' ').toLowerCase();
                    return !normalized || text.includes(normalized);
                });
                const list = document.getElementById('eventRoomList');

                list.innerHTML = filtered.length
                    ? filtered.map(room => `
                        <button type="button"
                                class="event-room-choice ${targetType.value === 'room' && Number(roomSelect.value) === Number(room.id) ? 'active' : ''}"
                                data-select-map-room="${Number(room.id)}">
                            <strong>${escapeHtml(roomLabel(room))}</strong>
                            <small>${escapeHtml(room.floor_label || 'Floor not specified')}${room.type ? ` · ${escapeHtml(room.type)}` : ''}</small>
                        </button>
                    `).join('')
                    : '<div class="text-muted small py-2">No matching rooms found.</div>';
            };

            renderRooms('');
            document.getElementById('eventRoomFilter')?.addEventListener('input', event => renderRooms(event.target.value));
            highlightMapTarget('building', building.id);
        }

        function renderLandusePicker(landuse) {
            previewedBuildingId = null;
            mapEmpty.hidden = true;
            mapSelection.hidden = false;
            mapSelection.innerHTML = `
                <div class="event-map-selection-kicker">Selected Open Area</div>
                <div class="event-map-selection-title">${escapeHtml(landuse.name || 'Land-use Area')}</div>
                <div class="event-map-selection-meta">Land-use / outdoor event destination</div>
                <button type="button" class="event-map-select-building" data-select-map-landuse="${Number(landuse.id)}">
                    <i class="ri-landscape-line me-1"></i> Use This Area
                </button>
            `;
            highlightMapTarget('landuse', landuse.id);
        }

        function initializeEventMap() {
            if (campusEventMap || typeof L === 'undefined') return;

            campusEventMap = L.map('campusEventTargetMap', {
                zoomControl: true,
                preferCanvas: true,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 21,
            }).addTo(campusEventMap);

            const visibleLayers = [];

            eventMapData.paths.forEach(path => {
                if (!path.geometry) return;
                const layer = L.geoJSON({
                    type: 'Feature',
                    geometry: path.geometry,
                    properties: path,
                }, {
                    style: pathStyle,
                    interactive: false,
                }).addTo(campusEventMap);
                visibleLayers.push(layer);
            });

            eventMapData.landuses.forEach(landuse => {
                if (!landuse.geometry) return;
                const layer = L.geoJSON({
                    type: 'Feature',
                    geometry: landuse.geometry,
                    properties: landuse,
                }, {
                    style: () => landuseStyle(false),
                }).addTo(campusEventMap);
                layer.bindTooltip(landuse.name || 'Land-use Area', { sticky: true });
                layer.on('click', event => {
                    L.DomEvent.stopPropagation(event);
                    renderLandusePicker(landuse);
                });
                landuseLayers.set(Number(landuse.id), layer);
                visibleLayers.push(layer);
            });

            eventMapData.buildings.forEach(building => {
                if (!building.geometry) return;
                const layer = L.geoJSON({
                    type: 'Feature',
                    geometry: building.geometry,
                    properties: building,
                }, {
                    style: candidate => buildingStyle(candidate),
                }).addTo(campusEventMap);
                layer.bindTooltip(building.name || 'Building', { sticky: true });
                layer.on('click', event => {
                    L.DomEvent.stopPropagation(event);
                    renderBuildingPicker(building);
                });
                buildingLayers.set(Number(building.id), layer);
                visibleLayers.push(layer);
            });

            if (visibleLayers.length) {
                campusEventMap.fitBounds(L.featureGroup(visibleLayers).getBounds(), { padding: [24, 24] });
            } else {
                campusEventMap.setView([10.2925, 124.9985], 18);
            }
        }

        function setSelectionMethod(method) {
            const mapMode = method === 'map';
            mapPickerWrap.hidden = !mapMode;
            dropdownPicker.hidden = mapMode;

            methodButtons.forEach(button => {
                button.classList.toggle('active', button.dataset.eventTargetMethod === method);
            });

            if (mapMode) {
                initializeEventMap();
                setTimeout(() => campusEventMap?.invalidateSize(), 0);
            }
        }

        methodButtons.forEach(button => {
            button.addEventListener('click', () => setSelectionMethod(button.dataset.eventTargetMethod));
        });

        mapSelection.addEventListener('click', function (event) {
            const buildingButton = event.target.closest('[data-select-map-building]');
            const roomButton = event.target.closest('[data-select-map-room]');
            const landuseButton = event.target.closest('[data-select-map-landuse]');

            if (buildingButton) {
                chooseDestination('building', buildingButton.dataset.selectMapBuilding);
                renderBuildingPicker(eventMapData.buildings.find(item => Number(item.id) === Number(buildingButton.dataset.selectMapBuilding)));
            }

            if (roomButton) {
                chooseDestination('room', roomButton.dataset.selectMapRoom);
                const building = eventMapData.buildings.find(item => Number(item.id) === Number(previewedBuildingId));
                if (building) renderBuildingPicker(building);
            }

            if (landuseButton) {
                chooseDestination('landuse', landuseButton.dataset.selectMapLanduse);
                const landuse = eventMapData.landuses.find(item => Number(item.id) === Number(landuseButton.dataset.selectMapLanduse));
                if (landuse) renderLandusePicker(landuse);
            }
        });

        targetType.addEventListener('change', function () {
            toggleTargetBox();
            updateSelectionSummary();
        });
        buildingSelect.addEventListener('change', updateSelectionSummary);
        roomSelect.addEventListener('change', updateSelectionSummary);
        landuseSelect.addEventListener('change', updateSelectionSummary);

        toggleTargetBox();
        initializeEventMap();
        updateSelectionSummary();
    });
</script>

@endsection
