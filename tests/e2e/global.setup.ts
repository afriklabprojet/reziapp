import { test as setup } from '@playwright/test';
import type { Browser } from '@playwright/test';
import { execSync } from 'child_process';
import * as path from 'path';
import * as fs from 'fs';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const STORAGE_DIR = path.join(__dirname, '.auth');

/**
 * Helper — se connecte via Breeze (/login) et sauvegarde le storageState.
 * Utilisé pour les rôles client et owner.
 */
async function saveAuthStateBreeeze(
  browser: Browser,
  email: string,
  password: string,
  filePath: string,
  expectedUrlContains: string,
): Promise<void> {
  const context = await browser.newContext();
  const page = await context.newPage();

  await page.goto('/login');
  // Attendre que le formulaire soit prêt
  await page.locator('input[name="email"]').waitFor({ state: 'visible', timeout: 15_000 });
  await page.locator('input[name="email"]').fill(email);
  await page.locator('input[name="password"]').fill(password);
  await page.getByRole('button', { name: 'Se connecter' }).click();
  // Attendre la redirection finale (pas de |\/ pour éviter une résolution prématurée)
  await page.waitForURL(
    (url) => url.pathname.includes(expectedUrlContains) || url.pathname === '/',
    { timeout: 20_000 },
  );

  await context.storageState({ path: filePath });
  await context.close();
}

/**
 * Helper — se connecte via Filament (/admin/login) et sauvegarde le storageState.
 * Utilisé pour le rôle admin afin d'obtenir une session valide pour le panel Filament.
 */
async function saveAdminAuthState(
  browser: Browser,
  email: string,
  password: string,
  filePath: string,
): Promise<void> {
  const context = await browser.newContext();
  const page = await context.newPage();

  await page.goto('/admin/login');
  // Attendre que le formulaire soit visible (networkidle bloque sur Filament/Livewire)
  // Filament 3 utilise Livewire — les inputs ont name="data.email" (pas "email")
  // On utilise input[type="email"] et input[type="password"] qui sont stables
  await page.locator('input[type="email"]').waitFor({ state: 'visible', timeout: 30_000 });

  // Filament login form — les selectors de Filament 3
  await page.locator('input[type="email"]').fill(email);
  await page.locator('input[type="password"]').fill(password);
  await page.getByRole('button', { name: /connexion|sign in/i }).click();

  // Attendre la redirection vers le dashboard admin (pas la page de login)
  await page.waitForURL(
    (url) => url.pathname.startsWith('/admin') && !url.pathname.includes('/login'),
    { timeout: 20_000 },
  );

  await context.storageState({ path: filePath });
  await context.close();
}

/**
 * Seed la base de données de test et génère les états d'authentification
 * stockés sur disque pour être réutilisés dans chaque test.
 *
 * Comptes créés :
 *  - client  : e2e.client@rezi.test / password
 *  - owner   : e2e.owner@rezi.test  / password
 *  - admin   : e2e.admin@rezi.test  / password
 */
setup('seed database & store auth states', async ({ browser }) => {
  // S'assurer que le dossier .auth existe
  if (!fs.existsSync(STORAGE_DIR)) {
    fs.mkdirSync(STORAGE_DIR, { recursive: true });
  }

  // ─── 1. Lancer le seeder de test via Artisan ────────────────────────────
  const artisan = process.env.PHP_BIN ?? 'php';
  const root = path.resolve(__dirname, '../..');

  try {
    execSync(`${artisan} artisan e2e:seed`, { cwd: root, stdio: 'pipe' });
  } catch (e) {
    console.warn('[setup] e2e:seed non disponible — les fixtures doivent exister en BDD');
  }

  // ─── 2–3. Client et Owner via Breeze (/login) ──────────────────────────
  await saveAuthStateBreeeze(
    browser, 'e2e.client@rezi.test', 'password',
    path.join(STORAGE_DIR, 'client.json'),
    '/client',
  );

  await saveAuthStateBreeeze(
    browser, 'e2e.owner@rezi.test', 'password',
    path.join(STORAGE_DIR, 'owner.json'),
    '/owner',
  );

  // ─── 4. Admin via Filament (/admin/login) ───────────────────────────────
  // Le panel Filament gère sa propre session — connexion directe nécessaire.
  await saveAdminAuthState(
    browser, 'e2e.admin@rezi.test', 'password',
    path.join(STORAGE_DIR, 'admin.json'),
  );

  console.log('[setup] ✅ Auth states saved for client / owner / admin');
});
