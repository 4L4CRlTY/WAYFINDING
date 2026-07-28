@extends('admin.dashboard')

@section('admin')
    <div class="admin-home">
        <section class="admin-home-hero">
            <div class="admin-home-copy">
                <div class="admin-home-kicker">
                    <span></span>
                    Secure administration workspace
                </div>

                <h1>
                    Campus navigation
                    <em>command center.</em>
                </h1>

                <p class="admin-home-lead">
                    Welcome, {{ auth()->user()->username ?? 'Administrator' }}. Manage the outdoor and indoor
                    navigation network, keep destinations accurate, publish campus events, and maintain safe
                    routing data from one control surface.
                </p>

                <div class="admin-home-actions">
                    <a href="{{ route('admin.authorized.index') }}" class="admin-home-btn">
                        <i class="ri-team-line"></i>
                        Manage Authorized Access
                    </a>

                    <a href="{{ route('admin.buildings') }}" class="admin-home-btn">
                        <i class="ri-building-2-line"></i>
                        Manage Campus Map
                    </a>

                    <a href="{{ route('admin.campus-event') }}" class="admin-home-btn secondary">
                        <i class="ri-megaphone-line"></i>
                        Campus Events
                    </a>

                    <a href="{{ route('admin.destination-links.index') }}" class="admin-home-btn secondary">
                        <i class="ri-links-line"></i>
                        Manage Event Links
                    </a>
                </div>
            </div>

            <div class="admin-home-system" aria-hidden="true">
                <div class="admin-system-orb">
                    <div class="admin-system-orb-content">
                        <i class="ri-radar-line"></i>
                        <strong>Wayfinding</strong>
                        <small>System online</small>
                    </div>
                </div>
            </div>
        </section>

        <div class="admin-home-section-head">
            <div>
                <h2>Navigation modules</h2>
                <p>Open a module to manage its data and routing connections.</p>
            </div>
        </div>

        <section class="admin-module-grid" aria-label="Administration modules">
            <a href="{{ route('admin.authorized.index') }}" class="admin-module-card">
                <span class="admin-module-icon"><i class="ri-team-line"></i></span>
                <span class="admin-module-copy">
                    <h3>Authorized Access Control</h3>
                    <p>Register authorized account positions and assign exactly which modules each account can manage.</p>
                </span>
                <i class="ri-arrow-right-up-line admin-module-arrow"></i>
            </a>

            <a href="{{ route('admin.buildings') }}" class="admin-module-card">
                <span class="admin-module-icon"><i class="ri-building-2-line"></i></span>
                <span class="admin-module-copy">
                    <h3>Buildings & Entrances</h3>
                    <p>Maintain campus structures, display colors, and accessible entry locations.</p>
                </span>
                <i class="ri-arrow-right-up-line admin-module-arrow"></i>
            </a>

            <a href="{{ route('admin.path') }}" class="admin-module-card">
                <span class="admin-module-icon"><i class="ri-route-line"></i></span>
                <span class="admin-module-copy">
                    <h3>Outdoor Routing</h3>
                    <p>Manage roads, walkways, stairs, entry points, and hazard-aware route data.</p>
                </span>
                <i class="ri-arrow-right-up-line admin-module-arrow"></i>
            </a>

            <a href="{{ route('admin.indoor-map') }}" class="admin-module-card">
                <span class="admin-module-icon"><i class="ri-map-2-line"></i></span>
                <span class="admin-module-copy">
                    <h3>Indoor Navigation</h3>
                    <p>Configure floor maps, rooms, indoor paths, entrances, and stairs connections.</p>
                </span>
                <i class="ri-arrow-right-up-line admin-module-arrow"></i>
            </a>

            <a href="{{ route('admin.landuse') }}" class="admin-module-card">
                <span class="admin-module-icon"><i class="ri-earth-line"></i></span>
                <span class="admin-module-copy">
                    <h3>Land Use</h3>
                    <p>Control open areas, courts, fields, overlays, and other campus destinations.</p>
                </span>
                <i class="ri-arrow-right-up-line admin-module-arrow"></i>
            </a>

            <a href="{{ route('admin.campus-event') }}" class="admin-module-card">
                <span class="admin-module-icon"><i class="ri-megaphone-line"></i></span>
                <span class="admin-module-copy">
                    <h3>Campus Events</h3>
                    <p>Publish active and upcoming events that users can view and navigate toward.</p>
                </span>
                <i class="ri-arrow-right-up-line admin-module-arrow"></i>
            </a>

            <a href="{{ route('admin.destination-links.index') }}" class="admin-module-card">
                <span class="admin-module-icon"><i class="ri-links-line"></i></span>
                <span class="admin-module-copy">
                    <h3>Campus Event Links</h3>
                    <p>Copy and manage the guest-ready route links generated automatically for campus events.</p>
                </span>
                <i class="ri-arrow-right-up-line admin-module-arrow"></i>
            </a>

            <a href="{{ route('admin.destination-keyword') }}" class="admin-module-card">
                <span class="admin-module-icon"><i class="ri-key-2-line"></i></span>
                <span class="admin-module-copy">
                    <h3>Search Intelligence</h3>
                    <p>Maintain destination keywords so text and voice searches find the right place.</p>
                </span>
                <i class="ri-arrow-right-up-line admin-module-arrow"></i>
            </a>
        </section>
    </div>
@endsection
