export function useSchoolLocale() {
  const auth = useAuth()
  const { locale, setLocale } = useI18n()
  const apiLocale = useState<string>('app.locale', () => locale.value)
  const supportedLocales = ['en', 'bn']

  watch(
    locale,
    (currentLocale) => {
      apiLocale.value = currentLocale
    },
    { immediate: true },
  )

  watch(
    () => auth.selectedSchool.value?.locale,
    (schoolLocale) => {
      if (schoolLocale && supportedLocales.includes(schoolLocale) && locale.value !== schoolLocale) {
        void setLocale(schoolLocale)
      }
    },
    { immediate: true },
  )

  return {
    locale,
    setLocale,
    supportedLocales,
  }
}
