import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                dash: {
                    void: '#070814',
                    ink: '#0c1024',
                    lift: '#141832',
                    muted: '#8b93b3',
                },
            },
            boxShadow: {
                'dash-glow-cyan': '0 0 32px -8px rgba(34,211,238,0.35)',
                'dash-glow-fuchsia': '0 0 32px -8px rgba(217,70,239,0.35)',
                'dash-glow-orange': '0 0 28px -8px rgba(251,146,60,0.4)',
                'dash-card': '0 4px 40px -12px rgba(15,23,42,0.85)',
            },
        },
    },

    plugins: [forms],
};
