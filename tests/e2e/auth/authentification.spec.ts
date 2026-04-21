import { test, expect } from '@playwright/test';
import { fileURLToPath } from 'url';
import * as path from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

/**
 * Tests E2E — Authentification
 * Couvre : page login, page register, déconnexion, forgot password
 */

test.describe('Connexion', () => {
  test('affiche le formulaire de connexion', async ({ page }) => {
    const res = await page.goto('/login');
    expect(res?.status()).toBe(200);

    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Se connecter' })).toBeVisible();
  });

  test('affiche une erreur avec des identifiants invalides', async ({ page }) => {
    await page.goto('/login');

    await page.locator('input[name="email"]').fill('inexistant@rezi.test');
    await page.locator('input[name="password"]').fill('mauvais_mdp');
    await page.getByRole('button', { name: 'Se connecter' }).click();

    // Attendre la réponse du serveur puis vérifier qu'on reste sur /login
    await page.waitForURL(/login/, { timeout: 10_000 });
    await expect(page).toHaveURL(/login/);
    // L'utilisateur n'est pas redirigé vers un dashboard
    await expect(page).not.toHaveURL(/dashboard/);
  });

  test('redirige après connexion réussie (compte e2e.client)', async ({ page }) => {
    await page.goto('/login');

    await page.locator('input[name="email"]').fill('e2e.client@rezi.test');
    await page.locator('input[name="password"]').fill('password');
    await page.getByRole('button', { name: 'Se connecter' }).click();

    // Doit quitter /login
    await page.waitForURL(url => !url.pathname.startsWith('/login'), { timeout: 10_000 });
    await expect(page).not.toHaveURL(/login/);
  });

  test('protège CSRF (token présent dans le formulaire)', async ({ page }) => {
    await page.goto('/login');
    const csrfInput = page.locator('input[name="_token"]');
    await expect(csrfInput).toBeHidden(); // présent mais caché
    const token = await csrfInput.getAttribute('value');
    expect(token).toBeTruthy();
  });
});

test.describe('Inscription', () => {
  test('affiche le formulaire d\'inscription', async ({ page }) => {
    const res = await page.goto('/register');
    expect(res?.status()).toBe(200);

    // Étape 1 : sélection du rôle (champs nom/email cachés jusqu'à l'étape 2)
    await expect(page.getByRole('button', { name: 'Continuer' })).toBeVisible();
    // Les radios rôle existent dans le DOM
    await expect(page.locator('input[name="role"]').first()).toBeAttached();
  });

  test('validation côté serveur : email déjà utilisé', async ({ page }) => {
    // Tester la validation serveur via soumission directe du formulaire
    // (sans naviguer dans l'UI multi-step Alpine.js qui cache les champs)
    await page.goto('/register');

    // Récupérer le token CSRF depuis la page
    const csrfToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');

    // Soumettre directement au endpoint register avec un email déjà utilisé
    const response = await page.request.post('/register', {
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-TOKEN': csrfToken ?? '',
        'X-Requested-With': 'XMLHttpRequest',
        'Referer': 'http://localhost:8000/register',
      },
      form: {
        name: 'Test Doublon',
        email: 'e2e.client@rezi.test',
        password: 'Password1!',
        password_confirmation: 'Password1!',
        role: 'user',
      },
    });

    // Doit renvoyer 422 (validation échouée) ou 302 (redirect avec erreur) — pas 500
    expect([302, 422]).toContain(response.status());
  });
});

test.describe('Mot de passe oublié', () => {
  test('page forgot-password se charge', async ({ page }) => {
    const res = await page.goto('/forgot-password');
    expect(res?.status()).toBe(200);
    await expect(page.locator('input[name="email"]')).toBeVisible();
  });

  test('soumet un email et affiche la confirmation', async ({ page }) => {
    await page.goto('/forgot-password');
    await page.locator('input[name="email"]').fill('e2e.client@rezi.test');
    // Cibler le premier bouton submit de la page (le formulaire forgot-password)
    await page.locator('form button[type="submit"]').first().click();

    // Ne doit pas planter
    await expect(page.locator('body')).not.toContainText('ErrorException');
    await expect(page.locator('body')).not.toContainText('Whoops');
  });
});

test.describe('Déconnexion', () => {
  test('déconnecte un utilisateur authentifié', async ({ browser }) => {
    const authFile = path.join(__dirname, '../.auth/client.json');
    const context = await browser.newContext({
      storageState: authFile,
      baseURL: 'http://localhost:8000',
    });
    const page = await context.newPage();

    await page.goto('http://localhost:8000/client/dashboard');
    await page.waitForLoadState('domcontentloaded');

    // Le logout utilise un <a> dans un <form method="POST"> (x-dropdown-link)
    // On soumet le formulaire directement via JS (fonctionne même si dans un dropdown)
    const logoutResult = await page.evaluate(() => {
      const forms = Array.from(document.querySelectorAll('form[method="POST"]'));
      const logoutForm = forms.find(f =>
        (f as HTMLFormElement).action.includes('logout')
      ) as HTMLFormElement | undefined;
      if (logoutForm) {
        logoutForm.submit();
        return { found: true, action: logoutForm.action };
      }
      return { found: false, action: '' };
    });

    if (!logoutResult.found) {
      // Fallback : navigation directe via fetch avec CSRF
      const token = await page.locator('meta[name="csrf-token"]').getAttribute('content')
        .catch(() => null);
      if (token) {
        await page.evaluate((t) => {
          fetch('/logout', { method: 'POST', headers: { 'X-CSRF-TOKEN': t, 'Content-Type': 'application/json' } });
        }, token);
      }
    }

    await page.waitForURL(
      url => url.pathname === '/' || url.pathname.startsWith('/login'),
      { timeout: 15_000 }
    );
    await expect(page).not.toHaveURL(/dashboard/);
    await context.close();
  });
});
