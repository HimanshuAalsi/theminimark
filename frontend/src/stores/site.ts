import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiFetch, getApiBaseUrl } from '@/lib/api'
import { announcement as defaultAnnouncement } from '@/data/siteContent'

function apiPrefix(): string {
  return getApiBaseUrl() ? '/v1' : '/api/v1'
}

export const useSiteStore = defineStore('site', () => {
  const announcement = ref(defaultAnnouncement)
  const hydrated = ref(false)

  async function hydrate(): Promise<void> {
    if (hydrated.value) return
    hydrated.value = true
    try {
      const data = await apiFetch<{ announcement?: string }>(`${apiPrefix()}/site`)
      if (typeof data.announcement === 'string' && data.announcement.trim() !== '') {
        announcement.value = data.announcement.trim()
      }
    } catch {
      /* keep bundled default when API is offline */
    }
  }

  return { announcement, hydrate }
})
