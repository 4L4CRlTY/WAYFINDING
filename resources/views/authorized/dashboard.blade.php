@extends('admin.dashboard')

@section('admin')
    <div class="admin-home">
        <section class="admin-home-hero">
            <div class="admin-home-copy">
                <div class="admin-home-kicker">
                    <span></span>
                    Authorized campus workspace
                </div>

                <h1>
                    {{ auth()->user()->displayPosition() }}
                    <em>Dashboard.</em>
                </h1>

                <p class="admin-home-lead">
                    Welcome, {{ auth()->user()->displayPosition() }}.
                    Signed in as {{ auth()->user()->username }}. Your workspace contains only the navigation
                    modules assigned by the system administrator.
                </p>

                @if (count($features))
                    @php($firstFeature = reset($features))
                    <div class="admin-home-actions">
                        <a href="{{ route($firstFeature['route']) }}" class="admin-home-btn">
                            <i class="{{ $firstFeature['icon'] }}"></i>
                            Open {{ $firstFeature['label'] }}
                        </a>
                    </div>
                @endif
            </div>

            <div class="admin-home-system" aria-hidden="true">
                <div class="admin-system-orb">
                    <div class="admin-system-orb-content">
                        <i class="ri-user-star-line"></i>
                        <strong>{{ count($features) }} modules</strong>
                        <small>Access enabled</small>
                    </div>
                </div>
            </div>
        </section>

        <div class="admin-home-section-head">
            <div>
                <h2>Your assigned modules</h2>
                <p>These permissions are managed by the system administrator.</p>
            </div>
        </div>

        @if (count($features))
            <section class="admin-module-grid" aria-label="Assigned campus modules">
                @foreach ($features as $feature)
                    <a href="{{ route($feature['route']) }}" class="admin-module-card">
                        <span class="admin-module-icon"><i class="{{ $feature['icon'] }}"></i></span>
                        <span class="admin-module-copy">
                            <h3>{{ $feature['label'] }}</h3>
                            <p>{{ $feature['description'] }}</p>
                        </span>
                        <i class="ri-arrow-right-up-line admin-module-arrow"></i>
                    </a>
                @endforeach
            </section>
        @else
            <div class="authorized-empty-state">
                <i class="ri-lock-2-line"></i>
                <h3>No modules assigned yet</h3>
                <p>Contact the system administrator to request feature access.</p>
            </div>
        @endif
    </div>
@endsection
