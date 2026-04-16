export default defineNuxtRouteMiddleware(async (to) => {
  if (import.meta.server) {
    return
  }

  const auth = useAuth()
  const isLoginRoute = to.path === '/'

  if (!auth.token.value) {
    if (isLoginRoute) {
      return
    }

    return navigateTo('/')
  }

  if (!auth.user.value) {
    try {
      await auth.refreshProfile()
      await auth.refreshSchools()
    } catch {
      auth.logout()

      if (!isLoginRoute) {
        return navigateTo('/')
      }

      return
    }
  }

  if (isLoginRoute) {
    return navigateTo('/dashboard')
  }
})
