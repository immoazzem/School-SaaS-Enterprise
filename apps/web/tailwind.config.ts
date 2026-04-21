import type { Config } from 'tailwindcss'

export default {
  content: [
    './app/**/*.{vue,ts,js}',
    './app.vue',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          DEFAULT: '#2563eb',
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
        cream: {
          DEFAULT: '#f7f3ef',
          50: '#fdfcfa',
          100: '#f7f3ef',
          200: '#ede5dc',
          300: '#ddd0c1',
        },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
        display: ['Lexend', 'Outfit', 'Inter', 'sans-serif'],
      },
      boxShadow: {
        surface: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
        'surface-hover': '0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05)',
        button: '0 1px 2px 0 rgba(17, 24, 39, 0.05)',
        'button-hover': '0 4px 6px -1px rgba(17, 24, 39, 0.1), 0 2px 4px -2px rgba(17, 24, 39, 0.1)',
      },
      borderRadius: {
        pill: '999px',
      },
    },
  },
  plugins: [],
} satisfies Config
