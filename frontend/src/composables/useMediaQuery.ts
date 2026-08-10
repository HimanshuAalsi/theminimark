import { onMounted, onUnmounted, ref, type Ref } from 'vue'

export function useMediaQuery(query: string): Ref<boolean> {
  const matches = ref(false)
  let mq: MediaQueryList | null = null

  function update() {
    if (mq) matches.value = mq.matches
  }

  onMounted(() => {
    mq = window.matchMedia(query)
    update()
    mq.addEventListener('change', update)
  })

  onUnmounted(() => {
    mq?.removeEventListener('change', update)
  })

  return matches
}

export function useIsMobileApp(): Ref<boolean> {
  return useMediaQuery('(max-width: 1023px)')
}
