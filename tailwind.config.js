import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', 'var(--flow-font-sans)', ...defaultTheme.fontFamily.sans],
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
        },
    },

    plugins: [forms],
};
