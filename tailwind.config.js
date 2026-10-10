import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                navy: {
                    50: '#f0f4f9',
                    100: '#d9e2ed',
                    200: '#b8c9dd',
                    300: '#8ea9c7',
                    400: '#6488b0',
                    500: '#466c98',
                    600: '#34537d',
                    700: '#284063',
                    800: '#1b2d47',
                    900: '#0f1c2f',
                    950: '#080f1b',
                },
                brand: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                    950: '#0f172a',
                },
            },
            boxShadow: {
                'xs': '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                'card': '0 4px 20px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.04)',
                'card-hover': '0 12px 30px -4px rgba(37, 99, 235, 0.12), 0 4px 12px -2px rgba(15, 23, 42, 0.06)',
                'glow-blue': '0 0 25px -4px rgba(37, 99, 235, 0.35)',
                'glow-emerald': '0 0 25px -4px rgba(16, 185, 129, 0.35)',
            },
        },
    },

    plugins: [forms],
};
