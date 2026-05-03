import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './config/dashboard.php',
    ],

    safelist: [
        // Paletas del dashboard financiero (config/dashboard.php).
        // Tailwind no podía detectarlas porque vienen interpoladas desde config.
        'bg-emerald-100', 'bg-emerald-400', 'bg-emerald-600', 'text-emerald-600',
        'bg-amber-100', 'bg-amber-400', 'bg-amber-600', 'text-amber-600',
        'bg-sky-100', 'bg-sky-400', 'bg-sky-600', 'text-sky-600',
        'bg-rose-100', 'bg-rose-400', 'bg-rose-600', 'text-rose-600',
    ],

    theme: {
        extend: {
            fontFamily: {
                roboto: ['Roboto', 'sans-serif'],
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [
        forms,
        // typography,
    ],
};
