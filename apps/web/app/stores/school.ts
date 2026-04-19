import { defineStore } from 'pinia'
import type { ApiSchool } from '~/composables/useApi'

export const useSchoolStore = defineStore('school', () => {
  const schools = useState<ApiSchool[]>('auth.schools', () => [])
  const loading = ref(false)
  const error = ref<string | null>(null)

  function setSchools(list: ApiSchool[]) {
    schools.value = list
  }

  return {
    schools,
    loading,
    error,
    setSchools,
  }
})

