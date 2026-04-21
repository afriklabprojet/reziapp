import { test as base, type Page, type BrowserContext } from '@playwright/test';
import * as path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// ─── Types ──────────────────────────────────────────────────────────────────

export type UserRole = 'client' | 'owner' | 'admin';

export interface ReziFixtures {
  clientPage: Page;
  ownerPage: Page;
  adminPage: Page;
}

// ─── Credentials ────────────────────────────────────────────────────────────

const CREDENTIALS: Record<UserRole, { email: string; password: string }> = {
  client: { email: 'e2e.client@rezi.test', password: 'password' },
  owner:  { email: 'e2e.owner@rezi.test',  password: 'password' },
  admin:  { email: 'e2e.admin@rezi.test',  password: 'password' },
};

// URL protégée utilisée pour vérifier la validité de la session par rôle
const PROTECTED_URL: Record<UserRole, string> = {
  client: '/client/dashboard',
  owner:  '/owner/dashboard',
  admin:  '/admin',
};

// URL de login par rôle (Filament a sa propre page de login)
const LOGIN_URL: Record<UserRole, string> = {
  client: '/login',
  owner:  '/login',
  admin:  '/admin/login',
};

// Texte du bouton de connexion selon le formulaire
const LOGIN_BUTTON: Record<UserRole, string | RegExp> = {
  client: 'Se connecter',
  owner:  'Se connecter',
  admin:  /connexion|sign in/i,
};

// Pattern d'URL après login réussi
const POST_LOGIN_URL: Record<UserRole, (pathname: string) => boolean> = {
  client: (p) => p.includes('/client') || p === '/',
  owner:  (p) => p.includes('/owner') || p === '/',
  admin:  (p) => p.startsWith('/admin') && !p.includes('/login'),
};

// ─── Helpers ────────────────────────────────────────────────────────────────

const storageStatePath = (role: UserRole) =>
  path.join(__dirname, `.auth/${role}.json`);

async function newAuthContext(
  browser: import('@playwright/test').Browser,
  role: UserRole,
): Promise<BrowserContext> {
  return browser.newContext({ storageState: storageStatePath(role) });
}

/**
 * Crée un contexte authentifié. Si la session stockée est périmée, se reconnecte
 * automatiquement via le bon formulaire (Breeze pour client/owner, Filament pour admin).
 */
async function newAuthPage(
  browser: import('@playwright/test').Browser,
  role: UserRole,
): Promise<{ page: Page; context: BrowserContext }> {
  const context = await newAuthContext(browser, role);
  const page = await context.newPage();

  // Vérifier que la session est encore valide
  await page.goto(PROTECTED_URL[role], { waitUntil: 'domcontentloaded' });

  const currentUrl = page.url();
  const isOnLogin = currentUrl.includes(LOGIN_URL[role]) ||
    (role === 'admin' && currentUrl.includes('/admin/login'));

  if (isOnLogin) {
    // Session expirée / invalidée — se reconnecter via le bon formulaire
    const loginUrl = LOGIN_URL[role];
    if (!currentUrl.includes(loginUrl)) {
      await page.goto(loginUrl);
    }
    await page.waitForLoadState('networkidle');
    const { email, password } = CREDENTIALS[role];
    // Filament utilise Livewire — inputs name="data.email", on utilise type='email'/'password'
    const emailInput = role === 'admin'
      ? page.locator('input[type="email"]')
      : page.locator('input[name="email"]');
    const passwordInput = role === 'admin'
      ? page.locator('input[type="password"]')
      : page.locator('input[name="password"]');
    await emailInput.waitFor({ state: 'visible', timeout: 10_000 });
    await emailInput.fill(email);
    await passwordInput.fill(password);
    await page.getByRole('button', { name: LOGIN_BUTTON[role] }).click();
    const urlCheck = POST_LOGIN_URL[role];
    await page.waitForURL((url) => urlCheck(url.pathname), { timeout: 15_000 });
  }

  return { page, context };
}

// ─── Fixtures Playwright ────────────────────────────────────────────────────

export const test = base.extend<ReziFixtures>({
  clientPage: async ({ browser }, use) => {
    const { page, context } = await newAuthPage(browser, 'client');
    await use(page);
    await context.close();
  },

  ownerPage: async ({ browser }, use) => {
    const { page, context } = await newAuthPage(browser, 'owner');
    await use(page);
    await context.close();
  },

  adminPage: async ({ browser }, use) => {
    const { page, context } = await newAuthPage(browser, 'admin');
    await use(page);
    await context.close();
  },
});

export { expect } from '@playwright/test';
