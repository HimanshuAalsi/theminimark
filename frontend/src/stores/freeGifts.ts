import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiFetch, getApiBaseUrl } from '@/lib/api'
import type { FreeGiftOption } from '@/lib/freeGift'
import { resolveProductImageUrl } from '@/lib/productImage'

function apiPrefix(): string {
  return getApiBaseUrl() ? '/v1' : '/api/v1'
}

export const useFreeGiftsStore = defineStore('freeGifts', () => {
  const configuredOptions = ref<FreeGiftOption[]>([])
  const ready = ref(false)

  async function ensureLoaded(): Promise<void> {
    if (ready.value) return
    try {
      const data = await apiFetch<{ options?: unknown[] }>(`${apiPrefix()}/site/free-gifts`)
      const opts = Array.isArray(data.options) ? data.options : []
      configuredOptions.value = opts.map((o) => {
        const row = o as Record<string, unknown>
        return {
          id: String(row.id),
          name: String(row.name),
          image: resolveProductImageUrl(String(row.image ?? '')),
        }
      })
    } catch {
      configuredOptions.value = []
    } finally {
      ready.value = true
    }
  }

  function invalidate(): void {
    ready.value = false
    configuredOptions.value = []
  }

  return { configuredOptions, ready, ensureLoaded, invalidate }
})
