import { test, expect } from '../fixtures';

/**
 * Tests E2E — Espace propriétaire (owner)
 * Couvre : dashboard, gestion résidences, réservations, analytics
 */

test.describe('Dashboard propriétaire', () => {
  test('accès protégé — redirige les invités', async ({ page }) => {
    await page.goto('/owner/dashboard');
    await expect(page).toHaveURL(/login/);
  });

  test('owner accède au dashboard', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/owner/dashboard');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
    await expect(ownerPage.locator('h1, h2').first()).toBeVisible();
  });
});

test.describe('Gestion des résidences (owner)', () => {
  test('liste des résidences s\'affiche', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/owner/residences');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });

  test('formulaire de création résidence se charge', async ({ ownerPage }) => {
    await ownerPage.goto('/owner/residences/create');
    await ownerPage.waitForLoadState('networkidle');

    // Vérifier qu'on n'a pas été redirigé vers /login (session invalide ou middleware bloquant)
    await expect(ownerPage).not.toHaveURL(/login/, { timeout: 5_000 });
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');

    // Le formulaire de création doit être présent
    await expect(ownerPage.locator('input[name="name"]').first()).toBeVisible({ timeout: 10_000 });
  });

  // Bug connu : la soumission vide retourne 500 au lieu d'erreurs de validation
  test.fixme('validation : soumission vide retourne des erreurs', async ({ ownerPage }) => {
    await ownerPage.goto('/owner/residences/create');

    const submitBtn = ownerPage.locator('button[type="submit"]').first();
    if (await submitBtn.isVisible()) {
      await submitBtn.click();
      // Doit rester sur la page avec des erreurs de validation
      await expect(ownerPage).not.toHaveURL(/dashboard/);
      await expect(ownerPage.locator('body')).not.toContainText('500');
    }
  });
});

test.describe('Réservations propriétaire', () => {
  test('liste des réservations owner se charge', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/owner/bookings');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });

  test('demandes en attente se chargent', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/owner/bookings/requests');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Analytics propriétaire', () => {
  test('page analytics se charge', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/owner/analytics');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Paiements & revenus', () => {
  test('page paiements se charge', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/payments/history');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });

  test('page factures se charge', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/invoices');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Messagerie propriétaire', () => {
  test('page chat accessible', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/chat');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Avis reçus (owner)', () => {
  test('page avis owner se charge', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/reviews/my');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Annulations (owner)', () => {
  test('liste des annulations se charge', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/owner/cancellations');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });
});
