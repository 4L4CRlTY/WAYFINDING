<div class="topbar container-fluid">
    <div class="d-flex align-items-center gap-lg-2 gap-1">
        <button class="button-toggle-menu" type="button" aria-label="Toggle admin navigation">
            <i class="ri-menu-2-fill"></i>
        </button>

        <div class="admin-command-brand" aria-label="Campus command center status">
            <span class="admin-command-signal"></span>
            <span class="admin-command-copy">
                <strong>Campus Command Center</strong>
                <small>Wayfinding control system online</small>
            </span>
        </div>
    </div>

    <ul class="topbar-menu d-flex align-items-center">
        <li>
            <a class="admin-topbar-action" href="{{ url('/') }}" title="Open public homepage">
                <i class="ri-global-line fs-18"></i>
                <span>View Site</span>
            </a>
        </li>

        <li class="d-none d-sm-inline-block">
            <a class="admin-topbar-action" href="#" data-toggle="fullscreen" title="Toggle fullscreen">
                <i class="ri-fullscreen-line fs-18"></i>
            </a>
        </li>

        <li class="dropdown">
            <a
                class="nav-link dropdown-toggle arrow-none nav-user"
                data-bs-toggle="dropdown"
                href="#"
                role="button"
                aria-haspopup="false"
                aria-expanded="false"
            >
                <span class="account-user-avatar">
                    <img src="{{ asset('background/slsu-logo.jpg') }}" alt="SLSU admin">
                </span>

                <span class="d-lg-flex flex-column gap-1 d-none">
                    <h5 class="my-0">{{ auth()->user()->username ?? 'Administrator' }}</h5>
                    <h6 class="my-0 fw-normal">System Admin</h6>
                </span>

                <i class="ri-arrow-down-s-line d-none d-lg-inline-block ms-1"></i>
            </a>

            <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated profile-dropdown">
                <div class="dropdown-header noti-title">
                    <h6 class="text-overflow m-0">Administrator Access</h6>
                </div>

                <a href="{{ route('admin.dashboard') }}" class="dropdown-item">
                    <i class="ri-dashboard-line fs-18 align-middle me-1"></i>
                    <span>Command Center</span>
                </a>

                <a href="{{ url('/') }}" class="dropdown-item">
                    <i class="ri-global-line fs-18 align-middle me-1"></i>
                    <span>Public Homepage</span>
                </a>

                <div class="dropdown-divider"></div>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item border-0 w-100 text-start">
                        <i class="ri-logout-box-line fs-18 align-middle me-1"></i>
                        <span>Logout Securely</span>
                    </button>
                </form>
            </div>
        </li>
    </ul>
</div>
