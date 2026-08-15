/*
 * Lightweight search and voice-search presentation controller.
 * Routing and destination matching remain owned by 06-search-voice.js.
 * Keep this file intentionally small: it is lazy-loaded on first assistant use.
 */

let assistantSearchRunning = false;
let assistantVoiceStarting = false;
let assistantConfirmationTimer = null;

function getAssistantElement(id) {
    return document.getElementById(id);
}

function setAssistantDockMode(mode = '') {
    const dock = getAssistantElement('floating-route-ui');
    if (!dock) return;

    dock.classList.toggle('transforming', Boolean(mode));
    dock.classList.toggle('search-mode', mode === 'search');
    dock.classList.toggle('voice-mode', mode === 'voice');
}

function resetAssistantPanels() {
    const searchPanel = getAssistantElement('ai-search-panel');
    const voicePanel = getAssistantElement('ai-voice-panel');
    const searchProgress = getAssistantElement('ai-search-progress');

    if (searchPanel) {
        searchPanel.style.display = 'none';
        searchPanel.classList.remove('is-searching');
    }
    if (voicePanel) {
        voicePanel.style.display = 'none';
        voicePanel.classList.remove('is-listening');
    }
    if (searchProgress) {
        searchProgress.hidden = true;
        searchProgress.textContent = '';
    }

    setAssistantDockMode('');
}

function showRouteReadyConfirmation(message = '') {
    const confirmation = getAssistantElement('ai-route-confirmation');
    const text = getAssistantElement('ai-route-confirmation-text');
    if (!confirmation || !text) return;

    const cleanMessage = String(message || '')
        .replace(/^Matched\s+"[^"]*"\s*[→-]\s*/i, '')
        .trim();

    text.textContent = cleanMessage || 'Destination selected';
    confirmation.hidden = false;
    confirmation.classList.add('is-visible');

    window.clearTimeout(assistantConfirmationTimer);
    assistantConfirmationTimer = window.setTimeout(() => {
        confirmation.classList.remove('is-visible');
        confirmation.hidden = true;
    }, 3200);
}

function closeAiTransformPanel() {
    assistantVoiceStarting = false;

    if (typeof stopVoiceCommand === 'function' && isVoiceListening) {
        stopVoiceCommand();
    }

    resetAssistantPanels();
}

function openInlineTextSearch() {
    closeFloatingActionCard();
    resetAssistantPanels();

    const panel = getAssistantElement('ai-search-panel');
    if (panel) panel.style.display = 'block';
    setAssistantDockMode('search');

    // Start downloading/normalizing while the user is typing.
    window.preloadWayfindingSearchIndex?.();

    window.requestAnimationFrame(() => {
        getAssistantElement('destination-search-input')?.focus({ preventScroll: true });
    });
}

function openInlineVoiceSearch() {
    if (assistantVoiceStarting || isVoiceListening) return;

    closeFloatingActionCard();
    resetAssistantPanels();

    const panel = getAssistantElement('ai-voice-panel');
    const heard = getAssistantElement('voice-heard-text');
    if (panel) {
        panel.style.display = 'block';
        panel.classList.add('is-listening');
    }
    if (heard) heard.style.display = 'none';
    setAssistantDockMode('voice');

    if (!ensureDefaultStartBeforeRoute()) {
        resetAssistantPanels();
        return;
    }

    // Voice capture gives the static index time to load before recognition ends.
    window.preloadWayfindingSearchIndex?.();
    assistantVoiceStarting = true;
    try {
        toggleVoiceCommand();
    } finally {
        assistantVoiceStarting = false;
    }
}

function restartInlineVoiceSearch() {
    if (isVoiceListening) stopVoiceCommand();

    // Web Speech needs a short release interval before start() can be called again.
    window.setTimeout(openInlineVoiceSearch, 120);
}

function stopInlineVoiceSearch() {
    if (isVoiceListening) stopVoiceCommand();
    setVoiceStatus('Processing your destination...');
}

const assistantBaseSearchTextDestination = searchTextDestination;
searchTextDestination = async function lightweightSearchTextDestination() {
    if (assistantSearchRunning) return;

    const panel = getAssistantElement('ai-search-panel');
    const submit = panel?.querySelector('.ai-search-submit');
    const progress = getAssistantElement('ai-search-progress');

    assistantSearchRunning = true;
    panel?.classList.add('is-searching');
    if (submit) {
        submit.disabled = true;
        submit.setAttribute('aria-busy', 'true');
    }
    if (progress) {
        progress.hidden = false;
        progress.textContent = 'Finding the best route…';
    }

    try {
        await assistantBaseSearchTextDestination();

        const routeMessage = String(getAssistantElement('route-result-label')?.textContent || '').trim();
        if (/^Matched\b/i.test(routeMessage)) {
            resetAssistantPanels();
            showRouteReadyConfirmation(routeMessage);
        }
    } finally {
        assistantSearchRunning = false;
        panel?.classList.remove('is-searching');
        if (submit) {
            submit.disabled = false;
            submit.removeAttribute('aria-busy');
        }
        if (progress) progress.hidden = true;
    }
};

// Redirect the older modal entry points to the same lightweight panels.
openTextSearchModal = openInlineTextSearch;
closeTextSearchModal = function closeInlineTextSearch() {
    resetAssistantPanels();
};
startVoiceSearchFlow = openInlineVoiceSearch;

const assistantBaseCloseBrowseOptionsModal = closeBrowseOptionsModal;
closeBrowseOptionsModal = function closeBrowseAndAssistantPanels() {
    assistantBaseCloseBrowseOptionsModal();
    resetAssistantPanels();
};

// Initialize Web Speech exactly once. The core onresult handler performs the
// same text search as a typed query; no nested recognition wrappers are added.
initVoiceRecognition();
updateVoiceButtonUi();
setVoiceStatus(voiceSupported ? 'Ready to listen' : 'Not supported in this browser');
setHeardText('');

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeAiTransformPanel();
});

document.addEventListener('pointerdown', event => {
    const dock = getAssistantElement('floating-route-ui');
    const browseModal = getAssistantElement('browseOptionsModal');
    if (dock?.contains(event.target) || browseModal?.contains(event.target)) return;

    const searchOpen = getAssistantElement('ai-search-panel')?.style.display === 'block';
    const voiceOpen = getAssistantElement('ai-voice-panel')?.style.display === 'block';
    if (searchOpen || voiceOpen) closeAiTransformPanel();
}, { passive: true });

window.selectLanduseDestination = function selectLanduseDestination(landuseId) {
    const landuse = (landuseRecords || []).find(item => Number(item.id) === Number(landuseId));

    if (landuse && isDesignLanduse(landuse)) {
        selectedDestinationLanduseId = null;
        updateRouteLabels();
        setRouteResultLabel('Design landuse is display-only and not available for routing.');
        return;
    }

    destinationTypeSelect.value = 'landuse';
    updateDestinationUi();
    if (destinationLanduseSelect) destinationLanduseSelect.value = String(landuseId);

    selectedDestinationLanduseId = Number(landuseId);
    selectedDestinationBuildingId = null;
    selectedIndoorRoomFeature = null;
    selectedBuildingEntranceId = null;
    updateRouteLabels();
    setRouteResultLabel('Landuse selected. Click Find Route.');
};

window.openInlineTextSearch = openInlineTextSearch;
window.openInlineVoiceSearch = openInlineVoiceSearch;
window.restartInlineVoiceSearch = restartInlineVoiceSearch;
window.startVoiceSearchFlow = startVoiceSearchFlow;
window.stopInlineVoiceSearch = stopInlineVoiceSearch;
window.closeAiTransformPanel = closeAiTransformPanel;
window.openTextSearchModal = openTextSearchModal;
window.closeTextSearchModal = closeTextSearchModal;
window.closeBrowseOptionsModal = closeBrowseOptionsModal;
window.searchTextDestination = searchTextDestination;
