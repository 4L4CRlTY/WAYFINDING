/* =========================================================
   REAL-DEVICE GPS DIAGNOSTICS + FIELD CALIBRATION
   Records measurements locally and exports only on request.
========================================================= */
(function () {
    if (window.__wayfindingGpsDiagnosticsInstalled) return;
    window.__wayfindingGpsDiagnosticsInstalled = true;

    const STORAGE_KEY = 'wayfinding:gps-calibration-session:v1';
    const MAX_SAVED_SAMPLES = 1000;
    const panel = document.getElementById('gps-diagnostics-panel');
    const toggleButton = document.getElementById('gps-diagnostics-toggle');
    const closeButton = document.getElementById('gps-diagnostics-close');
    const startButton = document.getElementById('gps-session-start');
    const stopButton = document.getElementById('gps-session-stop');
    const exportButton = document.getElementById('gps-session-export');
    const clearButton = document.getElementById('gps-session-clear');
    const signalLabel = document.getElementById('gps-diagnostics-signal-label');
    const signalMessage = document.getElementById('gps-diagnostics-signal-message');
    const accuracyValue = document.getElementById('gps-diagnostics-accuracy');
    const snapDistanceValue = document.getElementById('gps-diagnostics-snap-distance');
    const headingValue = document.getElementById('gps-diagnostics-heading');
    const speedValue = document.getElementById('gps-diagnostics-speed');
    const lockValue = document.getElementById('gps-diagnostics-lock');
    const offRouteValue = document.getElementById('gps-diagnostics-off-route');
    const warning = document.getElementById('gps-diagnostics-warning');
    const recordingBadge = document.getElementById('gps-recording-badge');
    const sampleCountValue = document.getElementById('gps-session-samples');
    const acceptedValue = document.getElementById('gps-session-accepted');
    const p95Value = document.getElementById('gps-session-p95');
    const durationValue = document.getElementById('gps-session-duration');
    const gradeValue = document.getElementById('gps-session-grade');
    const recommendationValue = document.getElementById('gps-session-recommendation');

    if (!panel || !toggleButton) return;

    let recording = false;
    let startedAt = null;
    let samples = [];
    let latestReading = null;
    let persistTimer = null;
    let durationTimer = null;
    let previouslyFocused = null;

    function finiteOrNull(value) {
        const number = Number(value);
        return Number.isFinite(number) ? number : null;
    }

    function formatMeters(value) {
        const number = finiteOrNull(value);
        return number === null ? '--' : `${Math.round(number)}m`;
    }

    function formatHeading(value) {
        const number = finiteOrNull(value);
        return number === null ? '--' : `${Math.round((number + 360) % 360)}°`;
    }

    function formatSpeed(value) {
        const number = finiteOrNull(value);
        return number === null ? '--' : `${(number * 3.6).toFixed(1)} km/h`;
    }

    function formatDuration(milliseconds) {
        const totalSeconds = Math.max(0, Math.floor(Number(milliseconds || 0) / 1000));
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        if (hours > 0) {
            return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        return `${minutes}:${String(seconds).padStart(2, '0')}`;
    }

    function calibrationOptions() {
        const thresholds = window.WayfindingGpsCalibration?.thresholds || {};

        return {
            strong: thresholds.strongAccuracy || 20,
            fair: thresholds.fairAccuracy || 45,
            reject: thresholds.rejectAccuracy || 60,
        };
    }

    function summarizeSession() {
        return window.WayfindingRouting.summarizeGpsCalibration(
            samples,
            calibrationOptions(),
        );
    }

    function safeSavedSession() {
        try {
            const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
            if (!saved || !Array.isArray(saved.samples)) return null;

            return {
                startedAt: finiteOrNull(saved.startedAt),
                samples: saved.samples.slice(-MAX_SAVED_SAMPLES),
            };
        } catch (error) {
            return null;
        }
    }

    function persistSession() {
        persistTimer = null;

        try {
            if (!samples.length) {
                localStorage.removeItem(STORAGE_KEY);
                return;
            }

            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                version: 1,
                startedAt,
                samples: samples.slice(-MAX_SAVED_SAMPLES),
            }));
        } catch (error) {
            window.showWayfindingToast?.(
                'GPS session could not be saved locally. Export it before leaving this page.',
                { kind: 'warning' },
            );
        }
    }

    function schedulePersist() {
        if (persistTimer !== null) return;
        persistTimer = window.setTimeout(persistSession, 750);
    }

    function updateDuration() {
        const summary = summarizeSession();
        const elapsed = recording && startedAt
            ? Math.max(summary.durationMs, Date.now() - startedAt)
            : summary.durationMs;

        if (durationValue) durationValue.textContent = formatDuration(elapsed);
    }

    function renderSession() {
        const summary = summarizeSession();

        if (recordingBadge) {
            recordingBadge.textContent = recording ? 'Recording' : 'Stopped';
            recordingBadge.classList.toggle('is-recording', recording);
        }
        if (sampleCountValue) sampleCountValue.textContent = String(summary.sampleCount);
        if (acceptedValue) {
            acceptedValue.textContent = summary.sampleCount
                ? `${Math.round(summary.acceptedRate * 100)}%`
                : '--';
        }
        if (p95Value) p95Value.textContent = formatMeters(summary.p95Accuracy);
        if (gradeValue) {
            gradeValue.textContent = summary.grade === 'not-ready'
                ? 'Not ready'
                : `${summary.grade.charAt(0).toUpperCase()}${summary.grade.slice(1)}`;
            gradeValue.dataset.grade = summary.grade;
        }
        if (recommendationValue) {
            recommendationValue.textContent = summary.recommendation;
        }

        if (startButton) {
            startButton.disabled = recording;
            startButton.textContent = samples.length && !recording
                ? 'Resume Recording'
                : 'Start Recording';
        }
        if (stopButton) stopButton.disabled = !recording;
        if (exportButton) exportButton.disabled = !samples.length;
        if (clearButton) clearButton.disabled = !samples.length || recording;

        updateDuration();
    }

    function signalCopy(detail, signal) {
        if (detail.type === 'error') {
            return {
                label: 'GPS unavailable',
                message: detail.message || 'Check location permission and GPS settings.',
                warning: 'Use Tap My Location if the device cannot provide a reliable GPS fix.',
            };
        }

        if (detail.status === 'calibrating') {
            return {
                label: `Calibrating (${detail.qualitySamples || 0}/${detail.thresholds?.requiredLockSamples || 4})`,
                message: `${formatMeters(detail.accuracy)} accuracy · keep the phone still`,
                warning: 'Stay in an open area until four stable readings complete the quality lock.',
            };
        }

        if (detail.status === 'off_route_confirming') {
            return {
                label: 'Checking off-route position',
                message: `${detail.offRouteCount || 0}/${detail.thresholds?.requiredOffRouteSamples || 3} confirmation readings`,
                warning: 'Continue walking normally. The route changes only after repeated off-route confirmation.',
            };
        }

        if (detail.status === 'off_path') {
            return {
                label: 'Outside path snap area',
                message: `${formatMeters(detail.snapDistance)} from the nearest mapped path`,
                warning: 'Move closer to a mapped walkway or use Tap My Location for an exact start.',
            };
        }

        if (detail.status === 'jump_rejected') {
            return {
                label: 'GPS jump ignored',
                message: `${formatMeters(detail.jumpDistance)} sudden movement was rejected`,
                warning: 'Tracking is still active and will wait for the next reliable reading.',
            };
        }

        if (signal === 'strong') {
            return {
                label: 'Strong GPS signal',
                message: `${formatMeters(detail.accuracy)} accuracy · ${detail.snapSource === 'active_route' ? 'locked to route' : 'locked to campus path'}`,
                warning: 'Signal quality is suitable for live campus navigation.',
            };
        }

        if (signal === 'fair') {
            return {
                label: 'Fair GPS signal',
                message: `${formatMeters(detail.accuracy)} accuracy · tracking remains active`,
                warning: 'Keep the phone visible and move toward an open area if the dot becomes unstable.',
            };
        }

        return {
            label: signal === 'rejected' ? 'Reading rejected' : 'Weak GPS signal',
            message: `${formatMeters(detail.accuracy)} accuracy`,
            warning: 'Move away from roofs or tall buildings, wait for a better fix, or use Tap My Location.',
        };
    }

    function renderLatest(detail) {
        latestReading = detail;

        if (detail.type === 'state') {
            if (detail.status === 'tracking_started') {
                panel.dataset.signal = 'calibrating';
                if (signalLabel) signalLabel.textContent = 'Starting GPS quality lock';
                if (signalMessage) signalMessage.textContent = 'Waiting for stable high-accuracy readings.';
            } else if (detail.status === 'tracking_stopped') {
                panel.dataset.signal = 'inactive';
                if (signalLabel) signalLabel.textContent = 'GPS tracking stopped';
                if (signalMessage) signalMessage.textContent = 'Your saved field-test samples are still available.';
            }
            return;
        }

        const signal = detail.type === 'error'
            ? 'rejected'
            : window.WayfindingRouting.classifyGpsSignal(
                detail.accuracy,
                calibrationOptions(),
            );
        const copy = signalCopy(detail, signal);
        panel.dataset.signal = signal;

        if (signalLabel) signalLabel.textContent = copy.label;
        if (signalMessage) signalMessage.textContent = copy.message;
        if (warning) warning.textContent = copy.warning;
        if (accuracyValue) accuracyValue.textContent = formatMeters(detail.accuracy);
        if (snapDistanceValue) snapDistanceValue.textContent = formatMeters(detail.snapDistance);
        if (headingValue) headingValue.textContent = formatHeading(detail.heading);
        if (speedValue) speedValue.textContent = formatSpeed(detail.speed);
        if (lockValue) {
            lockValue.textContent = detail.qualityLocked
                ? 'Locked'
                : `${detail.qualitySamples || 0} / ${detail.thresholds?.requiredLockSamples || 4}`;
        }
        if (offRouteValue) {
            offRouteValue.textContent = `${detail.offRouteCount || 0} / ${detail.thresholds?.requiredOffRouteSamples || 3}`;
        }
    }

    function normalizeRecordedSample(detail) {
        return {
            timestamp: Number(detail.timestamp || Date.now()),
            provider: String(detail.provider || 'device'),
            status: String(detail.status || ''),
            accepted: detail.accepted === true,
            lat: finiteOrNull(detail.lat),
            lng: finiteOrNull(detail.lng),
            accuracy: finiteOrNull(detail.accuracy),
            heading: finiteOrNull(detail.heading),
            speed: finiteOrNull(detail.speed),
            altitude: finiteOrNull(detail.altitude),
            snappedLat: finiteOrNull(detail.snappedLat),
            snappedLng: finiteOrNull(detail.snappedLng),
            snapDistance: finiteOrNull(detail.snapDistance),
            activeRouteDistance: finiteOrNull(detail.activeRouteDistance),
            snapRadius: finiteOrNull(detail.snapRadius),
            snapSource: String(detail.snapSource || ''),
            qualityLocked: detail.qualityLocked === true,
            qualitySamples: Number(detail.qualitySamples || 0),
            spread: finiteOrNull(detail.spread),
            offRouteCount: Number(detail.offRouteCount || 0),
            routeActive: detail.routeActive === true,
            reason: String(detail.reason || ''),
        };
    }

    function handleGpsDiagnostic(event) {
        const detail = event.detail || {};
        renderLatest(detail);

        if (recording && detail.type === 'reading') {
            samples.push(normalizeRecordedSample(detail));
            samples = samples.slice(-MAX_SAVED_SAMPLES);
            renderSession();
            schedulePersist();
        }
    }

    function openPanel() {
        previouslyFocused = document.activeElement;
        panel.hidden = false;
        toggleButton.setAttribute('aria-expanded', 'true');
        document.body.classList.add('gps-diagnostics-open');
        window.setTimeout(() => panel.focus(), 0);
    }

    function closePanel() {
        panel.hidden = true;
        toggleButton.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('gps-diagnostics-open');

        if (previouslyFocused?.focus) previouslyFocused.focus();
    }

    function startRecording() {
        if (recording) return;

        if (!samples.length) startedAt = Date.now();
        recording = true;
        renderSession();
        schedulePersist();

        if (!window.WayfindingGpsCalibration?.getState?.().tracking) {
            window.startOutdoorLiveGpsTracking?.();
        }

        window.showWayfindingToast?.(
            'GPS field-test recording started. Walk normally and keep this page open.',
            { kind: 'success', duration: 4200 },
        );
    }

    function stopRecording() {
        if (!recording) return;
        recording = false;
        renderSession();
        persistSession();
        window.showWayfindingToast?.(
            'GPS field-test recording stopped. Review the summary or export the CSV.',
            { kind: 'success', duration: 4200 },
        );
    }

    function clearSession() {
        if (recording) return;
        samples = [];
        startedAt = null;
        localStorage.removeItem(STORAGE_KEY);
        renderSession();
        window.showWayfindingToast?.('Saved GPS calibration samples cleared.');
    }

    function csvValue(value) {
        if (value === null || value === undefined) return '';
        const text = String(value);
        return /[",\r\n]/.test(text) ? `"${text.replaceAll('"', '""')}"` : text;
    }

    function exportCsv() {
        if (!samples.length) return;

        const columns = [
            'timestamp',
            'provider',
            'status',
            'accepted',
            'latitude',
            'longitude',
            'accuracy_m',
            'heading_deg',
            'speed_mps',
            'altitude_m',
            'snapped_latitude',
            'snapped_longitude',
            'path_offset_m',
            'active_route_offset_m',
            'snap_radius_m',
            'snap_source',
            'quality_locked',
            'quality_samples',
            'spread_m',
            'off_route_count',
            'route_active',
            'reason',
        ];
        const rows = samples.map(sample => [
            new Date(sample.timestamp).toISOString(),
            sample.provider,
            sample.status,
            sample.accepted,
            sample.lat,
            sample.lng,
            sample.accuracy,
            sample.heading,
            sample.speed,
            sample.altitude,
            sample.snappedLat,
            sample.snappedLng,
            sample.snapDistance,
            sample.activeRouteDistance,
            sample.snapRadius,
            sample.snapSource,
            sample.qualityLocked,
            sample.qualitySamples,
            sample.spread,
            sample.offRouteCount,
            sample.routeActive,
            sample.reason,
        ]);
        const csv = [
            columns.join(','),
            ...rows.map(row => row.map(csvValue).join(',')),
        ].join('\r\n');
        const blobUrl = URL.createObjectURL(new Blob([csv], {
            type: 'text/csv;charset=utf-8',
        }));
        const link = document.createElement('a');
        const date = new Date().toISOString().replaceAll(':', '-').replace(/\.\d{3}Z$/, 'Z');
        link.href = blobUrl;
        link.download = `gps-field-test-${date}.csv`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
    }

    const saved = safeSavedSession();
    if (saved) {
        startedAt = saved.startedAt;
        samples = saved.samples;
    }

    closeButton?.addEventListener('click', closePanel);
    startButton?.addEventListener('click', startRecording);
    stopButton?.addEventListener('click', stopRecording);
    exportButton?.addEventListener('click', exportCsv);
    clearButton?.addEventListener('click', clearSession);
    window.addEventListener('wayfinding:gps-diagnostic', handleGpsDiagnostic);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.hidden) closePanel();
    });

    durationTimer = window.setInterval(() => {
        if (recording) updateDuration();
    }, 1000);

    window.openGpsDiagnostics = openPanel;
    window.closeGpsDiagnostics = closePanel;
    window.WayfindingGpsDiagnostics = Object.freeze({
        open: openPanel,
        close: closePanel,
        startRecording,
        stopRecording,
        clearSession,
        exportCsv,
        getState() {
            return {
                recording,
                startedAt,
                samples: samples.map(sample => ({ ...sample })),
                latestReading: latestReading ? { ...latestReading } : null,
                summary: summarizeSession(),
            };
        },
    });

    renderSession();
})();
