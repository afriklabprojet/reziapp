import { test, expect } from '../fixtures';

/**
 * Tests E2E — Espace Admin (Filament)
 * Couvre : dashboard admin, ressources principales, modération, statistiques
 *
 * URLs réelles Filament (vérifiées via `php artisan route:list --path=admin`) :
 *   /admin/locataires        → clients (users with role=user)
 *   /admin/proprietaires     → owners
 *   /admin/administrateurs   → admins
 *   /admin/seo-datas         → données SEO
 */

test.describe('Dashboard Admin', () => {
  test('accès protégé — redirige les invités', async ({ page }) => {
    await page.goto('/admin');
    // Filament redirige vers sa propre page de login
    await expect(page).toHaveURL(/admin\/login/);
  });

  test('accès refusé aux clients', async ({ clientPage }) => {
    const response = await clientPage.goto('/admin');
    // Filament peut retourner 403 (l'URL reste /admin) OU rediriger
    const url = clientPage.url();
    const status = response?.status() ?? 0;
    const bodyText = await clientPage.locator('body').textContent();
    expect(
      status === 403 ||
      url.includes('/login') ||
      url.includes('/client') ||
      !url.includes('/admin') ||
      (bodyText?.includes('403') ?? false) ||
      (bodyText?.includes('Accès refusé') ?? false) ||
      (bodyText?.includes('Forbidden') ?? false),
      `Un client ne devrait pas accéder à /admin (url=${url}, status=${status})`,
    ).toBeTruthy();
  });

  test('accès refusé aux owners', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/admin');
    const url = ownerPage.url();
    const status = response?.status() ?? 0;
    const bodyText = await ownerPage.locator('body').textContent();
    expect(
      status === 403 ||
      url.includes('/login') ||
      url.includes('/owner') ||
      !url.includes('/admin') ||
      (bodyText?.includes('403') ?? false) ||
      (bodyText?.includes('Accès refusé') ?? false) ||
      (bodyText?.includes('Forbidden') ?? false),
      `Un owner ne devrait pas accéder à /admin (url=${url}, status=${status})`,
    ).toBeTruthy();
  });

  test('admin accède au dashboard', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin');
    expect(response?.status()).toBe(200);
    // S'assurer qu'on n'est pas sur la page de login
    await expect(adminPage).not.toHaveURL(/admin\/login/);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
    await expect(adminPage.locator('body')).not.toContainText('Whoops, looks like');
  });

  test('dashboard affiche les widgets', async ({ adminPage }) => {
    await adminPage.goto('/admin');
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage).not.toHaveURL(/admin\/login/);
    await expect(adminPage.locator('body')).not.toContainText('Whoops');
    await expect(adminPage.locator('body')).not.toContainText('500');
  });
});

test.describe('Gestion des utilisateurs (Admin)', () => {
  test('liste des clients se charge', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/locataires');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('liste des propriétaires se charge', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/proprietaires');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('liste des administrateurs se charge', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/administrateurs');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Gestion des résidences (Admin)', () => {
  test('liste des résidences se charge', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/residences');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('page de modération des résidences', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/residence-moderation');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Gestion des réservations (Admin)', () => {
  test('liste des réservations se charge', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/bookings');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('liste des demandes de réservation', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/booking-requests');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Gestion des paiements (Admin)', () => {
  test('liste des paiements se charge', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/payments');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('liste des factures', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/invoices');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('liste des remboursements', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/refunds');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('liste des payouts', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/payouts');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Gestion des avis (Admin)', () => {
  test('liste des avis se charge', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/reviews');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Gestion des propriétaires (Admin)', () => {
  test('liste des balances propriétaires', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/owner-balances');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Modération (Admin)', () => {
  test('modération des photos', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/photo-moderation');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('liste des signalements de fraude', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/fraud-reports');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('liste des litiges', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/disputes');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('blacklist', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/blacklists');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Support (Admin)', () => {
  test('tickets de support', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/support-tickets');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('messages de contact', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/contacts');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Marketing (Admin)', () => {
  test('dashboard marketing', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/marketing-dashboard');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('campagnes', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/campaigns');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('coupons', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/coupons');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('codes promo', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/promo-codes');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('newsletter subscribers', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/newsletter-subscribers');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Statistiques & Paramètres (Admin)', () => {
  test('page statistiques', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/statistics-page');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('paramètres plateforme', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/platform-settings');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('dashboard sécurité', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/security-dashboard');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('journal activité admin', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/admin-activity-logs');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Contenu & SEO (Admin)', () => {
  test('gestion contenu pages', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/page-contents');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('données SEO', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/seo-datas');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('équipements', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/amenities');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });

  test('catégories', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/categories');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Vérifications (Admin)', () => {
  test('vérifications d\'identité', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/identity-verifications');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Notifications Admin', () => {
  test('page broadcast notifications', async ({ adminPage }) => {
    const response = await adminPage.goto('/admin/broadcast-notification');
    expect(response?.status()).toBe(200);
    await expect(adminPage.locator('body')).not.toContainText('ErrorException');
  });
});
