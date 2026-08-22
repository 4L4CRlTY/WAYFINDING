import { expect, test } from 'playwright/test';
import {
    createFirstBuildingRoute,
    loginAsUser,
    monitorRuntimeErrors,
    openDestinationBrowser,
} from './helpers/wayfinding.mjs';

test('starts and replaces routes automatically, including nearest-CR navigation', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await loginAsUser(page);
    await createFirstBuildingRoute(page);

    await expect(page.locator('#route-result-label')).toContainText(/route|path|entrance/i);
    await expect(page.locator('#navigation-start-btn')).toHaveCount(0);
    const firstDestination = await page.locator('#navigation-destination').textContent();

    await openDestinationBrowser(page);
    await page.locator('#destination-building-select').selectOption({ index: 2 });
    await page.getByRole('button', { name: /Find Route/i }).click();

    await expect(page.locator('#navigation-details-toggle')).toBeVisible();
    await expect
        .poll(() => page.locator('#navigation-destination').textContent())
        .not.toBe(firstDestination);
    await page.locator('#navigation-details-toggle').click();
    await expect(page.locator('#navigation-sheet')).toBeVisible();
    await expect(page.locator('#navigation-kicker')).toHaveText('Live Navigation');
    await expect(page.locator('#navigation-pause-btn')).toBeVisible();

    await page.locator('#navigation-pause-btn').click();
    await expect(page.locator('#navigation-pause-btn')).toHaveText('Resume');
    await expect(page.locator('#navigation-guidance-title')).toHaveText('Guidance paused');

    await page.locator('#navigation-pause-btn').click();
    await expect(page.locator('#navigation-pause-btn')).toHaveText('Pause');

    await page.locator('#navigation-end-btn').click();
    await expect(page.locator('#navigation-sheet')).toBeHidden();

    await page.locator('#cr-navigation-toggle').click();
    await expect(page.locator('#cr-navigation-modal')).toBeVisible();
    await page.locator('#cr-navigation-modal [data-cr-mode="default"]').click();
    await expect(page.locator('#cr-navigation-results')).toBeVisible({
        timeout: 15_000,
    });
    await expect(page.locator('.cr-navigation-item.is-nearest')).toContainText('Nearest CR');
    await expect
        .poll(() => page.locator('.cr-navigation-item.is-close-range').count())
        .toBeGreaterThan(0);
    await expect
        .poll(() => page.locator('.cr-navigation-item.is-nearby-range').count())
        .toBeGreaterThan(0);
    await expect
        .poll(() => page.locator('.cr-navigation-suggestion').count())
        .toBeGreaterThan(6);
    await expect(page.locator('.cr-navigation-suggestion').first()).toHaveAttribute(
        'data-cr-building',
        'Admin Building',
    );
    await page.locator('.cr-navigation-suggestion').first().click();

    await expect(page.locator('#navigation-details-toggle')).toBeVisible();
    await expect(page.locator('#navigation-destination')).toContainText(/Comfort Room|CR/i);
    runtime.expectNone();
});

test('keeps destination dialog open and gives non-blocking validation feedback', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await loginAsUser(page);
    await openDestinationBrowser(page);

    await page.getByRole('button', { name: /Find Route/i }).click();

    await expect(page.locator('#browseOptionsModal')).toBeVisible();
    await expect(page.locator('#wayfinding-toast-region .wayfinding-toast')).toContainText(
        'Please choose a destination building.',
    );
    runtime.expectNone();
});

test('lazy text search stays responsive and creates a route on first use', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await loginAsUser(page);

    await page.locator('#destination-menu-toggle').click();
    await page.locator('#text-search-command-btn').click();
    await expect(page.locator('#ai-search-panel')).toBeVisible();

    await page.locator('#destination-search-input').fill('Information Technology');
    await page.locator('#ai-search-panel .ai-search-submit').click();

    await expect(page.locator('#navigation-details-toggle')).toBeVisible({
        timeout: 15_000,
    });
    await expect(page.locator('#navigation-destination')).toContainText(
        /Information Technology/i,
    );
    await expect(page.locator('#ai-search-panel')).toBeHidden();
    runtime.expectNone();
});

test('lazy voice search opens once and leaves the dashboard responsive', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await loginAsUser(page);

    await page.locator('#destination-menu-toggle').click();
    await page.locator('#voice-command-btn').click();

    await expect(page.locator('#ai-voice-panel')).toBeVisible();
    await expect
        .poll(() => page.evaluate(() => document.querySelector('#map') !== null))
        .toBe(true);
    await page.getByRole('button', { name: 'Close voice search' }).click();
    await expect(page.locator('#ai-voice-panel')).toBeHidden();
    runtime.expectNone({
        ignore: [/not-allowed|permission|audio-capture|Speech recognition/i],
    });
});

test('keeps the outdoor map usable when optional campus-event data fails', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await page.route('**/data/campus-snapshot.json*', route => route.abort('failed'));
    await page.route('**/api/campus-events', route => route.abort('failed'));
    await loginAsUser(page);

    await expect(page.locator('#wayfinding-connection-banner')).toBeVisible();
    await expect(page.locator('#wayfinding-connection-message')).toContainText(
        /Campus events|limited/i,
    );
    await expect
        .poll(() => page.locator('#destination-building-select option').count())
        .toBeGreaterThan(1);
    await expect(page.locator('#map')).toBeVisible();
    runtime.expectNone({ ignore: [/campus-events|campus-snapshot/] });
});

test('keeps loaded campus data usable when the connection drops', async ({ page, context }) => {
    const runtime = monitorRuntimeErrors(page);
    await loginAsUser(page);

    const savedDatasetCount = await page.evaluate(() => (
        Object.keys(window.localStorage)
            .filter(key => key.startsWith('wayfinding:last-known:v1:/api/'))
            .length
    ));
    expect(savedDatasetCount).toBeGreaterThan(0);

    await context.setOffline(true);
    try {
        await expect(page.locator('#pwa-profile-status')).toHaveAttribute(
            'data-state',
            'offline',
        );
        await page.evaluate(() => window.retryWayfindingData());
        await expect
            .poll(() => page.locator('#destination-building-select option').count())
            .toBeGreaterThan(1);
        await expect(page.locator('#map')).toBeVisible();
    } finally {
        await context.setOffline(false);
    }

    await expect(page.locator('#pwa-profile-status')).not.toHaveAttribute(
        'data-state',
        'offline',
    );
    runtime.expectNone({ ignore: [/ERR_INTERNET_DISCONNECTED|Failed to fetch/] });
});

test('serves a branded privacy-safe offline fallback screen', async ({ page }) => {
    await page.goto('/offline.html');

    await expect(page.getByRole('heading', { name: 'You are offline.' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Try Again' })).toBeVisible();
    await expect(page.locator('main')).toContainText(
        'stores no account or location information',
    );
});

test('GPS simulator reaches the quality lock used by the real dashboard', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await loginAsUser(page, '/user/dashboard?gps_simulator=1');

    await expect(page.locator('#gps-simulator-panel')).toBeVisible();
    await page.locator('.floating-mode-btn.gps').click();
    await page.locator('[data-gps-sim-speed]').selectOption('4');
    await page.locator('[data-gps-sim-start]').click();

    await expect(page.locator('body')).toHaveAttribute('data-gps-quality-lock', 'locked', {
        timeout: 15_000,
    });
    await expect(page.locator('#navigation-gps-quality')).not.toHaveText('Not active');

    await page.locator('#cr-navigation-toggle').click();
    await page.locator('#cr-navigation-modal [data-cr-mode="gps"]').click();
    await expect(page.locator('#cr-navigation-results')).toBeVisible({
        timeout: 15_000,
    });
    await expect(page.locator('.cr-navigation-item.is-nearest')).toContainText('Nearest CR');
    await page.locator('#cr-navigation-close').click();
    runtime.expectNone();
});

test('Mobile Low keeps GPS routing active after acquiring a trusted fix', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await loginAsUser(
        page,
        '/user/dashboard?gps_simulator=1&mobile_emulator=low',
    );

    await expect(page.locator('body')).toHaveAttribute('data-render-quality', 'low');
    await page.locator('.floating-mode-btn.gps').click();
    await page.locator('[data-gps-sim-speed]').selectOption('4');
    await page.locator('[data-gps-sim-start]').click();
    await expect(page.locator('body')).toHaveAttribute('data-gps-quality-lock', 'locked', {
        timeout: 15_000,
    });

    await page.locator('[data-gps-sim-collapse]').click();
    await openDestinationBrowser(page);
    await page.locator('#destination-building-select').selectOption({ index: 1 });
    await page.getByRole('button', { name: /Find Route/i }).click();
    await expect(page.locator('#navigation-details-toggle')).toBeVisible({
        timeout: 15_000,
    });
    const state = await page.evaluate(() => window.WayfindingGpsCalibration.getState());
    expect(state.acceptedFix).toBe(true);
    expect(state.routeActive).toBe(true);
    runtime.expectNone();
});

test('Mobile Low reveals labels in one zoom step and opens the event chip below its label', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.route('**/data/campus-snapshot.json*', async route => {
        const response = await route.fetch();
        const snapshot = await response.json();
        const buildings = snapshot.datasets?.['/api/buildings'] || [];
        const coveredCourt = buildings.find(building => building.name === 'Covered Court');

        snapshot.datasets['/api/campus-events'] = coveredCourt ? [{
            id: 999999,
            title: 'Campus Seminar',
            event_target_type: 'building',
            display_type: 'building',
            display_id: coveredCourt.id,
            route_type: 'building',
            route_id: coveredCourt.id,
            building_id: coveredCourt.id,
            building_name: coveredCourt.name,
            starts_at_display: 'Aug 22, 2026 09:00 AM',
            status: 'happening_now',
            priority: 1,
        }] : [];

        await route.fulfill({ response, json: snapshot });
    });

    await loginAsUser(page, '/user/dashboard?mobile_emulator=low');
    await expect(page.locator('.building-permanent-label')).not.toHaveCount(0);

    await page.evaluate(() => {
        window.map.setZoom(17, { animate: false });
    });
    await expect(page.locator('.building-permanent-label.label-z18').first()).toBeHidden();
    await page.locator('.leaflet-control-zoom-in').click();
    await expect(page.locator('.building-permanent-label.label-z18').first()).toBeVisible();

    const eventBadge = page.locator('.bldg-event-badge').first();
    await expect(eventBadge).toBeVisible();
    const placement = await eventBadge.evaluate(button => {
        const badge = button.getBoundingClientRect();
        const label = button.closest('.building-permanent-label-inner').getBoundingClientRect();
        return {
            badgeTop: badge.top,
            labelBottom: label.bottom,
            centerDelta: Math.abs(
                (badge.left + badge.width / 2) - (label.left + label.width / 2)
            ),
        };
    });
    expect(placement.badgeTop).toBeGreaterThanOrEqual(placement.labelBottom);
    expect(placement.centerDelta).toBeLessThan(2);

    await eventBadge.click();
    await expect(page.locator('#campus-event-panel')).toHaveClass(/open/);
    await expect(page.locator('#campus-event-list .campus-event-card')).toHaveCount(1);
    await expect(page.locator('#campus-event-list')).toContainText('Campus Seminar');
    runtime.expectNone();
});

test('GPS tracking revalidates degraded signal and rejects a false strong jump', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await loginAsUser(page, '/user/dashboard?gps_simulator=1');

    await page.evaluate(() => {
        window.__gpsRegressionStatuses = [];
        window.addEventListener('wayfinding:gps-diagnostic', event => {
            const status = event.detail?.status;
            if (status) window.__gpsRegressionStatuses.push(status);
        });
    });

    await page.locator('.floating-mode-btn.gps').click();
    await page.locator('[data-gps-sim-speed]').selectOption('4');
    await page.locator('[data-gps-sim-start]').click();
    await expect(page.locator('body')).toHaveAttribute('data-gps-quality-lock', 'locked', {
        timeout: 15_000,
    });

    await page.locator('[data-gps-sim-signal]').selectOption('weak');
    await expect(page.locator('body')).toHaveAttribute('data-gps-quality-lock', 'waiting', {
        timeout: 8_000,
    });

    await page.locator('[data-gps-sim-signal]').selectOption('false_jump');
    await expect(page.locator('body')).toHaveAttribute('data-gps-quality-lock', 'locked', {
        timeout: 8_000,
    });
    await expect.poll(() => page.evaluate(() => (
        window.__gpsRegressionStatuses.includes('jump_rejected')
    )), { timeout: 8_000 }).toBe(true);

    runtime.expectNone();
});

test('GPS ignores invalid device coordinates without breaking the dashboard', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await loginAsUser(page, '/user/dashboard?gps_simulator=1');

    await page.evaluate(() => {
        window.__gpsInvalidReadingSeen = false;
        window.addEventListener('wayfinding:gps-diagnostic', event => {
            if (event.detail?.status === 'invalid_coordinates') {
                window.__gpsInvalidReadingSeen = true;
            }
        });
        window.WayfindingGpsSimulator.geolocation.watchPosition = success => {
            window.setTimeout(() => success({
                coords: {
                    latitude: Number.NaN,
                    longitude: Number.NaN,
                    accuracy: 5,
                    heading: null,
                    speed: null,
                    altitude: null,
                },
                timestamp: Date.now(),
            }), 0);
            return 991;
        };
        window.WayfindingGpsSimulator.geolocation.clearWatch = () => {};
    });

    await page.locator('.floating-mode-btn.gps').click();
    await expect.poll(() => page.evaluate(() => window.__gpsInvalidReadingSeen))
        .toBe(true);
    const state = await page.evaluate(() => window.WayfindingGpsCalibration.getState());
    expect(state.tracking).toBe(true);
    expect(state.acceptedFix).toBe(false);
    runtime.expectNone();
});

test('GPS startup failure immediately switches to safe map placement', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await loginAsUser(page, '/user/dashboard?gps_simulator=1');

    await page.evaluate(() => {
        window.WayfindingGpsSimulator.geolocation.watchPosition = () => {
            throw new Error('Simulated GPS startup failure');
        };
    });

    await page.locator('.floating-mode-btn.gps').click();
    await expect(page.locator('.floating-mode-btn.pick')).toHaveClass(/active/);
    await expect(page.locator('#route-result-label')).toContainText(
        'browser could not start location tracking',
    );
    const state = await page.evaluate(() => window.WayfindingGpsCalibration.getState());
    expect(state.tracking).toBe(false);
    expect(state.acceptedFix).toBe(false);
    runtime.expectNone();
});

test('GPS outside campus starts safely at the nearest gateway', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await loginAsUser(page, '/user/dashboard?gps_simulator=1');

    await page.evaluate(() => {
        window.__gpsOutsideCampusAccepted = false;
        window.__gpsOutsideTimers = new Map();
        window.addEventListener('wayfinding:gps-diagnostic', event => {
            if (event.detail?.status === 'accepted_outside_campus') {
                window.__gpsOutsideCampusAccepted = true;
            }
        });
        window.WayfindingGpsSimulator.geolocation.watchPosition = success => {
            let emitted = 0;
            const watchId = 992;
            const timer = window.setInterval(() => {
                emitted += 1;
                success({
                    coords: {
                        latitude: 10.27,
                        longitude: 124.98,
                        accuracy: 5,
                        heading: null,
                        speed: 0,
                        altitude: null,
                    },
                    timestamp: Date.now(),
                });
                if (emitted >= 5) window.clearInterval(timer);
            }, 80);
            window.__gpsOutsideTimers.set(watchId, timer);
            return watchId;
        };
        window.WayfindingGpsSimulator.geolocation.clearWatch = watchId => {
            window.clearInterval(window.__gpsOutsideTimers.get(watchId));
            window.__gpsOutsideTimers.delete(watchId);
        };
    });

    await page.locator('.floating-mode-btn.gps').click();
    await expect.poll(() => page.evaluate(() => window.__gpsOutsideCampusAccepted))
        .toBe(true);
    await expect(page.locator('#map .gps-route-start-marker')).toHaveCount(1);
    const state = await page.evaluate(() => window.WayfindingGpsCalibration.getState());
    expect(state.acceptedFix).toBe(true);
    expect(state.lastSnappedPosition).not.toEqual(state.lastRawPosition);
    runtime.expectNone();
});

test('GPS field test records diagnostics and exports a local CSV', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
    await loginAsUser(page, '/user/dashboard?gps_simulator=1');

    await expect(page.locator('#gps-simulator-panel')).toBeVisible();
    await page.locator('.floating-mode-btn.gps').click();
    await page.locator('#navigation-details-toggle').click();
    await page.locator('#gps-diagnostics-toggle').click();
    await expect(page.locator('#gps-diagnostics-panel')).toBeVisible();

    await page.locator('#gps-session-start').click();
    await expect(page.locator('#gps-recording-badge')).toHaveText('Recording');
    await page.locator('[data-gps-sim-speed]').selectOption('4');
    await page.locator('[data-gps-sim-start]').click();

    await expect(page.locator('body')).toHaveAttribute('data-gps-quality-lock', 'locked', {
        timeout: 15_000,
    });
    await expect
        .poll(async () => Number(await page.locator('#gps-session-samples').textContent()))
        .toBeGreaterThanOrEqual(5);
    await expect(page.locator('#gps-diagnostics-accuracy')).toHaveText('5m');
    await expect(page.locator('#gps-session-p95')).toHaveText('5m');
    await expect(page.locator('#gps-session-grade')).not.toHaveText('Not ready');

    await page.locator('#gps-session-stop').click();
    await expect(page.locator('#gps-recording-badge')).toHaveText('Stopped');

    const downloadPromise = page.waitForEvent('download');
    await page.locator('#gps-session-export').click();
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toMatch(/^gps-field-test-.+\.csv$/);

    const state = await page.evaluate(() => window.WayfindingGpsDiagnostics.getState());
    expect(state.recording).toBe(false);
    expect(state.samples.length).toBeGreaterThanOrEqual(5);
    expect(state.summary.p95Accuracy).toBe(5);
    runtime.expectNone();
});

test.describe('mobile layout', () => {
    test.use({ viewport: { width: 390, height: 844 } });

    test('keeps mobile cards readable, touch-friendly, and fully inside the screen', async ({ page }) => {
        const runtime = monitorRuntimeErrors(page);
        const accessibilityWarnings = [];
        page.on('console', message => {
            if (
                message.type() === 'warning'
                && message.text().includes('Blocked aria-hidden')
            ) {
                accessibilityWarnings.push(message.text());
            }
        });
        await loginAsUser(page);
        await openDestinationBrowser(page);
        await page.locator('#destination-building-select').selectOption({
            label: 'Information Technology',
        });
        await page.getByRole('button', { name: /Find Route/i }).click();

        const popup = page.locator('.route-building-map-popup');
        await expect(popup).toBeVisible();
        await page.waitForTimeout(500);

        const closedBrowseState = await page.locator('#browseOptionsModal').evaluate(element => ({
            ariaHidden: element.getAttribute('aria-hidden'),
            inert: element.inert,
            containsFocus: element.contains(document.activeElement),
        }));
        expect(closedBrowseState.ariaHidden).toBeNull();
        expect(closedBrowseState.inert).toBe(true);
        expect(closedBrowseState.containsFocus).toBe(false);

        const layout = await page.evaluate(() => {
            const popupElement = document.querySelector('.route-building-map-popup');
            const close = document.querySelector('.route-building-map-popup-custom-close');
            const title = document.querySelector('.route-building-map-popup-title');
            const action = document.querySelector('.route-building-map-popup-btn');
            const popupRect = popupElement?.getBoundingClientRect();
            const closeRect = close?.getBoundingClientRect();
            const actionRect = action?.getBoundingClientRect();

            return {
                popupLeft: popupRect?.left ?? -1,
                popupRight: popupRect?.right ?? window.innerWidth + 1,
                popupTop: popupRect?.top ?? -1,
                popupBottom: popupRect?.bottom ?? window.innerHeight + 1,
                closeWidth: closeRect?.width ?? 0,
                closeHeight: closeRect?.height ?? 0,
                titleFont: Number.parseFloat(window.getComputedStyle(title).fontSize),
                actionHeight: actionRect?.height ?? 0,
                diagnosticsPresent: Boolean(document.querySelector('#gps-diagnostics-panel')),
            };
        });

        expect(layout.popupLeft).toBeGreaterThanOrEqual(0);
        expect(layout.popupRight).toBeLessThanOrEqual(390);
        expect(layout.popupTop).toBeGreaterThanOrEqual(0);
        expect(layout.popupBottom).toBeLessThanOrEqual(844);
        expect(layout.closeWidth).toBeGreaterThanOrEqual(36);
        expect(layout.closeHeight).toBeGreaterThanOrEqual(36);
        expect(layout.titleFont).toBeGreaterThanOrEqual(14);
        expect(layout.actionHeight).toBeGreaterThanOrEqual(44);
        expect(layout.diagnosticsPresent).toBe(false);

        const readPopupAnchor = () => page.evaluate(() => {
            const building = document.querySelector('.fake-3d-building.building-selected');
            const tip = document.querySelector('.route-building-map-popup .leaflet-popup-tip');
            const buildingRect = building?.getBoundingClientRect();
            const tipRect = tip?.getBoundingClientRect();

            return {
                buildingX: buildingRect ? buildingRect.left + buildingRect.width / 2 : null,
                buildingY: buildingRect ? buildingRect.top + buildingRect.height / 2 : null,
                tipX: tipRect ? tipRect.left + tipRect.width / 2 : null,
                tipY: tipRect ? tipRect.top + tipRect.height / 2 : null,
            };
        });
        const anchorBeforeDrag = await readPopupAnchor();
        const mapBox = await page.locator('#map').boundingBox();
        expect(mapBox).not.toBeNull();
        await page.mouse.move(mapBox.x + mapBox.width * 0.65, mapBox.y + mapBox.height * 0.48);
        await page.mouse.down();
        await page.mouse.move(mapBox.x + mapBox.width * 0.48, mapBox.y + mapBox.height * 0.58, {
            steps: 5,
        });
        await page.mouse.up();
        await page.waitForTimeout(150);
        const anchorAfterDrag = await readPopupAnchor();

        const buildingDeltaX = anchorAfterDrag.buildingX - anchorBeforeDrag.buildingX;
        const buildingDeltaY = anchorAfterDrag.buildingY - anchorBeforeDrag.buildingY;
        const popupDeltaX = anchorAfterDrag.tipX - anchorBeforeDrag.tipX;
        const popupDeltaY = anchorAfterDrag.tipY - anchorBeforeDrag.tipY;
        expect(Math.abs(buildingDeltaX - popupDeltaX)).toBeLessThan(3);
        expect(Math.abs(buildingDeltaY - popupDeltaY)).toBeLessThan(3);

        await page.getByRole('button', { name: 'Close indoor popup' }).click();
        await expect(popup).toHaveCount(0);

        const dashboardLayout = await page.evaluate(() => {
            const modeBar = document.querySelector('.floating-start-bar')?.getBoundingClientRect();
            const routeSheet = document.querySelector('#navigation-sheet')?.getBoundingClientRect();
            const navigatorBadge = document.querySelector('.floating-ai-badge')?.getBoundingClientRect();
            const navigatorOrb = document.querySelector('.floating-main-pin')?.getBoundingClientRect();
            const navigatorPin = document.querySelector('.pin-icon');
            const navigatorPinRect = navigatorPin?.getBoundingClientRect();
            const controls = Array.from(document.querySelectorAll(
                '.floating-mode-btn, .navigation-action, .navigation-details-toggle',
            ))
                .filter(element => {
                    const style = window.getComputedStyle(element);
                    return style.display !== 'none' && style.visibility !== 'hidden';
                })
                .map(element => ({
                    label: element.textContent.trim(),
                    compact: element.classList.contains('navigation-details-toggle'),
                    height: element.getBoundingClientRect().height,
                    cssHeight: window.getComputedStyle(element).height,
                    transform: window.getComputedStyle(element).transform,
                }))
                .filter(control => control.height > 0);

            return {
                viewportWidth: document.documentElement.clientWidth,
                scrollWidth: document.documentElement.scrollWidth,
                modeLeft: modeBar?.left ?? -1,
                modeRight: modeBar?.right ?? window.innerWidth + 1,
                controls,
                quickPillHeights: Array.from(
                    document.querySelectorAll('.navigation-details-toggle'),
                    element => element.getBoundingClientRect().height,
                ),
                routeBottom: routeSheet?.bottom ?? 0,
                modeTop: modeBar?.top ?? window.innerHeight,
                navigatorClearance: navigatorBadge && navigatorOrb
                    ? navigatorOrb.top - navigatorBadge.bottom
                    : 0,
                navigatorPinTransform: navigatorPin
                    ? window.getComputedStyle(navigatorPin).transform
                    : null,
                navigatorPinWidth: navigatorPinRect?.width ?? 0,
                navigatorPinHeight: navigatorPinRect?.height ?? 0,
                routeLegendDisplay: window.getComputedStyle(
                    document.querySelector('.premium-legend'),
                ).display,
                filteredBuildings: Array.from(
                    document.querySelectorAll('.leaflet-buildings-pane path'),
                ).filter(element => window.getComputedStyle(element).filter !== 'none').length,
                mapCanvasCount: document.querySelectorAll('#map canvas').length,
            };
        });

        expect(dashboardLayout.scrollWidth).toBeLessThanOrEqual(dashboardLayout.viewportWidth + 1);
        expect(dashboardLayout.modeLeft).toBeGreaterThanOrEqual(0);
        expect(dashboardLayout.modeRight).toBeLessThanOrEqual(dashboardLayout.viewportWidth);
        expect(dashboardLayout.navigatorClearance).toBeGreaterThanOrEqual(8);
        expect(dashboardLayout.navigatorPinTransform).toBe('none');
        expect(dashboardLayout.navigatorPinHeight).toBeGreaterThan(dashboardLayout.navigatorPinWidth);
        expect(dashboardLayout.routeLegendDisplay).toBe('none');
        expect(dashboardLayout.filteredBuildings).toBe(0);
        expect(dashboardLayout.mapCanvasCount).toBeGreaterThanOrEqual(1);
        expect(
            Math.min(...dashboardLayout.controls
                .filter(control => !control.compact)
                .map(control => control.height)),
            JSON.stringify(dashboardLayout.controls),
        ).toBeGreaterThanOrEqual(44);
        expect(Math.min(...dashboardLayout.quickPillHeights)).toBeGreaterThanOrEqual(38);
        expect(Math.max(...dashboardLayout.quickPillHeights)).toBeLessThanOrEqual(40);
        expect(dashboardLayout.routeBottom).toBeLessThanOrEqual(dashboardLayout.modeTop + 1);

        await page.locator('#navigation-details-toggle').click();
        await expect(page.locator('#navigation-sheet')).toBeVisible();
        await page.locator('#navigation-end-btn').click();
        await expect(page.locator('#navigation-sheet')).toBeHidden();

        await page.locator('#cr-navigation-toggle').click();
        await page.locator('#cr-navigation-modal [data-cr-mode="default"]').click();
        await expect(page.locator('#cr-navigation-results')).toBeVisible({
            timeout: 15_000,
        });
        const crMobileLayout = await page.locator('.cr-navigation-dialog').evaluate(element => {
            const rect = element.getBoundingClientRect();
            const legend = element.querySelector('.cr-navigation-range-legend');
            const close = element.querySelector('.cr-navigation-close')?.getBoundingClientRect();

            return {
                left: rect.left,
                right: rect.right,
                bottom: rect.bottom,
                viewportWidth: window.innerWidth,
                viewportHeight: window.innerHeight,
                legendVisible: Boolean(legend && legend.getBoundingClientRect().height > 0),
                closeWidth: close?.width || 0,
                closeHeight: close?.height || 0,
            };
        });
        expect(crMobileLayout.left).toBeGreaterThanOrEqual(0);
        expect(crMobileLayout.right).toBeLessThanOrEqual(crMobileLayout.viewportWidth);
        expect(crMobileLayout.bottom).toBeLessThanOrEqual(crMobileLayout.viewportHeight);
        expect(crMobileLayout.legendVisible).toBe(true);
        expect(crMobileLayout.closeWidth).toBeGreaterThanOrEqual(44);
        expect(crMobileLayout.closeHeight).toBeGreaterThanOrEqual(44);
        await page.locator('#cr-navigation-close').click();

        await openDestinationBrowser(page);
        const dialog = page.locator('#browseOptionsModal [role="dialog"]');
        await expect(dialog).toHaveAttribute('aria-modal', 'true');
        await expect
            .poll(() => dialog.evaluate(element => element.contains(document.activeElement)))
            .toBe(true);
        expect(accessibilityWarnings).toEqual([]);
        runtime.expectNone();
    });
});

test.describe('low-end mobile building popup', () => {
    test.use({
        viewport: { width: 390, height: 844 },
        deviceScaleFactor: 3,
        userAgent: 'Mozilla/5.0 (Linux; Android 11; CPH2349) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36',
    });

    test('keeps Canvas building layers aligned when indoor maps are unavailable', async ({ page }) => {
        const runtime = monitorRuntimeErrors(page);
        await loginAsUser(page);

        const initialState = await page.evaluate(async () => {
            const buildings = await fetch('/api/buildings').then(response => response.json());
            const building = buildings.find(item => item.name === 'Human Kinetic Building');
            const bounds = window.L.geoJSON({
                type: 'Feature',
                geometry: building.geometry,
            }).getBounds();
            const center = bounds.getCenter();
            const point = window.map.latLngToContainerPoint(center);

            return {
                mapCenter: window.map.getCenter(),
                point,
                renderMode: document.body.dataset.renderQuality,
                renderers: Array.from(document.querySelectorAll('#map canvas')).map(element => ({
                    pane: element.parentElement?.className,
                    backingWidth: element.width,
                    cssWidth: element.getBoundingClientRect().width,
                    left: element.getBoundingClientRect().left,
                    top: element.getBoundingClientRect().top,
                    transform: window.getComputedStyle(element).transform,
                })),
            };
        });

        expect(initialState.renderMode).toBe('low');
        const initialBuildingsBudget = initialState.renderers.find(renderer => (
            renderer.pane?.includes('leaflet-buildings-pane')
        ));
        const initialDepthBudget = initialState.renderers.find(renderer => (
            renderer.pane?.includes('leaflet-buildingDepth-pane')
        ));
        expect(initialBuildingsBudget.backingWidth / initialBuildingsBudget.cssWidth).toBeGreaterThan(1.45);
        expect(initialBuildingsBudget.backingWidth / initialBuildingsBudget.cssWidth).toBeLessThanOrEqual(1.51);
        expect(initialDepthBudget.backingWidth).toBeLessThanOrEqual(
            Math.ceil(initialDepthBudget.cssWidth) + 1,
        );
        await page.mouse.click(initialState.point.x, initialState.point.y);
        await expect(page.locator('.building-summary-leaflet-popup')).toBeVisible();
        await expect(page.locator('.building-map-summary.is-unavailable')).toBeVisible();

        const finalState = await page.evaluate(() => ({
            mapCenter: window.map.getCenter(),
            renderers: Array.from(document.querySelectorAll('#map canvas')).map(element => ({
                pane: element.parentElement?.className,
                left: element.getBoundingClientRect().left,
                top: element.getBoundingClientRect().top,
                transform: window.getComputedStyle(element).transform,
            })),
        }));
        const finalCenter = finalState.mapCenter;
        const initialBuildings = initialState.renderers.find(renderer => (
            renderer.pane?.includes('leaflet-buildings-pane')
        ));
        const finalBuildings = finalState.renderers.find(renderer => (
            renderer.pane?.includes('leaflet-buildings-pane')
        ));
        const finalDepth = finalState.renderers.find(renderer => (
            renderer.pane?.includes('leaflet-buildingDepth-pane')
        ));

        expect(finalBuildings.transform).toBe(initialBuildings.transform);
        expect(finalBuildings.left).toBeCloseTo(initialBuildings.left, 3);
        expect(finalBuildings.top).toBeCloseTo(initialBuildings.top, 3);
        expect(finalBuildings.left).toBeCloseTo(finalDepth.left, 3);
        expect(finalBuildings.top).toBeCloseTo(finalDepth.top, 3);
        expect(finalCenter.lat).toBeCloseTo(initialState.mapCenter.lat, 8);
        expect(finalCenter.lng).toBeCloseTo(initialState.mapCenter.lng, 8);
        runtime.expectNone();
    });
});

test.describe('low-end mobile indoor route', () => {
    test.use({
        viewport: { width: 390, height: 844 },
        deviceScaleFactor: 3,
        userAgent: 'Mozilla/5.0 (Linux; Android 11; CPH2349) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36',
    });

    test('centers and paints a room route opened from text search', async ({ page }) => {
        const runtime = monitorRuntimeErrors(page);
        await loginAsUser(page);

        await page.locator('#destination-menu-toggle').click();
        await page.locator('#text-search-command-btn').click();
        await expect(page.locator('#ai-search-panel')).toBeVisible();
        await page.locator('#destination-search-input').fill('it laboratory');
        await page.locator('#ai-search-panel .ai-search-submit').click();

        await expect(page.locator('.route-building-map-popup-btn')).toBeVisible({
            timeout: 15_000,
        });
        await page.evaluate(() => window.openIndoorFromRoutePopup());
        await expect(page.locator('#indoorPanel')).toHaveClass(/active/);
        await expect(page.locator('#indoorFooter')).toContainText('Destination floor reached');

        let visualState = null;
        await expect.poll(async () => {
            visualState = await page.evaluate(() => {
            const map = document.querySelector('#indoorMap');
            const image = map?.querySelector('.leaflet-image-layer');
            const routePath = map?.querySelector(
                '.wayfinding-indoor-route-pane path.route-line-live-indoor',
            );
            if (!map || !image || !routePath) return null;

            const mapRect = map.getBoundingClientRect();
            const imageRect = image.getBoundingClientRect();

            return {
                routeLength: routePath.getTotalLength(),
                horizontalCenterOffset: Math.abs(
                    (imageRect.left + imageRect.width / 2)
                    - (mapRect.left + mapRect.width / 2)
                ),
                verticalCenterOffset: Math.abs(
                    (imageRect.top + imageRect.height / 2)
                    - (mapRect.top + mapRect.height / 2)
                ),
                mapWidth: mapRect.width,
                mapHeight: mapRect.height,
            };
            });
            return visualState?.routeLength || 0;
        }).toBeGreaterThan(10);

        expect(visualState.horizontalCenterOffset).toBeLessThan(visualState.mapWidth * 0.18);
        expect(visualState.verticalCenterOffset).toBeLessThan(visualState.mapHeight * 0.18);
        runtime.expectNone();
    });

    test('keeps the room route visible during and after indoor zoom and drag', async ({ page }) => {
        const runtime = monitorRuntimeErrors(page);
        await loginAsUser(page);
        await expect.poll(() => page.evaluate(() => (
            window.WayfindingCrBridge?.getRooms?.().length || 0
        ))).toBeGreaterThan(0);

        const routedRoom = await page.evaluate(async () => {
            const bridge = window.WayfindingCrBridge;
            const seedRoom = bridge.getRooms().find(room => (
                room.properties?.building_name === 'Admin Building'
                && Number(room.properties?.floor_number) === 2
            ));
            if (!seedRoom) throw new Error('Admin Building 2F test room is unavailable.');

            await bridge.prepareRooms([seedRoom]);
            const room = bridge.getRooms().find(candidate => (
                Number(candidate.properties?.id) === Number(seedRoom.properties?.id)
            ));
            await bridge.chooseStartMode('default');
            await bridge.routeToRoom(room);

            return {
                id: Number(room.properties?.id),
                name: room.properties?.name,
            };
        });

        expect(routedRoom.id).toBeGreaterThan(0);
        await expect(page.locator('.route-building-map-popup-btn')).toBeVisible();
        await page.evaluate(() => window.openIndoorFromRoutePopup());
        await expect(page.locator('#indoorPanel')).toHaveClass(/active/);
        await page.locator('#indoorFloorButtons [data-floor="2"]').click();
        await expect(page.locator('#indoorFooter')).toContainText('Destination floor reached');

        const routePath = page.locator(
            '#indoorMap .wayfinding-indoor-route-pane path.route-line-live-indoor',
        );
        await expect(routePath).toBeVisible();
        await expect.poll(() => routePath.evaluate(path => path.getTotalLength()))
            .toBeGreaterThan(10);

        const gestureVisibility = await page.evaluate(() => {
            document.body.classList.add('indoor-map-zooming');
            const route = document.querySelector(
                '#indoorMap .wayfinding-indoor-route-pane path.route-line-live-indoor',
            );
            const markerPane = document.querySelector('#indoorMap .leaflet-marker-pane');
            const result = {
                routePath: route ? window.getComputedStyle(route).visibility : 'missing',
                markerPane: markerPane ? window.getComputedStyle(markerPane).visibility : 'missing',
            };
            document.body.classList.remove('indoor-map-zooming');
            return result;
        });
        expect(gestureVisibility.routePath).toBe('visible');
        expect(gestureVisibility.markerPane).toBe('visible');

        await page.locator('#indoorMap .leaflet-control-zoom-in').click();
        await expect(page.locator('body')).not.toHaveClass(/indoor-map-zooming/);
        await expect(routePath).toBeVisible();

        const mapBox = await page.locator('#indoorMap').boundingBox();
        expect(mapBox).not.toBeNull();
        await page.mouse.move(mapBox.x + mapBox.width * 0.55, mapBox.y + mapBox.height * 0.55);
        await page.mouse.down();
        await page.mouse.move(mapBox.x + mapBox.width * 0.42, mapBox.y + mapBox.height * 0.62, {
            steps: 5,
        });
        await page.mouse.up();
        await expect(routePath).toBeVisible();
        runtime.expectNone();
    });
});
