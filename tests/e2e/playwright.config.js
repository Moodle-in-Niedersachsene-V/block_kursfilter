'use strict';

// Browser tests against a running Moodle instance with block_kursfilter on the front page.
// Run: KURSFILTER_E2E_URL=https://… KURSFILTER_E2E_USER=… KURSFILTER_E2E_PASS=… npx playwright test -c tests/e2e
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: '.',
    testMatch: '*.spec.js',
    timeout: 60_000,
    retries: 0,
    workers: 1,
    use: {
        browserName: 'chromium',
        headless: true,
        viewport: {width: 1400, height: 900},
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
});
