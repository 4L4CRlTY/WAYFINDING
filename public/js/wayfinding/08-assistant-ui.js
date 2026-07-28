    /* =========================================================
       VOICE PANEL PERSIST RESULT PATCH
       The voice panel will NOT auto-close after recognition.
       It displays the spoken text and waits for manual close.
    ========================================================= */

    let aiVoiceFinalTranscript = '';
    let aiVoiceSearchInProgress = false;
    let aiVoiceAllowAutoClose = false;
    let restartInlineVoiceSearch = function () {};

    function getAiVoicePanel() {
        return document.getElementById('ai-voice-panel');
    }

    function getAiVoiceResultCard() {
        return document.getElementById('ai-voice-result-card');
    }

    function getAiVoiceResultText() {
        return document.getElementById('ai-voice-result-text');
    }

    function resetAiVoiceResultUi() {
        aiVoiceFinalTranscript = '';

        const panel = getAiVoicePanel();
        const card = getAiVoiceResultCard();
        const text = getAiVoiceResultText();

        if (panel) panel.classList.remove('voice-finished');

        if (card) card.style.display = 'none';
        if (text) text.textContent = '-';

        if (typeof setHeardText === 'function') {
            setHeardText('');
        }
    }

    function showAiVoiceResultUi(transcript = '') {
        const cleanTranscript = String(transcript || '').trim();
        const panel = getAiVoicePanel();
        const card = getAiVoiceResultCard();
        const text = getAiVoiceResultText();

        if (panel) panel.classList.add('voice-finished');

        if (cleanTranscript) {
            aiVoiceFinalTranscript = cleanTranscript;
        }

        if (card) card.style.display = 'block';
        if (text) text.textContent = aiVoiceFinalTranscript ||
            'No clear speech detected. You can try Voice Search again.';

        if (typeof setHeardText === 'function') {
            setHeardText(aiVoiceFinalTranscript || 'No clear speech detected.');
        }

        if (typeof setVoiceStatus === 'function') {
            setVoiceStatus('Stopped. Result is displayed below.');
        }

        if (typeof setRouteResultLabel === 'function') {
            setRouteResultLabel(aiVoiceFinalTranscript ? 'Voice captured. Review the detected text.' :
                'Voice stopped. No clear speech detected.');
        }
    }

    function keepVoicePanelOpenAfterStop(transcript = '') {
        const panel = getAiVoicePanel();
        const dock = document.getElementById('floating-route-ui');

        if (dock) dock.classList.add('transforming', 'voice-mode');
        if (panel) panel.style.display = 'block';

        showAiVoiceResultUi(transcript);
    }

    /*
       Override close behavior:
       - Manual close button closes panel.
       - Speech recognition ending does NOT close panel.
    */
    const __baseCloseAiTransformPanel = typeof closeAiTransformPanel === 'function' ? closeAiTransformPanel : null;
    closeAiTransformPanel = function() {
        aiVoiceAllowAutoClose = true;
        aiVoiceSearchInProgress = false;

        if (typeof stopVoiceCommand === 'function' && typeof isVoiceListening !== 'undefined' && isVoiceListening) {
            stopVoiceCommand();
        }

        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const dock = document.getElementById('floating-route-ui');

        if (searchPanel) searchPanel.style.display = 'none';
        if (voicePanel) {
            voicePanel.style.display = 'none';
            voicePanel.classList.remove('voice-finished');
        }

        if (dock) dock.classList.remove('transforming', 'search-mode', 'voice-mode');

        resetAiVoiceResultUi();
        setRouteResultLabel('Ready');
    };

    const __baseOpenInlineVoiceSearch = typeof openInlineVoiceSearch === 'function' ? openInlineVoiceSearch : null;
    openInlineVoiceSearch = function() {
        resetAiVoiceResultUi();

        aiVoiceAllowAutoClose = false;
        aiVoiceSearchInProgress = true;

        if (typeof showAiVoicePanel === 'function') {
            showAiVoicePanel();
        } else {
            const panel = getAiVoicePanel();
            const dock = document.getElementById('floating-route-ui');
            if (dock) dock.classList.add('transforming', 'voice-mode');
            if (panel) panel.style.display = 'block';
        }

        if (typeof ensureDefaultStartBeforeRoute === 'function') {
            if (!ensureDefaultStartBeforeRoute()) return;
        }

        if (typeof toggleVoiceCommand === 'function') {
            toggleVoiceCommand();
        } else {
            console.error('toggleVoiceCommand() not found.');
            alert('Voice command function not found.');
        }
    };

    const __baseStartVoiceSearchFlow = typeof startVoiceSearchFlow === 'function' ? startVoiceSearchFlow : null;
    startVoiceSearchFlow = function() {
        openInlineVoiceSearch();
    };

    const __baseStopInlineVoiceSearch = typeof stopInlineVoiceSearch === 'function' ? stopInlineVoiceSearch : null;
    stopInlineVoiceSearch = function() {
        aiVoiceSearchInProgress = false;

        if (typeof stopVoiceCommand === 'function') {
            stopVoiceCommand();
        }

        keepVoicePanelOpenAfterStop(aiVoiceFinalTranscript);
    };

    /*
       Wrap speech-recognition init so onresult/onend always leaves the panel open
       and displays the transcript.
    */
    const __baseInitVoiceRecognition = typeof initVoiceRecognition === 'function' ? initVoiceRecognition : null;
    if (__baseInitVoiceRecognition) {
        initVoiceRecognition = function() {
            __baseInitVoiceRecognition();

            if (!speechRecognition) return;

            const originalOnResult = speechRecognition.onresult;
            const originalOnEnd = speechRecognition.onend;
            const originalOnError = speechRecognition.onerror;

            speechRecognition.onresult = function(event) {
                let transcript = '';

                try {
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        transcript += event.results[i][0].transcript;
                    }
                } catch (e) {}

                transcript = String(transcript || '').trim();

                if (transcript) {
                    aiVoiceFinalTranscript = transcript;
                    showAiVoiceResultUi(transcript);
                }

                if (typeof originalOnResult === 'function') {
                    originalOnResult.call(this, event);
                }

                const input = document.getElementById('destination-search-input');
                if (input && aiVoiceFinalTranscript) {
                    input.value = aiVoiceFinalTranscript;
                }

                keepVoicePanelOpenAfterStop(aiVoiceFinalTranscript);
            };

            speechRecognition.onend = function(event) {
                if (typeof originalOnEnd === 'function') {
                    originalOnEnd.call(this, event);
                }

                if (!aiVoiceAllowAutoClose) {
                    isVoiceListening = false;
                    updateVoiceButtonUi();
                    keepVoicePanelOpenAfterStop(aiVoiceFinalTranscript);
                }
            };

            speechRecognition.onerror = function(event) {
                if (typeof originalOnError === 'function') {
                    originalOnError.call(this, event);
                }

                if (!aiVoiceAllowAutoClose) {
                    isVoiceListening = false;
                    updateVoiceButtonUi();
                    keepVoicePanelOpenAfterStop(aiVoiceFinalTranscript);
                }
            };
        };
    }

    /*
       If the original voice logic routes automatically after voice result,
       keep panel visible instead of closing.
    */
    const __baseSearchTextDestination = typeof searchTextDestination === 'function' ? searchTextDestination : null;
    if (__baseSearchTextDestination) {
        searchTextDestination = async function() {
            await __baseSearchTextDestination();

            const voicePanel = getAiVoicePanel();
            if (voicePanel && voicePanel.style.display === 'block') {
                keepVoicePanelOpenAfterStop(aiVoiceFinalTranscript || (document.getElementById(
                    'destination-search-input')?.value || ''));
            }
        };
    }

    window.openInlineVoiceSearch = openInlineVoiceSearch;
    window.startVoiceSearchFlow = startVoiceSearchFlow;
    window.stopInlineVoiceSearch = stopInlineVoiceSearch;
    window.closeAiTransformPanel = closeAiTransformPanel;
    window.searchTextDestination = searchTextDestination;


    initVoiceRecognition();
    updateVoiceButtonUi();
    setVoiceStatus(voiceSupported ? 'Idle' : 'Not supported in this browser');
    setHeardText('');

    window.enablePathStartPlacement = enablePathStartPlacement;
    window.useCurrentLocationAsStart = useCurrentLocationAsStart;
    window.useDefaultEntryPointAsStart = useDefaultEntryPointAsStart;
    window.findRouteByDestination = findRouteByDestination;
    window.resetRouteSelection = resetRouteSelection;
    window.openIndoorPanelForBuilding = openIndoorPanelForBuilding;
    window.closeIndoorPanelFn = closeIndoorPanelFn;
    window.searchTextDestination = searchTextDestination;
    window.toggleVoiceCommand = toggleVoiceCommand;
    window.startVoiceCommand = startVoiceCommand;
    window.stopVoiceCommand = stopVoiceCommand;

    window.toggleDestinationMenu = toggleDestinationMenu;

    window.toggleFloatingActionCard = toggleFloatingActionCard;
    window.closeFloatingActionCard = closeFloatingActionCard;
    window.openTextSearchModal = openTextSearchModal;
    window.closeTextSearchModal = closeTextSearchModal;
    window.openBrowseOptionsModal = openBrowseOptionsModal;
    window.closeBrowseOptionsModal = closeBrowseOptionsModal;
    window.startVoiceSearchFlow = startVoiceSearchFlow;

    window.selectPickPathMode = selectPickPathMode;
    window.selectGpsMode = selectGpsMode;
    window.selectDefaultMode = selectDefaultMode;

    window.selectLanduseDestination = function(landuseId) {
        const landuse = (landuseRecords || []).find(item => Number(item.id) === Number(landuseId));

        if (landuse && isDesignLanduse(landuse)) {
            selectedDestinationLanduseId = null;
            updateRouteLabels();
            setRouteResultLabel('Design landuse is display-only and not available for routing.');
            return;
        }

        destinationTypeSelect.value = 'landuse';
        updateDestinationUi();

        if (destinationLanduseSelect) {
            destinationLanduseSelect.value = String(landuseId);
        }

        selectedDestinationLanduseId = Number(landuseId);
        selectedDestinationBuildingId = null;
        selectedIndoorRoomFeature = null;
        selectedBuildingEntranceId = null;

        updateRouteLabels();
        setRouteResultLabel('Landuse selected. Click Find Route.');
    };

    /* =========================================================
       AI ORB TRANSFORM BEHAVIOR
       Search and Voice transform the main button area.
    ========================================================= */

    function hideAiPanels() {
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const dock = document.getElementById('floating-route-ui');

        if (searchPanel) searchPanel.style.display = 'none';
        if (voicePanel) voicePanel.style.display = 'none';
        if (dock) dock.classList.remove('transforming', 'search-mode', 'voice-mode');
    }

    function showAiSearchPanel() {
        const searchPanel = document.getElementById('ai-search-panel');
        const dock = document.getElementById('floating-route-ui');

        closeFloatingActionCard();
        hideAiPanels();

        if (dock) dock.classList.add('transforming', 'search-mode');
        if (searchPanel) searchPanel.style.display = 'block';

        setTimeout(() => {
            const input = document.getElementById('destination-search-input');
            if (input) input.focus();
        }, 80);
    }

    function showAiVoicePanel() {
        const voicePanel = document.getElementById('ai-voice-panel');
        const dock = document.getElementById('floating-route-ui');

        closeFloatingActionCard();
        hideAiPanels();

        if (dock) dock.classList.add('transforming', 'voice-mode');
        if (voicePanel) voicePanel.style.display = 'block';
    }

    function closeAiTransformPanel() {
        if (typeof stopVoiceCommand === 'function' && isVoiceListening) {
            stopVoiceCommand();
        }

        hideAiPanels();
        setRouteResultLabel('Ready');
    }

    function openInlineTextSearch() {
        showAiSearchPanel();
    }

    function openInlineVoiceSearch() {
        showAiVoicePanel();

        if (typeof ensureDefaultStartBeforeRoute === 'function') {
            if (!ensureDefaultStartBeforeRoute()) return;
        }

        if (typeof toggleVoiceCommand === 'function') {
            toggleVoiceCommand();
        } else {
            console.error('toggleVoiceCommand() not found.');
            alert('Voice command function not found.');
        }
    }

    function stopInlineVoiceSearch() {
        if (typeof stopVoiceCommand === 'function') {
            stopVoiceCommand();
        }

        closeAiTransformPanel();
    }

    const __originalOpenTextSearchModal = typeof openTextSearchModal === 'function' ? openTextSearchModal : null;
    openTextSearchModal = function() {
        openInlineTextSearch();
    };

    const __originalCloseTextSearchModal = typeof closeTextSearchModal === 'function' ? closeTextSearchModal : null;
    closeTextSearchModal = function() {
        closeAiTransformPanel();
    };

    const __originalStartVoiceSearchFlow = typeof startVoiceSearchFlow === 'function' ? startVoiceSearchFlow : null;
    startVoiceSearchFlow = function() {
        openInlineVoiceSearch();
    };

    const __originalCloseBrowseOptionsModal = typeof closeBrowseOptionsModal === 'function' ? closeBrowseOptionsModal :
        null;
    closeBrowseOptionsModal = function() {
        const modal = document.getElementById('browseOptionsModal');
        if (modal) modal.style.display = 'none';
        hideAiPanels();
    };

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeAiTransformPanel();
        }
    });

    document.addEventListener('click', function(event) {
        const dock = document.getElementById('floating-route-ui');
        const browseModal = document.getElementById('browseOptionsModal');
        const clickedDock = dock && dock.contains(event.target);
        const clickedBrowse = browseModal && browseModal.contains(event.target);

        if (!clickedDock && !clickedBrowse) {
            const searchPanel = document.getElementById('ai-search-panel');
            const voicePanel = document.getElementById('ai-voice-panel');
            const hasTransformOpen =
                (searchPanel && searchPanel.style.display === 'block') ||
                (voicePanel && voicePanel.style.display === 'block');

            if (hasTransformOpen) {
                closeAiTransformPanel();
            }
        }
    });

    window.openInlineTextSearch = openInlineTextSearch;
    window.openInlineVoiceSearch = openInlineVoiceSearch;
    window.stopInlineVoiceSearch = stopInlineVoiceSearch;
    window.closeAiTransformPanel = closeAiTransformPanel;
    window.openTextSearchModal = openTextSearchModal;
    window.closeTextSearchModal = closeTextSearchModal;
    window.startVoiceSearchFlow = startVoiceSearchFlow;
    window.closeBrowseOptionsModal = closeBrowseOptionsModal;


    /* =========================================================
       FINAL NO AUTO-CLOSE PATCH
       - Voice panel stays open after result.
       - Text search panel stays open after result.
       - Only the user close button / ESC closes transform panel.
    ========================================================= */

    let aiLastTextSearchValue = '';
    let aiLastTextSearchResult = '';
    let aiLastVoiceTranscript = '';

    function getAiSearchPanelFinal() {
        return document.getElementById('ai-search-panel');
    }

    function getAiTextResultCardFinal() {
        return document.getElementById('ai-text-result-card');
    }

    function getAiTextResultTextFinal() {
        return document.getElementById('ai-text-result-text');
    }

    function showAiTextResultFinal(message) {
        const panel = getAiSearchPanelFinal();
        const card = getAiTextResultCardFinal();
        const text = getAiTextResultTextFinal();

        if (panel) {
            panel.style.display = 'block';
            panel.classList.add('search-finished');
        }

        const dock = document.getElementById('floating-route-ui');
        if (dock) {
            dock.classList.add('transforming', 'search-mode');
            dock.classList.remove('voice-mode');
        }

        if (card) card.style.display = 'block';
        if (text) text.textContent = message || 'Search completed.';

        if (typeof setRouteResultLabel === 'function') {
            setRouteResultLabel(message || 'Search completed. Review the result.');
        }
    }

    function showAiVoiceResultFinal(transcript) {
        const panel = document.getElementById('ai-voice-panel');
        const card = document.getElementById('ai-voice-result-card');
        const text = document.getElementById('ai-voice-result-text');
        const dock = document.getElementById('floating-route-ui');

        if (dock) {
            dock.classList.add('transforming', 'voice-mode');
            dock.classList.remove('search-mode');
        }

        if (panel) {
            panel.style.display = 'block';
            panel.classList.add('voice-finished');
        }

        if (card) card.style.display = 'block';

        const clean = String(transcript || aiLastVoiceTranscript || '').trim();
        aiLastVoiceTranscript = clean;

        if (text) {
            text.textContent = clean || 'No clear speech detected. You can try Voice Search again.';
        }

        if (typeof setHeardText === 'function') {
            setHeardText(clean || 'No clear speech detected.');
        }

        if (typeof setVoiceStatus === 'function') {
            setVoiceStatus('Stopped. Result is displayed below.');
        }

        if (typeof setRouteResultLabel === 'function') {
            setRouteResultLabel(clean ? 'Voice captured. Review the detected text.' :
                'Voice stopped. No clear speech detected.');
        }
    }

    /* Only manual close should hide the active transformed panel. */
    closeAiTransformPanel = function() {
        aiVoiceAllowAutoClose = true;
        aiVoiceSearchInProgress = false;

        if (typeof stopVoiceCommand === 'function' && typeof isVoiceListening !== 'undefined' && isVoiceListening) {
            stopVoiceCommand();
        }

        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const dock = document.getElementById('floating-route-ui');

        if (searchPanel) {
            searchPanel.style.display = 'none';
            searchPanel.classList.remove('search-finished');
        }

        if (voicePanel) {
            voicePanel.style.display = 'none';
            voicePanel.classList.remove('voice-finished');
        }

        if (dock) dock.classList.remove('transforming', 'search-mode', 'voice-mode');

        const textCard = document.getElementById('ai-text-result-card');
        const voiceCard = document.getElementById('ai-voice-result-card');

        if (textCard) textCard.style.display = 'none';
        if (voiceCard) voiceCard.style.display = 'none';

        aiLastTextSearchValue = '';
        aiLastTextSearchResult = '';
        aiLastVoiceTranscript = '';

        if (typeof setHeardText === 'function') setHeardText('');
        if (typeof setRouteResultLabel === 'function') setRouteResultLabel('Ready');
    };

    openInlineTextSearch = function() {
        closeFloatingActionCard();

        const dock = document.getElementById('floating-route-ui');
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');

        if (voicePanel) voicePanel.style.display = 'none';

        if (dock) {
            dock.classList.add('transforming', 'search-mode');
            dock.classList.remove('voice-mode');
        }

        if (searchPanel) {
            searchPanel.style.display = 'block';
            searchPanel.classList.remove('search-finished');
        }

        const card = document.getElementById('ai-text-result-card');
        if (card) card.style.display = 'none';

        setTimeout(() => {
            const input = document.getElementById('destination-search-input');
            if (input) input.focus();
        }, 80);
    };

    openInlineVoiceSearch = function() {
        closeFloatingActionCard();

        const dock = document.getElementById('floating-route-ui');
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');

        if (searchPanel) searchPanel.style.display = 'none';

        if (dock) {
            dock.classList.add('transforming', 'voice-mode');
            dock.classList.remove('search-mode');
        }

        if (voicePanel) {
            voicePanel.style.display = 'block';
            voicePanel.classList.remove('voice-finished');
        }

        const card = document.getElementById('ai-voice-result-card');
        const text = document.getElementById('ai-voice-result-text');
        if (card) card.style.display = 'none';
        if (text) text.textContent = '-';

        aiVoiceAllowAutoClose = false;
        aiVoiceSearchInProgress = true;
        aiLastVoiceTranscript = '';

        if (typeof ensureDefaultStartBeforeRoute === 'function') {
            if (!ensureDefaultStartBeforeRoute()) return;
        }

        if (typeof toggleVoiceCommand === 'function') {
            toggleVoiceCommand();
        } else {
            console.error('toggleVoiceCommand() not found.');
            alert('Voice command function not found.');
        }
    };

    startVoiceSearchFlow = function() {
        openInlineVoiceSearch();
    };

    stopInlineVoiceSearch = function() {
        aiVoiceSearchInProgress = false;

        if (typeof stopVoiceCommand === 'function') {
            stopVoiceCommand();
        }

        showAiVoiceResultFinal(aiLastVoiceTranscript);
    };

    /*
       Text search override:
       This intentionally does NOT close the search panel after route/search completes.
    */
    const __noCloseSearchTextDestinationBase = searchTextDestination;
    searchTextDestination = async function() {
        const input = document.getElementById('destination-search-input');
        aiLastTextSearchValue = String(input?.value || '').trim();

        await __noCloseSearchTextDestinationBase();

        const routeText = document.getElementById('route-result-label')?.textContent || '';
        const message = routeText && routeText !== 'Ready' ?
            routeText :
            (aiLastTextSearchValue ? `Search completed for: ${aiLastTextSearchValue}` : 'Search completed.');

        aiLastTextSearchResult = message;
        showAiTextResultFinal(message);
    };

    /*
       Re-wrap speech recognition after all previous patches.
       onend/result must NOT close the panel.
    */
    const __noCloseInitVoiceRecognitionBase = initVoiceRecognition;
    initVoiceRecognition = function() {
        __noCloseInitVoiceRecognitionBase();

        if (!speechRecognition) return;

        const previousResult = speechRecognition.onresult;
        const previousEnd = speechRecognition.onend;
        const previousError = speechRecognition.onerror;

        speechRecognition.onresult = function(event) {
            let transcript = '';

            try {
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    transcript += event.results[i][0].transcript;
                }
            } catch (e) {}

            transcript = String(transcript || '').trim();

            if (transcript) {
                aiLastVoiceTranscript = transcript;

                const input = document.getElementById('destination-search-input');
                if (input) input.value = transcript;
            }

            if (typeof previousResult === 'function') {
                previousResult.call(this, event);
            }

            showAiVoiceResultFinal(aiLastVoiceTranscript || transcript);
        };

        speechRecognition.onend = function(event) {
            if (typeof previousEnd === 'function') {
                previousEnd.call(this, event);
            }

            isVoiceListening = false;
            if (typeof updateVoiceButtonUi === 'function') updateVoiceButtonUi();

            if (!aiVoiceAllowAutoClose) {
                showAiVoiceResultFinal(aiLastVoiceTranscript);
            }
        };

        speechRecognition.onerror = function(event) {
            if (typeof previousError === 'function') {
                previousError.call(this, event);
            }

            isVoiceListening = false;
            if (typeof updateVoiceButtonUi === 'function') updateVoiceButtonUi();

            if (!aiVoiceAllowAutoClose) {
                showAiVoiceResultFinal(aiLastVoiceTranscript);
            }
        };
    };

    /*
       If voice was already initialized before this final patch,
       apply patched handlers immediately.
    */
    if (speechRecognition) {
        initVoiceRecognition();
    }

    window.openInlineTextSearch = openInlineTextSearch;
    window.openInlineVoiceSearch = openInlineVoiceSearch;
    window.startVoiceSearchFlow = startVoiceSearchFlow;
    window.stopInlineVoiceSearch = stopInlineVoiceSearch;
    window.closeAiTransformPanel = closeAiTransformPanel;
    window.searchTextDestination = searchTextDestination;


    /* =========================================================
       RECORD AGAIN + NO AUTOCLOSE FINAL PATCH
       - Record button beside Search transforms to voice recorder.
       - Record Again restarts speech recognition without closing panel.
       - Text and Voice result panels stay open until manual close.
    ========================================================= */

    function resetVoiceUiForNewRecordingFinal() {
        const voicePanel = document.getElementById('ai-voice-panel');
        const resultCard = document.getElementById('ai-voice-result-card');
        const resultText = document.getElementById('ai-voice-result-text');

        if (voicePanel) voicePanel.classList.remove('voice-finished');
        if (resultCard) resultCard.style.display = 'none';
        if (resultText) resultText.textContent = '-';

        if (typeof setHeardText === 'function') setHeardText('');
        if (typeof setVoiceStatus === 'function') setVoiceStatus('Listening...');
        if (typeof setRouteResultLabel === 'function') setRouteResultLabel('Listening for your destination...');
    }

    function keepVoicePanelOpenFinal(transcript = '') {
        const dock = document.getElementById('floating-route-ui');
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const resultCard = document.getElementById('ai-voice-result-card');
        const resultText = document.getElementById('ai-voice-result-text');

        if (dock) {
            dock.classList.add('transforming', 'voice-mode');
            dock.classList.remove('search-mode');
        }

        if (searchPanel) searchPanel.style.display = 'none';

        if (voicePanel) {
            voicePanel.style.display = 'block';
            voicePanel.classList.add('voice-finished');
        }

        const clean = String(transcript || window.aiLastVoiceTranscript || '').trim();
        window.aiLastVoiceTranscript = clean;

        if (resultCard) resultCard.style.display = 'block';
        if (resultText) {
            resultText.textContent = clean || 'No clear speech detected. Click Record Again to try again.';
        }

        if (typeof setHeardText === 'function') {
            setHeardText(clean || 'No clear speech detected.');
        }

        if (typeof setVoiceStatus === 'function') {
            setVoiceStatus('Stopped. Result is displayed below.');
        }

        if (typeof setRouteResultLabel === 'function') {
            setRouteResultLabel(clean ? 'Voice captured. You can record again or close this panel.' :
                'Voice stopped. You can record again.');
        }
    }

    function keepTextPanelOpenFinal(message = '') {
        const dock = document.getElementById('floating-route-ui');
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const resultCard = document.getElementById('ai-text-result-card');
        const resultText = document.getElementById('ai-text-result-text');

        if (dock) {
            dock.classList.add('transforming', 'search-mode');
            dock.classList.remove('voice-mode');
        }

        if (voicePanel) voicePanel.style.display = 'none';

        if (searchPanel) {
            searchPanel.style.display = 'block';
            searchPanel.classList.add('search-finished');
        }

        if (resultCard) resultCard.style.display = 'block';
        if (resultText) resultText.textContent = message || 'Search completed.';

        if (typeof setRouteResultLabel === 'function') {
            setRouteResultLabel(message || 'Search completed. You can record again or close this panel.');
        }
    }

    closeAiTransformPanel = function() {
        window.aiVoiceAllowAutoClose = true;
        window.aiVoiceSearchInProgress = false;

        if (typeof stopVoiceCommand === 'function' && typeof isVoiceListening !== 'undefined' && isVoiceListening) {
            stopVoiceCommand();
        }

        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const dock = document.getElementById('floating-route-ui');

        if (searchPanel) {
            searchPanel.style.display = 'none';
            searchPanel.classList.remove('search-finished');
        }

        if (voicePanel) {
            voicePanel.style.display = 'none';
            voicePanel.classList.remove('voice-finished');
        }

        const textCard = document.getElementById('ai-text-result-card');
        const voiceCard = document.getElementById('ai-voice-result-card');

        if (textCard) textCard.style.display = 'none';
        if (voiceCard) voiceCard.style.display = 'none';

        if (dock) dock.classList.remove('transforming', 'search-mode', 'voice-mode');

        window.aiLastVoiceTranscript = '';
        window.aiLastTextSearchValue = '';
        window.aiLastTextSearchResult = '';

        if (typeof setHeardText === 'function') setHeardText('');
        if (typeof setRouteResultLabel === 'function') setRouteResultLabel('Ready');
    };

    openInlineTextSearch = function() {
        if (typeof closeFloatingActionCard === 'function') closeFloatingActionCard();

        const dock = document.getElementById('floating-route-ui');
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');
        const textCard = document.getElementById('ai-text-result-card');

        if (voicePanel) voicePanel.style.display = 'none';

        if (dock) {
            dock.classList.add('transforming', 'search-mode');
            dock.classList.remove('voice-mode');
        }

        if (searchPanel) {
            searchPanel.style.display = 'block';
            searchPanel.classList.remove('search-finished');
        }

        if (textCard) textCard.style.display = 'none';

        setTimeout(() => {
            const input = document.getElementById('destination-search-input');
            if (input) input.focus();
        }, 80);
    };

    openInlineVoiceSearch = function() {
        if (typeof closeFloatingActionCard === 'function') closeFloatingActionCard();

        const dock = document.getElementById('floating-route-ui');
        const searchPanel = document.getElementById('ai-search-panel');
        const voicePanel = document.getElementById('ai-voice-panel');

        if (searchPanel) searchPanel.style.display = 'none';

        if (dock) {
            dock.classList.add('transforming', 'voice-mode');
            dock.classList.remove('search-mode');
        }

        if (voicePanel) {
            voicePanel.style.display = 'block';
            voicePanel.classList.remove('voice-finished');
        }

        window.aiVoiceAllowAutoClose = false;
        window.aiVoiceSearchInProgress = true;
        window.aiLastVoiceTranscript = '';

        resetVoiceUiForNewRecordingFinal();

        if (typeof ensureDefaultStartBeforeRoute === 'function') {
            if (!ensureDefaultStartBeforeRoute()) return;
        }

        if (typeof toggleVoiceCommand === 'function') {
            toggleVoiceCommand();
        } else {
            console.error('toggleVoiceCommand() not found.');
            alert('Voice command function not found.');
        }
    };

    restartInlineVoiceSearch = function() {
        if (typeof stopVoiceCommand === 'function' && typeof isVoiceListening !== 'undefined' && isVoiceListening) {
            stopVoiceCommand();
        }

        setTimeout(() => {
            openInlineVoiceSearch();
        }, 180);
    };

    startVoiceSearchFlow = function() {
        openInlineVoiceSearch();
    };

    stopInlineVoiceSearch = function() {
        window.aiVoiceSearchInProgress = false;

        if (typeof stopVoiceCommand === 'function') {
            stopVoiceCommand();
        }

        keepVoicePanelOpenFinal(window.aiLastVoiceTranscript || '');
    };

    /* Override text search so it remains open and shows Record button/result */
    if (!window.__recordButtonSearchWrapped) {
        window.__recordButtonSearchWrapped = true;
        const __recordButtonBaseSearchTextDestination = searchTextDestination;

        searchTextDestination = async function() {
            const input = document.getElementById('destination-search-input');
            window.aiLastTextSearchValue = String(input?.value || '').trim();

            await __recordButtonBaseSearchTextDestination();

            const routeText = document.getElementById('route-result-label')?.textContent || '';
            const message = routeText && routeText !== 'Ready' ?
                routeText :
                (window.aiLastTextSearchValue ? `Search completed for: ${window.aiLastTextSearchValue}` :
                    'Search completed.');

            window.aiLastTextSearchResult = message;
            keepTextPanelOpenFinal(message);
        };
    }

    /* Re-wrap speech recognition to keep result visible and allow Record Again */
    if (typeof initVoiceRecognition === 'function' && !window.__recordButtonVoiceInitWrapped) {
        window.__recordButtonVoiceInitWrapped = true;
        const __recordButtonBaseInitVoiceRecognition = initVoiceRecognition;

        initVoiceRecognition = function() {
            __recordButtonBaseInitVoiceRecognition();

            if (!speechRecognition) return;

            const previousResult = speechRecognition.onresult;
            const previousEnd = speechRecognition.onend;
            const previousError = speechRecognition.onerror;

            speechRecognition.onresult = function(event) {
                let transcript = '';

                try {
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        transcript += event.results[i][0].transcript;
                    }
                } catch (e) {}

                transcript = String(transcript || '').trim();

                if (transcript) {
                    window.aiLastVoiceTranscript = transcript;

                    const input = document.getElementById('destination-search-input');
                    if (input) input.value = transcript;
                }

                if (typeof previousResult === 'function') {
                    previousResult.call(this, event);
                }

                keepVoicePanelOpenFinal(window.aiLastVoiceTranscript || transcript);
            };

            speechRecognition.onend = function(event) {
                if (typeof previousEnd === 'function') {
                    previousEnd.call(this, event);
                }

                isVoiceListening = false;
                if (typeof updateVoiceButtonUi === 'function') updateVoiceButtonUi();

                if (!window.aiVoiceAllowAutoClose) {
                    keepVoicePanelOpenFinal(window.aiLastVoiceTranscript || '');
                }
            };

            speechRecognition.onerror = function(event) {
                if (typeof previousError === 'function') {
                    previousError.call(this, event);
                }

                isVoiceListening = false;
                if (typeof updateVoiceButtonUi === 'function') updateVoiceButtonUi();

                if (!window.aiVoiceAllowAutoClose) {
                    keepVoicePanelOpenFinal(window.aiLastVoiceTranscript || '');
                }
            };
        };

        if (speechRecognition) {
            initVoiceRecognition();
        }
    }

    window.openInlineTextSearch = openInlineTextSearch;
    window.openInlineVoiceSearch = openInlineVoiceSearch;
    window.restartInlineVoiceSearch = restartInlineVoiceSearch;
    window.startVoiceSearchFlow = startVoiceSearchFlow;
    window.stopInlineVoiceSearch = stopInlineVoiceSearch;
    window.closeAiTransformPanel = closeAiTransformPanel;
    window.searchTextDestination = searchTextDestination;
