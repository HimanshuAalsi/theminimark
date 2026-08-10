import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import type { SiteProduct } from '@/data/siteContent'
import { useCatalogStore } from '@/stores/catalog'

const RECENT_KEY = 'tm_recent_searches'
const MAX_RECENT = 8

function loadRecent(): string[] {
  try {
    const raw = localStorage.getItem(RECENT_KEY)
    const parsed = raw ? (JSON.parse(raw) as unknown) : []
    return Array.isArray(parsed) ? parsed.filter((s) => typeof s === 'string').slice(0, MAX_RECENT) : []
  } catch {
    return []
  }
}

function saveRecent(list: string[]) {
  localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, MAX_RECENT)))
}

export const useSearchUiStore = defineStore('searchUi', () => {
  const panelOpen = ref(false)
  const query = ref('')
  const recent = ref<string[]>(loadRecent())

  function open(initial = '') {
    query.value = initial
    panelOpen.value = true
    void useCatalogStore().ensureLoaded()
  }

  function close() {
    panelOpen.value = false
  }

  function addRecent(term: string) {
    const t = term.trim()
    if (!t) return
    const next = [t, ...recent.value.filter((r) => r.toLowerCase() !== t.toLowerCase())].slice(0, MAX_RECENT)
    recent.value = next
    saveRecent(next)
  }

  function clearRecent() {
    recent.value = []
    localStorage.removeItem(RECENT_KEY)
  }

  function removeRecent(term: string) {
    recent.value = recent.value.filter((r) => r !== term)
    saveRecent(recent.value)
  }

  const suggestions = computed((): SiteProduct[] => {
    const catalog = useCatalogStore().catalog
    const q = query.value.trim().toLowerCase()
    if (!q || q.length < 2) return []
    return catalog
      .filter(
        (p) =>
          p.name.toLowerCase().includes(q) ||
          p.slug.toLowerCase().includes(q) ||
          p.category.toLowerCase().includes(q),
      )
      .slice(0, 6)
  })

  return {
    panelOpen,
    query,
    recent,
    suggestions,
    open,
    close,
    addRecent,
    clearRecent,
    removeRecent,
  }
})
