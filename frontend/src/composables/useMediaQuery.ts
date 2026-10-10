import { onMounted, onUnmounted, ref } from 'vue'

export function useMediaQuery(query: string) {
  const matches = ref(typeof window !== 'undefined' && window.matchMedia(query).matches)
  let media: MediaQueryList | null = null

  function update() {
    if (media) matches.value = media.matches
  }

  onMounted(() => {
    media = window.matchMedia(query)
    update()
    media.addEventListener('change', update)
  })

  onUnmounted(() => {
    media?.removeEventListener('change', update)
  })

  return matches
}
