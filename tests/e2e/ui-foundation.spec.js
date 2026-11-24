import { test, expect } from '@playwright/test';

test('UI kit exposes keyboard-focusable controls and responsive content', async ({ page }) => {
  await page.goto('/ui/components');
  await expect(page.getByRole('heading', { name: 'Một bộ giao diện rõ ràng cho việc học khó.' })).toBeVisible();

  const firstLink = page.getByRole('link').first();
  await firstLink.focus();
  await expect(firstLink).toBeFocused();

  await page.setViewportSize({ width: 390, height: 844 });
  await expect(page.getByRole('heading', { name: 'Một bộ giao diện rõ ràng cho việc học khó.' })).toBeVisible();

  await page.goto('/ui/prototype');
  const scene = page.locator('[data-scene-runtime]');
  await expect(scene.locator('[data-scene-stage] svg')).toBeVisible();
  for (const type of ['ant', 'graph', 'surface', 'radar']) {
    await scene.locator(`[data-scene-type="${type}"]`).click();
    await expect(scene.locator('[data-scene-stage] svg')).toHaveAttribute('aria-label', /.+/);
  }
  await scene.locator('[data-scene-play]').click();
  await expect(scene.locator('[data-scene-status]')).toHaveText('Đang chạy');
  await scene.locator('[data-scene-reset]').click();
  await expect(scene.locator('[data-scene-progress-label]')).toHaveText('0%');
});
