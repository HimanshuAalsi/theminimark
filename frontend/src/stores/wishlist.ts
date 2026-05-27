import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'
import { addWishlistItem, fetchWishlist, removeWishlistItem, syncWishlist } from '@/lib/wishlistApi'
import { useAuthStore } from '@/stores/auth'

const GUEST_KEY = 'tm_wishlist_guest'

function storageKeyForUser(userId: number | null): string {
  return userId ? `tm_wishlist_user_${userId}` : GUEST_KEY
}

function loadIds(key: string): string[] {
  if (typeof localStorage === 'undefined') return []
  try {
    const raw = localStorage.getItem(key)
    if (!raw) return []
    const parsed = JSON.parse(raw) as unknown
    return Array.isArray(parsed) ? parsed.map(String) : []
  } catch {
    return []
  }
}

function saveIds(key: string, ids: string[]) {
  if (typeof localStorage === 'undefined') return
  try {
    localStorage.setItem(key, JSON.stringify(ids))
  } catch {
    /* ignore quota */
  }
}

export const useWishlistStore = defineStore('wishlist', () => {
  const productIds = ref<string[]>([])
  const storageKey = ref(GUEST_KEY)
  const ready = ref(false)
  let inflight: Promise<void> | null = null

  const count = computed(() => productIds.value.length)

  function persist() {
    saveIds(storageKey.value, productIds.value)
  }

  function loadLocal(key: string) {
    storageKey.value = key
    productIds.value = loadIds(key)
  }

  function has(productId: string) {
    return productIds.value.includes(String(productId))
  }

  async function pullFromServer(token: string) {
    const r = await fetchWishlist(token)
    if (r.ok) {
      productIds.value = r.productIds
      persist()
    }
  }

  async function mergeGuestIntoAccount(token: string, userId: number) {
    const guestIds = loadIds(GUEST_KEY)
    const userKey = storageKeyForUser(userId)
    const userLocalIds = loadIds(userKey)
    const merged = [...new Set([...userLocalIds, ...guestIds, ...productIds.value])]
    const r = await syncWishlist(token, merged)
    if (r.ok) {
      productIds.value = r.productIds
      storageKey.value = userKey
      persist()
      saveIds(GUEST_KEY, [])
    }
  }

  async function ensureLoaded(options?: { force?: boolean }) {
    if (ready.value && !options?.force) return
    if (inflight) {
      await inflight
      return
    }

    inflight = (async () => {
      const auth = useAuthStore()
      await auth.initialize()

      if (auth.isAuthenticated && auth.token && auth.user) {
        const key = storageKeyForUser(auth.user.id)
        loadLocal(key)
        try {
          await pullFromServer(auth.token)
        } catch {
          /* offline or table missing — keep local cache */
        }
      } else {
        loadLocal(GUEST_KEY)
      }

      ready.value = true
    })().finally(() => {
      inflight = null
    })

    await inflight
  }

  async function onAuthChanged() {
    ready.value = false
    const auth = useAuthStore()
    const prevKey = storageKey.value
    const guestIds = prevKey === GUEST_KEY ? [...productIds.value] : loadIds(GUEST_KEY)

    if (auth.isAuthenticated && auth.token && auth.user) {
      loadLocal(storageKeyForUser(auth.user.id))
      const merged = [...new Set([...productIds.value, ...guestIds])]
      productIds.value = merged
      persist()
      try {
        await mergeGuestIntoAccount(auth.token, auth.user.id)
      } catch {
        persist()
      }
    } else {
      if (prevKey !== GUEST_KEY) {
        loadLocal(GUEST_KEY)
      }
      persist()
    }

    ready.value = true
  }

  async function toggle(productId: string) {
    const id = String(productId)
    const auth = useAuthStore()

    if (has(id)) {
      productIds.value = productIds.value.filter((x) => x !== id)
      persist()
      if (auth.isAuthenticated && auth.token) {
        try {
          const r = await removeWishlistItem(auth.token, id)
          if (r.ok) productIds.value = r.productIds
          persist()
        } catch {
          /* keep local state */
        }
      }
      return false
    }

    productIds.value = [...productIds.value, id]
    persist()
    if (auth.isAuthenticated && auth.token) {
      try {
        const r = await addWishlistItem(auth.token, id)
        if (r.ok) productIds.value = r.productIds
        persist()
      } catch {
        /* keep local state */
      }
    }
    return true
  }

  async function remove(productId: string) {
    if (!has(productId)) return
    await toggle(productId)
  }

  watch(
    () => {
      const auth = useAuthStore()
      return auth.user?.id ?? null
    },
    (userId, prev) => {
      if (userId !== prev) {
        void onAuthChanged()
      }
    }
  )

  return {
    productIds,
    count,
    ready,
    has,
    ensureLoaded,
    toggle,
    remove,
    onAuthChanged,
  }
})
