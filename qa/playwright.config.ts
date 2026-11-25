import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: '.',
  testMatch: '*.spec.ts',
  timeout: 600000,
  fullyParallel: false,
  workers: 1,
  reporter: [['list']],
  outputDir: './logs/pw-output',
  use: {
    baseURL: 'http://localhost/pideaca/',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
  },
});
