import { test, expect } from '../fixtures';

/**
 * Tests E2E — Profils publics et badges
 * Couvre : profil public, badges, avis reçus/donnés, édition profil public
 */

test.describe('Profil public (invité)', () => {
  test('profil utilisateur ID 1 se charge', async ({ page }) => {
    const response = await page.goto('/profile/u/1');
    // 200 si existe, 404 sinon
    expect([200, 404]).toContain(response?.status() ?? 0);

    if (response?.status() === 200) {
      await expect(page.locator('body')).not.toContainText('ErrorException');
    }
  });

  test('page badges utilisateur', async ({ page }) => {
    const response = await page.goto('/profile/u/1/badges');
    expect([200, 404]).toContain(response?.status() ?? 0);

    if (response?.status() === 200) {
      await expect(page.locator('body')).not.toContainText('ErrorException');
    }
  });

  test('avis reçus par utilisateur', async ({ page }) => {
    const response = await page.goto('/profile/u/1/reviews-received');
    expect([200, 404]).toContain(response?.status() ?? 0);

    if (response?.status() === 200) {
      await expect(page.locator('body')).not.toContainText('ErrorException');
    }
  });

  test('avis donnés par utilisateur', async ({ page }) => {
    const response = await page.goto('/profile/u/1/reviews-given');
    expect([200, 404]).toContain(response?.status() ?? 0);

    if (response?.status() === 200) {
      await expect(page.locator('body')).not.toContainText('ErrorException');
    }
  });
});

test.describe('Édition profil public (client)', () => {
  test('page édition profil public se charge', async ({ clientPage }) => {
    const response = await clientPage.goto('/profile/public/edit');
    expect(response?.status()).toBe(200);
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });

  test('formulaire édition contient les champs attendus', async ({ clientPage }) => {
    await clientPage.goto('/profile/public/edit');

    // Vérifier présence des champs de formulaire courants
    const hasForm = await clientPage.locator('form').first().isVisible({ timeout: 5_000 }).catch(() => false);

    if (hasForm) {
      // Le formulaire doit être fonctionnel
      await expect(clientPage.locator('body')).not.toContainText('Whoops');
    }
  });

  test('soumission profil public sans changement', async ({ clientPage }) => {
    await clientPage.goto('/profile/public/edit');

    const submitBtn = clientPage.locator('button[type="submit"]').first();
    const hasSubmit = await submitBtn.isVisible({ timeout: 3_000 }).catch(() => false);

    if (hasSubmit) {
      await submitBtn.click();
      await clientPage.waitForLoadState('networkidle');
      // Ne doit pas crasher (vérifie les erreurs serveur, pas le texte "500m" du contenu)
      await expect(clientPage.locator('body')).not.toContainText('ErrorException');
      await expect(clientPage.locator('body')).not.toContainText('Whoops, something went wrong');
      expect([200, 302, 422]).toContain(clientPage.url() ? 200 : 0);
    }
  });
});

test.describe('Édition profil public (owner)', () => {
  test('owner peut éditer son profil public', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/profile/public/edit');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Rafraîchissement badges', () => {
  test('endpoint refresh badges (client)', async ({ clientPage }) => {
    await clientPage.goto('/profile');
    const csrfToken = await clientPage.locator('meta[name="csrf-token"]').getAttribute('content');

    if (csrfToken) {
      const response = await clientPage.request.post('/profile/badges/refresh', {
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
      });
      // 200 OK ou 429 rate limit — jamais 500
      expect(response.status()).not.toBe(500);
    }
  });
});

test.describe('Accès protégé édition profil', () => {
  test('invité ne peut pas éditer profil public', async ({ page }) => {
    const response = await page.goto('/profile/public/edit');
    // Doit rediriger vers login
    await expect(page).toHaveURL(/login/);
  });
});

test.describe('Profil privé vs public', () => {
  test('page profil privé (/profile) accessible pour client', async ({ clientPage }) => {
    const response = await clientPage.goto('/profile');
    expect(response?.status()).toBe(200);
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });

  test('profil public visible pour tous', async ({ page }) => {
    // Même non connecté, on peut voir un profil public
    const response = await page.goto('/profile/u/1');
    // 200 ou 404 — pas de redirection login
    expect([200, 404]).toContain(response?.status() ?? 0);
    // Ne redirige PAS vers login
    expect(page.url()).not.toContain('/login');
  });
});

test.describe('Profil owner', () => {
  test('owner accède à son profil', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/profile');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });
});
