(function () {
    'use strict';

    const statusRoot = document.getElementById('pwa-profile-status');
    const statusLabel = document.getElementById('pwa-status-label');
    const statusDetail = document.getElementById('pwa-status-detail');
    const installButton = document.getElementById('pwa-install-button');
    const updateBanner = document.getElementById('pwa-update-banner');
    const updateButton = document.getElementById('pwa-update-button');
    const updateDismiss = document.getElementById('pwa-update-dismiss');
    const swMeta = document.querySelector('meta[name="wayfinding-service-worker"]');

    let deferredInstallPrompt = null;
    let registration = null;
    let reloadRequested = false;

    const state = {
        supported: 'serviceWorker' in navigator,
        secure: window.isSecureContext,
        online: navigator.onLine,
        installed: window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true,
        ready: false,
        updateAvailable: false,
    };

    function renderStatus() {
        state.online = navigator.onLine;
        if (!statusRoot || !statusLabel || !statusDetail) return;

        let tone = 'preparing';
        let label = 'Preparing offline access';
        let detail = 'Campus data is saved securely as it loads.';

        if (!state.online) {
            tone = 'offline';
            label = 'Offline mode active';
            detail = 'Saved campus data and GPS remain available in this session.';
        } else if (!state.secure) {
            tone = 'secure';
            label = 'HTTPS required';
            detail = 'Open the deployed system through HTTPS to enable installation.';
        } else if (!state.supported) {
            tone = 'unsupported';
            label = 'Offline mode unavailable';
            detail = 'This browser does not support installable offline apps.';
        } else if (state.installed) {
            tone = 'installed';
            label = 'Campus app installed';
            detail = 'Ready for quick launch and connection interruptions.';
        } else if (state.ready) {
            tone = 'ready';
            label = 'Offline-ready';
            detail = 'Public map data and app assets are saved as you browse.';
        }

        statusRoot.dataset.state = tone;
        statusLabel.textContent = label;
        statusDetail.textContent = detail;
        installButton.hidden = !deferredInstallPrompt || state.installed;
    }

    function showUpdateAvailable(worker) {
        state.updateAvailable = true;
        if (updateBanner) updateBanner.hidden = false;
        if (updateButton) {
            updateButton.onclick = () => {
                reloadRequested = true;
                if (worker) {
                    worker.postMessage({ type: 'SKIP_WAITING' });
                } else {
                    window.location.reload();
                }
            };
        }
    }

    async function requestInstall() {
        if (!deferredInstallPrompt) return false;

        deferredInstallPrompt.prompt();
        const choice = await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;

        if (choice.outcome === 'accepted') {
            state.installed = true;
        }

        renderStatus();
        return choice.outcome === 'accepted';
    }

    async function registerOfflineSupport() {
        if (!state.supported || !state.secure || !swMeta?.content) {
            renderStatus();
            return null;
        }

        try {
            registration = await navigator.serviceWorker.register(swMeta.content, {
                scope: '/',
                updateViaCache: 'none',
            });
            await navigator.serviceWorker.ready;
            state.ready = true;

            if (registration.waiting && navigator.serviceWorker.controller) {
                showUpdateAvailable(registration.waiting);
            }

            registration.addEventListener('updatefound', () => {
                const installingWorker = registration.installing;
                if (!installingWorker) return;

                installingWorker.addEventListener('statechange', () => {
                    if (
                        installingWorker.state === 'installed'
                        && navigator.serviceWorker.controller
                    ) {
                        showUpdateAvailable(installingWorker);
                    }
                });
            });

            renderStatus();
            return registration;
        } catch (error) {
            statusRoot?.setAttribute('data-state', 'unsupported');
            if (statusLabel) statusLabel.textContent = 'Offline setup paused';
            if (statusDetail) {
                statusDetail.textContent = 'Reload on a secure connection to try again.';
            }
            return null;
        }
    }

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        deferredInstallPrompt = event;
        renderStatus();
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        state.installed = true;
        renderStatus();
    });

    window.addEventListener('offline', renderStatus);
    window.addEventListener('online', () => {
        renderStatus();
        registration?.update();
    });

    navigator.serviceWorker?.addEventListener('controllerchange', () => {
        state.ready = true;
        if (reloadRequested) {
            window.location.reload();
            return;
        }
        renderStatus();
    });

    installButton?.addEventListener('click', requestInstall);
    updateDismiss?.addEventListener('click', () => {
        if (updateBanner) updateBanner.hidden = true;
    });

    window.addEventListener('load', registerOfflineSupport, { once: true });
    renderStatus();

    window.WayfindingPwa = {
        requestInstall,
        checkForUpdate() {
            return registration?.update() || Promise.resolve();
        },
        getState() {
            return { ...state };
        },
    };
})();
