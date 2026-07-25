/* =========================================================
   OUTDOOR GPS SAFE MODE + QGIS PATH SNAPPING
   IMPORTANT:
   - GPS is used ONLY when it is reliable enough.
   - If GPS drifts to the field/building/wrong side, the system rejects it.
   - User is asked to use Tap My Location for exact current position.
   - Indoor navigation remains normal/static.
========================================================= */
(function () {
    if (window.__outdoorLiveGpsTrackingInstalled) return;
    window.__outdoorLiveGpsTrackingInstalled = true;

    /*
    | ACCURACY-BASED GPS SNAP SETTINGS
    | Instead of fixed 10m snap only, the snap radius now follows GPS accuracy.
    | Example: accuracy 15m => snap radius about 15m. A small grace is added
    | for QGIS digitizing offset so the marker can still snap to the walkway.
    */
    const GPS_STRONG_ACCURACY_M = 15;          // <=15m = accepted as route start
    const GPS_PREVIEW_ACCURACY_M = 25;         // 16-25m = show snapped preview only, recommend Tap My Location
    const GPS_REJECT_ACCURACY_M = 25;          // >25m = too weak for campus navigation
    const GPS_MIN_SNAP_RADIUS_M = 12;          // minimum snap radius
    const GPS_MAX_SNAP_RADIUS_M = 30;          // strict cap to avoid snapping to wrong campus path
    const GPS_SNAP_GRACE_M = 4;                // small tolerance for QGIS/path offset
    const GPS_ACTIVE_ROUTE_EXTRA_M = 6;        // active route gets a little more allowance
    const GPS_MIN_SAMPLES = 4;                 // wait for stable readings before trusting
    const GPS_SAMPLE_SPREAD_M = 22;            // if samples are scattered, GPS is unstable
    const GPS_BAD_JUMP_DISTANCE_M = 30;        // reject sudden jump when tracking
    const GPS_SMOOTH_FACTOR = 0.30;

    function clampGpsMeters(value, min, max) {
        value = Number(value || 0);
        return Math.max(min, Math.min(max, value));
    }

    function getGpsSnapRadiusMeters(accuracy) {
        /*
        | Mao ni imong gipangayo:
        | if GPS says 15 meters accuracy, gamiton nato ang around 15 meters
        | as snap radius, with small grace para sa QGIS mismatch.
        */
        return clampGpsMeters(Number(accuracy || 999) + GPS_SNAP_GRACE_M, GPS_MIN_SNAP_RADIUS_M, GPS_MAX_SNAP_RADIUS_M);
    }

    function getGpsActiveRouteSnapRadiusMeters(accuracy) {
        return clampGpsMeters(getGpsSnapRadiusMeters(accuracy) + GPS_ACTIVE_ROUTE_EXTRA_M, GPS_MIN_SNAP_RADIUS_M, GPS_MAX_SNAP_RADIUS_M + GPS_ACTIVE_ROUTE_EXTRA_M);
    }

    let liveGpsWatchId = null;
    let liveGpsMarker = null;
    let liveGpsAccuracyCircle = null;
    let liveGpsStatusEl = null;
    let liveGpsSamples = [];
    let lastRawGpsLatLng = null;
    let lastSmoothGpsLatLng = null;
    let lastSnappedGpsLatLng = null;
    let liveGpsFollow = true;
    let cachedCampusSegments = null;
    let cachedCampusSegmentCount = 0;
    let gpsAutoTapFallbackActive = false;

    const baseSelectGpsMode = typeof selectGpsMode === 'function' ? selectGpsMode : null;
    const baseSelectPickPathMode = typeof selectPickPathMode === 'function' ? selectPickPathMode : null;
    const baseSelectDefaultMode = typeof selectDefaultMode === 'function' ? selectDefaultMode : null;
    const baseResetRouteSelection = typeof resetRouteSelection === 'function' ? resetRouteSelection : null;

    function toLiveLatLng(point) {
        if (!point) return null;
        if (point instanceof L.LatLng) return L.latLng(Number(point.lat), Number(point.lng));
        if (Array.isArray(point) && point.length >= 2) return L.latLng(Number(point[0]), Number(point[1]));
        if (typeof point === 'object' && point.lat !== undefined && point.lng !== undefined) {
            return L.latLng(Number(point.lat), Number(point.lng));
        }
        return null;
    }

    function makeLiveGpsIcon() {
        return L.divIcon({
            className: 'live-gps-marker',
            html: `
                <div class="live-gps-dot-wrap">
                    <div class="live-gps-pulse"></div>
                    <div class="live-gps-dot"></div>
                    <div class="live-gps-direction-dot"></div>
                </div>
            `,
            iconSize: [34, 34],
            iconAnchor: [17, 17]
        });
    }

    function makeGpsStartIcon() {
        return L.divIcon({
            className: 'gps-route-start-marker',
            html: `<div class="gps-route-start-pin">GPS</div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 17]
        });
    }

    function ensureLiveGpsStatus() {
        if (liveGpsStatusEl) return liveGpsStatusEl;

        liveGpsStatusEl = document.createElement('div');
        liveGpsStatusEl.id = 'live-gps-status-card';
        liveGpsStatusEl.className = 'live-gps-status-card';
        liveGpsStatusEl.innerHTML = `
            <div class="live-gps-status-top">
                <div>
                    <div class="live-gps-status-kicker">Outdoor Live GPS</div>
                    <div class="live-gps-status-title" id="live-gps-status-title">Getting location...</div>
                </div>
                <button type="button" class="live-gps-status-x" onclick="stopOutdoorLiveGpsTracking()">×</button>
            </div>
            <div class="live-gps-status-text" id="live-gps-status-text">Please wait for a better GPS reading.</div>
            <div class="live-gps-status-actions">
                <button type="button" class="live-gps-mini-btn" onclick="toggleOutdoorLiveGpsFollow()" id="live-gps-follow-btn">Follow: ON</button>
                <button type="button" class="live-gps-mini-btn ghost" onclick="selectPickPathMode()">Tap My Location</button>
            </div>
        `;
        document.body.appendChild(liveGpsStatusEl);
        return liveGpsStatusEl;
    }

    function setLiveGpsStatus(kind, title, text) {
        const el = ensureLiveGpsStatus();
        el.classList.remove('good', 'weak', 'bad', 'loading');
        el.classList.add(kind || 'loading');

        const titleEl = document.getElementById('live-gps-status-title');
        const textEl = document.getElementById('live-gps-status-text');
        const followBtn = document.getElementById('live-gps-follow-btn');

        if (titleEl) titleEl.textContent = title || 'Outdoor Live GPS';
        if (textEl) textEl.textContent = text || '';
        if (followBtn) followBtn.textContent = liveGpsFollow ? 'Follow: ON' : 'Follow: OFF';
    }

    function hideLiveGpsStatus() {
        if (liveGpsStatusEl) {
            liveGpsStatusEl.remove();
            liveGpsStatusEl = null;
        }
    }

    function removeLiveGpsMarkerOnly() {
        if (liveGpsMarker) {
            map.removeLayer(liveGpsMarker);
            liveGpsMarker = null;
        }
    }

    function clearGpsStartOnly() {
        if (startSourceType && String(startSourceType).includes('gps')) {
            clearStartMarker();
            startNodeKey = null;
            startSourceType = null;
            updateRouteLabels();
        }
    }

    function closestPointOnSegmentMeters(p, a, b) {
        p = toLiveLatLng(p);
        a = toLiveLatLng(a);
        b = toLiveLatLng(b);
        if (!p || !a || !b) return null;

        const px = p.lng, py = p.lat;
        const ax = a.lng, ay = a.lat;
        const bx = b.lng, by = b.lat;
        const dx = bx - ax;
        const dy = by - ay;

        if (dx === 0 && dy === 0) return a;

        let t = ((px - ax) * dx + (py - ay) * dy) / (dx * dx + dy * dy);
        t = Math.max(0, Math.min(1, t));

        return L.latLng(ay + (t * dy), ax + (t * dx));
    }

    function flattenLiveLatLngs(latlngs, out = []) {
        if (!Array.isArray(latlngs)) return out;
        latlngs.forEach(item => {
            if (Array.isArray(item)) {
                flattenLiveLatLngs(item, out);
            } else {
                const ll = toLiveLatLng(item);
                if (ll && Number.isFinite(ll.lat) && Number.isFinite(ll.lng)) out.push(ll);
            }
        });
        return out;
    }

    function extractSegmentsFromPolylineLayer(layer, segments = []) {
        if (!layer) return segments;

        if (typeof layer.eachLayer === 'function') {
            layer.eachLayer(child => extractSegmentsFromPolylineLayer(child, segments));
            return segments;
        }

        if (typeof layer.getLatLngs === 'function') {
            const points = flattenLiveLatLngs(layer.getLatLngs());
            for (let i = 0; i < points.length - 1; i++) {
                segments.push({ a: points[i], b: points[i + 1], source: 'active_route' });
            }
        }

        return segments;
    }

    function getActiveRouteSegments() {
        const segments = [];
        if (typeof routeLayer !== 'undefined' && routeLayer) {
            extractSegmentsFromPolylineLayer(routeLayer, segments);
        }
        return segments;
    }

    function getCampusPathSegments() {
        const features = pathGeojson?.features || [];
        const count = features.length;

        if (cachedCampusSegments && cachedCampusSegmentCount === count) {
            return cachedCampusSegments;
        }

        const segments = [];
        features.forEach(feature => {
            const geometry = feature?.geometry;
            if (!geometry) return;

            if (geometry.type === 'LineString') {
                const coords = geometry.coordinates || [];
                for (let i = 0; i < coords.length - 1; i++) {
                    segments.push({
                        a: L.latLng(Number(coords[i][1]), Number(coords[i][0])),
                        b: L.latLng(Number(coords[i + 1][1]), Number(coords[i + 1][0])),
                        source: 'campus_path',
                        feature
                    });
                }
            }

            if (geometry.type === 'MultiLineString') {
                (geometry.coordinates || []).forEach(line => {
                    for (let i = 0; i < line.length - 1; i++) {
                        segments.push({
                            a: L.latLng(Number(line[i][1]), Number(line[i][0])),
                            b: L.latLng(Number(line[i + 1][1]), Number(line[i + 1][0])),
                            source: 'campus_path',
                            feature
                        });
                    }
                });
            }
        });

        cachedCampusSegments = segments;
        cachedCampusSegmentCount = count;
        return segments;
    }

    function snapToSegments(gpsLatLng, segments, maxDistanceMeters) {
        gpsLatLng = toLiveLatLng(gpsLatLng);
        if (!gpsLatLng || !segments || !segments.length) {
            return { point: gpsLatLng, distance: Infinity, snapped: false, source: 'raw_gps' };
        }

        let best = null;
        let bestDistance = Infinity;
        let bestSource = 'raw_gps';

        segments.forEach(segment => {
            const candidate = closestPointOnSegmentMeters(gpsLatLng, segment.a, segment.b);
            if (!candidate) return;

            const distance = map.distance(gpsLatLng, candidate);
            if (distance < bestDistance) {
                bestDistance = distance;
                best = candidate;
                bestSource = segment.source || 'path';
            }
        });

        if (best && bestDistance <= maxDistanceMeters) {
            return { point: best, distance: bestDistance, snapped: true, source: bestSource };
        }

        return { point: gpsLatLng, distance: bestDistance, snapped: false, source: 'raw_gps' };
    }

    function snapOutdoorGps(gpsLatLng, accuracy) {
        const activeSnapRadius = getGpsActiveRouteSnapRadiusMeters(accuracy);
        const pathSnapRadius = getGpsSnapRadiusMeters(accuracy);

        const activeRouteSnap = snapToSegments(gpsLatLng, getActiveRouteSegments(), activeSnapRadius);
        if (activeRouteSnap.snapped) {
            return {
                ...activeRouteSnap,
                allowedDistance: activeSnapRadius,
                accuracy: Number(accuracy || 999)
            };
        }

        const campusPathSnap = snapToSegments(gpsLatLng, getCampusPathSegments(), pathSnapRadius);
        if (campusPathSnap.snapped) {
            return {
                ...campusPathSnap,
                allowedDistance: pathSnapRadius,
                accuracy: Number(accuracy || 999)
            };
        }

        const nearestDistance = Math.min(activeRouteSnap.distance || Infinity, campusPathSnap.distance || Infinity);
        return {
            point: gpsLatLng,
            distance: nearestDistance,
            allowedDistance: pathSnapRadius,
            snapped: false,
            source: 'raw_gps',
            accuracy: Number(accuracy || 999)
        };
    }

    function smoothGpsPoint(oldPoint, newPoint, factor = GPS_SMOOTH_FACTOR) {
        oldPoint = toLiveLatLng(oldPoint);
        newPoint = toLiveLatLng(newPoint);
        if (!oldPoint) return newPoint;
        if (!newPoint) return oldPoint;

        return L.latLng(
            oldPoint.lat + ((newPoint.lat - oldPoint.lat) * factor),
            oldPoint.lng + ((newPoint.lng - oldPoint.lng) * factor)
        );
    }

    function collectGpsSample(position) {
        const sample = {
            latLng: L.latLng(Number(position.coords.latitude), Number(position.coords.longitude)),
            accuracy: Number(position.coords.accuracy || 999),
            time: Date.now()
        };

        liveGpsSamples.push(sample);
        liveGpsSamples = liveGpsSamples.slice(-6);
        return sample;
    }

    function getBestGpsSample() {
        if (!liveGpsSamples.length) return null;
        return liveGpsSamples.reduce((best, current) => {
            return current.accuracy < best.accuracy ? current : best;
        }, liveGpsSamples[0]);
    }

    function getGpsSampleSpreadMeters() {
        if (liveGpsSamples.length < 2) return 0;
        const best = getBestGpsSample();
        if (!best) return 0;

        let maxDistance = 0;
        liveGpsSamples.forEach(sample => {
            const d = map.distance(best.latLng, sample.latLng);
            if (d > maxDistance) maxDistance = d;
        });
        return maxDistance;
    }

    function updateRawGpsAccuracyCircle(rawLatLng, accuracy) {
        if (!rawLatLng) return;

        if (!liveGpsAccuracyCircle) {
            liveGpsAccuracyCircle = L.circle(rawLatLng, {
                radius: accuracy || 15,
                color: '#f59e0b',
                weight: 1,
                fillColor: '#fbbf24',
                fillOpacity: 0.07,
                opacity: 0.34,
                interactive: false
            }).addTo(map);
        } else {
            liveGpsAccuracyCircle.setStyle({
                color: '#f59e0b',
                fillColor: '#fbbf24',
                fillOpacity: 0.07,
                opacity: 0.34
            });
            liveGpsAccuracyCircle.setLatLng(rawLatLng);
            liveGpsAccuracyCircle.setRadius(accuracy || 15);
        }
    }

    function updateLiveGpsVisual(rawLatLng, displayLatLng, accuracy) {
        if (!liveGpsMarker) {
            liveGpsMarker = L.marker(displayLatLng, {
                icon: makeLiveGpsIcon(),
                zIndexOffset: 99999,
                interactive: true
            }).addTo(map).bindPopup('Your live GPS location');
        } else {
            liveGpsMarker.setLatLng(displayLatLng);
        }

        if (!liveGpsAccuracyCircle) {
            liveGpsAccuracyCircle = L.circle(rawLatLng, {
                radius: accuracy || 15,
                color: '#2563eb',
                weight: 1,
                fillColor: '#3b82f6',
                fillOpacity: 0.08,
                opacity: 0.35,
                interactive: false
            }).addTo(map);
        } else {
            liveGpsAccuracyCircle.setStyle({
                color: '#2563eb',
                fillColor: '#3b82f6',
                fillOpacity: 0.08,
                opacity: 0.35
            });
            liveGpsAccuracyCircle.setLatLng(rawLatLng);
            liveGpsAccuracyCircle.setRadius(accuracy || 15);
        }
    }

    function updateGpsRouteStart(snappedLatLng, sourceText) {
        if (!snappedLatLng || !isInsideCampus(snappedLatLng.lat, snappedLatLng.lng)) {
            const fallback = entryPoints?.[0];
            if (!fallback) return false;

            const gatewayLat = Number(fallback.latitude);
            const gatewayLng = Number(fallback.longitude);
            const nearestKey = nearestNodeKey(gatewayLat, gatewayLng);
            if (!nearestKey) return false;

            startNodeKey = nearestKey;
            startSourceType = 'gps_outside_campus';
            drawOutsideGuideLine(snappedLatLng.lat, snappedLatLng.lng, gatewayLat, gatewayLng);
            updateRouteLabels();
            return true;
        }

        const nearestKey = nearestNodeKey(snappedLatLng.lat, snappedLatLng.lng);
        if (!nearestKey) return false;

        startNodeKey = nearestKey;
        startSourceType = sourceText || 'live_gps_snapped';

        if (!startMarker) {
            startMarker = L.marker(snappedLatLng, {
                icon: makeGpsStartIcon(),
                zIndexOffset: 99990,
                interactive: false
            }).addTo(map);
        } else {
            startMarker.setLatLng(snappedLatLng);
        }

        clearOutsideGuideLine();
        updateRouteLabels();
        return true;
    }

    function stopOutdoorLiveGpsWatchOnly() {
        if (liveGpsWatchId !== null) {
            navigator.geolocation.clearWatch(liveGpsWatchId);
            liveGpsWatchId = null;
        }

        gpsAutoTapFallbackActive = false;
        liveGpsSamples = [];
        lastRawGpsLatLng = null;
        lastSmoothGpsLatLng = null;
        lastSnappedGpsLatLng = null;
        liveGpsFollow = false;
    }

    function activateTapMyLocationFallback(title, message, rawLatLng = null, accuracy = null) {
        gpsAutoTapFallbackActive = true;

        if (rawLatLng && accuracy !== null) {
            updateRawGpsAccuracyCircle(rawLatLng, accuracy);
        }

        stopOutdoorLiveGpsWatchOnly();
        removeLiveGpsMarkerOnly();
        clearGpsStartOnly();

        placingStartMode = true;
        selectedStartMode = 'path';
        startSourceType = 'path';

        if (typeof setActiveStartModeButton === 'function') {
            setActiveStartModeButton('path');
        }

        if (typeof showPickPathHelper === 'function') {
            showPickPathHelper();
        }

        if (typeof updatePickPathHelperText === 'function') {
            updatePickPathHelperText();
        }

        const fallbackTitle = title || 'Tap My Location enabled';
        const fallbackMessage = message || 'GPS is not accurate enough. Tap your actual current walkway on the map to set an exact start point.';

        setLiveGpsStatus('bad', fallbackTitle, fallbackMessage);
        if (typeof setRouteResultLabel === 'function') {
            setRouteResultLabel(fallbackMessage);
        }
        if (typeof updateRouteLabels === 'function') {
            updateRouteLabels();
        }
    }

    function rejectGpsPoint(rawLatLng, accuracy, title, message) {
        const finalMessage = `${message} Tap your actual current walkway on the map now; your tap will snap to the nearest QGIS path.`;
        activateTapMyLocationFallback(title, finalMessage, rawLatLng, accuracy);
    }

    function handleLiveGpsPosition(position) {
        if (!position || !position.coords) return;

        const latestSample = collectGpsSample(position);
        const bestSample = getBestGpsSample() || latestSample;
        const rawLatLng = bestSample.latLng;
        const accuracy = Number(bestSample.accuracy || 999);
        const spread = getGpsSampleSpreadMeters();

        console.log('[Outdoor Live GPS]', {
            latestAccuracy: Math.round(latestSample.accuracy),
            bestAccuracy: Math.round(accuracy),
            sampleCount: liveGpsSamples.length,
            sampleSpread: Math.round(spread),
            snapRadius: Math.round(getGpsSnapRadiusMeters(accuracy)),
            lat: rawLatLng.lat,
            lng: rawLatLng.lng
        });

        if (liveGpsSamples.length < GPS_MIN_SAMPLES) {
            updateRawGpsAccuracyCircle(latestSample.latLng, latestSample.accuracy);
            removeLiveGpsMarkerOnly();
            setLiveGpsStatus(
                'loading',
                `Checking GPS (${Math.round(latestSample.accuracy)}m)`,
                'Waiting for 4 readings. If GPS is weak, Tap My Location will open automatically.'
            );
            return;
        }

        if (accuracy > GPS_REJECT_ACCURACY_M) {
            rejectGpsPoint(
                rawLatLng,
                accuracy,
                `GPS weak (${Math.round(accuracy)}m)`,
                'GPS is above 25m accuracy, which is too weak for campus routing. Please tap Tap My Location and select your exact walkway.'
            );
            return;
        }

        if (spread > GPS_SAMPLE_SPREAD_M && accuracy > GPS_STRONG_ACCURACY_M) {
            rejectGpsPoint(
                rawLatLng,
                accuracy,
                `GPS unstable (${Math.round(spread)}m drift)`,
                'GPS readings are jumping. Please use Tap My Location for the correct start point.'
            );
            return;
        }

        if (lastRawGpsLatLng) {
            const jumpDistance = map.distance(lastRawGpsLatLng, rawLatLng);
            if (jumpDistance > GPS_BAD_JUMP_DISTANCE_M && accuracy > GPS_STRONG_ACCURACY_M) {
                rejectGpsPoint(
                    rawLatLng,
                    accuracy,
                    `GPS jump detected (${Math.round(jumpDistance)}m)`,
                    'GPS suddenly jumped to another area. Keeping route safe; use Tap My Location if needed.'
                );
                return;
            }
        }

        lastRawGpsLatLng = rawLatLng;

        const snapResult = snapOutdoorGps(rawLatLng, accuracy);
        const snappedLatLng = snapResult.point || null;
        const snapDistanceText = Number.isFinite(snapResult.distance) ? `${Math.round(snapResult.distance)}m from path` : 'no nearby path';
        const snapRadiusText = `${Math.round(snapResult.allowedDistance || getGpsSnapRadiusMeters(accuracy))}m snap radius`;
        const snapText = snapResult.snapped
            ? (snapResult.source === 'active_route' ? `locked to active route (${snapDistanceText}, ${snapRadiusText})` : `snapped to QGIS path (${snapDistanceText}, ${snapRadiusText})`)
            : `not close to any QGIS path (${snapDistanceText}, ${snapRadiusText})`;

        if (!snapResult.snapped || !snappedLatLng) {
            rejectGpsPoint(
                rawLatLng,
                accuracy,
                `GPS off path (${Math.round(accuracy)}m)`,
                `Nearest path is ${snapDistanceText}. GPS snap radius is ${snapRadiusText}. Please move closer to a walkway or use Tap My Location.`
            );
            return;
        }

        const smoothLatLng = smoothGpsPoint(lastSmoothGpsLatLng, snappedLatLng);
        lastSmoothGpsLatLng = smoothLatLng;
        lastSnappedGpsLatLng = snappedLatLng;

        updateLiveGpsVisual(rawLatLng, smoothLatLng, accuracy);

        /*
        | IMPORTANT FOR WEAK CAMPUS GPS:
        | 16-25m accuracy can still be several buildings/paths away.
        | We show the snapped dot as an ESTIMATE only, but we do NOT use it
        | as the official route start. This prevents wrong routing when GPS
        | drifts to the open field even if the user is near the IT Building.
        */
        if (accuracy > GPS_STRONG_ACCURACY_M) {
            /*
            | Weak campus GPS fallback:
            | Do not keep following a misleading GPS dot. Automatically switch
            | to Tap My Location so the user can tap the real walkway once.
            */
            activateTapMyLocationFallback(
                `GPS weak (${Math.round(accuracy)}m)`,
                `GPS snapped as an estimate (${snapText}), but ${Math.round(accuracy)}m is not accurate enough for campus routing. Tap your actual current walkway on the map now; your tap will snap to the nearest QGIS path.`,
                rawLatLng,
                accuracy
            );
            return;
        }

        updateGpsRouteStart(snappedLatLng, 'live_gps_snapped');

        setLiveGpsStatus(
            'good',
            `GPS start accepted (${Math.round(accuracy)}m)`,
            `Location is ${snapText}. Accuracy circle is ${Math.round(accuracy)}m. If this is not your real position, tap Tap My Location.`
        );
        setRouteResultLabel(`GPS start accepted. Accuracy ${Math.round(accuracy)}m; ${snapText}.`);

        if (liveGpsFollow && smoothLatLng) {
            map.panTo(smoothLatLng, { animate: true, duration: 0.35 });
        }
    }

    function handleLiveGpsError(error) {
        console.error('Outdoor live GPS error:', error);

        let message = 'Unable to get GPS location.';
        if (error?.code === 1) message = 'GPS permission denied. Please allow location access.';
        if (error?.code === 2) message = 'GPS position unavailable. Move to an open area or use Tap My Location.';
        if (error?.code === 3) message = 'GPS timed out. Try again or use Tap My Location.';

        setLiveGpsStatus('bad', 'GPS unavailable', message);
        setRouteResultLabel(message);
    }

    function startOutdoorLiveGpsTracking() {
        selectedStartMode = 'gps';
        placingStartMode = false;
        hidePickPathHelper();
        setActiveStartModeButton('gps');

        if (!navigator.geolocation) {
            alert('Geolocation is not supported on this device/browser. Please use Tap My Location.');
            return;
        }

        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            alert('GPS requires HTTPS when deployed online. Please use HTTPS hosting.');
            return;
        }

        if (liveGpsWatchId !== null) {
            liveGpsFollow = true;
            setLiveGpsStatus('loading', 'Outdoor Live GPS already active', 'Follow mode enabled. If location is wrong, tap Tap My Location.');
            return;
        }

        liveGpsSamples = [];
        lastRawGpsLatLng = null;
        lastSmoothGpsLatLng = null;
        lastSnappedGpsLatLng = null;
        liveGpsFollow = true;

        clearCurrentLocationMarker();
        clearStartMarker();
        removeLiveGpsMarkerOnly();

        setLiveGpsStatus('loading', 'Starting live GPS tracking...', 'Live tracking is active. If GPS is weak, the system will automatically ask you to tap your real walkway.');
        setRouteResultLabel('Live GPS tracking started. Accuracy is checked first; weak GPS will automatically switch to Tap My Location.');

        liveGpsWatchId = navigator.geolocation.watchPosition(
            handleLiveGpsPosition,
            handleLiveGpsError,
            {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: 18000
            }
        );
    }

    function stopOutdoorLiveGpsTracking(options = {}) {
        if (liveGpsWatchId !== null) {
            navigator.geolocation.clearWatch(liveGpsWatchId);
            liveGpsWatchId = null;
        }

        liveGpsSamples = [];
        lastRawGpsLatLng = null;
        lastSmoothGpsLatLng = null;
        lastSnappedGpsLatLng = null;
        liveGpsFollow = false;

        if (liveGpsMarker) {
            map.removeLayer(liveGpsMarker);
            liveGpsMarker = null;
        }

        if (liveGpsAccuracyCircle) {
            map.removeLayer(liveGpsAccuracyCircle);
            liveGpsAccuracyCircle = null;
        }

        hideLiveGpsStatus();

        if (!options.keepStart && startSourceType && String(startSourceType).includes('gps')) {
            clearStartMarker();
            startNodeKey = null;
            startSourceType = null;
            updateRouteLabels();
        }
    }

    function toggleOutdoorLiveGpsFollow() {
        liveGpsFollow = !liveGpsFollow;
        setLiveGpsStatus(
            liveGpsFollow ? 'good' : 'loading',
            liveGpsFollow ? 'Follow mode enabled' : 'Follow mode paused',
            liveGpsFollow ? 'The map will follow your accepted/snapped GPS dot.' : 'The dot will update only when GPS is trusted.'
        );

        if (liveGpsFollow && lastSmoothGpsLatLng) {
            map.panTo(lastSmoothGpsLatLng, { animate: true, duration: 0.35 });
        }
    }

    selectGpsMode = function () {
        /* AUTO LOCATE now starts continuous outdoor live tracking. */
        startOutdoorLiveGpsTracking();
    };

    selectPickPathMode = function () {
        stopOutdoorLiveGpsTracking({ keepStart: false });
        if (baseSelectPickPathMode) return baseSelectPickPathMode.apply(this, arguments);
    };

    selectDefaultMode = function () {
        stopOutdoorLiveGpsTracking({ keepStart: false });
        if (baseSelectDefaultMode) return baseSelectDefaultMode.apply(this, arguments);
    };

    if (baseResetRouteSelection) {
        resetRouteSelection = function () {
            stopOutdoorLiveGpsTracking({ keepStart: false });
            return baseResetRouteSelection.apply(this, arguments);
        };
    }

    window.selectGpsMode = selectGpsMode;
    window.selectPickPathMode = selectPickPathMode;
    window.selectDefaultMode = selectDefaultMode;
    window.resetRouteSelection = resetRouteSelection;
    window.startOutdoorLiveGpsTracking = startOutdoorLiveGpsTracking;
    window.stopOutdoorLiveGpsTracking = stopOutdoorLiveGpsTracking;
    window.toggleOutdoorLiveGpsFollow = toggleOutdoorLiveGpsFollow;
    window.activateTapMyLocationFallback = activateTapMyLocationFallback;
})();
