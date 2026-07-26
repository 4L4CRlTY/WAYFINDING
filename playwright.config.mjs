import { defineConfig } from 'playwright/test';

const baseURL = process.env.E2E_BASE_URL || 'http://wayfinding.test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    timeout: 45_000,
    expect: {
        timeout: 10_000,
    },
    reporter: [['list']],
    outputDir: 'test-results',
    use: {
        baseURL,
        channel: 'chrome',
        headless: true,
        viewport: { width: 1440, height: 900 },
        actionTimeout: 10_000,
        navigationTimeout: 20_000,
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
});
