<style>
    /* ==========================================
           PREMIUM PATH STYLES (LIGHT THEME)
           ========================================== */

    /* Default Interactive Path */
    .path-interactive {
        transition: stroke-width 0.2s cubic-bezier(0.4, 0, 0.2, 1), stroke 0.2s;
        filter: drop-shadow(0px 2px 4px rgba(0, 0, 0, 0.15));
    }

    /* 3D Canopy Effect para sa Covered Stairs (Base Glass Roof) */
    .path-covered-stairs {
        filter: drop-shadow(4px 6px 5px rgba(0, 0, 0, 0.4)) drop-shadow(0px -1px 2px rgba(255, 255, 255, 0.3));
        stroke-linecap: butt;
    }

    /* White Structural Frames (Puti nga ribs sa ibabaw sa canopy) */
    .path-canopy-frames {
        pointer-events: none;
        /* Dili ma-click aron ang popup sa main path gihapon mo-gawas */
        filter: drop-shadow(0px 1px 1px rgba(0, 0, 0, 0.6));
        stroke-linecap: butt;
    }

    /* Smooth Marching Ants para sa Hazard */
    @keyframes path-flow {
        to {
            stroke-dashoffset: -20;
        }
    }

    .path-hazard-flow {
        animation: path-flow 0.8s linear infinite;
        filter: drop-shadow(0px 0px 5px rgba(225, 29, 72, 0.5));
    }

    /* ==========================================
           PREMIUM LEGEND (LIGHT GLASSMORPHISM)
           ========================================== */
    .premium-legend {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 16px;
        padding: 16px 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        font-family: 'Plus Jakarta Sans', sans-serif;
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
</style>
