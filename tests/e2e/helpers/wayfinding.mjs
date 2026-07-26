import { expect } from 'playwright/test';

export function monitorRuntimeErrors(page) {
    const errors = [];
    page.__wayfindingRuntimeErrors = errors;

    page.on('pageerror', error => {
        errors.push(`pageerror: ${error.message}`);
    });

    page.on('console', message => {
        if (message.type() === 'error') {
            const location = message.location()?.url || '';
            const currentOrigin = page.url() ? new URL(page.url()).origin : '';
            const locationOrigin = location ? new URL(location).origin : '';
            const isExternalResourceFailure = message.text().startsWith('Failed to load resource')
                && locationOrigin
                && locationOrigin !== currentOrigin;

            if (!isExternalResourceFailure) {
                errors.push(`console: ${message.text()}${location ? ` (${location})` : ''}`);
            }
        }
    });

    return {
        expectNone({ ignore = [] } = {}) {
            const unexpected = errors.filter(error => (
                !ignore.some(pattern => pattern.test(error))
            ));
            expect(unexpected, unexpected.join('\n')).toEqual([]);
        },
    };
}

export async function loginAsUser(page, path = '/user/dashboard') {
    const email = process.env.E2E_USER_EMAIL || 'user@gmail.com';
    const password = process.env.E2E_USER_PASSWORD || '111';

    await page.goto('/login');

    if (!page.url().includes('/user/dashboard')) {
        await page.locator('input[name="email"]').fill(email);
        await page.locator('input[name="password"]').fill(password);
        await Promise.all([
            page.waitForURL(url => url.pathname === '/user/dashboard'),
            page.getByRole('button', { name: 'Log in' }).click(),
        ]);
    }

    const currentUrl = new URL(page.url());
    const targetUrl = new URL(path, page.url());
    if (
        currentUrl.pathname !== targetUrl.pathname
        || currentUrl.search !== targetUrl.search
    ) {
        await page.goto(path);
    }

    await expect(page.locator('#map')).toBeVisible();
    try {
        await expect
            .poll(() => page.locator('#destination-building-select option').count())
            .toBeGreaterThan(1);
    } catch (error) {
        const diagnostics = await page.evaluate(() => ({
            routeStatus: document.querySelector('#route-result-label')?.textContent?.trim(),
            connectionStatus: document.querySelector('#wayfinding-connection-message')?.textContent?.trim(),
            wayfindingScript: document.querySelector('script[src*="wayfinding-entry"]')?.getAttribute('src'),
        }));
        const runtimeErrors = page.__wayfindingRuntimeErrors || [];

        throw new Error([
            error.message,
            `Dashboard diagnostics: ${JSON.stringify(diagnostics)}`,
            ...runtimeErrors,
        ].join('\n'));
    }
}

export async function openDestinationBrowser(page) {
    await page.locator('#destination-menu-toggle').click();
    await page.getByRole('button', { name: /Browse Options/i }).click();
    await expect(page.locator('#browseOptionsModal')).toBeVisible();
}

export async function createFirstBuildingRoute(page) {
    await openDestinationBrowser(page);
    await page.locator('#destination-building-select').selectOption({ index: 1 });
    await page.getByRole('button', { name: /Find Route/i }).click();

    await expect(page.locator('#navigation-details-toggle')).toBeVisible();
    await expect(page.locator('#navigation-kicker')).toHaveText('Live Navigation');
    await expect(page.locator('#navigation-distance')).not.toHaveText('--');
    await expect(page.locator('#navigation-eta')).not.toHaveText('--');
    await expect(page.locator('#navigation-safety')).not.toHaveText('Checking');
}
