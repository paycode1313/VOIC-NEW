import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * Frontend VOIC (Vite + Tailwind). Hasil build ditulis ke `backend/public/build`
 * supaya Laravel di folder `backend/` bisa menyajikannya lewat @vite().
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ['src/css/app.css', 'src/js/app.js'],
            publicDirectory: '../backend/public',
            refresh: ['../backend/resources/views/**', '../backend/routes/**'],
        }),
    ],
    build: {
        emptyOutDir: true,
    },
    server: {
        fs: {
            // pronunciation.js membaca ../backend/ai_service/pronunciation.json
            allow: ['..'],
        },
    },
});
