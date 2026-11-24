import { test, expect } from '@playwright/test';

test('UI shell exposes security headers and remains usable on mobile', async ({ page }) => {
  const response = await page.goto('/ui/components');
  expect(response.headers()['x-content-type-options']).toBe('nosniff');
  expect(response.headers()['x-frame-options']).toBe('SAMEORIGIN');
  expect(response.headers()['referrer-policy']).toBe('strict-origin-when-cross-origin');

  await page.setViewportSize({ width: 390, height: 844 });
  await expect(page.getByRole('heading', { name: 'Một bộ giao diện rõ ràng cho việc học khó.' })).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBeTruthy();
});
