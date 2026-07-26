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

test('keeps the outdoor map usable when optional campus-event data fails', async ({ page }) => {
    const runtime = monitorRuntimeErrors(page);
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
    runtime.expectNone({ ignore: [/campus-events/] });
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
        await loginAsUser(page);
        await openDestinationBrowser(page);
        await page.locator('#destination-building-select').selectOption({
            label: 'Information Technology',
        });
        await page.getByRole('button', { name: /Find Route/i }).click();

        const popup = page.locator('.route-building-map-popup');
        await expect(popup).toBeVisible();
        await page.waitForTimeout(500);

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
        expect(layout.actionHeight).toBeGreaterThanOrEqual(52);
        expect(layout.diagnosticsPresent).toBe(false);

        await page.getByRole('button', { name: 'Close indoor popup' }).click();
        await expect(popup).toHaveCount(0);

        const dashboardLayout = await page.evaluate(() => {
            const modeBar = document.querySelector('.floating-start-bar')?.getBoundingClientRect();
            const routeSheet = document.querySelector('#navigation-sheet')?.getBoundingClientRect();
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
                controls,
                quickPillHeights: Array.from(
                    document.querySelectorAll('.navigation-details-toggle'),
                    element => element.getBoundingClientRect().height,
                ),
                routeBottom: routeSheet?.bottom ?? 0,
                modeTop: modeBar?.top ?? window.innerHeight,
            };
        });

        expect(dashboardLayout.scrollWidth).toBeLessThanOrEqual(dashboardLayout.viewportWidth + 1);
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
        runtime.expectNone();
    });
});
