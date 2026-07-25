<style>
        .fake-3d-building {
            transition: transform 0.2s ease, filter 0.2s ease;
            cursor: pointer;
        }

        /* ✨ GLASSMORPHISM POPUP ✨ */
        .leaflet-popup-content-wrapper {
            background: rgba(255, 255, 255, 0.85) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border-radius: 16px !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.6);
            padding: 5px;
        }

        .leaflet-popup-tip {
            background: rgba(255, 255, 255, 0.85) !important;
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
    </style>
