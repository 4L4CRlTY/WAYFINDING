@extends('admin.dashboard')

@section('admin')

<style>
    .building-wrapper {
        padding: 24px;
    }

    .building-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .building-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .building-card-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .building-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .building-form-body {
        padding: 24px;
    }

    .building-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .building-form-control {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }

    .building-form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .building-upload-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .building-upload-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .building-reset-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: #fee2e2;
        color: #b91c1c;
    }

    .building-reset-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .building-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .building-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .building-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .building-table {
        margin-bottom: 0;
    }

    .building-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .building-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .building-id-badge {
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

    .building-name-text {
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

    .color-cell {
        min-width: 250px;
    }

    .color-preview {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        border: 1px solid #dbe3ef;
        display: inline-block;
        flex-shrink: 0;
        box-shadow: inset 0 0 0 2px rgba(255,255,255,0.5);
    }

    .building-color-picker {
        padding: 0.2rem;
        width: 52px;
        height: 38px;
        min-width: 52px;
        cursor: pointer;
        border-radius: 12px;
        border: 1px solid #dbe3ef;
    }

    .color-code {
        font-weight: 800;
        color: #475569;
        font-size: 12px;
    }

    .recommended-colors {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .recommended-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 800;
    }

    .recommended-color-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #dbe3ef;
        background: #ffffff;
        color: #334155;
        border-radius: 999px;
        padding: 6px 11px;
        font-size: 12px;
        font-weight: 800;
        transition: 0.2s ease;
        cursor: pointer;
    }

    .recommended-color-btn:hover {
        background: #f8fafc;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
    }

    .recommended-swatch {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 1px solid rgba(0,0,0,0.15);
        display: inline-block;
    }

    .saving-color {
        opacity: 0.6;
        pointer-events: none;
    }

    .building-label-control {
        min-width: 178px;
    }

    .building-label-switch {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        margin: 0;
        padding: 7px 11px;
        border: 1px solid #dbe3ef;
        border-radius: 999px;
        background: #f8fafc;
    }

    .building-label-switch .form-check-input {
        width: 2.25rem;
        height: 1.2rem;
        margin: 0;
        cursor: pointer;
        border-color: #94a3b8;
        box-shadow: none;
    }

    .building-label-switch .form-check-input:checked {
        border-color: #0f9f78;
        background-color: #0f9f78;
    }

    .building-label-status {
        min-width: 44px;
        color: #64748b;
        font-size: 12px;
        font-weight: 850;
    }

    .building-label-status.is-visible {
        color: #047857;
    }

    .building-label-help {
        display: block;
        margin-top: 6px;
        color: #94a3b8;
        font-size: 11px;
        line-height: 1.3;
    }

    .empty-building-box {
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


    .building-search-form {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .building-search-box {
        position: relative;
        min-width: 280px;
    }

    .building-search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: 18px;
    }

    .building-search-input {
        width: 100%;
        min-height: 44px;
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        padding: 10px 14px 10px 42px;
        font-size: 14px;
        outline: none;
        transition: 0.2s ease;
    }

    .building-search-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .building-search-btn {
        border: none;
        border-radius: 14px;
        min-height: 44px;
        padding: 10px 18px;
        font-weight: 800;
        color: white;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.16);
    }

    .building-search-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .building-clear-btn {
        border-radius: 14px;
        min-height: 44px;
        padding: 10px 16px;
        font-weight: 800;
        text-decoration: none;
        background: #f1f5f9;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .building-clear-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .building-label-editor-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 44px;
        padding: 10px 16px;
        border-radius: 14px;
        color: #fff;
        background: linear-gradient(135deg, #0f766e, #287bc2);
        box-shadow: 0 12px 24px rgba(40, 123, 194, .18);
        font-size: 12px;
        font-weight: 850;
        text-decoration: none;
    }

    .building-label-editor-link:hover {
        color: #fff;
        transform: translateY(-1px);
    }

    @media (max-width: 768px) {
        .building-search-form,
        .building-search-box,
        .building-search-btn,
        .building-clear-btn,
        .building-label-editor-link {
            width: 100%;
        }

        .building-search-box {
            min-width: 100%;
        }

        .building-wrapper {
            padding: 14px;
        }

        .building-card-header,
        .building-form-body,
        .building-table-header {
            padding: 18px;
        }

        .building-table {
            min-width: 1080px;
        }

        .building-form-actions {
            flex-direction: column;
            align-items: stretch !important;
        }

        .building-upload-btn,
        .building-reset-btn {
            width: 100%;
        }
    }
</style>

<div class="building-wrapper">

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
    <div class="building-card">
        <div class="building-card-header">
            <h4>Buildings Manager</h4>
            <p>Upload, reset, rename, customize colors, and choose which building labels stay visible on the campus map.</p>
        </div>

        <div class="building-form-body">
            <form action="{{ route('admin.buildings.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3 align-items-end">
                    <div class="col-lg-8">
                        <label class="building-form-label">Upload Buildings GeoJSON</label>
                        <input
                            type="file"
                            name="geojson"
                            class="form-control building-form-control"
                            accept=".json,.geojson"
                            required
                        >
                    </div>

                    <div class="col-lg-4">
                        <button type="submit" class="building-upload-btn w-100">
                            <i class="ri-upload-cloud-2-line me-1"></i>
                            Upload Buildings
                        </button>
                    </div>
                </div>
            </form>

            <form
                action="{{ route('admin.buildings.reset') }}"
                method="POST"
                class="mt-3"
                onsubmit="return confirm('Restore previous upload?')"
            >
                @csrf
                @method('DELETE')

                <div class="d-flex justify-content-end building-form-actions">
                    <button type="submit" class="building-reset-btn">
                        <i class="ri-refresh-line me-1"></i>
                        Reset Buildings
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="building-table-card">
        <div class="building-table-header">
            <div>
                <h5>Uploaded Buildings</h5>
                <span class="muted-small">
                    Edit names and colors, then toggle a permanent map label for any building.
                </span>
            </div>

            <form action="{{ route('admin.buildings') }}" method="GET" class="building-search-form">
                <div class="building-search-box">
                    <i class="ri-search-line"></i>
                    <input
                        type="text"
                        name="search"
                        class="building-search-input"
                        placeholder="Search building name, ID, or color..."
                        value="{{ $search ?? request('search') }}"
                    >
                </div>

                <button type="submit" class="building-search-btn">
                    <i class="ri-search-line me-1"></i>
                    Search
                </button>

                @if(!empty($search ?? request('search')))
                    <a href="{{ route('admin.buildings') }}" class="building-clear-btn">
                        <i class="ri-close-line me-1"></i>
                        Clear
                    </a>
                @endif
            </form>

            <span class="muted-small">
                Total Buildings: {{ $buildings->total() ?? $buildings->count() }}
            </span>

            <a href="{{ route('admin.buildings.labelEditor') }}" class="building-label-editor-link">
                <i class="ri-drag-move-2-line"></i>
                Open Label Editor
            </a>
        </div>

        <div class="table-responsive">
            @if($buildings->count())
                <table class="table building-table align-middle">
                    <thead>
                        <tr>
                            <th width="80">ID</th>
                            <th>Name</th>
                            <th width="210">Map label</th>
                            <th width="300">Color</th>
                            <th width="210">Created</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($buildings as $building)
                            @php
                                $currentColor = $building->color ?? '#2b82cc';
                            @endphp

                            <tr>
                                <td>
                                    <span class="building-id-badge">
                                        #{{ $building->id }}
                                    </span>
                                </td>

                                <td
                                    class="editable-name"
                                    data-url="{{ route('admin.buildings.updateName', $building->id) }}"
                                    data-name="{{ $building->name }}"
                                >
                                    <span class="building-name-text">
                                        {{ $building->name }}
                                    </span>
                                </td>

                                <td>
                                    <div class="building-label-control">
                                        <label class="building-label-switch" for="building-map-label-{{ $building->id }}">
                                            <input
                                                type="checkbox"
                                                role="switch"
                                                class="form-check-input building-map-label-toggle"
                                                id="building-map-label-{{ $building->id }}"
                                                data-url="{{ route('admin.buildings.updateMapLabel', $building->id) }}"
                                                @checked($building->show_map_label)
                                            >
                                            <span class="building-label-status {{ $building->show_map_label ? 'is-visible' : '' }}">
                                                {{ $building->show_map_label ? 'Visible' : 'Hidden' }}
                                            </span>
                                        </label>
                                        <small class="building-label-help">
                                            Permanent icon + shortened building name
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <div class="color-cell">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span
                                                class="color-preview"
                                                style="background: {{ $currentColor }}"
                                            ></span>

                                            <input
                                                type="color"
                                                class="form-control form-control-color building-color-picker"
                                                value="{{ $currentColor }}"
                                                data-url="{{ route('admin.buildings.updateColor', $building->id) }}"
                                                title="Choose color"
                                            >

                                            <small class="color-code">
                                                {{ $currentColor }}
                                            </small>
                                        </div>

                                        <div class="recommended-colors">
                                            <span class="recommended-label">Recommended:</span>

                                            <button
                                                type="button"
                                                class="recommended-color-btn"
                                                data-color="#1e7138"
                                                data-target-url="{{ route('admin.buildings.updateColor', $building->id) }}"
                                                title="#1e7138"
                                            >
                                                <span class="recommended-swatch" style="background:#1e7138;"></span>
                                                #1e7138
                                            </button>

                                            <button
                                                type="button"
                                                class="recommended-color-btn"
                                                data-color="#2b82cc"
                                                data-target-url="{{ route('admin.buildings.updateColor', $building->id) }}"
                                                title="#2b82cc"
                                            >
                                                <span class="recommended-swatch" style="background:#2b82cc;"></span>
                                                #2b82cc
                                            </button>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="building-name-text">
                                        {{ optional($building->created_at)->format('M d, Y') }}
                                    </div>
                                    <div class="muted-small">
                                        {{ optional($building->created_at)->format('h:i A') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-building-box">
                    @if(!empty($search ?? request('search')))
                        No buildings found for "<strong>{{ $search ?? request('search') }}</strong>".
                    @else
                        No buildings uploaded yet.
                    @endif
                </div>
            @endif
        </div>

        @include('admin.partials.pagination', [
            'paginator' => $buildings,
            'label' => 'buildings',
        ])
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

        const save = async () => {
            const value = input.value.trim();

            if (!value) {
                alert('Required');
                cell.innerHTML = `<span class="building-name-text">${original}</span>`;
                return;
            }

            if (value === original) {
                cell.innerHTML = `<span class="building-name-text">${original}</span>`;
                return;
            }

            try {
                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ name: value })
                });

                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.message || 'Failed to update name');
                }

                cell.innerHTML = `<span class="building-name-text">${data.name}</span>`;
                cell.dataset.name = data.name;

            } catch (error) {
                alert(error.message || 'Failed to update building name');
                cell.innerHTML = `<span class="building-name-text">${original}</span>`;
            }
        };

        input.addEventListener('keydown', e => {
            if (e.key === 'Enter') save();

            if (e.key === 'Escape') {
                cell.innerHTML = `<span class="building-name-text">${original}</span>`;
            }
        });

        input.addEventListener('blur', save);
    });
});

async function saveBuildingColor(url, color, cellTd) {
    const preview = cellTd.querySelector('.color-preview');
    const codeText = cellTd.querySelector('.color-code');
    const colorPicker = cellTd.querySelector('.building-color-picker');
    const originalColor = codeText.textContent.trim();

    colorPicker.classList.add('saving-color');

    preview.style.background = color;
    codeText.textContent = color;
    colorPicker.value = color;

    try {
        const res = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ color: color })
        });

        const data = await res.json();

        if (!res.ok) {
            throw new Error(data.message || 'Failed to update color');
        }

        preview.style.background = data.color;
        codeText.textContent = data.color;
        colorPicker.value = data.color;

    } catch (error) {
        alert(error.message || 'Failed to update building color');
        preview.style.background = originalColor;
        codeText.textContent = originalColor;
        colorPicker.value = originalColor;
    } finally {
        colorPicker.classList.remove('saving-color');
    }
}

document.querySelectorAll('.building-color-picker').forEach(input => {
    input.addEventListener('change', async function () {
        const url = this.dataset.url;
        const color = this.value;
        const cellTd = this.closest('td');

        await saveBuildingColor(url, color, cellTd);
    });
});

document.querySelectorAll('.recommended-color-btn').forEach(button => {
    button.addEventListener('click', async function () {
        const url = this.dataset.targetUrl;
        const color = this.dataset.color;
        const cellTd = this.closest('td');

        await saveBuildingColor(url, color, cellTd);
    });
});

document.querySelectorAll('.building-map-label-toggle').forEach(input => {
    input.addEventListener('change', async function () {
        const enabled = this.checked;
        const status = this.closest('.building-label-switch')
            ?.querySelector('.building-label-status');

        this.disabled = true;

        try {
            const res = await fetch(this.dataset.url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ show_map_label: enabled })
            });
            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.message || 'Failed to update the map label');
            }

            this.checked = Boolean(data.show_map_label);
            if (status) {
                status.textContent = this.checked ? 'Visible' : 'Hidden';
                status.classList.toggle('is-visible', this.checked);
            }
        } catch (error) {
            this.checked = !enabled;
            alert(error.message || 'Failed to update the map label');
        } finally {
            this.disabled = false;
        }
    });
});
</script>

@endsection
