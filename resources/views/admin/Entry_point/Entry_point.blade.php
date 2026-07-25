@extends('admin.dashboard')

@section('admin')

<style>
    .entry-wrapper {
        padding: 24px;
    }

    .entry-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .entry-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .entry-card-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .entry-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .entry-form-body {
        padding: 24px;
    }

    .entry-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .entry-form-control {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }

    .entry-form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .entry-upload-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .entry-upload-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .entry-reset-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: #fee2e2;
        color: #b91c1c;
    }

    .entry-reset-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .entry-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .entry-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .entry-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .entry-table {
        margin-bottom: 0;
    }

    .entry-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .entry-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .entry-id-badge {
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

    .entry-name-text {
        font-weight: 800;
        color: #0f172a;
    }

    .editable-name {
        border-radius: 12px;
        transition: 0.18s ease;
    }

    .editable-name:hover {
        background: #f8fafc;
        cursor: pointer;
    }

    .editable-name input {
        border-radius: 12px;
        border: 1px solid #dbe3ef;
    }

    .empty-entry-box {
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
        .entry-wrapper {
            padding: 14px;
        }

        .entry-card-header,
        .entry-form-body,
        .entry-table-header {
            padding: 18px;
        }

        .entry-table {
            min-width: 720px;
        }

        .entry-form-actions {
            flex-direction: column;
            align-items: stretch !important;
        }

        .entry-upload-btn,
        .entry-reset-btn {
            width: 100%;
        }
    }
</style>

<div class="entry-wrapper">

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
    <div class="entry-card">
        <div class="entry-card-header">
            <h4>Entry Points Manager</h4>
            <p>Upload, reset, and rename campus entry points used as routing start locations.</p>
        </div>

        <div class="entry-form-body">
            <form action="{{ route('admin.entry-point.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3 align-items-end">
                    <div class="col-lg-8">
                        <label class="entry-form-label">Upload Entry Points GeoJSON</label>
                        <input
                            type="file"
                            name="geojson"
                            class="form-control entry-form-control"
                            accept=".json,.geojson"
                            required
                        >
                    </div>

                    <div class="col-lg-4">
                        <button type="submit" class="entry-upload-btn w-100">
                            <i class="ri-upload-cloud-2-line me-1"></i>
                            Upload Entry Points
                        </button>
                    </div>
                </div>
            </form>

            <form
                action="{{ route('admin.entry-point.reset') }}"
                method="POST"
                class="mt-3"
                onsubmit="return confirm('Restore previous entry point upload?')"
            >
                @csrf
                @method('DELETE')

                <div class="d-flex justify-content-end entry-form-actions">
                    <button type="submit" class="entry-reset-btn">
                        <i class="ri-refresh-line me-1"></i>
                        Reset Entry Points
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="entry-table-card">
        <div class="entry-table-header">
            <div>
                <h5>Uploaded Entry Points</h5>
                <span class="muted-small">
                    Double click / tap an entry point name to edit.
                </span>
            </div>

            <span class="muted-small">
                Total Entry Points: {{ $entryPoints->total() ?? $entryPoints->count() }}
            </span>
        </div>

        <div class="table-responsive">
            @if($entryPoints->count())
                <table class="table entry-table align-middle">
                    <thead>
                        <tr>
                            <th width="80">ID</th>
                            <th>Name</th>
                            <th width="220">Created</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($entryPoints as $entryPoint)
                            <tr>
                                <td>
                                    <span class="entry-id-badge">
                                        #{{ $entryPoint->id }}
                                    </span>
                                </td>

                                <td
                                    class="editable-name"
                                    data-url="{{ route('admin.entry-point.updateName', $entryPoint->id) }}"
                                    data-name="{{ $entryPoint->name }}"
                                >
                                    <span class="entry-name-text">
                                        {{ $entryPoint->name }}
                                    </span>
                                </td>

                                <td>
                                    <div class="entry-name-text">
                                        {{ optional($entryPoint->created_at)->format('M d, Y') }}
                                    </div>
                                    <div class="muted-small">
                                        {{ optional($entryPoint->created_at)->format('h:i A') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-entry-box">
                    No entry points uploaded yet.
                </div>
            @endif
        </div>

        @if($entryPoints->count())
            <div class="px-4 py-3 border-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="muted-small">
                        Showing {{ $entryPoints->firstItem() }} to {{ $entryPoints->lastItem() }}
                        of {{ $entryPoints->total() }} entry points
                    </div>

                    @if ($entryPoints->hasPages())
                        <ul class="custom-pagination">
                            @if ($entryPoints->onFirstPage())
                                <li class="disabled"><span>«</span></li>
                            @else
                                <li><a href="{{ $entryPoints->previousPageUrl() }}">«</a></li>
                            @endif

                            @foreach ($entryPoints->getUrlRange(1, $entryPoints->lastPage()) as $page => $url)
                                @if ($page == $entryPoints->currentPage())
                                    <li class="active"><span>{{ $page }}</span></li>
                                @else
                                    <li><a href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach

                            @if ($entryPoints->hasMorePages())
                                <li><a href="{{ $entryPoints->nextPageUrl() }}">»</a></li>
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
                cell.innerHTML = `<span class="entry-name-text">${original}</span>`;
                return;
            }

            if (value === original) {
                cell.innerHTML = `<span class="entry-name-text">${original}</span>`;
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

                cell.innerHTML = `<span class="entry-name-text">${data.name}</span>`;
                cell.dataset.name = data.name;
            } catch (error) {
                alert(error.message || 'Update failed.');
                cell.innerHTML = `<span class="entry-name-text">${original}</span>`;
            }
        };

        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') save();

            if (e.key === 'Escape') {
                cell.innerHTML = `<span class="entry-name-text">${original}</span>`;
            }
        });

        input.addEventListener('blur', save);
    });
});
</script>

@endsection
