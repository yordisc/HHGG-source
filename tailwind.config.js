import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#f0f9ff',
                    600: '#0284c7',
                    700: '#0369a1',
                },
                secondary: {
                    50: '#faf5ff',
                    600: '#a855f7',
                    700: '#9333ea',
                },
            },
            spacing: {
                xs: '0.5rem',
                sm: '1rem',
                md: '1.5rem',
                lg: '2rem',
                xl: '2.5rem',
            },
            borderRadius: {
                DEFAULT: '0.5rem',
                lg: '0.75rem',
                xl: '1rem',
            },
            boxShadow: {
                DEFAULT: '0 1px 3px 0 rgba(0, 0, 0, 0.1)',
                lg: '0 10px 15px -3px rgba(0, 0, 0, 0.1)',
            },
            transitionDuration: {
                DEFAULT: '300ms',
            },
        },
    },
    plugins: [],
};
