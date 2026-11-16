import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// The web pages are built here (frontend/) and served by Laravel (../backend):
// the build and the dev-server "hot" file go into the backend's public folder.
export default defineConfig({
    plugins: [
        laravel({
            input: ['css/app.css', 'js/app.js'],
            publicDirectory: '../backend/public',
            buildDirectory: 'build',
            hotFile: '../backend/public/hot',
            refresh: ['views/**', '../backend/routes/**', '../backend/config/exam.php'],
        }),
    ],
    // The output folder (../backend/public/build) is outside this folder: Vite only clears it when asked.
    build: { emptyOutDir: true },
    server: {
        // js/pronunciation.js reads the lexicon shared with the AI service (../backend/ai_service).
        fs: { allow: ['..'] },
    },
});
