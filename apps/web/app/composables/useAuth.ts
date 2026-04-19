import { storeToRefs } from 'pinia'
import { useAuthStore } from '~/stores/auth'

export function useAuth() {
  const authStore = useAuthStore()
  const { token, user, schools, selectedSchoolId, selectedSchool, permissions } = storeToRefs(authStore)

  return {
    token,
    user,
    schools,
    selectedSchoolId,
    selectedSchool,
    permissions,
    can: authStore.can,
    login: authStore.login,
    refreshProfile: authStore.refreshProfile,
    refreshSchools: authStore.refreshSchools,
    createSchool: authStore.createSchool,
    selectSchool: authStore.selectSchool,
    logout: authStore.logout,
  }
}
