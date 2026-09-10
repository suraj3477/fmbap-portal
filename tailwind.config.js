import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                'none': '0px',
                'sm': '2px',
                DEFAULT: '3px',
                'md': '4px',
                'lg': '4px',
                'xl': '6px',
                '2xl': '6px',
                '3xl': '8px',
                'full': '4px', // Override oval rounded-full to crisp square-sharp radius
            },
        },
    },

    plugins: [forms],
};

