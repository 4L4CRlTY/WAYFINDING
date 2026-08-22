    function setVoiceStatus(text) {
        if (voiceStatusLabel) {
            voiceStatusLabel.textContent = `Voice Status: ${text}`;
        }
    }

    function setHeardText(text = '') {
        if (!voiceHeardText || !voiceHeardValue) return;

        if (!text) {
            voiceHeardText.style.display = 'none';
            voiceHeardValue.textContent = '-';
            return;
        }

        voiceHeardText.style.display = 'block';
        voiceHeardValue.textContent = text;
    }

    function updateVoiceButtonUi() {
        if (!voiceCommandBtn) return;

        if (isVoiceListening) {
            voiceCommandBtn.classList.add('listening');
            voiceCommandBtn.innerHTML = '🛑 Stop';
        } else {
            voiceCommandBtn.classList.remove('listening');
            voiceCommandBtn.innerHTML = '🎤 Voice';
        }
    }

    function initVoiceRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (!SpeechRecognition) {
            voiceSupported = false;
            setVoiceStatus('Not supported in this browser');
            if (voiceCommandBtn) {
                voiceCommandBtn.disabled = true;
                voiceCommandBtn.style.opacity = '0.6';
                voiceCommandBtn.style.cursor = 'not-allowed';
            }
            return;
        }

        voiceSupported = true;
        speechRecognition = new SpeechRecognition();
        speechRecognition.lang = 'en-PH';
        speechRecognition.interimResults = false;
        speechRecognition.maxAlternatives = 1;
        speechRecognition.continuous = false;

        speechRecognition.onstart = function() {
            isVoiceListening = true;
            updateVoiceButtonUi();
            setVoiceStatus('Listening...');
        };

        speechRecognition.onend = function() {
            isVoiceListening = false;
            updateVoiceButtonUi();
            setVoiceStatus('Idle');
        };

        speechRecognition.onerror = function(event) {
            isVoiceListening = false;
            updateVoiceButtonUi();

            const message = event?.error ? String(event.error) : 'Unknown error';
            setVoiceStatus(`Error - ${message}`);

            if (message !== 'no-speech') {
                console.error('Voice recognition error:', message);
            }
        };

        speechRecognition.onresult = function(event) {
            const transcript = Array.from(event.results)
                .map(result => result[0]?.transcript || '')
                .join(' ')
                .trim();

            if (!transcript) {
                setVoiceStatus('No speech detected');
                return;
            }

            if (destinationSearchInput) {
                destinationSearchInput.value = transcript;
            }

            setHeardText(transcript);
            setVoiceStatus('Voice captured');

            searchTextDestination();
        };
    }

    function startVoiceCommand() {
        if (window.WAYFINDING_GUEST_MODE === true) {
            window.showWayfindingToast?.(
                'Voice Search is available after signing in. Please use Browse Options.',
                { kind: 'info' }
            );
            return;
        }

        if (!voiceSupported || !speechRecognition) {
            alert('Voice recognition is not supported in this browser.');
            return;
        }

        setHeardText('');
        setVoiceStatus('Preparing microphone...');

        try {
            speechRecognition.start();
        } catch (error) {
            console.error(error);
        }
    }

    function stopVoiceCommand() {
        if (!speechRecognition) return;

        try {
            speechRecognition.stop();
        } catch (error) {
            console.error(error);
        }
    }

    function toggleVoiceCommand() {
        if (isVoiceListening) {
            stopVoiceCommand();
            return;
        }

        startVoiceCommand();
    }

    function populateLanduseSelect() {
        if (!destinationLanduseSelect) return;

        destinationLanduseSelect.innerHTML = '<option value="">Select Landuse Area</option>';

        const routableLanduses = getRoutableLanduses();

        routableLanduses.forEach(landuse => {
            destinationLanduseSelect.innerHTML += `
            <option value="${landuse.id}">
                ${landuse.name || `Landuse ${landuse.id}`}
            </option>
        `;
        });

        if (!routableLanduses.length) {
            destinationLanduseSelect.innerHTML += `
            <option value="" disabled>No routable landuse available</option>
        `;
        }
    }

    const WAYFINDING_RESPONSE_CACHE_PREFIX = 'wayfinding:last-known:v1:';
    const WAYFINDING_CACHEABLE_ENDPOINT = /^\/api\/(?:buildings|paths|entry-points|building-entrances|hazard-points|landuses|indoor-maps|indoor-rooms|indoor-paths|indoor-entrances|building-entrance-links|indoor-stairs-links|campus-events)(?:\?|$)/;
    const WAYFINDING_MAX_CACHED_RESPONSE_CHARS = 750000;
    const WAYFINDING_SNAPSHOT_URL = document
        .querySelector('meta[name="wayfinding-snapshot"]')
        ?.getAttribute('content') || '/data/campus-snapshot.json';
    const WAYFINDING_SNAPSHOT_ENDPOINTS = new Set([
        '/api/buildings',
        '/api/paths',
        '/api/entry-points',
        '/api/building-entrances',
        '/api/hazard-points',
        '/api/landuses',
        '/api/indoor-maps',
        '/api/indoor-rooms',
        '/api/indoor-paths',
        '/api/indoor-entrances',
        '/api/building-entrance-links',
        '/api/indoor-stairs-links',
        '/api/campus-events',
    ]);
    let wayfindingSnapshotPromise = null;
    let wayfindingSearchIndexPromise = null;
    let wayfindingSearchWorker = null;
    let wayfindingSearchWorkerReady = null;
    let wayfindingSearchWorkerVersion = null;
    let wayfindingSearchWorkerRequestId = 0;
    let latestWayfindingSearchWorkerRequestId = 0;
    let latestDestinationSearchRequestId = 0;
    let activeDestinationSearchController = null;
    const wayfindingSearchWorkerPending = new Map();
    const wayfindingSearchResultCache = new Map();
    const loadedIndoorBuildingIds = new Set();
    const indoorBuildingDataPromises = new Map();

    window.__wayfindingStaleDataUrls = window.__wayfindingStaleDataUrls || new Set();

    function getWayfindingUrlPath(url) {
        try {
            return new URL(url, window.location.origin).pathname;
        } catch (error) {
            return String(url || '').split('?')[0];
        }
    }

    async function loadWayfindingSnapshot() {
        if (wayfindingSnapshotPromise) return wayfindingSnapshotPromise;

        wayfindingSnapshotPromise = fetch(WAYFINDING_SNAPSHOT_URL, {
            cache: 'force-cache',
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(async response => {
                if (!response.ok) {
                    throw new Error(`Campus snapshot returned ${response.status}`);
                }

                const snapshot = await response.json();
                if (
                    Number(snapshot?.schema_version) !== 1 ||
                    !snapshot?.datasets ||
                    typeof snapshot.datasets !== 'object'
                ) {
                    throw new Error('Campus snapshot format is invalid');
                }

                return snapshot;
            })
            .catch(() => null);

        return wayfindingSnapshotPromise;
    }

    function createWayfindingSearchStore(document) {
        if (
            Number(document?.schema_version) === 1 &&
            Array.isArray(document?.search_index)
        ) {
            return {
                compact: false,
                entries: document.search_index,
                rows: document.search_index,
                document: null,
                decodedEntries: new Map()
            };
        }

        if (
            Number(document?.schema_version) !== 2 ||
            document?.format !== 'compact-v1' ||
            !Array.isArray(document?.destinations) ||
            !Array.isArray(document?.search_index)
        ) {
            return null;
        }

        /*
         * Keep the compact rows compact on the main browser thread. Only the
         * handful of winning matches are decoded after the Worker ranks them.
         * This avoids allocating thousands of duplicate result objects when a
         * slower phone opens Search for the first time.
         */
        return {
            compact: true,
            entries: null,
            rows: document.search_index,
            document,
            decodedEntries: new Map()
        };
    }

    function getWayfindingSearchEntry(searchStore, index) {
        if (!searchStore || !Number.isInteger(index) || index < 0) return null;
        if (!searchStore.compact) return searchStore.entries?.[index] || null;
        if (searchStore.decodedEntries.has(index)) {
            return searchStore.decodedEntries.get(index);
        }

        const row = searchStore.rows?.[index];
        const destination = searchStore.document?.destinations?.[Number(row?.[2])];
        const destinationTypes = ['building', 'room', 'landuse'];
        const destinationType = Array.isArray(destination)
            ? destinationTypes[Number(destination[0])]
            : null;
        const destinationId = Number(destination?.[1] || 0);
        if (!Array.isArray(row) || !destinationType || !destinationId) return null;

        const result = {
            destination_type: destinationType,
            destination_id: destinationId,
            label: String(destination[2] || '')
        };

        if (destinationType === 'room') {
            result.room_code = destination[3] ?? null;
            result.building_id = Number(destination[4] || 0) || null;
            result.building_name = destination[5] ?? null;
            result.floor_number = destination[6] === null ? null : Number(destination[6]);
            result.floor_label = destination[7] ?? null;
        }

        const entry = {
            id: Number(row[0] || 0),
            keyword: String(row[1] || ''),
            destination_type: destinationType,
            destination_id: destinationId,
            priority: Number(row[3] || 0),
            result
        };
        searchStore.decodedEntries.set(index, entry);
        return entry;
    }

    async function loadWayfindingSearchIndex() {
        if (wayfindingSearchIndexPromise) return wayfindingSearchIndexPromise;

        wayfindingSearchIndexPromise = loadWayfindingSnapshot()
            .then(async snapshot => {
                if (!snapshot) return null;

                const searchIndexUrl = snapshot.search_index_url || '/data/destination-keywords.json';
                const cacheVersion = Number(snapshot.cache_version || 0);
                const searchIndexParams = new URLSearchParams({ format: '2' });
                if (cacheVersion > 0) {
                    searchIndexParams.set('v', String(cacheVersion));
                }
                const versionedSearchIndexUrl = `${searchIndexUrl}${searchIndexUrl.includes('?') ? '&' : '?'}${searchIndexParams}`;

                const response = await fetch(
                    versionedSearchIndexUrl,
                    {
                        cache: 'force-cache',
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );
                if (!response.ok) return null;

                const document = await response.json();
                const searchStore = createWayfindingSearchStore(document);
                if (
                    !searchStore ||
                    Number(document?.cache_version) !== Number(snapshot.cache_version)
                ) {
                    return null;
                }

                initializeWayfindingSearchWorker(
                    searchStore,
                    Number(document.cache_version)
                );
                return searchStore;
            })
            .catch(() => {
                // A temporary slow/offline failure must not permanently disable
                // the compact index for the rest of the browser session.
                wayfindingSearchIndexPromise = null;
                return null;
            });

        return wayfindingSearchIndexPromise;
    }

    window.preloadWayfindingSearchIndex = function preloadWayfindingSearchIndex() {
        return loadWayfindingSearchIndex().catch(() => null);
    };

    async function readWayfindingSnapshotDataset(url) {
        const pathname = getWayfindingUrlPath(url);
        if (!WAYFINDING_SNAPSHOT_ENDPOINTS.has(pathname)) return null;

        const snapshot = await loadWayfindingSnapshot();
        if (!snapshot || !Object.prototype.hasOwnProperty.call(snapshot.datasets, pathname)) {
            return null;
        }

        const dataset = snapshot.datasets[pathname];

        if (pathname === '/api/campus-events') {
            return normalizeSnapshotCampusEvents(dataset);
        }

        return dataset;
    }

    function mergeIndoorFeatureCollection(target, incoming) {
        const existing = new Map(
            (target?.features || []).map(feature => [
                Number(feature?.properties?.id || 0),
                feature
            ])
        );

        (incoming?.features || []).forEach(feature => {
            existing.set(Number(feature?.properties?.id || 0), normalizeIndoorFeature(feature));
        });

        return {
            type: 'FeatureCollection',
            features: Array.from(existing.values())
        };
    }

    function mergeIndoorLinks(target, incoming) {
        const existing = new Map(
            (target || []).map(link => [Number(link?.id || 0), link])
        );
        (incoming || []).forEach(link => existing.set(Number(link?.id || 0), link));
        return Array.from(existing.values());
    }

    let indoorDataLoaderPromise = null;

    function loadIndoorDataTransport() {
        if (window.WayfindingIndoorDataLoader) {
            return Promise.resolve(window.WayfindingIndoorDataLoader);
        }
        if (indoorDataLoaderPromise) return indoorDataLoaderPromise;

        indoorDataLoaderPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = '/js/wayfinding-indoor-data.js?v=20260815.2';
            script.async = true;
            script.dataset.wayfindingIndoorData = 'true';
            script.addEventListener('load', () => {
                if (window.WayfindingIndoorDataLoader) {
                    resolve(window.WayfindingIndoorDataLoader);
                    return;
                }
                reject(new Error('Indoor data loader did not initialize.'));
            }, { once: true });
            script.addEventListener('error', () => {
                indoorDataLoaderPromise = null;
                reject(new Error('Indoor data loader could not be loaded.'));
            }, { once: true });
            document.head.appendChild(script);
        });

        return indoorDataLoaderPromise;
    }

    async function ensureIndoorBuildingData(buildingId) {
        const normalizedBuildingId = Number(buildingId || 0);
        if (!normalizedBuildingId) return false;
        if (loadedIndoorBuildingIds.has(normalizedBuildingId)) return true;
        if (indoorBuildingDataPromises.has(normalizedBuildingId)) {
            return indoorBuildingDataPromises.get(normalizedBuildingId);
        }

        const promise = (async () => {
            const loader = await loadIndoorDataTransport();
            const datasets = await loader.load(normalizedBuildingId, {
                loadSnapshot: loadWayfindingSnapshot,
                fetchJson
            });

            allIndoorPaths = mergeIndoorFeatureCollection(
                allIndoorPaths,
                datasets['/api/indoor-paths']
            );
            allIndoorEntrances = mergeIndoorFeatureCollection(
                allIndoorEntrances,
                datasets['/api/indoor-entrances']
            );
            allIndoorStairsLinks = mergeIndoorLinks(
                allIndoorStairsLinks,
                datasets['/api/indoor-stairs-links']
            );
            window.clearWayfindingIndoorGraphCache?.(normalizedBuildingId);
            loadedIndoorBuildingIds.add(normalizedBuildingId);
            return true;
        })().finally(() => {
            indoorBuildingDataPromises.delete(normalizedBuildingId);
        });

        indoorBuildingDataPromises.set(normalizedBuildingId, promise);
        return promise;
    }

    window.prefetchWayfindingIndoorBuilding = function(buildingId) {
        return ensureIndoorBuildingData(buildingId).catch(() => false);
    };

    function normalizeSnapshotCampusEvents(events) {
        if (!Array.isArray(events)) return [];

        const now = Date.now();

        return events
            .filter(event => {
                const endsAt = event?.ends_at ? Date.parse(event.ends_at) : null;
                return endsAt === null || Number.isNaN(endsAt) || endsAt >= now;
            })
            .map(event => {
                const startsAt = event?.starts_at ? Date.parse(event.starts_at) : null;
                const endsAt = event?.ends_at ? Date.parse(event.ends_at) : null;
                const isHappeningNow =
                    startsAt !== null
                    && !Number.isNaN(startsAt)
                    && startsAt <= now
                    && (endsAt === null || Number.isNaN(endsAt) || endsAt >= now);

                return {
                    ...event,
                    status: isHappeningNow ? 'happening_now' : 'upcoming',
                };
            });
    }

    function readLastKnownWayfindingResponse(url) {
        if (!WAYFINDING_CACHEABLE_ENDPOINT.test(url)) return null;

        try {
            const cached = window.localStorage.getItem(
                `${WAYFINDING_RESPONSE_CACHE_PREFIX}${url}`
            );
            if (!cached) return null;

            const parsed = JSON.parse(cached);
            if (!parsed || !Object.prototype.hasOwnProperty.call(parsed, 'data')) {
                return null;
            }

            return parsed.data;
        } catch (error) {
            return null;
        }
    }

    function saveLastKnownWayfindingResponse(url, data) {
        if (!WAYFINDING_CACHEABLE_ENDPOINT.test(url)) return;

        try {
            const payload = JSON.stringify({
                savedAt: Date.now(),
                data,
            });

            if (payload.length > WAYFINDING_MAX_CACHED_RESPONSE_CHARS) return;

            window.localStorage.setItem(
                `${WAYFINDING_RESPONSE_CACHE_PREFIX}${url}`,
                payload
            );
        } catch (error) {
            /* Storage quota/privacy mode must never block live map loading. */
        }
    }

    async function fetchJson(url, options = {}) {
        const snapshotData = await readWayfindingSnapshotDataset(url);
        if (snapshotData !== null) {
            saveLastKnownWayfindingResponse(url, snapshotData);
            return snapshotData;
        }

        try {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                },
                signal: options.signal
            });
            if (!res.ok) throw new Error(`Failed to load ${url}`);

            const data = await res.json();
            saveLastKnownWayfindingResponse(url, data);
            return data;
        } catch (error) {
            const lastKnown = readLastKnownWayfindingResponse(url);

            if (lastKnown !== null) {
                window.__wayfindingStaleDataUrls.add(url);
                return lastKnown;
            }

            throw error;
        }
    }

    function failWayfindingSearchWorker(error) {
        wayfindingSearchWorker?.terminate?.();
        wayfindingSearchWorker = null;
        wayfindingSearchWorkerVersion = null;
        wayfindingSearchWorkerReady = Promise.reject(error);
        wayfindingSearchWorkerReady.catch(() => {});
        wayfindingSearchWorkerPending.forEach(({ reject }) => reject(error));
        wayfindingSearchWorkerPending.clear();
    }

    function initializeWayfindingSearchWorker(searchStore, version) {
        if (typeof Worker !== 'function') return Promise.resolve(false);
        if (
            wayfindingSearchWorker
            && wayfindingSearchWorkerVersion === version
            && wayfindingSearchWorkerReady
        ) {
            return wayfindingSearchWorkerReady;
        }

        wayfindingSearchWorker?.terminate?.();
        wayfindingSearchWorkerVersion = version;
        wayfindingSearchWorker = new Worker('/js/wayfinding-search-worker.js');

        wayfindingSearchWorkerReady = new Promise((resolve, reject) => {
            const readyTimeout = window.setTimeout(() => {
                reject(new Error('Search worker initialization timed out.'));
            }, 5000);
            let initialized = false;

            wayfindingSearchWorker.addEventListener('message', event => {
                const message = event.data || {};

                if (message.type === 'ready') {
                    window.clearTimeout(readyTimeout);
                    initialized = true;
                    resolve(true);
                    return;
                }

                const pending = wayfindingSearchWorkerPending.get(message.requestId);
                if (!pending) return;
                wayfindingSearchWorkerPending.delete(message.requestId);

                if (message.type === 'error') {
                    pending.reject(Object.assign(new Error(message.message), {
                        code: message.code || 'SEARCH_WORKER_FAILED'
                    }));
                    return;
                }

                pending.resolve({
                    stale: message.requestId !== latestWayfindingSearchWorkerRequestId,
                    matches: Array.isArray(message.matches) ? message.matches : []
                });
            });

            wayfindingSearchWorker.addEventListener('error', event => {
                window.clearTimeout(readyTimeout);
                const error = new Error(event.message || 'Search worker failed.');
                if (!initialized) reject(error);
                failWayfindingSearchWorker(error);
            });
        }).catch(error => {
            failWayfindingSearchWorker(error);
            return false;
        });

        wayfindingSearchWorker.postMessage({
            type: 'init',
            version,
            entries: searchStore?.compact ? undefined : searchStore?.entries,
            document: searchStore?.compact ? searchStore.document : undefined
        });

        return wayfindingSearchWorkerReady;
    }

    function normalizeSnapshotKeywordText(value) {
        let normalized = String(value || '').toLowerCase();
        const phrasesToRemove = [
            'i want to go to',
            'i wanna go to',
            'i need to go to',
            'take me to',
            'route me to',
            'bring me to',
            'navigate to',
            'go to',
            'where is',
            'find',
            'search',
            'please',
            'room',
            'office',
            'asa ang',
            'asa dapit ang',
            'adto ko sa',
            'ganahan ko moadto sa',
            'moadto ko sa',
            'dad-a ko sa',
            'pangitaa ang',
            'pangita ang',
            'palihog',
            'kwarto',
            'opisina',
        ];

        phrasesToRemove.forEach(phrase => {
            normalized = normalized.replaceAll(phrase, ' ');
        });

        return normalized
            .replace(/[^a-z0-9\s]/gi, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function snapshotKeywordScore(keyword, normalized, queryWords) {
        const candidate = normalizeSnapshotKeywordText(keyword);
        if (!candidate) return -1;

        if (normalized === candidate) {
            return 2000 + candidate.length;
        }

        const boundedCandidate = new RegExp(
            `(^|\\s)${candidate.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(\\s|$)`,
            'u'
        );
        if (boundedCandidate.test(normalized)) {
            return 1700 + candidate.length;
        }
        if (normalized.includes(candidate)) {
            return 1500 + candidate.length;
        }

        const candidateWords = candidate.split(' ').filter(Boolean);
        const commonWords = queryWords.filter(word => candidateWords.includes(word));
        return commonWords.length ? (commonWords.length * 120) + candidate.length : -1;
    }

    function rankSearchIndexSynchronously(searchStore, normalized) {
        const queryWords = normalized.split(' ').filter(Boolean);
        const matches = [];

        (searchStore?.rows || []).forEach((row, index) => {
            const entry = searchStore.compact
                ? null
                : searchStore.entries?.[index];
            const destination = searchStore.compact
                ? searchStore.document?.destinations?.[Number(row?.[2])]
                : null;
            const destinationType = searchStore.compact
                ? ['building', 'room', 'landuse'][Number(destination?.[0])]
                : entry?.destination_type;
            const keyword = searchStore.compact ? row?.[1] : entry?.keyword;
            const hasResult = searchStore.compact
                ? Boolean(destinationType && Number(destination?.[1] || 0))
                : Boolean(entry?.result);
            let score = snapshotKeywordScore(keyword, normalized, queryWords);
            if (score < 100 || !hasResult) return;
            if (destinationType === 'room') score += 350;
            if (destinationType === 'building') score += 120;
            const decodedEntry = getWayfindingSearchEntry(searchStore, index);
            if (!decodedEntry) return;
            matches.push({ entry: decodedEntry, score, index });
        });

        return matches.sort((left, right) =>
            right.score - left.score
            || Number(right.entry.priority || 0) - Number(left.entry.priority || 0)
            || left.index - right.index
        );
    }

    async function rankSearchIndex(searchStore, normalized) {
        const workerReady = await initializeWayfindingSearchWorker(
            searchStore,
            wayfindingSearchWorkerVersion ?? 'runtime'
        );
        if (!workerReady || !wayfindingSearchWorker) {
            return rankSearchIndexSynchronously(searchStore, normalized);
        }

        const requestId = ++wayfindingSearchWorkerRequestId;
        latestWayfindingSearchWorkerRequestId = requestId;

        try {
            const response = await new Promise((resolve, reject) => {
                wayfindingSearchWorkerPending.set(requestId, { resolve, reject });
                wayfindingSearchWorker.postMessage({
                    type: 'search',
                    requestId,
                    query: normalized
                });
            });

            if (response.stale) return [];
            return response.matches
                .map(match => ({
                    entry: getWayfindingSearchEntry(searchStore, Number(match.index)),
                    score: Number(match.score || 0),
                    index: Number(match.index)
                }))
                .filter(match => Boolean(match.entry));
        } catch (error) {
            failWayfindingSearchWorker(error);
            return rankSearchIndexSynchronously(searchStore, normalized);
        }
    }

    function rememberWayfindingSearchResult(normalized, response) {
        if (!response?.success) return response;
        if (wayfindingSearchResultCache.size >= 64) {
            wayfindingSearchResultCache.delete(wayfindingSearchResultCache.keys().next().value);
        }
        wayfindingSearchResultCache.set(normalized, response);
        return response;
    }

    async function findSnapshotKeywordMatch(message) {
        const normalized = normalizeSnapshotKeywordText(message);
        if (!normalized) return null;
        if (wayfindingSearchResultCache.has(normalized)) {
            return wayfindingSearchResultCache.get(normalized);
        }

        const snapshot = await loadWayfindingSnapshot();
        const searchIndex = await loadWayfindingSearchIndex();
        if (!snapshot || !searchIndex?.rows?.length) return null;

        const matches = await rankSearchIndex(searchIndex, normalized);

        if (!matches.length) return null;

        const buildingMatch = matches.find(match =>
            match.entry.destination_type === 'building'
        ) || null;
        const detectedBuildingId = Number(
            buildingMatch?.entry?.result?.destination_id || 0
        );

        if (
            buildingMatch
            && normalized === normalizeSnapshotKeywordText(buildingMatch.entry.keyword)
        ) {
            return rememberWayfindingSearchResult(
                normalized,
                createSnapshotKeywordResponse(buildingMatch.entry)
            );
        }

        const roomMatches = matches.filter(match =>
            match.entry.destination_type === 'room'
        );
        const roomMatch = roomMatches.find(match =>
            !detectedBuildingId
            || Number(match.entry.result?.building_id) === detectedBuildingId
        );

        if (roomMatch) {
            const response = createSnapshotKeywordResponse(roomMatch.entry);
            if (buildingMatch) {
                response.matched_keywords = [
                    buildingMatch.entry.keyword,
                    roomMatch.entry.keyword,
                ];
                response.matched_keyword_ids = [
                    Number(buildingMatch.entry.id),
                    Number(roomMatch.entry.id),
                ];
            }
            return rememberWayfindingSearchResult(normalized, response);
        }

        if (detectedBuildingId && roomMatches.length) {
            return {
                success: false,
                is_keyword_match: false,
                source: 'destination_keywords_snapshot',
                message: 'A room keyword matched, but it is not under the detected building keyword.',
            };
        }

        if (buildingMatch) {
            return rememberWayfindingSearchResult(
                normalized,
                createSnapshotKeywordResponse(buildingMatch.entry)
            );
        }

        const landuseMatch = matches.find(match =>
            match.entry.destination_type === 'landuse'
        );
        return landuseMatch
            ? rememberWayfindingSearchResult(
                normalized,
                createSnapshotKeywordResponse(landuseMatch.entry)
            )
            : null;
    }

    function createSnapshotKeywordResponse(entry) {
        const result = entry?.result;
        if (!result) return null;

        return {
            success: true,
            is_keyword_match: true,
            source: 'destination_keywords_snapshot',
            match_type: entry.destination_type,
            matched_keyword: entry.keyword,
            matched_keywords: [entry.keyword],
            matched_keyword_ids: [Number(entry.id)],
            result,
        };
    }


    /* =========================================================
       BUILDING-AWARE TEXT / VOICE ROOM MATCHING
       If the user's keyword contains a building + room, the selected room
       must come from that detected building even if another building has
       the same room name/code.
    ========================================================= */

    function normalizeDestinationSearchTextFinal(text) {
        return String(text || '')
            .toLowerCase()
            .replace(/[^a-z0-9\s]/gi, ' ')
            .replace(/\b(i|want|wanna|need|to|go|goto|navigate|route|take|bring|me|the|a|an|sa|ko|adto|moadto|asa|ang|dapit|room|office|kwarto|opisina)\b/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function getSearchWordsFinal(text) {
        return normalizeDestinationSearchTextFinal(text)
            .split(' ')
            .map(w => w.trim())
            .filter(Boolean);
    }

    function getBuildingSearchAliasesFinal(building) {
        const rawName = String(building?.name || '').trim();
        const normalizedName = normalizeDestinationSearchTextFinal(rawName);

        const aliases = new Set();

        if (normalizedName) aliases.add(normalizedName);

        /*
        |--------------------------------------------------------------------------
        | Make common building aliases searchable.
        | Examples:
        | "IT Building" => "it"
        | "B3" => "b3"
        | "SMB Building" => "smb"
        |--------------------------------------------------------------------------
        */
        normalizedName
            .replace(/\bbuilding\b/g, ' ')
            .replace(/\bhall\b/g, ' ')
            .replace(/\broom\b/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .split(' ')
            .filter(Boolean)
            .forEach(part => {
                if (part.length >= 2) aliases.add(part);
            });

        const p = building?.properties || {};
        [
            p.code,
            p.short_name,
            p.shortName,
            p.abbreviation,
            p.alias,
            p.aliases
        ].forEach(value => {
            if (Array.isArray(value)) {
                value.forEach(v => {
                    const n = normalizeDestinationSearchTextFinal(v);
                    if (n) aliases.add(n);
                });
            } else {
                const n = normalizeDestinationSearchTextFinal(value);
                if (n) aliases.add(n);
            }
        });

        return Array.from(aliases).filter(Boolean);
    }

    function detectBuildingFromTextFinal(message) {
        const normalized = normalizeDestinationSearchTextFinal(message);
        const words = getSearchWordsFinal(message);

        if (!normalized || !buildingRecords?.length) return null;

        let best = null;
        let bestScore = -1;

        (buildingRecords || []).forEach(building => {
            const aliases = getBuildingSearchAliasesFinal(building);
            let score = -1;

            aliases.forEach(alias => {
                if (!alias) return;

                if (normalized === alias) {
                    score = Math.max(score, 2000 + alias.length);
                    return;
                }

                const exactRegex = new RegExp(`(^|\\s)${alias.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(\\s|$)`, 'i');

                if (exactRegex.test(normalized)) {
                    score = Math.max(score, 1600 + alias.length);
                    return;
                }

                const aliasWords = alias.split(' ').filter(Boolean);
                const common = aliasWords.filter(w => words.includes(w));

                if (common.length) {
                    score = Math.max(score, (common.length * 180) + alias.length);
                }
            });

            if (score > bestScore) {
                bestScore = score;
                best = building;
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Require a meaningful match so random single letters will not win.
        |--------------------------------------------------------------------------
        */
        if (best && bestScore >= 180) {
            return best;
        }

        return null;
    }

    function scoreRoomAgainstTextFinal(roomFeature, message) {
        const normalized = normalizeDestinationSearchTextFinal(message);
        const words = getSearchWordsFinal(message);
        const p = roomFeature?.properties || {};

        const roomName = normalizeDestinationSearchTextFinal(p.name || '');
        const roomCode = normalizeDestinationSearchTextFinal(p.room_code || '');
        const roomType = normalizeDestinationSearchTextFinal(p.type || '');

        let score = -1;

        if (roomCode) {
            const codeRegex = new RegExp(`(^|\\s)${roomCode.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(\\s|$)`, 'i');

            if (normalized === roomCode) {
                score = Math.max(score, 2200 + roomCode.length);
            } else if (codeRegex.test(normalized)) {
                score = Math.max(score, 2000 + roomCode.length);
            } else if (normalized.includes(roomCode)) {
                score = Math.max(score, 1800 + roomCode.length);
            }
        }

        if (roomName) {
            const nameRegex = new RegExp(`(^|\\s)${roomName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(\\s|$)`, 'i');

            if (normalized === roomName) {
                score = Math.max(score, 1700 + roomName.length);
            } else if (nameRegex.test(normalized)) {
                score = Math.max(score, 1500 + roomName.length);
            } else if (normalized.includes(roomName)) {
                score = Math.max(score, 1400 + roomName.length);
            } else {
                const roomWords = roomName.split(' ').filter(Boolean);
                const common = roomWords.filter(w => words.includes(w));

                if (common.length) {
                    score = Math.max(score, (common.length * 120) + roomName.length);
                }
            }
        }

        if (roomType) {
            const typeWords = roomType.split(' ').filter(Boolean);
            const common = typeWords.filter(w => words.includes(w));

            if (common.length) {
                score = Math.max(score, (common.length * 70) + roomType.length);
            }
        }

        return score;
    }

    function findBestRoomInsideBuildingFromTextFinal(buildingId, message) {
        const candidateRooms = (allIndoorRooms.features || []).filter(room =>
            Number(room.properties?.building_id) === Number(buildingId)
        );

        let bestRoom = null;
        let bestScore = -1;

        candidateRooms.forEach(room => {
            const score = scoreRoomAgainstTextFinal(room, message);

            if (score > bestScore) {
                bestScore = score;
                bestRoom = room;
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Score threshold prevents auto-selecting a random room when the text only
        | contains a building and no room keyword.
        |--------------------------------------------------------------------------
        */
        if (bestRoom && bestScore >= 120) {
            return bestRoom;
        }

        return null;
    }

    function makeRoomDestinationResultFinal(roomFeature) {
        const p = roomFeature?.properties || {};

        return {
            destination_type: 'room',
            destination_id: Number(p.id),
            label: p.name || p.room_code || 'Room / Office',
            room_code: p.room_code || null,
            building_id: Number(p.building_id),
            building_name: p.building_name || getBuildingNameById(p.building_id),
            floor_number: p.floor_number ?? null,
            floor_label: p.floor_label || (hasIndoorFloorValue(p.floor_number) ? formatIndoorFloorLabel(p.floor_number) : null)
        };
    }

    function refineTextSearchResultByBuildingContextFinal(apiResult, message) {
        /*
        |--------------------------------------------------------------------------
        | Main rule:
        | If message contains a building, and message also matches a room inside
        | that building, force the selected room to be under that detected building.
        |--------------------------------------------------------------------------
        */
        const detectedBuilding = detectBuildingFromTextFinal(message);

        if (!detectedBuilding) {
            return {
                result: apiResult,
                buildingContextLabel: ''
            };
        }

        const detectedBuildingId = Number(detectedBuilding.id);
        const bestRoomInDetectedBuilding = findBestRoomInsideBuildingFromTextFinal(detectedBuildingId, message);

        if (bestRoomInDetectedBuilding) {
            return {
                result: makeRoomDestinationResultFinal(bestRoomInDetectedBuilding),
                buildingContextLabel: detectedBuilding.name || getBuildingNameById(detectedBuildingId)
            };
        }

        /*
        |--------------------------------------------------------------------------
        | If API returned a room from another building but the text explicitly
        | mentioned a building, do not keep that wrong-room match.
        | Fallback to routing to the detected building only.
        |--------------------------------------------------------------------------
        */
        if (
            apiResult?.destination_type === 'room' &&
            Number(apiResult?.building_id || 0) !== detectedBuildingId
        ) {
            return {
                result: {
                    destination_type: 'building',
                    destination_id: detectedBuildingId,
                    label: detectedBuilding.name || getBuildingNameById(detectedBuildingId)
                },
                buildingContextLabel: detectedBuilding.name || getBuildingNameById(detectedBuildingId)
            };
        }

        return {
            result: apiResult,
            buildingContextLabel: detectedBuilding.name || getBuildingNameById(detectedBuildingId)
        };
    }


    async function searchTextDestination() {
        if (window.WAYFINDING_GUEST_MODE === true) {
            window.showWayfindingToast?.(
                'Text Search is available after signing in. Please use Browse Options.',
                { kind: 'info' }
            );
            return;
        }

        const message = String(destinationSearchInput?.value || '').trim();

        if (!message) {
            alert('Please type your destination message first.');
            return;
        }

        if (!ensureDefaultStartBeforeRoute()) return;

        const searchRequestId = ++latestDestinationSearchRequestId;
        activeDestinationSearchController?.abort?.();
        activeDestinationSearchController = typeof AbortController === 'function'
            ? new AbortController()
            : null;

        try {
            setRouteResultLabel('Preparing search...');
            const searchProgress = document.getElementById('ai-search-progress');
            if (searchProgress && !searchProgress.hidden) {
                searchProgress.textContent = 'Preparing search…';
            }

            // The compact static index is CDN/browser cacheable and avoids a
            // PHP/database request on every search, including slow connections.
            const snapshotResponse = await findSnapshotKeywordMatch(message);
            if (searchRequestId !== latestDestinationSearchRequestId) return;

            if (searchProgress && !searchProgress.hidden) {
                searchProgress.textContent = 'Finding the best route…';
            }

            const apiResponse = snapshotResponse || await fetchJson(
                `/api/search-destination?q=${encodeURIComponent(message)}`,
                { signal: activeDestinationSearchController?.signal }
            );
            if (searchRequestId !== latestDestinationSearchRequestId) return;

            if (!apiResponse || !apiResponse.success || !apiResponse.result) {
                const errorMessage = apiResponse?.message || 'No destination keyword matched your text.';
                alert(errorMessage);
                setRouteResultLabel(errorMessage);
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | STRICT DESTINATION KEYWORD RULE
            |--------------------------------------------------------------------------
            | Do NOT route if the destination was not matched from the
            | destination_keywords database table.
            |
            | The updated ApiController below returns:
            | is_keyword_match: true
            | source: "destination_keywords"
            |
            | This prevents direct/fallback routing to building/room names that are
            | not registered as destination keywords by the admin.
            |--------------------------------------------------------------------------
            */
            const isStrictKeywordMatch =
                apiResponse.is_keyword_match === true ||
                apiResponse.source === 'destination_keywords' ||
                apiResponse.source === 'destination_keywords_snapshot';

            if (!isStrictKeywordMatch) {
                const errorMessage = 'No active destination keyword matched your text. Please ask admin to add this keyword first.';
                alert(errorMessage);
                setRouteResultLabel(errorMessage);
                return;
            }

            const matchedText = [
                ...(Array.isArray(apiResponse.matched_keywords) ? apiResponse.matched_keywords : []),
                apiResponse.matched_keyword || ''
            ].filter(Boolean);

            /* Start the selected building's small graph request before the
               keyboard/panel transition. Search and Voice therefore overlap
               network time with UI cleanup instead of beginning it afterwards. */
            const destinationBuildingId = Number(
                apiResponse.result?.building_id
                || (apiResponse.result?.destination_type === 'building' ? apiResponse.result?.destination_id : 0)
            );
            const indoorDataReady = apiResponse.result?.destination_type === 'room' && destinationBuildingId
                ? ensureIndoorBuildingData(destinationBuildingId).catch(() => false)
                : Promise.resolve(true);

            closeTextSearchModal();

            /* Let the browser hide the assistant panel, dismiss the software
               keyboard, and paint the map before route work starts. Running
               both transitions in one task looked like a frozen Search/Voice
               button on slower phones even though routing was correct. */
            if (window.matchMedia('(hover: none), (pointer: coarse), (max-width: 768px)').matches) {
                await new Promise(resolve => {
                    window.requestAnimationFrame(() => window.setTimeout(resolve, 0));
                });
                if (searchRequestId !== latestDestinationSearchRequestId) return;
            }

            await indoorDataReady;
            if (searchRequestId !== latestDestinationSearchRequestId) return;

            applyTextSearchDestination(apiResponse.result, matchedText.length ? [...new Set(matchedText)].join(' + ') : '');
        } catch (error) {
            if (error?.name === 'AbortError' || searchRequestId !== latestDestinationSearchRequestId) {
                return;
            }
            console.error(error);
            alert('Failed to search destination keyword.');
            setRouteResultLabel('Failed to search destination keyword.');
        } finally {
            if (searchRequestId === latestDestinationSearchRequestId) {
                activeDestinationSearchController = null;
            }
        }
    }

    function applyTextSearchDestination(result, matchedKeyword = '') {
        if (!result || !result.destination_type) {
            alert('Invalid destination result.');
            return;
        }

        const destinationType = String(result.destination_type);

        if (destinationType === 'building') {
            destinationTypeSelect.value = 'building';
            updateDestinationUi();

            selectedDestinationBuildingId = Number(result.destination_id);
            selectedDestinationLanduseId = null;
            selectedIndoorRoomFeature = null;
            selectedBuildingEntranceId = null;

            if (destinationBuildingSelect) {
                destinationBuildingSelect.value = String(result.destination_id);
            }

            if (destinationLanduseSelect) destinationLanduseSelect.value = '';
            if (destinationRoomSelect) destinationRoomSelect.value = '';

            updateRouteLabels();
            setRouteResultLabel(`Matched "${matchedKeyword}" → ${result.label}`);
            findRouteToBuilding(Number(result.destination_id));
            return;
        }

        if (destinationType === 'landuse') {
            destinationTypeSelect.value = 'landuse';
            updateDestinationUi();

            selectedDestinationLanduseId = Number(result.destination_id);
            selectedDestinationBuildingId = null;
            selectedIndoorRoomFeature = null;
            selectedBuildingEntranceId = null;

            if (destinationLanduseSelect) {
                destinationLanduseSelect.value = String(result.destination_id);
            }

            if (destinationBuildingSelect) destinationBuildingSelect.value = '';
            if (destinationRoomSelect) destinationRoomSelect.value = '';

            updateRouteLabels();
            setRouteResultLabel(`Matched "${matchedKeyword}" → ${result.label}`);
            findRouteToLanduse(Number(result.destination_id));
            return;
        }

        if (destinationType === 'room') {
            destinationTypeSelect.value = 'room';
            updateDestinationUi();

            const roomId = Number(result.destination_id);

            if (destinationRoomSelect) {
                destinationRoomSelect.value = String(roomId);
            }

            const room = (allIndoorRooms.features || []).find(
                f => Number(f.properties?.id) === roomId
            );

            if (!room) {
                alert('Matched room exists in database but not found in loaded indoor data.');
                setRouteResultLabel('Matched room not found in indoor data.');
                return;
            }

            selectedIndoorRoomFeature = room;
            selectedDestinationBuildingId = Number(room.properties?.building_id || result.building_id);
            selectedDestinationLanduseId = null;
            selectedBuildingEntranceId = null;

            if (destinationBuildingSelect && selectedDestinationBuildingId) {
                destinationBuildingSelect.value = String(selectedDestinationBuildingId);
            }

            if (destinationLanduseSelect) {
                destinationLanduseSelect.value = '';
            }

            updateRouteLabels();

            const labelParts = [
                result.building_name || room.properties?.building_name || 'Building',
                result.label || room.properties?.name || room.properties?.room_code || 'Room',
                result.floor_label || room.properties?.floor_label || ''
            ].filter(Boolean);

            setRouteResultLabel(`Matched "${matchedKeyword}" → ${labelParts.join(' - ')}`);

            computeCompleteRouteToRoom(room);
            return;
        }

        alert('Unsupported destination type.');
        setRouteResultLabel('Unsupported destination type.');
    }

    function populateDestinationRoomSelect() {
        if (!destinationRoomSelect) return;

        destinationRoomSelect.innerHTML = '<option value="">Select Room / Office</option>';

        const rooms = [...(allIndoorRooms.features || [])].sort((a, b) => {
            const af = getRoomFloorNumber(a) ?? 999;
            const bf = getRoomFloorNumber(b) ?? 999;
            if (af !== bf) return af - bf;

            const aName = String(a.properties?.name || '');
            const bName = String(b.properties?.name || '');
            return aName.localeCompare(bName);
        });

        rooms.forEach(room => {
            const p = room.properties || {};
            const label =
                `${p.name || 'Room'}${p.room_code ? ' (' + p.room_code + ')' : ''} - ${p.building_name || ('Building ' + p.building_id)} - ${formatIndoorFloorLabel(p.floor_number, p.floor_label)}`;

            destinationRoomSelect.innerHTML += `
                <option value="${p.id}">
                    ${escapeWayfindingHtml(label)}
                </option>
            `;
        });

        populateRoomBuildingFilterSelect();
        renderBrowseRoomPicker();
    }
