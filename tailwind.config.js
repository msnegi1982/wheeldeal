import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/View/Components/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#FFFBEB',
                    100: '#FEF3C7',
                    200: '#FDE68A',
                    300: '#FCD34D',
                    400: '#FBBF24',
                    500: '#FFC107',
                    600: '#F0B100',
                    700: '#D99A00',
                    800: '#B37A00',
                    900: '#8C5D00',
                },
                ink: {
                    DEFAULT: '#111111',
                    900: '#111111',
                    800: '#1C1C1E',
                    700: '#2A2A2E',
                    600: '#3F3F46',
                    500: '#52525B',
                    400: '#71717A',
                    300: '#A1A1AA',
                    200: '#D4D4D8',
                    100: '#F4F4F5',
                    50: '#FAFAFA',
                },
                sand: '#F7F3E8',
            },
            fontFamily: {
                sans: ['ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                display: ['ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
            },
            boxShadow: {
                card: '0 1px 2px rgba(17, 17, 17, 0.04), 0 12px 32px -16px rgba(17, 17, 17, 0.18)',
                lift: '0 2px 4px rgba(17, 17, 17, 0.05), 0 24px 48px -20px rgba(17, 17, 17, 0.25)',
                focus: '0 0 0 4px rgba(255, 193, 7, 0.35)',
            },
            maxWidth: {
                shell: '80rem',
            },
            spacing: {
                18: '4.5rem',
                22: '5.5rem',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                shimmer: {
                    '100%': { transform: 'translateX(100%)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 400ms ease-out both',
                shimmer: 'shimmer 1.4s infinite',
            },
        },
    },
    plugins: [forms({ strategy: 'class' })],
};
