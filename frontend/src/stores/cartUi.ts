import { ref, watch } from 'vue'
import { defineStore } from 'pinia'

export const useCartUiStore = defineStore('cartUi', () => {
  const isOpen = ref(false)

  function open() {
    isOpen.value = true
  }

  function close() {
    isOpen.value = false
  }

  function toggle() {
    isOpen.value = !isOpen.value
  }

  watch(isOpen, (open) => {
    if (typeof document === 'undefined') return
    document.body.style.overflow = open ? 'hidden' : ''
  })

  return { isOpen, open, close, toggle }
})
