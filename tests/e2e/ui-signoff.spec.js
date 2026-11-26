import { test, expect } from '@playwright/test';

const publicRoutes = [
  ['/ui/components', 'Một bộ giao diện rõ ràng cho việc học khó.'],
  ['/ui/prototype', 'Máy bay đi vào vùng radar'],
  ['/login', 'Đăng nhập'],
  ['/forgot-password', 'Quên mật khẩu'],
];

test('core public routes render on desktop and mobile without horizontal overflow', async ({ page }) => {
  for (const [path, heading] of publicRoutes) {
    await page.goto(path);
    await expect(page.getByRole('heading', { name: heading })).toBeVisible();
    await page.setViewportSize({ width: 390, height: 844 });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBeTruthy();
    await page.setViewportSize({ width: 1440, height: 900 });
  }
});

test('protected workspaces redirect guests to login', async ({ page }) => {
  for (const path of ['/workspace', '/verify-email', '/student', '/student/lessons/radar-may-bay', '/student/practice/radar-may-bay', '/teacher', '/teacher/exams', '/admin', '/admin/users', '/student/chat']) {
    await page.goto(path);
    await expect(page).toHaveURL(/\/login/);
  }
});

test('authenticated student hydrates and reloads the real learning dashboard', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Email').fill('test@example.com');
  await page.getByLabel('Mật khẩu').fill('password');
  await page.getByRole('button', { name: /Đăng nhập/ }).click();
  await expect(page).toHaveURL(/\/student$/);

  await page.goto('/student/learn');
  await expect(page.getByText('Không gian học tập', { exact: true })).toBeVisible();
  await page.getByRole('link', { name: /E2E Radar Lesson/ }).click();
  await expect(page).toHaveURL(/\/student\/learn\/courses\/e2e-radar\/lessons\/e2e-lesson/);
  await expect(page.getByRole('heading', { name: 'E2E Radar Lesson' })).toBeVisible();
  const completeButton = page.getByRole('button', { name: 'Đánh dấu hoàn thành' }).first();
  if (await completeButton.count()) await completeButton.click();
  await expect(page.getByText('Đã hoàn thành', { exact: true })).toBeVisible();
  await page.getByRole('radio', { name: 'Khi máy bay nằm trong vùng kiểm soát' }).check();
  const attempt = page.waitForResponse((response) => response.url().includes('/student/practice/attempts') && response.request().method() === 'POST');
  await page.getByRole('button', { name: 'Nộp câu trả lời' }).click();
  expect((await attempt).status()).toBe(201);
  await page.reload();
  await expect(page.getByRole('heading', { name: 'E2E Radar Lesson' })).toBeVisible();
  await expect(page.getByText('Đã hoàn thành', { exact: true })).toBeVisible();
});

test('exam autosave recovers a local draft after reconnect', async ({ page, context }) => {
  await page.goto('/login');
  await page.getByLabel('Email').fill('test@example.com');
  await page.getByLabel('Mật khẩu').fill('password');
  await page.getByRole('button', { name: /Đăng nhập/ }).click();
  await expect(page).toHaveURL(/\/student$/);

  await page.goto('/student/exams/01J0E2EEXAM000000000000000/attempt');
  await expect(page.getByRole('heading', { name: 'E2E Radar Exam' })).toBeVisible();
  const input = page.locator('[data-answer-input]').first();
  const state = page.locator('[data-answer-state]').first();
  await input.fill('offline draft');
  await context.setOffline(true);
  await page.getByRole('button', { name: 'Lưu đáp án' }).first().click();
  await expect(state).toContainText('Chưa đồng bộ');

  await context.setOffline(false);
  const saveResponse = page.waitForResponse((response) => response.url().includes('/answers') && response.request().method() === 'POST');
  await page.getByRole('button', { name: 'Lưu đáp án' }).first().click();
  expect((await saveResponse).status()).toBe(200);
  await expect(state).toContainText('Đã lưu');
  await page.reload();
  await expect(input).toHaveValue('offline draft');
});
