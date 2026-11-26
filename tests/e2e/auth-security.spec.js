import { test, expect } from '@playwright/test';

const password = process.env.UAT_PASSWORD || 'password';

async function login(page, email = 'uat.student@example.com') {
  await page.goto('/login');
  await page.locator('input[name="email"]').fill(email);
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
}

test('preserves an internal intended redirect and supports logout', async ({ page }) => {
  await page.goto('/student/lessons/radar-may-bay');
  await expect(page).toHaveURL(/\/login/);
  await login(page);
  await expect(page).toHaveURL(/\/student\/lessons\/radar-may-bay$/);
  await page.goto('/account/settings');
  await page.locator('button').first().click();
  await expect(page).toHaveURL(/\/login$/);
});

test('requires password confirmation before revoking another browser session', async ({ browser }) => {
  const first = await browser.newContext();
  const second = await browser.newContext();
  const firstPage = await first.newPage();
  const secondPage = await second.newPage();

  await login(firstPage);
  await login(secondPage);
  await firstPage.goto('/account/settings');

  const revokeForm = firstPage.locator('form[action*="/account/sessions/"]').filter({ has: firstPage.locator('input[name="_method"][value="DELETE"]') }).first();
  await expect(revokeForm).toBeVisible();
  await revokeForm.locator('button').click();
  await expect(firstPage).toHaveURL(/\/confirm-password$/);
  await firstPage.locator('input[name="password"]').fill(password);
  await firstPage.locator('button[type="submit"]').click();
  await expect(firstPage).toHaveURL(/\/account\/settings$/);
  await firstPage.locator('form[action*="/account/sessions/"]').filter({ has: firstPage.locator('input[name="_method"][value="DELETE"]') }).first().locator('button').click();
  await expect(firstPage).toHaveURL(/\/account\/settings$/);

  await first.close();
  await second.close();
});

test('shows verification notice and throttled resend for an unverified account', async ({ page }) => {
  await login(page, 'uat.unverified@example.com');
  await page.goto('/student/learn');
  await expect(page).toHaveURL(/\/verify-email$/);
  await page.locator('form[action*="/email/verification-notification"] button').click();
  await expect(page.getByRole('status')).toBeVisible();
});

test('keeps reset and invite entry points browser-accessible without exposing account state', async ({ page }) => {
  await page.goto('/forgot-password');
  await expect(page.locator('input[name="email"]')).toBeVisible();
  await page.goto('/invite/invalid-token');
  await expect(page).toHaveURL(/\/invite\/invalid-token$/);
  await expect(page.locator('body')).toContainText('Liên kết không còn hiệu lực');
});
