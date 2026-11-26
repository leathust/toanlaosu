import { test, expect } from '@playwright/test';

const password = process.env.UAT_PASSWORD || 'password';

async function login(page, email = 'uat.admin@example.com') {
  await page.goto('/login');
  await page.getByLabel('Email').fill(email);
  await page.locator('#password').fill(password);
  await page.locator('#login-submit').click();
}

test('admin can traverse the decision surfaces and preserve filter context', async ({ page }) => {
  test.setTimeout(90000);
  await login(page);
  await expect(page).toHaveURL(/\/admin$/);

  const filter = page.locator('main select').first();
  await filter.selectOption('7d');
  await page.locator('main button').nth(1).click();
  await page.waitForURL(/range=7d/, { timeout: 20000 });
  await expect(page.getByText('Operations Center')).toBeVisible();

  const drillDown = page.getByRole('link', { name: 'Drill-down' }).first();
  if (await drillDown.count()) {
    await drillDown.click();
    await expect(page).toHaveURL(/course=/);
  }

  const surfaces = [
    ['/admin/users', /Users/],
    ['/admin/content/review', /Content review queue/],
    ['/admin/exams', /Live exam health/],
    ['/admin/ai-operations', /AI control plane/],
    ['/admin/analytics/learning', /Learning analytics/],
    ['/admin/action-center', /Action Center/],
    ['/admin/operations', /Audit explorer & system health/],
  ];

  for (const [path, heading] of surfaces) {
    await page.goto(path);
    const landmark = path === '/admin/ai-operations'
      ? page.getByText(heading).first()
      : page.getByRole('heading', { name: heading });
    await expect(landmark).toBeVisible();
  }
});

test('admin surfaces pass desktop, tablet and mobile emergency viewport checks', async ({ page }) => {
  await login(page);
  const viewports = [
    { width: 1440, height: 900 },
    { width: 1024, height: 768 },
    { width: 390, height: 844 },
  ];

  for (const viewport of viewports) {
    await page.setViewportSize(viewport);
    await page.goto('/admin/operations');
    await expect(page.getByRole('heading', { name: 'Audit explorer & system health' })).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBeTruthy();
  }
});

test('guest and non-admin roles cannot cross the admin boundary', async ({ browser }) => {
  const guest = await browser.newContext();
  const guestPage = await guest.newPage();
  await guestPage.goto('/admin');
  await expect(guestPage).toHaveURL(/\/login/);
  await guest.close();

  for (const email of ['uat.student@example.com', 'uat.teacher@example.com']) {
    const context = await browser.newContext();
    const page = await context.newPage();
    await login(page, email);
    const response = await page.goto('/admin');
    expect(response.status()).toBe(403);
    await context.close();
  }
});

test('expired admin session returns to login instead of rendering stale workspace', async ({ page, context }) => {
  await login(page);
  await expect(page).toHaveURL(/\/admin$/);
  await context.clearCookies();
  await page.goto('/admin');
  await expect(page).toHaveURL(/\/login/);
});
