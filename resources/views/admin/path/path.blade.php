@extends('admin.dashboard')

@section('admin')

<style>
    .path-wrapper {
        padding: 24px;
    }

    .path-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .path-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .path-card-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .path-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .path-form-body {
        padding: 24px;
    }

    .path-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .path-form-control {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }

    .path-form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .path-upload-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .path-upload-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .path-reset-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: #fee2e2;
        color: #b91c1c;
    }

    .path-reset-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .path-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .path-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .path-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .path-table {
        margin-bottom: 0;
    }

    .path-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .path-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .path-id-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        height: 32px;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 800;
        font-size: 12px;
    }

    .path-name-text {
        font-weight: 800;
        color: #0f172a;
    }

    .editable-name,
    .editable-settings {
        border-radius: 12px;
        transition: 0.18s ease;
    }

    .editable-name:hover,
    .editable-settings:hover {
        background: #f8fafc;
        cursor: pointer;
    }

    .editable-name input,
    .editable-settings input,
    .editable-settings select {
        border-radius: 12px;
        border: 1px solid #dbe3ef;
        font-size: 13px;
    }

    .path-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .path-type-pill {
        background: #dbeafe;
        color: #1d4ed8;
        text-transform: uppercase;
    }

    .risk-low,
    .difficulty-easy,
    .status-open {
        background: #dcfce7;
        color: #15803d;
    }

    .risk-medium,
    .difficulty-moderate {
        background: #fef3c7;
        color: #92400e;
    }

    .risk-high,
    .difficulty-hard,
    .status-blocked {
        background: #fee2e2;
        color: #b91c1c;
    }

    .hazard-note-text {
        color: #475569;
        max-width: 360px;
    }

    .empty-path-box {
        padding: 36px;
        text-align: center;
        color: #64748b;
    }

    .custom-pagination {
        display: flex;
        gap: 8px;
        list-style: none;
        padding: 0;
        margin: 0;
        flex-wrap: wrap;
    }

    .custom-pagination li a,
    .custom-pagination li span {
        min-width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 14px;
        border-radius: 12px;
        background: #f1f5f9;
        text-decoration: none;
        color: #334155;
        font-weight: 800;
        transition: 0.2s;
    }

    .custom-pagination li a:hover {
        background: #2563eb;
        color: white;
        transform: translateY(-2px);
    }

    .custom-pagination .active span {
        background: #2563eb;
        color: white;
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.22);
    }

    .custom-pagination .disabled span {
        background: #e2e8f0;
        color: #94a3b8;
    }

    @media (max-width: 768px) {
        .path-wrapper {
            padding: 14px;
        }

        .path-card-header,
        .path-form-body,
        .path-table-header {
            padding: 18px;
        }

        .path-table {
            min-width: 1150px;
        }

        .path-form-actions {
            flex-direction: column;
            align-items: stretch !important;
        }

        .path-upload-btn,
        .path-reset-btn {
            width: 100%;
        }
    }
</style>

<div class="path-wrapper">

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

    {{-- Upload Card --}}
    <div class="path-card">
        <div class="path-card-header">
            <h4>Paths Manager</h4>
            <p>Upload, reset, and configure campus paths used for outdoor routing.</p>
        </div>

        <div class="path-form-body">
            <form action="{{ route('admin.path.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3 align-items-end">
                    <div class="col-lg-8">
                        <label class="path-form-label">Upload Paths GeoJSON</label>
                        <input
                            type="file"
                            name="geojson"
                            class="form-control path-form-control"
                            accept=".json,.geojson"
                            required
                        >
                    </div>

                    <div class="col-lg-4">
                        <button type="submit" class="path-upload-btn w-100">
                            <i class="ri-upload-cloud-2-line me-1"></i>
                            Upload Paths
                        </button>
                    </div>
                </div>
            </form>

            <form
                action="{{ route('admin.path.reset') }}"
                method="POST"
                class="mt-3"
                onsubmit="return confirm('Restore previous path upload?')"
            >
                @csrf
                @method('DELETE')

                <div class="d-flex justify-content-end path-form-actions">
                    <button type="submit" class="path-reset-btn">
                        <i class="ri-refresh-line me-1"></i>
                        Reset Paths
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="path-table-card">
        <div class="path-table-header">
            <div>
                <h5>Uploaded Paths</h5>
                <span class="muted-small">
                    Double click / tap a path name or type cell to edit settings.
                </span>
            </div>

            <span class="muted-small">
                Total Paths: {{ $paths->total() ?? $paths->count() }}
            </span>
        </div>

        <div class="table-responsive">
            @if($paths->count())
                <table class="table path-table align-middle">
                    <thead>
                        <tr>
                            <th width="70">ID</th>
                            <th>Name</th>
                            <th width="150">Type</th>
                            <th width="120">Risk</th>
                            <th width="140">Difficulty</th>
                            <th width="120">Blocked</th>
                            <th>Hazard Note</th>
                            <th width="190">Created</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($paths as $path)
                            @php
                                $riskLabel = match($path->risk_level) {
                                    1 => 'Low',
                                    2 => 'Medium',
                                    3 => 'High',
                                    default => 'Low',
                                };

                                $riskClass = match($path->risk_level) {
                                    1 => 'risk-low',
                                    2 => 'risk-medium',
                                    3 => 'risk-high',
                                    default => 'risk-low',
                                };

                                $difficultyLabel = match($path->difficulty_level) {
                                    1 => 'Easy',
                                    2 => 'Moderate',
                                    3 => 'Hard',
                                    default => 'Easy',
                                };

                                $difficultyClass = match($path->difficulty_level) {
                                    1 => 'difficulty-easy',
                                    2 => 'difficulty-moderate',
                                    3 => 'difficulty-hard',
                                    default => 'difficulty-easy',
                                };
                            @endphp

                            <tr>
                                <td>
                                    <span class="path-id-badge">
                                        #{{ $path->id }}
                                    </span>
                                </td>

                                <td
                                    class="editable-name"
                                    data-url="{{ route('admin.path.updateName', $path->id) }}"
                                    data-name="{{ $path->name }}"
                                >
                                    <span class="path-name-text">
                                        {{ $path->name }}
                                    </span>
                                </td>

                                <td
                                    class="editable-settings"
                                    data-url="{{ route('admin.path.updateSettings', $path->id) }}"
                                    data-type="{{ $path->type }}"
                                    data-risk="{{ $path->risk_level }}"
                                    data-difficulty="{{ $path->difficulty_level }}"
                                    data-blocked="{{ $path->is_blocked ? 1 : 0 }}"
                                    data-note="{{ $path->hazard_note }}"
                                >
                                    <span class="path-pill path-type-pill">
                                        {{ $path->type }}
                                    </span>
                                </td>

                                <td>
                                    <span class="path-pill {{ $riskClass }}">
                                        {{ $riskLabel }}
                                    </span>
                                </td>

                                <td>
                                    <span class="path-pill {{ $difficultyClass }}">
                                        {{ $difficultyLabel }}
                                    </span>
                                </td>

                                <td>
                                    @if($path->is_blocked)
                                        <span class="path-pill status-blocked">Blocked</span>
                                    @else
                                        <span class="path-pill status-open">Open</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="hazard-note-text">
                                        {{ $path->hazard_note ?: '—' }}
                                    </div>
                                </td>

                                <td>
                                    <div class="path-name-text">
                                        {{ optional($path->created_at)->format('M d, Y') }}
                                    </div>
                                    <div class="muted-small">
                                        {{ optional($path->created_at)->format('h:i A') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-path-box">
                    No paths uploaded yet.
                </div>
            @endif
        </div>

        @if($paths->count())
            <div class="px-4 py-3 border-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="muted-small">
                        Showing {{ $paths->firstItem() }} to {{ $paths->lastItem() }}
                        of {{ $paths->total() }} paths
                    </div>

                    @if ($paths->hasPages())
                        <ul class="custom-pagination">
                            @if ($paths->onFirstPage())
                                <li class="disabled"><span>«</span></li>
                            @else
                                <li><a href="{{ $paths->previousPageUrl() }}">«</a></li>
                            @endif

                            @foreach ($paths->getUrlRange(1, $paths->lastPage()) as $page => $url)
                                @if ($page == $paths->currentPage())
                                    <li class="active"><span>{{ $page }}</span></li>
                                @else
                                    <li><a href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach

                            @if ($paths->hasMorePages())
                                <li><a href="{{ $paths->nextPageUrl() }}">»</a></li>
                            @else
                                <li class="disabled"><span>»</span></li>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<script>
document.querySelectorAll('.editable-name').forEach(cell => {
    const isMobile = window.innerWidth <= 768;
    const trigger = isMobile ? 'click' : 'dblclick';

    cell.addEventListener(trigger, () => {
        if (cell.querySelector('input')) return;

        const original = cell.dataset.name;
        const url = cell.dataset.url;

        const input = document.createElement('input');
        input.value = original;
        input.className = 'form-control form-control-sm';

        cell.innerHTML = '';
        cell.appendChild(input);

        input.focus();
        input.select();

        const save = async () => {
            const value = input.value.trim();

            if (!value) {
                alert('Required');
                cell.innerHTML = `<span class="path-name-text">${original}</span>`;
                return;
            }

            if (value === original) {
                cell.innerHTML = `<span class="path-name-text">${original}</span>`;
                return;
            }

            try {
                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ name: value })
                });

                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.message || 'Update failed.');
                }

                cell.innerHTML = `<span class="path-name-text">${data.name}</span>`;
                cell.dataset.name = data.name;
            } catch (error) {
                alert(error.message || 'Update failed.');
                cell.innerHTML = `<span class="path-name-text">${original}</span>`;
            }
        };

        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') save();

            if (e.key === 'Escape') {
                cell.innerHTML = `<span class="path-name-text">${original}</span>`;
            }
        });

        input.addEventListener('blur', save);
    });
});

document.querySelectorAll('.editable-settings').forEach(cell => {
    const isMobile = window.innerWidth <= 768;
    const trigger = isMobile ? 'click' : 'dblclick';

    cell.addEventListener(trigger, () => {
        if (cell.querySelector('.setting-type')) return;

        const url = cell.dataset.url;
        const originalType = cell.dataset.type;
        const originalRisk = cell.dataset.risk;
        const originalDifficulty = cell.dataset.difficulty;
        const originalBlocked = cell.dataset.blocked;
        const originalNote = cell.dataset.note || '';

        const form = document.createElement('div');
        form.innerHTML = `
            <select class="form-select form-select-sm mb-1 setting-type">
                <option value="walkway">Walkway</option>
                <option value="stairs">Stairs</option>
                <option value="covered_stairs">Covered Stairs</option>
                <option value="road">Road</option>
            </select>

            <select class="form-select form-select-sm mb-1 setting-risk">
                <option value="1">Risk: Low</option>
                <option value="2">Risk: Medium</option>
                <option value="3">Risk: High</option>
            </select>

            <select class="form-select form-select-sm mb-1 setting-difficulty">
                <option value="1">Difficulty: Easy</option>
                <option value="2">Difficulty: Moderate</option>
                <option value="3">Difficulty: Hard</option>
            </select>

            <select class="form-select form-select-sm mb-1 setting-blocked">
                <option value="0">Open</option>
                <option value="1">Blocked</option>
            </select>

            <input type="text" class="form-control form-control-sm setting-note" placeholder="Hazard note" />
        `;

        cell.innerHTML = '';
        cell.appendChild(form);

        const typeSelect = cell.querySelector('.setting-type');
        const riskSelect = cell.querySelector('.setting-risk');
        const difficultySelect = cell.querySelector('.setting-difficulty');
        const blockedSelect = cell.querySelector('.setting-blocked');
        const noteInput = cell.querySelector('.setting-note');

        typeSelect.value = originalType;
        riskSelect.value = originalRisk;
        difficultySelect.value = originalDifficulty;
        blockedSelect.value = originalBlocked;
        noteInput.value = originalNote;

        typeSelect.focus();

        const renderCell = () => {
            cell.innerHTML = `<span class="path-pill path-type-pill">${cell.dataset.type}</span>`;
        };

        const save = async () => {
            try {
                const payload = {
                    type: typeSelect.value,
                    risk_level: Number(riskSelect.value),
                    difficulty_level: Number(difficultySelect.value),
                    is_blocked: Number(blockedSelect.value),
                    hazard_note: noteInput.value.trim()
                };

                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.message || 'Update failed.');
                }

                cell.dataset.type = data.type;
                cell.dataset.risk = data.risk_level;
                cell.dataset.difficulty = data.difficulty_level;
                cell.dataset.blocked = data.is_blocked ? '1' : '0';
                cell.dataset.note = data.hazard_note ?? '';

                renderCell();
                window.location.reload();
            } catch (error) {
                alert(error.message || 'Update failed.');
                renderCell();
            }
        };

        [typeSelect, riskSelect, difficultySelect, blockedSelect].forEach(el => {
            el.addEventListener('change', save);
        });

        noteInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') save();
            if (e.key === 'Escape') renderCell();
        });

        noteInput.addEventListener('blur', save);
    });
});
</script>

@endsection
