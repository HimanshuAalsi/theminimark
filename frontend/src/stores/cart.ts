import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'
import type { Product } from '@/types/product'

const STORAGE_KEY = 'theminimark_cart'

export interface CartLine {
  productId: Product['id']
  name: string
  unitPrice: number
  quantity: number
  imageUrl?: string
}

function loadFromStorage(): CartLine[] {
  if (typeof localStorage === 'undefined') return []
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return []
    const parsed = JSON.parse(raw) as unknown
    if (!Array.isArray(parsed)) return []
    return parsed as CartLine[]
  } catch {
    return []
  }
}

export const useCartStore = defineStore('cart', () => {
  const lines = ref<CartLine[]>(loadFromStorage())

  watch(
    lines,
    (v) => {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(v))
      } catch {
        /* ignore quota / private mode */
      }
    },
    { deep: true }
  )

  const totalQuantity = computed(() =>
    lines.value.reduce((n, l) => n + l.quantity, 0)
  )

  const subtotal = computed(() =>
    lines.value.reduce((n, l) => n + l.unitPrice * l.quantity, 0)
  )

  function addProduct(product: Product, quantity = 1) {
    const q = Math.max(1, quantity)
    const existing = lines.value.find((l) => l.productId === product.id)
    if (existing) {
      existing.quantity += q
      return
    }
    lines.value.push({
      productId: product.id,
      name: product.name,
      unitPrice: product.price,
      quantity: q,
      imageUrl: product.imageUrl,
    })
  }

  function setQuantity(productId: Product['id'], quantity: number) {
    const line = lines.value.find((l) => l.productId === productId)
    if (!line) return
    if (quantity < 1) {
      removeLine(productId)
      return
    }
    line.quantity = quantity
  }

  function removeLine(productId: Product['id']) {
    lines.value = lines.value.filter((l) => l.productId !== productId)
  }

  function clear() {
    lines.value = []
  }

  return {
    lines,
    totalQuantity,
    subtotal,
    addProduct,
    setQuantity,
    removeLine,
    clear,
  }
})
