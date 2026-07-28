@extends('admin.dashboard')

@section('admin')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
    .dk-page {
        padding: 24px;
    }

    .dk-card {
        overflow: hidden;
        margin-bottom: 24px;
        border: 1px solid rgba(104, 167, 238, .28);
        border-radius: 22px 7px 22px 7px;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 20px 50px rgba(24, 55, 93, .1);
    }

    .dk-card-header {
        padding: 22px 24px;
        color: #fff;
        background:
            radial-gradient(circle at 90% 10%, rgba(255, 255, 255, .2), transparent 30%),
            linear-gradient(135deg, #18375d, #68a7ee);
    }

    .dk-card-header h2,
    .dk-card-header h3 {
        margin: 0;
        color: #fff;
        font-weight: 800;
    }

    .dk-card-header p {
        max-width: 850px;
        margin: 6px 0 0;
        color: rgba(255, 255, 255, .84);
        font-size: 13px;
    }

    .dk-card-body {
        padding: 22px;
    }

    .dk-alert {
        padding: 14px 16px;
        margin-bottom: 16px;
        color: #18375d;
        border: 1px solid rgba(104, 167, 238, .32);
        border-radius: 14px 5px 14px 5px;
        background: #fff;
        font-weight: 700;
    }

    .dk-alert-error {
        border-color: rgba(190, 24, 93, .3);
        background: #fff1f5;
    }

    .dk-sync-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
        padding: 14px 16px;
        color: #18375d;
        border: 1px solid rgba(104, 167, 238, .24);
        border-radius: 15px 5px 15px 5px;
        background: rgba(104, 167, 238, .09);
    }

    .dk-sync-row p {
        margin: 0;
        color: #45698f;
        font-size: 12px;
    }

    .dk-btn {
        display: inline-flex;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 10px 15px;
        cursor: pointer;
        color: #18375d;
        border: 1px solid rgba(104, 167, 238, .35);
        border-radius: 13px 4px 13px 4px;
        background: rgba(104, 167, 238, .1);
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        transition: .16s ease;
    }

    .dk-btn:hover {
        color: #fff;
        border-color: #18375d;
        background: #18375d;
    }

    .dk-btn-primary,
    .dk-btn-sync {
        color: #fff;
        border-color: #68a7ee;
        background: linear-gradient(135deg, #18375d, #68a7ee);
        box-shadow: 0 12px 26px rgba(24, 55, 93, .16);
    }

    .dk-btn-danger {
        min-height: 34px;
        padding: 7px 10px;
        color: #9f1239;
        border-color: rgba(190, 24, 93, .24);
        background: #fff1f2;
    }

    .dk-methods {
        display: inline-flex;
        gap: 7px;
        margin-bottom: 15px;
        padding: 5px;
        border: 1px solid rgba(104, 167, 238, .25);
        border-radius: 15px 5px 15px 5px;
        background: rgba(104, 167, 238, .08);
    }

    .dk-method {
        padding: 9px 14px;
        color: #18375d;
        border: 0;
        border-radius: 11px 4px 11px 4px;
        background: transparent;
        font-size: 12px;
        font-weight: 800;
    }

    .dk-method.active {
        color: #fff;
        background: linear-gradient(135deg, #18375d, #68a7ee);
    }

    .dk-picker-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(290px, .75fr);
        overflow: hidden;
        min-height: 430px;
        border: 1px solid rgba(104, 167, 238, .3);
        border-radius: 18px 6px 18px 6px;
        background: #f7fbff;
    }

    #destinationKeywordMap {
        z-index: 1;
        min-height: 430px;
        background: #eef6ff;
    }

    .dk-map-panel {
        overflow-y: auto;
        max-height: 430px;
        padding: 18px;
        border-left: 1px solid rgba(104, 167, 238, .24);
        background: linear-gradient(155deg, #fff, rgba(104, 167, 238, .09));
    }

    .dk-map-empty {
        display: flex;
        min-height: 360px;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 28px;
        text-align: center;
        color: #45698f;
    }

    .dk-map-empty i {
        margin-bottom: 12px;
        color: #68a7ee;
        font-size: 34px;
    }

    .dk-map-kicker {
        margin-bottom: 5px;
        color: #5280b5;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .dk-map-title {
        color: #18375d;
        font-size: 18px;
        font-weight: 800;
    }

    .dk-map-meta {
        margin-top: 4px;
        color: #45698f;
        font-size: 11px;
    }

    .dk-use-destination {
        width: 100%;
        margin-top: 14px;
    }

    .dk-room-heading {
        margin: 18px 0 8px;
        color: #18375d;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .dk-room-filter,
    .dk-input,
    .dk-select,
    .dk-textarea {
        width: 100%;
        color: #18375d;
        border: 1px solid rgba(104, 167, 238, .4);
        border-radius: 13px 4px 13px 4px;
        outline: none;
        background: #fff;
        font: inherit;
        font-size: 13px;
    }

    .dk-room-filter,
    .dk-input,
    .dk-select {
        min-height: 43px;
        padding: 10px 12px;
    }

    .dk-textarea {
        min-height: 105px;
        padding: 12px;
        resize: vertical;
    }

    .dk-room-filter:focus,
    .dk-input:focus,
    .dk-select:focus,
    .dk-textarea:focus {
        border-color: #68a7ee;
        box-shadow: 0 0 0 4px rgba(104, 167, 238, .14);
    }

    .dk-room-list {
        display: grid;
        gap: 7px;
        margin-top: 9px;
    }

    .dk-room-choice {
        width: 100%;
        padding: 10px 11px;
        text-align: left;
        color: #18375d;
        border: 1px solid rgba(104, 167, 238, .24);
        border-radius: 12px 4px 12px 4px;
        background: #fff;
    }

    .dk-room-choice:hover,
    .dk-room-choice.active {
        border-color: #68a7ee;
        background: rgba(104, 167, 238, .13);
    }

    .dk-room-choice strong,
    .dk-room-choice small {
        display: block;
    }

    .dk-room-choice small {
        margin-top: 2px;
        color: #527397;
        font-size: 10px;
    }

    .dk-dropdown-picker[hidden],
    .dk-map-picker[hidden] {
        display: none !important;
    }

    .dk-dropdown-grid,
    .dk-keyword-fields {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .dk-dropdown-grid {
        padding: 18px;
        border: 1px solid rgba(104, 167, 238, .25);
        border-radius: 16px 5px 16px 5px;
        background: rgba(104, 167, 238, .07);
    }

    .dk-field {
        display: flex;
        gap: 7px;
        flex-direction: column;
    }

    .dk-field-wide {
        grid-column: 1 / -1;
    }

    .dk-field label {
        margin: 0;
        color: #18375d;
        font-size: 11px;
        font-weight: 800;
    }

    .dk-help {
        color: #527397;
        font-size: 10px;
    }

    .dk-selected-summary {
        display: flex;
        align-items: center;
        gap: 11px;
        margin: 18px 0;
        padding: 13px 15px;
        color: #18375d;
        border: 1px solid rgba(104, 167, 238, .3);
        border-radius: 14px 5px 14px 5px;
        background: rgba(104, 167, 238, .1);
    }

    .dk-selected-summary i {
        color: #68a7ee;
        font-size: 23px;
    }

    .dk-selected-summary strong,
    .dk-selected-summary small {
        display: block;
    }

    .dk-selected-summary small {
        margin-top: 2px;
        color: #527397;
    }

    .dk-form-error {
        display: none;
        margin: -7px 0 14px;
        color: #b4234d;
        font-size: 11px;
        font-weight: 800;
    }

    .dk-form-error.visible {
        display: block;
    }

    .dk-directory-layout {
        display: grid;
        grid-template-columns: 260px minmax(0, 1fr);
        min-height: 420px;
    }

    .dk-group-browser {
        padding: 16px;
        border-right: 1px solid rgba(104, 167, 238, .22);
        background: rgba(104, 167, 238, .07);
    }

    .dk-group-browser h4 {
        margin: 0 0 4px;
        color: #18375d;
        font-size: 13px;
        font-weight: 900;
    }

    .dk-group-browser > p {
        margin: 0 0 12px;
        color: #527397;
        font-size: 10px;
    }

    .dk-group-list {
        display: grid;
        gap: 7px;
        max-height: 540px;
        overflow-y: auto;
        padding-right: 3px;
    }

    .dk-group-link {
        display: grid;
        grid-template-columns: 30px minmax(0, 1fr) auto;
        align-items: center;
        gap: 8px;
        padding: 9px;
        color: #18375d;
        border: 1px solid rgba(104, 167, 238, .22);
        border-radius: 12px 4px 12px 4px;
        background: #fff;
        text-decoration: none;
    }

    .dk-group-link:hover,
    .dk-group-link.active {
        color: #18375d;
        border-color: #68a7ee;
        background: rgba(104, 167, 238, .14);
    }

    .dk-group-link i {
        display: inline-flex;
        width: 30px;
        height: 30px;
        align-items: center;
        justify-content: center;
        color: #18375d;
        border-radius: 9px 3px 9px 3px;
        background: rgba(104, 167, 238, .18);
    }

    .dk-group-link strong {
        overflow: hidden;
        font-size: 10px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dk-group-link span {
        min-width: 25px;
        padding: 3px 6px;
        text-align: center;
        color: #45698f;
        border-radius: 999px;
        background: rgba(104, 167, 238, .13);
        font-size: 9px;
        font-weight: 900;
    }

    .dk-directory-content {
        min-width: 0;
        padding: 18px;
    }

    .dk-directory-head {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 14px;
    }

    .dk-directory-head h3 {
        margin: 0;
        color: #18375d;
        font-size: 17px;
        font-weight: 900;
    }

    .dk-directory-head p {
        margin: 3px 0 0;
        color: #527397;
        font-size: 10px;
    }

    .dk-search-form {
        display: flex;
        min-width: min(100%, 420px);
        gap: 7px;
    }

    .dk-table-wrap {
        overflow-x: auto;
        border: 1px solid rgba(104, 167, 238, .2);
        border-radius: 14px 5px 14px 5px;
    }

    .dk-table {
        width: 100%;
        min-width: 760px;
        border-collapse: collapse;
    }

    .dk-table th,
    .dk-table td {
        padding: 13px 14px;
        color: #18375d;
        border-bottom: 1px solid rgba(104, 167, 238, .17);
        font-size: 12px;
        vertical-align: middle;
    }

    .dk-table th {
        background: rgba(104, 167, 238, .14);
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .dk-keyword {
        color: #18375d;
        font-weight: 900;
    }

    .dk-destination strong,
    .dk-destination small {
        display: block;
    }

    .dk-destination small {
        max-width: 320px;
        margin-top: 3px;
        color: #527397;
        font-size: 9px;
    }

    .dk-badge {
        display: inline-flex;
        padding: 5px 8px;
        color: #18375d;
        border: 1px solid rgba(104, 167, 238, .28);
        border-radius: 999px;
        background: rgba(104, 167, 238, .12);
        font-size: 9px;
        font-weight: 900;
    }

    .dk-empty {
        padding: 48px 20px;
        text-align: center;
        color: #527397;
    }

    @media (max-width: 1100px) {
        .dk-picker-grid {
            grid-template-columns: 1fr;
        }

        .dk-map-panel {
            max-height: none;
            border-top: 1px solid rgba(104, 167, 238, .24);
            border-left: 0;
        }

        .dk-map-empty {
            min-height: 150px;
        }

        .dk-directory-layout {
            grid-template-columns: 1fr;
        }

        .dk-group-browser {
            border-right: 0;
            border-bottom: 1px solid rgba(104, 167, 238, .22);
        }

        .dk-group-list {
            display: flex;
            max-height: none;
            overflow-x: auto;
        }

        .dk-group-link {
            min-width: 190px;
        }
    }

    @media (max-width: 768px) {
        .dk-page,
        .dk-card-body,
        .dk-directory-content {
            padding: 14px;
        }

        .dk-sync-row,
        .dk-directory-head {
            align-items: stretch;
            flex-direction: column;
        }

        .dk-dropdown-grid,
        .dk-keyword-fields {
            grid-template-columns: 1fr;
        }

        .dk-field-wide {
            grid-column: auto;
        }

        .dk-search-form {
            min-width: 0;
            width: 100%;
        }

        #destinationKeywordMap {
            min-height: 360px;
        }
    }
</style>

<div class="dk-page">
    @if(session('success'))
        <div class="dk-alert"><strong>Success:</strong> {{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="dk-alert dk-alert-error"><strong>Unable to save:</strong> {{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="dk-alert dk-alert-error">
            <strong>Please check the form:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="dk-card">
        <header class="dk-card-header">
            <h2>Destination Keyword Mapper</h2>
            <p>Click a building or outdoor area on the map, then choose the entire destination or one of its rooms.</p>
        </header>

        <div class="dk-card-body">
            <div class="dk-sync-row">
                <div>
                    <strong>Automatic keyword assistant</strong>
                    <p>Generate missing names, room codes, acronyms, and common aliases without deleting manual entries.</p>
                </div>
                <form action="{{ route('admin.destination-keyword.sync') }}" method="POST">
                    @csrf
                    <button type="submit" class="dk-btn dk-btn-sync">
                        <i class="ri-magic-line"></i> Generate Missing Keywords
                    </button>
                </form>
            </div>

            <form action="{{ route('admin.destination-keyword.store') }}" method="POST" id="destinationKeywordForm">
                @csrf

                <div class="dk-methods" role="group" aria-label="Destination selection method">
                    <button type="button" class="dk-method active" data-dk-method="map">
                        <i class="ri-map-2-line"></i> Select on Map
                    </button>
                    <button type="button" class="dk-method" data-dk-method="dropdown">
                        <i class="ri-list-check-2"></i> Use Dropdown
                    </button>
                </div>

                <div class="dk-map-picker" id="dkMapPicker">
                    <div class="dk-picker-grid">
                        <div id="destinationKeywordMap" aria-label="Destination keyword campus map"></div>
                        <aside class="dk-map-panel">
                            <div class="dk-map-empty" id="dkMapEmpty">
                                <i class="ri-cursor-line"></i>
                                <strong>Select a destination</strong>
                                <span>Click a building or land-use area on the map.</span>
                            </div>
                            <div id="dkMapSelection" hidden></div>
                        </aside>
                    </div>
                </div>

                <div class="dk-dropdown-picker" id="dkDropdownPicker" hidden>
                    <div class="dk-dropdown-grid">
                        <div class="dk-field">
                            <label for="dkDestinationTypeSelect">Destination type</label>
                            <select id="dkDestinationTypeSelect" class="dk-select">
                                <option value="">Choose destination type</option>
                                <option value="building">Building</option>
                                <option value="room">Room / Office</option>
                                <option value="landuse">Land-use Area</option>
                            </select>
                        </div>

                        <div class="dk-field" id="dkBuildingField" hidden>
                            <label for="dkBuildingSelect">Building</label>
                            <select id="dkBuildingSelect" class="dk-select">
                                <option value="">Choose building</option>
                                @foreach($buildings as $building)
                                    <option value="{{ $building->id }}">{{ $building->name ?: 'Unnamed Building' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="dk-field" id="dkRoomField" hidden>
                            <label for="dkRoomSelect">Room / Office</label>
                            <select id="dkRoomSelect" class="dk-select">
                                <option value="">Choose room or office</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}">
                                        {{ $room->room_code ?: ($room->name ?: 'Unnamed Room') }}
                                        @if($room->name && $room->room_code) — {{ $room->name }} @endif
                                        @if($room->indoorMap?->building) — {{ $room->indoorMap->building->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="dk-field" id="dkLanduseField" hidden>
                            <label for="dkLanduseSelect">Land-use area</label>
                            <select id="dkLanduseSelect" class="dk-select">
                                <option value="">Choose land-use area</option>
                                @foreach($landuses as $landuse)
                                    <option value="{{ $landuse->id }}">{{ $landuse->name ?: 'Unnamed Area' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="destination_type" id="dkDestinationType" value="{{ old('destination_type') }}">
                <input type="hidden" name="destination_id" id="dkDestinationId" value="{{ old('destination_id') }}">

                <div class="dk-selected-summary" id="dkSelectedSummary">
                    <i class="ri-map-pin-line"></i>
                    <div>
                        <strong>No destination selected</strong>
                        <small>Choose a building, room, or outdoor area before saving keywords.</small>
                    </div>
                </div>
                <div class="dk-form-error" id="dkDestinationError">Please select a destination first.</div>

                <div class="dk-keyword-fields">
                    <div class="dk-field dk-field-wide">
                        <label for="dkKeywords">Keywords</label>
                        <textarea
                            name="keywords"
                            id="dkKeywords"
                            class="dk-textarea"
                            placeholder="Example: registrar, registrar office, records office"
                            required
                        >{{ old('keywords') }}</textarea>
                        <span class="dk-help">Separate multiple keywords with commas.</span>
                    </div>

                    <div class="dk-field">
                        <label for="dkPriority">Search priority</label>
                        <select name="priority" id="dkPriority" class="dk-select">
                            <option value="1" @selected((int) old('priority', 1) === 1)>Low</option>
                            <option value="2" @selected((int) old('priority') === 2)>Medium</option>
                            <option value="3" @selected((int) old('priority') === 3)>High</option>
                        </select>
                    </div>

                    <div class="dk-field" style="justify-content:end;">
                        <button type="submit" class="dk-btn dk-btn-primary">
                            <i class="ri-save-3-line"></i> Save Destination Keywords
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <section class="dk-card">
        <header class="dk-card-header">
            <h3>Keyword Directory by Building</h3>
            <p>Select a building to view both its building keywords and the keywords assigned to rooms inside it.</p>
        </header>

        <div class="dk-directory-layout">
            <aside class="dk-group-browser">
                <h4>Destination groups</h4>
                <p>Choose one group to narrow the directory.</p>

                <nav class="dk-group-list" aria-label="Keyword destination groups">
                    <a
                        href="{{ route('admin.destination-keyword', array_filter(['search' => $search])) }}"
                        class="dk-group-link {{ $selectedGroup === 'all' ? 'active' : '' }}"
                    >
                        <i class="ri-apps-2-line"></i>
                        <strong>All Destinations</strong>
                        <span>{{ $allKeywordCount }}</span>
                    </a>

                    @foreach($buildings as $building)
                        @php($groupValue = 'building:'.$building->id)
                        <a
                            href="{{ route('admin.destination-keyword', array_filter(['destination_group' => $groupValue, 'search' => $search])) }}"
                            class="dk-group-link {{ $selectedGroup === $groupValue ? 'active' : '' }}"
                            title="{{ $building->name }}"
                        >
                            <i class="ri-building-2-line"></i>
                            <strong>{{ $building->name ?: 'Unnamed Building' }}</strong>
                            <span>{{ $buildingKeywordCounts->get($building->id, 0) }}</span>
                        </a>
                    @endforeach

                    <a
                        href="{{ route('admin.destination-keyword', array_filter(['destination_group' => 'landuse', 'search' => $search])) }}"
                        class="dk-group-link {{ $selectedGroup === 'landuse' ? 'active' : '' }}"
                    >
                        <i class="ri-landscape-line"></i>
                        <strong>Outdoor Areas</strong>
                        <span>{{ $landuseKeywordCount }}</span>
                    </a>
                </nav>
            </aside>

            <div class="dk-directory-content">
                <div class="dk-directory-head">
                    <div>
                        <h3>
                            @if($selectedGroup === 'landuse')
                                Outdoor Area Keywords
                            @elseif(str_starts_with($selectedGroup, 'building:'))
                                {{ $buildings->firstWhere('id', (int) str($selectedGroup)->after('building:')->toString())?->name ?? 'Building' }} Keywords
                            @else
                                All Destination Keywords
                            @endif
                        </h3>
                        <p>{{ $keywords->total() }} matching keyword{{ $keywords->total() === 1 ? '' : 's' }}</p>
                    </div>

                    <form action="{{ route('admin.destination-keyword') }}" method="GET" class="dk-search-form">
                        @if($selectedGroup !== 'all')
                            <input type="hidden" name="destination_group" value="{{ $selectedGroup }}">
                        @endif
                        <input
                            type="search"
                            name="search"
                            class="dk-input"
                            value="{{ $search }}"
                            placeholder="Search keyword, room, or destination"
                            aria-label="Search destination keywords"
                        >
                        <button type="submit" class="dk-btn dk-btn-primary"><i class="ri-search-line"></i> Search</button>
                        @if($search !== '')
                            <a
                                href="{{ route('admin.destination-keyword', $selectedGroup === 'all' ? [] : ['destination_group' => $selectedGroup]) }}"
                                class="dk-btn"
                            >Clear</a>
                        @endif
                    </form>
                </div>

                @if($keywords->count())
                    <div class="dk-table-wrap">
                        <table class="dk-table">
                            <thead>
                                <tr>
                                    <th>Keyword</th>
                                    <th>Destination</th>
                                    <th>Type</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($keywords as $item)
                                    <tr>
                                        <td><span class="dk-keyword">{{ $item->keyword }}</span></td>
                                        <td class="dk-destination">
                                            <strong>{{ $item->destination_label }}</strong>
                                            <small>{{ $item->destination_context }}</small>
                                        </td>
                                        <td><span class="dk-badge">{{ ucfirst($item->destination_type) }}</span></td>
                                        <td><span class="dk-badge">{{ ['Low', 'Medium', 'High'][(int) $item->priority - 1] ?? 'Low' }}</span></td>
                                        <td>
                                            <form
                                                action="{{ route('admin.destination-keyword.destroy', $item) }}"
                                                method="POST"
                                                onsubmit="return confirm('Delete this destination keyword?');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dk-btn dk-btn-danger">
                                                    <i class="ri-delete-bin-line"></i> Delete
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
                        <i class="ri-search-eye-line fs-2 d-block mb-2"></i>
                        No keywords found in this destination group.
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mapData = @json($mapData);
        const destinationType = document.getElementById('dkDestinationType');
        const destinationId = document.getElementById('dkDestinationId');
        const selectedSummary = document.getElementById('dkSelectedSummary');
        const destinationError = document.getElementById('dkDestinationError');
        const mapPicker = document.getElementById('dkMapPicker');
        const dropdownPicker = document.getElementById('dkDropdownPicker');
        const mapEmpty = document.getElementById('dkMapEmpty');
        const mapSelection = document.getElementById('dkMapSelection');
        const methodButtons = document.querySelectorAll('[data-dk-method]');
        const typeSelect = document.getElementById('dkDestinationTypeSelect');
        const buildingSelect = document.getElementById('dkBuildingSelect');
        const roomSelect = document.getElementById('dkRoomSelect');
        const landuseSelect = document.getElementById('dkLanduseSelect');
        const buildingField = document.getElementById('dkBuildingField');
        const roomField = document.getElementById('dkRoomField');
        const landuseField = document.getElementById('dkLanduseField');
        const keywordForm = document.getElementById('destinationKeywordForm');
        const buildingLayers = new Map();
        const landuseLayers = new Map();
        let keywordMap = null;
        let previewedBuildingId = null;

        const escapeHtml = value => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const roomLabel = room => {
            const code = String(room?.room_code || '').trim();
            const name = String(room?.name || '').trim();
            return code && name ? `${code} — ${name}` : (code || name || 'Unnamed Room');
        };

        function selectedDestination() {
            const type = destinationType.value;
            const id = Number(destinationId.value);

            if (!type || !id) return null;
            if (type === 'building') return mapData.buildings.find(item => Number(item.id) === id);
            if (type === 'room') return mapData.rooms.find(item => Number(item.id) === id);
            return mapData.landuses.find(item => Number(item.id) === id);
        }

        function updateSummary() {
            const destination = selectedDestination();

            if (!destination) {
                selectedSummary.innerHTML = `
                    <i class="ri-map-pin-line"></i>
                    <div><strong>No destination selected</strong><small>Choose a building, room, or outdoor area before saving keywords.</small></div>
                `;
                return;
            }

            const type = destinationType.value;
            const title = type === 'room' ? roomLabel(destination) : (destination.name || 'Selected Destination');
            const context = type === 'room'
                ? [destination.building_name, destination.floor_label].filter(Boolean).join(' · ')
                : (type === 'building' ? 'Entire building' : 'Outdoor land-use area');

            selectedSummary.innerHTML = `
                <i class="${type === 'room' ? 'ri-door-line' : (type === 'building' ? 'ri-building-2-line' : 'ri-landscape-line')}"></i>
                <div><strong>${escapeHtml(title)}</strong><small>${escapeHtml(context)}</small></div>
            `;
            destinationError.classList.remove('visible');
        }

        function highlightTarget(type, id) {
            buildingLayers.forEach((layer, layerId) => {
                layer.setStyle({
                    color: Number(layerId) === Number(id) && type === 'building' ? '#18375d' : '#245a86',
                    weight: Number(layerId) === Number(id) && type === 'building' ? 5 : 2,
                    fillColor: Number(layerId) === Number(id) && type === 'building' ? '#68a7ee' : '#8fc5ee',
                    fillOpacity: Number(layerId) === Number(id) && type === 'building' ? 1 : .9,
                });
            });

            landuseLayers.forEach((layer, layerId) => {
                layer.setStyle({
                    color: Number(layerId) === Number(id) && type === 'landuse' ? '#18375d' : '#4e8b69',
                    weight: Number(layerId) === Number(id) && type === 'landuse' ? 5 : 2,
                    fillColor: Number(layerId) === Number(id) && type === 'landuse' ? '#68a7ee' : '#b8dfc4',
                    fillOpacity: Number(layerId) === Number(id) && type === 'landuse' ? .55 : .26,
                });
            });
        }

        function chooseDestination(type, id) {
            destinationType.value = type;
            destinationId.value = String(id || '');
            typeSelect.value = type;

            buildingField.hidden = type !== 'building';
            roomField.hidden = type !== 'room';
            landuseField.hidden = type !== 'landuse';

            if (type === 'building') buildingSelect.value = String(id);
            if (type === 'room') roomSelect.value = String(id);
            if (type === 'landuse') landuseSelect.value = String(id);

            updateSummary();
            highlightTarget(type, id);
        }

        function renderBuildingPicker(building) {
            if (!building) return;

            previewedBuildingId = Number(building.id);
            const rooms = mapData.rooms.filter(room => Number(room.building_id) === Number(building.id));
            mapEmpty.hidden = true;
            mapSelection.hidden = false;
            mapSelection.innerHTML = `
                <div class="dk-map-kicker">Selected Building</div>
                <div class="dk-map-title">${escapeHtml(building.name || 'Building')}</div>
                <div class="dk-map-meta">${rooms.length} room${rooms.length === 1 ? '' : 's'} available</div>
                <button type="button" class="dk-btn dk-btn-primary dk-use-destination" data-dk-building="${Number(building.id)}">
                    <i class="ri-building-2-line"></i> Use Entire Building
                </button>
                <div class="dk-room-heading">Or choose a room inside</div>
                ${rooms.length ? '<input type="search" class="dk-room-filter" id="dkRoomFilter" placeholder="Filter room name or code">' : ''}
                <div class="dk-room-list" id="dkRoomList"></div>
            `;

            const renderRooms = search => {
                const normalized = String(search || '').trim().toLowerCase();
                const filtered = rooms.filter(room => {
                    const text = [room.room_code, room.name, room.floor_label, room.type].filter(Boolean).join(' ').toLowerCase();
                    return !normalized || text.includes(normalized);
                });
                const list = document.getElementById('dkRoomList');

                list.innerHTML = filtered.length
                    ? filtered.map(room => `
                        <button type="button"
                                class="dk-room-choice ${destinationType.value === 'room' && Number(destinationId.value) === Number(room.id) ? 'active' : ''}"
                                data-dk-room="${Number(room.id)}">
                            <strong>${escapeHtml(roomLabel(room))}</strong>
                            <small>${escapeHtml(room.floor_label || 'Floor not specified')}${room.type ? ` · ${escapeHtml(room.type)}` : ''}</small>
                        </button>
                    `).join('')
                    : '<div class="dk-map-meta">No matching rooms found.</div>';
            };

            renderRooms('');
            document.getElementById('dkRoomFilter')?.addEventListener('input', event => renderRooms(event.target.value));
            highlightTarget('building', building.id);
        }

        function renderLandusePicker(landuse) {
            if (!landuse) return;

            previewedBuildingId = null;
            mapEmpty.hidden = true;
            mapSelection.hidden = false;
            mapSelection.innerHTML = `
                <div class="dk-map-kicker">Selected Outdoor Area</div>
                <div class="dk-map-title">${escapeHtml(landuse.name || 'Land-use Area')}</div>
                <div class="dk-map-meta">Available as an outdoor search destination</div>
                <button type="button" class="dk-btn dk-btn-primary dk-use-destination" data-dk-landuse="${Number(landuse.id)}">
                    <i class="ri-landscape-line"></i> Use This Area
                </button>
            `;
            highlightTarget('landuse', landuse.id);
        }

        function initializeMap() {
            if (keywordMap || typeof L === 'undefined') return;

            keywordMap = L.map('destinationKeywordMap', {
                zoomControl: true,
                preferCanvas: true,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 21,
            }).addTo(keywordMap);

            const visibleLayers = [];

            mapData.paths.forEach(path => {
                if (!path.geometry) return;
                const layer = L.geoJSON({ type: 'Feature', geometry: path.geometry }, {
                    style: {
                        color: path.type === 'walkway' ? '#68a7ee' : '#526c88',
                        weight: path.type === 'walkway' ? 3 : 4,
                        opacity: .72,
                    },
                    interactive: false,
                }).addTo(keywordMap);
                visibleLayers.push(layer);
            });

            mapData.landuses.forEach(landuse => {
                if (!landuse.geometry) return;
                const layer = L.geoJSON({ type: 'Feature', geometry: landuse.geometry }, {
                    style: { color: '#4e8b69', weight: 2, fillColor: '#b8dfc4', fillOpacity: .26 },
                }).addTo(keywordMap);
                layer.bindTooltip(landuse.name || 'Land-use Area', { sticky: true });
                layer.on('click', event => {
                    L.DomEvent.stopPropagation(event);
                    renderLandusePicker(landuse);
                });
                landuseLayers.set(Number(landuse.id), layer);
                visibleLayers.push(layer);
            });

            mapData.buildings.forEach(building => {
                if (!building.geometry) return;
                const layer = L.geoJSON({ type: 'Feature', geometry: building.geometry }, {
                    style: { color: '#245a86', weight: 2, fillColor: building.color || '#8fc5ee', fillOpacity: .9 },
                }).addTo(keywordMap);
                layer.bindTooltip(building.name || 'Building', { sticky: true });
                layer.on('click', event => {
                    L.DomEvent.stopPropagation(event);
                    renderBuildingPicker(building);
                });
                buildingLayers.set(Number(building.id), layer);
                visibleLayers.push(layer);
            });

            if (visibleLayers.length) {
                keywordMap.fitBounds(L.featureGroup(visibleLayers).getBounds(), { padding: [24, 24] });
            } else {
                keywordMap.setView([10.2925, 124.9985], 18);
            }
        }

        function setMethod(method) {
            const mapMode = method === 'map';
            mapPicker.hidden = !mapMode;
            dropdownPicker.hidden = mapMode;

            methodButtons.forEach(button => {
                button.classList.toggle('active', button.dataset.dkMethod === method);
            });

            if (mapMode) {
                initializeMap();
                setTimeout(() => keywordMap?.invalidateSize(), 0);
            }
        }

        function updateDropdownFields() {
            const type = typeSelect.value;
            buildingField.hidden = type !== 'building';
            roomField.hidden = type !== 'room';
            landuseField.hidden = type !== 'landuse';

            const id = type === 'building'
                ? buildingSelect.value
                : (type === 'room' ? roomSelect.value : landuseSelect.value);

            if (type && id) {
                chooseDestination(type, id);
            } else {
                destinationType.value = type;
                destinationId.value = '';
                updateSummary();
            }
        }

        methodButtons.forEach(button => {
            button.addEventListener('click', () => setMethod(button.dataset.dkMethod));
        });

        mapSelection.addEventListener('click', function (event) {
            const buildingButton = event.target.closest('[data-dk-building]');
            const roomButton = event.target.closest('[data-dk-room]');
            const landuseButton = event.target.closest('[data-dk-landuse]');

            if (buildingButton) {
                chooseDestination('building', buildingButton.dataset.dkBuilding);
                renderBuildingPicker(mapData.buildings.find(item => Number(item.id) === Number(buildingButton.dataset.dkBuilding)));
            } else if (roomButton) {
                chooseDestination('room', roomButton.dataset.dkRoom);
                const building = mapData.buildings.find(item => Number(item.id) === Number(previewedBuildingId));
                if (building) renderBuildingPicker(building);
            } else if (landuseButton) {
                chooseDestination('landuse', landuseButton.dataset.dkLanduse);
                renderLandusePicker(mapData.landuses.find(item => Number(item.id) === Number(landuseButton.dataset.dkLanduse)));
            }
        });

        typeSelect.addEventListener('change', updateDropdownFields);
        buildingSelect.addEventListener('change', updateDropdownFields);
        roomSelect.addEventListener('change', updateDropdownFields);
        landuseSelect.addEventListener('change', updateDropdownFields);

        keywordForm.addEventListener('submit', function (event) {
            if (!destinationType.value || !destinationId.value) {
                event.preventDefault();
                destinationError.classList.add('visible');
                selectedSummary.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        initializeMap();
        updateSummary();

        const oldType = @json(old('destination_type'));
        const oldId = @json(old('destination_id'));
        if (oldType && oldId) chooseDestination(oldType, oldId);
    });
</script>
@endsection
