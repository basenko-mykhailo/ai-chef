import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // Figma design system: Inter (UI), Oswald (display), Playfair Display SC (wordmark)
                bunny('Inter', {
                    weights: [400, 500, 600, 700],
                }),
                bunny('Oswald', {
                    weights: [500, 600, 700],
                }),
                bunny('Playfair Display SC', {
                    weights: [400],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
