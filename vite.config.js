import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        // Forcer IPv4 pour éviter que le navigateur reçoive http://[::1]:port
        // qui est une source invalide dans les directives CSP.
        host: '127.0.0.1',
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/chart.js',
                'resources/js/leaflet.js',
            ],
            refresh: true,
        }),
    ],
});
