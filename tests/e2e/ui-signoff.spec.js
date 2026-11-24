import { test, expect } from '@playwright/test';

const publicRoutes = [
  ['/ui/components', 'Một bộ giao diện rõ ràng cho việc học khó.'],
  ['/ui/prototype', 'Máy bay đi vào vùng radar'],
  ['/student', 'Chào An, hôm nay học gì?'],
  ['/student/lessons/radar-may-bay', 'Máy bay đi vào vùng radar'],
  ['/student/practice/radar-may-bay', 'Câu hỏi tương tác'],
  ['/login', 'Đăng nhập'],
  ['/forgot-password', 'Quên mật khẩu'],
  ['/verify-email', 'Kiểm tra hộp thư của bạn'],
  ['/workspace', 'Bạn muốn làm gì hôm nay?'],
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
  for (const path of ['/teacher', '/teacher/exams', '/admin', '/admin/users', '/student/chat']) {
    await page.goto(path);
    await expect(page).toHaveURL(/\/login/);
  }
});
