import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import animate from 'tailwindcss-animate';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,ts,jsx,tsx,vue}',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"DM Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Primary Palette
                cream: '#F4F0E4',
                teal: {
                    light: '#D0EDEA',
                    DEFAULT: '#44A194',
                    dark: '#2D7A6F',
                    deeper: '#1D5A52',
                },
                steel: {
                    light: '#D6E3EC',
                    DEFAULT: '#537D96',
                    dark: '#3A5E74',
                },
                coral: {
                    light: '#FAEAEA',
                    DEFAULT: '#EC8F8D',
                    dark: '#D4615F',
                },
                // Semantic Colors
                success: {
                    light: '#EBF5EB',
                    DEFAULT: '#107C10',
                },
                warning: {
                    light: '#FFF4EC',
                    DEFAULT: '#CA5010',
                },
                // Neutrals
                surface: {
                    DEFAULT: '#FFFFFF',
                    alt: '#EDEAE0',
                },
                text: {
                    primary: '#1E2A35',
                    muted: '#6B7A8D',
                },
                border: {
                    DEFAULT: '#D9D3C5',
                    strong: '#B8B0A0',
                },
            },
        },
    },
    plugins: [forms, animate],
};
