import { computed, ref } from 'vue'

export interface AdminListMeta {
  total: number
  page: number
  perPage: number
  count?: number
}

export function useAdminList(defaultPerPage = 25) {
  const page = ref(1)
  const perPage = ref(defaultPerPage)
  const total = ref(0)
  const busy = ref(false)
  const error = ref('')

  const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)))
  const hasPrev = computed(() => page.value > 1)
  const hasNext = computed(() => page.value < totalPages.value)
  const rangeLabel = computed(() => {
    if (total.value === 0) return '0 results'
    const start = (page.value - 1) * perPage.value + 1
    const end = Math.min(page.value * perPage.value, total.value)
    return `${start}–${end} of ${total.value}`
  })

  function setMeta(meta: AdminListMeta) {
    total.value = meta.total
    page.value = meta.page
    perPage.value = meta.perPage
  }

  function goToPage(next: number) {
    page.value = Math.min(Math.max(1, next), totalPages.value)
  }

  function resetPage() {
    page.value = 1
  }

  function listParams(extra: Record<string, string> = {}): Record<string, string> {
    return {
      ...extra,
      page: String(page.value),
      perPage: String(perPage.value),
    }
  }

  async function run<T>(loader: () => Promise<T>): Promise<T | null> {
    busy.value = true
    error.value = ''
    try {
      return await loader()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Request failed'
      return null
    } finally {
      busy.value = false
    }
  }

  return {
    page,
    perPage,
    total,
    busy,
    error,
    totalPages,
    hasPrev,
    hasNext,
    rangeLabel,
    setMeta,
    goToPage,
    resetPage,
    listParams,
    run,
  }
}
