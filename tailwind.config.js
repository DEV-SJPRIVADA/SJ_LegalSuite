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
                sans: [
                    '"neue-haas-grotesk-display"',
                    'ui-sans-serif',
                    'system-ui',
                    ...defaultTheme.fontFamily.sans,
                ],
                display: [
                    '"neue-haas-grotesk-display"',
                    'ui-sans-serif',
                    'system-ui',
                    ...defaultTheme.fontFamily.sans,
                ],
            },
            colors: {
                /** Identidad corporativa SJSP */
                sj: {
                    blue: '#1e2743',
                    orange: '#f7a823',
                    white: '#ffffff',
                },
                dash: {
                    void: '#121628',
                    ink: '#1e2743',
                    lift: '#2a3354',
                    muted: '#8b93b3',
                },
            },
            boxShadow: {
                'dash-glow-cyan': '0 0 32px -8px rgba(34,211,238,0.35)',
                'dash-glow-fuchsia': '0 0 32px -8px rgba(217,70,239,0.35)',
                'dash-glow-orange': '0 0 28px -8px rgba(247,168,35,0.45)',
                'dash-card': '0 4px 40px -12px rgba(15,23,42,0.85)',
                'sj-glow': '0 0 28px -8px rgba(247,168,35,0.4)',
            },
        },
    },

    plugins: [forms],
};
