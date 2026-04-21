import { test, expect } from '../fixtures';

/**
 * Tests E2E — Avis / Reviews
 * Couvre : liste des avis, création, réponse propriétaire, signalement
 */

test.describe('Avis publics (résidence)', () => {
  test('liste des avis d\'une résidence se charge', async ({ page }) => {
    // Récupérer une résidence existante
    await page.goto('/residences');

    const firstCard = page.locator(
      'a[href*="/residences/"]:not([href*="/residences/map"]):not([href*="/residences/search"]):not([href="/residences"])'
    ).first();

    const hasCard = await firstCard.isVisible({ timeout: 3_000 }).catch(() => false);
    if (hasCard) {
      const href = await firstCard.getAttribute('href');
      if (href) {
        const residenceId = href.split('/').pop();
        const response = await page.goto(`/residences/${residenceId}/reviews`);
        expect(response?.status()).toBe(200);
        await expect(page.locator('body')).not.toContainText('ErrorException');
      }
    }
  });

  test('avis affichés sur la page résidence', async ({ page }) => {
    await page.goto('/residences');

    const firstCard = page.locator(
      'a[href*="/residences/"]:not([href*="/residences/map"]):not([href*="/residences/search"]):not([href="/residences"])'
    ).first();

    const hasCard = await firstCard.isVisible({ timeout: 3_000 }).catch(() => false);
    if (hasCard) {
      const href = await firstCard.getAttribute('href');
      if (href) {
        await page.goto(href);
        // Section avis peut être vide mais ne doit pas crasher
        await expect(page.locator('body')).not.toContainText('ErrorException');
        await expect(page.locator('body')).not.toContainText('Whoops');
      }
    }
  });
});

test.describe('Mes avis (client)', () => {
  test('page mes avis se charge', async ({ clientPage }) => {
    const response = await clientPage.goto('/reviews/my');
    expect(response?.status()).toBe(200);
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Mes avis (owner)', () => {
  test('page avis reçus se charge', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/reviews/my');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Création d\'avis', () => {
  test('formulaire création avis (page)', async ({ clientPage }) => {
    // Nécessite une résidence existante
    await clientPage.goto('/residences');

    const firstCard = clientPage.locator(
      'a[href*="/residences/"]:not([href*="/residences/map"]):not([href*="/residences/search"]):not([href="/residences"])'
    ).first();

    const hasCard = await firstCard.isVisible({ timeout: 3_000 }).catch(() => false);
    if (hasCard) {
      const href = await firstCard.getAttribute('href');
      if (href) {
        const residenceId = href.split('/').pop();
        const response = await clientPage.goto(`/reviews/create/${residenceId}`);
        // 200 si autorisé, 403 si pas de réservation passée, 404 si résidence inexistante
        expect([200, 403, 404]).toContain(response?.status() ?? 0);
        await expect(clientPage.locator('body')).not.toContainText('ErrorException');
      }
    }
  });

  test('validation côté serveur (soumission vide)', async ({ clientPage }) => {
    await clientPage.goto('/residences');

    const firstCard = clientPage.locator(
      'a[href*="/residences/"]:not([href*="/residences/map"]):not([href*="/residences/search"]):not([href="/residences"])'
    ).first();

    const hasCard = await firstCard.isVisible({ timeout: 3_000 }).catch(() => false);
    if (hasCard) {
      const href = await firstCard.getAttribute('href');
      if (href) {
        const residenceId = href.split('/').pop();
        await clientPage.goto(`/reviews/create/${residenceId}`);

        const submitBtn = clientPage.locator('button[type="submit"]').first();
        const hasSubmit = await submitBtn.isVisible({ timeout: 2_000 }).catch(() => false);

        if (hasSubmit) {
          await submitBtn.click();
          // Doit avoir des erreurs de validation, pas un crash serveur
          await expect(clientPage.locator('body')).not.toContainText('ErrorException');
          await expect(clientPage.locator('body')).not.toContainText('Whoops, something went wrong');
        }
      }
    }
  });
});

test.describe('Réponse propriétaire', () => {
  test('endpoint respond ne crash pas', async ({ ownerPage }) => {
    // On teste que l'endpoint existe et répond correctement
    const csrfToken = await ownerPage.goto('/reviews/my').then(async () => {
      return ownerPage.locator('meta[name="csrf-token"]').getAttribute('content');
    });

    if (csrfToken) {
      const response = await ownerPage.request.post('/reviews/9999/respond', {
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        data: { response: 'Merci pour votre retour !' },
      });
      // 404 si review inexistante, 403 si pas autorisé, 422 si validation — jamais 500
      expect(response.status()).not.toBe(500);
    }
  });
});

test.describe('Vote utile', () => {
  test('endpoint helpful ne crash pas', async ({ clientPage }) => {
    await clientPage.goto('/reviews/my');
    const csrfToken = await clientPage.locator('meta[name="csrf-token"]').getAttribute('content');

    if (csrfToken) {
      const response = await clientPage.request.post('/reviews/9999/helpful', {
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        data: {},
      });
      // 404 si review inexistante — jamais 500
      expect(response.status()).not.toBe(500);
    }
  });
});

test.describe('Signalement d\'avis', () => {
  test('endpoint report ne crash pas', async ({ clientPage }) => {
    await clientPage.goto('/reviews/my');
    const csrfToken = await clientPage.locator('meta[name="csrf-token"]').getAttribute('content');

    if (csrfToken) {
      const response = await clientPage.request.post('/reviews/9999/report', {
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        data: { reason: 'inappropriate' },
      });
      // 404, 422 ou 429 (rate limit) — jamais 500
      expect(response.status()).not.toBe(500);
    }
  });
});

test.describe('Avis sur invité (propriétaire)', () => {
  test('endpoint guest-review ne crash pas', async ({ ownerPage }) => {
    await ownerPage.goto('/reviews/my');
    const csrfToken = await ownerPage.locator('meta[name="csrf-token"]').getAttribute('content');

    if (csrfToken) {
      const response = await ownerPage.request.post('/reviews/9999/guest-review', {
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        data: {
          rating: 5,
          comment: 'Excellent invité !',
        },
      });
      // 404, 403, 422 — jamais 500
      expect(response.status()).not.toBe(500);
    }
  });
});

test.describe('Détail d\'un avis', () => {
  test('page show review', async ({ page }) => {
    // On teste avec un ID potentiellement inexistant
    const response = await page.goto('/reviews/1');
    // 200 si existe, 404 sinon — jamais 500
    expect([200, 404]).toContain(response?.status() ?? 0);
    if (response?.status() === 200) {
      await expect(page.locator('body')).not.toContainText('ErrorException');
    }
  });
});
