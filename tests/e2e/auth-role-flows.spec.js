import { test, expect } from '@playwright/test';

const password = process.env.UAT_PASSWORD || 'password';

test('login routes each UAT role to its server-owned workspace', async ({ browser }) => {
  const cases = [
    ['uat.student@example.com', '/student'],
    ['uat.teacher@example.com', '/teacher'],
    ['uat.admin@example.com', '/admin'],
  ];

  for (const [email, destination] of cases) {
    const context = await browser.newContext();
    const page = await context.newPage();

    await page.goto('/login');
    await page.getByLabel('Email').fill(email);
    await page.locator('#password').fill(password);
    await page.locator('#login-submit').click();
    await expect(page).toHaveURL(new RegExp(`${destination.replace('/', '\\/')}$`));

    await context.close();
  }
});
