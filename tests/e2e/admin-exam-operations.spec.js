import { test, expect } from '@playwright/test';

const password = process.env.UAT_PASSWORD || 'password';

test('admin can open exam operations and see incident-safe controls', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Email').fill('uat.admin@example.com');
  await page.getByLabel('Mật khẩu').fill(password);
  await page.getByRole('button', { name: /Đăng nhập/ }).click();
  await expect(page).toHaveURL(/\/admin$/);

  await page.goto('/admin/exams');
  await expect(page.getByRole('heading', { name: 'Live exam health' })).toBeVisible();
  await expect(page.getByText('AI disabled during exam')).toBeVisible();
  await expect(page.getByText(/polling/)).toBeVisible();
});
