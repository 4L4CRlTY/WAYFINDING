@extends('admin.dashboard')

@section('admin')
    <div class="authorized-access-page">
        <section class="authorized-access-hero">
            <div>
                <span class="authorized-access-kicker"><i class="ri-shield-keyhole-line"></i> Role-based access control</span>
                <h1>Authorized access management</h1>
                <p>
                    Register authorized campus accounts, record each official position, and control exactly which
                    wayfinding modules they can view and manage.
                </p>
            </div>

            <div class="authorized-access-stat">
                <strong>{{ $authorizedUsers->total() }}</strong>
                <span>Authorized accounts</span>
            </div>
        </section>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ri-checkbox-circle-line me-1"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Please review the authorized account details.</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="authorized-access-layout">
            <section class="authorized-panel authorized-create-panel">
                <div class="authorized-panel-head">
                    <span class="authorized-panel-icon"><i class="ri-user-add-line"></i></span>
                    <div>
                        <h2>Register authorized account</h2>
                        <p>Create a secure login and assign its initial access.</p>
                    </div>
                </div>

                <form action="{{ route('admin.authorized.store') }}" method="POST" class="authorized-access-form">
                    @csrf

                    <div class="authorized-field-grid">
                        <label class="authorized-field">
                            <span>Account holder name</span>
                            <input
                                type="text"
                                name="username"
                                value="{{ old('username') }}"
                                placeholder="e.g. Juan Dela Cruz"
                                maxlength="100"
                                required
                            >
                        </label>

                        <label class="authorized-field">
                            <span>Official position</span>
                            <input
                                type="text"
                                name="position"
                                value="{{ old('position') }}"
                                placeholder="e.g. SSC President"
                                maxlength="120"
                                required
                            >
                        </label>

                        <label class="authorized-field authorized-field-wide">
                            <span>Email address</span>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="authorized@slsu.edu.ph"
                                required
                            >
                        </label>

                        <label class="authorized-field">
                            <span>Temporary password</span>
                            <input type="password" name="password" autocomplete="new-password" required>
                        </label>

                        <label class="authorized-field">
                            <span>Confirm password</span>
                            <input type="password" name="password_confirmation" autocomplete="new-password" required>
                        </label>

                        <label class="authorized-field authorized-field-wide">
                            <span>Account status</span>
                            <select name="status" required>
                                <option value="1" @selected(old('status', '1') === '1')>Active — can sign in</option>
                                <option value="0" @selected(old('status') === '0')>Inactive — sign-in disabled</option>
                            </select>
                        </label>
                    </div>

                    <div class="authorized-permission-head">
                        <div>
                            <h3>Allowed features</h3>
                            <p>Select at least one module for this authorized account.</p>
                        </div>
                        <div class="authorized-permission-tools">
                            <button type="button" data-permission-action="all">Select all</button>
                            <button type="button" data-permission-action="none">Clear</button>
                        </div>
                    </div>

                    <div class="authorized-permission-grid">
                        @foreach ($features as $key => $feature)
                            <label class="authorized-permission-option">
                                <input
                                    type="checkbox"
                                    name="authorized_permissions[]"
                                    value="{{ $key }}"
                                    @checked(in_array($key, old('authorized_permissions', []), true))
                                >
                                <span class="authorized-permission-icon"><i class="{{ $feature['icon'] }}"></i></span>
                                <span class="authorized-permission-copy">
                                    <strong>{{ $feature['label'] }}</strong>
                                    <small>{{ $feature['group'] }}</small>
                                </span>
                                <span class="authorized-permission-check"><i class="ri-check-line"></i></span>
                            </label>
                        @endforeach
                    </div>

                    <button type="submit" class="authorized-submit-btn">
                        <i class="ri-shield-user-line"></i>
                        Create Authorized Account
                    </button>
                </form>
            </section>

            <section class="authorized-panel authorized-directory-panel">
                <div class="authorized-panel-head">
                    <span class="authorized-panel-icon"><i class="ri-team-line"></i></span>
                    <div>
                        <h2>Authorized accounts</h2>
                        <p>Review positions, status, and assigned feature access.</p>
                    </div>
                </div>

                <div class="authorized-directory-list">
                    @forelse ($authorizedUsers as $authorizedUser)
                        <article class="authorized-member-card">
                            <div class="authorized-member-summary">
                                <span class="authorized-member-avatar">
                                    {{ strtoupper(substr($authorizedUser->username ?: 'A', 0, 1)) }}
                                </span>

                                <div class="authorized-member-identity">
                                    <div class="authorized-member-name-row">
                                        <h3>{{ $authorizedUser->username }}</h3>
                                        <span class="authorized-status {{ (string) $authorizedUser->status === '1' ? 'is-active' : 'is-inactive' }}">
                                            {{ (string) $authorizedUser->status === '1' ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <p>{{ $authorizedUser->displayPosition() }}</p>
                                    <small>{{ $authorizedUser->email }}</small>
                                </div>
                            </div>

                            <div class="authorized-access-tags">
                                @foreach ($authorizedUser->authorized_permissions ?? [] as $permission)
                                    @if (isset($features[$permission]))
                                        <span><i class="{{ $features[$permission]['icon'] }}"></i> {{ $features[$permission]['label'] }}</span>
                                    @endif
                                @endforeach

                                @if (empty($authorizedUser->authorized_permissions))
                                    <span class="is-empty"><i class="ri-lock-line"></i> No feature access</span>
                                @endif
                            </div>

                            <details class="authorized-edit-drawer">
                                <summary>
                                    <span><i class="ri-settings-3-line"></i> Edit account and access</span>
                                    <i class="ri-arrow-down-s-line"></i>
                                </summary>

                                <form action="{{ route('admin.authorized.update', $authorizedUser) }}" method="POST" class="authorized-access-form">
                                    @csrf
                                    @method('PATCH')

                                    <div class="authorized-field-grid">
                                        <label class="authorized-field">
                                            <span>Account holder name</span>
                                            <input type="text" name="username" value="{{ $authorizedUser->username }}" maxlength="100" required>
                                        </label>

                                        <label class="authorized-field">
                                            <span>Official position</span>
                                            <input type="text" name="position" value="{{ $authorizedUser->position }}" maxlength="120" required>
                                        </label>

                                        <label class="authorized-field authorized-field-wide">
                                            <span>Email address</span>
                                            <input type="email" name="email" value="{{ $authorizedUser->email }}" required>
                                        </label>

                                        <label class="authorized-field">
                                            <span>New password <small>(optional)</small></span>
                                            <input type="password" name="password" autocomplete="new-password">
                                        </label>

                                        <label class="authorized-field">
                                            <span>Confirm new password</span>
                                            <input type="password" name="password_confirmation" autocomplete="new-password">
                                        </label>

                                        <label class="authorized-field authorized-field-wide">
                                            <span>Account status</span>
                                            <select name="status" required>
                                                <option value="1" @selected((string) $authorizedUser->status === '1')>Active — can sign in</option>
                                                <option value="0" @selected((string) $authorizedUser->status === '0')>Inactive — sign-in disabled</option>
                                            </select>
                                        </label>
                                    </div>

                                    <div class="authorized-permission-head compact">
                                        <div>
                                            <h3>Allowed features</h3>
                                            <p>Changes apply on the account holder's next request.</p>
                                        </div>
                                        <div class="authorized-permission-tools">
                                            <button type="button" data-permission-action="all">Select all</button>
                                            <button type="button" data-permission-action="none">Clear</button>
                                        </div>
                                    </div>

                                    <div class="authorized-permission-grid compact">
                                        @foreach ($features as $key => $feature)
                                            <label class="authorized-permission-option">
                                                <input
                                                    type="checkbox"
                                                    name="authorized_permissions[]"
                                                    value="{{ $key }}"
                                                    @checked(in_array($key, $authorizedUser->authorized_permissions ?? [], true))
                                                >
                                                <span class="authorized-permission-icon"><i class="{{ $feature['icon'] }}"></i></span>
                                                <span class="authorized-permission-copy">
                                                    <strong>{{ $feature['label'] }}</strong>
                                                    <small>{{ $feature['group'] }}</small>
                                                </span>
                                                <span class="authorized-permission-check"><i class="ri-check-line"></i></span>
                                            </label>
                                        @endforeach
                                    </div>

                                    <button type="submit" class="authorized-submit-btn">
                                        <i class="ri-save-3-line"></i>
                                        Save Account Changes
                                    </button>
                                </form>
                            </details>
                        </article>
                    @empty
                        <div class="authorized-empty-state">
                            <i class="ri-user-add-line"></i>
                            <h3>No authorized accounts yet</h3>
                            <p>Use the registration form to create the first authorized workspace.</p>
                        </div>
                    @endforelse
                </div>

                @if ($authorizedUsers->hasPages())
                    <div class="authorized-pagination">
                        {{ $authorizedUsers->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            const actionButton = event.target.closest('[data-permission-action]');

            if (!actionButton) {
                return;
            }

            const form = actionButton.closest('form');
            const shouldCheck = actionButton.dataset.permissionAction === 'all';

            form.querySelectorAll('input[name="authorized_permissions[]"]').forEach(function (checkbox) {
                checkbox.checked = shouldCheck;
            });
        });
    </script>
@endsection
