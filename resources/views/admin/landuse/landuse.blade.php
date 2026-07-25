@extends('admin.dashboard')

@section('admin')

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<style>
    .landuse-wrapper {
        padding: 24px;
    }

    .landuse-card {
        border: none;
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .landuse-card-header {
        padding: 22px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .landuse-card-header h4 {
        margin: 0;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .landuse-card-header p {
        margin: 6px 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .landuse-form-body {
        padding: 24px;
    }

    .landuse-form-label {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .landuse-form-control,
    .landuse-form-select {
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        min-height: 46px;
        font-size: 14px;
    }

    .landuse-form-control:focus,
    .landuse-form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .landuse-upload-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .landuse-upload-btn:hover {
        opacity: 0.94;
        color: white;
    }

    .landuse-reset-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 800;
        background: #fee2e2;
        color: #b91c1c;
    }

    .landuse-reset-btn:hover {
        background: #fecaca;
        color: #991b1b;
    }

    .landuse-table-card {
        margin-top: 24px;
        border: none;
        border-radius: 22px;
        background: white;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .landuse-table-header {
        padding: 20px 24px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .landuse-table-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .muted-small {
        color: #64748b;
        font-size: 12px;
    }

    .landuse-table {
        margin-bottom: 0;
    }

    .landuse-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 16px;
        white-space: nowrap;
    }

    .landuse-table tbody td {
        vertical-align: middle;
        padding: 16px;
        color: #334155;
        font-size: 14px;
    }

    .landuse-id-badge {
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

    .landuse-name-text {
        font-weight: 800;
        color: #0f172a;
    }

    #landuseTable .editable-name {
        border-radius: 12px;
        transition: 0.18s ease;
    }

    #landuseTable .editable-name:hover {
        background: #f8fafc;
        cursor: pointer;
    }

    #landuseTable .editable-name.editing {
        background: #fef3c7;
    }

    #landuseTable .editable-input {
        min-width: 180px;
        border-radius: 12px;
        border: 1px solid #dbe3ef;
    }

    .landuse-preview {
        object-fit: cover;
        border-radius: 14px;
        border: 1px solid #dbe3ef;
        background: #fff;
        max-width: 100%;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    }

    .landuse-no-image {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        background: #f1f5f9;
        color: #475569;
        white-space: nowrap;
    }

    .landuse-mini-number {
        font-weight: 800;
        color: #334155;
        font-size: 13px;
        white-space: nowrap;
    }

    .quick-update-form .form-control,
    .quick-update-form .form-control-sm {
        border-radius: 12px;
        border: 1px solid #dbe3ef;
        font-size: 12px;
    }

    .quick-save-btn {
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
        background: #dcfce7;
        color: #15803d;
    }

    .quick-save-btn:hover {
        background: #bbf7d0;
        color: #166534;
    }

    .texture-btn {
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .texture-btn:hover {
        background: #dbeafe;
        color: #1e40af;
    }

    .texture-btn:disabled {
        background: #e2e8f0;
        color: #94a3b8;
        cursor: not-allowed;
    }

    .empty-landuse-box {
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

    .landuse-editor-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(6px);
        z-index: 1050;
        display: none;
    }

    .landuse-editor-backdrop.active {
        display: block;
    }

    .landuse-editor-modal {
        position: fixed;
        inset: 50% auto auto 50%;
        transform: translate(-50%, -50%);
        width: min(1120px, 95vw);
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
        z-index: 1060;
        display: none;
        overflow: hidden;
    }

    .landuse-editor-modal.active {
        display: block;
    }

    .landuse-editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        padding: 20px 24px;
        background: linear-gradient(135deg, #0f766e, #2563eb);
        color: white;
    }

    .landuse-editor-title {
        font-size: 18px;
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .landuse-editor-subtitle {
        font-size: 12px;
        opacity: 0.9;
        margin-top: 4px;
    }

    .landuse-editor-close-btn {
        border: none;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 800;
        background: rgba(255,255,255,0.18);
        color: white;
    }

    .landuse-editor-close-btn:hover {
        background: rgba(255,255,255,0.28);
        color: white;
    }

    .landuse-editor-body {
        display: grid;
        grid-template-columns: 1fr 320px;
        min-height: 620px;
    }

    .landuse-editor-canvas-wrap {
        position: relative;
        background: #f8fafc;
        min-height: 620px;
        overflow: hidden;
    }

    #landuseEditorMap {
        position: absolute;
        inset: 0;
        z-index: 1;
    }

    .landuse-editor-image {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 120px;
        height: 120px;
        object-fit: cover;
        cursor: grab;
        user-select: none;
        z-index: 9999;
        transform-origin: center center;
        transform: translate(-50%, -50%) rotate(0deg);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.22);
        border-radius: 14px;
        border: 2px solid rgba(255, 255, 255, 0.9);
    }

    .landuse-editor-controls {
        padding: 20px;
        border-left: 1px solid #e5e7eb;
        background: #ffffff;
        overflow-y: auto;
        max-height: 620px;
    }

    .landuse-editor-controls label {
        font-weight: 800;
        color: #0f172a;
        font-size: 13px;
    }

    .landuse-editor-controls .form-control {
        border-radius: 12px;
        border: 1px solid #dbe3ef;
    }

    .normalized-box {
        margin: 14px 0;
        padding: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        font-size: 12px;
        color: #334155;
        line-height: 1.6;
    }

    .save-editor-btn {
        border: none;
        border-radius: 15px;
        padding: 12px 16px;
        font-weight: 800;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }

    .save-editor-btn:hover {
        opacity: 0.94;
        color: white;
    }

    @media (max-width: 900px) {
        .landuse-editor-body {
            grid-template-columns: 1fr;
        }

        .landuse-editor-controls {
            border-left: none;
            border-top: 1px solid #e5e7eb;
            max-height: 360px;
        }
    }

    @media (max-width: 768px) {
        .landuse-wrapper {
            padding: 14px;
        }

        .landuse-card-header,
        .landuse-form-body,
        .landuse-table-header {
            padding: 18px;
        }

        .landuse-table {
            min-width: 2100px;
        }

        .landuse-form-actions {
            flex-direction: column;
            align-items: stretch !important;
        }

        .landuse-upload-btn,
        .landuse-reset-btn {
            width: 100%;
        }

        #landuseTable .editable-input {
            min-width: 140px;
            font-size: 14px;
        }

        .landuse-preview {
            width: 90px !important;
            height: 90px !important;
        }

        .landuse-editor-modal {
            width: 96vw;
        }

        .landuse-editor-canvas-wrap {
            min-height: 360px;
        }

        .landuse-editor-body {
            min-height: auto;
        }
    }
</style>

<div class="landuse-wrapper">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please check the form:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Upload Card --}}
    <div class="landuse-card">
        <div class="landuse-card-header">
            <h4>Landuse Manager</h4>
            <p>Upload landuse GeoJSON, assign default texture images, and manage polygon image alignment.</p>
        </div>

        <div class="landuse-form-body">
            <form action="{{ route('admin.landuse.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="landuse-form-label">Upload Landuse GeoJSON</label>
                        <input
                            type="file"
                            name="geojson"
                            class="form-control landuse-form-control"
                            accept=".json,.geojson,.txt"
                            required
                        >
                    </div>

                    <div class="col-lg-6">
                        <label class="landuse-form-label">Default Image for Uploaded Landuse Optional</label>
                        <input
                            type="file"
                            name="default_image"
                            class="form-control landuse-form-control"
                            accept="image/*"
                        >
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="landuse-form-label">Default Width</label>
                        <input
                            type="number"
                            name="default_image_width"
                            class="form-control landuse-form-control"
                            value="120"
                            min="20"
                            max="2000"
                        >
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="landuse-form-label">Default Height</label>
                        <input
                            type="number"
                            name="default_image_height"
                            class="form-control landuse-form-control"
                            value="120"
                            min="20"
                            max="2000"
                        >
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="landuse-form-label">Default Rotation</label>
                        <input
                            type="number"
                            name="default_image_rotation"
                            class="form-control landuse-form-control"
                            value="0"
                            min="-360"
                            max="360"
                        >
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="landuse-form-label">Default Offset X</label>
                        <input
                            type="number"
                            name="default_image_offset_x"
                            class="form-control landuse-form-control"
                            value="0"
                            min="-5000"
                            max="5000"
                        >
                    </div>

                    <div class="col-lg-2 col-md-4">
                        <label class="landuse-form-label">Default Offset Y</label>
                        <input
                            type="number"
                            name="default_image_offset_y"
                            class="form-control landuse-form-control"
                            value="0"
                            min="-5000"
                            max="5000"
                        >
                    </div>

                    <div class="col-lg-2 col-md-4 d-flex align-items-end">
                        <button type="submit" class="landuse-upload-btn w-100">
                            <i class="ri-upload-cloud-2-line me-1"></i>
                            Upload
                        </button>
                    </div>
                </div>
            </form>

            <form
                action="{{ route('admin.landuse.reset') }}"
                method="POST"
                class="mt-3"
                onsubmit="return confirm('Restore previous landuse upload?')"
            >
                @csrf
                @method('DELETE')

                <div class="d-flex justify-content-end landuse-form-actions">
                    <button type="submit" class="landuse-reset-btn">
                        <i class="ri-refresh-line me-1"></i>
                        Reset Landuse
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="landuse-table-card">
        <div class="landuse-table-header">
            <div>
                <h5>Uploaded Landuse</h5>
                <span class="muted-small">
                    PC: double click name to edit · Phone: tap name to edit.
                </span>
            </div>

            <span class="muted-small">
                Total Landuse: {{ $landuses->total() ?? $landuses->count() }}
            </span>
        </div>

        <div class="table-responsive">
            @if ($landuses->count())
                <table class="table landuse-table align-middle" id="landuseTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th style="min-width: 180px;">Name</th>
                            <th style="width: 180px;">Preview</th>
                            <th style="width: 100px;">W</th>
                            <th style="width: 100px;">H</th>
                            <th style="width: 100px;">Rot</th>
                            <th style="width: 100px;">X</th>
                            <th style="width: 100px;">Y</th>
                            <th style="width: 110px;">Scale X</th>
                            <th style="width: 110px;">Scale Y</th>
                            <th style="width: 120px;">Offset X Ratio</th>
                            <th style="width: 120px;">Offset Y Ratio</th>
                            <th style="width: 120px;">Poly Angle</th>
                            <th style="width: 120px;">Local Rot</th>
                            <th style="min-width: 290px;">Quick Update</th>
                            <th style="width: 130px;">Editor</th>
                            <th style="width: 200px;">Created At</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($landuses as $landuse)
                            <tr>
                                <td>
                                    <span class="landuse-id-badge">
                                        #{{ $landuse->id }}
                                    </span>
                                </td>

                                <td
                                    class="editable-name"
                                    data-url="{{ route('admin.landuse.updateName', $landuse->id) }}"
                                    data-name="{{ $landuse->name }}"
                                    title="Double click on desktop or tap on mobile to edit"
                                >
                                    <span class="landuse-name-text">
                                        {{ $landuse->name }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    @if ($landuse->image)
                                        <img
                                            src="{{ asset('landuse_images/' . $landuse->image) }}"
                                            alt="Landuse Image"
                                            width="{{ $landuse->image_width ?? 120 }}"
                                            height="{{ $landuse->image_height ?? 120 }}"
                                            class="landuse-preview"
                                        >

                                        <div class="small text-muted mt-2 text-break">
                                            {{ $landuse->image }}
                                        </div>
                                    @else
                                        <span class="landuse-no-image">No Image</span>
                                    @endif
                                </td>

                                <td><span class="landuse-mini-number">{{ $landuse->image_width ?? 120 }}</span></td>
                                <td><span class="landuse-mini-number">{{ $landuse->image_height ?? 120 }}</span></td>
                                <td><span class="landuse-mini-number">{{ $landuse->image_rotation ?? 0 }}°</span></td>
                                <td><span class="landuse-mini-number">{{ $landuse->image_offset_x ?? 0 }}</span></td>
                                <td><span class="landuse-mini-number">{{ $landuse->image_offset_y ?? 0 }}</span></td>
                                <td><span class="landuse-mini-number">{{ number_format((float) ($landuse->image_scale_x ?? 1), 4) }}</span></td>
                                <td><span class="landuse-mini-number">{{ number_format((float) ($landuse->image_scale_y ?? 1), 4) }}</span></td>
                                <td><span class="landuse-mini-number">{{ number_format((float) ($landuse->image_offset_x_ratio ?? 0), 4) }}</span></td>
                                <td><span class="landuse-mini-number">{{ number_format((float) ($landuse->image_offset_y_ratio ?? 0), 4) }}</span></td>
                                <td><span class="landuse-mini-number">{{ number_format((float) ($landuse->polygon_base_angle ?? 0), 4) }}</span></td>
                                <td><span class="landuse-mini-number">{{ number_format((float) ($landuse->image_local_rotation ?? 0), 4) }}</span></td>

                                <td>
                                    <form
                                        action="{{ route('admin.landuse.updateImage', $landuse->id) }}"
                                        method="POST"
                                        enctype="multipart/form-data"
                                        class="row g-2 quick-update-form"
                                    >
                                        @csrf

                                        <div class="col-12">
                                            <input
                                                type="file"
                                                name="image"
                                                class="form-control form-control-sm"
                                                accept="image/*"
                                            >
                                        </div>

                                        <div class="col-4">
                                            <input
                                                type="number"
                                                name="image_width"
                                                class="form-control form-control-sm"
                                                value="{{ $landuse->image_width ?? 120 }}"
                                                min="20"
                                                max="2000"
                                                placeholder="Width"
                                                required
                                            >
                                        </div>

                                        <div class="col-4">
                                            <input
                                                type="number"
                                                name="image_height"
                                                class="form-control form-control-sm"
                                                value="{{ $landuse->image_height ?? 120 }}"
                                                min="20"
                                                max="2000"
                                                placeholder="Height"
                                                required
                                            >
                                        </div>

                                        <div class="col-4">
                                            <input
                                                type="number"
                                                name="image_rotation"
                                                class="form-control form-control-sm"
                                                value="{{ $landuse->image_rotation ?? 0 }}"
                                                min="-360"
                                                max="360"
                                                placeholder="Rotation"
                                                required
                                            >
                                        </div>

                                        <div class="col-6">
                                            <input
                                                type="number"
                                                name="image_offset_x"
                                                class="form-control form-control-sm"
                                                value="{{ $landuse->image_offset_x ?? 0 }}"
                                                min="-5000"
                                                max="5000"
                                                placeholder="Offset X"
                                            >
                                        </div>

                                        <div class="col-6">
                                            <input
                                                type="number"
                                                name="image_offset_y"
                                                class="form-control form-control-sm"
                                                value="{{ $landuse->image_offset_y ?? 0 }}"
                                                min="-5000"
                                                max="5000"
                                                placeholder="Offset Y"
                                            >
                                        </div>

                                        <div class="col-12">
                                            <button type="submit" class="quick-save-btn w-100">
                                                Save
                                            </button>
                                        </div>
                                    </form>
                                </td>

                                <td>
                                    <button
                                        type="button"
                                        class="texture-btn open-landuse-editor"
                                        data-id="{{ $landuse->id }}"
                                        data-name="{{ $landuse->name }}"
                                        data-image="{{ $landuse->image ? asset('landuse_images/' . $landuse->image) : '' }}"
                                        data-width="{{ $landuse->image_width ?? 120 }}"
                                        data-height="{{ $landuse->image_height ?? 120 }}"
                                        data-rotation="{{ $landuse->image_rotation ?? 0 }}"
                                        data-offset-x="{{ $landuse->image_offset_x ?? 0 }}"
                                        data-offset-y="{{ $landuse->image_offset_y ?? 0 }}"
                                        data-scale-x="{{ $landuse->image_scale_x ?? 1 }}"
                                        data-scale-y="{{ $landuse->image_scale_y ?? 1 }}"
                                        data-offset-x-ratio="{{ $landuse->image_offset_x_ratio ?? 0 }}"
                                        data-offset-y-ratio="{{ $landuse->image_offset_y_ratio ?? 0 }}"
                                        data-polygon-base-angle="{{ $landuse->polygon_base_angle ?? 0 }}"
                                        data-image-local-scale-x="{{ $landuse->image_local_scale_x ?? 1 }}"
                                        data-image-local-scale-y="{{ $landuse->image_local_scale_y ?? 1 }}"
                                        data-image-local-offset-u="{{ $landuse->image_local_offset_u ?? 0 }}"
                                        data-image-local-offset-v="{{ $landuse->image_local_offset_v ?? 0 }}"
                                        data-image-local-rotation="{{ $landuse->image_local_rotation ?? 0 }}"
                                        data-geometry='@json($landuse->geometry)'
                                        data-update-url="{{ route('admin.landuse.updateEditor', $landuse->id) }}"
                                        @if (!$landuse->image) disabled @endif
                                    >
                                        Edit Texture
                                    </button>
                                </td>

                                <td>
                                    <div class="landuse-name-text">
                                        {{ optional($landuse->created_at)->format('M d, Y') }}
                                    </div>
                                    <div class="muted-small">
                                        {{ optional($landuse->created_at)->format('h:i A') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-landuse-box">
                    No landuse uploaded yet.
                </div>
            @endif
        </div>

        @if ($landuses->count())
            <div class="px-4 py-3 border-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="muted-small">
                        Showing {{ $landuses->firstItem() }} to {{ $landuses->lastItem() }}
                        of {{ $landuses->total() }} landuse records
                    </div>

                    @if ($landuses->hasPages())
                        <ul class="custom-pagination">
                            @if ($landuses->onFirstPage())
                                <li class="disabled"><span>«</span></li>
                            @else
                                <li><a href="{{ $landuses->previousPageUrl() }}">«</a></li>
                            @endif

                            @foreach ($landuses->getUrlRange(1, $landuses->lastPage()) as $page => $url)
                                @if ($page == $landuses->currentPage())
                                    <li class="active"><span>{{ $page }}</span></li>
                                @else
                                    <li><a href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach

                            @if ($landuses->hasMorePages())
                                <li><a href="{{ $landuses->nextPageUrl() }}">»</a></li>
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

<div class="landuse-editor-backdrop" id="landuseEditorBackdrop"></div>

<div class="landuse-editor-modal" id="landuseEditorModal">
    <div class="landuse-editor-header">
        <div>
            <div class="landuse-editor-title" id="landuseEditorTitle">Landuse Texture Editor</div>
            <div class="landuse-editor-subtitle">Real GeoJSON polygon is shown below. Drag the image only.</div>
        </div>

        <button type="button" class="landuse-editor-close-btn" id="closeLanduseEditor">
            Close
        </button>
    </div>

    <div class="landuse-editor-body">
        <div class="landuse-editor-canvas-wrap" id="landuseEditorCanvasWrap">
            <div id="landuseEditorMap"></div>
            <img id="landuseEditorImage" class="landuse-editor-image" src="" alt="Texture">
        </div>

        <div class="landuse-editor-controls">
            <div class="mb-3">
                <label class="form-label">Width</label>
                <input type="range" id="editorWidth" min="20" max="2000" value="120" class="form-range">
                <input type="number" id="editorWidthNumber" class="form-control" value="120">
            </div>

            <div class="mb-3">
                <label class="form-label">Height</label>
                <input type="range" id="editorHeight" min="20" max="2000" value="120" class="form-range">
                <input type="number" id="editorHeightNumber" class="form-control" value="120">
            </div>

            <div class="mb-3">
                <label class="form-label">Rotation</label>
                <input type="range" id="editorRotation" min="-360" max="360" value="0" class="form-range">
                <input type="number" id="editorRotationNumber" class="form-control" value="0">
            </div>

            <div class="mb-3">
                <label class="form-label">Offset X</label>
                <input type="number" id="editorOffsetX" class="form-control" value="0">
            </div>

            <div class="mb-3">
                <label class="form-label">Offset Y</label>
                <input type="number" id="editorOffsetY" class="form-control" value="0">
            </div>

            <div class="normalized-box">
                <div><strong>Normalized Values</strong></div>
                <div>Scale X: <span id="normalizedScaleX">1.0000</span></div>
                <div>Scale Y: <span id="normalizedScaleY">1.0000</span></div>
                <div>Offset X Ratio: <span id="normalizedOffsetXRatio">0.0000</span></div>
                <div>Offset Y Ratio: <span id="normalizedOffsetYRatio">0.0000</span></div>
                <hr>
                <div><strong>Polygon-aware Values</strong></div>
                <div>Polygon Base Angle: <span id="polygonBaseAnglePreview">0.0000</span></div>
                <div>Local Scale X: <span id="localScaleXPreview">1.0000</span></div>
                <div>Local Scale Y: <span id="localScaleYPreview">1.0000</span></div>
                <div>Local Offset U: <span id="localOffsetUPreview">0.0000</span></div>
                <div>Local Offset V: <span id="localOffsetVPreview">0.0000</span></div>
                <div>Local Rotation: <span id="localRotationPreview">0.0000</span></div>
            </div>

            <button type="button" class="save-editor-btn w-100" id="saveLanduseEditor">
                Save Editor Settings
            </button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editableCells = document.querySelectorAll('.editable-name');
        const isMobile = window.matchMedia('(max-width: 768px)').matches;

        editableCells.forEach(cell => {
            cell.addEventListener('dblclick', function() {
                if (!isMobile) startEdit(cell);
            });

            cell.addEventListener('click', function() {
                if (isMobile) startEdit(cell);
            });
        });

        function startEdit(cell) {
            if (cell.querySelector('input')) return;

            const originalText = cell.dataset.name ? cell.dataset.name.trim() : cell.textContent.trim();
            const url = cell.dataset.url;

            cell.classList.add('editing');

            const input = document.createElement('input');
            input.type = 'text';
            input.value = originalText;
            input.className = 'form-control form-control-sm editable-input';

            cell.innerHTML = '';
            cell.appendChild(input);

            input.focus();
            input.select();

            let isSaving = false;
            let isCancelled = false;

            const cancelEdit = () => {
                isCancelled = true;
                cell.classList.remove('editing');
                cell.innerHTML = `<span class="landuse-name-text">${originalText}</span>`;
                cell.dataset.name = originalText;
            };

            const saveEdit = async () => {
                if (isSaving || isCancelled) return;

                isSaving = true;
                const newValue = input.value.trim();

                if (newValue === '') {
                    alert('Landuse name is required.');
                    cell.classList.remove('editing');
                    cell.innerHTML = `<span class="landuse-name-text">${originalText}</span>`;
                    cell.dataset.name = originalText;
                    return;
                }

                if (newValue === originalText) {
                    cell.classList.remove('editing');
                    cell.innerHTML = `<span class="landuse-name-text">${originalText}</span>`;
                    cell.dataset.name = originalText;
                    return;
                }

                try {
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            name: newValue
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Update failed.');
                    }

                    cell.classList.remove('editing');
                    cell.innerHTML = `<span class="landuse-name-text">${data.name}</span>`;
                    cell.dataset.name = data.name;
                } catch (error) {
                    alert(error.message || 'Update failed.');
                    cell.classList.remove('editing');
                    cell.innerHTML = `<span class="landuse-name-text">${originalText}</span>`;
                    cell.dataset.name = originalText;
                }
            };

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveEdit();
                }

                if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelEdit();
                }
            });

            input.addEventListener('blur', function() {
                saveEdit();
            });
        }

        const editorBackdrop = document.getElementById('landuseEditorBackdrop');
        const editorModal = document.getElementById('landuseEditorModal');
        const editorTitle = document.getElementById('landuseEditorTitle');
        const closeEditorBtn = document.getElementById('closeLanduseEditor');
        const saveEditorBtn = document.getElementById('saveLanduseEditor');
        const editorImage = document.getElementById('landuseEditorImage');
        const editorMapEl = document.getElementById('landuseEditorMap');

        const editorWidth = document.getElementById('editorWidth');
        const editorHeight = document.getElementById('editorHeight');
        const editorRotation = document.getElementById('editorRotation');
        const editorWidthNumber = document.getElementById('editorWidthNumber');
        const editorHeightNumber = document.getElementById('editorHeightNumber');
        const editorRotationNumber = document.getElementById('editorRotationNumber');
        const editorOffsetX = document.getElementById('editorOffsetX');
        const editorOffsetY = document.getElementById('editorOffsetY');

        const normalizedScaleX = document.getElementById('normalizedScaleX');
        const normalizedScaleY = document.getElementById('normalizedScaleY');
        const normalizedOffsetXRatio = document.getElementById('normalizedOffsetXRatio');
        const normalizedOffsetYRatio = document.getElementById('normalizedOffsetYRatio');

        const polygonBaseAnglePreview = document.getElementById('polygonBaseAnglePreview');
        const localScaleXPreview = document.getElementById('localScaleXPreview');
        const localScaleYPreview = document.getElementById('localScaleYPreview');
        const localOffsetUPreview = document.getElementById('localOffsetUPreview');
        const localOffsetVPreview = document.getElementById('localOffsetVPreview');
        const localRotationPreview = document.getElementById('localRotationPreview');

        let currentEditor = {
            id: null,
            name: '',
            updateUrl: '',
            dragX: 0,
            dragY: 0,
            geometry: null,
        };

        let editorMap = null;
        let editorPolygonLayer = null;
        let editorPolygonBounds = null;

        function getPolygonPixelBox() {
            if (!editorMap || !editorPolygonLayer) return null;

            const bounds = editorPolygonLayer.getBounds();
            if (!bounds || !bounds.isValid()) return null;

            const northWest = editorMap.latLngToContainerPoint(bounds.getNorthWest());
            const southEast = editorMap.latLngToContainerPoint(bounds.getSouthEast());

            const width = Math.abs(southEast.x - northWest.x) || 1;
            const height = Math.abs(southEast.y - northWest.y) || 1;

            return {
                width,
                height,
                left: Math.min(northWest.x, southEast.x),
                top: Math.min(northWest.y, southEast.y),
                centerX: (northWest.x + southEast.x) / 2,
                centerY: (northWest.y + southEast.y) / 2
            };
        }

        function normalizeAngleToAcute(angle) {
            let a = angle % 180;
            if (a > 90) a -= 180;
            if (a < -90) a += 180;
            return a;
        }

        function computePolygonAngle(geometry) {
            try {
                if (!editorMap || !geometry || !geometry.coordinates) return 0;

                let ring = null;

                if (geometry.type === 'Polygon') {
                    ring = geometry.coordinates[0] || [];
                } else if (geometry.type === 'MultiPolygon') {
                    ring = geometry.coordinates[0]?.[0] || [];
                }

                if (!ring || ring.length < 2) return 0;

                let bestAngle = 0;
                let longest = 0;

                for (let i = 0; i < ring.length - 1; i++) {
                    const p1 = ring[i];
                    const p2 = ring[i + 1];

                    const latlng1 = L.latLng(Number(p1[1]), Number(p1[0]));
                    const latlng2 = L.latLng(Number(p2[1]), Number(p2[0]));

                    const pt1 = editorMap.latLngToContainerPoint(latlng1);
                    const pt2 = editorMap.latLngToContainerPoint(latlng2);

                    const dx = pt2.x - pt1.x;
                    const dy = pt2.y - pt1.y;

                    const len = Math.sqrt(dx * dx + dy * dy);
                    if (len > longest) {
                        longest = len;
                        bestAngle = Math.atan2(dy, dx) * (180 / Math.PI);
                    }
                }

                return normalizeAngleToAcute(bestAngle);
            } catch (e) {
                return 0;
            }
        }

        function getImageScreenRect() {
            const mapRect = editorMapEl.getBoundingClientRect();

            const width = Number(editorWidth.value || 120);
            const height = Number(editorHeight.value || 120);
            const offsetX = Number(editorOffsetX.value || 0);
            const offsetY = Number(editorOffsetY.value || 0);

            const centerX = (mapRect.width / 2) + offsetX;
            const centerY = (mapRect.height / 2) + offsetY;

            return {
                left: centerX - (width / 2),
                top: centerY - (height / 2),
                right: centerX + (width / 2),
                bottom: centerY + (height / 2),
                width,
                height,
                centerX,
                centerY
            };
        }

        function rotatePoint(cx, cy, x, y, angleDeg) {
            const rad = angleDeg * Math.PI / 180;
            const cos = Math.cos(rad);
            const sin = Math.sin(rad);

            const dx = x - cx;
            const dy = y - cy;

            return {
                x: cx + dx * cos - dy * sin,
                y: cy + dx * sin + dy * cos
            };
        }

        function getImageCornerContainerPoints() {
            const rect = getImageScreenRect();
            const angle = Number(editorRotation.value || 0);

            const cx = rect.centerX;
            const cy = rect.centerY;

            const hw = rect.width / 2;
            const hh = rect.height / 2;

            const rawTL = { x: cx - hw, y: cy - hh };
            const rawTR = { x: cx + hw, y: cy - hh };
            const rawBL = { x: cx - hw, y: cy + hh };
            const rawBR = { x: cx + hw, y: cy + hh };

            const tl = rotatePoint(cx, cy, rawTL.x, rawTL.y, angle);
            const tr = rotatePoint(cx, cy, rawTR.x, rawTR.y, angle);
            const bl = rotatePoint(cx, cy, rawBL.x, rawBL.y, angle);
            const br = rotatePoint(cx, cy, rawBR.x, rawBR.y, angle);

            return { tl, tr, bl, br };
        }

        function containerPointToLatLng(point) {
            if (!editorMap) return null;
            return editorMap.containerPointToLatLng([point.x, point.y]);
        }

        function getImageCornerLatLngs() {
            const pts = getImageCornerContainerPoints();

            return {
                tl: containerPointToLatLng(pts.tl),
                tr: containerPointToLatLng(pts.tr),
                bl: containerPointToLatLng(pts.bl),
                br: containerPointToLatLng(pts.br),
            };
        }

        function updateComputedValues() {
            const polygonBox = getPolygonPixelBox();
            if (!polygonBox) return;

            const scaleX = Number(editorWidth.value || 120) / polygonBox.width;
            const scaleY = Number(editorHeight.value || 120) / polygonBox.height;
            const offsetXRatio = Number(editorOffsetX.value || 0) / polygonBox.width;
            const offsetYRatio = Number(editorOffsetY.value || 0) / polygonBox.height;

            const polygonBaseAngle = computePolygonAngle(currentEditor.geometry);
            const theta = polygonBaseAngle * Math.PI / 180;

            const localScaleX = scaleX;
            const localScaleY = scaleY;
            const localOffsetU = (offsetXRatio * Math.cos(theta)) + (offsetYRatio * Math.sin(theta));
            const localOffsetV = (-offsetXRatio * Math.sin(theta)) + (offsetYRatio * Math.cos(theta));
            const localRotation = Number(editorRotation.value || 0) - polygonBaseAngle;

            normalizedScaleX.textContent = scaleX.toFixed(4);
            normalizedScaleY.textContent = scaleY.toFixed(4);
            normalizedOffsetXRatio.textContent = offsetXRatio.toFixed(4);
            normalizedOffsetYRatio.textContent = offsetYRatio.toFixed(4);

            polygonBaseAnglePreview.textContent = polygonBaseAngle.toFixed(4);
            localScaleXPreview.textContent = localScaleX.toFixed(4);
            localScaleYPreview.textContent = localScaleY.toFixed(4);
            localOffsetUPreview.textContent = localOffsetU.toFixed(4);
            localOffsetVPreview.textContent = localOffsetV.toFixed(4);
            localRotationPreview.textContent = localRotation.toFixed(4);
        }

        function openEditor(data) {
            currentEditor.id = data.id;
            currentEditor.name = data.name;
            currentEditor.updateUrl = data.updateUrl;
            currentEditor.dragX = Number(data.offsetX || 0);
            currentEditor.dragY = Number(data.offsetY || 0);
            currentEditor.geometry = data.geometry;

            editorTitle.textContent = `${data.name} Texture Editor`;
            editorImage.src = data.image || '';
            editorWidth.value = data.width || 120;
            editorHeight.value = data.height || 120;
            editorRotation.value = data.rotation || 0;
            editorWidthNumber.value = data.width || 120;
            editorHeightNumber.value = data.height || 120;
            editorRotationNumber.value = data.rotation || 0;
            editorOffsetX.value = currentEditor.dragX;
            editorOffsetY.value = currentEditor.dragY;

            editorBackdrop.classList.add('active');
            editorModal.classList.add('active');

            applyEditorImageStyle();

            setTimeout(() => {
                if (editorMap) {
                    editorMap.remove();
                    editorMap = null;
                }

                editorMap = L.map('landuseEditorMap', {
                    zoomControl: true,
                    attributionControl: false
                });

                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
                    maxZoom: 22
                }).addTo(editorMap);

                if (editorPolygonLayer) {
                    editorPolygonLayer.remove();
                    editorPolygonLayer = null;
                }

                const feature = {
                    type: 'Feature',
                    geometry: data.geometry,
                    properties: {
                        name: data.name
                    }
                };

                editorPolygonLayer = L.geoJSON(feature, {
                    style: {
                        color: '#16a34a',
                        weight: 2,
                        fillColor: '#86efac',
                        fillOpacity: 0.18
                    }
                }).addTo(editorMap);

                editorPolygonBounds = editorPolygonLayer.getBounds();

                if (editorPolygonBounds.isValid()) {
                    editorMap.fitBounds(editorPolygonBounds.pad(0.2));
                }

                setTimeout(() => {
                    editorMap.invalidateSize();
                    updateComputedValues();
                }, 150);
            }, 120);
        }

        function closeEditor() {
            editorBackdrop.classList.remove('active');
            editorModal.classList.remove('active');

            if (editorMap) {
                editorMap.remove();
                editorMap = null;
            }

            editorPolygonLayer = null;
            editorPolygonBounds = null;
        }

        function applyEditorImageStyle() {
            const w = Number(editorWidth.value || 120);
            const h = Number(editorHeight.value || 120);
            const r = Number(editorRotation.value || 0);
            const x = Number(editorOffsetX.value || 0);
            const y = Number(editorOffsetY.value || 0);

            editorImage.style.width = `${w}px`;
            editorImage.style.height = `${h}px`;
            editorImage.style.transform = `translate(calc(-50% + ${x}px), calc(-50% + ${y}px)) rotate(${r}deg)`;
            editorImage.style.display = editorImage.src ? 'block' : 'none';

            updateComputedValues();
        }

        document.querySelectorAll('.open-landuse-editor').forEach(btn => {
            btn.addEventListener('click', function() {
                openEditor({
                    id: btn.dataset.id,
                    name: btn.dataset.name,
                    image: btn.dataset.image,
                    width: btn.dataset.width,
                    height: btn.dataset.height,
                    rotation: btn.dataset.rotation,
                    offsetX: btn.dataset.offsetX,
                    offsetY: btn.dataset.offsetY,
                    geometry: JSON.parse(btn.dataset.geometry),
                    updateUrl: btn.dataset.updateUrl,
                });
            });
        });

        [editorWidth, editorHeight, editorRotation].forEach(input => {
            input.addEventListener('input', function() {
                editorWidthNumber.value = editorWidth.value;
                editorHeightNumber.value = editorHeight.value;
                editorRotationNumber.value = editorRotation.value;
                applyEditorImageStyle();
            });
        });

        [editorWidthNumber, editorHeightNumber, editorRotationNumber, editorOffsetX, editorOffsetY].forEach(input => {
            input.addEventListener('input', function() {
                editorWidth.value = editorWidthNumber.value;
                editorHeight.value = editorHeightNumber.value;
                editorRotation.value = editorRotationNumber.value;
                applyEditorImageStyle();
            });
        });

        closeEditorBtn.addEventListener('click', closeEditor);
        editorBackdrop.addEventListener('click', closeEditor);
        window.addEventListener('resize', updateComputedValues);

        let isDragging = false;
        let startX = 0;
        let startY = 0;

        editorImage.addEventListener('mousedown', function(e) {
            if (!editorImage.src) return;

            isDragging = true;
            startX = e.clientX - Number(editorOffsetX.value || 0);
            startY = e.clientY - Number(editorOffsetY.value || 0);
            editorImage.style.cursor = 'grabbing';
            e.preventDefault();
        });

        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;

            editorOffsetX.value = e.clientX - startX;
            editorOffsetY.value = e.clientY - startY;
            applyEditorImageStyle();
        });

        document.addEventListener('mouseup', function() {
            isDragging = false;
            editorImage.style.cursor = 'grab';
        });

        saveEditorBtn.addEventListener('click', async function() {
            try {
                const polygonBox = getPolygonPixelBox();

                if (!polygonBox) {
                    alert('Polygon bounds are not ready yet.');
                    return;
                }

                const imageScaleX = Number(editorWidth.value || 120) / polygonBox.width;
                const imageScaleY = Number(editorHeight.value || 120) / polygonBox.height;
                const imageOffsetXRatio = Number(editorOffsetX.value || 0) / polygonBox.width;
                const imageOffsetYRatio = Number(editorOffsetY.value || 0) / polygonBox.height;

                const polygonBaseAngle = computePolygonAngle(currentEditor.geometry);
                const theta = polygonBaseAngle * Math.PI / 180;

                const imageLocalScaleX = imageScaleX;
                const imageLocalScaleY = imageScaleY;
                const imageLocalOffsetU =
                    (imageOffsetXRatio * Math.cos(theta)) +
                    (imageOffsetYRatio * Math.sin(theta));
                const imageLocalOffsetV =
                    (-imageOffsetXRatio * Math.sin(theta)) +
                    (imageOffsetYRatio * Math.cos(theta));
                const imageLocalRotation = Number(editorRotation.value || 0) - polygonBaseAngle;

                const corners = getImageCornerLatLngs();

                const response = await fetch(currentEditor.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        image_width: Number(editorWidth.value || 120),
                        image_height: Number(editorHeight.value || 120),
                        image_rotation: Number(editorRotation.value || 0),
                        image_offset_x: Number(editorOffsetX.value || 0),
                        image_offset_y: Number(editorOffsetY.value || 0),

                        image_scale_x: imageScaleX,
                        image_scale_y: imageScaleY,
                        image_offset_x_ratio: imageOffsetXRatio,
                        image_offset_y_ratio: imageOffsetYRatio,

                        polygon_base_angle: polygonBaseAngle,
                        image_local_scale_x: imageLocalScaleX,
                        image_local_scale_y: imageLocalScaleY,
                        image_local_offset_u: imageLocalOffsetU,
                        image_local_offset_v: imageLocalOffsetV,
                        image_local_rotation: imageLocalRotation,

                        image_tl_lat: corners.tl ? corners.tl.lat : null,
                        image_tl_lng: corners.tl ? corners.tl.lng : null,
                        image_tr_lat: corners.tr ? corners.tr.lat : null,
                        image_tr_lng: corners.tr ? corners.tr.lng : null,
                        image_bl_lat: corners.bl ? corners.bl.lat : null,
                        image_bl_lng: corners.bl ? corners.bl.lng : null,
                        image_br_lat: corners.br ? corners.br.lat : null,
                        image_br_lng: corners.br ? corners.br.lng : null,
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Failed to save editor settings.');
                }

                alert('Editor settings saved successfully.');
                window.location.reload();
            } catch (error) {
                alert(error.message || 'Failed to save editor settings.');
            }
        });
    });
</script>

@endsection
