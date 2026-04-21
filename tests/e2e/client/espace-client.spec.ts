import { test, expect } from '../fixtures';

/**
 * Tests E2E — Espace client
 * Couvre : dashboard, favoris, reservations, profil, historique
 */

test.describe('Dashboard client', () => {
  test('accès protégé — redirige les invités', async ({ page }) => {
    const response = await page.goto('/client/dashboard');
    // Doit rediriger vers login
    await expect(page).toHaveURL(/login/);
  });

  test('client authentifié accède au dashboard', async ({ clientPage }) => {
    const response = await clientPage.goto('/client/dashboard');
    expect(response?.status()).toBe(200);
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
    // H1 ou titre de section visible
    await expect(clientPage.locator('h1, h2').first()).toBeVisible();
  });

  test('dashboard affiche les blocs de résumé', async ({ clientPage }) => {
    await clientPage.goto('/client/dashboard');
    // Vérifier l'absence d'erreur — les widgets peuvent être vides si pas de données
    await expect(clientPage.locator('body')).not.toContainText('Whoops');
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Favoris', () => {
  test('page des favoris se charge', async ({ clientPage }) => {
    const response = await clientPage.goto('/favorites');
    expect(response?.status()).toBe(200);
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });

  test('toggle favori sur une résidence', async ({ clientPage }) => {
    await clientPage.goto('/residences');
    const toggleBtn = clientPage.locator('[data-testid="favorite-toggle"], button[aria-label*="favori" i]').first();

    if (await toggleBtn.isVisible()) {
      await toggleBtn.click();
      // Vérifier que la requête AJAX ne renvoie pas d'erreur
      await clientPage.waitForLoadState('networkidle');
      await expect(clientPage.locator('body')).not.toContainText('500');
    }
  });
});

test.describe('Réservations client', () => {
  test('liste des réservations se charge', async ({ clientPage }) => {
    const response = await clientPage.goto('/bookings');
    expect(response?.status()).toBe(200);
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });

  test('formulaire de réservation sur une résidence', async ({ clientPage }) => {
    // Accéder à une résidence disponible
    await clientPage.goto('/residences');
    const firstCard = clientPage.locator('a[href*="/residences/"]').first();

    if (await firstCard.isVisible()) {
      const href = await firstCard.getAttribute('href');
      if (href) {
        await clientPage.goto(href);
        // Vérifier que le bouton "Réserver" ou formulaire est présent
        const bookBtn = clientPage.locator('a[href*="/bookings/create"], button:has-text("Réserver"), a:has-text("Réserver")').first();
        // On ne clique pas pour éviter des réservations test en BD — on vérifie juste la présence
        if (await bookBtn.isVisible()) {
          await expect(bookBtn).toBeEnabled();
        }
      }
    }
  });
});

test.describe('Profil utilisateur', () => {
  test('page profil se charge', async ({ clientPage }) => {
    const response = await clientPage.goto('/profile');
    expect(response?.status()).toBe(200);
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });

  test('formulaire de mise à jour du nom', async ({ clientPage }) => {
    await clientPage.goto('/profile');
    const nameInput = clientPage.locator('input[name="name"]').first();

    if (await nameInput.isVisible()) {
      const current = await nameInput.inputValue();
      await nameInput.fill(current); // réentrer la même valeur — pas de changement réel
      // Vérifier que le formulaire n'est pas cassé
      await expect(nameInput).toBeEditable();
    }
  });
});

test.describe('Notifications client', () => {
  test('page notifications se charge', async ({ clientPage }) => {
    const response = await clientPage.goto('/notifications');
    expect(response?.status()).toBe(200);
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Historique des vues', () => {
  test('page historique se charge', async ({ clientPage }) => {
    const response = await clientPage.goto('/history');
    expect(response?.status()).toBe(200);
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Messagerie', () => {
  test('page chat se charge', async ({ clientPage }) => {
    const response = await clientPage.goto('/chat');
    expect(response?.status()).toBe(200);
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });
});
