<style>
    :root {
        --step: 1px;
        --panel-bg: rgba(255, 255, 255, 0.92);
        --panel-border: rgba(255, 255, 255, 0.65);
        --text-dark: #0f172a;
        --text-mid: #475569;
        --text-soft: #64748b;
        --green: #16a34a;
        --blue: #2563eb;
        --violet: #7c3aed;
        --danger: #dc2626;
        --warning: #ca8a04;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: #f8fafc;
    }

    #map {
        width: 100%;
        height: 100vh;
        opacity: 0;
        transition: opacity 0.8s ease;
    }

    #route-panel {
        position: absolute;
        top: 18px;
        left: 18px;
        z-index: 9999;
        width: 410px;
        pointer-events: none;
    }

    .route-card {
        pointer-events: auto;
        background: var(--panel-bg);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1px solid var(--panel-border);
        border-radius: 22px;
        padding: 18px;
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.12);
    }

    .route-title {
        font-size: 19px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 4px;
    }

    .route-subtitle {
        font-size: 12px;
        line-height: 1.6;
        color: var(--text-soft);
        margin-bottom: 14px;
    }

    .route-field {
        margin-bottom: 12px;
    }

    .route-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-mid);
        margin-bottom: 6px;
    }

    .route-select,
    .route-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        padding: 12px 14px;
        font-size: 13px;
        background: white;
        outline: none;
        font-family: inherit;
    }

    .route-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .route-btn {
        border: none;
        border-radius: 14px;
        padding: 11px 14px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        transition: 0.2s ease;
        font-family: inherit;
    }

    .route-btn:hover {
        transform: translateY(-1px);
    }

    .route-btn.primary {
        background: var(--green);
        color: white;
    }

    .route-btn.neutral {
        background: #e2e8f0;
        color: var(--text-dark);
    }

    .route-btn.success {
        background: var(--blue);
        color: white;
    }

    .route-btn.gps {
        background: var(--violet);
        color: white;
    }

    .route-btn.full {
        width: 100%;
    }

    .route-status {
        margin-top: 12px;
        background: rgba(248, 250, 252, 0.95);
        border-radius: 14px;
        padding: 12px 14px;
        font-size: 12px;
        color: var(--text-mid);
        line-height: 1.8;
    }

    .mt-8 {
        margin-top: 8px;
    }

    .route-start-arrow {
        width: 18px;
        height: 18px;
        background: var(--green);
        clip-path: polygon(50% 0%, 100% 100%, 50% 78%, 0% 100%);
        border: 2px solid white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.28);
        transform: rotate(180deg);
    }

    .route-destination-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--danger);
        border: 2px solid white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.28);
    }

    .route-gps-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--violet);
        border: 2px solid white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.28);
    }

    .route-line-safe {
        filter: drop-shadow(0 0 6px rgba(34, 197, 94, 0.35));
    }

    .route-line-caution {
        filter: drop-shadow(0 0 6px rgba(250, 204, 21, 0.40));
    }

    .route-line-danger {
        filter: drop-shadow(0 0 8px rgba(220, 38, 38, 0.40));
    }

    .fake-3d-building {
        transition: transform 0.2s ease, filter 0.2s ease;
        cursor: pointer;
    }

    .leaflet-popup-content-wrapper {
        background: rgba(255, 255, 255, 0.92) !important;
        backdrop-filter: blur(12px) !important;
        border-radius: 16px !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.6);
        padding: 5px;
    }

    .leaflet-popup-tip {
        background: rgba(255, 255, 255, 0.92) !important;
    }

    .leaflet-popup-content {
        margin: 10px 15px !important;
        text-align: center;
    }

    .custom-popup-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 5px 0;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 6px;
    }

    .custom-popup-subtitle {
        font-size: 12px;
        color: #64748b;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .path-interactive {
        transition: stroke-width 0.2s ease, stroke 0.2s;
        filter: drop-shadow(0px 2px 4px rgba(0, 0, 0, 0.15));
    }

    .path-covered-stairs {
        filter: drop-shadow(4px 6px 5px rgba(0, 0, 0, 0.4)) drop-shadow(0px -1px 2px rgba(255, 255, 255, 0.3));
        stroke-linecap: butt;
    }

    .path-canopy-frames {
        pointer-events: none;
        filter: drop-shadow(0px 1px 1px rgba(0, 0, 0, 0.6));
        stroke-linecap: butt;
    }

    .premium-legend {
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 16px;
        padding: 16px 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        min-width: 170px;
    }

    .legend-title {
        font-size: 11px;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 12px;
        display: block;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
    }

    .legend-line {
        width: 24px;
        height: 4px;
        border-radius: 4px;
    }

    .indoor-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.28);
        z-index: 9998;
        display: none;
    }

    .indoor-backdrop.active {
        display: block;
    }

    .indoor-panel {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10000;
        width: min(1100px, 92vw);
        height: min(760px, 88vh);
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(16px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
        overflow: hidden;
        display: none;
    }

    .indoor-panel.active {
        display: block;
    }

    .indoor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 18px;
        border-bottom: 1px solid #e5e7eb;
        background: rgba(248, 250, 252, 0.75);
    }

    .indoor-title {
        font-size: 18px;
        font-weight: 800;
        color: var(--text-dark);
    }

    .indoor-subtitle {
        font-size: 12px;
        color: var(--text-soft);
        margin-top: 3px;
    }

    .indoor-close {
        border: none;
        background: #e2e8f0;
        color: var(--text-dark);
        border-radius: 12px;
        padding: 10px 12px;
        cursor: pointer;
        font-weight: 800;
    }

    .indoor-toolbar {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        padding: 14px 18px;
        border-bottom: 1px solid #e5e7eb;
    }

    .indoor-toolbar select,
    .indoor-toolbar input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        padding: 12px 14px;
        font-size: 13px;
        font-family: inherit;
        background: white;
        outline: none;
    }

    .indoor-body {
        display: grid;
        grid-template-columns: 280px 1fr;
        height: calc(100% - 126px);
    }

    .indoor-sidebar {
        border-right: 1px solid #e5e7eb;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .indoor-sidebar-title {
        padding: 14px 14px 10px;
        font-size: 13px;
        font-weight: 800;
        color: var(--text-dark);
    }

    .room-list {
        padding: 0 12px 12px;
        overflow: auto;
    }

    .room-item {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 12px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .room-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.06);
        border-color: #93c5fd;
    }

    .room-item.active {
        border-color: #2563eb;
        background: #eff6ff;
    }

    .room-name {
        font-size: 13px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 4px;
    }

    .room-meta {
        font-size: 11px;
        color: var(--text-soft);
    }

    .indoor-main {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .indoor-map-wrap {
        position: relative;
        flex: 1;
        min-height: 0;
        background: #f8fafc;
    }

    #indoorMap {
        width: 100%;
        height: 100%;
        background: #f8fafc;
    }

    .indoor-footer {
        border-top: 1px solid #e5e7eb;
        padding: 12px 16px;
        background: white;
        font-size: 12px;
        color: var(--text-mid);
        line-height: 1.7;
    }

    .indoor-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 800;
        margin-right: 6px;
    }

    .badge-blue {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .badge-green {
        background: #dcfce7;
        color: #15803d;
    }

    .badge-yellow {
        background: #fef3c7;
        color: #a16207;
    }

    .loading-overlay {
        position: absolute;
        inset: 0;
        z-index: 12000;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(248, 250, 252, 0.76);
        backdrop-filter: blur(6px);
        font-weight: 800;
        color: #0f172a;
        letter-spacing: 0.02em;
    }

    @media (max-width: 1200px) {
        .indoor-panel {
            width: min(980px, 94vw);
        }

        .indoor-body {
            grid-template-columns: 240px 1fr;
        }
    }

    @media (max-width: 768px) {
        #route-panel {
            top: 12px;
            left: 12px;
            right: 12px;
            width: auto;
        }

        .indoor-panel {
            width: 96vw;
            height: 92vh;
        }

        .indoor-body {
            grid-template-columns: 1fr;
            grid-template-rows: 220px 1fr;
            height: calc(100% - 126px);
        }

        .indoor-sidebar {
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
        }

        .indoor-toolbar {
            grid-template-columns: 1fr;
        }
    }

    .landuse-label {
        display: inline-block;
        background: rgba(255, 255, 255, 0.94);
        color: #166534;
        font-size: 11px;
        font-weight: 800;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid rgba(22, 101, 52, 0.18);
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.10);
        white-space: nowrap;
        backdrop-filter: blur(8px);
    }

    .landuse-popup-image {
        margin-top: 10px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid #dbe4ee;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.10);
        max-width: 100%;
    }


    .route-landuse-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #16a34a;
        border: 2px solid white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.28);
    }

    .landuse-label {
        display: inline-block;
        background: rgba(255, 255, 255, 0.94);
        color: #166534;
        font-size: 11px;
        font-weight: 800;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid rgba(22, 101, 52, 0.18);
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.10);
        white-space: nowrap;
        backdrop-filter: blur(8px);
    }
</style>

<style>
    :root {
        --step: 1px;
        --panel-bg: rgba(255, 255, 255, 0.92);
        --panel-border: rgba(255, 255, 255, 0.65);
        --text-dark: #0f172a;
        --text-mid: #475569;
        --text-soft: #64748b;
        --green: #16a34a;
        --blue: #2563eb;
        --violet: #7c3aed;
        --danger: #dc2626;
        --warning: #ca8a04;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: #f8fafc;
    }

    #map {
        width: 100%;
        height: 100vh;
        opacity: 0;
        transition: opacity 0.8s ease;
    }

    #route-panel {
        position: absolute;
        top: 18px;
        left: 18px;
        z-index: 9999;
        width: 410px;
        pointer-events: none;
    }

    .route-card {
        pointer-events: auto;
        background: var(--panel-bg);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1px solid var(--panel-border);
        border-radius: 22px;
        padding: 18px;
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.12);
    }

    .route-title {
        font-size: 19px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 4px;
    }

    .route-subtitle {
        font-size: 12px;
        line-height: 1.6;
        color: var(--text-soft);
        margin-bottom: 14px;
    }

    .route-field {
        margin-bottom: 12px;
    }

    .route-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-mid);
        margin-bottom: 6px;
    }

    .route-select,
    .route-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        padding: 12px 14px;
        font-size: 13px;
        background: white;
        outline: none;
        font-family: inherit;
    }

    .route-search-wrap {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 8px;
        align-items: stretch;
    }

    .route-help-text {
        margin-top: 6px;
        font-size: 11px;
        color: var(--text-soft);
        line-height: 1.5;
    }

    .route-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .route-btn {
        border: none;
        border-radius: 14px;
        padding: 11px 14px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        transition: 0.2s ease;
        font-family: inherit;
    }

    .route-btn:hover {
        transform: translateY(-1px);
    }

    .route-btn.primary {
        background: var(--green);
        color: white;
    }

    .route-btn.neutral {
        background: #e2e8f0;
        color: var(--text-dark);
    }

    .route-btn.success {
        background: var(--blue);
        color: white;
    }

    .route-btn.gps {
        background: var(--violet);
        color: white;
    }

    .route-btn.full {
        width: 100%;
    }

    .route-status {
        margin-top: 12px;
        background: rgba(248, 250, 252, 0.95);
        border-radius: 14px;
        padding: 12px 14px;
        font-size: 12px;
        color: var(--text-mid);
        line-height: 1.8;
    }

    .mt-8 {
        margin-top: 8px;
    }

    .route-start-arrow {
        width: 22px;
        height: 22px;
        background: var(--green);
        clip-path: polygon(50% 0%, 100% 100%, 50% 78%, 0% 100%);
        border: 2px solid white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.28);
        transform: rotate(180deg);
    }

    .route-destination-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--danger);
        border: 2px solid white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.28);
    }

    .route-gps-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--violet);
        border: 2px solid white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.28);
    }

    .route-line-safe {
        filter: drop-shadow(0 0 6px rgba(34, 197, 94, 0.35));
    }

    .route-line-caution {
        filter: drop-shadow(0 0 6px rgba(250, 204, 21, 0.40));
    }

    .route-line-danger {
        filter: drop-shadow(0 0 8px rgba(220, 38, 38, 0.40));
    }

    .fake-3d-building {
        transition: transform 0.2s ease, filter 0.2s ease;
        cursor: pointer;
    }

    .leaflet-popup-content-wrapper {
        background: rgba(255, 255, 255, 0.92) !important;
        backdrop-filter: blur(12px) !important;
        border-radius: 16px !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.6);
        padding: 5px;
    }

    .leaflet-popup-tip {
        background: rgba(255, 255, 255, 0.92) !important;
    }

    .leaflet-popup-content {
        margin: 10px 15px !important;
        text-align: center;
    }

    .custom-popup-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 5px 0;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 6px;
    }

    .custom-popup-subtitle {
        font-size: 12px;
        color: #64748b;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .path-interactive {
        transition: stroke-width 0.2s ease, stroke 0.2s;
        filter: drop-shadow(0px 2px 4px rgba(0, 0, 0, 0.15));
    }

    .path-covered-stairs {
        filter: drop-shadow(4px 6px 5px rgba(0, 0, 0, 0.4)) drop-shadow(0px -1px 2px rgba(255, 255, 255, 0.3));
        stroke-linecap: butt;
    }

    .path-canopy-frames {
        pointer-events: none;
        filter: drop-shadow(0px 1px 1px rgba(0, 0, 0, 0.6));
        stroke-linecap: butt;
    }

    .premium-legend {
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 16px;
        padding: 16px 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        min-width: 170px;
    }

    .legend-title {
        font-size: 11px;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 12px;
        display: block;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
    }

    .legend-line {
        width: 24px;
        height: 4px;
        border-radius: 4px;
    }

    .indoor-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.28);
        z-index: 9998;
        display: none;
    }

    .indoor-backdrop.active {
        display: block;
    }

    .indoor-panel {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10000;
        width: min(1100px, 92vw);
        height: min(760px, 88vh);
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(16px);
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.18);
        overflow: hidden;
        display: none;
    }

    .indoor-panel.active {
        display: block;
    }

    .indoor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 18px;
        border-bottom: 1px solid #e5e7eb;
        background: rgba(248, 250, 252, 0.75);
    }

    .indoor-title {
        font-size: 18px;
        font-weight: 800;
        color: var(--text-dark);
    }

    .indoor-subtitle {
        font-size: 12px;
        color: var(--text-soft);
        margin-top: 3px;
    }

    .indoor-close {
        border: none;
        background: #e2e8f0;
        color: var(--text-dark);
        border-radius: 12px;
        padding: 10px 12px;
        cursor: pointer;
        font-weight: 800;
    }

    .indoor-toolbar {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        padding: 14px 18px;
        border-bottom: 1px solid #e5e7eb;
    }

    .indoor-toolbar select,
    .indoor-toolbar input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        padding: 12px 14px;
        font-size: 13px;
        font-family: inherit;
        background: white;
        outline: none;
    }

    .indoor-body {
        display: grid;
        grid-template-columns: 280px 1fr;
        height: calc(100% - 126px);
    }

    .indoor-sidebar {
        border-right: 1px solid #e5e7eb;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .indoor-sidebar-title {
        padding: 14px 14px 10px;
        font-size: 13px;
        font-weight: 800;
        color: var(--text-dark);
    }

    .room-list {
        padding: 0 12px 12px;
        overflow: auto;
    }

    .room-item {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 12px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .room-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
    }

    .indoor-main {
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .indoor-map-wrap {
        position: relative;
        flex: 1;
        min-height: 0;
    }

    #indoorMap {
        width: 100%;
        height: 100%;
    }

    .loading-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.72);
        font-weight: 800;
        color: #334155;
        z-index: 999;
    }

    .indoor-footer {
        padding: 12px 14px;
        border-top: 1px solid #e5e7eb;
        font-size: 12px;
        color: var(--text-soft);
        background: #fff;
    }

    .indoor-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 999px;
        margin-right: 8px;
        font-size: 11px;
        font-weight: 800;
    }

    .badge-blue {
        background: #dbeafe;
        color: #1d4ed8;
    }

    @media (max-width: 768px) {
        #route-panel {
            width: calc(100% - 20px);
            left: 10px;
            top: 10px;
        }

        .route-search-wrap {
            grid-template-columns: 1fr;
        }

        .indoor-body {
            grid-template-columns: 1fr;
        }

        .indoor-sidebar {
            display: none;
        }
    }
</style>


<style>
    .route-search-wrap.voice-enabled {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 8px;
        align-items: stretch;
    }

    .route-btn.voice {
        background: #0f172a;
        color: white;
        min-width: 92px;
    }

    .route-btn.voice.listening {
        background: #dc2626;
        animation: voicePulse 1s infinite;
    }

    .route-voice-status {
        margin-top: 8px;
        font-size: 11px;
        font-weight: 700;
        color: #475569;
    }

    .route-heard-text {
        margin-top: 6px;
        font-size: 11px;
        color: #334155;
        background: rgba(248, 250, 252, 0.95);
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 10px;
        line-height: 1.5;
    }

    .route-heard-text span {
        font-weight: 800;
        color: #0f172a;
    }

    @keyframes voicePulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4);
        }

        70% {
            transform: scale(1.02);
            box-shadow: 0 0 0 10px rgba(220, 38, 38, 0);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 38, 38, 0);
        }
    }

    @media (max-width: 768px) {
        .route-search-wrap.voice-enabled {
            grid-template-columns: 1fr;
        }
    }


    /* ===============================
   CENTERED ROUTE CONTROL UI
================================ */

    .text-center {
        text-align: center;
    }

    .text-center-input,
    .text-center-select {
        text-align: center;
    }

    #route-panel {
        position: absolute;
        top: 22px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        width: min(430px, calc(100vw - 24px));
        pointer-events: none;
    }

    .route-card-centered {
        pointer-events: auto;
        text-align: center;
        padding: 18px;
    }

    .start-mode-bar {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin: 14px auto 18px;
        flex-wrap: nowrap;
    }

    .mode-pill {
        border: none;
        padding: 13px 14px;
        min-width: 118px;
        font-size: 13px;
        font-weight: 900;
        color: #ffffff;
        cursor: pointer;
        border-radius: 0;
        font-family: inherit;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.15);
        transition: 0.18s ease;
    }

    .mode-pill:hover {
        transform: translateY(-2px);
    }

    .mode-pill.pick {
        background: #00f044;
    }

    .mode-pill.gps {
        background: #4b00ff;
    }

    .mode-pill.default {
        background: #0349ff;
    }

    .mode-pill.active {
        outline: 4px solid rgba(15, 23, 42, 0.12);
        transform: translateY(-2px) scale(1.03);
    }

    .big-route-toggle {
        width: 176px;
        height: 176px;
        border-radius: 50%;
        border: none;
        background: #1298e8;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 8px auto 16px;
        cursor: pointer;
        box-shadow: 0 18px 34px rgba(18, 152, 232, 0.22);
        transition: 0.2s ease;
    }

    .big-route-toggle:hover {
        transform: translateY(-2px) scale(1.02);
    }

    .big-route-toggle.active {
        box-shadow:
            0 0 0 8px rgba(18, 152, 232, 0.18),
            0 18px 34px rgba(18, 152, 232, 0.28);
    }

    .big-route-pin {
        width: 78px;
        height: 78px;
        background: #050505;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        position: relative;
        display: block;
    }

    .big-route-pin::after {
        content: "";
        position: absolute;
        width: 24px;
        height: 24px;
        background: #1298e8;
        border-radius: 50%;
        top: 27px;
        left: 27px;
    }

    .destination-menu {
        animation: menuDrop 0.22s ease;
        border-top: 1px solid rgba(148, 163, 184, 0.35);
        padding-top: 14px;
        margin-top: 8px;
        text-align: center;
    }

    @keyframes menuDrop {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .destination-action-row {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 9px;
        margin-top: 10px;
        flex-wrap: wrap;
    }

    .destination-action-row .route-btn {
        min-width: 142px;
    }

    .centered-row {
        justify-content: center;
    }

    .wide-btn {
        min-width: 160px;
    }

    .route-btn.voice {
        background: #0f172a;
        color: white;
    }

    .route-btn.voice.listening {
        background: #dc2626;
        animation: voicePulse 1s infinite;
    }

    .route-help-text,
    .route-voice-status,
    .route-heard-text {
        font-size: 11px;
        color: var(--text-soft);
        line-height: 1.5;
        margin-top: 7px;
    }

    .route-voice-status {
        font-weight: 800;
        color: #475569;
    }

    .route-heard-text {
        background: #f8fafc;
        border: 1px solid #dbe4ee;
        border-radius: 12px;
        padding: 8px 10px;
    }

    .route-heard-text span {
        font-weight: 800;
        color: #0f172a;
    }

    .default-entry-hidden {
        display: none !important;
    }

    .compact-status {
        margin-top: 14px;
        text-align: left;
    }

    @keyframes voicePulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4);
        }

        70% {
            transform: scale(1.02);
            box-shadow: 0 0 0 10px rgba(220, 38, 38, 0);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 38, 38, 0);
        }
    }

    @media (max-width: 768px) {
        #route-panel {
            top: 12px;
            width: calc(100vw - 20px);
        }

        .route-card-centered {
            padding: 14px;
        }

        .start-mode-bar {
            gap: 6px;
        }

        .mode-pill {
            min-width: 0;
            flex: 1;
            font-size: 11px;
            padding: 11px 6px;
        }

        .big-route-toggle {
            width: 138px;
            height: 138px;
        }

        .big-route-pin {
            width: 62px;
            height: 62px;
        }

        .big-route-pin::after {
            width: 20px;
            height: 20px;
            top: 21px;
            left: 21px;
        }

        .destination-action-row {
            display: grid;
            grid-template-columns: 1fr;
        }

        .destination-action-row .route-btn,
        .wide-btn {
            width: 100%;
            min-width: 0;
        }
    }
</style>

<style>
    /* =========================
       FLOATING ROUTE UI
    ========================= */
    #floating-route-ui {
        position: absolute;
        top: 70px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 18px;
        pointer-events: none;
        width: min(560px, calc(100vw - 24px));
    }

    #floating-route-ui>* {
        pointer-events: auto;
    }

    .floating-start-bar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        flex-wrap: nowrap;
    }

    .floating-mode-btn {
        border: none;
        border-radius: 12px;
        padding: 14px 18px;
        min-width: 150px;
        font-size: 18px;
        font-weight: 800;
        color: #fff;
        cursor: pointer;
        font-family: inherit;
        box-shadow:
            0 8px 18px rgba(0, 0, 0, 0.18),
            inset 0 1px 0 rgba(255, 255, 255, 0.22);
        transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
    }

    .floating-mode-btn:hover {
        transform: translateY(-2px);
        box-shadow:
            0 12px 24px rgba(0, 0, 0, 0.22),
            inset 0 1px 0 rgba(255, 255, 255, 0.24);
    }

    .floating-mode-btn.pick {
        background: linear-gradient(180deg, #51e73f 0%, #1bc726 100%);
    }

    .floating-mode-btn.gps {
        background: linear-gradient(180deg, #cc73ff 0%, #8a2eff 100%);
    }

    .floating-mode-btn.default {
        background: linear-gradient(180deg, #53a8ff 0%, #1569f5 100%);
    }

    .floating-mode-btn.active {
        outline: 4px solid rgba(255, 255, 255, 0.55);
        transform: translateY(-2px) scale(1.02);
    }

    .floating-main-pin {
        width: 190px;
        height: 190px;
        border: none;
        border-radius: 50%;
        background: transparent;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease, filter 0.2s ease;
    }

    .floating-main-pin:hover {
        transform: translateY(-2px) scale(1.02);
    }

    .floating-main-pin.active .pin-disc {
        box-shadow:
            0 0 0 7px rgba(255, 255, 255, 0.5),
            0 18px 32px rgba(16, 84, 183, 0.28);
    }

    .pin-disc {
        width: 168px;
        height: 168px;
        border-radius: 50%;
        background: linear-gradient(180deg, #37a5ff 0%, #0f6ee7 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow:
            inset 0 3px 0 rgba(255, 255, 255, 0.48),
            inset 0 -6px 0 rgba(0, 0, 0, 0.12),
            0 14px 28px rgba(16, 84, 183, 0.24);
        border: 3px solid rgba(255, 255, 255, 0.82);
        position: relative;
    }

    .pin-disc::after {
        content: "";
        position: absolute;
        inset: 8px;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.25);
        pointer-events: none;
    }

    .pin-icon {
        width: 70px;
        height: 70px;
        background: #fff;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        position: relative;
        display: block;
    }

    .pin-hole {
        position: absolute;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: #1f8cff;
        top: 22px;
        left: 22px;
    }

    .floating-action-card {
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.7);
        border-radius: 22px;
        padding: 16px;
        width: min(360px, calc(100vw - 28px));
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
        animation: floatingPop 0.18s ease;
    }

    @keyframes floatingPop {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .floating-action-btn {
        width: 100%;
        border: none;
        border-radius: 14px;
        padding: 13px 14px;
        margin-bottom: 10px;
        background: #2563eb;
        color: #fff;
        font-size: 14px;
        font-weight: 800;
        cursor: pointer;
        font-family: inherit;
        transition: 0.18s ease;
    }

    .floating-action-btn:hover {
        transform: translateY(-1px);
    }

    .floating-action-btn.dark {
        background: #0f172a;
    }

    .floating-action-btn.blue {
        background: #0f6ee7;
    }

    .floating-voice-status,
    .floating-heard-text {
        margin-top: 8px;
        font-size: 12px;
        color: #475569;
        text-align: center;
    }

    .floating-heard-text {
        background: #f8fafc;
        border: 1px solid #dbe4ee;
        border-radius: 12px;
        padding: 10px 12px;
    }

    .floating-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 10020;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.38);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        padding: 16px;
    }

    .floating-modal-card {
        width: min(460px, 100%);
        background: rgba(255, 255, 255, 0.98);
        border-radius: 24px;
        padding: 22px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        border: 1px solid rgba(255, 255, 255, 0.78);
        animation: floatingPop 0.18s ease;
    }

    .floating-modal-title {
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 4px;
        text-align: center;
    }

    .floating-modal-subtitle {
        font-size: 12px;
        color: #64748b;
        line-height: 1.6;
        text-align: center;
        margin-bottom: 16px;
    }

    .floating-modal-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        margin-top: 8px;
    }

    .floating-modal-actions .route-btn {
        min-width: 130px;
    }

    #route-status-bubble {
        position: absolute;
        left: 50%;
        bottom: 18px;
        transform: translateX(-50%);
        z-index: 9998;
        width: min(440px, calc(100vw - 24px));
        background: rgba(255, 255, 255, 0.93);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.7);
        border-radius: 18px;
        padding: 12px 14px;
        font-size: 12px;
        color: #475569;
        line-height: 1.8;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.14);
        pointer-events: none;
    }

    .default-entry-hidden {
        display: none !important;
    }

    @media (max-width: 768px) {
        #floating-route-ui {
            top: 18px;
            gap: 14px;
            width: calc(100vw - 16px);
        }

        .floating-start-bar {
            gap: 8px;
            width: 100%;
        }

        .floating-mode-btn {
            min-width: 0;
            flex: 1;
            font-size: 11px;
            padding: 11px 8px;
        }

        .floating-main-pin {
            width: 150px;
            height: 150px;
        }

        .pin-disc {
            width: 134px;
            height: 134px;
        }

        .pin-icon {
            width: 56px;
            height: 56px;
        }

        .pin-hole {
            width: 20px;
            height: 20px;
            top: 18px;
            left: 18px;
        }

        .floating-action-card {
            width: calc(100vw - 24px);
        }

        .floating-modal-actions {
            flex-direction: column;
        }

        .floating-modal-actions .route-btn {
            width: 100%;
            min-width: 0;
        }

        #route-status-bubble {
            width: calc(100vw - 16px);
            bottom: 10px;
        }
    }
</style>

<style>
    /* =========================================================
       FINAL FLOATING ROUTE UI OVERRIDES
       Paste-ready with the floating dashboard view.
    ========================================================= */

    #floating-route-ui {
        position: absolute;
        top: 70px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 18px;
        pointer-events: none;
        width: min(560px, calc(100vw - 24px));
    }

    #floating-route-ui>* {
        pointer-events: auto;
    }

    .floating-start-bar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        flex-wrap: nowrap;
        width: 100%;
    }

    .floating-mode-btn {
        border: none;
        border-radius: 13px;
        padding: 14px 18px;
        min-width: 150px;
        font-size: 15px;
        font-weight: 900;
        color: #fff;
        cursor: pointer;
        font-family: inherit;
        white-space: nowrap;
        box-shadow:
            0 10px 20px rgba(0, 0, 0, 0.18),
            inset 0 1px 0 rgba(255, 255, 255, 0.28);
        transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
    }

    .floating-mode-btn:hover {
        transform: translateY(-2px);
        box-shadow:
            0 14px 26px rgba(0, 0, 0, 0.22),
            inset 0 1px 0 rgba(255, 255, 255, 0.30);
    }

    .floating-mode-btn.pick {
        background: linear-gradient(180deg, #58f04d 0%, #16be23 100%);
    }

    .floating-mode-btn.gps {
        background: linear-gradient(180deg, #c86bff 0%, #7c27f4 100%);
    }

    .floating-mode-btn.default {
        background: linear-gradient(180deg, #4ba8ff 0%, #1265ef 100%);
    }

    .floating-mode-btn.active {
        outline: 4px solid rgba(255, 255, 255, 0.72);
        transform: translateY(-2px) scale(1.025);
        filter: saturate(1.08);
    }

    .floating-main-pin {
        width: 198px;
        height: 198px;
        border: none;
        border-radius: 50%;
        background: transparent;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease, filter 0.2s ease;
        padding: 0;
    }

    .floating-main-pin:hover {
        transform: translateY(-3px) scale(1.02);
    }

    .floating-main-pin.active .pin-disc {
        box-shadow:
            0 0 0 8px rgba(255, 255, 255, 0.58),
            0 18px 34px rgba(16, 84, 183, 0.32),
            inset 0 3px 0 rgba(255, 255, 255, 0.5),
            inset 0 -7px 0 rgba(0, 0, 0, 0.14);
    }

    .pin-disc {
        width: 174px;
        height: 174px;
        border-radius: 50%;
        background: linear-gradient(180deg, #38aaff 0%, #0d70ea 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow:
            inset 0 3px 0 rgba(255, 255, 255, 0.50),
            inset 0 -7px 0 rgba(0, 0, 0, 0.13),
            0 16px 32px rgba(16, 84, 183, 0.26);
        border: 3px solid rgba(255, 255, 255, 0.86);
        position: relative;
    }

    .pin-disc::after {
        content: "";
        position: absolute;
        inset: 8px;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.25);
        pointer-events: none;
    }

    .pin-icon {
        width: 72px;
        height: 72px;
        background: #fff;
        border-radius: 50% 50% 50% 0;
        transform: rotate(-45deg);
        position: relative;
        display: block;
        box-shadow: 0 7px 18px rgba(15, 23, 42, 0.16);
    }

    .pin-hole {
        position: absolute;
        width: 27px;
        height: 27px;
        border-radius: 50%;
        background: #1f8cff;
        top: 22.5px;
        left: 22.5px;
    }

    .floating-action-card {
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.7);
        border-radius: 22px;
        padding: 16px;
        width: min(360px, calc(100vw - 28px));
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.18);
        animation: floatingPop 0.18s ease;
    }

    @keyframes floatingPop {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .floating-action-btn {
        width: 100%;
        border: none;
        border-radius: 14px;
        padding: 13px 14px;
        margin-bottom: 10px;
        background: #2563eb;
        color: #fff;
        font-size: 14px;
        font-weight: 900;
        cursor: pointer;
        font-family: inherit;
        transition: 0.18s ease;
    }

    .floating-action-btn:hover {
        transform: translateY(-1px);
    }

    .floating-action-btn.dark {
        background: #0f172a;
    }

    .floating-action-btn.blue {
        background: #0f6ee7;
    }

    .floating-voice-status,
    .floating-heard-text,
    .route-voice-status,
    .route-heard-text {
        margin-top: 8px;
        font-size: 12px;
        color: #475569;
        text-align: center;
    }

    .floating-heard-text,
    .route-heard-text {
        background: #f8fafc;
        border: 1px solid #dbe4ee;
        border-radius: 12px;
        padding: 10px 12px;
    }

    .floating-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 10020;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.38);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        padding: 16px;
    }

    .floating-modal-card {
        width: min(460px, 100%);
        background: rgba(255, 255, 255, 0.98);
        border-radius: 24px;
        padding: 22px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        border: 1px solid rgba(255, 255, 255, 0.78);
        animation: floatingPop 0.18s ease;
    }

    .floating-modal-title {
        font-size: 20px;
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 4px;
        text-align: center;
    }

    .floating-modal-subtitle {
        font-size: 12px;
        color: #64748b;
        line-height: 1.6;
        text-align: center;
        margin-bottom: 16px;
    }

    .floating-modal-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        margin-top: 8px;
    }

    .floating-modal-actions .route-btn {
        min-width: 130px;
    }

    #route-status-bubble {
        position: absolute;
        left: 50%;
        bottom: 18px;
        transform: translateX(-50%);
        z-index: 9998;
        width: min(440px, calc(100vw - 24px));
        background: rgba(255, 255, 255, 0.93);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.7);
        border-radius: 18px;
        padding: 12px 14px;
        font-size: 12px;
        color: #475569;
        line-height: 1.8;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.14);
        pointer-events: none;
    }

    .default-entry-hidden {
        display: none !important;
    }

    @media (max-width: 768px) {
        #floating-route-ui {
            top: 18px;
            gap: 14px;
            width: calc(100vw - 16px);
        }

        .floating-start-bar {
            gap: 8px;
            width: 100%;
        }

        .floating-mode-btn {
            min-width: 0;
            flex: 1;
            font-size: 11px;
            padding: 11px 6px;
            border-radius: 10px;
        }

        .floating-main-pin {
            width: 150px;
            height: 150px;
        }

        .pin-disc {
            width: 134px;
            height: 134px;
        }

        .pin-icon {
            width: 56px;
            height: 56px;
        }

        .pin-hole {
            width: 20px;
            height: 20px;
            top: 18px;
            left: 18px;
        }

        .floating-action-card {
            width: calc(100vw - 24px);
        }

        .floating-modal-actions {
            flex-direction: column;
        }

        .floating-modal-actions .route-btn {
            width: 100%;
            min-width: 0;
        }

        #route-status-bubble {
            width: calc(100vw - 16px);
            bottom: 10px;
            font-size: 11px;
            max-height: 160px;
            overflow: auto;
        }
    }
</style>


<style>
    /* =========================================================
       FLOATING UI CLICK / LAYER FIX
       Keeps the floating pin and action buttons above the map.
    ========================================================= */

    #floating-route-ui {
        z-index: 10040 !important;
        pointer-events: none !important;
    }

    #floating-route-ui > * {
        pointer-events: auto !important;
    }

    .floating-main-pin {
        position: relative !important;
        z-index: 10045 !important;
    }

    .floating-main-pin,
    .pin-disc,
    .pin-icon,
    .pin-hole {
        pointer-events: auto !important;
    }

    .floating-action-card {
        position: relative !important;
        z-index: 10050 !important;
        pointer-events: auto !important;
    }

    .floating-action-card .floating-action-btn {
        pointer-events: auto !important;
    }

    .floating-modal-backdrop {
        z-index: 10060 !important;
    }

    #route-status-bubble {
        z-index: 10030 !important;
    }

    .leaflet-container {
        z-index: 1;
    }

    @media (max-width: 768px) {
        #floating-route-ui {
            z-index: 10040 !important;
        }

        .floating-action-card {
            z-index: 10050 !important;
        }
    }
</style>



<style>
/* =========================================================
   AI / CHATGPT-LIKE FLOATING DOCK THEME
   Bottom-center controls + cooler glassmorphism look
========================================================= */

:root {
    --ai-bg-1: rgba(8, 12, 20, 0.76);
    --ai-bg-2: rgba(15, 23, 42, 0.80);
    --ai-bg-3: rgba(2, 6, 23, 0.88);
    --ai-border: rgba(255, 255, 255, 0.12);
    --ai-text: #e5eef8;
    --ai-soft: #9fb2c8;
    --ai-green: #10a37f;
    --ai-green-2: #19c37d;
    --ai-cyan: #22d3ee;
    --ai-blue: #3b82f6;
    --ai-violet: #8b5cf6;
    --ai-shadow: 0 30px 80px rgba(2, 8, 23, 0.28);
}

#floating-route-ui {
    position: fixed !important;
    left: 50% !important;
    right: auto !important;
    top: auto !important;
    bottom: 26px !important;
    transform: translateX(-50%) !important;
    z-index: 10040 !important;
    width: min(760px, calc(100vw - 24px));
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: flex-end !important;
    gap: 14px !important;
    pointer-events: none !important;
}

#floating-route-ui > * {
    pointer-events: auto !important;
}

.floating-ai-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 8px 14px;
    border-radius: 999px;
    color: var(--ai-text);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.82), rgba(2, 6, 23, 0.88));
    border: 1px solid rgba(16, 163, 127, 0.28);
    box-shadow: 0 10px 30px rgba(2, 8, 23, 0.18), inset 0 1px 0 rgba(255,255,255,0.06);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}

.ai-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #7ef3d7 0%, var(--ai-green-2) 48%, #0c7a62 100%);
    box-shadow: 0 0 0 6px rgba(16, 163, 127, 0.10), 0 0 18px rgba(16, 163, 127, 0.55);
    animation: aiPulse 2.4s ease-in-out infinite;
}

@keyframes aiPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.16); opacity: 0.86; }
}

.floating-main-pin {
    width: 120px !important;
    height: 120px !important;
    border-radius: 999px !important;
    border: 1px solid rgba(255,255,255,0.16) !important;
    background:
        radial-gradient(circle at 30% 30%, rgba(34, 211, 238, 0.14), transparent 40%),
        radial-gradient(circle at 70% 70%, rgba(16, 163, 127, 0.16), transparent 35%),
        linear-gradient(180deg, rgba(15,23,42,0.86) 0%, rgba(2,6,23,0.96) 100%) !important;
    box-shadow:
        0 28px 60px rgba(2, 8, 23, 0.30),
        0 0 0 1px rgba(255,255,255,0.04) inset,
        0 0 50px rgba(16, 163, 127, 0.12) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    transition: transform 0.22s ease, box-shadow 0.22s ease;
    position: relative !important;
    z-index: 10045 !important;
}

.floating-main-pin:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow:
        0 34px 70px rgba(2, 8, 23, 0.36),
        0 0 0 1px rgba(255,255,255,0.06) inset,
        0 0 60px rgba(16, 163, 127, 0.18) !important;
}

.floating-main-pin.active .pin-disc {
    box-shadow:
        0 0 0 1px rgba(255,255,255,0.14) inset,
        0 0 38px rgba(16, 163, 127, 0.30),
        0 16px 34px rgba(2, 8, 23, 0.28) !important;
}

.pin-disc {
    width: 92px !important;
    height: 92px !important;
    border-radius: 999px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background:
        radial-gradient(circle at 35% 30%, rgba(255,255,255,0.22), transparent 28%),
        linear-gradient(180deg, rgba(26, 35, 55, 0.90), rgba(10, 15, 27, 0.94)) !important;
    border: 1px solid rgba(255,255,255,0.09);
    box-shadow:
        0 16px 34px rgba(2, 8, 23, 0.22),
        0 0 0 1px rgba(255,255,255,0.04) inset !important;
}

.pin-icon {
    width: 40px !important;
    height: 56px !important;
    background: linear-gradient(180deg, #f8fbff 0%, #dfeaf5 100%) !important;
    position: relative !important;
    clip-path: path('M20 0 C31 0 40 9 40 20 C40 36 20 56 20 56 C20 56 0 36 0 20 C0 9 9 0 20 0 Z');
    display: block;
    box-shadow: 0 10px 22px rgba(2, 8, 23, 0.16);
}

.pin-hole {
    position: absolute;
    top: 14px;
    left: 50%;
    transform: translateX(-50%);
    width: 13px;
    height: 13px;
    border-radius: 50%;
    background: linear-gradient(180deg, var(--ai-green-2), var(--ai-cyan));
    box-shadow: 0 0 12px rgba(16, 163, 127, 0.34);
}

.floating-start-bar {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
    width: min(760px, 100%) !important;
    padding: 12px !important;
    border-radius: 26px !important;
    background:
        linear-gradient(180deg, rgba(15,23,42,0.82) 0%, rgba(2,6,23,0.88) 100%) !important;
    border: 1px solid var(--ai-border) !important;
    box-shadow: var(--ai-shadow), inset 0 1px 0 rgba(255,255,255,0.04) !important;
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
}

.floating-mode-btn {
    flex: 1 1 170px;
    min-width: 150px;
    border: 1px solid rgba(255,255,255,0.08) !important;
    border-radius: 18px !important;
    padding: 14px 18px !important;
    font-size: 14px !important;
    font-weight: 800 !important;
    letter-spacing: 0.02em;
    color: var(--ai-text) !important;
    background:
        linear-gradient(180deg, rgba(30, 41, 59, 0.72), rgba(15, 23, 42, 0.92)) !important;
    box-shadow:
        0 10px 24px rgba(2,8,23,0.18),
        inset 0 1px 0 rgba(255,255,255,0.04) !important;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.floating-mode-btn:hover {
    transform: translateY(-2px);
    box-shadow:
        0 14px 28px rgba(2,8,23,0.24),
        inset 0 1px 0 rgba(255,255,255,0.06) !important;
}

.floating-mode-btn.pick { --accent: #22c55e; }
.floating-mode-btn.gps { --accent: #8b5cf6; }
.floating-mode-btn.default { --accent: #3b82f6; }

.floating-mode-btn.active {
    border-color: color-mix(in srgb, var(--accent) 54%, white 16%) !important;
    background:
        linear-gradient(180deg,
            color-mix(in srgb, var(--accent) 42%, rgba(255,255,255,0.06)) 0%,
            color-mix(in srgb, var(--accent) 75%, rgba(15, 23, 42, 0.16)) 100%) !important;
    box-shadow:
        0 18px 30px rgba(2,8,23,0.26),
        0 0 0 1px rgba(255,255,255,0.06) inset,
        0 0 26px color-mix(in srgb, var(--accent) 34%, transparent) !important;
}

.floating-action-card {
    width: min(390px, calc(100vw - 28px)) !important;
    border-radius: 28px !important;
    padding: 18px !important;
    background:
        radial-gradient(circle at top right, rgba(34, 211, 238, 0.08), transparent 30%),
        radial-gradient(circle at top left, rgba(16, 163, 127, 0.10), transparent 35%),
        linear-gradient(180deg, rgba(15,23,42,0.86) 0%, rgba(2,6,23,0.94) 100%) !important;
    border: 1px solid rgba(255,255,255,0.10) !important;
    box-shadow:
        0 30px 70px rgba(2,8,23,0.34),
        inset 0 1px 0 rgba(255,255,255,0.05) !important;
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
    color: var(--ai-text);
    position: relative !important;
    z-index: 10050 !important;
}

.floating-action-head {
    margin-bottom: 14px;
}

.floating-action-kicker {
    color: #7de6d4;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.floating-action-title {
    color: var(--ai-text);
    font-size: 16px;
    line-height: 1.4;
    font-weight: 800;
}

.floating-action-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: center;
    border-radius: 18px !important;
    padding: 13px 16px !important;
    margin-top: 10px;
    border: 1px solid rgba(255,255,255,0.08) !important;
    background:
        linear-gradient(180deg, rgba(30, 41, 59, 0.72), rgba(15, 23, 42, 0.92)) !important;
    color: var(--ai-text) !important;
    font-size: 14px !important;
    font-weight: 800 !important;
    letter-spacing: 0.01em;
    box-shadow: 0 10px 20px rgba(2,8,23,0.16), inset 0 1px 0 rgba(255,255,255,0.04);
    transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.floating-action-btn:hover {
    transform: translateY(-2px);
    border-color: rgba(16, 163, 127, 0.34) !important;
    box-shadow: 0 16px 28px rgba(2,8,23,0.24), 0 0 24px rgba(16, 163, 127, 0.10);
}

.floating-action-btn.dark {
    border-color: rgba(139, 92, 246, 0.18) !important;
}

.floating-action-btn.blue {
    border-color: rgba(59, 130, 246, 0.20) !important;
}

.action-icon {
    font-size: 16px;
    line-height: 1;
}

.floating-voice-status,
.floating-heard-text {
    margin-top: 12px;
    padding: 10px 12px;
    border-radius: 16px;
    font-size: 12px;
    line-height: 1.6;
    color: var(--ai-soft);
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.05);
}

#route-status-bubble {
    position: fixed !important;
    top: 22px !important;
    right: 22px !important;
    bottom: auto !important;
    left: auto !important;
    width: min(360px, calc(100vw - 24px)) !important;
    z-index: 10030 !important;
    padding: 16px 18px !important;
    border-radius: 24px !important;
    background:
        radial-gradient(circle at top right, rgba(34, 211, 238, 0.06), transparent 35%),
        linear-gradient(180deg, rgba(15,23,42,0.82), rgba(2,6,23,0.90)) !important;
    border: 1px solid rgba(255,255,255,0.10) !important;
    box-shadow: 0 18px 40px rgba(2,8,23,0.20), inset 0 1px 0 rgba(255,255,255,0.04) !important;
    color: var(--ai-soft) !important;
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
    line-height: 1.75 !important;
}

.route-status-head {
    margin-bottom: 10px;
}

.route-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 7px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #dffcf5;
    background: rgba(16, 163, 127, 0.14);
    border: 1px solid rgba(16, 163, 127, 0.20);
}

.route-status-pill::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: linear-gradient(180deg, #7ef3d7, var(--ai-green-2));
    box-shadow: 0 0 16px rgba(16, 163, 127, 0.52);
}

#route-status-bubble strong {
    color: var(--ai-text) !important;
    font-weight: 800;
}

#route-status-bubble span {
    color: var(--ai-soft) !important;
}

.floating-modal-backdrop {
    background: rgba(2, 6, 23, 0.52) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    z-index: 10060 !important;
}

.floating-modal-card {
    width: min(470px, calc(100vw - 28px)) !important;
    border-radius: 28px !important;
    padding: 22px !important;
    background:
        radial-gradient(circle at top right, rgba(34, 211, 238, 0.08), transparent 32%),
        radial-gradient(circle at top left, rgba(16, 163, 127, 0.10), transparent 38%),
        linear-gradient(180deg, rgba(15,23,42,0.88) 0%, rgba(2,6,23,0.96) 100%) !important;
    border: 1px solid rgba(255,255,255,0.10) !important;
    box-shadow: 0 36px 80px rgba(2,8,23,0.34), inset 0 1px 0 rgba(255,255,255,0.05) !important;
    color: var(--ai-text);
}

.floating-modal-title {
    color: var(--ai-text) !important;
    font-size: 20px !important;
    font-weight: 800 !important;
    margin-bottom: 6px !important;
}

.floating-modal-subtitle {
    color: var(--ai-soft) !important;
    font-size: 13px !important;
    line-height: 1.7 !important;
    margin-bottom: 14px !important;
}

.route-label {
    color: #dce7f3 !important;
    font-size: 12px !important;
    font-weight: 700 !important;
}

.route-select,
.route-input,
.indoor-toolbar select,
.indoor-toolbar input {
    background: rgba(255,255,255,0.05) !important;
    color: var(--ai-text) !important;
    border: 1px solid rgba(255,255,255,0.10) !important;
    border-radius: 16px !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
}

.route-select option,
.indoor-toolbar select option {
    color: #0f172a;
}

.route-select::placeholder,
.route-input::placeholder,
.indoor-toolbar input::placeholder {
    color: #90a4ba !important;
}

.floating-modal-actions {
    gap: 10px !important;
}

.route-btn {
    border-radius: 16px !important;
    padding: 12px 16px !important;
    font-size: 13px !important;
    font-weight: 800 !important;
    box-shadow: 0 10px 24px rgba(2,8,23,0.16);
}

.route-btn.success {
    background: linear-gradient(180deg, #1dbf92, #10a37f) !important;
    color: white !important;
}

.route-btn.neutral {
    background: linear-gradient(180deg, rgba(51,65,85,0.92), rgba(30,41,59,0.96)) !important;
    color: var(--ai-text) !important;
    border: 1px solid rgba(255,255,255,0.08);
}

.indoor-panel {
    background: rgba(255, 255, 255, 0.96) !important;
}

@media (max-width: 768px) {
    #floating-route-ui {
        width: calc(100vw - 18px) !important;
        bottom: 14px !important;
        gap: 12px !important;
    }

    .floating-start-bar {
        border-radius: 24px !important;
        padding: 10px !important;
        gap: 8px !important;
    }

    .floating-mode-btn {
        flex: 1 1 100%;
        min-width: 100%;
        padding: 13px 14px !important;
        font-size: 13px !important;
    }

    .floating-main-pin {
        width: 102px !important;
        height: 102px !important;
    }

    .pin-disc {
        width: 78px !important;
        height: 78px !important;
    }

    .pin-icon {
        width: 34px !important;
        height: 48px !important;
    }

    #route-status-bubble {
        top: 12px !important;
        left: 12px !important;
        right: 12px !important;
        width: auto !important;
    }

    .floating-modal-card {
        padding: 18px !important;
        border-radius: 24px !important;
    }
}
</style>


<style>
/* =========================================================
   AI ORB TRANSFORM UI
   Pin transforms into Search Bar or Voice Recorder.
========================================================= */

#floating-route-ui {
    position: fixed !important;
    left: 50% !important;
    right: auto !important;
    top: auto !important;
    bottom: 24px !important;
    transform: translateX(-50%) !important;
    z-index: 10040 !important;
    width: min(780px, calc(100vw - 24px)) !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: flex-end !important;
    gap: 12px !important;
    pointer-events: none !important;
}

#floating-route-ui > * {
    pointer-events: auto !important;
}

.ai-orb-shell {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: min(560px, calc(100vw - 28px));
    min-height: 128px;
    pointer-events: none;
}

.ai-orb-shell > * {
    pointer-events: auto;
}

.floating-ai-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 8px 14px;
    border-radius: 999px;
    color: #e5eef8;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.82), rgba(2, 6, 23, 0.88));
    border: 1px solid rgba(16, 163, 127, 0.28);
    box-shadow: 0 10px 30px rgba(2, 8, 23, 0.18), inset 0 1px 0 rgba(255,255,255,0.06);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}

.ai-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 35%, #7ef3d7 0%, #19c37d 48%, #0c7a62 100%);
    box-shadow: 0 0 0 6px rgba(16, 163, 127, 0.10), 0 0 18px rgba(16, 163, 127, 0.55);
    animation: aiPulse 2.4s ease-in-out infinite;
}

.ai-dot.tiny {
    width: 8px;
    height: 8px;
    box-shadow: 0 0 0 4px rgba(16, 163, 127, 0.10), 0 0 14px rgba(16, 163, 127, 0.45);
}

@keyframes aiPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.16); opacity: 0.86; }
}

.floating-main-pin {
    width: 120px !important;
    height: 120px !important;
    border-radius: 999px !important;
    border: 1px solid rgba(255,255,255,0.16) !important;
    background:
        radial-gradient(circle at 30% 30%, rgba(34, 211, 238, 0.14), transparent 40%),
        radial-gradient(circle at 70% 70%, rgba(16, 163, 127, 0.16), transparent 35%),
        linear-gradient(180deg, rgba(15,23,42,0.86) 0%, rgba(2,6,23,0.96) 100%) !important;
    box-shadow:
        0 28px 60px rgba(2, 8, 23, 0.30),
        0 0 0 1px rgba(255,255,255,0.04) inset,
        0 0 50px rgba(16, 163, 127, 0.12) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    transition: transform 0.22s ease, opacity 0.18s ease, width 0.24s ease, box-shadow 0.22s ease;
    position: relative !important;
    z-index: 10045 !important;
}

.floating-main-pin:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow:
        0 34px 70px rgba(2, 8, 23, 0.36),
        0 0 0 1px rgba(255,255,255,0.06) inset,
        0 0 60px rgba(16, 163, 127, 0.18) !important;
}

#floating-route-ui.transforming .floating-main-pin {
    opacity: 0;
    transform: translateY(10px) scale(0.86);
    pointer-events: none !important;
}

.pin-disc {
    width: 92px !important;
    height: 92px !important;
    border-radius: 999px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background:
        radial-gradient(circle at 35% 30%, rgba(255,255,255,0.22), transparent 28%),
        linear-gradient(180deg, rgba(26, 35, 55, 0.90), rgba(10, 15, 27, 0.94)) !important;
    border: 1px solid rgba(255,255,255,0.09);
    box-shadow:
        0 16px 34px rgba(2, 8, 23, 0.22),
        0 0 0 1px rgba(255,255,255,0.04) inset !important;
}

.pin-icon {
    width: 40px !important;
    height: 56px !important;
    background: linear-gradient(180deg, #f8fbff 0%, #dfeaf5 100%) !important;
    position: relative !important;
    clip-path: path('M20 0 C31 0 40 9 40 20 C40 36 20 56 20 56 C20 56 0 36 0 20 C0 9 9 0 20 0 Z');
    display: block;
}

.pin-hole {
    position: absolute;
    top: 14px;
    left: 50%;
    transform: translateX(-50%);
    width: 13px;
    height: 13px;
    border-radius: 50%;
    background: linear-gradient(180deg, #19c37d, #22d3ee);
    box-shadow: 0 0 12px rgba(16, 163, 127, 0.34);
}

.floating-action-card {
    position: absolute !important;
    bottom: 130px;
    left: 50%;
    transform: translateX(-50%);
    width: min(390px, calc(100vw - 28px)) !important;
    border-radius: 28px !important;
    padding: 18px !important;
    background:
        radial-gradient(circle at top right, rgba(34, 211, 238, 0.08), transparent 30%),
        radial-gradient(circle at top left, rgba(16, 163, 127, 0.10), transparent 35%),
        linear-gradient(180deg, rgba(15,23,42,0.88) 0%, rgba(2,6,23,0.95) 100%) !important;
    border: 1px solid rgba(255,255,255,0.10) !important;
    box-shadow:
        0 30px 70px rgba(2,8,23,0.34),
        inset 0 1px 0 rgba(255,255,255,0.05) !important;
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
    color: #e5eef8;
    z-index: 10050 !important;
    animation: aiPanelPop 0.2s ease;
}

@keyframes aiPanelPop {
    from { opacity: 0; transform: translateX(-50%) translateY(12px) scale(0.96); }
    to { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }
}

.floating-action-head {
    margin-bottom: 14px;
}

.floating-action-kicker {
    color: #7de6d4;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.floating-action-title {
    color: #e5eef8;
    font-size: 16px;
    line-height: 1.4;
    font-weight: 800;
}

.floating-action-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    justify-content: center;
    border-radius: 18px !important;
    padding: 13px 16px !important;
    margin-top: 10px;
    border: 1px solid rgba(255,255,255,0.08) !important;
    background:
        linear-gradient(180deg, rgba(30, 41, 59, 0.72), rgba(15, 23, 42, 0.92)) !important;
    color: #e5eef8 !important;
    font-size: 14px !important;
    font-weight: 800 !important;
    letter-spacing: 0.01em;
    box-shadow: 0 10px 20px rgba(2,8,23,0.16), inset 0 1px 0 rgba(255,255,255,0.04);
}

.floating-action-btn:hover {
    transform: translateY(-2px);
    border-color: rgba(16, 163, 127, 0.34) !important;
    box-shadow: 0 16px 28px rgba(2,8,23,0.24), 0 0 24px rgba(16, 163, 127, 0.10);
}

.action-icon {
    font-size: 16px;
}

.ai-transform-panel {
    position: relative;
    width: min(540px, calc(100vw - 28px));
    border-radius: 32px;
    padding: 18px;
    color: #e5eef8;
    background:
        radial-gradient(circle at top right, rgba(34, 211, 238, 0.10), transparent 34%),
        radial-gradient(circle at top left, rgba(16, 163, 127, 0.12), transparent 38%),
        linear-gradient(180deg, rgba(15,23,42,0.90) 0%, rgba(2,6,23,0.96) 100%);
    border: 1px solid rgba(255,255,255,0.11);
    box-shadow:
        0 34px 80px rgba(2,8,23,0.36),
        inset 0 1px 0 rgba(255,255,255,0.05);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    z-index: 10055;
    animation: aiTransformIn 0.26s ease;
}

@keyframes aiTransformIn {
    from {
        opacity: 0;
        transform: translateY(18px) scale(0.90);
        border-radius: 999px;
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
        border-radius: 32px;
    }
}

.ai-transform-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 34px;
    height: 34px;
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 999px;
    background: rgba(255,255,255,0.06);
    color: #dbeafe;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
}

.ai-transform-close:hover {
    background: rgba(255,255,255,0.10);
    transform: translateY(-1px);
}

.ai-transform-kicker {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: #7de6d4;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    margin-bottom: 12px;
    padding-right: 34px;
}

.ai-search-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
    align-items: center;
}

.ai-search-input {
    width: 100%;
    min-height: 50px;
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 18px;
    padding: 14px 16px;
    background: rgba(255,255,255,0.06);
    color: #eef6ff;
    font-family: inherit;
    font-size: 14px;
    outline: none;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
}

.ai-search-input::placeholder {
    color: #93a7bd;
}

.ai-search-input:focus {
    border-color: rgba(16, 163, 127, 0.46);
    box-shadow: 0 0 0 4px rgba(16, 163, 127, 0.10), inset 0 1px 0 rgba(255,255,255,0.04);
}

.ai-search-submit,
.ai-stop-voice {
    min-height: 50px;
    border: none;
    border-radius: 18px;
    padding: 0 18px;
    background: linear-gradient(180deg, #1dbf92, #10a37f);
    color: #ffffff;
    font-family: inherit;
    font-size: 13px;
    font-weight: 900;
    cursor: pointer;
    box-shadow: 0 14px 26px rgba(16, 163, 127, 0.18);
}

.ai-search-submit:hover,
.ai-stop-voice:hover {
    transform: translateY(-1px);
}

.ai-transform-hint {
    margin-top: 10px;
    color: #9fb2c8;
    font-size: 12px;
    line-height: 1.6;
}

.ai-voice-core {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 14px;
    align-items: center;
}

.ai-voice-orb {
    width: 68px;
    height: 68px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background:
        radial-gradient(circle, rgba(16, 163, 127, 0.26) 0%, rgba(16, 163, 127, 0.10) 42%, rgba(255,255,255,0.04) 100%);
    border: 1px solid rgba(16, 163, 127, 0.26);
    box-shadow: 0 0 30px rgba(16, 163, 127, 0.18);
}

.ai-voice-orb span {
    width: 28px;
    height: 28px;
    border-radius: 999px;
    background: linear-gradient(180deg, #7ef3d7, #10a37f);
    box-shadow: 0 0 0 0 rgba(16, 163, 127, 0.40);
    animation: voiceWave 1.15s ease-out infinite;
}

@keyframes voiceWave {
    0% { box-shadow: 0 0 0 0 rgba(16, 163, 127, 0.38); transform: scale(0.92); }
    70% { box-shadow: 0 0 0 22px rgba(16, 163, 127, 0); transform: scale(1); }
    100% { box-shadow: 0 0 0 0 rgba(16, 163, 127, 0); transform: scale(0.92); }
}

.ai-voice-title {
    font-size: 16px;
    font-weight: 900;
    color: #eef6ff;
    margin-bottom: 4px;
}

.floating-voice-status,
.floating-heard-text {
    margin-top: 10px;
    color: #9fb2c8 !important;
    font-size: 12px;
    line-height: 1.6;
}

.floating-heard-text {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 16px;
    padding: 10px 12px;
}

.ai-stop-voice {
    width: 100%;
    margin-top: 12px;
    background: linear-gradient(180deg, #ef4444, #dc2626);
    box-shadow: 0 14px 26px rgba(220, 38, 38, 0.18);
}

.floating-start-bar {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-wrap: wrap !important;
    gap: 10px !important;
    width: min(760px, 100%) !important;
    padding: 12px !important;
    border-radius: 26px !important;
    background:
        linear-gradient(180deg, rgba(15,23,42,0.82) 0%, rgba(2,6,23,0.88) 100%) !important;
    border: 1px solid rgba(255,255,255,0.12) !important;
    box-shadow: 0 30px 80px rgba(2, 8, 23, 0.28), inset 0 1px 0 rgba(255,255,255,0.04) !important;
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
}

.floating-mode-btn {
    flex: 1 1 170px;
    min-width: 150px;
    border: 1px solid rgba(255,255,255,0.08) !important;
    border-radius: 18px !important;
    padding: 14px 18px !important;
    font-size: 14px !important;
    font-weight: 800 !important;
    color: #e5eef8 !important;
    background:
        linear-gradient(180deg, rgba(30, 41, 59, 0.72), rgba(15, 23, 42, 0.92)) !important;
    box-shadow:
        0 10px 24px rgba(2,8,23,0.18),
        inset 0 1px 0 rgba(255,255,255,0.04) !important;
}

.floating-mode-btn.pick { --accent: #22c55e; }
.floating-mode-btn.gps { --accent: #8b5cf6; }
.floating-mode-btn.default { --accent: #3b82f6; }

.floating-mode-btn.active {
    border-color: color-mix(in srgb, var(--accent) 54%, white 16%) !important;
    background:
        linear-gradient(180deg,
            color-mix(in srgb, var(--accent) 42%, rgba(255,255,255,0.06)) 0%,
            color-mix(in srgb, var(--accent) 75%, rgba(15, 23, 42, 0.16)) 100%) !important;
}

#route-status-bubble {
    position: fixed !important;
    top: 22px !important;
    right: 22px !important;
    bottom: auto !important;
    left: auto !important;
    width: min(360px, calc(100vw - 24px)) !important;
    z-index: 10030 !important;
    padding: 16px 18px !important;
    border-radius: 24px !important;
    background:
        radial-gradient(circle at top right, rgba(34, 211, 238, 0.06), transparent 35%),
        linear-gradient(180deg, rgba(15,23,42,0.82), rgba(2,6,23,0.90)) !important;
    border: 1px solid rgba(255,255,255,0.10) !important;
    box-shadow: 0 18px 40px rgba(2,8,23,0.20), inset 0 1px 0 rgba(255,255,255,0.04) !important;
    color: #9fb2c8 !important;
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
    line-height: 1.75 !important;
}

.route-status-head {
    margin-bottom: 10px;
}

.route-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 7px 12px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #dffcf5;
    background: rgba(16, 163, 127, 0.14);
    border: 1px solid rgba(16, 163, 127, 0.20);
}

.route-status-pill::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: linear-gradient(180deg, #7ef3d7, #19c37d);
    box-shadow: 0 0 16px rgba(16, 163, 127, 0.52);
}

#route-status-bubble strong {
    color: #e5eef8 !important;
}

#route-status-bubble span {
    color: #9fb2c8 !important;
}

.floating-modal-backdrop {
    background: rgba(2, 6, 23, 0.52) !important;
    backdrop-filter: blur(12px) !important;
    -webkit-backdrop-filter: blur(12px) !important;
    z-index: 10060 !important;
}

.floating-modal-card {
    width: min(470px, calc(100vw - 28px)) !important;
    border-radius: 28px !important;
    padding: 22px !important;
    background:
        radial-gradient(circle at top right, rgba(34, 211, 238, 0.08), transparent 32%),
        radial-gradient(circle at top left, rgba(16, 163, 127, 0.10), transparent 38%),
        linear-gradient(180deg, rgba(15,23,42,0.88) 0%, rgba(2,6,23,0.96) 100%) !important;
    border: 1px solid rgba(255,255,255,0.10) !important;
    box-shadow: 0 36px 80px rgba(2,8,23,0.34), inset 0 1px 0 rgba(255,255,255,0.05) !important;
    color: #e5eef8;
}

.floating-modal-title {
    color: #e5eef8 !important;
}

.floating-modal-subtitle,
.route-label {
    color: #9fb2c8 !important;
}

.route-select,
.route-input,
.indoor-toolbar select,
.indoor-toolbar input {
    background: rgba(255,255,255,0.05) !important;
    color: #e5eef8 !important;
    border: 1px solid rgba(255,255,255,0.10) !important;
    border-radius: 16px !important;
}

.route-select option,
.indoor-toolbar select option {
    color: #0f172a;
}

.route-btn.success {
    background: linear-gradient(180deg, #1dbf92, #10a37f) !important;
    color: #ffffff !important;
}

.route-btn.neutral {
    background: linear-gradient(180deg, rgba(51,65,85,0.92), rgba(30,41,59,0.96)) !important;
    color: #e5eef8 !important;
    border: 1px solid rgba(255,255,255,0.08);
}

.default-entry-hidden {
    display: none !important;
}

@media (max-width: 768px) {
    #floating-route-ui {
        width: calc(100vw - 18px) !important;
        bottom: 14px !important;
        gap: 10px !important;
    }

    .ai-orb-shell {
        width: calc(100vw - 18px);
        min-height: 108px;
    }

    .floating-action-card {
        bottom: 108px;
    }

    .floating-main-pin {
        width: 104px !important;
        height: 104px !important;
    }

    .pin-disc {
        width: 80px !important;
        height: 80px !important;
    }

    .pin-icon {
        width: 34px !important;
        height: 48px !important;
    }

    .ai-transform-panel {
        width: calc(100vw - 20px);
        border-radius: 26px;
        padding: 16px;
    }

    .ai-search-row {
        grid-template-columns: 1fr;
    }

    .ai-search-submit {
        width: 100%;
    }

    .floating-start-bar {
        border-radius: 24px !important;
        padding: 10px !important;
        gap: 8px !important;
    }

    .floating-mode-btn {
        flex: 1 1 100%;
        min-width: 100%;
        padding: 13px 14px !important;
        font-size: 13px !important;
    }

    #route-status-bubble {
        top: 12px !important;
        left: 12px !important;
        right: 12px !important;
        width: auto !important;
    }
}
</style>


<style>
/* =========================================================
   VOICE PANEL PERSIST RESULT FIX
   Voice panel stays open after recording and displays result.
========================================================= */

.ai-voice-panel.voice-finished .ai-voice-orb span {
    animation: none !important;
    background: linear-gradient(180deg, #93c5fd, #10a37f) !important;
    box-shadow: 0 0 18px rgba(16, 163, 127, 0.22) !important;
}

.ai-voice-panel.voice-finished .ai-voice-title::after {
    content: " · Done";
    color: #7ef3d7;
    font-weight: 900;
}

.ai-voice-result-card {
    margin-top: 12px;
    padding: 12px 14px;
    border-radius: 18px;
    background:
        radial-gradient(circle at top right, rgba(16, 163, 127, 0.12), transparent 32%),
        rgba(255, 255, 255, 0.055);
    border: 1px solid rgba(16, 163, 127, 0.18);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.ai-voice-result-label {
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #7ef3d7;
    margin-bottom: 6px;
}

.ai-voice-result-text {
    color: #eef6ff;
    font-size: 14px;
    font-weight: 800;
    line-height: 1.5;
    word-break: break-word;
}

.ai-voice-result-note {
    margin-top: 7px;
    color: #9fb2c8;
    font-size: 11px;
    line-height: 1.5;
}

.ai-voice-panel.voice-finished .ai-stop-voice {
    background: linear-gradient(180deg, #334155, #1e293b) !important;
    box-shadow: 0 14px 26px rgba(2, 8, 23, 0.18) !important;
}

.ai-voice-panel.voice-finished .ai-stop-voice::after {
    content: " / Close";
}
</style>


<style>
/* =========================================================
   TEXT + VOICE PANEL PERSIST FIX
   Text and voice panels stay open until user closes them.
========================================================= */

.ai-search-panel.search-finished .ai-transform-kicker::after {
    content: " · Done";
    color: #7ef3d7;
    font-weight: 900;
}

.ai-text-result-card,
.ai-voice-result-card {
    margin-top: 12px;
    padding: 12px 14px;
    border-radius: 18px;
    background:
        radial-gradient(circle at top right, rgba(16, 163, 127, 0.12), transparent 32%),
        rgba(255, 255, 255, 0.055);
    border: 1px solid rgba(16, 163, 127, 0.18);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.ai-text-result-label,
.ai-voice-result-label {
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #7ef3d7;
    margin-bottom: 6px;
}

.ai-text-result-text,
.ai-voice-result-text {
    color: #eef6ff;
    font-size: 14px;
    font-weight: 800;
    line-height: 1.5;
    word-break: break-word;
}

.ai-text-result-note,
.ai-voice-result-note {
    margin-top: 7px;
    color: #9fb2c8;
    font-size: 11px;
    line-height: 1.5;
}
</style>


<style>
/* =========================================================
   RECORD AGAIN BUTTON ADD-ON
   Adds record button beside Search and keeps AI panels open.
========================================================= */

.ai-search-row {
    grid-template-columns: 1fr auto auto !important;
}

.ai-record-inline-btn,
.ai-record-again-btn {
    min-height: 50px;
    border: none;
    border-radius: 18px;
    padding: 0 16px;
    background:
        radial-gradient(circle at top left, rgba(34, 211, 238, 0.22), transparent 38%),
        linear-gradient(180deg, #7c3aed, #4f46e5);
    color: #ffffff;
    font-family: inherit;
    font-size: 13px;
    font-weight: 900;
    cursor: pointer;
    box-shadow:
        0 14px 26px rgba(79, 70, 229, 0.20),
        inset 0 1px 0 rgba(255,255,255,0.14);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
    white-space: nowrap;
}

.ai-record-inline-btn:hover,
.ai-record-again-btn:hover {
    transform: translateY(-1px);
    box-shadow:
        0 18px 34px rgba(79, 70, 229, 0.26),
        0 0 24px rgba(124, 58, 237, 0.18),
        inset 0 1px 0 rgba(255,255,255,0.16);
}

.ai-voice-button-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 12px;
}

.ai-voice-button-row .ai-stop-voice,
.ai-voice-button-row .ai-record-again-btn {
    width: 100%;
    margin-top: 0 !important;
}

.ai-voice-panel:not(.voice-finished) .ai-record-again-btn {
    opacity: 0.78;
}

.ai-search-panel.search-finished .ai-record-inline-btn {
    background:
        radial-gradient(circle at top left, rgba(34, 211, 238, 0.24), transparent 38%),
        linear-gradient(180deg, #8b5cf6, #2563eb);
}

@media (max-width: 768px) {
    .ai-search-row {
        grid-template-columns: 1fr !important;
    }

    .ai-search-submit,
    .ai-record-inline-btn {
        width: 100%;
    }

    .ai-voice-button-row {
        grid-template-columns: 1fr;
    }
}
</style>


<style>
/* =========================================================
   ROUTE BUILDING POPUP
   Shows after routing to a building/room and opens indoor rooms.
========================================================= */

.route-building-popup {
    position: fixed;
    z-index: 10035;
    width: min(300px, calc(100vw - 28px));
    background: rgba(255, 255, 255, 0.94);
    color: #334155;
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 18px;
    padding: 16px 18px 18px;
    box-shadow:
        0 18px 45px rgba(15, 23, 42, 0.16),
        inset 0 1px 0 rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    pointer-events: auto;
    animation: routePopupIn 0.22s ease;
}

@keyframes routePopupIn {
    from {
        opacity: 0;
        transform: translate(-50%, -8px) scale(0.96);
    }
    to {
        opacity: 1;
        transform: translate(-50%, 0) scale(1);
    }
}

.route-building-popup::after {
    content: "";
    position: absolute;
    left: 50%;
    bottom: -11px;
    width: 22px;
    height: 22px;
    transform: translateX(-50%) rotate(45deg);
    background: rgba(255, 255, 255, 0.94);
    border-right: 1px solid rgba(226, 232, 240, 0.9);
    border-bottom: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 4px;
}

.route-building-popup-close {
    position: absolute;
    top: 8px;
    right: 9px;
    width: 26px;
    height: 26px;
    border: none;
    border-radius: 999px;
    background: transparent;
    color: #64748b;
    font-size: 20px;
    line-height: 1;
    cursor: pointer;
}

.route-building-popup-close:hover {
    background: rgba(15, 23, 42, 0.06);
    color: #0f172a;
}

.route-building-popup-head {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding-right: 18px;
}

.route-building-popup-icon {
    font-size: 18px;
}

.route-building-popup-title {
    font-size: 15px;
    font-weight: 900;
    color: #1e293b;
    text-align: center;
}

.route-building-popup-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.55), transparent);
    margin: 12px 4px 13px;
}

.route-building-popup-btn {
    width: 100%;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.07em;
    cursor: pointer;
    padding: 7px 6px;
    border-radius: 12px;
    font-family: inherit;
}

.route-building-popup-btn:hover {
    color: #0f766e;
    background: rgba(20, 184, 166, 0.08);
}

.route-building-popup-hint {
    margin-top: 6px;
    text-align: center;
    color: #94a3b8;
    font-size: 10px;
    line-height: 1.4;
}

.route-building-popup.route-popup-ai {
    background:
        radial-gradient(circle at top right, rgba(16, 163, 127, 0.10), transparent 36%),
        rgba(255, 255, 255, 0.94);
}

@media (max-width: 768px) {
    .route-building-popup {
        width: min(290px, calc(100vw - 24px));
        padding: 15px 16px 17px;
    }
}
</style>


<style>
/* =========================================================
   INDOOR FRONT MODE FIX
   Indoor panel stays in front; floating controls/status/popups move back.
========================================================= */

body.indoor-open #floating-route-ui,
body.indoor-open #route-status-bubble,
body.indoor-open #route-building-popup,
body.indoor-open .leaflet-control-container {
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
    transform: translateY(10px) scale(0.98) !important;
    transition: opacity 0.18s ease, transform 0.18s ease, visibility 0.18s ease !important;
}

body.indoor-open .floating-modal-backdrop {
    display: none !important;
}

body.indoor-open #map {
    filter: blur(2px) brightness(0.96);
}

.indoor-backdrop {
    z-index: 120000 !important;
    background: rgba(2, 6, 23, 0.52) !important;
    backdrop-filter: blur(10px) !important;
    -webkit-backdrop-filter: blur(10px) !important;
}

.indoor-panel {
    z-index: 120010 !important;
    width: min(1180px, 96vw) !important;
    height: min(820px, 94vh) !important;
    border-radius: 26px !important;
    box-shadow:
        0 34px 90px rgba(2, 8, 23, 0.34),
        0 0 0 1px rgba(255,255,255,0.60) inset !important;
}

.indoor-panel.active {
    display: block !important;
    animation: indoorFrontPop 0.22s ease;
}

@keyframes indoorFrontPop {
    from {
        opacity: 0;
        transform: translate(-50%, -48%) scale(0.975);
    }

    to {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
}

/* Make indoor UI cleaner and readable when it is in front */
.indoor-header {
    position: relative;
    z-index: 5;
    background: rgba(255, 255, 255, 0.96) !important;
}

.indoor-toolbar {
    position: relative;
    z-index: 5;
    background: rgba(248, 250, 252, 0.96) !important;
}

.indoor-sidebar,
.indoor-main,
.indoor-map-wrap,
#indoorMap {
    position: relative;
    z-index: 1;
}

#indoorMap .leaflet-control-container {
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
    transform: none !important;
}

body.indoor-open .indoor-panel .leaflet-control-container {
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
    transform: none !important;
}

@media (max-width: 768px) {
    .indoor-panel {
        width: 98vw !important;
        height: 94vh !important;
        border-radius: 22px !important;
    }

    body.indoor-open #map {
        filter: blur(1px) brightness(0.96);
    }
}
</style>


<style>
/* =========================================================
   INDOOR ROOM MAP FOCUS MODE
   Removes left room list/search and turns floor dropdown into big buttons.
========================================================= */

.indoor-panel {
    width: min(1240px, 98vw) !important;
    height: min(880px, 96vh) !important;
}

.indoor-header {
    min-height: 72px;
}

.indoor-floor-toolbar {
    display: flex !important;
    justify-content: center;
    align-items: center;
    gap: 12px;
    padding: 14px 18px !important;
    min-height: 76px;
    background:
        linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(241, 245, 249, 0.96)) !important;
}

.indoor-floor-select-hidden,
.indoor-room-search-hidden {
    display: none !important;
}

.indoor-floor-buttons {
    width: min(720px, 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.indoor-floor-btn {
    border: 1px solid rgba(37, 99, 235, 0.18);
    border-radius: 18px;
    min-width: 132px;
    padding: 15px 22px;
    background:
        linear-gradient(180deg, rgba(255,255,255,0.98), rgba(239,246,255,0.96));
    color: #1e293b;
    font-family: inherit;
    font-size: 15px;
    font-weight: 900;
    cursor: pointer;
    box-shadow:
        0 12px 26px rgba(15, 23, 42, 0.08),
        inset 0 1px 0 rgba(255,255,255,0.90);
    transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease, background 0.16s ease;
}

.indoor-floor-btn:hover {
    transform: translateY(-2px);
    border-color: rgba(37, 99, 235, 0.34);
    box-shadow:
        0 16px 32px rgba(37, 99, 235, 0.12),
        inset 0 1px 0 rgba(255,255,255,0.95);
}

.indoor-floor-btn.active {
    color: #ffffff;
    border-color: rgba(37, 99, 235, 0.70);
    background:
        radial-gradient(circle at top left, rgba(34,211,238,0.20), transparent 34%),
        linear-gradient(180deg, #3b82f6, #1d4ed8);
    box-shadow:
        0 18px 38px rgba(37, 99, 235, 0.28),
        0 0 0 4px rgba(37, 99, 235, 0.10),
        inset 0 1px 0 rgba(255,255,255,0.20);
}

.indoor-body {
    display: block !important;
    height: calc(100% - 148px) !important;
}

.indoor-sidebar {
    display: none !important;
}

.indoor-main {
    width: 100% !important;
    height: 100% !important;
    min-width: 0;
}

.indoor-map-wrap {
    height: calc(100% - 48px) !important;
    min-height: 560px;
    background: #f8fafc;
}

#indoorMap {
    width: 100% !important;
    height: 100% !important;
}

.indoor-footer {
    min-height: 48px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px !important;
}

/* More readable room labels/features when map is bigger */
.room-item {
    display: none !important;
}

@media (max-width: 768px) {
    .indoor-panel {
        width: 100vw !important;
        height: 100vh !important;
        border-radius: 0 !important;
    }

    .indoor-header {
        min-height: 68px;
        padding: 12px 14px !important;
    }

    .indoor-title {
        font-size: 16px !important;
    }

    .indoor-subtitle {
        font-size: 11px !important;
    }

    .indoor-close {
        padding: 10px 13px !important;
        border-radius: 14px !important;
    }

    .indoor-floor-toolbar {
        padding: 10px 12px !important;
        min-height: 72px;
        overflow-x: auto;
        justify-content: flex-start;
    }

    .indoor-floor-buttons {
        width: max-content;
        min-width: 100%;
        justify-content: flex-start;
        flex-wrap: nowrap;
        gap: 10px;
        padding-bottom: 2px;
    }

    .indoor-floor-btn {
        min-width: 112px;
        padding: 14px 18px;
        font-size: 14px;
        flex: 0 0 auto;
    }

    .indoor-body {
        height: calc(100% - 140px) !important;
    }

    .indoor-map-wrap {
        height: calc(100% - 44px) !important;
        min-height: 0;
    }

    .indoor-footer {
        min-height: 44px;
        font-size: 11px !important;
        overflow-x: auto;
        white-space: nowrap;
    }
}
</style>


<style>
/* =========================================================
   FINAL MOBILE FRIENDLY PATCH
   Responsive floating buttons, modals, status box, indoor map.
========================================================= */

html,
body {
    width: 100%;
    height: 100%;
    overflow: hidden;
    overscroll-behavior: none;
}

button,
select,
input {
    -webkit-tap-highlight-color: transparent;
}

#map {
    width: 100vw !important;
    height: 100dvh !important;
}

/* ---------- Floating Navigator ---------- */
#floating-route-ui {
    max-width: calc(100vw - 16px) !important;
}

.floating-start-bar {
    max-width: calc(100vw - 16px) !important;
}

.floating-mode-btn,
.floating-action-btn,
.ai-search-submit,
.ai-record-inline-btn,
.ai-stop-voice,
.ai-record-again-btn,
.route-btn,
.indoor-close,
.indoor-floor-btn {
    touch-action: manipulation;
}

/* ---------- Modals ---------- */
.floating-modal-backdrop {
    padding: 12px !important;
    align-items: center !important;
    justify-content: center !important;
}

.floating-modal-card {
    max-height: min(680px, calc(100dvh - 24px)) !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch;
}

/* ---------- Status bubble ---------- */
#route-status-bubble {
    max-height: 40dvh;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

/* ---------- Indoor front priority ---------- */
body.indoor-open #floating-route-ui,
body.indoor-open #route-status-bubble,
body.indoor-open #route-building-popup,
body.indoor-open .floating-modal-backdrop {
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
}

/* ---------- Indoor Panel Responsive ---------- */
.indoor-panel {
    max-width: 100vw !important;
    max-height: 100dvh !important;
}

.indoor-floor-toolbar {
    overflow-x: auto !important;
    overflow-y: hidden !important;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}

.indoor-floor-buttons {
    flex-wrap: nowrap !important;
}

.indoor-map-wrap {
    min-height: 0 !important;
}

#indoorMap {
    min-height: 0 !important;
}

.indoor-footer {
    overflow-x: auto;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
}

/* ---------- Tablet ---------- */
@media (max-width: 900px) {
    #route-status-bubble {
        top: 12px !important;
        right: 12px !important;
        width: min(330px, calc(100vw - 24px)) !important;
        font-size: 11px !important;
        line-height: 1.55 !important;
        padding: 12px 14px !important;
        border-radius: 18px !important;
    }

    #floating-route-ui {
        bottom: max(12px, env(safe-area-inset-bottom)) !important;
        gap: 8px !important;
    }

    .floating-ai-badge {
        font-size: 9px !important;
        padding: 6px 10px !important;
    }

    .floating-main-pin {
        width: 92px !important;
        height: 92px !important;
    }

    .pin-disc {
        width: 72px !important;
        height: 72px !important;
    }

    .pin-icon {
        width: 31px !important;
        height: 44px !important;
    }

    .pin-hole {
        width: 11px !important;
        height: 11px !important;
        top: 12px !important;
    }

    .ai-orb-shell {
        min-height: 96px !important;
    }

    .floating-start-bar {
        padding: 8px !important;
        gap: 8px !important;
        border-radius: 22px !important;
    }

    .floating-mode-btn {
        flex: 1 1 0 !important;
        min-width: 0 !important;
        padding: 12px 9px !important;
        font-size: 11px !important;
        border-radius: 15px !important;
        white-space: nowrap;
    }

    .floating-action-card {
        width: min(360px, calc(100vw - 22px)) !important;
        bottom: 104px !important;
        padding: 15px !important;
        border-radius: 22px !important;
    }

    .floating-action-title {
        font-size: 14px !important;
    }

    .floating-action-btn {
        padding: 12px 14px !important;
        font-size: 13px !important;
        border-radius: 15px !important;
    }

    .ai-transform-panel {
        width: min(520px, calc(100vw - 20px)) !important;
        padding: 15px !important;
        border-radius: 24px !important;
    }

    .ai-search-row {
        grid-template-columns: 1fr !important;
        gap: 9px !important;
    }

    .ai-search-input,
    .ai-search-submit,
    .ai-record-inline-btn {
        width: 100% !important;
        min-height: 48px !important;
    }

    .ai-voice-core {
        grid-template-columns: 56px 1fr !important;
        gap: 12px !important;
    }

    .ai-voice-orb {
        width: 56px !important;
        height: 56px !important;
    }

    .ai-voice-button-row {
        grid-template-columns: 1fr !important;
    }

    .route-building-popup {
        width: min(290px, calc(100vw - 24px)) !important;
    }
}

/* ---------- Phone portrait ---------- */
@media (max-width: 600px) {
    #map {
        height: 100dvh !important;
    }

    #route-status-bubble {
        top: 8px !important;
        left: 8px !important;
        right: 8px !important;
        width: auto !important;
        max-height: 25dvh !important;
        padding: 10px 12px !important;
        font-size: 10px !important;
        border-radius: 16px !important;
    }

    .route-status-pill {
        font-size: 9px !important;
        padding: 5px 9px !important;
    }

    #floating-route-ui {
        width: calc(100vw - 12px) !important;
        bottom: max(8px, env(safe-area-inset-bottom)) !important;
    }

    .floating-ai-badge {
        display: none !important;
    }

    .ai-orb-shell {
        min-height: 80px !important;
    }

    .floating-main-pin {
        width: 78px !important;
        height: 78px !important;
    }

    .pin-disc {
        width: 61px !important;
        height: 61px !important;
    }

    .pin-icon {
        width: 26px !important;
        height: 37px !important;
    }

    .pin-hole {
        width: 9px !important;
        height: 9px !important;
        top: 10px !important;
    }

    .floating-start-bar {
        width: calc(100vw - 12px) !important;
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        padding: 7px !important;
        gap: 6px !important;
        border-radius: 18px !important;
    }

    .floating-mode-btn {
        min-width: 0 !important;
        width: 100% !important;
        padding: 11px 5px !important;
        font-size: 9.5px !important;
        border-radius: 13px !important;
        letter-spacing: 0 !important;
    }

    .floating-action-card {
        bottom: 86px !important;
        width: calc(100vw - 18px) !important;
        padding: 14px !important;
        border-radius: 20px !important;
    }

    .floating-action-kicker {
        font-size: 9px !important;
    }

    .floating-action-title {
        font-size: 13px !important;
    }

    .floating-action-btn {
        min-height: 46px !important;
        padding: 11px 12px !important;
        font-size: 12px !important;
        border-radius: 14px !important;
    }

    .ai-transform-panel {
        width: calc(100vw - 16px) !important;
        padding: 14px !important;
        border-radius: 20px !important;
        max-height: 58dvh !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    .ai-transform-kicker {
        font-size: 9px !important;
        margin-bottom: 10px !important;
    }

    .ai-search-input {
        font-size: 13px !important;
        min-height: 46px !important;
        border-radius: 15px !important;
    }

    .ai-transform-hint,
    .ai-text-result-note,
    .ai-voice-result-note,
    .floating-voice-status,
    .floating-heard-text {
        font-size: 10.5px !important;
    }

    .ai-text-result-card,
    .ai-voice-result-card {
        padding: 10px 12px !important;
        border-radius: 15px !important;
    }

    .floating-modal-backdrop {
        padding: 8px !important;
        align-items: flex-end !important;
    }

    .floating-modal-card {
        width: 100% !important;
        max-height: 86dvh !important;
        border-radius: 24px 24px 18px 18px !important;
        padding: 18px !important;
    }

    .floating-modal-title {
        font-size: 17px !important;
    }

    .floating-modal-subtitle {
        font-size: 11px !important;
    }

    .route-select,
    .route-input {
        min-height: 48px !important;
        font-size: 13px !important;
        border-radius: 14px !important;
    }

    .floating-modal-actions {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 9px !important;
    }

    .route-btn {
        width: 100% !important;
        min-height: 46px !important;
        font-size: 12px !important;
        border-radius: 14px !important;
    }

    .route-building-popup {
        width: calc(100vw - 28px) !important;
        max-width: 300px !important;
        padding: 14px 15px 16px !important;
    }

    /* Indoor becomes real fullscreen on phone */
    .indoor-backdrop.active {
        background: rgba(2, 6, 23, 0.62) !important;
    }

    .indoor-panel {
        top: 0 !important;
        left: 0 !important;
        transform: none !important;
        width: 100vw !important;
        height: 100dvh !important;
        max-width: 100vw !important;
        max-height: 100dvh !important;
        border-radius: 0 !important;
        border: none !important;
        display: none;
    }

    .indoor-panel.active {
        display: flex !important;
        flex-direction: column !important;
    }

    .indoor-header {
        flex: 0 0 auto !important;
        min-height: 62px !important;
        padding: max(10px, env(safe-area-inset-top)) 12px 10px !important;
        gap: 10px !important;
    }

    .indoor-title {
        font-size: 15px !important;
        line-height: 1.2 !important;
    }

    .indoor-subtitle {
        font-size: 10px !important;
    }

    .indoor-close {
        min-width: 68px !important;
        min-height: 42px !important;
        padding: 9px 12px !important;
        border-radius: 14px !important;
        font-size: 12px !important;
    }

    .indoor-floor-toolbar {
        flex: 0 0 auto !important;
        min-height: 64px !important;
        padding: 9px 10px !important;
        justify-content: flex-start !important;
    }

    .indoor-floor-buttons {
        width: max-content !important;
        min-width: 100% !important;
        justify-content: flex-start !important;
        gap: 8px !important;
    }

    .indoor-floor-btn {
        flex: 0 0 auto !important;
        min-width: 102px !important;
        min-height: 46px !important;
        padding: 12px 15px !important;
        font-size: 13px !important;
        border-radius: 15px !important;
    }

    .indoor-body {
        flex: 1 1 auto !important;
        display: block !important;
        height: auto !important;
        min-height: 0 !important;
    }

    .indoor-sidebar {
        display: none !important;
    }

    .indoor-main {
        height: 100% !important;
        min-height: 0 !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .indoor-map-wrap {
        flex: 1 1 auto !important;
        height: auto !important;
        min-height: 0 !important;
    }

    #indoorMap {
        height: 100% !important;
        min-height: 0 !important;
    }

    .indoor-footer {
        flex: 0 0 auto !important;
        min-height: 42px !important;
        padding: 8px 10px max(8px, env(safe-area-inset-bottom)) !important;
        font-size: 10px !important;
    }

    .indoor-badge {
        font-size: 9px !important;
        padding: 4px 8px !important;
    }

    .indoor-room-label-pill {
        max-width: 92px !important;
        padding: 5px 7px !important;
    }

    .indoor-room-label-name {
        font-size: 9px !important;
    }

    .leaflet-control-zoom a {
        width: 34px !important;
        height: 34px !important;
        line-height: 34px !important;
    }

    .leaflet-popup-content {
        max-width: 230px !important;
        font-size: 12px !important;
    }
}

/* ---------- Very small phones ---------- */
@media (max-width: 380px) {
    .floating-mode-btn {
        font-size: 8.5px !important;
        padding-left: 3px !important;
        padding-right: 3px !important;
    }

    .floating-main-pin {
        width: 70px !important;
        height: 70px !important;
    }

    .pin-disc {
        width: 55px !important;
        height: 55px !important;
    }

    .indoor-floor-btn {
        min-width: 92px !important;
        font-size: 12px !important;
    }

    .route-status-pill {
        font-size: 8px !important;
    }
}

/* ---------- Landscape phones ---------- */
@media (max-height: 520px) and (orientation: landscape) {
    #route-status-bubble {
        display: none !important;
    }

    #floating-route-ui {
        bottom: 6px !important;
        transform: translateX(-50%) scale(0.88) !important;
        transform-origin: bottom center !important;
    }

    .ai-orb-shell {
        min-height: 68px !important;
    }

    .floating-main-pin {
        width: 68px !important;
        height: 68px !important;
    }

    .pin-disc {
        width: 52px !important;
        height: 52px !important;
    }

    .floating-start-bar {
        padding: 6px !important;
    }

    .floating-mode-btn {
        min-height: 38px !important;
        padding: 8px 10px !important;
    }

    .indoor-panel.active {
        display: flex !important;
        flex-direction: column !important;
    }

    .indoor-header {
        min-height: 52px !important;
        padding: 8px 12px !important;
    }

    .indoor-floor-toolbar {
        min-height: 54px !important;
        padding: 6px 10px !important;
    }

    .indoor-floor-btn {
        min-height: 38px !important;
        padding: 8px 14px !important;
    }

    .indoor-footer {
        display: none !important;
    }
}
</style>


<style>
/* =========================================================
   MOBILE OVERLAP FIX
   Fixes legend/control overlap with the bottom AI navigator on phones.
========================================================= */

@media (max-width: 768px) {
    /*
    |--------------------------------------------------------------------------
    | Hide desktop legend on mobile.
    |--------------------------------------------------------------------------
    | The legend was sitting behind the AI orb and bottom buttons.
    */
    .premium-legend,
    .legend-title,
    .legend-item {
        display: none !important;
    }

    /*
    |--------------------------------------------------------------------------
    | Keep Leaflet controls away from the bottom navigator.
    |--------------------------------------------------------------------------
    */
    .leaflet-bottom.leaflet-left,
    .leaflet-bottom.leaflet-right {
        bottom: 118px !important;
    }

    .leaflet-control-attribution {
        display: none !important;
    }

    /*
    |--------------------------------------------------------------------------
    | Put navigator above everything else and keep it inside the screen.
    |--------------------------------------------------------------------------
    */
    #floating-route-ui {
        z-index: 250000 !important;
        width: calc(100vw - 12px) !important;
        max-width: calc(100vw - 12px) !important;
        left: 50% !important;
        right: auto !important;
        bottom: max(8px, env(safe-area-inset-bottom)) !important;
        transform: translateX(-50%) !important;
        gap: 6px !important;
    }

    .ai-orb-shell {
        width: 100% !important;
        min-height: 74px !important;
        order: 1;
    }

    .floating-start-bar {
        order: 2;
        width: 100% !important;
        max-width: 100% !important;
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 6px !important;
        padding: 6px !important;
        border-radius: 18px !important;
        overflow: hidden !important;
    }

    .floating-mode-btn {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        flex: none !important;
        padding: 10px 4px !important;
        font-size: 9px !important;
        line-height: 1.1 !important;
        border-radius: 13px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: clip !important;
    }

    .floating-main-pin {
        width: 72px !important;
        height: 72px !important;
    }

    .pin-disc {
        width: 56px !important;
        height: 56px !important;
    }

    .pin-icon {
        width: 24px !important;
        height: 34px !important;
    }

    .pin-hole {
        width: 9px !important;
        height: 9px !important;
        top: 9px !important;
    }

    .floating-action-card {
        bottom: 82px !important;
        width: calc(100vw - 18px) !important;
        max-width: calc(100vw - 18px) !important;
    }

    .ai-transform-panel {
        width: calc(100vw - 18px) !important;
        max-width: calc(100vw - 18px) !important;
    }

    /*
    |--------------------------------------------------------------------------
    | When indoor panel is open, hide all outdoor/mobile floating controls.
    |--------------------------------------------------------------------------
    */
    body.indoor-open #floating-route-ui,
    body.indoor-open #route-status-bubble,
    body.indoor-open #route-building-popup,
    body.indoor-open .premium-legend {
        display: none !important;
    }
}

@media (max-width: 380px) {
    .floating-mode-btn {
        font-size: 8px !important;
        padding-left: 2px !important;
        padding-right: 2px !important;
    }

    .floating-main-pin {
        width: 66px !important;
        height: 66px !important;
    }

    .pin-disc {
        width: 52px !important;
        height: 52px !important;
    }
}
</style>


<style>
/* =========================================================
   MOBILE AI ORB PIN ICON FIX
   Fixes broken/shrunk location pin inside the round AI button.
========================================================= */

.pin-icon {
    clip-path: none !important;
    width: 34px !important;
    height: 34px !important;
    border-radius: 50% 50% 50% 8px !important;
    background:
        radial-gradient(circle at 38% 38%, rgba(255,255,255,0.95) 0 18%, transparent 19%),
        linear-gradient(180deg, #f8fbff 0%, #dbeafe 100%) !important;
    transform: rotate(-45deg) !important;
    display: block !important;
    position: relative !important;
    box-shadow:
        0 8px 18px rgba(2, 8, 23, 0.18),
        inset 0 1px 0 rgba(255,255,255,0.72) !important;
}

.pin-hole {
    display: none !important;
}

.pin-icon::after {
    content: "";
    position: absolute;
    width: 11px;
    height: 11px;
    border-radius: 999px;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    background: linear-gradient(180deg, #22d3ee, #10b981);
    box-shadow:
        0 0 0 4px rgba(16, 185, 129, 0.10),
        0 0 14px rgba(34, 211, 238, 0.38);
}

@media (max-width: 768px) {
    .pin-icon {
        width: 30px !important;
        height: 30px !important;
        border-radius: 50% 50% 50% 7px !important;
    }

    .pin-icon::after {
        width: 10px;
        height: 10px;
    }
}

@media (max-width: 380px) {
    .pin-icon {
        width: 27px !important;
        height: 27px !important;
        border-radius: 50% 50% 50% 6px !important;
    }

    .pin-icon::after {
        width: 9px;
        height: 9px;
    }
}
</style>


<style>
/* =========================================================
   MOBILE INDOOR MAP SPACE PATCH
   Gives indoor map more visible space on phones.
========================================================= */

@media (max-width: 600px) {
    .indoor-header {
        min-height: 54px !important;
        padding-top: max(8px, env(safe-area-inset-top)) !important;
        padding-bottom: 8px !important;
    }

    .indoor-title {
        font-size: 14px !important;
    }

    .indoor-subtitle {
        font-size: 9.5px !important;
    }

    .indoor-floor-toolbar {
        min-height: 56px !important;
        padding: 7px 8px !important;
    }

    .indoor-floor-btn {
        min-width: 96px !important;
        min-height: 42px !important;
        padding: 10px 13px !important;
        font-size: 12px !important;
    }

    .indoor-footer {
        min-height: 34px !important;
        padding-top: 6px !important;
        padding-bottom: max(6px, env(safe-area-inset-bottom)) !important;
        font-size: 9.5px !important;
    }

    .indoor-map-wrap {
        flex: 1 1 auto !important;
        min-height: calc(100dvh - 150px) !important;
    }

    #indoorMap {
        height: 100% !important;
    }
}
</style>

<style>
/* =========================================================
   LANDUSE LABEL REMOVE PATCH
   Hide landuse center labels like Open Field Area and multipurpose.
   This keeps landuse polygons, image overlays, popups, and routing active.
========================================================= */
.landuse-label {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
}
</style>

<!-- ROUTE STATUS BUBBLE REMOVED / HIDDEN FALLBACK -->
<style>
    #route-status-bubble,
    body.indoor-open #route-status-bubble {
        display: none !important;
    }

    /*
    |--------------------------------------------------------------------------
    | ROUTE BUILDING POPUP - MAP ANCHORED + MOBILE FRIENDLY CTA
    |--------------------------------------------------------------------------
    | This popup is now a Leaflet popup anchored above the selected building.
    | The old fixed popup is hidden so it will not follow the phone screen.
    */
    #route-building-popup {
        display: none !important;
    }

    .route-building-map-popup {
        z-index: 120050 !important;
    }

    .route-building-map-popup .leaflet-popup-content-wrapper {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(248, 250, 252, 0.97)) !important;
        backdrop-filter: blur(18px) !important;
        -webkit-backdrop-filter: blur(18px) !important;
        border-radius: 24px !important;
        border: 1px solid rgba(37, 99, 235, 0.20) !important;
        box-shadow:
            0 24px 60px rgba(15, 23, 42, 0.24),
            0 0 0 1px rgba(255, 255, 255, 0.75) inset,
            0 0 28px rgba(37, 99, 235, 0.14) !important;
        padding: 0 !important;
        overflow: hidden !important;
    }

    .route-building-map-popup .leaflet-popup-content {
        margin: 0 !important;
        width: 100% !important;
    }

    .route-building-map-popup .leaflet-popup-tip {
        background: rgba(255, 255, 255, 0.98) !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.18) !important;
    }

    .route-building-map-popup .leaflet-popup-close-button {
        width: 32px !important;
        height: 32px !important;
        right: 10px !important;
        top: 10px !important;
        color: #64748b !important;
        font-size: 23px !important;
        line-height: 30px !important;
        border-radius: 999px !important;
        z-index: 5 !important;
        background: rgba(241, 245, 249, 0.85) !important;
        transition: 0.18s ease !important;
    }

    .route-building-map-popup .leaflet-popup-close-button:hover {
        background: #e2e8f0 !important;
        color: #0f172a !important;
    }

    .route-building-map-popup-inner {
        width: 315px;
        max-width: calc(100vw - 28px);
        padding: 18px 18px 16px;
        text-align: center;
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #0f172a;
    }

    .route-building-map-popup-kicker {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 10px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(37, 99, 235, 0.10);
        color: #1d4ed8;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.09em;
        text-transform: uppercase;
    }

    .route-building-map-popup-pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.55);
        animation: indoorPopupPulse 1.35s infinite;
    }

    @keyframes indoorPopupPulse {
        0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.55); }
        70% { box-shadow: 0 0 0 9px rgba(34, 197, 94, 0); }
        100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    .route-building-map-popup-head {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 0 32px 0 12px;
        margin-bottom: 12px;
    }

    .route-building-map-popup-icon {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: linear-gradient(135deg, #dbeafe, #dcfce7);
        font-size: 18px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }

    .route-building-map-popup-title-wrap {
        min-width: 0;
        text-align: left;
    }

    .route-building-map-popup-title {
        display: block;
        color: #0f172a;
        font-size: 15px;
        font-weight: 900;
        line-height: 1.25;
        word-break: break-word;
    }

    .route-building-map-popup-subtitle {
        display: block;
        margin-top: 2px;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
    }

    .route-building-map-popup-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.38), transparent);
        margin: 0 -18px 14px;
    }

    .route-building-map-popup-btn {
        position: relative;
        width: 100%;
        min-height: 58px;
        border: none;
        border-radius: 18px;
        background: linear-gradient(135deg, #2563eb, #16a34a);
        color: #ffffff;
        font-size: 13px;
        font-weight: 950;
        letter-spacing: 0.035em;
        cursor: pointer;
        padding: 13px 16px;
        font-family: inherit;
        box-shadow:
            0 16px 32px rgba(37, 99, 235, 0.28),
            0 8px 20px rgba(22, 163, 74, 0.18),
            inset 0 1px 0 rgba(255, 255, 255, 0.28);
        transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
        overflow: hidden;
        touch-action: manipulation;
        animation: indoorButtonAttention 1.9s ease-in-out infinite;
    }

    .route-building-map-popup-btn::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, transparent 0%, rgba(255,255,255,0.28) 45%, transparent 70%);
        transform: translateX(-120%);
        animation: indoorButtonShine 2.4s ease-in-out infinite;
    }

    .route-building-map-popup-btn:hover {
        transform: translateY(-2px);
        box-shadow:
            0 20px 38px rgba(37, 99, 235, 0.34),
            0 10px 24px rgba(22, 163, 74, 0.22);
        filter: saturate(1.08);
    }

    .route-building-map-popup-btn:active {
        transform: scale(0.98);
    }

    .route-building-map-popup-btn-main {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        line-height: 1.15;
    }

    .route-building-map-popup-btn-icon {
        width: 28px;
        height: 28px;
        flex: 0 0 28px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.22);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }

    .route-building-map-popup-btn-text {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }

    .route-building-map-popup-btn-text strong {
        font-size: 13px;
        font-weight: 950;
        letter-spacing: 0.035em;
    }

    .route-building-map-popup-btn-text small {
        margin-top: 2px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0;
        opacity: 0.88;
    }

    .route-building-map-popup-hint {
        margin-top: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        font-size: 11px;
        color: #475569;
        font-weight: 700;
        line-height: 1.45;
    }

    .route-building-map-popup-hint-icon {
        width: 20px;
        height: 20px;
        flex: 0 0 20px;
        border-radius: 999px;
        background: #eff6ff;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    @keyframes indoorButtonAttention {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-2px); }
    }

    @keyframes indoorButtonShine {
        0% { transform: translateX(-130%); }
        45%, 100% { transform: translateX(130%); }
    }

    @media (max-width: 768px) {
        .route-building-map-popup {
            margin-bottom: 10px !important;
        }

        .route-building-map-popup-inner {
            width: min(342px, calc(100vw - 24px));
            padding: 18px 16px 15px;
        }

        .route-building-map-popup .leaflet-popup-content-wrapper {
            border-radius: 24px !important;
        }

        .route-building-map-popup-head {
            padding-right: 34px;
            margin-bottom: 13px;
        }

        .route-building-map-popup-title {
            font-size: 14px;
        }

        .route-building-map-popup-subtitle {
            font-size: 10px;
        }

        .route-building-map-popup-btn {
            min-height: 64px;
            border-radius: 20px;
            padding: 14px 15px;
        }

        .route-building-map-popup-btn-icon {
            width: 32px;
            height: 32px;
            font-size: 17px;
        }

        .route-building-map-popup-btn-text strong {
            font-size: 13px;
        }

        .route-building-map-popup-btn-text small {
            font-size: 10px;
        }

        .route-building-map-popup-hint {
            font-size: 10.5px;
        }
    }

    @media (max-width: 380px) {
        .route-building-map-popup-inner {
            width: min(318px, calc(100vw - 18px));
            padding: 16px 13px 13px;
        }

        .route-building-map-popup-btn {
            min-height: 62px;
            padding: 12px 12px;
        }

        .route-building-map-popup-btn-text strong {
            font-size: 12px;
        }

        .route-building-map-popup-btn-text small {
            font-size: 9.5px;
        }
    }



    /* LIVE GREEN ROUTE STYLE */
    .route-line-live {
        filter: drop-shadow(0 0 8px rgba(22, 163, 74, 0.38));
    }

    .route-line-live-indoor {
        filter: drop-shadow(0 0 8px rgba(22, 163, 74, 0.30));
    }


    .running-route-arrow {
        color: var(--route-arrow-color, #16a34a);
        font-size: 24px;
        font-weight: 900;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        text-shadow:
            0 0 3px #ffffff,
            0 0 8px rgba(22, 163, 74, 0.65),
            0 3px 8px rgba(15, 23, 42, 0.25);
        transform-origin: center center;
        will-change: transform;
        pointer-events: none;
    }

    .route-line-live,
    .route-line-live-indoor {
        filter: drop-shadow(0 0 8px rgba(22, 163, 74, 0.45));
    }

</style>


<style>
/* =========================================================
   FINAL MOBILE FIX: INDOOR ROOMS CTA AS BOTTOM SHEET
   Mobile only: do NOT use the wide Leaflet popup because it can be
   clipped on small screens like iPhone SE. Desktop keeps map popup.
========================================================= */
@media (max-width: 768px) {
    .route-building-map-popup {
        display: none !important;
    }

    #route-building-popup.mobile-active {
        display: block !important;
        position: fixed !important;
        left: 10px !important;
        right: 10px !important;
        top: auto !important;
        bottom: calc(86px + env(safe-area-inset-bottom)) !important;
        transform: none !important;
        z-index: 120500 !important;
        width: auto !important;
        max-width: none !important;
        min-width: 0 !important;
        padding: 12px !important;
        border-radius: 24px !important;
        background: rgba(255, 255, 255, 0.98) !important;
        border: 1px solid rgba(37, 99, 235, 0.22) !important;
        box-shadow:
            0 18px 45px rgba(15, 23, 42, 0.26),
            0 0 0 1px rgba(255, 255, 255, 0.76) inset !important;
        backdrop-filter: blur(18px) !important;
        -webkit-backdrop-filter: blur(18px) !important;
    }

    #route-building-popup.mobile-active .route-building-popup-close {
        position: absolute !important;
        top: 8px !important;
        right: 8px !important;
        width: 32px !important;
        height: 32px !important;
        border: none !important;
        border-radius: 999px !important;
        background: #f1f5f9 !important;
        color: #334155 !important;
        font-size: 22px !important;
        font-weight: 900 !important;
        line-height: 28px !important;
        cursor: pointer !important;
        z-index: 2 !important;
    }

    #route-building-popup.mobile-active::before {
        content: "";
        position: absolute;
        left: 50%;
        top: 7px;
        width: 44px;
        height: 5px;
        border-radius: 999px;
        background: #cbd5e1;
        transform: translateX(-50%);
    }

    #route-building-popup.mobile-active .route-building-popup-head {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 16px 38px 8px 4px !important;
    }

    #route-building-popup.mobile-active .route-building-popup-icon {
        width: 42px !important;
        height: 42px !important;
        flex: 0 0 42px !important;
        border-radius: 16px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: linear-gradient(135deg, #dbeafe, #dcfce7) !important;
        font-size: 20px !important;
    }

    #route-building-popup.mobile-active .route-building-popup-title {
        color: #0f172a !important;
        font-size: 15px !important;
        line-height: 1.2 !important;
        font-weight: 950 !important;
        text-align: left !important;
        padding-right: 4px !important;
    }

    #route-building-popup.mobile-active .route-building-popup-title::before {
        content: "● INDOOR AVAILABLE";
        display: block;
        width: max-content;
        max-width: 100%;
        margin-bottom: 5px;
        padding: 5px 9px;
        border-radius: 999px;
        background: rgba(37, 99, 235, 0.10);
        color: #1d4ed8;
        font-size: 9px;
        font-weight: 950;
        letter-spacing: 0.08em;
        white-space: nowrap;
    }

    #route-building-popup.mobile-active .route-building-popup-divider {
        display: none !important;
    }

    #route-building-popup.mobile-active .route-building-popup-btn {
        position: relative !important;
        width: 100% !important;
        min-height: 58px !important;
        margin-top: 6px !important;
        border: none !important;
        border-radius: 20px !important;
        background: linear-gradient(135deg, #2563eb, #16a34a) !important;
        color: #ffffff !important;
        font-family: inherit !important;
        cursor: pointer !important;
        padding: 12px 14px !important;
        box-shadow:
            0 14px 28px rgba(37, 99, 235, 0.30),
            0 7px 18px rgba(22, 163, 74, 0.20) !important;
        overflow: hidden !important;
        touch-action: manipulation !important;
        animation: mobileIndoorCtaBounce 1.75s ease-in-out infinite !important;
    }

    #route-building-popup.mobile-active .mobile-indoor-cta-main {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 11px;
        width: 100%;
        min-width: 0;
    }

    #route-building-popup.mobile-active .mobile-indoor-cta-icon {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.22);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    #route-building-popup.mobile-active .mobile-indoor-cta-text {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        min-width: 0;
        text-align: left;
        line-height: 1.1;
    }

    #route-building-popup.mobile-active .mobile-indoor-cta-text strong {
        font-size: 13px;
        font-weight: 950;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }

    #route-building-popup.mobile-active .mobile-indoor-cta-text small {
        margin-top: 3px;
        font-size: 10px;
        font-weight: 800;
        opacity: 0.9;
        white-space: nowrap;
    }

    #route-building-popup.mobile-active .route-building-popup-hint {
        margin-top: 9px !important;
        padding: 0 4px !important;
        color: #475569 !important;
        font-size: 10.5px !important;
        line-height: 1.35 !important;
        font-weight: 750 !important;
        text-align: center !important;
    }

    @keyframes mobileIndoorCtaBounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-2px); }
    }
}

@media (max-width: 380px) {
    #route-building-popup.mobile-active {
        left: 8px !important;
        right: 8px !important;
        bottom: calc(82px + env(safe-area-inset-bottom)) !important;
        padding: 10px !important;
        border-radius: 22px !important;
    }

    #route-building-popup.mobile-active .route-building-popup-head {
        gap: 8px !important;
        padding-top: 15px !important;
    }

    #route-building-popup.mobile-active .route-building-popup-icon {
        width: 38px !important;
        height: 38px !important;
        flex-basis: 38px !important;
    }

    #route-building-popup.mobile-active .route-building-popup-title {
        font-size: 14px !important;
    }

    #route-building-popup.mobile-active .route-building-popup-btn {
        min-height: 56px !important;
        padding: 11px 12px !important;
    }

    #route-building-popup.mobile-active .mobile-indoor-cta-icon {
        width: 31px;
        height: 31px;
        flex-basis: 31px;
        font-size: 16px;
    }

    #route-building-popup.mobile-active .mobile-indoor-cta-text strong {
        font-size: 12px;
    }

    #route-building-popup.mobile-active .mobile-indoor-cta-text small {
        font-size: 9px;
    }
}
</style>

<style>
/* =========================================================
   FINAL FIX: MOBILE POPUP ABOVE BUILDING, NOT BOTTOM SHEET
   This overrides the previous mobile bottom CTA. The indoor button
   stays anchored above the selected building but is compact enough
   for small phones like iPhone SE.
========================================================= */
@media (max-width: 768px) {
    #route-building-popup,
    #route-building-popup.mobile-active {
        display: none !important;
    }

    .route-building-map-popup {
        display: block !important;
        z-index: 120800 !important;
    }

    .route-building-map-popup .leaflet-popup-content-wrapper {
        border-radius: 20px !important;
        max-width: calc(100vw - 36px) !important;
        overflow: hidden !important;
    }

    .route-building-map-popup .leaflet-popup-content {
        width: auto !important;
        max-width: calc(100vw - 36px) !important;
        margin: 0 !important;
    }

    .route-building-map-popup-inner {
        width: min(250px, calc(100vw - 46px)) !important;
        max-width: calc(100vw - 46px) !important;
        padding: 12px 12px 11px !important;
    }

    .route-building-map-popup-kicker {
        margin-bottom: 7px !important;
        padding: 5px 8px !important;
        font-size: 8.5px !important;
        letter-spacing: 0.07em !important;
    }

    .route-building-map-popup-head {
        gap: 7px !important;
        padding: 0 30px 0 4px !important;
        margin-bottom: 8px !important;
    }

    .route-building-map-popup-icon {
        width: 31px !important;
        height: 31px !important;
        flex-basis: 31px !important;
        border-radius: 12px !important;
        font-size: 15px !important;
    }

    .route-building-map-popup-title {
        font-size: 13px !important;
        line-height: 1.15 !important;
        max-width: 160px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    .route-building-map-popup-subtitle {
        font-size: 9.5px !important;
        margin-top: 1px !important;
    }

    .route-building-map-popup-divider {
        margin: 0 -12px 10px !important;
    }

    .route-building-map-popup-btn {
        min-height: 48px !important;
        border-radius: 16px !important;
        padding: 10px 11px !important;
    }

    .route-building-map-popup-btn-main {
        gap: 8px !important;
    }

    .route-building-map-popup-btn-icon {
        width: 27px !important;
        height: 27px !important;
        flex-basis: 27px !important;
        font-size: 14px !important;
    }

    .route-building-map-popup-btn-text strong {
        font-size: 11.5px !important;
        white-space: nowrap !important;
    }

    .route-building-map-popup-btn-text small {
        font-size: 8.8px !important;
        white-space: nowrap !important;
    }

    .route-building-map-popup-hint {
        margin-top: 8px !important;
        font-size: 9px !important;
        line-height: 1.25 !important;
    }

    .route-building-map-popup-hint-icon {
        width: 17px !important;
        height: 17px !important;
        flex-basis: 17px !important;
        font-size: 10px !important;
    }

    .route-building-map-popup .leaflet-popup-close-button {
        width: 28px !important;
        height: 28px !important;
        right: 7px !important;
        top: 7px !important;
        font-size: 20px !important;
        line-height: 26px !important;
    }
}

@media (max-width: 380px) {
    .route-building-map-popup-inner {
        width: min(238px, calc(100vw - 42px)) !important;
        padding: 11px 11px 10px !important;
    }

    .route-building-map-popup-title {
        max-width: 148px !important;
        font-size: 12.5px !important;
    }

    .route-building-map-popup-btn-text strong {
        font-size: 11px !important;
    }

    .route-building-map-popup-btn-text small {
        font-size: 8.5px !important;
    }
}
</style>

<style>
/* =========================================================
   MOBILE MAP DRAG FIX FOR INDOOR POPUP ABOVE BUILDING
   Problem: popup/card can catch touch events, so the map cannot drag.
   Fix: popup container lets drag pass through, but the real buttons remain clickable.
========================================================= */
@media (max-width: 768px) {
    .route-building-map-popup,
    .route-building-map-popup .leaflet-popup-content-wrapper,
    .route-building-map-popup .leaflet-popup-content,
    .route-building-map-popup .route-building-map-popup-inner,
    .route-building-map-popup .route-building-map-popup-head,
    .route-building-map-popup .route-building-map-popup-kicker,
    .route-building-map-popup .route-building-map-popup-divider,
    .route-building-map-popup .route-building-map-popup-hint,
    .route-building-map-popup .leaflet-popup-tip {
        pointer-events: none !important;
        touch-action: pan-x pan-y !important;
    }

    .route-building-map-popup .route-building-map-popup-btn,
    .route-building-map-popup .leaflet-popup-close-button {
        pointer-events: auto !important;
        touch-action: manipulation !important;
    }

    .route-building-map-popup .leaflet-popup-content-wrapper {
        max-width: calc(100vw - 26px) !important;
    }

    .route-building-map-popup-inner {
        max-width: calc(100vw - 28px) !important;
    }
}
</style>


<style>
/* =========================================================
   FINAL FIX: popup above building but map can still drag on mobile
   Only the OPEN INDOOR ROOMS button and X close are touchable.
   The rest of the popup becomes transparent to touch/drag.
========================================================= */
@media (max-width: 768px) {
    #route-building-popup,
    #route-building-popup.mobile-active {
        display: none !important;
    }

    .leaflet-popup-pane .route-building-map-popup {
        display: block !important;
        pointer-events: none !important;
        touch-action: pan-x pan-y !important;
    }

    .leaflet-popup-pane .route-building-map-popup .leaflet-popup-content-wrapper,
    .leaflet-popup-pane .route-building-map-popup .leaflet-popup-content,
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-inner,
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-kicker,
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-head,
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-title-wrap,
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-title,
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-subtitle,
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-divider,
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-hint,
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-hint-icon,
    .leaflet-popup-pane .route-building-map-popup .leaflet-popup-tip {
        pointer-events: none !important;
        touch-action: pan-x pan-y !important;
        -webkit-user-select: none !important;
        user-select: none !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-btn,
    .leaflet-popup-pane .route-building-map-popup .leaflet-popup-close-button {
        pointer-events: auto !important;
        touch-action: manipulation !important;
        -webkit-user-select: none !important;
        user-select: none !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-btn * {
        pointer-events: none !important;
    }

    .leaflet-popup-pane .route-building-map-popup .leaflet-popup-content-wrapper {
        max-width: calc(100vw - 28px) !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-inner {
        width: min(248px, calc(100vw - 42px)) !important;
        max-width: calc(100vw - 42px) !important;
    }
}
</style>

<style>
/* =========================================================
   INDOOR ROUTE MOVING ARROW ANIMATION
   Green glowing route + visible moving arrows inside indoor map
========================================================= */
.route-line-live-indoor {
    stroke-linecap: round !important;
    stroke-linejoin: round !important;
    filter:
        drop-shadow(0 0 4px rgba(34, 197, 94, 0.65))
        drop-shadow(0 0 12px rgba(34, 197, 94, 0.35));
}

.running-route-arrow-indoor {
    color: #16a34a;
    font-size: 20px;
    font-weight: 950;
    line-height: 1;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-shadow:
        0 0 4px #ffffff,
        0 0 9px rgba(34, 197, 94, 0.95),
        0 3px 8px rgba(15, 23, 42, 0.35);
    transform-origin: center center;
    will-change: transform;
    pointer-events: none;
}

.running-route-arrow-indoor::before {
    content: "";
    position: absolute;
    width: 22px;
    height: 22px;
    border-radius: 999px;
    background: rgba(34, 197, 94, 0.16);
    box-shadow: 0 0 14px rgba(34, 197, 94, 0.50);
    z-index: -1;
}

.indoor-route-live-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 5px 10px;
    margin-left: 6px;
    background: #dcfce7;
    color: #15803d;
    font-size: 11px;
    font-weight: 900;
}

.indoor-route-live-label::before {
    content: "➤";
    font-size: 11px;
    animation: indoorLiveArrowHint 1s linear infinite;
}

@keyframes indoorLiveArrowHint {
    0% { transform: translateX(-2px); opacity: 0.55; }
    50% { transform: translateX(2px); opacity: 1; }
    100% { transform: translateX(-2px); opacity: 0.55; }
}

@media (max-width: 768px) {
    .running-route-arrow-indoor {
        font-size: 18px;
    }

    .running-route-arrow-indoor::before {
        width: 20px;
        height: 20px;
    }
}
</style>


<style>
/* FINAL FIX: make indoor moving arrows visible above rooms/floorplan */
.leaflet-marker-icon .running-route-arrow-indoor,
.running-route-arrow-indoor {
    color: #16a34a !important;
    font-size: 24px !important;
    font-weight: 950 !important;
    z-index: 99999 !important;
    transform-origin: center center !important;
    pointer-events: none !important;
    filter: drop-shadow(0 0 3px #ffffff) drop-shadow(0 0 8px rgba(22, 163, 74, 0.95));
}

.leaflet-marker-icon:has(.running-route-arrow-indoor) {
    z-index: 99999 !important;
}

@media (max-width: 768px) {
    .leaflet-marker-icon .running-route-arrow-indoor,
    .running-route-arrow-indoor {
        font-size: 22px !important;
    }
}
</style>


<style>
    /* =========================================================
       HAZARD ICON - SAME SCREEN SIZE / NO SCALE
       - Severity 1 = White
       - Severity 2 = Yellow
       - Severity 3 = Red
       - Same pixel size in zoom in and zoom out.
       - No transform scale.
    ========================================================= */
    .hazard-pin-wrap {
        width: 34px;
        height: 42px;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        position: relative;
        overflow: visible;
        pointer-events: auto;
    }

    .hazard-pin {
        --hazard-bg: #ffffff;
        --hazard-fg: #0f172a;

        width: 28px;
        height: 28px;
        border-radius: 999px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--hazard-bg);
        color: var(--hazard-fg);
        border: 2px solid #ffffff;
        box-shadow:
            0 8px 18px rgba(15, 23, 42, 0.30),
            inset 0 1px 0 rgba(255, 255, 255, 0.55);
        font-size: 16px;
        font-weight: 900;
        line-height: 1;
    }

    .hazard-pin::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: -7px;
        width: 12px;
        height: 12px;
        background: var(--hazard-bg);
        border-right: 2px solid #ffffff;
        border-bottom: 2px solid #ffffff;
        transform: translateX(-50%) rotate(45deg);
        border-bottom-right-radius: 3px;
        box-shadow: 4px 4px 8px rgba(15, 23, 42, 0.10);
    }

    .hazard-pin.severity-1 {
        --hazard-bg: #ffffff;
        --hazard-fg: #0f172a;
        border-color: #e2e8f0;
    }

    .hazard-pin.severity-1::after {
        border-color: #e2e8f0;
    }

    .hazard-pin.severity-2 {
        --hazard-bg: #facc15;
        --hazard-fg: #713f12;
    }

    .hazard-pin.severity-3 {
        --hazard-bg: #dc2626;
        --hazard-fg: #ffffff;
    }

    .hazard-pin-symbol {
        position: relative;
        z-index: 2;
        transform: translateY(-1px);
        user-select: none;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .hazard-popup-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        margin-top: 8px;
    }

    .hazard-popup-badge.severity-1 {
        background: #f8fafc;
        color: #0f172a;
        border: 1px solid #e2e8f0;
    }

    .hazard-popup-badge.severity-2 {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
    }

    .hazard-popup-badge.severity-3 {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
</style>

<style>
/* =========================================================
   FINAL FIX: OPEN INDOOR ROOMS POPUP ZOOM-SCALE
   Purpose: kung mag zoom out ka, mo gamay ang popup/button para
   dili niya matabunan ang route. Ang button ug X close clickable gihapon.
========================================================= */
.route-building-map-popup {
    --route-popup-scale: 1;
}

.leaflet-popup-pane .route-building-map-popup .leaflet-popup-content-wrapper {
    transform: scale(var(--route-popup-scale)) !important;
    transform-origin: bottom center !important;
    will-change: transform !important;
}

.leaflet-popup-pane .route-building-map-popup .leaflet-popup-tip-container {
    transform: scale(var(--route-popup-scale)) !important;
    transform-origin: top center !important;
    will-change: transform !important;
}

.leaflet-popup-pane .route-building-map-popup .leaflet-popup-content {
    margin: 0 !important;
}

.leaflet-popup-pane .route-building-map-popup .route-building-map-popup-inner {
    width: 285px !important;
    max-width: 285px !important;
}

.leaflet-popup-pane .route-building-map-popup .route-building-map-popup-btn {
    min-height: 42px !important;
}

.leaflet-popup-pane .route-building-map-popup.zoom-out-small .leaflet-popup-content-wrapper {
    opacity: 0.92 !important;
}

@media (max-width: 768px) {
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-inner {
        width: 230px !important;
        max-width: 230px !important;
        padding: 10px 10px 9px !important;
    }

    .leaflet-popup-pane .route-building-map-popup .leaflet-popup-content-wrapper {
        border-radius: 14px !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-kicker,
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-hint {
        font-size: 8.5px !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-head {
        gap: 6px !important;
        margin-bottom: 7px !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-title {
        font-size: 11px !important;
        max-width: 132px !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-subtitle {
        font-size: 8px !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-icon {
        width: 24px !important;
        height: 24px !important;
        flex-basis: 24px !important;
        font-size: 13px !important;
        border-radius: 10px !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-btn {
        min-height: 34px !important;
        padding: 7px 9px !important;
        border-radius: 11px !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-btn-icon {
        width: 22px !important;
        height: 22px !important;
        flex-basis: 22px !important;
        font-size: 13px !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-btn-text strong {
        font-size: 9.5px !important;
        line-height: 1.1 !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-btn-text small {
        font-size: 7.5px !important;
        line-height: 1.1 !important;
    }

    .leaflet-popup-pane .route-building-map-popup .leaflet-popup-close-button {
        transform: scale(var(--route-popup-scale)) !important;
        transform-origin: center center !important;
    }
}


/* =========================================================
   FIX: Custom X inside scaled OPEN INDOOR ROOMS popup
   - Default Leaflet close button is hidden/disabled in JS
   - This custom X follows the popup scale during zoom out
========================================================= */
.leaflet-popup-pane .route-building-map-popup .route-building-map-popup-inner {
    position: relative !important;
}

.leaflet-popup-pane .route-building-map-popup .route-building-map-popup-custom-close {
    position: absolute !important;
    top: -8px !important;
    right: -8px !important;
    z-index: 30 !important;

    width: 22px !important;
    height: 22px !important;
    border: 1px solid rgba(148, 163, 184, 0.35) !important;
    border-radius: 999px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    background: rgba(255, 255, 255, 0.96) !important;
    color: #64748b !important;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14) !important;

    font-size: 14px !important;
    font-weight: 900 !important;
    line-height: 1 !important;
    cursor: pointer !important;
    pointer-events: auto !important;
    touch-action: manipulation !important;
}

.leaflet-popup-pane .route-building-map-popup .route-building-map-popup-custom-close:hover {
    background: #eff6ff !important;
    color: #2563eb !important;
}

.leaflet-popup-pane .route-building-map-popup .leaflet-popup-close-button {
    display: none !important;
}

@media (max-width: 768px) {
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-custom-close {
        top: -7px !important;
        right: -7px !important;
        width: 20px !important;
        height: 20px !important;
        font-size: 13px !important;
    }
}



/* =========================================================
   FINAL FIX: Visible X close button for scaled route popup
   - Button is INSIDE the card, not outside, so it won't be clipped
   - Higher contrast so visible on white map/background
========================================================= */
.leaflet-popup-pane .route-building-map-popup .route-building-map-popup-inner {
    position: relative !important;
    overflow: visible !important;
    padding-top: 18px !important;
}

.leaflet-popup-pane .route-building-map-popup .route-building-map-popup-custom-close {
    position: absolute !important;
    top: 7px !important;
    right: 7px !important;
    z-index: 99999 !important;

    width: 24px !important;
    height: 24px !important;
    min-width: 24px !important;
    min-height: 24px !important;
    padding: 0 !important;
    margin: 0 !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    border: 2px solid rgba(255, 255, 255, 0.95) !important;
    border-radius: 999px !important;
    background: #0f172a !important;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.28) !important;

    font-size: 17px !important;
    font-weight: 900 !important;
    line-height: 1 !important;
    text-align: center !important;
    cursor: pointer !important;
    pointer-events: auto !important;
    touch-action: manipulation !important;
    opacity: 1 !important;
    visibility: visible !important;
}

.leaflet-popup-pane .route-building-map-popup .route-building-map-popup-custom-close:hover,
.leaflet-popup-pane .route-building-map-popup .route-building-map-popup-custom-close:focus {
    background: #dc2626 !important;
    color: #ffffff !important;
    outline: none !important;
}

.leaflet-popup-pane .route-building-map-popup .leaflet-popup-close-button {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
}

@media (max-width: 768px) {
    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-inner {
        padding-top: 18px !important;
    }

    .leaflet-popup-pane .route-building-map-popup .route-building-map-popup-custom-close {
        top: 6px !important;
        right: 6px !important;
        width: 23px !important;
        height: 23px !important;
        min-width: 23px !important;
        min-height: 23px !important;
        font-size: 16px !important;
    }
}



    /*
    |--------------------------------------------------------------------------
    | ROUTE ARROW ANIMATION REMOVED
    |--------------------------------------------------------------------------
    | Safety CSS: if an old marker class remains from browser cache or old code,
    | keep it hidden. The route itself stays visible as a static green line.
    */
    .running-route-arrow,
    .running-route-arrow-indoor,
    .leaflet-marker-icon .running-route-arrow,
    .leaflet-marker-icon .running-route-arrow-indoor,
    .indoor-route-live-label {
        display: none !important;
    }
</style>



<style>
/* =========================================================
   TAP-ONLY PICK PATH ENHANCEMENT
   Mobile-friendly: user taps anywhere, route start snaps to path.
========================================================= */
body.pick-path-active #map {
    cursor: pointer !important;
}

body.pick-path-active .leaflet-container {
    cursor: pointer !important;
}

body.pick-path-active .path-interactive {
    filter: drop-shadow(0 0 10px rgba(34, 197, 94, 0.55)) !important;
}

.pick-path-helper {
    position: fixed;
    left: 50%;
    bottom: calc(118px + env(safe-area-inset-bottom));
    transform: translateX(-50%);
    z-index: 10045;
    width: min(430px, calc(100vw - 22px));
    padding: 14px;
    border-radius: 26px;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.94), rgba(2, 6, 23, 0.96));
    border: 1px solid rgba(255, 255, 255, 0.16);
    box-shadow: 0 24px 60px rgba(2, 8, 23, 0.42);
    color: #ffffff;
    overflow: hidden;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}

.pick-path-helper.tap-only {
    animation: pickHelperRise 0.24s ease-out;
}

.pick-helper-glow {
    position: absolute;
    width: 190px;
    height: 190px;
    right: -76px;
    top: -90px;
    background: radial-gradient(circle, rgba(34, 197, 94, 0.48), transparent 68%);
    pointer-events: none;
}

.pick-helper-top {
    position: relative;
    display: grid;
    grid-template-columns: 48px 1fr;
    gap: 12px;
    align-items: center;
}

.pick-helper-icon {
    width: 48px;
    height: 48px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    background: rgba(34, 197, 94, 0.18);
    border: 1px solid rgba(74, 222, 128, 0.28);
    font-size: 23px;
    box-shadow: inset 0 0 20px rgba(34, 197, 94, 0.18);
    animation: tapIconPulse 1.25s ease-in-out infinite;
}

.pick-helper-title {
    font-size: 15px;
    font-weight: 950;
    letter-spacing: -0.01em;
}

.pick-helper-subtitle {
    margin-top: 3px;
    font-size: 11px;
    line-height: 1.55;
    color: rgba(226, 232, 240, 0.88);
}

.pick-helper-tap-demo {
    position: relative;
    margin-top: 12px;
    min-height: 44px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.10);
    color: rgba(240, 253, 244, 0.94);
    font-size: 11px;
    font-weight: 900;
}

.tap-demo-dot {
    width: 14px;
    height: 14px;
    border-radius: 999px;
    background: #22c55e;
    border: 3px solid #ffffff;
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.55);
    animation: tapDemoPulse 1.2s infinite;
}

.pick-helper-actions {
    position: relative;
    display: grid;
    grid-template-columns: 1fr;
    gap: 9px;
    margin-top: 10px;
}

.pick-helper-actions.single {
    grid-template-columns: 1fr;
}

.pick-helper-btn {
    border: none;
    min-height: 44px;
    border-radius: 17px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 900;
    cursor: pointer;
    touch-action: manipulation;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.pick-helper-btn:active {
    transform: scale(0.98);
}

.pick-helper-btn.ghost {
    color: #e2e8f0;
    background: rgba(255, 255, 255, 0.10);
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.pick-tap-ripple {
    position: absolute;
    z-index: 10020;
    width: 18px;
    height: 18px;
    margin-left: -9px;
    margin-top: -9px;
    border-radius: 999px;
    pointer-events: none;
    border: 3px solid rgba(34, 197, 94, 0.95);
    background: rgba(34, 197, 94, 0.20);
    box-shadow: 0 0 0 10px rgba(34, 197, 94, 0.16);
    animation: pickTapRipple 0.68s ease-out forwards;
}

.route-start-arrow {
    position: relative;
}

.route-start-arrow::after {
    content: '';
    position: absolute;
    left: 50%;
    top: 50%;
    width: 34px;
    height: 34px;
    transform: translate(-50%, -50%);
    border-radius: 999px;
    border: 2px solid rgba(34, 197, 94, 0.55);
    box-shadow: 0 0 0 8px rgba(34, 197, 94, 0.12);
    pointer-events: none;
}

@keyframes pickHelperRise {
    from { transform: translateX(-50%) translateY(16px); opacity: 0; }
    to { transform: translateX(-50%) translateY(0); opacity: 1; }
}

@keyframes tapIconPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.08); }
}

@keyframes tapDemoPulse {
    0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.58); }
    70% { box-shadow: 0 0 0 12px rgba(34, 197, 94, 0); }
    100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}

@keyframes pickTapRipple {
    from { transform: scale(0.8); opacity: 1; }
    to { transform: scale(3.5); opacity: 0; }
}

@media (min-width: 769px) {
    .pick-path-helper {
        bottom: 152px;
    }
}

@media (max-width: 768px) {
    body.pick-path-active #floating-route-ui {
        opacity: 0.16 !important;
        transform: translateX(-50%) translateY(10px) scale(0.96) !important;
        pointer-events: none !important;
    }

    .pick-path-helper {
        bottom: max(16px, env(safe-area-inset-bottom)) !important;
    }
}
</style>

<style>
/* =========================================================
   CAMPUS EVENT NOTIFICATION BELL + MOBILE FRIENDLY DRAWER
   Small top-right bell. Events show only after click/tap.
========================================================= */
.campus-event-notification-wrap {
    position: fixed;
    top: max(14px, env(safe-area-inset-top));
    right: calc(max(14px, env(safe-area-inset-right)) + 62px);
    z-index: 9400;
    font-family: 'Plus Jakarta Sans', sans-serif;
    pointer-events: auto;
}

.campus-event-bell-btn {
    position: relative;
    width: 48px;
    height: 48px;
    border: none;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.94);
    color: #0f172a;
    box-shadow:
        0 14px 34px rgba(15, 23, 42, 0.18),
        0 0 0 1px rgba(226, 232, 240, 0.92) inset;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.campus-event-bell-btn:hover {
    transform: translateY(-1px);
    box-shadow:
        0 18px 38px rgba(15, 23, 42, 0.22),
        0 0 0 1px rgba(37, 99, 235, 0.16) inset;
}

.campus-event-bell-icon {
    font-size: 21px;
    line-height: 1;
}

.campus-event-bell-count {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 19px;
    height: 19px;
    padding: 0 5px;
    border-radius: 999px;
    background: #dc2626;
    color: white;
    border: 2px solid white;
    font-size: 10px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 16px rgba(220, 38, 38, 0.25);
}

.campus-event-bell-pulse {
    position: absolute;
    inset: -5px;
    border-radius: 999px;
    border: 2px solid rgba(37, 99, 235, 0.22);
    animation: campusBellPulse 1.8s ease-out infinite;
    pointer-events: none;
}

@keyframes campusBellPulse {
    0% {
        opacity: 0.85;
        transform: scale(0.9);
    }
    100% {
        opacity: 0;
        transform: scale(1.24);
    }
}

.campus-event-panel {
    position: absolute;
    top: 58px;
    right: 0;
    width: min(340px, calc(100vw - 28px));
    max-height: min(520px, calc(100vh - 92px));
    display: none;
    animation: campusEventDrop 0.18s ease both;
}

.campus-event-panel.open {
    display: block;
}

@keyframes campusEventDrop {
    from {
        opacity: 0;
        transform: translateY(-7px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.campus-event-panel-card {
    overflow: hidden;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(226, 232, 240, 0.92);
    box-shadow:
        0 24px 55px rgba(15, 23, 42, 0.20),
        0 0 0 1px rgba(255, 255, 255, 0.45) inset;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}

.campus-event-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    padding: 13px 14px 11px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.9);
    background: linear-gradient(135deg, rgba(239, 246, 255, 0.95), rgba(240, 253, 244, 0.95));
}

.campus-event-panel-kicker {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 4px;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #2563eb;
}

.campus-event-panel-title {
    font-size: 14px;
    font-weight: 900;
    color: #0f172a;
    line-height: 1.2;
}

.campus-event-panel-subtitle {
    margin-top: 3px;
    font-size: 10.5px;
    color: #64748b;
    line-height: 1.35;
}

.campus-event-panel-close {
    width: 30px;
    height: 30px;
    border: none;
    border-radius: 999px;
    background: rgba(226, 232, 240, 0.86);
    color: #0f172a;
    font-size: 18px;
    font-weight: 900;
    line-height: 1;
    cursor: pointer;
}

.campus-event-list {
    max-height: 390px;
    overflow: auto;
    padding: 10px;
    background: rgba(248, 250, 252, 0.68);
}

.campus-event-list::-webkit-scrollbar {
    width: 6px;
}

.campus-event-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

.campus-event-card {
    border-radius: 16px;
    padding: 10px;
    background: white;
    border: 1px solid rgba(226, 232, 240, 0.96);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
    margin-bottom: 9px;
}

.campus-event-card:last-child {
    margin-bottom: 0;
}

.campus-event-card.now-card {
    border-color: rgba(248, 113, 113, 0.45);
    background: linear-gradient(135deg, #fff, #fff7f7);
}

.campus-event-card.upcoming-card {
    border-color: rgba(96, 165, 250, 0.40);
    background: linear-gradient(135deg, #fff, #f8fbff);
}

.campus-event-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 7px;
}

.campus-event-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border-radius: 999px;
    padding: 4px 7px;
    font-size: 9.5px;
    font-weight: 900;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
}

.campus-event-status.now {
    background: #fee2e2;
    color: #b91c1c;
}

.campus-event-status.upcoming {
    background: #dbeafe;
    color: #1d4ed8;
}

.campus-event-target {
    font-size: 9.5px;
    font-weight: 900;
    color: #64748b;
    text-transform: uppercase;
    white-space: nowrap;
}

.campus-event-title {
    font-size: 12.5px;
    font-weight: 900;
    color: #0f172a;
    line-height: 1.25;
    margin-bottom: 4px;
}

.campus-event-place {
    font-size: 10.5px;
    color: #475569;
    line-height: 1.35;
    margin-bottom: 6px;
    font-weight: 700;
}

.campus-event-time {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    font-size: 10.5px;
    color: #334155;
    line-height: 1.42;
    margin-bottom: 8px;
}

.campus-event-route-btn {
    width: 100%;
    border: none;
    border-radius: 11px;
    padding: 7px 9px;
    font-size: 10.5px;
    font-weight: 900;
    color: white;
    background: linear-gradient(135deg, #16a34a, #2563eb);
    cursor: pointer;
    box-shadow: 0 8px 16px rgba(37, 99, 235, 0.16);
}

.campus-event-route-btn:hover {
    transform: translateY(-1px);
}

.campus-event-mini-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
    background: currentColor;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.12);
}

.campus-event-mini-dot.upcoming-dot {
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.campus-event-empty {
    padding: 18px 14px;
    text-align: center;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
}

@media (max-width: 768px) {
    .campus-event-notification-wrap {
        top: max(12px, env(safe-area-inset-top));
        right: calc(max(12px, env(safe-area-inset-right)) + 58px);
        z-index: 9600;
    }

    .campus-event-bell-btn {
        width: 42px;
        height: 42px;
    }

    .campus-event-bell-icon {
        font-size: 19px;
    }

    .campus-event-bell-count {
        min-width: 17px;
        height: 17px;
        font-size: 9px;
    }

    .campus-event-panel {
        position: fixed;
        top: 58px;
        right: 10px;
        width: min(310px, calc(100vw - 20px));
        max-height: min(430px, calc(100vh - 92px));
    }

    .campus-event-panel-card {
        border-radius: 18px;
    }

    .campus-event-panel-head {
        padding: 11px 12px 9px;
    }

    .campus-event-panel-title {
        font-size: 13px;
    }

    .campus-event-panel-subtitle {
        font-size: 10px;
    }

    .campus-event-list {
        max-height: min(335px, calc(100vh - 160px));
        padding: 8px;
    }

    .campus-event-card {
        padding: 9px;
        border-radius: 14px;
    }

    .campus-event-title {
        font-size: 11.5px;
    }

    .campus-event-place,
    .campus-event-time {
        font-size: 10px;
    }
}
</style>

<style>
/* =========================================================
   USER PROFILE ICON BESIDE NOTIFICATION ICON
   - Place beside the notification bell
   - Dropdown contains logout button
========================================================= */
.user-profile-wrap {
    position: fixed;
    top: max(14px, env(safe-area-inset-top));
    right: max(14px, env(safe-area-inset-right));
    z-index: 10060;
    font-family: 'Plus Jakarta Sans', sans-serif;
    pointer-events: auto;
}

.user-profile-btn {
    width: 48px;
    height: 48px;
    border: none;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.94);
    color: #0f172a;
    box-shadow:
        0 14px 34px rgba(15, 23, 42, 0.18),
        0 0 0 1px rgba(226, 232, 240, 0.92) inset;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.user-profile-btn:hover {
    transform: translateY(-1px);
    box-shadow:
        0 18px 38px rgba(15, 23, 42, 0.22),
        0 0 0 1px rgba(37, 99, 235, 0.18) inset;
}

.user-profile-btn.active {
    box-shadow:
        0 18px 38px rgba(37, 99, 235, 0.22),
        0 0 0 2px rgba(37, 99, 235, 0.22) inset;
}

.user-profile-icon {
    font-size: 22px;
    line-height: 1;
}

.user-profile-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 999px;
}

.user-profile-menu {
    position: absolute;
    top: 58px;
    right: 0;
    width: 260px;
    display: none;
    padding: 12px;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(226, 232, 240, 0.9);
    box-shadow: 0 24px 58px rgba(15, 23, 42, 0.20);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

.user-profile-menu.open {
    display: block;
    animation: userProfileMenuIn 0.18s ease-out;
}

.user-profile-info {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 8px 8px 12px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.9);
    margin-bottom: 10px;
}

.user-profile-avatar {
    width: 44px;
    height: 44px;
    border-radius: 999px;
    background: linear-gradient(135deg, #dbeafe, #eef2ff);
    color: #1d4ed8;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    font-size: 20px;
    flex: 0 0 auto;
}

.user-profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-profile-text {
    min-width: 0;
}

.user-profile-name {
    font-size: 13px;
    font-weight: 900;
    color: #0f172a;
    max-width: 170px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-profile-email {
    margin-top: 3px;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    max-width: 170px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-logout-form {
    margin: 0;
}

.user-logout-btn {
    width: 100%;
    border: none;
    border-radius: 15px;
    padding: 12px 13px;
    background: #fee2e2;
    color: #b91c1c;
    font-size: 13px;
    font-weight: 900;
    cursor: pointer;
    font-family: inherit;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: 0.18s ease;
}

.user-logout-btn:hover {
    background: #dc2626;
    color: #ffffff;
    transform: translateY(-1px);
}

@keyframes userProfileMenuIn {
    from {
        transform: translateY(-8px) scale(0.96);
        opacity: 0;
    }

    to {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .user-profile-wrap {
        top: max(12px, env(safe-area-inset-top));
        right: max(12px, env(safe-area-inset-right));
    }

    .user-profile-btn {
        width: 46px;
        height: 46px;
    }

    .user-profile-menu {
        width: min(250px, calc(100vw - 24px));
        top: 56px;
    }
}


/* =========================================================
   FINAL ALIGNMENT OVERRIDE: NOTIFICATION + PROFILE
   Profile = right, notification = left, same vertical line
========================================================= */
.campus-event-notification-wrap {
    top: max(14px, env(safe-area-inset-top)) !important;
    right: calc(max(14px, env(safe-area-inset-right)) + 62px) !important;
}

.user-profile-wrap {
    top: max(14px, env(safe-area-inset-top)) !important;
    right: max(14px, env(safe-area-inset-right)) !important;
}

@media (max-width: 768px) {
    .campus-event-notification-wrap {
        top: max(12px, env(safe-area-inset-top)) !important;
        right: calc(max(12px, env(safe-area-inset-right)) + 58px) !important;
    }

    .user-profile-wrap {
        top: max(12px, env(safe-area-inset-top)) !important;
        right: max(12px, env(safe-area-inset-right)) !important;
    }
}
</style>

<style>
/* =========================================================
   MODERN GREEN-BLUE CAMPUS GLASS MAP THEME
   Design/theme only. Building colors are not modified.
   Paste at the very bottom of user.style.style if you do not
   want to replace the full style file.
========================================================= */

:root {
    --campus-green: #0c7a43;
    --campus-green-dark: #075f35;
    --campus-blue: #173b8f;
    --campus-blue-soft: #2563eb;
    --campus-text: #183126;
    --campus-muted: #61776d;
    --campus-bg: #f5fbf8;
    --campus-white: #ffffff;
    --campus-line: rgba(11, 122, 67, 0.14);
    --campus-shadow: 0 22px 55px rgba(8, 45, 28, 0.16);
    --campus-shadow-soft: 0 12px 28px rgba(8, 45, 28, 0.10);

    --panel-bg: rgba(255, 255, 255, 0.92);
    --panel-border: rgba(11, 122, 67, 0.14);
    --text-dark: #183126;
    --text-mid: #475569;
    --text-soft: #61776d;
    --green: #0c7a43;
    --blue: #173b8f;
    --violet: #2563eb;
    --danger: #b42318;
    --warning: #f59e0b;
}

body {
    background:
        radial-gradient(circle at top left, rgba(23, 59, 143, 0.10), transparent 28%),
        radial-gradient(circle at bottom right, rgba(12, 122, 67, 0.14), transparent 34%),
        linear-gradient(180deg, #f8fcfa 0%, #f3faf6 100%) !important;
    color: var(--campus-text) !important;
}

#map {
    background: #edf7f2 !important;
}

.leaflet-container {
    font-family: 'Plus Jakarta Sans', sans-serif !important;
}

.leaflet-control-zoom {
    border: none !important;
    border-radius: 18px !important;
    overflow: hidden !important;
    box-shadow: var(--campus-shadow-soft) !important;
}

.leaflet-control-zoom a {
    width: 38px !important;
    height: 38px !important;
    line-height: 38px !important;
    border: none !important;
    color: var(--campus-green-dark) !important;
    background: rgba(255, 255, 255, 0.94) !important;
    font-weight: 900 !important;
}

.leaflet-control-zoom a:hover {
    background: rgba(12, 122, 67, 0.10) !important;
    color: var(--campus-green-dark) !important;
}

#floating-route-ui {
    gap: 14px !important;
}

.floating-ai-badge {
    padding: 9px 14px !important;
    border-radius: 999px !important;
    background: rgba(255, 255, 255, 0.92) !important;
    color: var(--campus-green-dark) !important;
    border: 1px solid var(--campus-line) !important;
    box-shadow: var(--campus-shadow-soft) !important;
    backdrop-filter: blur(14px) !important;
    -webkit-backdrop-filter: blur(14px) !important;
    font-size: 12px !important;
    font-weight: 900 !important;
    letter-spacing: 0.08em !important;
    text-transform: uppercase !important;
}

.ai-dot,
.badge-dot {
    background: #18b764 !important;
    box-shadow: 0 0 0 6px rgba(24, 183, 100, 0.16) !important;
}

.floating-main-pin {
    width: 160px !important;
    height: 160px !important;
}

.pin-disc {
    width: 142px !important;
    height: 142px !important;
    background: linear-gradient(135deg, var(--campus-green-dark), var(--campus-green)) !important;
    border: 4px solid rgba(255, 255, 255, 0.92) !important;
    box-shadow:
        inset 0 3px 0 rgba(255, 255, 255, 0.38),
        inset 0 -7px 0 rgba(0, 0, 0, 0.12),
        0 18px 42px rgba(12, 122, 67, 0.28) !important;
}

.pin-disc::after {
    border-color: rgba(255, 255, 255, 0.24) !important;
}

.pin-icon {
    background: #ffffff !important;
    width: 58px !important;
    height: 58px !important;
    box-shadow: 0 10px 20px rgba(8, 45, 28, 0.18) !important;
}

.pin-hole {
    width: 21px !important;
    height: 21px !important;
    top: 18.5px !important;
    left: 18.5px !important;
    background: var(--campus-green) !important;
}

.floating-main-pin:hover {
    transform: translateY(-3px) scale(1.03) !important;
}

.floating-main-pin.active .pin-disc {
    box-shadow:
        0 0 0 8px rgba(12, 122, 67, 0.16),
        0 18px 42px rgba(12, 122, 67, 0.32),
        inset 0 3px 0 rgba(255, 255, 255, 0.42),
        inset 0 -7px 0 rgba(0, 0, 0, 0.13) !important;
}

.floating-start-bar {
    padding: 9px !important;
    border-radius: 24px !important;
    background: rgba(255, 255, 255, 0.92) !important;
    border: 1px solid var(--campus-line) !important;
    box-shadow: var(--campus-shadow-soft) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
}

.floating-mode-btn {
    min-width: 132px !important;
    border-radius: 999px !important;
    padding: 12px 15px !important;
    font-size: 13px !important;
    font-weight: 900 !important;
    box-shadow: none !important;
}

.floating-mode-btn.pick {
    background: linear-gradient(135deg, #0f8f4f, #18b764) !important;
}

.floating-mode-btn.gps {
    background: linear-gradient(135deg, #173b8f, #2563eb) !important;
}

.floating-mode-btn.default {
    background: linear-gradient(135deg, #075f35, #0c7a43) !important;
}

.floating-mode-btn.active {
    outline: 4px solid rgba(12, 122, 67, 0.14) !important;
    transform: translateY(-1px) scale(1.02) !important;
}

.floating-action-card,
.ai-transform-panel,
.floating-modal-card {
    background: rgba(255, 255, 255, 0.95) !important;
    border: 1px solid var(--campus-line) !important;
    border-radius: 26px !important;
    box-shadow: var(--campus-shadow) !important;
    backdrop-filter: blur(18px) !important;
    -webkit-backdrop-filter: blur(18px) !important;
}

.floating-action-kicker,
.ai-transform-kicker {
    color: var(--campus-green-dark) !important;
    font-weight: 900 !important;
    letter-spacing: 0.08em !important;
    text-transform: uppercase !important;
}

.floating-action-title,
.ai-voice-title,
.floating-modal-title {
    color: var(--campus-text) !important;
    font-weight: 900 !important;
}

.floating-modal-subtitle,
.ai-transform-hint,
.floating-voice-status,
.floating-heard-text {
    color: var(--campus-muted) !important;
}

.floating-action-btn,
.ai-search-submit,
.ai-record-inline-btn,
.ai-stop-voice,
.ai-record-again-btn,
.route-btn.success {
    border-radius: 999px !important;
    font-weight: 900 !important;
    border: none !important;
    box-shadow: 0 12px 26px rgba(12, 122, 67, 0.18) !important;
}

.floating-action-btn,
.ai-search-submit,
.route-btn.success {
    background: linear-gradient(135deg, var(--campus-green-dark), var(--campus-green)) !important;
    color: #ffffff !important;
}

.floating-action-btn.dark,
.ai-record-inline-btn,
.ai-record-again-btn {
    background: linear-gradient(135deg, var(--campus-blue), #2563eb) !important;
    color: #ffffff !important;
}

.floating-action-btn.blue {
    background: linear-gradient(135deg, #0c7a43, #173b8f) !important;
    color: #ffffff !important;
}

.ai-stop-voice {
    background: linear-gradient(135deg, #b42318, #ef4444) !important;
    color: #ffffff !important;
}

.floating-action-btn:hover,
.ai-search-submit:hover,
.ai-record-inline-btn:hover,
.ai-stop-voice:hover,
.ai-record-again-btn:hover,
.route-btn:hover {
    transform: translateY(-2px) !important;
}

.route-select,
.route-input,
.ai-search-input,
.indoor-toolbar select,
.indoor-toolbar input {
    border-radius: 16px !important;
    border: 1px solid rgba(11, 122, 67, 0.18) !important;
    background: rgba(255, 255, 255, 0.96) !important;
    color: var(--campus-text) !important;
    box-shadow: 0 8px 18px rgba(8, 45, 28, 0.06) !important;
}

.route-select:focus,
.route-input:focus,
.ai-search-input:focus,
.indoor-toolbar select:focus,
.indoor-toolbar input:focus {
    outline: none !important;
    border-color: rgba(12, 122, 67, 0.60) !important;
    box-shadow: 0 0 0 4px rgba(12, 122, 67, 0.12) !important;
}

.ai-text-result-card,
.ai-voice-result-card,
.floating-heard-text,
.route-heard-text,
.route-status {
    background: rgba(245, 251, 248, 0.92) !important;
    border: 1px solid rgba(11, 122, 67, 0.12) !important;
    border-radius: 18px !important;
    color: var(--campus-text) !important;
}

.ai-text-result-label,
.ai-voice-result-label {
    color: var(--campus-green-dark) !important;
    font-weight: 900 !important;
}

.floating-modal-backdrop {
    background: rgba(8, 45, 28, 0.34) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
}

.route-btn.neutral {
    background: #edf5f1 !important;
    color: var(--campus-text) !important;
}

.route-btn.primary {
    background: linear-gradient(135deg, var(--campus-green-dark), var(--campus-green)) !important;
    color: #ffffff !important;
}

.route-btn.gps {
    background: linear-gradient(135deg, var(--campus-blue), #2563eb) !important;
    color: #ffffff !important;
}

.leaflet-popup-content-wrapper {
    background: rgba(255, 255, 255, 0.96) !important;
    border-radius: 22px !important;
    border: 1px solid rgba(11, 122, 67, 0.14) !important;
    box-shadow: 0 22px 48px rgba(8, 45, 28, 0.18) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
}

.leaflet-popup-tip {
    background: rgba(255, 255, 255, 0.96) !important;
}

.custom-popup-title {
    color: var(--campus-green-dark) !important;
    border-bottom: 1px solid rgba(11, 122, 67, 0.12) !important;
    font-weight: 900 !important;
}

.custom-popup-subtitle {
    color: var(--campus-muted) !important;
}

.premium-legend {
    background: rgba(255, 255, 255, 0.94) !important;
    border: 1px solid var(--campus-line) !important;
    border-radius: 22px !important;
    box-shadow: var(--campus-shadow-soft) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
}

.legend-title {
    color: var(--campus-green-dark) !important;
}

.legend-item {
    color: var(--campus-text) !important;
}

.indoor-backdrop {
    background: rgba(8, 45, 28, 0.32) !important;
    backdrop-filter: blur(8px) !important;
}

.indoor-panel {
    border-radius: 28px !important;
    border: 1px solid var(--campus-line) !important;
    background: rgba(255, 255, 255, 0.96) !important;
    box-shadow: 0 28px 70px rgba(8, 45, 28, 0.24) !important;
}

.indoor-header {
    background: linear-gradient(135deg, rgba(245, 251, 248, 0.98), rgba(238, 247, 242, 0.94)) !important;
    border-bottom: 1px solid rgba(11, 122, 67, 0.12) !important;
}

.indoor-title {
    color: var(--campus-green-dark) !important;
    font-weight: 900 !important;
}

.indoor-subtitle {
    color: var(--campus-muted) !important;
}

.indoor-close {
    border-radius: 999px !important;
    background: rgba(12, 122, 67, 0.10) !important;
    color: var(--campus-green-dark) !important;
}

.indoor-close:hover {
    background: var(--campus-green) !important;
    color: #ffffff !important;
}

.indoor-sidebar {
    background: #f5fbf8 !important;
    border-right: 1px solid rgba(11, 122, 67, 0.10) !important;
}

.indoor-sidebar-title {
    color: var(--campus-green-dark) !important;
    font-weight: 900 !important;
}

.room-item {
    border-radius: 18px !important;
    border: 1px solid rgba(11, 122, 67, 0.12) !important;
    box-shadow: 0 8px 20px rgba(8, 45, 28, 0.05) !important;
}

.room-item:hover,
.room-item.active {
    border-color: rgba(12, 122, 67, 0.42) !important;
    background: rgba(12, 122, 67, 0.07) !important;
}

.room-name {
    color: var(--campus-text) !important;
}

.room-meta {
    color: var(--campus-muted) !important;
}

.indoor-footer {
    background: #ffffff !important;
    color: var(--campus-muted) !important;
    border-top: 1px solid rgba(11, 122, 67, 0.10) !important;
}

.badge-blue {
    background: rgba(23, 59, 143, 0.10) !important;
    color: var(--campus-blue) !important;
}

.badge-green {
    background: rgba(12, 122, 67, 0.12) !important;
    color: var(--campus-green-dark) !important;
}

.badge-yellow {
    background: rgba(245, 158, 11, 0.16) !important;
    color: #92400e !important;
}

.pick-path-helper {
    background: rgba(255, 255, 255, 0.95) !important;
    color: var(--campus-text) !important;
    border: 1px solid var(--campus-line) !important;
    box-shadow: var(--campus-shadow) !important;
}

.pick-helper-title {
    color: var(--campus-green-dark) !important;
}

.pick-helper-subtitle,
.tap-demo-text {
    color: var(--campus-muted) !important;
}

.pick-helper-icon,
.tap-demo-dot {
    background: rgba(12, 122, 67, 0.12) !important;
    color: var(--campus-green-dark) !important;
}

.route-building-map-popup .leaflet-popup-content-wrapper {
    border: 1px solid rgba(11, 122, 67, 0.16) !important;
    box-shadow:
        0 24px 60px rgba(8, 45, 28, 0.22),
        0 0 0 1px rgba(255, 255, 255, 0.78) inset !important;
}

.route-building-map-popup-title,
.route-building-map-popup-kicker {
    color: var(--campus-green-dark) !important;
}

.route-building-map-popup-btn {
    background: linear-gradient(135deg, var(--campus-green-dark), var(--campus-green)) !important;
    border-radius: 999px !important;
}

@media (max-width: 768px) {
    #floating-route-ui {
        top: 14px !important;
        width: calc(100vw - 16px) !important;
        gap: 10px !important;
    }

    .floating-ai-badge {
        font-size: 10px !important;
        padding: 8px 12px !important;
    }

    .floating-start-bar {
        width: 100% !important;
        gap: 7px !important;
        padding: 7px !important;
        border-radius: 18px !important;
    }

    .floating-mode-btn {
        min-width: 0 !important;
        flex: 1 !important;
        font-size: 10px !important;
        padding: 10px 6px !important;
    }

    .floating-main-pin {
        width: 126px !important;
        height: 126px !important;
    }

    .pin-disc {
        width: 112px !important;
        height: 112px !important;
    }

    .pin-icon {
        width: 46px !important;
        height: 46px !important;
    }

    .pin-hole {
        width: 17px !important;
        height: 17px !important;
        top: 14.5px !important;
        left: 14.5px !important;
    }

    .floating-action-card,
    .ai-transform-panel {
        width: calc(100vw - 24px) !important;
        border-radius: 22px !important;
    }

    .floating-modal-card {
        border-radius: 22px !important;
    }

    .premium-legend {
        font-size: 11px !important;
        padding: 12px 14px !important;
    }
}
</style>

<style>
/* =========================================================
   FINAL CENTER + LOWER PANEL FIX
   For Text Search, Voice Search, and Browse Destination.
   Design/layout only. No routing algorithm or building color changes.
   Paste this at the VERY BOTTOM of user.style.style.
========================================================= */

/* Keep the bottom dock working but never above opened panels */
#floating-route-ui {
    z-index: 10040 !important;
    pointer-events: none !important;
}

#floating-route-ui > *,
#floating-route-ui .ai-orb-shell,
#floating-route-ui .ai-transform-panel,
#floating-route-ui .floating-start-bar,
#floating-route-ui .floating-main-pin {
    pointer-events: auto !important;
}

/* Prevent the orb shell from pulling panels upward/sideways */
.ai-orb-shell {
    position: static !important;
    display: contents !important;
}

/* Text Search and Voice Search: true fixed center, slightly lower for mobile comfort */
#ai-search-panel,
#ai-voice-panel {
    position: fixed !important;
    top: 54% !important;
    left: 50% !important;
    right: auto !important;
    bottom: auto !important;
    transform: translate(-50%, -50%) !important;

    width: min(360px, calc(100vw - 44px)) !important;
    max-height: calc(100dvh - 150px) !important;
    overflow-y: auto !important;

    margin: 0 !important;
    z-index: 25000 !important;
    border-radius: 28px !important;
}

/* Text search should stack neatly */
#ai-search-panel .ai-search-row {
    display: grid !important;
    grid-template-columns: 1fr !important;
    gap: 10px !important;
}

#ai-search-panel .ai-search-input,
#ai-search-panel .ai-search-submit,
#ai-search-panel .ai-record-inline-btn {
    width: 100% !important;
}

/* Voice search should stack neatly */
#ai-voice-panel .ai-voice-core {
    justify-content: center !important;
    text-align: center !important;
}

#ai-voice-panel .ai-voice-button-row {
    display: grid !important;
    grid-template-columns: 1fr !important;
    gap: 10px !important;
}

#ai-voice-panel .ai-stop-voice,
#ai-voice-panel .ai-record-again-btn {
    width: 100% !important;
}

/* Keep close button visible */
.ai-transform-close {
    position: absolute !important;
    top: 14px !important;
    right: 16px !important;
    z-index: 3 !important;
}

/* Browse Destination modal: center it and keep it above bottom buttons/orb */
#browseOptionsModal.floating-modal-backdrop,
#browseOptionsModal {
    position: fixed !important;
    inset: 0 !important;
    z-index: 30000 !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 18px !important;
    background: rgba(8, 45, 28, 0.38) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
}

#browseOptionsModal[style*="display: flex"],
#browseOptionsModal[style*="display:flex"] {
    display: flex !important;
}

#browseOptionsModal .floating-modal-card {
    position: relative !important;
    top: auto !important;
    left: auto !important;
    transform: none !important;

    width: min(430px, calc(100vw - 36px)) !important;
    max-height: calc(100dvh - 56px) !important;
    overflow-y: auto !important;

    margin: 0 auto !important;
    padding: 24px !important;
    border-radius: 28px !important;
    z-index: 30001 !important;
}

/* Browse buttons should stay inside the card, not under the bottom dock */
#browseOptionsModal .floating-modal-actions {
    position: static !important;
    display: grid !important;
    grid-template-columns: 1fr !important;
    gap: 10px !important;
    margin-top: 18px !important;
}

#browseOptionsModal .floating-modal-actions .route-btn {
    width: 100% !important;
    min-width: 0 !important;
    border-radius: 999px !important;
}

#browseOptionsModal .route-field {
    margin-bottom: 14px !important;
}

#browseOptionsModal .route-select {
    height: 50px !important;
}

/* Mobile tuning */
@media (max-width: 768px) {
    #ai-search-panel,
    #ai-voice-panel {
        top: 53% !important;
        width: calc(100vw - 44px) !important;
        max-height: calc(100dvh - 145px) !important;
        border-radius: 24px !important;
    }

    #browseOptionsModal {
        padding: 12px !important;
    }

    #browseOptionsModal .floating-modal-card {
        width: calc(100vw - 24px) !important;
        max-height: calc(100dvh - 36px) !important;
        padding: 22px 18px !important;
        border-radius: 24px !important;
    }
}

@media (max-width: 420px) {
    #ai-search-panel,
    #ai-voice-panel {
        top: 52% !important;
        width: calc(100vw - 34px) !important;
    }
}
</style>
<style>
/* =========================================================
   LOWER TEXT SEARCH / VOICE SEARCH PANEL FIX
   Purpose: place the Text Search and Voice Search panels lower,
   near the bottom route buttons, without changing routing logic.
   Paste this at the VERY BOTTOM of user.style.style.
========================================================= */

/* Keep the bottom route dock clickable */
#floating-route-ui {
    z-index: 10040 !important;
    pointer-events: none !important;
}

#floating-route-ui > *,
#floating-route-ui .ai-orb-shell,
#floating-route-ui .ai-transform-panel,
#floating-route-ui .floating-start-bar,
#floating-route-ui .floating-main-pin {
    pointer-events: auto !important;
}

/* Do not let the old orb wrapper position pull the panels upward */
.ai-orb-shell {
    position: static !important;
    display: contents !important;
}

/* Put Text Search and Voice Search above the bottom buttons */
#ai-search-panel,
#ai-voice-panel {
    position: fixed !important;

    top: auto !important;
    left: 50% !important;
    right: auto !important;
    bottom: 104px !important;

    transform: translateX(-50%) !important;

    width: min(360px, calc(100vw - 44px)) !important;
    max-height: calc(100dvh - 145px) !important;
    overflow-y: auto !important;

    margin: 0 !important;
    z-index: 25000 !important;
    border-radius: 28px !important;
}

/* Text search layout */
#ai-search-panel .ai-search-row {
    display: grid !important;
    grid-template-columns: 1fr !important;
    gap: 10px !important;
}

#ai-search-panel .ai-search-input,
#ai-search-panel .ai-search-submit,
#ai-search-panel .ai-record-inline-btn {
    width: 100% !important;
}

/* Voice search layout */
#ai-voice-panel .ai-voice-core {
    justify-content: center !important;
    text-align: center !important;
}

#ai-voice-panel .ai-voice-button-row {
    display: grid !important;
    grid-template-columns: 1fr !important;
    gap: 10px !important;
}

#ai-voice-panel .ai-stop-voice,
#ai-voice-panel .ai-record-again-btn {
    width: 100% !important;
}

/* Keep close button visible */
.ai-transform-close {
    position: absolute !important;
    top: 14px !important;
    right: 16px !important;
    z-index: 3 !important;
}

/* Browse Destination modal stays centered and above the bottom buttons */
#browseOptionsModal.floating-modal-backdrop,
#browseOptionsModal {
    position: fixed !important;
    inset: 0 !important;
    z-index: 30000 !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 18px !important;
    background: rgba(8, 45, 28, 0.38) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
}

#browseOptionsModal[style*="display: flex"],
#browseOptionsModal[style*="display:flex"] {
    display: flex !important;
}

#browseOptionsModal .floating-modal-card {
    position: relative !important;
    top: auto !important;
    left: auto !important;
    transform: none !important;

    width: min(430px, calc(100vw - 36px)) !important;
    max-height: calc(100dvh - 56px) !important;
    overflow-y: auto !important;

    margin: 0 auto !important;
    padding: 24px !important;
    border-radius: 28px !important;
    z-index: 30001 !important;
}

#browseOptionsModal .floating-modal-actions {
    position: static !important;
    display: grid !important;
    grid-template-columns: 1fr !important;
    gap: 10px !important;
    margin-top: 18px !important;
}

#browseOptionsModal .floating-modal-actions .route-btn {
    width: 100% !important;
    min-width: 0 !important;
    border-radius: 999px !important;
}

/* Mobile tuning: keep it close to the bottom buttons */
@media (max-width: 768px) {
    #ai-search-panel,
    #ai-voice-panel {
        bottom: 96px !important;
        width: calc(100vw - 44px) !important;
        max-height: calc(100dvh - 130px) !important;
        border-radius: 24px !important;
    }

    #browseOptionsModal {
        padding: 12px !important;
    }

    #browseOptionsModal .floating-modal-card {
        width: calc(100vw - 24px) !important;
        max-height: calc(100dvh - 36px) !important;
        padding: 22px 18px !important;
        border-radius: 24px !important;
    }
}

/* Very small phones */
@media (max-width: 420px) {
    #ai-search-panel,
    #ai-voice-panel {
        bottom: 88px !important;
        width: calc(100vw - 34px) !important;
        max-height: calc(100dvh - 120px) !important;
    }
}
</style>

<style>
/* =========================================================
   SMOOTHNESS / PERFORMANCE CSS PATCH
   Visual optimization only. No building color changes.
   Paste this at the VERY BOTTOM of user.style.style.
========================================================= */

/* Make map rendering/compositing steadier */
#map,
.leaflet-container {
    transform: translateZ(0) !important;
    backface-visibility: hidden !important;
    -webkit-font-smoothing: antialiased !important;
}

.leaflet-pane,
.leaflet-tile,
.leaflet-marker-icon,
.leaflet-overlay-pane svg {
    backface-visibility: hidden !important;
}

/* During pan/zoom: temporarily reduce expensive effects only while moving */
body.map-moving body.map-moving .path-interactive,
body.map-moving .path-covered-stairs,
body.map-moving .path-canopy-frames,
body.map-moving .leaflet-marker-icon,
body.map-moving .leaflet-popup,
body.map-moving .route-building-map-popup {
    transition: none !important;
    animation: none !important;
}

body.map-moving .path-covered-stairs,
body.map-moving .path-canopy-frames {
    filter: none !important;
}

/* Pause non-essential pulsing effects while map is moving or tab is hidden */
body.map-moving .ai-dot,
body.map-moving .ai-voice-orb,
body.map-moving .ai-voice-orb span,
body.map-moving .hazard-pin,
body.page-hidden .ai-dot,
body.page-hidden .ai-voice-orb,
body.page-hidden .ai-voice-orb span,
body.page-hidden .hazard-pin {
    animation-play-state: paused !important;
}

/* Lighter effects on mobile = less lag */
@media (max-width: 768px) {
    .leaflet-popup-content-wrapper,
    .premium-legend,
    .floating-ai-badge,
    .floating-action-card,
    .ai-transform-panel,
    .floating-modal-card,
    .floating-start-bar,
    .indoor-panel,
    .user-profile-menu {
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    .floating-ai-badge,
    .floating-action-card,
    .ai-transform-panel,
    .floating-modal-card,
    .floating-start-bar,
    .indoor-panel,
    .user-profile-menu {
        box-shadow: 0 10px 28px rgba(8, 45, 28, 0.16) !important;
    }

    .floating-main-pin,
    .pin-disc,
    .floating-mode-btn,
    .route-btn,
    .user-profile-btn {
        transition: transform 0.12s ease !important;
    }

    .ai-dot,
    .badge-dot,
    .hazard-pin {
        animation-duration: 3.5s !important;
    }
}

/* Respect devices/users that prefer fewer animations */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.001ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: 0.001ms !important;
    }
}
</style>

<style>
/* =========================================================
   3-LAYER LIGHTWEIGHT FAKE 3D BUILDINGS
   Goal:
   - Dili flat ang buildings.
   - 3 visible fake-3D side layers.
   - Still lighter than old heavy multi-shadow version.
   - No duplicate shadow polygons.
   - No routing / Dijkstra changes.
========================================================= */

:root {
    --mobile-side-1: 1px;
    --mobile-side-2: 2px;
    --mobile-side-3: 3px;
    --mobile-edge-width: 1.35;
}

/* Hide old duplicate shadow panes if cached */
.leaflet-buildingShadowPane-pane,
.fake-3d-building-shadow,
.mobile-fake-3d-shadow {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
}

/* Stable SVG rendering */
.fake-3d-building,
.leaflet-interactive.fake-3d-building,
.leaflet-buildingsPane-pane .leaflet-interactive {
    transform: none !important;
    backface-visibility: hidden !important;
    vector-effect: non-scaling-stroke !important;
    shape-rendering: geometricPrecision !important;
}

/* No transition jank while moving/zooming */
body.map-moving .fake-3d-building,
body.map-moving .fake-3d-building:hover,
body.map-zooming .fake-3d-building,
body.map-zooming .fake-3d-building:hover {
    transition: none !important;
    transform: none !important;
}

/* Many-buildings mode keeps 3D visible, just lighter */
body.many-buildings-mode .fake-3d-building,
body.many-buildings-mode .fake-3d-building:hover {
    transition: none !important;
}

/* During zoom: still fake 3D, but smaller 3 layers */
body.map-zooming .fake-3d-building,
body.map-zooming .fake-3d-building:hover {
    filter:
        drop-shadow(1px 1px 0 var(--building-side-1, rgba(15, 23, 42, 0.28)))
        drop-shadow(2px 2px 0 var(--building-side-2, rgba(15, 23, 42, 0.20)))
        drop-shadow(3px 3px 1px var(--building-side-3, rgba(15, 23, 42, 0.12))) !important;
}

/* Pause small animations while map is moving */
body.map-moving .ai-dot,
body.map-moving .badge-dot,
body.map-moving .hazard-pin,
body.map-zooming .ai-dot,
body.map-zooming .badge-dot,
body.map-zooming .hazard-pin {
    animation-play-state: paused !important;
}

body.map-moving .path-interactive,
body.map-moving .path-covered-stairs,
body.map-moving .path-canopy-frames,
body.map-zooming .path-interactive,
body.map-zooming .path-covered-stairs,
body.map-zooming .path-canopy-frames {
    transition: none !important;
}

/* Mobile: readable edge, not too thick */
@media (hover: none), (max-width: 768px) {
    .fake-3d-building,
    .fake-3d-building:hover,
    body.map-moving .fake-3d-building,
    body.map-moving .fake-3d-building:hover {
        stroke-width: var(--mobile-edge-width, 1.35) !important;
        vector-effect: non-scaling-stroke !important;
    }
}
</style>

<style>
/* =========================================================
   NOTIFICATION BELL RESTORE / ALWAYS VISIBLE FIX
   Purpose:
   - Notification icon must stay visible beside the profile icon.
   - If there are no active/current campus events, show badge "0"
     instead of hiding the bell.
   - Does not change routing / Dijkstra / building fake 3D.
========================================================= */

.campus-event-notification-wrap {
    position: fixed !important;
    top: max(14px, env(safe-area-inset-top)) !important;
    right: calc(max(14px, env(safe-area-inset-right)) + 62px) !important;
    z-index: 10070 !important;
    display: block;
    pointer-events: auto !important;
}

.campus-event-notification-wrap.force-visible {
    display: block !important;
}

.campus-event-bell-btn {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.campus-event-bell-count.is-zero {
    background: #94a3b8 !important;
    color: #ffffff !important;
}

.campus-event-empty {
    padding: 18px 14px;
    text-align: center;
    color: #64748b;
    font-size: 12px;
    line-height: 1.55;
    font-weight: 700;
}

.campus-event-empty-icon {
    display: block;
    font-size: 26px;
    margin-bottom: 6px;
}

@media (max-width: 420px) {
    .campus-event-notification-wrap {
        right: calc(max(10px, env(safe-area-inset-right)) + 58px) !important;
        top: max(12px, env(safe-area-inset-top)) !important;
    }
}
</style>

<style>
    /* =========================================================
       SLSU SMART CAMPUS BRAND - TOP LEFT MAP BADGE
       Image file path: public/background/slsu-logo.png
       Matches landing page green/white glass theme.
    ========================================================= */

    .campus-brand-wrap {
        position: fixed;
        top: max(16px, env(safe-area-inset-top));
        left: 72px; /* Leaves space for Leaflet + / - buttons */
        z-index: 9997;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: min(320px, calc(100vw - 148px));
        padding: 9px 14px 9px 9px;
        border-radius: 999px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.92), rgba(240, 253, 244, 0.78));
        border: 1px solid rgba(15, 118, 74, 0.14);
        box-shadow:
            0 14px 34px rgba(6, 95, 70, 0.14),
            inset 0 1px 0 rgba(255, 255, 255, 0.86);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        pointer-events: none;
        user-select: none;
    }

    .campus-brand-logo-shell {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.88);
        box-shadow:
            0 8px 18px rgba(15, 23, 42, 0.12),
            inset 0 0 0 1px rgba(255, 255, 255, 0.9);
        overflow: hidden;
    }

    .campus-brand-logo {
        width: 35px;
        height: 35px;
        object-fit: contain;
        display: block;
        filter: drop-shadow(0 4px 8px rgba(15, 23, 42, 0.10));
    }

    .campus-brand-text {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 0;
        line-height: 1.05;
    }

    .campus-brand-title {
        color: #066b3a;
        font-size: 14px;
        font-weight: 900;
        letter-spacing: -0.02em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .campus-brand-subtitle {
        margin-top: 3px;
        color: #0f3f86;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    @media (max-width: 768px) {
        .campus-brand-wrap {
            top: max(12px, env(safe-area-inset-top));
            left: 58px;
            max-width: calc(100vw - 128px);
            padding: 7px 11px 7px 7px;
            gap: 8px;
            box-shadow:
                0 10px 24px rgba(6, 95, 70, 0.13),
                inset 0 1px 0 rgba(255, 255, 255, 0.86);
        }

        .campus-brand-logo-shell {
            width: 32px;
            height: 32px;
            flex-basis: 32px;
        }

        .campus-brand-logo {
            width: 28px;
            height: 28px;
        }

        .campus-brand-title {
            font-size: 12px;
        }

        .campus-brand-subtitle {
            font-size: 8px;
            letter-spacing: 0.06em;
        }
    }

    @media (max-width: 390px) {
        .campus-brand-wrap {
            max-width: 176px;
            padding-right: 10px;
        }

        .campus-brand-title {
            font-size: 11px;
        }

        .campus-brand-subtitle {
            font-size: 7.5px;
        }
    }
</style>


<style>
/* =========================================================
   SMOOTH ROUTE LINE FIX
   Keeps outdoor/indoor green route visually attached to the
   path and prevents sharp-looking skipped/laktaw segments.
========================================================= */
.route-line-live,
.route-line-live-indoor,
.route-line-safe,
.route-line-caution,
.route-line-danger {
    stroke-linecap: round !important;
    stroke-linejoin: round !important;
    vector-effect: non-scaling-stroke !important;
    shape-rendering: geometricPrecision !important;
}

.leaflet-path-pane .route-line-live,
.leaflet-path-pane .route-line-live-indoor {
    filter: drop-shadow(0 0 6px rgba(34, 197, 94, 0.34));
}
</style>

<style>
    /* =========================================================
       ENHANCED BROWSE DESTINATION MODAL
       Building -> Floor -> Room/Office cards for faster choices
    ========================================================= */
    .browse-destination-card {
        position: relative;
        width: min(820px, 94vw) !important;
        max-height: min(88vh, 820px);
        overflow: hidden;
        padding: 0 !important;
        border-radius: 28px !important;
        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, 0.13), transparent 34%),
            radial-gradient(circle at top right, rgba(22, 163, 74, 0.14), transparent 30%),
            rgba(255, 255, 255, 0.96) !important;
        border: 1px solid rgba(255, 255, 255, 0.72) !important;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.24) !important;
    }

    .browse-modal-glow {
        position: absolute;
        inset: -120px -120px auto auto;
        width: 250px;
        height: 250px;
        border-radius: 999px;
        background: rgba(34, 197, 94, 0.15);
        filter: blur(8px);
        pointer-events: none;
    }

    .browse-modal-header {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 22px 24px 16px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.82);
    }

    .floating-modal-kicker {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        margin-bottom: 7px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(22, 163, 74, 0.10);
        color: #047857;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .browse-modal-x {
        flex: 0 0 auto;
        width: 42px;
        height: 42px;
        border: 0;
        border-radius: 16px;
        background: rgba(15, 23, 42, 0.07);
        color: #0f172a;
        font-size: 24px;
        font-weight: 900;
        cursor: pointer;
        transition: 0.18s ease;
    }

    .browse-modal-x:hover {
        transform: translateY(-1px);
        background: rgba(220, 38, 38, 0.10);
        color: #dc2626;
    }

    .browse-type-picker {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        padding: 16px 24px 10px;
    }

    .browse-type-card {
        border: 1px solid rgba(203, 213, 225, 0.82);
        border-radius: 20px;
        padding: 13px;
        background: rgba(255, 255, 255, 0.78);
        display: flex;
        align-items: center;
        gap: 10px;
        text-align: left;
        cursor: pointer;
        font-family: inherit;
        transition: 0.18s ease;
    }

    .browse-type-card:hover {
        transform: translateY(-1px);
        border-color: rgba(37, 99, 235, 0.45);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .browse-type-card.active {
        background: linear-gradient(135deg, #2563eb, #16a34a);
        border-color: rgba(37, 99, 235, 0.40);
        color: white;
        box-shadow: 0 18px 34px rgba(37, 99, 235, 0.25);
    }

    .browse-type-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: rgba(15, 23, 42, 0.07);
        font-size: 18px;
        flex: 0 0 auto;
    }

    .browse-type-card.active .browse-type-icon {
        background: rgba(255, 255, 255, 0.18);
    }

    .browse-type-card strong,
    .browse-type-card small {
        display: block;
    }

    .browse-type-card strong {
        font-size: 13px;
        font-weight: 900;
        line-height: 1.2;
    }

    .browse-type-card small {
        margin-top: 3px;
        font-size: 10px;
        font-weight: 700;
        color: #64748b;
        line-height: 1.25;
    }

    .browse-type-card.active small {
        color: rgba(255, 255, 255, 0.78);
    }

    .browse-native-type-select {
        display: none !important;
    }

    .browse-section {
        position: relative;
        z-index: 1;
        margin: 10px 24px 0;
        padding: 16px;
        border-radius: 22px;
        border: 1px solid rgba(226, 232, 240, 0.88);
        background: rgba(248, 250, 252, 0.78);
    }

    .browse-section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
    }

    .browse-section-title {
        font-size: 15px;
        font-weight: 900;
        color: #0f172a;
    }

    .browse-section-subtitle {
        margin-top: 3px;
        font-size: 11px;
        color: #64748b;
        line-height: 1.5;
    }

    .no-margin {
        margin-bottom: 0 !important;
    }

    .browse-big-select {
        min-height: 46px;
        border-radius: 16px !important;
        font-weight: 800;
    }

    .room-result-count {
        white-space: nowrap;
        padding: 7px 10px;
        border-radius: 999px;
        background: #dcfce7;
        color: #15803d;
        font-size: 11px;
        font-weight: 900;
    }

    .room-filter-grid {
        display: grid;
        grid-template-columns: minmax(210px, 0.85fr) 1fr;
        gap: 12px;
    }

    .browse-search-shell {
        position: relative;
        display: flex;
        align-items: center;
    }

    .browse-search-icon {
        position: absolute;
        left: 14px;
        color: #64748b;
        font-weight: 900;
        pointer-events: none;
    }

    .browse-search-input {
        width: 100%;
        min-height: 46px;
        border: 1px solid #cbd5e1;
        border-radius: 16px;
        padding: 12px 14px 12px 38px;
        font-size: 13px;
        font-weight: 750;
        font-family: inherit;
        outline: none;
        background: white;
    }

    .browse-search-input:focus,
    .browse-big-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
    }

    .room-floor-filter-wrap {
        margin-top: 2px;
        margin-bottom: 12px;
    }

    .room-filter-label {
        margin-bottom: 8px;
        font-size: 11px;
        font-weight: 900;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .room-floor-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .room-floor-chip {
        border: 1px solid rgba(203, 213, 225, 0.9);
        border-radius: 999px;
        padding: 8px 12px;
        background: white;
        color: #334155;
        cursor: pointer;
        font-size: 12px;
        font-weight: 900;
        font-family: inherit;
        transition: 0.16s ease;
    }

    .room-floor-chip:hover {
        border-color: #2563eb;
        transform: translateY(-1px);
    }

    .room-floor-chip.active {
        background: #0f172a;
        color: white;
        border-color: #0f172a;
    }

    .browse-hidden-room-select {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    .room-office-results {
        display: grid;
        gap: 12px;
        max-height: 300px;
        overflow: auto;
        padding: 2px 4px 4px 0;
        scrollbar-width: thin;
    }

    .room-floor-group {
        display: grid;
        gap: 8px;
    }

    .room-floor-group-title {
        position: sticky;
        top: 0;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 10px;
        border-radius: 14px;
        background: rgba(241, 245, 249, 0.96);
        backdrop-filter: blur(8px);
        color: #0f172a;
        font-size: 12px;
        font-weight: 950;
    }

    .room-floor-group-title small {
        font-size: 10px;
        color: #64748b;
        font-weight: 850;
    }

    .room-office-card {
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 18px;
        padding: 12px;
        background: rgba(255, 255, 255, 0.94);
        display: flex;
        gap: 11px;
        align-items: flex-start;
        text-align: left;
        cursor: pointer;
        font-family: inherit;
        transition: 0.16s ease;
    }

    .room-office-card:hover {
        transform: translateY(-1px);
        border-color: rgba(37, 99, 235, 0.42);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
    }

    .room-office-card.active {
        border-color: rgba(22, 163, 74, 0.68);
        background: linear-gradient(135deg, rgba(220, 252, 231, 0.96), rgba(239, 246, 255, 0.96));
        box-shadow: 0 14px 28px rgba(22, 163, 74, 0.12);
    }

    .room-office-card-icon {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        background: #eff6ff;
        flex: 0 0 auto;
    }

    .room-office-card-main {
        min-width: 0;
        display: block;
    }

    .room-office-card-main strong {
        display: block;
        font-size: 13px;
        font-weight: 950;
        color: #0f172a;
        line-height: 1.25;
    }

    .room-office-card-main small,
    .room-office-card-main em {
        display: block;
        margin-top: 3px;
        font-size: 11px;
        line-height: 1.35;
        color: #64748b;
        font-style: normal;
        font-weight: 750;
    }

    .room-office-card-main em {
        color: #2563eb;
    }

    .room-empty-state {
        padding: 24px 14px;
        text-align: center;
        border: 1px dashed rgba(148, 163, 184, 0.8);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.65);
    }

    .room-empty-icon {
        font-size: 25px;
        margin-bottom: 8px;
    }

    .room-empty-title {
        font-size: 13px;
        font-weight: 950;
        color: #0f172a;
    }

    .room-empty-text {
        margin-top: 4px;
        font-size: 11px;
        color: #64748b;
        line-height: 1.45;
    }

    .browse-actions {
        position: relative;
        z-index: 1;
        padding: 16px 24px 22px;
        margin-top: 0 !important;
    }

    .browse-find-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 160px;
        background: linear-gradient(135deg, #2563eb, #16a34a) !important;
        box-shadow: 0 15px 28px rgba(37, 99, 235, 0.22);
    }

    @media (max-width: 768px) {
        .browse-destination-card {
            width: 94vw !important;
            max-height: 86vh;
            border-radius: 24px !important;
        }

        .browse-modal-header {
            padding: 18px 16px 12px;
        }

        .browse-type-picker {
            grid-template-columns: 1fr;
            padding: 14px 16px 8px;
        }

        .browse-section {
            margin: 10px 16px 0;
            padding: 14px;
        }

        .room-filter-grid {
            grid-template-columns: 1fr;
            gap: 4px;
        }

        .room-office-results {
            max-height: 260px;
        }

        .browse-actions {
            padding: 14px 16px 18px;
        }

        .browse-actions .route-btn {
            flex: 1 1 auto;
        }

        .browse-find-btn {
            min-width: 100%;
        }
    }
</style>

<style>
/* =========================================================
   COMPACT BLUE INDOOR START PIN - CENTERED ON ENTRANCE DOT
   The Leaflet iconAnchor is set to the exact bottom center of the pin.
========================================================= */
.route-indoor-start-marker {
    background: transparent !important;
    border: none !important;
    pointer-events: auto !important;
}

.route-indoor-start-wrap {
    position: relative;
    width: 30px;
    height: 40px;
    display: block;
    overflow: visible;
    pointer-events: auto;
}

.route-indoor-start-pin {
    position: absolute;
    left: 0;
    top: 0;
    width: 30px;
    height: 40px;
    background: linear-gradient(180deg, #3b82f6 0%, #2563eb 55%, #1d4ed8 100%);
    clip-path: path('M15 0 C23.3 0 30 6.7 30 15 C30 26.5 18.5 36.5 15 40 C11.5 36.5 0 26.5 0 15 C0 6.7 6.7 0 15 0 Z');
    filter: drop-shadow(0 5px 8px rgba(15, 23, 42, 0.22));
    z-index: 2;
}

.route-indoor-start-pin::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 34% 25%, rgba(255,255,255,0.34), transparent 30%);
    clip-path: inherit;
    pointer-events: none;
}

.route-indoor-start-hole {
    position: absolute;
    left: 50%;
    top: 8px;
    width: 11px;
    height: 11px;
    transform: translateX(-50%);
    border-radius: 50%;
    background: #ffffff;
    box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.12);
    z-index: 3;
}

.route-indoor-start-text {
    position: absolute;
    left: 50%;
    top: 22px;
    transform: translateX(-50%);
    z-index: 3;
    color: #ffffff;
    font-size: 5.6px;
    font-weight: 950;
    letter-spacing: 0.02em;
    line-height: 1;
    text-shadow: 0 1px 1px rgba(15, 23, 42, 0.35);
    white-space: nowrap;
}

@media (max-width: 768px) {
    .route-indoor-start-wrap,
    .route-indoor-start-pin {
        width: 25px !important;
        height: 34px !important;
    }

    .route-indoor-start-pin {
        clip-path: path('M12.5 0 C19.4 0 25 5.6 25 12.5 C25 22.2 15.4 31 12.5 34 C9.6 31 0 22.2 0 12.5 C0 5.6 5.6 0 12.5 0 Z') !important;
        filter: drop-shadow(0 4px 7px rgba(15, 23, 42, 0.20)) !important;
    }

    .route-indoor-start-hole {
        top: 7px !important;
        width: 9px !important;
        height: 9px !important;
    }

    .route-indoor-start-text {
        top: 19px !important;
        font-size: 4.6px !important;
        letter-spacing: 0 !important;
    }
}

@media (max-width: 420px) {
    .route-indoor-start-wrap,
    .route-indoor-start-pin {
        width: 23px !important;
        height: 31px !important;
    }

    .route-indoor-start-pin {
        clip-path: path('M11.5 0 C17.9 0 23 5.1 23 11.5 C23 20.4 14.2 28.3 11.5 31 C8.8 28.3 0 20.4 0 11.5 C0 5.1 5.1 0 11.5 0 Z') !important;
    }

    .route-indoor-start-hole {
        top: 6px !important;
        width: 8px !important;
        height: 8px !important;
    }

    .route-indoor-start-text {
        top: 17px !important;
        font-size: 4.2px !important;
    }
}
</style>


<style>
/* =========================================================
   OUTDOOR LIVE GPS TRACKING UI
   Blue live dot + accuracy circle + compact mobile status card.
========================================================= */
.live-gps-marker,
.gps-route-start-marker {
    background: transparent !important;
    border: none !important;
}

.live-gps-dot-wrap {
    position: relative;
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.live-gps-pulse {
    position: absolute;
    width: 34px;
    height: 34px;
    border-radius: 999px;
    background: rgba(37, 99, 235, 0.15);
    border: 2px solid rgba(37, 99, 235, 0.28);
    animation: liveGpsPulse 1.7s ease-out infinite;
}

.live-gps-dot {
    position: relative;
    width: 16px;
    height: 16px;
    border-radius: 999px;
    background: #2563eb;
    border: 3px solid #ffffff;
    box-shadow:
        0 6px 16px rgba(15, 23, 42, 0.28),
        0 0 0 1px rgba(37, 99, 235, 0.22);
    z-index: 2;
}

.live-gps-direction-dot {
    position: absolute;
    right: 6px;
    top: 6px;
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: #ffffff;
    border: 2px solid #2563eb;
    z-index: 3;
}

.gps-route-start-pin {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    border: 3px solid #ffffff;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
    font-size: 9px;
    font-weight: 950;
    letter-spacing: 0.04em;
}

@keyframes liveGpsPulse {
    0% {
        transform: scale(0.62);
        opacity: 0.9;
    }

    100% {
        transform: scale(1.45);
        opacity: 0;
    }
}

.live-gps-status-card {
    position: fixed;
    right: max(14px, env(safe-area-inset-right));
    bottom: calc(116px + env(safe-area-inset-bottom));
    z-index: 10060;
    width: min(330px, calc(100vw - 28px));
    padding: 13px;
    border-radius: 22px;
    background: rgba(15, 23, 42, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.14);
    color: #ffffff;
    box-shadow: 0 24px 60px rgba(2, 8, 23, 0.38);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    pointer-events: auto;
}

.live-gps-status-card.good {
    border-color: rgba(34, 197, 94, 0.44);
    box-shadow: 0 24px 60px rgba(2, 8, 23, 0.38), 0 0 28px rgba(34, 197, 94, 0.16);
}

.live-gps-status-card.weak {
    border-color: rgba(250, 204, 21, 0.50);
    box-shadow: 0 24px 60px rgba(2, 8, 23, 0.38), 0 0 28px rgba(250, 204, 21, 0.13);
}

.live-gps-status-card.bad {
    border-color: rgba(248, 113, 113, 0.54);
    box-shadow: 0 24px 60px rgba(2, 8, 23, 0.38), 0 0 28px rgba(248, 113, 113, 0.13);
}

.live-gps-status-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 7px;
}

.live-gps-status-kicker {
    font-size: 9px;
    font-weight: 950;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #93c5fd;
    margin-bottom: 3px;
}

.live-gps-status-title {
    font-size: 13px;
    font-weight: 900;
    line-height: 1.25;
}

.live-gps-status-text {
    font-size: 11px;
    line-height: 1.55;
    color: rgba(226, 232, 240, 0.94);
}

.live-gps-status-x {
    width: 28px;
    height: 28px;
    border: 0;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.10);
    color: #ffffff;
    font-size: 18px;
    font-weight: 900;
    line-height: 1;
    cursor: pointer;
}

.live-gps-status-actions {
    display: flex;
    gap: 8px;
    margin-top: 10px;
}

.live-gps-mini-btn {
    flex: 1;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 13px;
    padding: 8px 10px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    font-size: 11px;
    font-weight: 900;
    font-family: inherit;
    cursor: pointer;
}

.live-gps-mini-btn.ghost {
    background: rgba(255, 255, 255, 0.10);
}

@media (max-width: 768px) {
    .live-gps-dot-wrap {
        width: 30px;
        height: 30px;
    }

    .live-gps-pulse {
        width: 30px;
        height: 30px;
    }

    .live-gps-dot {
        width: 14px;
        height: 14px;
        border-width: 2.5px;
    }

    .gps-route-start-pin {
        width: 29px;
        height: 29px;
        font-size: 8px;
        border-width: 2.5px;
    }

    .live-gps-status-card {
        left: 50%;
        right: auto;
        bottom: calc(104px + env(safe-area-inset-bottom));
        transform: translateX(-50%);
        width: min(360px, calc(100vw - 22px));
        padding: 11px;
        border-radius: 20px;
    }

    .live-gps-status-title {
        font-size: 12px;
    }

    .live-gps-status-text {
        font-size: 10.5px;
    }
}

body.map-moving .live-gps-pulse,
body.page-hidden .live-gps-pulse {
    animation-play-state: paused !important;
}


/* =========================================================
   FINAL GPS WEAK-SIGNAL UX
   Make weak GPS message more noticeable on mobile.
========================================================= */
.live-gps-status-card.weak .live-gps-status-title::before {
    content: '⚠️ ';
}

.live-gps-status-card.good .live-gps-status-title::before {
    content: '✅ ';
}

.live-gps-status-card.bad .live-gps-status-title::before {
    content: '🚫 ';
}

@media (max-width: 768px) {
    .live-gps-status-card {
        bottom: calc(92px + env(safe-area-inset-bottom)) !important;
        left: 12px !important;
        right: 12px !important;
        width: auto !important;
        border-radius: 18px !important;
        padding: 11px 12px !important;
    }

    .live-gps-status-text {
        font-size: 11px !important;
        line-height: 1.45 !important;
    }
}


/* =========================================================
   AUTO GPS FALLBACK: TAP MY LOCATION
   When GPS is weak, the system enables map-tap start selection.
========================================================= */
.live-gps-status-card.bad .live-gps-mini-btn.ghost {
    background: linear-gradient(135deg, #16a34a, #15803d) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.35) !important;
    box-shadow: 0 10px 22px rgba(22, 163, 74, 0.24) !important;
}

body.pick-path-active .floating-mode-btn.pick {
    border-color: rgba(34, 197, 94, 0.70) !important;
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.18), 0 16px 30px rgba(2, 8, 23, 0.20) !important;
}

@media (max-width: 768px) {
    .live-gps-status-card.bad {
        border-color: rgba(34, 197, 94, 0.35) !important;
    }
}



    /* CAMPUS MAP ROTATION CONTROL */
    .campus-rotate-control {
        position: absolute;
        top: 96px;
        right: 18px;
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.90);
        border: 1px solid rgba(255, 255, 255, 0.70);
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.16);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        pointer-events: auto;
    }

    .campus-rotate-btn,
    .campus-rotate-reset {
        border: none;
        outline: none;
        cursor: pointer;
        font-family: inherit;
        font-weight: 900;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .campus-rotate-btn {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #0f172a;
        font-size: 20px;
        line-height: 1;
    }

    .campus-rotate-reset {
        height: 38px;
        min-width: 74px;
        border-radius: 999px;
        background: linear-gradient(135deg, #16a34a, #2563eb);
        color: white;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 12px;
        font-size: 12px;
        letter-spacing: 0.02em;
    }

    .campus-rotate-btn:hover,
    .campus-rotate-reset:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.14);
    }

    .campus-compass-arrow {
        display: inline-block;
        font-size: 12px;
        transform-origin: 50% 55%;
    }

    @media (max-width: 768px) {
        .campus-rotate-control {
            top: 84px;
            right: 12px;
            gap: 6px;
            padding: 6px;
        }

        .campus-rotate-btn {
            width: 34px;
            height: 34px;
            font-size: 18px;
        }

        .campus-rotate-reset {
            height: 34px;
            min-width: 64px;
            padding: 0 10px;
            font-size: 11px;
        }
    }

</style>


<style>
    /*
    |--------------------------------------------------------------------------
    | FULL MAP ROTATION VISUAL FIX
    |--------------------------------------------------------------------------
    | Keeps all Leaflet panes rotating together: basemap, GeoJSON buildings,
    | paths, landuse image overlays, markers, labels, and route lines.
    */
    #map .leaflet-pane.campus-layer-pane-rotated {
        transform-style: preserve-3d;
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
    }

    #map .leaflet-pane.campus-layer-pane-rotated .leaflet-layer,
    #map .leaflet-pane.campus-layer-pane-rotated svg,
    #map .leaflet-pane.campus-layer-pane-rotated canvas,
    #map .leaflet-pane.campus-layer-pane-rotated img {
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
    }


    /* Better cursor/control feeling while the rotated drag fix is active. */
    #map.campus-rotated-dragging {
        cursor: grabbing !important;
        touch-action: none;
        user-select: none;
        -webkit-user-select: none;
    }

    #map.campus-rotated-dragging * {
        cursor: grabbing !important;
    }
</style>


<style>
    /* Smooth rotated map drag controls */
    #map.campus-rotated-view {
        touch-action: none;
        cursor: grab;
    }

    #map.campus-rotated-dragging {
        cursor: grabbing !important;
        user-select: none;
        -webkit-user-select: none;
    }

    #map.campus-rotated-dragging * {
        cursor: grabbing !important;
        user-select: none;
        -webkit-user-select: none;
    }

    #map.campus-rotated-view .leaflet-pane {
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
    }
</style>
