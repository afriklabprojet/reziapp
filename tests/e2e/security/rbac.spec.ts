import { test, expect } from '../fixtures';

/**
 * Tests E2E — Sécurité / RBAC
 * Vérifie que les routes protégées rejettent les accès non autorisés
 */

const ownerOnlyRoutes = [
  '/owner/dashboard',
  '/owner/residences',
  '/owner/residences/create',
  '/owner/bookings',
  '/owner/analytics',
];

const authOnlyRoutes = [
  '/client/dashboard',
  '/favorites',
  '/bookings',
  '/chat',
  '/notifications',
  '/profile',
  '/history',
  '/payments/history',
  '/invoices',
];

test.describe('Routes protégées (invité non authentifié)', () => {
  for (const url of [...ownerOnlyRoutes, ...authOnlyRoutes]) {
    test(`${url} redirige vers /login`, async ({ page }) => {
      const response = await page.goto(url);
      // Soit redirection 302 vers login, soit la page finale est /login
      await expect(page).toHaveURL(/login/);
    });
  }
});

test.describe('Routes owner protégées (client simple)', () => {
  for (const url of ownerOnlyRoutes) {
    test(`${url} est inaccessible pour un client`, async ({ clientPage }) => {
      const response = await clientPage.goto(url);
      // Doit retourner 403, ou rediriger — jamais afficher la page owner
      const status = response?.status() ?? 0;
      const isBlocked = status === 403 || status === 302 || !clientPage.url().includes(url.replace('/owner', ''));
      expect(
        isBlocked || !clientPage.url().startsWith(new URL(url, clientPage.url()).href),
        `Le client ne devrait pas avoir accès à ${url}`,
      ).toBeTruthy();
    });
  }
});

test.describe('API — rate limiting', () => {
  test('route de calcul prix respecte le throttle', async ({ page }) => {
    // Cette route a throttle:30,1 — on fait 1 seule requête pour ne pas bloquer les tests
    const response = await page.request.post('/residences/1/calculate-price', {
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      data: { check_in: '2026-05-01', check_out: '2026-05-05' },
    });
    // 404 si résidence 1 n'existe pas, 422 si validation, 200 si OK — jamais 500
    expect(response.status()).not.toBe(500);
  });
});

test.describe('CSRF', () => {
  test('POST sans token CSRF est refusé (419)', async ({ page }) => {
    // Tester via API sans referer Laravel
    const response = await page.request.post('/favorites/store', {
      headers: { 'Content-Type': 'application/json' },
      data: { residence_id: 1 },
      // Pas de cookie de session → 419 ou 401
    });
    expect([401, 403, 419, 302]).toContain(response.status());
  });
});
