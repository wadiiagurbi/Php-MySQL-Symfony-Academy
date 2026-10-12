import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'

// Single locked accent for the whole product.
const ACCENT = '#4F46E5'

export default createVuetify({
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        dark: false,
        colors: {
          primary: ACCENT,
          'primary-darken-1': '#4338CA',
          secondary: '#64748B',
          background: '#F6F7F9',
          surface: '#FCFCFD',
          'surface-variant': '#E9EBF0',
          'surface-bright': '#FCFCFD',
          'on-background': '#18181B',
          'on-surface': '#18181B',
          'on-primary': '#F5F5F7',
          success: '#15803D',
          warning: '#B45309',
          error: '#B91C1C',
          info: '#1D4ED8',
        },
      },
      dark: {
        dark: true,
        colors: {
          primary: ACCENT,
          'primary-darken-1': '#4338CA',
          secondary: '#94A3B8',
          background: '#131316',
          surface: '#1D1D22',
          'surface-variant': '#2A2A32',
          'surface-bright': '#26262E',
          'on-background': '#F4F4F5',
          'on-surface': '#F4F4F5',
          'on-primary': '#F5F5F7',
          success: '#4ADE80',
          warning: '#FBBF24',
          error: '#F87171',
          info: '#93C5FD',
        },
      },
    },
  },
  defaults: {
    // Single corner-radius scale: 12px (xl) for cards, 8px (lg) for buttons.
    VCard: { rounded: 'xl' },
    VBtn: { rounded: 'lg' },
  },
})
