'use strict';

const { test, expect } = require('@playwright/test');

const BASE = (process.env.KURSFILTER_E2E_URL || '').replace(/\/$/, '');
const USER = process.env.KURSFILTER_E2E_USER || '';
const PASS = process.env.KURSFILTER_E2E_PASS || '';

test.skip(!BASE || !USER || !PASS, 'KURSFILTER_E2E_URL/USER/PASS not set');

const block = (page) => page.locator('.block_kursfilter').first();

async function login(page) {
    await page.goto(`${BASE}/login/index.php`);
    await page.fill('#username', USER);
    await page.fill('#password', PASS);
    await page.click('#loginbtn');
    await page.waitForLoadState('networkidle');
}

async function openFrontpage(page) {
    await page.goto(`${BASE}/?redirect=0`, {waitUntil: 'networkidle'});
    await expect(block(page)).toBeVisible();
}

async function search(page, term) {
    await block(page).locator('[data-filter="searchterm"]').fill(term);
}

test.describe('block_kursfilter on the front page', () => {
    let errors;

    test.beforeEach(async ({page}) => {
        errors = [];
        page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
        page.on('console', (m) => m.type() === 'error' && errors.push(`console: ${m.text()}`));
        await login(page);
        await openFrontpage(page);
    });

    test.afterEach(() => {
        expect(errors).toEqual([]);
    });

    test('shows the filter hint before any filter is set', async ({page}) => {
        await expect(block(page).locator('.kf-item')).toHaveCount(0);
        await expect(block(page).locator('[data-region="results"]')).toContainText(/.+/);
        await expect(block(page).locator('[data-region="count"]')).toHaveText('–');
    });

    test('a search shows courses and a count', async ({page}) => {
        await search(page, 'testkurs');
        await expect(block(page).locator('.kf-item').first()).toBeVisible();
        await expect(block(page).locator('[data-region="count"]')).not.toHaveText('–');
    });

    test('search term narrows results and reset restores them', async ({page}) => {
        const items = block(page).locator('.kf-item');
        await search(page, 'testkurs');
        await expect(items.first()).toBeVisible();
        const before = await items.count();
        await search(page, 'bolda-testkurs');
        await expect(items).toHaveCount(1);
        await expect(items.first()).toContainText('bolda-testkurs');
        await block(page).locator('[data-action="reset"]').click();
        await expect(items).toHaveCount(0);
        await expect(block(page).locator('[data-filter="searchterm"]')).toHaveValue('');
        expect(before).toBeGreaterThan(1);
    });

    test('unknown search term shows the empty message', async ({page}) => {
        await block(page).locator('[data-filter="searchterm"]').fill('zzz-gibt-es-nicht-9f3');
        await expect(block(page).locator('.kf-item')).toHaveCount(0);
        await expect(block(page).locator('[data-region="results"]')).toContainText(/.+/);
    });

    test('category select filters by Kursbereich', async ({page}) => {
        const select = block(page).locator('select[data-filter="category"]');
        await select.selectOption({label: 'Bolda'});
        const items = block(page).locator('.kf-item');
        await expect(items.first()).toBeVisible();
        for (const text of await items.locator('.text-muted.small').allTextContents()) {
            expect(text).toMatch(/Bolda/);
        }
    });

    test('chips toggle aria-pressed and are keyboard focusable with visible focus', async ({page}) => {
        const chip = block(page).locator('.kf-chip').first();
        await expect(chip).toHaveAttribute('aria-pressed', 'false');
        await chip.focus();
        const outline = await chip.evaluate((el) => {
            const s = getComputedStyle(el);
            return {style: s.outlineStyle, width: s.outlineWidth, shadow: s.boxShadow};
        });
        expect(outline.style !== 'none' || outline.shadow !== 'none').toBeTruthy();
        await page.keyboard.press('Enter');
        await expect(chip).toHaveAttribute('aria-pressed', 'true');
        await block(page).locator('[data-action="reset"]').click();
        await expect(chip).toHaveAttribute('aria-pressed', 'false');
    });

    test('backup button points to the signed backup endpoint', async ({page, request}) => {
        await search(page, 'bolda-testkurs');
        const link = block(page).locator('a.kf-backup-btn').first();
        await expect(link).toBeVisible();
        const href = await link.getAttribute('href');
        expect(href).toContain('/blocks/kursfilter/backup.php');
        expect(href).toMatch(/sesskey=|courseid=/);
    });

    test('preview button opens the course via a pool account', async ({page, context}) => {
        await block(page).locator('[data-filter="searchterm"]').fill('bolda-testkurs');
        await expect(block(page).locator('.kf-item')).toHaveCount(1);
        const [popup] = await Promise.all([
            context.waitForEvent('page'),
            block(page).locator('.kf-item form button[type="submit"]').first().click(),
        ]);
        await popup.waitForLoadState('networkidle');
        // A real login is never replaced: the user is sent on to the course (or its enrolment page).
        expect(popup.url()).toMatch(/\/course\/view\.php\?id=\d+|\/enrol\/index\.php\?id=\d+/);
        await expect(popup.locator('body')).not.toContainText(/Fatal error|Exception|Coding error/i);
        await popup.close();
    });

    test('rating widget renders a keyboard-operable star control', async ({page}) => {
        await search(page, 'bolda-testkurs');
        const stars = block(page).locator('.kf-stars').first();
        await expect(stars).toBeVisible();
        await expect(stars.locator('button').first()).toBeVisible();
    });

    test('preview as anonymous visitor signs in a pool account and opens the course', async ({browser}) => {
        const context = await browser.newContext();
        const page = await context.newPage();
        await page.goto(`${BASE}/login/index.php`, {waitUntil: 'networkidle'});
        const sesskey = await page.evaluate(() => window.M && window.M.cfg && window.M.cfg.sesskey);
        test.skip(!sesskey, 'no anonymous sesskey available on the login page');
        const courseid = Number(process.env.KURSFILTER_E2E_COURSEID || 0);
        test.skip(!courseid, 'KURSFILTER_E2E_COURSEID not set (a public course id)');
        const response = await page.request.post(`${BASE}/blocks/kursfilter/preview.php`, {
            form: {courseid: String(courseid), sesskey},
        });
        try {
            expect(response.status()).toBe(200);
            expect(response.url()).toMatch(/\/course\/view\.php\?id=\d+/);
            expect(await response.text()).not.toMatch(/Fatal error|Coding error|Exception/);
        } finally {
            // Log the pool account out again, otherwise this IP keeps an occupied preview.
            await page.goto(`${BASE}/course/view.php?id=${courseid}`, {waitUntil: 'networkidle'});
            const poolSesskey = await page.evaluate(() => window.M && window.M.cfg && window.M.cfg.sesskey);
            if (poolSesskey) {
                await page.goto(`${BASE}/login/logout.php?sesskey=${poolSesskey}`, {waitUntil: 'networkidle'});
            }
            await context.close();
        }
    });

    test('a second preview from the same IP shows a notice instead of an error page', async ({browser}) => {
        const courseid = Number(process.env.KURSFILTER_E2E_COURSEID || 0);
        test.skip(!courseid, 'KURSFILTER_E2E_COURSEID not set (a public course id)');
        const first = await browser.newContext();
        const firstPage = await first.newPage();
        const second = await browser.newContext();
        const secondPage = await second.newPage();
        const start = async (page) => {
            await page.goto(`${BASE}/login/index.php`, {waitUntil: 'networkidle'});
            const sesskey = await page.evaluate(() => window.M.cfg.sesskey);
            return page.request.post(`${BASE}/blocks/kursfilter/preview.php`, {
                form: {courseid: String(courseid), sesskey},
            });
        };
        try {
            const ok = await start(firstPage);
            expect(ok.url()).toMatch(/\/course\/view\.php\?id=\d+/);
            // Different session, same IP: no pool account, but a readable notice with HTTP 200.
            const blocked = await start(secondPage);
            expect(blocked.status()).toBe(200);
            expect(await blocked.text()).toMatch(/IP-Adresse|IP address/);
            expect(await blocked.text()).not.toMatch(/Whoops|exc-message|Coding error/);
        } finally {
            await firstPage.goto(`${BASE}/course/view.php?id=${courseid}`, {waitUntil: 'networkidle'});
            const sesskey = await firstPage.evaluate(() => window.M && window.M.cfg && window.M.cfg.sesskey);
            if (sesskey) {
                await firstPage.goto(`${BASE}/login/logout.php?sesskey=${sesskey}`, {waitUntil: 'networkidle'});
            }
            await first.close();
            await second.close();
        }
    });

    test('rating stores the first rating and keeps it on a second attempt', async ({browser}) => {
        // Fresh context: no rating cookie yet, so the course is not rated for this visitor.
        const context = await browser.newContext();
        const page = await context.newPage();
        await login(page);
        await openFrontpage(page);
        await search(page, 'bolda-testkurs');
        const stars = block(page).locator('.kf-stars').first();
        const buttons = stars.locator('button');
        await expect(buttons).toHaveCount(5);
        const wasRated = (await stars.getAttribute('data-alreadyrated')) === '1';
        test.skip(wasRated, 'course already rated by this visitor');
        const responses = [];
        page.on('response', (r) => r.url().includes('/blocks/kursfilter/rate.php') && responses.push(r));
        await buttons.nth(3).click();
        await expect(stars.locator('.kf-star-note')).toBeVisible();
        expect(responses).toHaveLength(1);
        const first = await responses[0].json();
        expect(first).toMatchObject({success: true, stars: 4});
        // Same visitor, new search: the widget is rendered as already rated with the stored stars.
        await block(page).locator('[data-action="reset"]').click();
        await search(page, 'bolda-testkurs');
        const again = block(page).locator('.kf-stars').first();
        await expect(again).toHaveAttribute('data-alreadyrated', '1');
        await expect(again).toHaveAttribute('data-visitorrating', '4');
        await context.close();
    });

    test('backup link answers with a file or a clear hint, never an error page', async ({page}) => {
        await search(page, 'bolda-testkurs');
        const href = await block(page).locator('a.kf-backup-btn').first().getAttribute('href');
        const response = await page.request.get(href);
        expect(response.status()).toBeLessThan(500);
        expect(await response.text()).not.toMatch(/Fatal error|Coding error|Stack trace/);
    });
});
