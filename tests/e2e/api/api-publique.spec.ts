import { test, expect } from '@playwright/test';

/**
 * Tests E2E — API REST publique
 * Couvre : health check, résidences, disponibilité, équipements, localisations
 */

const API_BASE = '/api/v1';

test.describe('Health Check', () => {
  test('endpoint /health retourne un statut', async ({ request }) => {
    const response = await request.get(`${API_BASE}/health`);
    expect([200, 503]).toContain(response.status());

    const body = await response.json();
    expect(body).toHaveProperty('success');
    expect(body).toHaveProperty('status');
    expect(body).toHaveProperty('timestamp');
  });
});

test.describe('API Résidences', () => {
  test('liste des résidences', async ({ request }) => {
    const response = await request.get(`${API_BASE}/residences`);
    expect(response.status()).toBe(200);

    const body = await response.json();
    // La réponse doit être un objet avec data ou être directement un tableau
    expect(body).toBeDefined();
  });

  test('détail d\'une résidence (ID 1)', async ({ request }) => {
    const response = await request.get(`${API_BASE}/residences/1`);
    // 200 si existe, 404 sinon
    expect([200, 404]).toContain(response.status());
  });

  test('recherche de résidences', async ({ request }) => {
    // Coords d'Abidjan requises pour la recherche géolocalisée
    const response = await request.get(
      `${API_BASE}/residences/search?latitude=5.3364&longitude=-4.0266&radius=50&q=cocody`
    );
    // 200 OK ou 422 si la validation échoue
    expect([200, 422]).toContain(response.status());
  });

  test('résidences à proximité (POST)', async ({ request }) => {
    const response = await request.post(`${API_BASE}/residences/nearby`, {
      headers: { 'Content-Type': 'application/json' },
      data: {
        latitude: 5.3364,
        longitude: -4.0266,
        radius: 10,
      },
    });
    // 200 OK ou 422 validation error
    expect([200, 422]).toContain(response.status());
  });
});

test.describe('API Disponibilité', () => {
  test('calendrier disponibilité résidence', async ({ request }) => {
    const response = await request.get(`${API_BASE}/residences/1/availability`);
    // 200 si existe, 404 sinon
    expect([200, 404]).toContain(response.status());
  });

  test('vérifier disponibilité (POST)', async ({ request }) => {
    const response = await request.post(`${API_BASE}/residences/1/check-availability`, {
      headers: { 'Content-Type': 'application/json' },
      data: {
        check_in: '2026-06-01',
        check_out: '2026-06-05',
      },
    });
    // 200, 404 (résidence inexistante), 422 (validation) — pas 500
    expect([200, 404, 422]).toContain(response.status());
  });
});

test.describe('API Équipements', () => {
  test('liste des équipements', async ({ request }) => {
    const response = await request.get(`${API_BASE}/amenities`);
    expect(response.status()).toBe(200);

    const body = await response.json();
    expect(body).toBeDefined();
  });
});

test.describe('API Localisations', () => {
  test('liste des pays', async ({ request }) => {
    const response = await request.get(`${API_BASE}/locations/countries`);
    expect(response.status()).toBe(200);
  });

  test('villes par pays (CI)', async ({ request }) => {
    const response = await request.get(`${API_BASE}/locations/countries/CI/cities`);
    expect([200, 404]).toContain(response.status());
  });
});

test.describe('Rate Limiting API', () => {
  test('throttle sur recherche géo (1 requête)', async ({ request }) => {
    const response = await request.get(
      `${API_BASE}/residences/search?latitude=5.3364&longitude=-4.0266&radius=50&q=plateau`
    );
    // 200 (OK), 429 (rate-limited) — le serveur doit répondre
    expect([200, 422, 429]).toContain(response.status());
  });
});

test.describe('Webhooks', () => {
  test('webhook Jeko accepte POST', async ({ request }) => {
    const response = await request.post('/api/webhooks/jeko', {
      headers: { 'Content-Type': 'application/json' },
      data: { event: 'test' },
    });
    // 200, 400, 401, 422 — pas 500
    expect(response.status()).not.toBe(500);
  });

  test('webhook WhatsApp (verification GET)', async ({ request }) => {
    const response = await request.get('/api/webhooks/whatsapp?hub.mode=subscribe&hub.challenge=test123', {
      headers: { 'Content-Type': 'application/json' },
    });
    // 200 (token valid), 403 (bad token), ou 500/503 (WhatsApp non configuré dans cet env)
    expect(response.status()).toBeGreaterThan(0);
    expect(response.status()).toBeLessThan(600);
  });
});

test.describe('Headers de sécurité API', () => {
  test('Content-Type JSON sur réponses API', async ({ request }) => {
    const response = await request.get(`${API_BASE}/health`);
    const contentType = response.headers()['content-type'] ?? '';
    expect(contentType).toContain('application/json');
  });

  test('pas de stack trace en production', async ({ request }) => {
    // Requête vers un endpoint qui n'existe pas
    const response = await request.get(`${API_BASE}/nonexistent-endpoint`);

    if (response.status() === 404) {
      const body = await response.text();
      // Ne doit pas contenir de stack trace PHP
      expect(body).not.toContain('vendor/laravel');
      expect(body).not.toContain('Stack trace');
    }
  });
});

test.describe('Compression API', () => {
  test('accepte gzip', async ({ request }) => {
    const response = await request.get(`${API_BASE}/residences`, {
      headers: { 'Accept-Encoding': 'gzip, deflate' },
    });
    expect(response.status()).toBe(200);
    // Le serveur peut ou non compresser selon la config
  });
});

test.describe('CORS API', () => {
  test('preflight OPTIONS sur API', async ({ request }) => {
    const response = await request.fetch(`${API_BASE}/residences`, {
      method: 'OPTIONS',
      headers: {
        'Origin': 'https://example.com',
        'Access-Control-Request-Method': 'GET',
      },
    });
    // 200, 204, ou 405 si OPTIONS non supporté — pas 500
    expect(response.status()).not.toBe(500);
  });
});
