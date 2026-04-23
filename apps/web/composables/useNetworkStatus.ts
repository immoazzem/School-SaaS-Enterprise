export function useNetworkStatus() {
  const online = useOnline()

  return {
    isOnline: computed(() => online.value),
  }
}
