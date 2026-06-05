import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', 'Fira Code', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                brand: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                    950: '#1e1b4b',
                },
                surface: {
                    light: '#ffffff',
                    dark: '#0f172a',
                },
            },
            boxShadow: {
                'glow': '0 0 20px rgba(99, 102, 241, 0.15)',
                'glow-lg': '0 0 40px rgba(99, 102, 241, 0.2)',
                'glow-green': '0 0 20px rgba(16, 185, 129, 0.15)',
                'glow-red': '0 0 20px rgba(239, 68, 68, 0.15)',
                'glow-amber': '0 0 20px rgba(245, 158, 11, 0.15)',
                'card': '0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06)',
                'card-hover': '0 10px 25px rgba(0, 0, 0, 0.08), 0 4px 10px rgba(0, 0, 0, 0.05)',
                'card-dark': '0 1px 3px rgba(0, 0, 0, 0.2), 0 1px 2px rgba(0, 0, 0, 0.3)',
                'card-dark-hover': '0 10px 25px rgba(0, 0, 0, 0.4), 0 4px 10px rgba(0, 0, 0, 0.3)',
                'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                'soft-lg': '0 10px 40px -10px rgba(0, 0, 0, 0.1), 0 2px 10px -2px rgba(0, 0, 0, 0.04)',
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.25rem',
                '4xl': '1.5rem',
            },
            animation: {
                'fade-in': 'fadeIn 0.3s ease-out',
                'fade-in-up': 'fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1)',
                'fade-in-down': 'fadeInDown 0.3s ease-out',
                'slide-in-right': 'slideInRight 0.3s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
                'pulse-soft': 'pulseSoft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'shimmer': 'shimmer 1.5s infinite',
                'spin-slow': 'spin 3s linear infinite',
                'float': 'float 3s ease-in-out infinite',
                'glow-pulse': 'glowPulse 2s ease-in-out infinite',
                'bounce-gentle': 'bounceGentle 2s infinite',
                'skeleton': 'skeleton 1.5s ease-in-out infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                fadeInUp: {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                fadeInDown: {
                    '0%': { opacity: '0', transform: 'translateY(-8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideInRight: {
                    '0%': { opacity: '0', transform: 'translateX(16px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                scaleIn: {
                    '0%': { opacity: '0', transform: 'scale(0.95)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                pulseSoft: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.7' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-6px)' },
                },
                glowPulse: {
                    '0%, 100%': { boxShadow: '0 0 20px rgba(99, 102, 241, 0.1)' },
                    '50%': { boxShadow: '0 0 30px rgba(99, 102, 241, 0.25)' },
                },
                bounceGentle: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-3px)' },
                },
                skeleton: {
                    '0%': { opacity: '0.6' },
                    '50%': { opacity: '1' },
                    '100%': { opacity: '0.6' },
                },
            },
            backdropBlur: {
                xs: '2px',
            },
            transitionTimingFunction: {
                'out-expo': 'cubic-bezier(0.16, 1, 0.3, 1)',
                'out-back': 'cubic-bezier(0.34, 1.56, 0.64, 1)',
            },
            transitionDuration: {
                '400': '400ms',
                '600': '600ms',
            },
        },
    },

    plugins: [forms],
};
