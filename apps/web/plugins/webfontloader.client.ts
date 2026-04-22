/**
 * plugins/webfontloader.js
 *
 * webfontloader documentation: https://github.com/typekit/webfontloader
 */

export async function loadFonts() {
  const webFontLoader = await import(/* webpackChunkName: "webfontloader" */'webfontloader')

  webFontLoader.load({
    google: {
      families: [
        'Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap',
        'JetBrains+Mono:wght@400;500;600&display=swap',
      ],
    },
  })
}

export default defineNuxtPlugin(() => {
  loadFonts()
})
