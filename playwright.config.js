import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  globalSetup: './tests/e2e/global-setup.js',
  use: { baseURL: 'http://127.0.0.1:8000', ...devices['Desktop Chrome'] },
  webServer: {
    command: 'php artisan serve --host=127.0.0.1 --port=8000',
    url: 'http://127.0.0.1:8000/ui/components',
    reuseExistingServer: true,
    timeout: 120000,
  },
});
