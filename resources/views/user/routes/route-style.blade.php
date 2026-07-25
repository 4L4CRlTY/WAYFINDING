<style>
    #route-panel {
        position: absolute;
        top: 18px;
        left: 18px;
        z-index: 9999;
        width: 340px;
        pointer-events: none;
    }

    .route-card {
        pointer-events: auto;
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 18px;
        padding: 16px 16px 14px;
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.10);
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .route-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 4px;
    }

    .route-subtitle {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 14px;
        line-height: 1.5;
    }

    .route-field {
        margin-bottom: 10px;
    }

    .route-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }

    .route-select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 10px 12px;
        font-size: 12px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: white;
        outline: none;
    }

    .route-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .route-btn {
        border: none;
        border-radius: 12px;
        padding: 10px 12px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.2s ease;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .route-btn:hover {
        transform: translateY(-1px);
    }

    .route-btn.primary {
        background: #16a34a;
        color: white;
    }

    .route-btn.neutral {
        background: #e2e8f0;
        color: #0f172a;
    }

    .route-btn.success {
        background: #2563eb;
        color: white;
    }

    .route-btn.gps {
        background: #7c3aed;
        color: white;
    }

    .route-btn.full {
        width: 100%;
    }

    .route-status {
        margin-top: 12px;
        background: rgba(248, 250, 252, 0.9);
        border-radius: 12px;
        padding: 10px 12px;
        font-size: 12px;
        color: #334155;
        line-height: 1.7;
    }

    .mt-8 {
        margin-top: 8px;
    }

    .route-start-arrow {
        width: 22px;
        height: 22px;
        background: #16a34a;
        clip-path: polygon(50% 0%, 100% 100%, 50% 78%, 0% 100%);
        border: 2px solid white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.28);
        transform: rotate(180deg);
    }

    .route-destination-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #dc2626;
        border: 2px solid white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.28);
    }

    .route-gps-dot {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #7c3aed;
        border: 2px solid white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.28);
    }

    @media (max-width: 768px) {
        #route-panel {
            top: 12px;
            left: 12px;
            right: 12px;
            width: auto;
        }

        .route-card {
            padding: 14px;
        }
    }
</style>
<style>
    .route-line-safe {
        filter: drop-shadow(0 0 6px rgba(34, 197, 94, 0.35));
    }

    .route-line-caution {
        filter: drop-shadow(0 0 6px rgba(250, 204, 21, 0.40));
    }

    .route-line-danger {
        filter: drop-shadow(0 0 8px rgba(220, 38, 38, 0.40));
    }
</style>
