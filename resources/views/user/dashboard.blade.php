@php
    $guestMode = $guestMode ?? false;
    $sharedDestination = $sharedDestination ?? null;
    $gpsSimulatorEnabled = app()->environment('local')
        && config('app.debug')
        && ! $guestMode
        && request()->boolean('gps_simulator');
    $gpsDiagnosticsEnabled = ! $guestMode
        && config('app.debug')
        && ($gpsSimulatorEnabled || request()->boolean('gps_diagnostics'));
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#18375d">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Campus Nav">
    <meta name="wayfinding-service-worker"
          content="/sw.js?v={{ filemtime(public_path('sw.js')) }}">
    <link rel="manifest"
          href="/manifest.webmanifest?v={{ filemtime(public_path('manifest.webmanifest')) }}">
    <link rel="apple-touch-icon" href="/icons/pwa-icon-180.png">
    <title>SLSU Campus - Smart Navigation</title>

    @vite('resources/css/wayfinding.css')

    @if($gpsSimulatorEnabled)
        <link rel="stylesheet"
              href="{{ asset('css/wayfinding/13-gps-simulator.css') }}?v={{ filemtime(public_path('css/wayfinding/13-gps-simulator.css')) }}">
    @endif
</head>

<body class="{{ $guestMode ? 'guest-mode' : '' }}">

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

        <div class="user-profile-menu"
             id="user-profile-menu"
             role="menu"
             aria-label="User account menu">
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
                        {{ $guestMode ? 'Guest Mode' : (auth()->user()->username ?? 'User') }}
                    </div>
                    <div class="user-profile-email">
                        {{ $guestMode ? 'Browse-only campus access' : auth()->user()->email }}
                    </div>
                </div>
            </div>

            <section class="pwa-profile-status"
                     id="pwa-profile-status"
                     data-state="preparing"
                     aria-label="Offline app status">
                <span class="pwa-status-dot" aria-hidden="true"></span>
                <div class="pwa-status-copy">
                    <strong id="pwa-status-label">Preparing offline access</strong>
                    <small id="pwa-status-detail">Campus data is saved securely as it loads.</small>
                </div>
                <button type="button"
                        class="pwa-install-button"
                        id="pwa-install-button"
                        hidden>
                    Install Campus App
                </button>
            </section>

            @if($guestMode)
                <div class="guest-profile-upgrade">
                    <strong>Unlock the complete navigator</strong>
                    <small>Get live GPS, text search, and voice guidance.</small>
                </div>
                <a href="{{ route('login') }}" class="user-logout-btn guest-profile-login">
                    <span aria-hidden="true">↪</span>
                    Log In
                </a>
            @else
            <form method="POST" action="{{ route('user.logout') }}" class="user-logout-form">
                @csrf
                <button type="submit" class="user-logout-btn">
                    <span>🚪</span>
                    Logout
                </button>
            </form>
            @endif
        </div>
    </div>

    @if($guestMode && !$sharedDestination)
        <aside class="guest-upgrade-card"
               id="guest-upgrade-card"
               aria-labelledby="guest-upgrade-title">
            <button type="button"
                    class="guest-upgrade-close"
                    id="guest-upgrade-close"
                    aria-label="Dismiss account invitation">×</button>
            <div class="guest-upgrade-kicker">
                <span aria-hidden="true"></span>
                Guest Preview
            </div>
            <h2 id="guest-upgrade-title">Unlock smarter campus navigation.</h2>
            <p>Create a free account or log in to use every navigation feature.</p>
            <div class="guest-upgrade-features" aria-label="Account features">
                <span>Live GPS</span>
                <span>Text Search</span>
                <span>Voice Search</span>
            </div>
            <div class="guest-upgrade-actions">
                <a href="{{ route('login') }}" class="guest-upgrade-login">Log In</a>
            </div>
        </aside>
    @endif

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

                @if($guestMode)
                    <button type="button"
                            class="floating-action-btn guest-feature-locked"
                            id="guest-text-search-command-btn"
                            onclick="requestGuestFeatureAccess('Text Search')">
                        <span class="action-icon">🔒</span>
                        Search Text
                    </button>

                    <button type="button"
                            class="floating-action-btn dark guest-feature-locked"
                            id="guest-voice-command-btn"
                            onclick="requestGuestFeatureAccess('Voice Search')">
                        <span class="action-icon">🔒</span>
                        Voice Search
                    </button>
                @else
                    <button type="button" class="floating-action-btn" id="text-search-command-btn" onclick="openInlineTextSearch()">
                        <span class="action-icon">⌨️</span>
                        Search Text
                    </button>

                    <button type="button" class="floating-action-btn dark" id="voice-command-btn" onclick="openInlineVoiceSearch()">
                        <span class="action-icon">🎙️</span>
                        Voice Search
                    </button>
                @endif

                <button type="button" class="floating-action-btn blue" onclick="openBrowseOptionsModal()">
                    <span class="action-icon">🧭</span>
                    Browse Options
                </button>
            </div>

            <!-- MAIN ORB BUTTON -->
            <button type="button"
                    class="floating-main-pin"
                    id="destination-menu-toggle"
                    onclick="toggleFloatingActionCard()"
                    aria-label="Choose a destination"
                    aria-expanded="false"
                    aria-controls="floating-action-card">
                <span class="pin-disc">
                    <span class="pin-icon">
                        <span class="pin-hole"></span>
                    </span>
                </span>
            </button>

            <!-- TRANSFORMED TEXT SEARCH BAR -->
            <div class="ai-transform-panel ai-search-panel" id="ai-search-panel" style="display:none;">
                <button type="button"
                        class="ai-transform-close"
                        onclick="closeAiTransformPanel()"
                        aria-label="Close text search">×</button>

                <div class="ai-transform-kicker">
                    <span class="ai-dot tiny"></span>
                     Text Search
                </div>

                <div class="ai-search-row">
                    <input type="text" id="destination-search-input" class="ai-search-input"
                        aria-label="Search for a campus destination"
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
                <button type="button"
                        class="ai-transform-close"
                        onclick="closeAiTransformPanel()"
                        aria-label="Close voice search">×</button>

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
            <button type="button"
                    class="floating-mode-btn pick"
                    onclick="selectPickPathMode()"
                    aria-pressed="false">
                📍 PICK PATH
            </button>

            @if($guestMode)
                <button type="button"
                        class="floating-mode-btn gps guest-feature-locked"
                        onclick="requestGuestFeatureAccess('Live GPS navigation')"
                        aria-label="Log in to use GPS navigation"
                        aria-pressed="false">
                    🔒 USE GPS
                </button>
            @else
                <button type="button"
                        class="floating-mode-btn gps"
                        onclick="selectGpsMode()"
                        aria-pressed="false">
                    🧭 USE GPS
                </button>
            @endif

            <button type="button"
                    class="floating-mode-btn default active"
                    onclick="selectDefaultMode()"
                    aria-pressed="true">
                🗺️ DEFAULT ROUTE
            </button>
        </div>
    </div>

    <button type="button"
            class="navigation-details-toggle navigation-action"
            id="navigation-details-toggle"
            aria-label="Show route details"
            aria-controls="navigation-sheet"
            aria-expanded="false"
            hidden>
        <svg class="navigation-details-toggle-icon"
             viewBox="0 0 24 24"
             aria-hidden="true">
            <path d="M6 4a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm12 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM7.5 7.5l7.2 7.2M14 8h4v4" />
        </svg>
        <span id="navigation-details-toggle-label">Route</span>
    </button>

    <button type="button"
            class="navigation-details-toggle navigation-action cr-navigation-toggle"
            id="cr-navigation-toggle"
            style="left:auto; right:7px;"
            aria-label="Find the nearest comfort room"
            aria-haspopup="dialog"
            aria-controls="cr-navigation-modal"
            onclick="openCrNavigator(this)">
        <span aria-hidden="true">🚻</span>
        <span>CR</span>
    </button>

    <!-- UNIFIED ROUTE DETAILS + LIVE NAVIGATION -->
    <section id="navigation-sheet"
             class="navigation-sheet"
             aria-label="Navigation status"
             aria-live="polite"
             aria-atomic="false"
             hidden>
        <div class="navigation-sheet-glow" aria-hidden="true"></div>

        <header class="navigation-sheet-header">
            <div class="navigation-status-lockup">
                <span class="navigation-status-dot" id="navigation-status-dot" aria-hidden="true"></span>
                <div>
                    <div class="navigation-kicker" id="navigation-kicker">Route Preview</div>
                    <h2 class="navigation-destination" id="navigation-destination">Choose a destination</h2>
                </div>
            </div>

            <button type="button"
                    class="navigation-collapse-btn"
                    id="navigation-collapse-btn"
                    aria-label="Hide route details"
                    aria-expanded="true">
                <span aria-hidden="true">&times;</span>
            </button>
        </header>

        <div class="navigation-sheet-body" id="navigation-sheet-body">
            <div class="navigation-guidance" id="navigation-guidance">
                <div class="navigation-guidance-arrow" id="navigation-guidance-arrow" aria-hidden="true">↑</div>
                <div class="navigation-guidance-copy">
                    <div class="navigation-guidance-title" id="navigation-guidance-title">Route preview ready</div>
                    <div class="navigation-guidance-meta" id="navigation-guidance-meta">
                        Review the highlighted path, then start navigation.
                    </div>
                </div>
            </div>

            <div class="navigation-metrics" aria-label="Route summary">
                <div class="navigation-metric">
                    <span class="navigation-metric-label">Distance</span>
                    <strong id="navigation-distance">--</strong>
                </div>
                <div class="navigation-metric">
                    <span class="navigation-metric-label">Walk time</span>
                    <strong id="navigation-eta">--</strong>
                </div>
                @if($gpsDiagnosticsEnabled)
                    <button type="button"
                            class="navigation-metric navigation-metric-action"
                            id="gps-diagnostics-toggle"
                            aria-expanded="false"
                            aria-controls="gps-diagnostics-panel">
                        <span class="navigation-metric-label">Location</span>
                        <strong id="navigation-gps-quality">Not active</strong>
                        <small>Open testing tools</small>
                    </button>
                @else
                    <div class="navigation-metric navigation-location-card">
                        <span class="navigation-metric-label">Location</span>
                        <strong id="navigation-gps-quality">Not active</strong>
                        <small>{{ $guestMode ? 'Use Default Route or Pick Path' : 'Tap Use GPS when ready' }}</small>
                    </div>
                @endif
                <div class="navigation-metric">
                    <span class="navigation-metric-label">Route safety</span>
                    <strong id="navigation-safety">Checking</strong>
                </div>
            </div>

            <p class="navigation-status-message"
               id="route-result-label"
               role="status">
                Choose a destination to create a route.
            </p>

            <div class="navigation-actions">
                <button type="button"
                        class="navigation-action"
                        id="navigation-recenter-btn">
                    Recenter
                </button>
                <button type="button"
                        class="navigation-action"
                        id="navigation-pause-btn"
                        hidden>
                    Pause
                </button>
                <button type="button"
                        class="navigation-action danger"
                        id="navigation-end-btn">
                    End
                </button>
            </div>
        </div>
    </section>

    <div class="cr-navigation-modal"
         id="cr-navigation-modal"
         hidden>
        <section class="cr-navigation-dialog"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="cr-navigation-title"
                 tabindex="-1">
            <header class="cr-navigation-header">
                <div>
                    <div class="cr-navigation-kicker">Quick Campus Assistance</div>
                    <h2 id="cr-navigation-title">Find the Nearest CR</h2>
                    <p>Choose where you are starting. We will suggest the closest reachable comfort rooms.</p>
                </div>
                <button type="button"
                        class="cr-navigation-close"
                        id="cr-navigation-close"
                        aria-label="Close CR navigation">
                    &times;
                </button>
            </header>

            <div class="cr-navigation-modes" id="cr-navigation-modes">
                @unless($guestMode)
                    <button type="button" class="cr-navigation-mode" data-cr-mode="gps">
                        <span class="cr-navigation-mode-icon" aria-hidden="true">⌖</span>
                        <span>
                            <strong>Use GPS</strong>
                            <small>Use your current location</small>
                        </span>
                    </button>
                @endunless
                <button type="button" class="cr-navigation-mode" data-cr-mode="path">
                    <span class="cr-navigation-mode-icon" aria-hidden="true">⌁</span>
                    <span>
                        <strong>Pick Path</strong>
                        <small>Tap your position on the map</small>
                    </span>
                </button>
                <button type="button" class="cr-navigation-mode" data-cr-mode="default">
                    <span class="cr-navigation-mode-icon" aria-hidden="true">◆</span>
                    <span>
                        <strong>Default</strong>
                        <small>Start from the campus entrance</small>
                    </span>
                </button>
            </div>

            <div class="cr-navigation-status"
                 id="cr-navigation-status"
                 role="status"
                 hidden>
                <span class="cr-navigation-spinner" aria-hidden="true"></span>
                <span id="cr-navigation-status-text">Checking campus routes…</span>
            </div>

            <section class="cr-navigation-results"
                     id="cr-navigation-results"
                     aria-labelledby="cr-navigation-results-title"
                     hidden>
                <div class="cr-navigation-results-head">
                    <div>
                        <span>Suggested Destinations</span>
                        <h3 id="cr-navigation-results-title">Nearest Comfort Rooms</h3>
                    </div>
                    <button type="button" id="cr-navigation-change-start">Change Start</button>
                </div>
                <div class="cr-navigation-context" id="cr-navigation-context"></div>
                <div class="cr-navigation-range-legend"
                     aria-label="Distance highlight guide">
                    <span><i class="is-nearest" aria-hidden="true"></i>Nearest</span>
                    <span><i class="is-close-range" aria-hidden="true"></i>Within 25 m</span>
                    <span><i class="is-nearby-range" aria-hidden="true"></i>Within 100 m</span>
                </div>
                <ol class="cr-navigation-list" id="cr-navigation-list"></ol>
            </section>
        </section>
    </div>

    @if($gpsDiagnosticsEnabled)
        <!-- DEVELOPMENT / FIELD-CALIBRATION TOOLS -->
        <aside id="gps-diagnostics-panel"
           class="gps-diagnostics-panel"
           role="dialog"
           aria-modal="false"
           aria-labelledby="gps-diagnostics-title"
           tabindex="-1"
           hidden>
        <div class="gps-diagnostics-glow" aria-hidden="true"></div>

        <header class="gps-diagnostics-header">
            <div>
                <div class="gps-diagnostics-kicker">Real-device field test</div>
                <h2 id="gps-diagnostics-title">GPS Diagnostics</h2>
            </div>
            <button type="button"
                    class="gps-diagnostics-close"
                    id="gps-diagnostics-close"
                    aria-label="Close GPS diagnostics">×</button>
        </header>

        <div class="gps-diagnostics-signal">
            <span class="gps-diagnostics-signal-dot" aria-hidden="true"></span>
            <div>
                <strong id="gps-diagnostics-signal-label">GPS not active</strong>
                <span id="gps-diagnostics-signal-message">Start recording to begin a field test.</span>
            </div>
        </div>

        <div class="gps-diagnostics-metrics" aria-label="Live GPS measurements">
            <div class="gps-diagnostics-metric">
                <span>Accuracy</span>
                <strong id="gps-diagnostics-accuracy">--</strong>
            </div>
            <div class="gps-diagnostics-metric">
                <span>Path offset</span>
                <strong id="gps-diagnostics-snap-distance">--</strong>
            </div>
            <div class="gps-diagnostics-metric">
                <span>Heading</span>
                <strong id="gps-diagnostics-heading">--</strong>
            </div>
            <div class="gps-diagnostics-metric">
                <span>Speed</span>
                <strong id="gps-diagnostics-speed">--</strong>
            </div>
            <div class="gps-diagnostics-metric">
                <span>Quality lock</span>
                <strong id="gps-diagnostics-lock">0 / 4</strong>
            </div>
            <div class="gps-diagnostics-metric">
                <span>Off-route check</span>
                <strong id="gps-diagnostics-off-route">0 / 3</strong>
            </div>
        </div>

        <div class="gps-diagnostics-warning" id="gps-diagnostics-warning" role="status">
            Keep the phone steady in an open area while the quality lock is starting.
        </div>

        <section class="gps-calibration-session" aria-labelledby="gps-session-title">
            <div class="gps-calibration-session-head">
                <div>
                    <span class="gps-calibration-eyebrow">Calibration session</span>
                    <h3 id="gps-session-title">Walk-test recording</h3>
                </div>
                <span class="gps-recording-badge" id="gps-recording-badge">Stopped</span>
            </div>

            <div class="gps-calibration-summary">
                <div>
                    <span>Samples</span>
                    <strong id="gps-session-samples">0</strong>
                </div>
                <div>
                    <span>Accepted</span>
                    <strong id="gps-session-accepted">--</strong>
                </div>
                <div>
                    <span>95% accuracy</span>
                    <strong id="gps-session-p95">--</strong>
                </div>
                <div>
                    <span>Duration</span>
                    <strong id="gps-session-duration">0:00</strong>
                </div>
            </div>

            <div class="gps-calibration-grade">
                <span id="gps-session-grade">Not ready</span>
                <p id="gps-session-recommendation">
                    Collect at least four GPS readings while walking a campus route.
                </p>
            </div>
        </section>

        <details class="gps-threshold-details">
            <summary>Safe routing thresholds</summary>
            <div class="gps-threshold-grid">
                <span>Strong lock <strong>≤20m</strong></span>
                <span>Usable preview <strong>≤45m</strong></span>
                <span>Reject reading <strong>&gt;60m</strong></span>
                <span>Path snap cap <strong>30m</strong></span>
                <span>Arrival radius <strong>10m</strong></span>
                <span>Off-route confirm <strong>3 readings</strong></span>
            </div>
        </details>

        <div class="gps-diagnostics-actions">
            <button type="button" class="gps-diagnostics-btn primary" id="gps-session-start">
                Start Recording
            </button>
            <button type="button" class="gps-diagnostics-btn" id="gps-session-stop" disabled>
                Stop
            </button>
            <button type="button" class="gps-diagnostics-btn" id="gps-session-export" disabled>
                Export CSV
            </button>
            <button type="button" class="gps-diagnostics-btn ghost" id="gps-session-clear" disabled>
                Clear
            </button>
        </div>

        <p class="gps-diagnostics-privacy">
            Coordinates stay on this device unless you choose Export CSV. Recording does not upload GPS data.
        </p>
        </aside>
    @endif

    <!-- CONNECTION / PARTIAL-DATA STATUS -->
    <aside id="wayfinding-connection-banner"
           class="wayfinding-connection-banner"
           role="status"
           aria-live="polite"
           hidden>
        <span class="connection-indicator" aria-hidden="true"></span>
        <div class="connection-copy">
            <strong id="wayfinding-connection-title">Campus data status</strong>
            <span id="wayfinding-connection-message"></span>
        </div>
        <button type="button"
                id="wayfinding-retry-btn"
                class="wayfinding-retry-btn">
            Retry
        </button>
        <button type="button"
                id="wayfinding-connection-close"
                class="wayfinding-connection-close"
                aria-label="Dismiss connection message">×</button>
    </aside>

    <aside id="pwa-update-banner"
           class="pwa-update-banner"
           role="status"
           aria-live="polite"
           hidden>
        <div class="pwa-update-copy">
            <strong>Navigation update ready</strong>
            <span>Reload when it is safe to use the latest campus app version.</span>
        </div>
        <div class="pwa-update-actions">
            <button type="button" id="pwa-update-button">Reload Update</button>
            <button type="button"
                    class="pwa-update-later"
                    id="pwa-update-dismiss">Later</button>
        </div>
    </aside>

    <!-- ACCESSIBLE NON-BLOCKING NOTIFICATIONS -->
    <div id="wayfinding-toast-region"
         class="wayfinding-toast-region"
         role="region"
         aria-label="System notifications"
         aria-live="polite"
         aria-relevant="additions"></div>



    <!-- BROWSE OPTIONS MODAL - ENHANCED USER FRIENDLY DESTINATION PICKER -->
    <div class="floating-modal-backdrop"
         id="browseOptionsModal"
         style="display:none;"
         aria-hidden="true">
        <div class="floating-modal-card browse-destination-card"
             role="dialog"
             aria-modal="true"
             aria-labelledby="browse-destination-title"
             tabindex="-1">
            <div class="browse-modal-glow"></div>

            <div class="browse-modal-header">
                <div>
                    <div class="floating-modal-kicker">Smart Destination Picker</div>
                    <div class="floating-modal-title" id="browse-destination-title">Browse Destination</div>
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
                <label class="route-label" for="default-entry-select">Default Starting Point</label>
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
                <label class="route-label" for="destination-type-select">Destination Type</label>
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
                    <label class="sr-only" for="destination-building-select">Destination building</label>
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
                    <label class="sr-only" for="destination-landuse-select">Destination landuse area</label>
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
                        <label class="route-label" for="room-building-filter-select">Building Filter</label>
                        <select id="room-building-filter-select" class="route-select browse-big-select">
                            <option value="">All Buildings</option>
                        </select>
                    </div>

                    <div class="route-field">
                        <label class="route-label" for="room-office-search-input">Search Room / Office</label>
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
                <label class="sr-only" for="destination-room-select">Destination room or office</label>
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
    <div id="route-building-popup"
         class="route-building-popup"
         role="dialog"
         aria-label="Indoor navigation available"
         style="display:none;">
        <button type="button"
                class="route-building-popup-close"
                onclick="closeRouteBuildingPopup()"
                aria-label="Close indoor navigation prompt">×</button>

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


    <div id="map"></div>

    <div class="indoor-backdrop" id="indoorBackdrop"></div>

    <div class="indoor-panel"
         id="indoorPanel"
         role="dialog"
         aria-modal="true"
         aria-labelledby="indoorTitle"
         tabindex="-1">
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

    @if($gpsSimulatorEnabled)
        <script>window.WAYFINDING_GPS_SIMULATOR_ENABLED = true;</script>
    @endif

    <script>
        window.WAYFINDING_GUEST_MODE = @json($guestMode);
        window.WAYFINDING_SHARED_DESTINATION = @json($sharedDestination);
    </script>

    @if($guestMode && !$sharedDestination)
        <script>
            (() => {
                const card = document.getElementById('guest-upgrade-card');
                const closeButton = document.getElementById('guest-upgrade-close');
                const dismissalKey = 'wayfinding_guest_upgrade_dismissed';

                try {
                    card.hidden = sessionStorage.getItem(dismissalKey) === '1';
                } catch {
                    card.hidden = false;
                }

                closeButton?.addEventListener('click', () => {
                    card.hidden = true;
                    try {
                        sessionStorage.setItem(dismissalKey, '1');
                    } catch {
                        // The card still closes when browser storage is unavailable.
                    }
                });
            })();
        </script>
    @endif

    @if($guestMode)
        <script>
            window.requestGuestFeatureAccess = async featureName => {
                const shouldLogIn = await window.FuturisticDialog.confirm(
                    `${featureName} is available with a full account.\n\nLog in to unlock GPS tracking, text search, voice search, and complete navigation tools. New users can create an account from the login page.`,
                    {
                        icon: '🔒',
                        kicker: 'Full Account Feature',
                        title: 'Log In to Continue',
                        confirmText: 'Log In',
                        cancelText: 'Not Now',
                        danger: false,
                    }
                );

                if (shouldLogIn) {
                    window.location.assign(@json(route('login')));
                }
            };
        </script>
    @endif

    @include('components.futuristic-dialogs')
    @vite('resources/js/wayfinding-entry.js')

    <script>
        window.openCrNavigator = async function(trigger) {
            if (trigger) {
                trigger.disabled = true;
                trigger.setAttribute('aria-busy', 'true');
            }

            try {
                await import(@json(
                    asset('js/wayfinding/15-cr-navigation.js')
                    . '?v='
                    . filemtime(public_path('js/wayfinding/15-cr-navigation.js'))
                ));
                await window.WayfindingCrNavigation.open(trigger);
            } catch (error) {
                console.error('Unable to open CR navigation:', error);
                window.showWayfindingToast?.(
                    'CR navigation could not open. Please reload and try again.',
                    { kind: 'error' }
                );
            } finally {
                if (trigger) {
                    trigger.disabled = false;
                    trigger.removeAttribute('aria-busy');
                }
            }
        };
    </script>

    @if($gpsSimulatorEnabled)
        <script type="module"
                src="{{ asset('js/wayfinding/13-gps-simulator.js') }}?v={{ filemtime(public_path('js/wayfinding/13-gps-simulator.js')) }}"></script>
    @endif
</body>

</html>
