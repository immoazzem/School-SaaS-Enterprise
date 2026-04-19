import { useOnline } from '@vueuse/core'

export function useNetworkStatus() {
  const isOnline = useOnline()

  const statusLabel = computed(() => (isOnline.value ? 'Online' : 'Offline'))

  return {
    isOnline,
    statusLabel,
  }
}
