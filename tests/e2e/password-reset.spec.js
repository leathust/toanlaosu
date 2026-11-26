import { test, expect } from '@playwright/test';

test('forgot password shows a neutral confirmation in the browser', async ({ page }) => {
  await page.goto('/forgot-password');
  await page.getByLabel('Email tài khoản').fill('unknown-browser@example.test');
  await page.getByRole('button', { name: 'Gửi hướng dẫn' }).click();

  await expect(page).toHaveURL(/\/forgot-password$/);
  await expect(page.getByRole('status')).toContainText('Nếu email tồn tại');
});
