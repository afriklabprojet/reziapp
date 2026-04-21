import { defineConfig, devices } from '@playwright/test';

/**
 * Configuration Playwright pour REZI
 * App URL : http://localhost:8000 (php artisan serve)
 *
 * Pour lancer :
 *   npm run test:e2e              → suite complète
 *   npm run test:e2e -- --ui      → mode interactif
 *   npm run test:e2e -- --headed  → avec navigateur visible
 */
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false,       // séquentiel — l'appli partage une DB de test
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: [
    ['line'],
    ['html', { outputFolder: 'tests/e2e/reports', open: 'never' }],
  ],

  use: {
    baseURL: process.env.APP_URL ?? 'http://localhost:8000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'off',
    locale: 'fr-FR',
    timezoneId: 'Africa/Abidjan',
  },

  // ─── Serveur de développement automatique ─────────────────────────────
  webServer: {
    command: 'php artisan serve --port=8000',
    url: 'http://localhost:8000',
    reuseExistingServer: true,
    timeout: 30_000,
    stdout: 'pipe',
    stderr: 'pipe',
  },

  projects: [
    // ─── Setup : seed DB, créer les comptes de test ───────────────────────
    {
      name: 'setup',
      testMatch: /.*\.setup\.ts/,
      timeout: 90_000, // Filament + seeder peuvent être lents
      use: { channel: 'chrome' },
    },

    // ─── Suite principale (Chrome système) ───────────────────────────────
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'], channel: 'chrome' },
      dependencies: ['setup'],
    },

    // ─── Mobile Safari (optionnel — désactiver dans la CI pour aller vite) ─
    // {
    //   name: 'mobile-safari',
    //   use: { ...devices['iPhone 14'] },
    //   dependencies: ['setup'],
    // },
  ],
});
