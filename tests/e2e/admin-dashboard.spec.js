import { test, expect } from '@playwright/test';

const password = process.env.UAT_PASSWORD || 'password';

async function loginAsAdmin(page) {
  await page.goto('/login');
  await page.getByLabel('Email').fill('uat.admin@example.com');
  await page.locator('#password').fill(password);
  await page.locator('#login-submit').click();
  await expect(page).toHaveURL(/\/admin$/);
}

test('admin dashboard supports keyboard, responsive and lazy-loaded operations views', async ({ page }) => {
  const response = await page.goto('/login');
  expect(response.headers()['content-security-policy']).toContain("default-src 'self'");
  await loginAsAdmin(page);

  await expect(page.getByText('Operations Center')).toBeVisible();
  await page.locator('a[href="#admin-main"]').focus();
  await expect(page.locator('a[href="#admin-main"]')).toBeFocused();

  await page.setViewportSize({ width: 390, height: 844 });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBeTruthy();

  await page.goto('/admin/operations');
  await expect(page.getByText('Audit explorer & system health')).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Audit log' })).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBeTruthy();
});
