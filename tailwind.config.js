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
                /* Single-name fallback inside var() so missing --flow-font-sans never invalidates the rule */
                sans: ['var(--flow-font-sans, ui-sans-serif)', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                flow: {
                    primary: 'var(--flow-primary)',
                    'primary-hover': 'var(--flow-primary-hover)',
                    secondary: 'var(--flow-secondary)',
                    surface: 'var(--flow-surface)',
                    'surface-muted': 'var(--flow-surface-muted)',
                    text: 'var(--flow-text)',
                    'text-muted': 'var(--flow-text-muted)',
                    border: 'var(--flow-border)',
                },
            },
            borderRadius: {
                flow: 'var(--flow-radius-lg)',
            },
            maxWidth: {
                '12xl': '96rem',
            },
        },
    },

    plugins: [forms],
};
