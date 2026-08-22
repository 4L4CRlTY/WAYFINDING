@extends('admin.dashboard')

@section('admin')

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
    .label-editor-page {
        padding: 24px;
    }

    .label-editor-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 18px;
        padding: 22px 24px;
        border-radius: 22px;
        color: #fff;
        background: linear-gradient(135deg, #123454, #176b78 55%, #2474bd);
        box-shadow: 0 18px 42px rgba(18, 52, 84, .18);
    }

    .label-editor-kicker {
        display: block;
        margin-bottom: 5px;
        color: #8ff1df;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .label-editor-header h3 {
        margin: 0;
        color: #fff !important;
        font-size: 24px;
        font-weight: 850;
        letter-spacing: -.025em;
    }

    .label-editor-header p {
        max-width: 720px;
        margin: 7px 0 0;
        color: rgba(255, 255, 255, .78);
        font-size: 13px;
    }

    .label-editor-back {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 42px;
        padding: 9px 14px;
        border: 1px solid rgba(255, 255, 255, .25);
        border-radius: 13px;
        color: #fff;
        background: rgba(255, 255, 255, .1);
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
    }

    .label-editor-back:hover {
        color: #fff;
        background: rgba(255, 255, 255, .18);
    }

    .label-editor-layout {
        display: grid;
        grid-template-columns: minmax(430px, 1fr) 350px;
        align-items: start;
        gap: 24px;
        min-height: 660px;
        padding: 22px;
        overflow: visible;
        border: 1px solid #c9dced;
        border-radius: 22px;
        background:
            radial-gradient(circle at 18% 8%, rgba(25, 203, 239, .13), transparent 28rem),
            #071522;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .09);
    }

    .label-editor-device-stage {
        min-width: 0;
        display: grid;
        justify-items: center;
        align-content: start;
    }

    .label-editor-device-toolbar {
        width: min(100%, 438px);
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 10px;
        color: #dff7ff;
    }

    .label-editor-device-field {
        display: grid;
        flex: 1 1 auto;
        gap: 5px;
        margin: 0;
    }

    .label-editor-device-field > span {
        color: #8eb1c0;
        font-size: 10px;
        font-weight: 850;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .label-editor-device-select {
        width: 100%;
        min-height: 40px;
        padding: 0 34px 0 11px;
        border: 1px solid #27617b;
        border-radius: 10px;
        color: #eefaff;
        background: #071b2a;
        font-size: 12px;
        font-weight: 750;
    }

    .label-editor-device-status {
        flex: 0 0 auto;
        padding-bottom: 11px;
        color: #8edcf0;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .label-editor-phone {
        padding: 10px;
        border: 1px solid #426778;
        border-radius: 30px;
        background: #02080d;
        box-shadow: 0 26px 70px rgba(0, 0, 0, .48);
    }

    .label-editor-map-wrap {
        position: relative;
        width: 390px;
        height: 844px;
        min-width: 0;
        overflow: hidden;
        border-radius: 21px;
        background: #edf5fb;
    }

    #building-label-editor-map {
        width: 100%;
        height: 100%;
        min-height: 0;
        z-index: 1;
    }

    .label-editor-map-help {
        position: absolute;
        z-index: 600;
        top: 14px;
        left: 50%;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        max-width: calc(100% - 120px);
        padding: 8px 12px;
        border: 1px solid rgba(101, 222, 255, .42);
        border-radius: 999px;
        color: #eafcff;
        background: rgba(8, 35, 58, .9);
        box-shadow: 0 8px 18px rgba(0, 20, 40, .18);
        font-size: 11px;
        font-weight: 800;
        transform: translateX(-50%);
        pointer-events: none;
    }

    .label-editor-panel {
        position: relative;
        z-index: 2;
        max-height: 844px;
        align-self: start;
        overflow-y: auto;
        padding: 20px;
        border: 1px solid #d7e4ef;
        border-radius: 18px;
        background: linear-gradient(180deg, #fbfdff, #f3f8fc);
        box-shadow: 0 18px 46px rgba(0, 0, 0, .2);
    }

    .label-editor-sheet-handle {
        display: none;
    }

    .label-editor-panel-title {
        margin-bottom: 17px;
    }

    .label-editor-panel-title strong {
        display: block;
        color: #173b5d;
        font-size: 16px;
        font-weight: 850;
    }

    .label-editor-panel-title span {
        display: block;
        margin-top: 4px;
        color: #6d8296;
        font-size: 11px;
    }

    .label-editor-field {
        margin-bottom: 16px;
    }

    .label-editor-field > label,
    .label-editor-field-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 7px;
        color: #294c6c;
        font-size: 11px;
        font-weight: 850;
        letter-spacing: .025em;
    }

    .label-editor-input,
    .label-editor-select,
    .label-editor-number {
        width: 100%;
        min-height: 42px;
        border: 1px solid #c7d9e8;
        border-radius: 12px;
        color: #18375d;
        background: #fff;
        font-size: 13px;
        font-weight: 700;
        outline: none;
    }

    .label-editor-input,
    .label-editor-select {
        padding: 9px 11px;
    }

    .label-editor-input:focus,
    .label-editor-select:focus,
    .label-editor-number:focus {
        border-color: #4d9edf;
        box-shadow: 0 0 0 4px rgba(77, 158, 223, .12);
    }

    .label-editor-field-help {
        display: block;
        margin-top: 6px;
        color: #7890a5;
        font-size: 10px;
        line-height: 1.4;
    }

    .label-visible-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 12px;
        border: 1px solid #c7d9e8;
        border-radius: 13px;
        background: #fff;
    }

    .label-visible-copy strong {
        display: block;
        color: #18375d;
        font-size: 12px;
        font-weight: 850;
    }

    .label-visible-copy small {
        display: block;
        margin-top: 2px;
        color: #7890a5;
        font-size: 10px;
    }

    .label-editor-switch {
        width: 2.6rem !important;
        height: 1.35rem;
        margin: 0 !important;
        cursor: pointer;
        box-shadow: none !important;
    }

    .label-editor-switch:checked {
        border-color: #0f9f78;
        background-color: #0f9f78;
    }

    .label-size-output {
        padding: 4px 7px;
        border-radius: 8px;
        color: #176b78;
        background: #ddf8f2;
        font-size: 11px;
        font-weight: 900;
    }

    .label-size-range {
        width: 100%;
        accent-color: #168f85;
    }

    .label-position-grid {
        display: grid;
        grid-template-columns: repeat(3, 44px);
        justify-content: center;
        gap: 7px;
        margin: 10px 0 13px;
    }

    .label-position-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 42px;
        border: 1px solid #c5d8e8;
        border-radius: 12px;
        color: #245777;
        background: #fff;
        font-size: 18px;
        cursor: pointer;
    }

    .label-position-btn:hover {
        color: #fff;
        border-color: #2b82cc;
        background: #2b82cc;
    }

    .label-position-btn.is-center {
        color: #0f766e;
        background: #e4f8f4;
    }

    .label-position-values {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 9px;
    }

    .label-editor-number {
        padding: 8px 9px;
        text-align: center;
    }

    .label-editor-actions {
        display: grid;
        grid-template-columns: .75fr 1.25fr;
        gap: 9px;
        margin-top: 21px;
    }

    .label-reset-btn,
    .label-save-btn {
        min-height: 46px;
        border: 0;
        border-radius: 13px;
        font-size: 12px;
        font-weight: 850;
        cursor: pointer;
    }

    .label-reset-btn {
        color: #475569;
        background: #e7edf3;
    }

    .label-save-btn {
        color: #fff;
        background: linear-gradient(135deg, #0f9f78, #287bc2);
        box-shadow: 0 11px 22px rgba(40, 123, 194, .2);
    }

    .label-save-btn:disabled,
    .label-reset-btn:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    .label-editor-status {
        min-height: 32px;
        margin-top: 12px;
        padding: 8px 10px;
        border-radius: 10px;
        color: #56718b;
        background: #eaf2f8;
        font-size: 10px;
        font-weight: 750;
        line-height: 1.4;
    }

    .label-editor-status.is-dirty {
        color: #8a5a00;
        background: #fff3ce;
    }

    .label-editor-status.is-success {
        color: #087056;
        background: #dcf8ef;
    }

    .label-editor-status.is-error {
        color: #a72d39;
        background: #ffe5e8;
    }

    .label-editor-marker-shell {
        overflow: visible !important;
        border: 0 !important;
        background: transparent !important;
    }

    .label-editor-map-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        max-width: 170px;
        min-height: 28px;
        padding: 5px 10px 5px 6px;
        border: 1px solid rgba(101, 222, 255, .7);
        border-radius: 999px;
        color: #f4fbff;
        background: linear-gradient(145deg, rgba(17, 52, 87, .97), rgba(5, 25, 42, .96));
        box-shadow: 0 8px 18px rgba(0, 18, 36, .25);
        font-size: 12px;
        font-weight: 850;
        line-height: 1;
        white-space: nowrap;
        transform: translate(-50%, -100%) scale(var(--label-editor-scale, 1));
        transform-origin: center bottom;
        cursor: grab;
        user-select: none;
    }

    .label-editor-map-badge.is-selected {
        border-color: #63f2c6;
        box-shadow:
            0 0 0 3px rgba(99, 242, 198, .2),
            0 10px 22px rgba(0, 18, 36, .28);
    }

    .label-editor-map-badge.is-hidden-preview {
        opacity: .55;
        border-style: dashed;
    }

    .label-editor-map-badge svg {
        width: 18px;
        height: 18px;
        flex: 0 0 18px;
        fill: none;
        stroke: #6de7ff;
        stroke-width: 1.55;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .label-editor-map-badge span {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    @media (max-width: 1050px) {
        .label-editor-layout {
            grid-template-columns: 1fr;
        }

        .label-editor-panel {
            width: min(100%, 520px);
            max-height: none;
            justify-self: center;
        }
    }

    @media (max-width: 620px) {
        .label-editor-page {
            min-height: calc(100svh - 70px);
            margin-inline: -12px;
            padding: 0;
        }

        .label-editor-header {
            position: relative;
            z-index: 4;
            flex-direction: row;
            align-items: center;
            margin: 0;
            padding: 12px 14px;
            border-radius: 0;
            box-shadow: none;
        }

        .label-editor-kicker,
        .label-editor-header p {
            display: none;
        }

        .label-editor-header h3 {
            font-size: 17px;
        }

        .label-editor-back {
            width: 42px;
            min-width: 42px;
            min-height: 42px;
            padding: 0;
            border-radius: 999px;
            font-size: 0;
        }

        .label-editor-back i {
            font-size: 19px;
        }

        .label-editor-layout {
            display: flex;
            flex-direction: column;
            width: 100%;
            height: calc(100svh - 132px);
            min-height: 610px;
            gap: 0;
            padding: 0;
            overflow: hidden;
            border: 0;
            border-radius: 0;
            background: #fff;
            box-shadow: none;
        }

        .label-editor-device-stage {
            display: block;
            flex: 0 0 43%;
            width: 100%;
            min-height: 260px;
            overflow: hidden;
        }

        .label-editor-device-toolbar {
            display: none;
        }

        .label-editor-phone {
            width: 100%;
            height: 100%;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .label-editor-map-wrap {
            width: 100% !important;
            height: 100% !important;
            min-height: 260px;
            border-radius: 0;
        }

        #building-label-editor-map {
            height: 100%;
            min-height: 0;
        }

        .label-editor-map-help {
            top: 9px;
            max-width: calc(100% - 90px);
            padding: 7px 10px;
            font-size: 8px;
            line-height: 1.25;
        }

        .label-editor-panel {
            flex: 1 1 auto;
            width: 100%;
            max-height: none;
            min-height: 0;
            margin-top: -20px;
            padding: 8px 15px calc(18px + env(safe-area-inset-bottom, 0px));
            overflow-y: auto;
            overscroll-behavior: contain;
            border: 0;
            border-top: 1px solid #bcd4e6;
            border-radius: 23px 23px 0 0;
            box-shadow: 0 -12px 30px rgba(15, 45, 70, .16);
            -webkit-overflow-scrolling: touch;
        }

        .label-editor-sheet-handle {
            display: block;
            width: 44px;
            height: 5px;
            margin: 1px auto 10px;
            border-radius: 999px;
            background: #b7c9d8;
        }

        .label-editor-panel-title {
            margin-bottom: 12px;
            text-align: center;
        }

        .label-editor-panel-title strong {
            font-size: 14px;
        }

        .label-editor-panel-title span {
            font-size: 10px;
        }

        .label-editor-field {
            margin-bottom: 13px;
        }

        .label-editor-input,
        .label-editor-select,
        .label-editor-number {
            min-height: 48px;
            font-size: 14px;
        }

        .label-visible-row {
            min-height: 54px;
            padding: 9px 11px;
        }

        .label-editor-switch {
            width: 3rem !important;
            height: 1.55rem;
        }

        .label-size-range {
            min-height: 34px;
        }

        .label-position-grid {
            grid-template-columns: repeat(3, 50px);
            gap: 9px;
            margin-block: 8px 12px;
        }

        .label-position-btn {
            width: 50px;
            height: 48px;
            font-size: 20px;
            touch-action: manipulation;
        }

        .label-editor-actions {
            position: sticky;
            z-index: 5;
            bottom: calc(-18px - env(safe-area-inset-bottom, 0px));
            margin: 16px -15px 0;
            padding: 10px 15px calc(10px + env(safe-area-inset-bottom, 0px));
            border-top: 1px solid rgba(190, 211, 227, .88);
            background: rgba(247, 251, 254, .97);
            box-shadow: 0 -8px 20px rgba(22, 55, 81, .08);
        }

        .label-reset-btn,
        .label-save-btn {
            min-height: 50px;
            touch-action: manipulation;
        }

        .label-editor-status {
            margin-bottom: 4px;
        }
    }
</style>

<div class="label-editor-page">
    <header class="label-editor-header">
        <div>
            <span class="label-editor-kicker">Buildings · Visual Configuration</span>
            <h3>Building Label Editor</h3>
            <p>Select a building, drag its label to a clear position, adjust the size or text, and preview every visible campus label before saving.</p>
        </div>
        <a href="{{ route('admin.buildings') }}" class="label-editor-back">
            <i class="ri-arrow-left-line"></i>
            Back to Buildings
        </a>
    </header>

    <div class="label-editor-layout">
        <section class="label-editor-device-stage" aria-label="Mobile building label preview">
            <div class="label-editor-device-toolbar">
                <label class="label-editor-device-field" for="label-editor-device-size">
                    <span>Mobile viewport</span>
                    <select id="label-editor-device-size" class="label-editor-device-select">
                        <option value="360x800">360 × 800 — compact Android</option>
                        <option value="390x844" selected>390 × 844 — standard phone</option>
                        <option value="412x915">412 × 915 — large Android</option>
                    </select>
                </label>
                <span class="label-editor-device-status" id="label-editor-device-status" aria-live="polite">
                    390 × 844
                </span>
            </div>

            <div class="label-editor-phone">
                <div class="label-editor-map-wrap">
                    <div class="label-editor-map-help">
                        <i class="ri-drag-move-2-line"></i>
                        Select a building, then drag its label or use the arrow controls
                    </div>
                    <div id="building-label-editor-map" aria-label="Interactive building label editor map"></div>
                </div>
            </div>
        </section>

        <aside class="label-editor-panel">
            <span class="label-editor-sheet-handle" aria-hidden="true"></span>
            <div class="label-editor-panel-title">
                <strong>Label controls</strong>
                <span>Changes are previewed immediately and saved per building.</span>
            </div>

            <form id="building-label-editor-form">
                <div class="label-editor-field">
                    <label for="label-editor-building">Building</label>
                    <select id="label-editor-building" class="label-editor-select">
                        @foreach($buildings as $building)
                            <option value="{{ $building->id }}">{{ $building->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="label-editor-field">
                    <div class="label-visible-row">
                        <div class="label-visible-copy">
                            <strong>Show permanent label</strong>
                            <small>Visible on the public campus map</small>
                        </div>
                        <input
                            type="checkbox"
                            role="switch"
                            id="label-editor-visible"
                            class="form-check-input label-editor-switch"
                        >
                    </div>
                </div>

                <div class="label-editor-field">
                    <label for="label-editor-text">
                        Label text
                        <small id="label-editor-auto-text">Auto</small>
                    </label>
                    <input
                        type="text"
                        id="label-editor-text"
                        class="label-editor-input"
                        maxlength="80"
                        placeholder="Use shortened building name"
                    >
                </div>

                <div class="label-editor-field">
                    <label for="label-editor-min-zoom">Appears from</label>
                    <select id="label-editor-min-zoom" class="label-editor-select">
                        <option value="17">Wide view · always visible</option>
                        <option value="18">Medium zoom · secondary</option>
                        <option value="19">Close zoom · detailed</option>
                    </select>
                    <small class="label-editor-field-help">
                        Important labels can stay visible when zoomed out; secondary labels appear as the user zooms in.
                    </small>
                </div>

                <div class="label-editor-field">
                    <div class="label-editor-field-label">
                        <span>Label size</span>
                        <output id="label-editor-size-output" class="label-size-output">100%</output>
                    </div>
                    <input
                        type="range"
                        id="label-editor-size"
                        class="label-size-range"
                        min="0.65"
                        max="1.6"
                        step="0.05"
                        value="1"
                    >
                </div>

                <div class="label-editor-field">
                    <div class="label-editor-field-label">
                        <span>Position</span>
                        <small>4 px per tap</small>
                    </div>

                    <div class="label-position-grid" aria-label="Nudge label position">
                        <span></span>
                        <button type="button" class="label-position-btn" data-nudge-x="0" data-nudge-y="-4" aria-label="Move label up">
                            <i class="ri-arrow-up-line"></i>
                        </button>
                        <span></span>
                        <button type="button" class="label-position-btn" data-nudge-x="-4" data-nudge-y="0" aria-label="Move label left">
                            <i class="ri-arrow-left-line"></i>
                        </button>
                        <button type="button" class="label-position-btn is-center" id="label-editor-center" aria-label="Reset label position">
                            <i class="ri-focus-3-line"></i>
                        </button>
                        <button type="button" class="label-position-btn" data-nudge-x="4" data-nudge-y="0" aria-label="Move label right">
                            <i class="ri-arrow-right-line"></i>
                        </button>
                        <span></span>
                        <button type="button" class="label-position-btn" data-nudge-x="0" data-nudge-y="4" aria-label="Move label down">
                            <i class="ri-arrow-down-line"></i>
                        </button>
                        <span></span>
                    </div>

                    <div class="label-position-values">
                        <div>
                            <label for="label-editor-offset-x" class="label-editor-field-label">Horizontal</label>
                            <input type="number" id="label-editor-offset-x" class="label-editor-number" min="-120" max="120" value="0">
                        </div>
                        <div>
                            <label for="label-editor-offset-y" class="label-editor-field-label">Vertical</label>
                            <input type="number" id="label-editor-offset-y" class="label-editor-number" min="-120" max="120" value="0">
                        </div>
                    </div>
                </div>

                <div class="label-editor-actions">
                    <button type="button" id="label-editor-reset" class="label-reset-btn">
                        <i class="ri-restart-line"></i> Reset
                    </button>
                    <button type="submit" id="label-editor-save" class="label-save-btn">
                        <i class="ri-save-3-line"></i> Save label
                    </button>
                </div>

                <div id="label-editor-status" class="label-editor-status" role="status" aria-live="polite">
                    Select a building or its label on the map to begin.
                </div>
            </form>
        </aside>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const buildings = @json($buildings->values());
    const updateUrlTemplate = @json(route('admin.buildings.updateLabelLayout', ['building' => '__BUILDING__']));
    const csrfToken = @json(csrf_token());
    const mapElement = document.getElementById('building-label-editor-map');
    const mapWrap = mapElement?.closest('.label-editor-map-wrap');
    const deviceSizeSelect = document.getElementById('label-editor-device-size');
    const deviceStatus = document.getElementById('label-editor-device-status');
    const form = document.getElementById('building-label-editor-form');
    const buildingSelect = document.getElementById('label-editor-building');
    const visibleInput = document.getElementById('label-editor-visible');
    const textInput = document.getElementById('label-editor-text');
    const autoText = document.getElementById('label-editor-auto-text');
    const minZoomInput = document.getElementById('label-editor-min-zoom');
    const sizeInput = document.getElementById('label-editor-size');
    const sizeOutput = document.getElementById('label-editor-size-output');
    const offsetXInput = document.getElementById('label-editor-offset-x');
    const offsetYInput = document.getElementById('label-editor-offset-y');
    const centerButton = document.getElementById('label-editor-center');
    const resetButton = document.getElementById('label-editor-reset');
    const saveButton = document.getElementById('label-editor-save');
    const status = document.getElementById('label-editor-status');
    const buildingLayers = new Map();
    const labelMarkers = new Map();
    const labelAnchors = new Map();
    const dirtyBuildingIds = new Set();
    let selectedBuildingId = Number(buildings[0]?.id || 0);

    if (!mapElement || !form || typeof L === 'undefined' || !buildings.length) {
        if (status) {
            status.textContent = buildings.length
                ? 'The map library could not be loaded.'
                : 'No buildings are available to edit.';
            status.classList.add('is-error');
        }
        return;
    }

    function clamp(value, minimum, maximum, fallback = 0) {
        const number = Number(value);
        return Math.max(minimum, Math.min(maximum, Number.isFinite(number) ? number : fallback));
    }

    function escapeHtml(value = '') {
        return String(value).replace(/[&<>'"]/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        })[character]);
    }

    function buildingById(id) {
        return buildings.find(building => Number(building.id) === Number(id)) || null;
    }

    function automaticLabel(building) {
        const fullName = String(building?.name || 'Building').trim();
        return fullName.replace(/\s+building\s*$/i, '').trim() || fullName;
    }

    function displayLabel(building) {
        return String(building?.map_label_text || '').trim() || automaticLabel(building);
    }

    function normalizeBuilding(building) {
        building.show_map_label = Boolean(building.show_map_label);
        building.map_label_text = String(building.map_label_text || '').trim();
        building.map_label_scale = clamp(building.map_label_scale, .65, 1.6, 1);
        building.map_label_offset_x = Math.round(clamp(building.map_label_offset_x, -120, 120, 0));
        building.map_label_offset_y = Math.round(clamp(building.map_label_offset_y, -120, 120, 0));
        building.map_label_min_zoom = Math.round(clamp(building.map_label_min_zoom, 17, 19, 18));
        return building;
    }

    buildings.forEach(normalizeBuilding);

    const map = L.map(mapElement, {
        zoomControl: true,
        preferCanvas: true,
        minZoom: 17,
        maxZoom: 20,
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_nolabels/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
        maxZoom: 20,
    }).addTo(map);

    function createBadgeHtml(building, selected = false) {
        const belowMinimumZoom = map.getZoom() < building.map_label_min_zoom;
        const classes = [
            'label-editor-map-badge',
            selected ? 'is-selected' : '',
            (!building.show_map_label || belowMinimumZoom) ? 'is-hidden-preview' : ''
        ].filter(Boolean).join(' ');

        return `
            <span class="${classes}" style="--label-editor-scale:${building.map_label_scale.toFixed(2)}">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M5 20V7l7-3 7 3v13M3 20h18M12 4v16" />
                    <path d="M8 9h2m4 0h2m-8 4h2m4 0h2m-8 4h2m4 0h2" />
                </svg>
                <span>${escapeHtml(displayLabel(building))}</span>
            </span>
        `;
    }

    function markerLatLng(building) {
        const anchor = labelAnchors.get(Number(building.id));
        if (!anchor) return null;

        const anchorPoint = map.latLngToContainerPoint(anchor);
        return map.containerPointToLatLng(L.point(
            anchorPoint.x + building.map_label_offset_x,
            anchorPoint.y + building.map_label_offset_y - 7
        ));
    }

    function renderLabelMarker(building) {
        const buildingId = Number(building.id);
        const selected = buildingId === selectedBuildingId;
        const isVisibleAtZoom = map.getZoom() >= building.map_label_min_zoom;
        const shouldRender = (building.show_map_label && isVisibleAtZoom) || selected;
        let marker = labelMarkers.get(buildingId);

        if (!shouldRender) {
            if (marker) {
                map.removeLayer(marker);
                labelMarkers.delete(buildingId);
            }
            return;
        }

        const position = markerLatLng(building);
        if (!position) return;

        const icon = L.divIcon({
            className: 'label-editor-marker-shell',
            html: createBadgeHtml(building, selected),
            iconSize: [1, 1],
            iconAnchor: [0, 0],
        });

        if (!marker) {
            marker = L.marker(position, {
                icon,
                draggable: selected,
                keyboard: true,
                riseOnHover: true,
                zIndexOffset: selected ? 900 : 500,
            }).addTo(map);

            marker.on('click', () => selectBuilding(buildingId, false));
            marker.on('dragend', () => {
                const currentBuilding = buildingById(buildingId);
                const anchor = labelAnchors.get(buildingId);
                if (!currentBuilding || !anchor) return;

                const markerPoint = map.latLngToContainerPoint(marker.getLatLng());
                const anchorPoint = map.latLngToContainerPoint(anchor);
                currentBuilding.map_label_offset_x = Math.round(clamp(
                    markerPoint.x - anchorPoint.x,
                    -120,
                    120,
                    0
                ));
                currentBuilding.map_label_offset_y = Math.round(clamp(
                    markerPoint.y - anchorPoint.y + 7,
                    -120,
                    120,
                    0
                ));
                markDirty(currentBuilding);
                populateControls(currentBuilding);
                renderLabelMarker(currentBuilding);
            });

            labelMarkers.set(buildingId, marker);
        } else {
            marker.setLatLng(position);
            marker.setIcon(icon);
        }

        marker.setZIndexOffset(selected ? 900 : 500);
        if (selected) {
            marker.dragging?.enable();
        } else {
            marker.dragging?.disable();
        }
    }

    function renderAllLabels() {
        buildings.forEach(renderLabelMarker);
    }

    function setLayerSelection() {
        buildingLayers.forEach((layer, buildingId) => {
            const building = buildingById(buildingId);
            const selected = Number(buildingId) === selectedBuildingId;
            layer.setStyle({
                color: selected ? '#0d3b60' : '#2b638f',
                weight: selected ? 4 : 1.5,
                fillColor: selected ? '#70b8e9' : (building?.color || '#4f94d4'),
                fillOpacity: selected ? .96 : .8,
            });
        });
    }

    function setStatus(message, type = '') {
        status.textContent = message;
        status.classList.remove('is-dirty', 'is-success', 'is-error');
        if (type) status.classList.add(`is-${type}`);
    }

    function markDirty(building) {
        dirtyBuildingIds.add(Number(building.id));
        setStatus('Unsaved preview changes. Select Save label when ready.', 'dirty');
    }

    function populateControls(building) {
        if (!building) return;
        buildingSelect.value = String(building.id);
        visibleInput.checked = building.show_map_label;
        textInput.value = building.map_label_text;
        textInput.placeholder = automaticLabel(building);
        autoText.textContent = `Auto: ${automaticLabel(building)}`;
        sizeInput.value = String(building.map_label_scale);
        sizeOutput.value = `${Math.round(building.map_label_scale * 100)}%`;
        sizeOutput.textContent = sizeOutput.value;
        minZoomInput.value = String(building.map_label_min_zoom);
        offsetXInput.value = String(building.map_label_offset_x);
        offsetYInput.value = String(building.map_label_offset_y);
        saveButton.disabled = false;
        resetButton.disabled = false;
    }

    function selectBuilding(buildingId, focusMap = true) {
        const building = buildingById(buildingId);
        if (!building) return;

        selectedBuildingId = Number(building.id);
        populateControls(building);
        setLayerSelection();
        renderAllLabels();

        if (dirtyBuildingIds.has(selectedBuildingId)) {
            setStatus('This building has unsaved preview changes.', 'dirty');
        } else {
            setStatus('Drag the selected label or adjust the controls, then save.');
        }

        if (focusMap) {
            const layer = buildingLayers.get(selectedBuildingId);
            if (layer) map.panTo(layer.getBounds().getCenter());
        }
    }

    const polygonLayers = [];
    buildings.forEach(building => {
        if (!building.geometry) return;

        const layer = L.geoJSON(
            { type: 'Feature', geometry: building.geometry },
            {
                style: {
                    color: '#2b638f',
                    weight: 1.5,
                    fillColor: building.color || '#4f94d4',
                    fillOpacity: .8,
                }
            }
        ).addTo(map);

        labelAnchors.set(Number(building.id), layer.getBounds().getCenter());
        layer.bindTooltip(building.name || 'Building', { sticky: true });
        layer.on('click', () => selectBuilding(building.id, false));
        buildingLayers.set(Number(building.id), layer);
        polygonLayers.push(layer);
    });

    const campusBounds = polygonLayers.length
        ? L.featureGroup(polygonLayers).getBounds()
        : null;

    function fitCampusToMobileViewport() {
        if (!campusBounds) return;
        map.fitBounds(campusBounds, {
            padding: [45, 45],
            maxZoom: 18.5,
        });
    }

    function applyMobileViewport() {
        if (!deviceSizeSelect || !mapWrap) return;

        const [width, height] = deviceSizeSelect.value.split('x').map(Number);
        if (!Number.isFinite(width) || !Number.isFinite(height)) return;

        mapWrap.style.width = `${width}px`;
        mapWrap.style.height = `${height}px`;
        if (deviceStatus) deviceStatus.textContent = `${width} × ${height}`;

        window.requestAnimationFrame(() => {
            map.invalidateSize({ pan: false });
            fitCampusToMobileViewport();
            renderAllLabels();
            updateDeviceStatus();
        });
    }

    function updateDeviceStatus() {
        if (!deviceSizeSelect || !deviceStatus) return;
        const [width, height] = deviceSizeSelect.value.split('x');
        deviceStatus.textContent = `${width} × ${height} · Zoom ${map.getZoom().toFixed(1)}`;
    }

    fitCampusToMobileViewport();

    map.on('zoomend resize', () => {
        renderAllLabels();
        updateDeviceStatus();
    });
    deviceSizeSelect?.addEventListener('change', applyMobileViewport);

    buildingSelect.addEventListener('change', () => selectBuilding(buildingSelect.value));

    visibleInput.addEventListener('change', () => {
        const building = buildingById(selectedBuildingId);
        if (!building) return;
        building.show_map_label = visibleInput.checked;
        markDirty(building);
        renderAllLabels();
    });

    textInput.addEventListener('input', () => {
        const building = buildingById(selectedBuildingId);
        if (!building) return;
        building.map_label_text = textInput.value.slice(0, 80);
        markDirty(building);
        renderLabelMarker(building);
    });

    minZoomInput.addEventListener('change', () => {
        const building = buildingById(selectedBuildingId);
        if (!building) return;
        building.map_label_min_zoom = Math.round(clamp(minZoomInput.value, 17, 19, 18));
        markDirty(building);
        renderAllLabels();
    });

    sizeInput.addEventListener('input', () => {
        const building = buildingById(selectedBuildingId);
        if (!building) return;
        building.map_label_scale = clamp(sizeInput.value, .65, 1.6, 1);
        sizeOutput.value = `${Math.round(building.map_label_scale * 100)}%`;
        sizeOutput.textContent = sizeOutput.value;
        markDirty(building);
        renderLabelMarker(building);
    });

    function applyOffsetInputs() {
        const building = buildingById(selectedBuildingId);
        if (!building) return;
        building.map_label_offset_x = Math.round(clamp(offsetXInput.value, -120, 120, 0));
        building.map_label_offset_y = Math.round(clamp(offsetYInput.value, -120, 120, 0));
        offsetXInput.value = String(building.map_label_offset_x);
        offsetYInput.value = String(building.map_label_offset_y);
        markDirty(building);
        renderLabelMarker(building);
    }

    offsetXInput.addEventListener('change', applyOffsetInputs);
    offsetYInput.addEventListener('change', applyOffsetInputs);

    document.querySelectorAll('[data-nudge-x][data-nudge-y]').forEach(button => {
        button.addEventListener('click', () => {
            const building = buildingById(selectedBuildingId);
            if (!building) return;
            building.map_label_offset_x = Math.round(clamp(
                building.map_label_offset_x + Number(button.dataset.nudgeX),
                -120,
                120,
                0
            ));
            building.map_label_offset_y = Math.round(clamp(
                building.map_label_offset_y + Number(button.dataset.nudgeY),
                -120,
                120,
                0
            ));
            populateControls(building);
            markDirty(building);
            renderLabelMarker(building);
        });
    });

    centerButton.addEventListener('click', () => {
        const building = buildingById(selectedBuildingId);
        if (!building) return;
        building.map_label_offset_x = 0;
        building.map_label_offset_y = 0;
        populateControls(building);
        markDirty(building);
        renderLabelMarker(building);
    });

    resetButton.addEventListener('click', () => {
        const building = buildingById(selectedBuildingId);
        if (!building) return;
        building.map_label_text = '';
        building.map_label_scale = 1;
        building.map_label_offset_x = 0;
        building.map_label_offset_y = 0;
        building.map_label_min_zoom = 18;
        populateControls(building);
        markDirty(building);
        renderLabelMarker(building);
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const building = buildingById(selectedBuildingId);
        if (!building) return;

        saveButton.disabled = true;
        resetButton.disabled = true;
        setStatus('Saving label configuration…');

        try {
            const response = await fetch(
                updateUrlTemplate.replace('__BUILDING__', encodeURIComponent(building.id)),
                {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        show_map_label: building.show_map_label,
                        map_label_text: building.map_label_text || null,
                        map_label_scale: building.map_label_scale,
                        map_label_offset_x: building.map_label_offset_x,
                        map_label_offset_y: building.map_label_offset_y,
                        map_label_min_zoom: building.map_label_min_zoom,
                    }),
                }
            );
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'The label could not be saved.');
            }

            Object.assign(building, data.label || {});
            normalizeBuilding(building);
            dirtyBuildingIds.delete(Number(building.id));
            populateControls(building);
            renderLabelMarker(building);
            setStatus('Saved. The public map will use this label layout after refresh.', 'success');
        } catch (error) {
            setStatus(error.message || 'The label could not be saved.', 'error');
        } finally {
            saveButton.disabled = false;
            resetButton.disabled = false;
        }
    });

    applyMobileViewport();
    selectBuilding(selectedBuildingId, false);
});
</script>

@endsection
