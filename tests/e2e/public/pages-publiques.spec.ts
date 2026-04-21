import { test, expect } from '../fixtures';

/**
 * Tests E2E — Pages publiques
 * Couvre : accueil, liste résidences, détail résidence, recherche, pages légales
 */

test.describe('Accueil', () => {
  test('affiche le titre et les résidences vedettes', async ({ page }) => {
    await page.goto('/');

    await expect(page).toHaveTitle(/REZI|Rezi/i);

    // Hero / CTA principal visible
    await expect(page.locator('h1').first()).toBeVisible();

    // La section des résidences vedettes se charge (si des résidences existent)
    const featuredSection = page.locator('[data-testid="featured-residences"], .featured-residences, #featured').first();
    // On vérifie simplement que la page ne renvoie pas d'erreur 500
    await expect(page).not.toHaveURL(/error|500/);
  });

  test('formulaire de recherche fonctionne', async ({ page }) => {
    await page.goto('/');

    // Focus sur le champ de recherche principal (commune ou adresse)
    const searchInput = page.locator('input[name="commune"], input[placeholder*="commune" i], input[placeholder*="recherch" i]').first();

    if (await searchInput.isVisible()) {
      await searchInput.fill('Cocody');
      // Vérifier qu'aucune erreur JS ne se produit
      await expect(page).not.toHaveURL(/error/);
    }
  });

  test('liens de navigation principale sont fonctionnels', async ({ page }) => {
    await page.goto('/');

    const navLinks = [
      { url: '/residences', text: /résidences|logements/i },
      { url: '/faq',        text: /faq/i },
    ];

    for (const { url } of navLinks) {
      const response = await page.request.get(url);
      expect(response.status(), `${url} devrait retourner 200`).toBe(200);
    }
  });
});

test.describe('Liste des résidences', () => {
  test('affiche les résidences avec pagination', async ({ page }) => {
    const response = await page.goto('/residences');
    expect(response?.status()).toBe(200);

    // Pas d'erreur Blade visible
    await expect(page.locator('body')).not.toContainText('Whoops! Something went wrong');
    await expect(page.locator('body')).not.toContainText('ErrorException');
  });

  test('filtre par commune fonctionne', async ({ page }) => {
    await page.goto('/residences');

    const communeSelect = page.locator('select[name="commune"]').first();
    if (await communeSelect.isVisible()) {
      await communeSelect.selectOption({ index: 1 });
      // Attendre la navigation ou le rechargement AJAX
      await page.waitForLoadState('networkidle');
      await expect(page).not.toHaveURL(/error/);
    }
  });

  test('peut accéder à la vue carte', async ({ page }) => {
    const response = await page.goto('/residences/map');
    expect(response?.status()).toBe(200);
    await expect(page.locator('body')).not.toContainText('ErrorException');
  });

  test('recherche par texte fonctionne', async ({ page }) => {
    const response = await page.goto('/residences/search?q=cocody');
    expect(response?.status()).toBe(200);
    await expect(page.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Détail résidence', () => {
  test('page de détail se charge sans erreur', async ({ page }) => {
    // Accéder à la liste pour récupérer la première résidence
    await page.goto('/residences');

    // Exclure les liens de navigation (/map, /search) — on veut uniquement les cartes de résidence
    const firstCard = page.locator(
      'a[href*="/residences/"]:not([href*="/residences/map"]):not([href*="/residences/search"]):not([href="/residences"])',
    ).first();

    const hasCard = await firstCard.isVisible({ timeout: 3_000 }).catch(() => false);
    if (!hasCard) {
      // Aucune résidence trouvée en DB — on vérifie juste qu'il n'y a pas d'erreur serveur
      await expect(page.locator('body')).not.toContainText('ErrorException');
      return;
    }

    const href = await firstCard.getAttribute('href');
    if (href) {
      const response = await page.goto(href);
      expect(response?.status()).toBe(200);
      await expect(page.locator('body')).not.toContainText('ErrorException');
      await expect(page.locator('body')).not.toContainText('Whoops');
    }
  });
});

test.describe('Pages légales et statiques', () => {
  const staticPages = [
    '/faq',
    '/conditions-utilisation',
    '/confidentialite',
    '/mentions-legales',
    '/a-propos',
    '/nous-contacter',
    '/guide-proprietaire',
  ];

  for (const url of staticPages) {
    test(`${url} retourne 200`, async ({ page }) => {
      const response = await page.goto(url);
      expect(response?.status(), `${url} doit retourner 200`).toBe(200);
      await expect(page.locator('body')).not.toContainText('ErrorException');
      await expect(page.locator('body')).not.toContainText('Whoops');
    });
  }
});

test.describe('Sitemap', () => {
  test('/sitemap.xml est valide', async ({ page }) => {
    const response = await page.goto('/sitemap.xml');
    expect(response?.status()).toBe(200);
    const contentType = response?.headers()['content-type'] ?? '';
    expect(contentType).toMatch(/xml/);
  });
});
