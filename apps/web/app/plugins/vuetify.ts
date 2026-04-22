import { createVuetify } from 'vuetify'
import type { ThemeDefinition } from 'vuetify'
import 'vuetify/styles'

const lightTheme: ThemeDefinition = {
  dark: false,
  colors: {
    primary: '#7367f0',
    'primary-darken-1': '#675dd8',
    secondary: '#808390',
    success: '#28c76f',
    info: '#00bad1',
    warning: '#ff9f43',
    error: '#ff4c51',
    background: '#f4f5fa',
    surface: '#ffffff',
    'surface-bright': '#ffffff',
    'surface-light': '#ffffff',
    'surface-variant': '#f8f7fa',
    'on-background': '#2f2b3d',
    'on-surface': '#2f2b3d',
    'on-primary': '#ffffff',
  },
  variables: {
    'border-color': '#2f2b3d',
    'border-opacity': 0.12,
    'high-emphasis-opacity': 0.9,
    'medium-emphasis-opacity': 0.7,
    'disabled-opacity': 0.4,
    'theme-kbd': '#f5f5f7',
    'theme-on-kbd': '#2f2b3d',
    'theme-code': '#f5f5f7',
    'theme-on-code': '#2f2b3d',
  },
}

export default defineNuxtPlugin((nuxtApp) => {
  const vuetify = createVuetify({
    ssr: false,
    theme: {
      defaultTheme: 'lightTheme',
      themes: {
        lightTheme,
      },
    },
    defaults: {
      VCard: {
        rounded: 'lg',
        elevation: 0,
      },
      VBtn: {
        rounded: 'lg',
        elevation: 0,
      },
      VTextField: {
        variant: 'outlined',
        density: 'comfortable',
        hideDetails: 'auto',
      },
      VSelect: {
        variant: 'outlined',
        density: 'comfortable',
        hideDetails: 'auto',
      },
      VTextarea: {
        variant: 'outlined',
        density: 'comfortable',
        hideDetails: 'auto',
      },
      VNavigationDrawer: {
        elevation: 0,
      },
      VAppBar: {
        elevation: 0,
      },
    },
  })

  nuxtApp.vueApp.use(vuetify)
})
