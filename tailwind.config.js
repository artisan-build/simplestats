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
