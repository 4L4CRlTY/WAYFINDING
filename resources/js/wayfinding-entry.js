/*
 * Load the outdoor navigation core first. Account-only assistant and GPS
 * features are separate chunks and are fetched only after their first use.
 */
import 'virtual:wayfinding-core';

const guestMode = window.WAYFINDING_GUEST_MODE === true;
const featurePromises = new Map();

function setFeatureBusy(selector, busy, label) {
    document.querySelectorAll(selector).forEach(button => {
        button.disabled = busy;
        button.setAttribute('aria-busy', busy ? 'true' : 'false');
        if (busy && label) button.dataset.loadingLabel = label;
        if (!busy) delete button.dataset.loadingLabel;
    });
}

function loadFeature(name, importer, selector, label) {
    if (featurePromises.has(name)) return featurePromises.get(name);

    setFeatureBusy(selector, true, label);
    const promise = importer()
        .catch(error => {
            featurePromises.delete(name);
            window.showWayfindingToast?.(
                `${label} could not load. Check your connection and try again.`,
                { kind: 'error' }
            );
            throw error;
        })
        .finally(() => setFeatureBusy(selector, false));

    featurePromises.set(name, promise);
    return promise;
}

async function loadAssistantFeature() {
    return loadFeature(
        'assistant',
        () => import('./wayfinding-assistant-entry.js'),
        '#text-search-command-btn, #voice-command-btn, #ai-text-record-btn, #ai-voice-record-again-btn',
        'Search tools'
    );
}

async function loadGpsFeature() {
    await loadFeature(
        'gps',
        () => import('./wayfinding-gps-entry.js'),
        '.floating-mode-btn.gps',
        'Live GPS'
    );

    if (window.WAYFINDING_GPS_DIAGNOSTICS_ENABLED === true) {
        await loadFeature(
            'gps-diagnostics',
            () => import('./wayfinding-gps-diagnostics-entry.js'),
            '#gps-diagnostics-toggle',
            'GPS diagnostics'
        );
    }
}

if (!guestMode) {
    const baseGpsMode = window.selectGpsMode;
    const lazyGpsMode = async function (...args) {
        await loadGpsFeature();
        const upgradedGpsMode = window.selectGpsMode;
        return upgradedGpsMode && upgradedGpsMode !== lazyGpsMode
            ? upgradedGpsMode.apply(this, args)
            : baseGpsMode?.apply(this, args);
    };
    window.selectGpsMode = lazyGpsMode;

    /*
     * Only the two entry-point buttons need proxies before the Assistant
     * chunk exists. Do not proxy searchTextDestination or the voice internals:
     * the Assistant decorates those existing core functions during import,
     * and proxying them first creates a recursive wrapper chain.
     */
    ['openInlineTextSearch', 'openInlineVoiceSearch'].forEach(functionName => {
        const lazyFunction = async function (...args) {
            await loadAssistantFeature();
            // Start fetching/normalizing the keyword index only after the
            // user opens a search tool. This gives the fetch time to overlap
            // with typing or voice capture without competing with initial map
            // rendering and touch gestures on mobile.
            window.preloadWayfindingSearchIndex?.();
            const loadedFunction = window[functionName];
            return loadedFunction && loadedFunction !== lazyFunction
                ? loadedFunction.apply(this, args)
                : undefined;
        };
        window[functionName] = lazyFunction;
    });

    window.openGpsDiagnosticsLazy = async function () {
        await loadGpsFeature();
        window.WayfindingGpsDiagnostics?.open();
    };

    const idlePreloadSearch = () => {
        loadAssistantFeature().catch(() => {});
        window.preloadWayfindingSearchIndex?.();
    };

    window.addEventListener('load', () => {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        const isMobileViewport = window.matchMedia('(max-width: 768px)').matches;
        const constrainedConnection = connection?.saveData === true
            || /(^|-)2g$|^3g$/.test(String(connection?.effectiveType || ''));

        // Save-data and slower connections use the small server response on
        // demand. On normal phones, wait until the map has fully settled and
        // then warm the assistant/index during browser idle time. This avoids
        // both startup contention and the first-search pause.
        if (constrainedConnection) return;

        if (isMobileViewport) {
            window.setTimeout(() => {
                if (typeof window.requestIdleCallback === 'function') {
                    window.requestIdleCallback(idlePreloadSearch, { timeout: 6000 });
                } else {
                    idlePreloadSearch();
                }
            }, 3000);
            return;
        }

        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(idlePreloadSearch, { timeout: 2500 });
        } else {
            window.setTimeout(idlePreloadSearch, 1200);
        }
    }, { once: true });
}
