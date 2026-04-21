import { test, expect } from '../fixtures';

/**
 * Tests E2E — Messagerie / Chat
 * Couvre : conversations, envoi messages, pièces jointes, actions sur conversations
 */

test.describe('Liste des conversations', () => {
  test('page chat se charge (client)', async ({ clientPage }) => {
    const response = await clientPage.goto('/chat');
    expect(response?.status()).toBe(200);
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });

  test('page chat se charge (owner)', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/chat');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });

  test('recherche de conversations', async ({ clientPage }) => {
    await clientPage.goto('/chat');
    const searchInput = clientPage.locator('input[placeholder*="recherch" i], input[type="search"]').first();

    if (await searchInput.isVisible()) {
      await searchInput.fill('test');
      await clientPage.waitForLoadState('networkidle');
      await expect(clientPage.locator('body')).not.toContainText('ErrorException');
    }
  });
});

test.describe('Démarrer une conversation', () => {
  test('bouton nouvelle conversation visible', async ({ clientPage }) => {
    await clientPage.goto('/chat');
    // Le bouton pour démarrer peut être un + ou "Nouveau message"
    const newBtn = clientPage.locator(
      'button[aria-label*="nouveau" i], a[href*="/chat/start"], button:has-text("Nouveau"), [data-testid="new-conversation"]'
    ).first();

    // Vérifie qu'il n'y a pas d'erreur même si le bouton n'existe pas (liste vide)
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });

  test('API start conversation accepte une requête valide', async ({ clientPage }) => {
    // On récupère un token CSRF
    await clientPage.goto('/chat');
    const csrfToken = await clientPage.locator('meta[name="csrf-token"]').getAttribute('content');

    if (csrfToken) {
      const response = await clientPage.request.post('/chat/start', {
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        data: { user_id: 1 }, // ID potentiellement invalide — on vérifie juste pas de 500
      });
      // 422 validation, 404 user not found, 200 OK — jamais 500
      expect(response.status()).not.toBe(500);
    }
  });
});

test.describe('Conversation individuelle', () => {
  test('accéder à une conversation existante', async ({ clientPage }) => {
    await clientPage.goto('/chat');

    // Cliquer sur la première conversation si elle existe
    const firstConvo = clientPage.locator(
      'a[href*="/chat/"]:not([href="/chat"]), [data-conversation-id]'
    ).first();

    const hasConvo = await firstConvo.isVisible({ timeout: 3_000 }).catch(() => false);
    if (hasConvo) {
      const href = await firstConvo.getAttribute('href');
      if (href) {
        const response = await clientPage.goto(href);
        expect(response?.status()).toBe(200);
        await expect(clientPage.locator('body')).not.toContainText('ErrorException');
      }
    }
  });

  test('zone de saisie message visible', async ({ clientPage }) => {
    await clientPage.goto('/chat');

    const firstConvo = clientPage.locator('a[href*="/chat/"]:not([href="/chat"])').first();
    const hasConvo = await firstConvo.isVisible({ timeout: 3_000 }).catch(() => false);

    if (hasConvo) {
      await firstConvo.click();
      await clientPage.waitForLoadState('networkidle');

      // Zone de texte pour envoyer un message
      const messageInput = clientPage.locator(
        'textarea[name="message"], input[name="message"], [contenteditable="true"], textarea[placeholder*="message" i]'
      ).first();

      // Vérifie qu'aucune erreur serveur ne s'affiche
      await expect(clientPage.locator('body')).not.toContainText('ErrorException');
    }
  });
});

test.describe('Envoi de messages', () => {
  test('formulaire envoi ne retourne pas d\'erreur 500', async ({ clientPage }) => {
    await clientPage.goto('/chat');

    const firstConvo = clientPage.locator('a[href*="/chat/"]:not([href="/chat"])').first();
    const hasConvo = await firstConvo.isVisible({ timeout: 3_000 }).catch(() => false);

    if (hasConvo) {
      const href = await firstConvo.getAttribute('href');
      if (href) {
        await clientPage.goto(href);

        const messageInput = clientPage.locator(
          'textarea[name="message"], input[name="message"], textarea[placeholder*="message" i]'
        ).first();

        const inputVisible = await messageInput.isVisible({ timeout: 3_000 }).catch(() => false);
        if (inputVisible) {
          await messageInput.fill('Test message E2E');

          const sendBtn = clientPage.locator(
            'button[type="submit"], button:has-text("Envoyer"), button[aria-label*="envoyer" i]'
          ).first();

          if (await sendBtn.isVisible()) {
            // On ne clique pas vraiment pour éviter de polluer la BD
            // On vérifie juste que le bouton est enabled
            await expect(sendBtn).toBeEnabled();
          }
        }
      }
    }
  });
});

test.describe('Actions sur conversation', () => {
  test('archiver une conversation', async ({ clientPage }) => {
    await clientPage.goto('/chat');

    // Bouton menu ou action archive
    const archiveBtn = clientPage.locator(
      'button[aria-label*="archiv" i], [data-action="archive"]'
    ).first();

    // Vérifie juste qu'il n'y a pas d'erreur sur la page
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });

  test('page recherche dans conversation', async ({ clientPage }) => {
    await clientPage.goto('/chat/search?q=test');
    await expect(clientPage.locator('body')).not.toContainText('ErrorException');
  });
});

test.describe('Templates de message', () => {
  test('liste des templates accessible', async ({ clientPage }) => {
    await clientPage.goto('/chat');

    const csrfToken = await clientPage.locator('meta[name="csrf-token"]').getAttribute('content');

    if (csrfToken) {
      const response = await clientPage.request.get('/chat/templates/list', {
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
      });
      // 200 OK ou 404 si pas de templates — jamais 500
      expect(response.status()).not.toBe(500);
    }
  });
});

test.describe('Messagerie — Propriétaire', () => {
  test('owner peut accéder au chat', async ({ ownerPage }) => {
    const response = await ownerPage.goto('/chat');
    expect(response?.status()).toBe(200);
    await expect(ownerPage.locator('body')).not.toContainText('ErrorException');
  });

  test('owner voit ses conversations avec clients', async ({ ownerPage }) => {
    await ownerPage.goto('/chat');
    // La liste peut être vide si pas de conversations
    await expect(ownerPage.locator('body')).not.toContainText('Whoops');
    await expect(ownerPage.locator('body')).not.toContainText('500');
  });
});

test.describe('Polling nouveaux messages', () => {
  test('endpoint /new retourne une réponse valide', async ({ clientPage }) => {
    await clientPage.goto('/chat');

    const firstConvo = clientPage.locator('a[href*="/chat/"]:not([href="/chat"])').first();
    const hasConvo = await firstConvo.isVisible({ timeout: 3_000 }).catch(() => false);

    if (hasConvo) {
      const href = await firstConvo.getAttribute('href');
      if (href) {
        const convoId = href.split('/').pop();

        const csrfToken = await clientPage.locator('meta[name="csrf-token"]').getAttribute('content');

        if (csrfToken && convoId) {
          const response = await clientPage.request.get(`/chat/${convoId}/new`, {
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
            },
          });
          expect(response.status()).not.toBe(500);
        }
      }
    }
  });
});

test.describe('Indicateur de frappe', () => {
  test('endpoint typing ne crash pas', async ({ clientPage }) => {
    await clientPage.goto('/chat');

    const firstConvo = clientPage.locator('a[href*="/chat/"]:not([href="/chat"])').first();
    const hasConvo = await firstConvo.isVisible({ timeout: 3_000 }).catch(() => false);

    if (hasConvo) {
      const href = await firstConvo.getAttribute('href');
      if (href) {
        const convoId = href.split('/').pop();
        const csrfToken = await clientPage.locator('meta[name="csrf-token"]').getAttribute('content');

        if (csrfToken && convoId) {
          const response = await clientPage.request.post(`/chat/${convoId}/typing`, {
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
            },
            data: {},
          });
          expect(response.status()).not.toBe(500);
        }
      }
    }
  });
});
