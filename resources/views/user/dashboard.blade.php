<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLSU Campus - Smart Navigation</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    @include('user.style.style')
</head>

<body>

    <!-- SLSU SMART CAMPUS BRAND (TOP LEFT) -->
    <div class="campus-brand-wrap" aria-label="Smart Campus Navigation System">
        <div class="campus-brand-logo-shell">
            <img src="{{ asset('background/slsu-logo.jpg') }}"
                 alt="Southern Leyte State University Logo"
                 class="campus-brand-logo">
        </div>

        <div class="campus-brand-text">
            <div class="campus-brand-title">Smart Campus</div>
            <div class="campus-brand-subtitle">Navigation System</div>
        </div>
    </div>


    <!-- USER PROFILE TOP ACTION -->
    <div class="user-profile-wrap" id="user-profile-wrap">
        <button type="button"
                class="user-profile-btn"
                id="user-profile-btn"
                onclick="toggleUserProfileMenu(event)"
                aria-label="Open user profile menu">

            @if(auth()->check() && auth()->user()->photo)
                <img src="{{ asset('image/' . auth()->user()->photo) }}"
                     alt="Profile"
                     class="user-profile-img">
            @else
                <span class="user-profile-icon">👤</span>
            @endif
        </button>

        <div class="user-profile-menu" id="user-profile-menu">
            <div class="user-profile-info">
                <div class="user-profile-avatar">
                    @if(auth()->check() && auth()->user()->photo)
                        <img src="{{ asset('image/' . auth()->user()->photo) }}" alt="Profile">
                    @else
                        <span>👤</span>
                    @endif
                </div>

                <div class="user-profile-text">
                    <div class="user-profile-name">
                        {{ auth()->check() ? (auth()->user()->username ?? 'User') : 'User' }}
                    </div>
                    <div class="user-profile-email">
                        {{ auth()->check() ? auth()->user()->email : '' }}
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('user.logout') }}" class="user-logout-form">
                @csrf
                <button type="submit" class="user-logout-btn">
                    <span>🚪</span>
                    Logout
                </button>
            </form>
        </div>
    </div>

    <!-- FLOATING MAIN CONTROLS -->
    <div id="floating-route-ui" class="ai-floating-dock">
        <div class="floating-ai-badge">
            <span class="ai-dot"></span>
            Campus Navigator
        </div>

        <div class="ai-orb-shell" id="ai-orb-shell">
            <!-- SECONDARY ACTION CARD -->
            <div class="floating-action-card" id="floating-action-card" style="display:none;">
                <div class="floating-action-head">
                    <div class="floating-action-kicker">Assistant</div>
                    <div class="floating-action-title">Choose how you want to search your destination</div>
                </div>

                <button type="button" class="floating-action-btn" onclick="openInlineTextSearch()">
                    <span class="action-icon">⌨️</span>
                    Search Text
                </button>

                <button type="button" class="floating-action-btn dark" id="voice-command-btn" onclick="openInlineVoiceSearch()">
                    <span class="action-icon">🎙️</span>
                    Voice Search
                </button>

                <button type="button" class="floating-action-btn blue" onclick="openBrowseOptionsModal()">
                    <span class="action-icon">🧭</span>
                    Browse Options
                </button>
            </div>

            <!-- MAIN ORB BUTTON -->
            <button type="button" class="floating-main-pin" id="destination-menu-toggle" onclick="toggleFloatingActionCard()">
                <span class="pin-disc">
                    <span class="pin-icon">
                        <span class="pin-hole"></span>
                    </span>
                </span>
            </button>

            <!-- TRANSFORMED TEXT SEARCH BAR -->
            <div class="ai-transform-panel ai-search-panel" id="ai-search-panel" style="display:none;">
                <button type="button" class="ai-transform-close" onclick="closeAiTransformPanel()">×</button>

                <div class="ai-transform-kicker">
                    <span class="ai-dot tiny"></span>
                     Text Search
                </div>

                <div class="ai-search-row">
                    <input type="text" id="destination-search-input" class="ai-search-input"
                        placeholder="Ask: IT building room 202...">

                    <button type="button" class="ai-search-submit" onclick="searchTextDestination()">
                        Search
                    </button>

                    <button type="button" class="ai-record-inline-btn" id="ai-text-record-btn" onclick="restartInlineVoiceSearch()">
                        🎙️ Record
                    </button>
                </div>

                <div class="ai-transform-hint">
                    Type a building, room, office, or phrase. Default start is used automatically if no GPS/Pick Path is selected.
                </div>

                <div class="ai-text-result-card" id="ai-text-result-card" style="display:none;">
                    <div class="ai-text-result-label">Search result</div>
                    <div class="ai-text-result-text" id="ai-text-result-text">-</div>
                    <div class="ai-text-result-note">
                        Search completed. This panel will stay open until you close it.
                    </div>
                </div>

            </div>

            <!-- TRANSFORMED VOICE RECORDER -->
            <div class="ai-transform-panel ai-voice-panel" id="ai-voice-panel" style="display:none;">
                <button type="button" class="ai-transform-close" onclick="closeAiTransformPanel()">×</button>

                <div class="ai-transform-kicker">
                    <span class="ai-dot tiny"></span>
                     Voice Search
                </div>

                <div class="ai-voice-core">
                    <div class="ai-voice-orb">
                        <span></span>
                    </div>

                    <div>
                        <div class="ai-voice-title">Listening for destination</div>
                        <div class="floating-voice-status" id="voice-status-label">Voice Status: Idle</div>
                    </div>
                </div>

                <div class="floating-heard-text" id="voice-heard-text" style="display:none;">
                    Heard: <span id="voice-heard-value">-</span>
                </div>

                <div class="ai-voice-result-card" id="ai-voice-result-card" style="display:none;">
                    <div class="ai-voice-result-label">Detected speech</div>
                    <div class="ai-voice-result-text" id="ai-voice-result-text">-</div>
                    <div class="ai-voice-result-note">
                        Recording stopped. Review the detected text, then close this panel when you are done.
                    </div>
                </div>

                <div class="ai-voice-button-row">
                    <button type="button" class="ai-stop-voice" onclick="stopInlineVoiceSearch()">
                        Stop Recording
                    </button>

                    <button type="button" class="ai-record-again-btn" id="ai-voice-record-again-btn" onclick="restartInlineVoiceSearch()">
                        🎙️ Record Again
                    </button>
                </div>
            </div>
        </div>

        <div class="floating-start-bar">
            <button type="button" class="floating-mode-btn pick" onclick="selectPickPathMode()">
                📍 PICK PATH
            </button>

            <button type="button" class="floating-mode-btn gps" onclick="selectGpsMode()">
                🧭 USE GPS
            </button>

            <button type="button" class="floating-mode-btn default active" onclick="selectDefaultMode()">
                🗺️ DEFAULT ROUTE
            </button>
        </div>
    </div>




    <!-- BROWSE OPTIONS MODAL - ENHANCED USER FRIENDLY DESTINATION PICKER -->
    <div class="floating-modal-backdrop" id="browseOptionsModal" style="display:none;">
        <div class="floating-modal-card browse-destination-card">
            <div class="browse-modal-glow"></div>

            <div class="browse-modal-header">
                <div>
                    <div class="floating-modal-kicker">Smart Destination Picker</div>
                    <div class="floating-modal-title">Browse Destination</div>
                    <div class="floating-modal-subtitle">
                        Filter by destination type, building, floor, then choose the exact room or office.
                    </div>
                </div>

                <button type="button" class="browse-modal-x" onclick="closeBrowseOptionsModal()" aria-label="Close browse destination">
                    ×
                </button>
            </div>

            <!-- Hidden but still needed by script -->
            <div class="route-field default-entry-hidden">
                <label class="route-label">Default Starting Point</label>
                <select id="default-entry-select" class="route-select">
                    <option value="">Default Start</option>
                </select>
            </div>

            <div class="browse-type-picker" aria-label="Destination Type">
                <button type="button" class="browse-type-card active" data-destination-type="building" onclick="setBrowseDestinationType('building')">
                    <span class="browse-type-icon">🏢</span>
                    <span>
                        <strong>Building</strong>
                        <small>Route to building entrance</small>
                    </span>
                </button>

                <button type="button" class="browse-type-card" data-destination-type="room" onclick="setBrowseDestinationType('room')">
                    <span class="browse-type-icon">🚪</span>
                    <span>
                        <strong>Room / Office</strong>
                        <small>Filter by building & floor</small>
                    </span>
                </button>

                <button type="button" class="browse-type-card" data-destination-type="landuse" onclick="setBrowseDestinationType('landuse')">
                    <span class="browse-type-icon">🌿</span>
                    <span>
                        <strong>Landuse</strong>
                        <small>Open areas and courts</small>
                    </span>
                </button>
            </div>

            <div class="route-field browse-native-type-select">
                <label class="route-label">Destination Type</label>
                <select id="destination-type-select" class="route-select">
                    <option value="building">Building</option>
                    <option value="landuse">Landuse Area</option>
                    <option value="room">Room / Office</option>
                </select>
            </div>

            <div class="browse-section" id="building-destination-wrap">
                <div class="browse-section-head">
                    <div>
                        <div class="browse-section-title">Choose Building</div>
                        <div class="browse-section-subtitle">Select the campus building you want to visit.</div>
                    </div>
                </div>

                <div class="route-field no-margin">
                    <select id="destination-building-select" class="route-select browse-big-select">
                        <option value="">Select Destination Building</option>
                    </select>
                </div>
            </div>

            <div class="browse-section" id="landuse-destination-wrap" style="display:none;">
                <div class="browse-section-head">
                    <div>
                        <div class="browse-section-title">Choose Landuse Area</div>
                        <div class="browse-section-subtitle">Select open fields, courts, gardens, or other campus areas.</div>
                    </div>
                </div>

                <div class="route-field no-margin">
                    <select id="destination-landuse-select" class="route-select browse-big-select">
                        <option value="">Select Landuse Area</option>
                    </select>
                </div>
            </div>

            <div class="browse-section room-smart-picker" id="room-destination-wrap" style="display:none;">
                <div class="browse-section-head">
                    <div>
                        <div class="browse-section-title">Find Room / Office</div>
                        <div class="browse-section-subtitle">
                            Pick a building first, choose a floor, then tap the room card. This keeps choices short and fast.
                        </div>
                    </div>
                    <div class="room-result-count" id="room-result-count">0 rooms</div>
                </div>

                <div class="room-filter-grid">
                    <div class="route-field">
                        <label class="route-label">Building Filter</label>
                        <select id="room-building-filter-select" class="route-select browse-big-select">
                            <option value="">All Buildings</option>
                        </select>
                    </div>

                    <div class="route-field">
                        <label class="route-label">Search Room / Office</label>
                        <div class="browse-search-shell">
                            <span class="browse-search-icon">⌕</span>
                            <input type="text" id="room-office-search-input" class="browse-search-input" placeholder="Search room code, office, CR...">
                        </div>
                    </div>
                </div>

                <div class="room-floor-filter-wrap">
                    <div class="room-filter-label">Floor Filter</div>
                    <div class="room-floor-chips" id="room-floor-filter-chips">
                        <button type="button" class="room-floor-chip active" data-floor="all">All Floors</button>
                    </div>
                </div>

                <!-- Hidden select kept for existing route logic. Cards below update this value. -->
                <select id="destination-room-select" class="route-select browse-hidden-room-select">
                    <option value="">Select Room / Office</option>
                </select>

                <div class="room-office-results" id="room-office-results-list">
                    <div class="room-empty-state">
                        <div class="room-empty-icon">🔎</div>
                        <div class="room-empty-title">Choose a building to narrow the list</div>
                        <div class="room-empty-text">Rooms and offices will appear here as easy tap cards.</div>
                    </div>
                </div>
            </div>

            <div class="floating-modal-actions browse-actions">
                <button type="button" class="route-btn success browse-find-btn" onclick="findRouteByDestination()">
                    <span>🧭</span> Find Route
                </button>
                <button type="button" class="route-btn neutral" onclick="resetRouteSelection(); closeBrowseOptionsModal();">
                    Reset
                </button>
                <button type="button" class="route-btn neutral" onclick="closeBrowseOptionsModal()">
                    Close
                </button>
            </div>
        </div>
    </div>


    <!-- ROUTE / BUILDING INDOOR POPUP -->
    <div id="route-building-popup" class="route-building-popup" style="display:none;">
        <button type="button" class="route-building-popup-close" onclick="closeRouteBuildingPopup()">×</button>

        <div class="route-building-popup-head">
            <div class="route-building-popup-icon">🏢</div>
            <div class="route-building-popup-title" id="route-building-popup-title">Building</div>
        </div>

        <div class="route-building-popup-divider"></div>

        <button type="button" class="route-building-popup-btn" id="route-building-popup-btn"
            onclick="openIndoorFromRoutePopup()">
            CLICK TO OPEN INDOOR ROOMS
        </button>

        <div class="route-building-popup-hint" id="route-building-popup-hint">
            You can also click the building on the map.
        </div>
    </div>




    <!-- MOBILE TAP-ONLY PICK PATH HELPER -->
    <div id="pick-path-helper" class="pick-path-helper tap-only" style="display:none;">
        <div class="pick-helper-glow"></div>
        <div class="pick-helper-top">
            <div class="pick-helper-icon">👆</div>
            <div class="pick-helper-copy">
                <div class="pick-helper-title">Tap your current spot</div>
                <div class="pick-helper-subtitle" id="pick-path-helper-text">
                    Tap anywhere on the campus map. The pin will automatically snap to the nearest path.
                </div>
            </div>
        </div>

        <div class="pick-helper-tap-demo">
            <span class="tap-demo-dot"></span>
            <span class="tap-demo-text">One tap only · Auto-snap to path</span>
        </div>

        <div class="pick-helper-actions single">
            <button type="button" class="pick-helper-btn ghost" onclick="cancelPickPathMode()">
                Cancel Pick Path
            </button>
        </div>
    </div>




    <!-- CAMPUS MAP ROTATION CONTROL -->
    <div class="campus-rotate-control" id="campus-rotate-control" aria-label="Map rotation controls">
        <button type="button" class="campus-rotate-btn" onclick="rotateMapLeft()" title="Rotate left">↺</button>
        <button type="button" class="campus-rotate-reset" onclick="resetMapRotation()" title="Reset map north">
            <span class="campus-compass-arrow">▲</span>
            <span id="campus-rotate-value">0°</span>
        </button>
        <button type="button" class="campus-rotate-btn" onclick="rotateMapRight()" title="Rotate right">↻</button>
    </div>

    <div id="map"></div>

    <div class="indoor-backdrop" id="indoorBackdrop"></div>

    <div class="indoor-panel" id="indoorPanel">
        <div class="indoor-header">
            <div>
                <div class="indoor-title" id="indoorTitle">Indoor Navigation</div>
                <div class="indoor-subtitle" id="indoorSubtitle">Choose room or office</div>
            </div>
            <button type="button" class="indoor-close" id="closeIndoorPanel">Close</button>
        </div>

        <div class="indoor-toolbar indoor-floor-toolbar">
            <!-- Hidden select kept for script compatibility -->
            <select id="indoorFloorSelect" class="indoor-floor-select-hidden">
                <option value="">Select Floor</option>
            </select>

            <!-- Hidden search kept for script compatibility -->
            <input type="text" id="indoorRoomSearch" class="indoor-room-search-hidden"
                placeholder="Search room or office...">

            <div class="indoor-floor-buttons" id="indoorFloorButtons"></div>
        </div>

        <div class="indoor-body">
            <div class="indoor-sidebar">
                <div class="indoor-sidebar-title">Rooms / Offices</div>
                <div class="room-list" id="roomList"></div>
            </div>

            <div class="indoor-main">
                <div class="indoor-map-wrap">
                    <div id="indoorMap"></div>
                    <div class="loading-overlay" id="indoorLoading" style="display:none;">Loading indoor map...</div>
                </div>
                <div class="indoor-footer" id="indoorFooter">
                    <span class="indoor-badge badge-blue">Select Building</span>
                    Choose a room or office to compute the route.
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    @include('user.script.script')
</body>

</html>
