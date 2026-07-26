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

    window.__wayfindingStaleDataUrls = window.__wayfindingStaleDataUrls || new Set();

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

    async function fetchJson(url) {
        try {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
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
        const message = String(destinationSearchInput?.value || '').trim();

        if (!message) {
            alert('Please type your destination message first.');
            return;
        }

        if (!ensureDefaultStartBeforeRoute()) return;

        try {
            setRouteResultLabel('Checking destination keyword database...');

            const apiResponse = await fetchJson(`/api/search-destination?q=${encodeURIComponent(message)}`);

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
                apiResponse.source === 'destination_keywords';

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

            closeTextSearchModal();
            applyTextSearchDestination(apiResponse.result, matchedText.length ? [...new Set(matchedText)].join(' + ') : '');
        } catch (error) {
            console.error(error);
            alert('Failed to search destination keyword.');
            setRouteResultLabel('Failed to search destination keyword.');
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
                    ${escapeBrowseHtml(label)}
                </option>
            `;
        });

        populateRoomBuildingFilterSelect();
        renderBrowseRoomPicker();
    }
