import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        // Helpers/composables build HTML strings with Tailwind classes
        // (e.g. simpleMarkdown.js, TooltipHelpers.js).
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            zIndex: {
                '100': '100',
            },
            screens: {
                'xs': '400px',
                '3xl': '1920px',
                '4xl': '2160px',
                '5xl': '2560px',
            },
            // Tailwind only ships max-w-screen-* for the default breakpoints (sm..2xl),
            // so the custom screens above need matching maxWidth tokens to be usable
            // as max-w-screen-3xl/4xl/5xl.
            maxWidth: {
                'screen-3xl': '1920px',
                'screen-4xl': '2160px',
                'screen-5xl': '2560px',
            },
            colors: {
                dark: {
                    50:  '#fbfbfb',
                    100: '#f6f6f6',
                    200: '#e7e7e7',
                    300: '#d6d6d6',
                    400: '#a6a6a6',
                    500: '#767676',
                    600: '#555555',
                    700: '#434343',
                    800: '#292929',
                    900: '#1a1a1a',
                    950: '#1a1a1a80',
                }
            }
        },
    },

    plugins: [
        forms,
        typography,
        require('@tailwindcss/container-queries'),
    ],
};
