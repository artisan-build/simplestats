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
                    50:  '#f6f6f6',
                    100: '#e7e7e7',
                    200: '#d1d1d1',
                    300: '#b0b0b0',
                    400: '#888888',
                    500: '#6d6d6d',
                    600: '#3c3c3c',
                    700: '#282828',
                    800: '#212121',
                    900: '#333333',
                    950: '#292929'
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
