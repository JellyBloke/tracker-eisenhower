import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/ts/app.ts',
                'resources/ts/auth.ts',
                'resources/ts/dashboard.ts',
                'resources/ts/focus.ts',
                'resources/ts/stats.ts',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/ts',
        },
    },
});
