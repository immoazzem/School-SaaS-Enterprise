export default defineNuxtRouteMiddleware(async to => {
  const session = useSession()

  if (to.meta.public) {
    if (to.path === '/login' && session.token.value) {
      if (!session.user.value && !session.loading.value) {
        try {
          await session.fetchMe()
        }
        catch {
          return
        }
      }

      if (session.user.value)
        return navigateTo('/')
    }

    return
  }

  if (!session.token.value)
    return navigateTo('/login')

  if (!session.user.value && !session.loading.value) {
    try {
      await session.fetchMe()
    }
    catch {
      return navigateTo('/login')
    }
  }
})
