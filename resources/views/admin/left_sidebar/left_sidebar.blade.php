<div class="leftside-menu">
    <a href="{{ route('admin.dashboard') }}" class="logo logo-light" aria-label="SLSU Wayfinding Admin">
        <span class="logo-lg">
            <span class="admin-sidebar-brand">
                <img src="{{ asset('background/slsu-logo.jpg') }}" alt="SLSU logo">
                <span class="admin-sidebar-brand-copy">
                    <strong>Smart Campus</strong>
                    <small>Admin Control</small>
                </span>
            </span>
        </span>

        <span class="logo-sm">
            <span class="admin-sidebar-brand">
                <img src="{{ asset('background/slsu-logo.jpg') }}" alt="SLSU logo">
            </span>
        </span>
    </a>

    <div class="button-sm-hover" data-bs-toggle="tooltip" data-bs-placement="right" title="Show full sidebar">
        <i class="ri-checkbox-blank-circle-line align-middle"></i>
    </div>

    <div class="button-close-fullsidebar">
        <i class="ri-close-fill align-middle"></i>
    </div>

    <div class="h-100" id="leftside-menu-container" data-simplebar>
        <div class="leftbar-user">
            <a href="{{ route('admin.dashboard') }}">
                <span class="admin-sidebar-user-icon">
                    <i class="ri-shield-user-line"></i>
                </span>

                <span class="admin-sidebar-user-copy">
                    <strong>{{ auth()->user()->username ?? 'Administrator' }}</strong>
                    <small>Authorized session</small>
                </span>
            </a>
        </div>

        <ul class="side-nav">
            <li class="side-nav-title">Command</li>

            <li class="side-nav-item">
                <a href="{{ route('admin.dashboard') }}" class="side-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.campus-event') }}" class="side-nav-link {{ request()->routeIs('admin.campus-event*') ? 'active' : '' }}">
                    <i class="ri-megaphone-line"></i>
                    <span>Campus Events</span>
                </a>
            </li>

            <li class="side-nav-title">Outdoor Network</li>

            <li class="side-nav-item">
                <a href="{{ route('admin.buildings') }}" class="side-nav-link {{ request()->routeIs('admin.buildings*') ? 'active' : '' }}">
                    <i class="ri-building-2-line"></i>
                    <span>Buildings</span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.path') }}" class="side-nav-link {{ request()->routeIs('admin.path*') ? 'active' : '' }}">
                    <i class="ri-route-line"></i>
                    <span>Paths</span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.hazard-point') }}" class="side-nav-link {{ request()->routeIs('admin.hazard-point*') ? 'active' : '' }}">
                    <i class="ri-error-warning-line"></i>
                    <span>Hazard Points</span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.entry-point') }}" class="side-nav-link {{ request()->routeIs('admin.entry-point*') ? 'active' : '' }}">
                    <i class="ri-map-pin-add-line"></i>
                    <span>Entry Points</span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.building-entrances') }}" class="side-nav-link {{ request()->routeIs('admin.building-entrances*') ? 'active' : '' }}">
                    <i class="ri-door-open-line"></i>
                    <span>Building Entrances</span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.landuse') }}" class="side-nav-link {{ request()->routeIs('admin.landuse*') ? 'active' : '' }}">
                    <i class="ri-earth-line"></i>
                    <span>Land Use</span>
                </a>
            </li>

            <li class="side-nav-title">Indoor Network</li>

            <li class="side-nav-item">
                <a href="{{ route('admin.indoor-map') }}" class="side-nav-link {{ request()->routeIs('admin.indoor-map*') ? 'active' : '' }}">
                    <i class="ri-map-2-line"></i>
                    <span>Indoor Maps</span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.indoor-path') }}" class="side-nav-link {{ request()->routeIs('admin.indoor-path*') ? 'active' : '' }}">
                    <i class="ri-route-line"></i>
                    <span>Indoor Paths</span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.indoor-room') }}" class="side-nav-link {{ request()->routeIs('admin.indoor-room*') ? 'active' : '' }}">
                    <i class="ri-door-line"></i>
                    <span>Indoor Rooms</span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.indoor-entrances') }}" class="side-nav-link {{ request()->routeIs('admin.indoor-entrances*') ? 'active' : '' }}">
                    <i class="ri-door-open-line"></i>
                    <span>Indoor Entrances</span>
                </a>
            </li>

            <li class="side-nav-title">Routing Links</li>

            <li class="side-nav-item">
                <a href="{{ route('admin.indoor-stairs-link') }}" class="side-nav-link {{ request()->routeIs('admin.indoor-stairs-link*') ? 'active' : '' }}">
                    <i class="ri-arrow-up-down-line"></i>
                    <span>Stairs Links</span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.building-entrance-link') }}" class="side-nav-link {{ request()->routeIs('admin.building-entrance-link*') ? 'active' : '' }}">
                    <i class="ri-link-m"></i>
                    <span>Entrance Links</span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('admin.destination-keyword') }}" class="side-nav-link {{ request()->routeIs('admin.destination-keyword*') ? 'active' : '' }}">
                    <i class="ri-key-2-line"></i>
                    <span>Destination Keywords</span>
                </a>
            </li>
        </ul>

        <div class="clearfix"></div>
    </div>
</div>
