export function useEnterpriseAccess() {
  const auth = useAuth()

  const canAccessEnterpriseAdmin = computed(() =>
    auth.schools.value.some(school => school.roles?.some(role => role.key === 'super-admin')),
  )

  return {
    canAccessEnterpriseAdmin,
  }
}
