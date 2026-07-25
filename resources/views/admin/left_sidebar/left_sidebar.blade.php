@php
    $currentUser = auth()->user();
    $isAdministrator = $currentUser?->role === 'admin';
    $dashboardRoute = $isAdministrator ? 'admin.dashboard' : 'authorized.dashboard';
    $featureGroups = collect(config('authorized_features', []))->groupBy('group', preserveKeys: true);
    $positionLabel = $currentUser?->displayPosition() ?? 'Position Not Assigned';
@endphp

<div class="leftside-menu">
    <a href="{{ route($dashboardRoute) }}" class="logo logo-light" aria-label="SLSU Wayfinding Control">
        <span class="logo-lg">
            <span class="admin-sidebar-brand">
                <img src="{{ asset('background/slsu-logo.jpg') }}" alt="SLSU logo">
                <span class="admin-sidebar-brand-copy">
                    <strong>Smart Campus</strong>
                    <small>{{ $isAdministrator ? 'Admin Control' : 'Authorized Workspace' }}</small>
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
            <a href="{{ route($dashboardRoute) }}">
                <span class="admin-sidebar-user-icon">
                    <i class="{{ $isAdministrator ? 'ri-shield-user-line' : 'ri-user-star-line' }}"></i>
                </span>

                <span class="admin-sidebar-user-copy">
                    <strong>{{ $isAdministrator ? ($currentUser?->username ?? 'Administrator') : $positionLabel }}</strong>
                    <small>{{ $isAdministrator ? 'System administrator' : ($currentUser?->username ?? 'Authorized user') }}</small>
                </span>
            </a>
        </div>

        <ul class="side-nav">
            <li class="side-nav-title">Command</li>

            <li class="side-nav-item">
                <a href="{{ route($dashboardRoute) }}" class="side-nav-link {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            @if ($isAdministrator)
                <li class="side-nav-item">
                    <a href="{{ route('admin.authorized.index') }}" class="side-nav-link {{ request()->routeIs('admin.authorized*') ? 'active' : '' }}">
                        <i class="ri-team-line"></i>
                        <span>Authorized Access</span>
                    </a>
                </li>
            @endif

            @foreach ($featureGroups as $group => $groupFeatures)
                @php
                    $visibleFeatures = $groupFeatures->filter(
                        fn (array $definition, string $feature) => $currentUser?->canAccessFeature($feature)
                    );
                @endphp

                @if ($visibleFeatures->isNotEmpty())
                    @if ($group !== 'Command')
                        <li class="side-nav-title">{{ $group }}</li>
                    @endif

                    @foreach ($visibleFeatures as $feature => $definition)
                        <li class="side-nav-item">
                            <a
                                href="{{ route($definition['route']) }}"
                                class="side-nav-link {{ request()->routeIs($definition['route_pattern']) ? 'active' : '' }}"
                            >
                                <i class="{{ $definition['icon'] }}"></i>
                                <span>{{ $definition['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ul>

        <div class="clearfix"></div>
    </div>
</div>
