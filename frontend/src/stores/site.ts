import { computed } from 'vue'
import { defineStore } from 'pinia'
import { useHomePageStore } from '@/stores/homePage'

export const useSiteStore = defineStore('site', () => {
  const homePage = useHomePageStore()
  const announcement = computed(() => homePage.announcement)

  async function hydrate(): Promise<void> {
    await homePage.hydrate()
  }

  return { announcement, hydrate }
})
