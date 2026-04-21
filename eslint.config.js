// ESLint v9 flat config — https://eslint.org/docs/latest/use/configure/configuration-files
// Compatible avec ESLint v9 sans dépendances supplémentaires.
// Pour activer les règles TypeScript avancées : npm i -D typescript-eslint

export default [
  // ─── Ignores globaux ────────────────────────────────────────────────────
  {
    ignores: [
      'vendor/**',
      'public/build/**',
      'public/js/**',
      'node_modules/**',
      'storage/**',
      'bootstrap/cache/**',
      'tests/e2e/.auth/**',
      'tests/e2e/reports/**',
    ],
  },

  // ─── Fichiers JS frontend (Vite / Alpine) ───────────────────────────────
  {
    files: ['resources/js/**/*.{js,ts}'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: {
        window: 'readonly',
        document: 'readonly',
        console: 'readonly',
        fetch: 'readonly',
        navigator: 'readonly',
        setTimeout: 'readonly',
        clearTimeout: 'readonly',
        setInterval: 'readonly',
        clearInterval: 'readonly',
        localStorage: 'readonly',
        sessionStorage: 'readonly',
        URL: 'readonly',
        URLSearchParams: 'readonly',
        FormData: 'readonly',
        HTMLElement: 'readonly',
        Event: 'readonly',
        CustomEvent: 'readonly',
        IntersectionObserver: 'readonly',
        MutationObserver: 'readonly',
        ResizeObserver: 'readonly',
        requestAnimationFrame: 'readonly',
        cancelAnimationFrame: 'readonly',
        // Navigateur — globals manquants
        alert: 'readonly',
        confirm: 'readonly',
        location: 'writable',
        history: 'readonly',
        // Alpine.js
        Alpine: 'writable',
        // Chart.js (chargé via script tag ou CDN)
        Chart: 'readonly',
        // Mapbox GL (chargé via CDN)
        mapboxgl: 'readonly',
        L: 'readonly',
        // Google Maps (chargé via CDN)
        google: 'readonly',
        // Browser APIs
        Notification: 'readonly',
        MediaRecorder: 'readonly',
        Blob: 'readonly',
        Audio: 'readonly',
        FileReader: 'readonly',
        // Laravel / Vite globals
        import: 'readonly',
      },
    },
    rules: {
      // ── Erreurs potentielles ──
      'no-unused-vars': ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_', caughtErrorsIgnorePattern: '^_' }],
      'no-undef': 'warn',
      'no-duplicate-imports': 'error',

      // ── Bonnes pratiques ──
      'eqeqeq': ['error', 'always', { null: 'ignore' }],
      'no-var': 'error',
      'prefer-const': 'warn',
      'no-console': ['warn', { allow: ['warn', 'error'] }],

      // ── Style ──
      'semi': ['warn', 'always'],
      'quotes': ['warn', 'single', { avoidEscape: true }],
    },
  },

  // ─── Tests E2E Playwright (TypeScript) ──────────────────────────────────
  // NOTE: Les fichiers .ts nécessitent @typescript-eslint/parser pour être
  // parsés correctement. Ils sont donc ignorés par ESLint jusqu'à l'installation
  // de typescript-eslint (npm i -D typescript-eslint).
  {
    ignores: ['tests/e2e/**/*.ts', 'resources/js/**/*.ts'],
  },
];
