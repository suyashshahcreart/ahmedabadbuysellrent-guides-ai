import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/scss/icons.scss',
                'resources/scss/app.scss',
                'resources/js/admin-config.js',
                'resources/js/admin-app.js',
                'resources/js/admin-layout.js',
            ],
            refresh: true,
        }),
    ],
});
