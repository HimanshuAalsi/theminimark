import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'
import type { SiteProduct } from '@/data/siteContent'
import type { PersonaliseType } from '@/data/personalise'
import type { PersonaliseOptions } from '@/data/personaliseOptions'
import { resolveProductImageUrl } from '@/lib/productImage'
import {
  buildCartMilestoneState,
  discountAmountForSubtotal,
  MILESTONE_DISCOUNT_INR,
  MILESTONE_FREE_GIFT_INR,
  MILESTONE_FREE_SHIPPING_INR,
  shippingFeeForSubtotal,
} from '@/lib/cartMilestones'
import type { FreeGiftOption } from '@/lib/freeGift'
import {
  isFreeGiftEligible,
  mergeFreeGiftOptions,
  pickConfiguredFreeGifts,
  pickRandomFreeGifts,
  siteProductToFreeGiftOption,
} from '@/lib/freeGift'
import type { Product } from '@/types/product'

const STORAGE_KEY = 'theminimark_cart'
const FREE_GIFT_STORAGE_KEY = 'theminimark_free_gift'

export interface CartLine {
  productId: Product['id']
  name: string
  unitPrice: number
  quantity: number
  imageUrl?: string
  /** Custom personalise studio item */
  customType?: PersonaliseType
  customPhotoUrl?: string
  customPhotoPath?: string
  customZoom?: number
  customPosX?: number
  customPosY?: number
  customOptions?: PersonaliseOptions
}

export interface CustomCartPayload {
  type: PersonaliseType
  name: string
  unitPrice: number
  photoUrl: string
  photoPath: string
  zoom?: number
  posX?: number
  posY?: number
  options?: PersonaliseOptions
  quantity?: number
}

function loadFreeGiftFromStorage(): FreeGiftOption | null {
  if (typeof localStorage === 'undefined') return null
  try {
    const raw = localStorage.getItem(FREE_GIFT_STORAGE_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw) as unknown
    if (
      typeof parsed === 'object' &&
      parsed !== null &&
      'id' in parsed &&
      'name' in parsed &&
      typeof (parsed as FreeGiftOption).id === 'string' &&
      typeof (parsed as FreeGiftOption).name === 'string'
    ) {
      return parsed as FreeGiftOption
    }
  } catch {
    /* ignore */
  }
  return null
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
  const freeGiftOptions = ref<FreeGiftOption[]>([])
  const selectedFreeGift = ref<FreeGiftOption | null>(loadFreeGiftFromStorage())
  let freeGiftOptionsCartKey = ''

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

  const discountAmount = computed(() => discountAmountForSubtotal(subtotal.value))

  const subtotalAfterDiscount = computed(() => Math.max(0, subtotal.value - discountAmount.value))

  const shippingFee = computed(() => shippingFeeForSubtotal(subtotal.value))

  const total = computed(() => subtotalAfterDiscount.value + shippingFee.value)

  const milestoneState = computed(() => buildCartMilestoneState(subtotal.value))

  const hasFreeShipping = computed(() => subtotal.value >= MILESTONE_FREE_SHIPPING_INR)

  const hasFreeGift = computed(() => subtotal.value >= MILESTONE_FREE_GIFT_INR)

  const hasDiscount = computed(() => subtotal.value >= MILESTONE_DISCOUNT_INR)

  const freeGiftReady = computed(
    () => !hasFreeGift.value || selectedFreeGift.value !== null,
  )

  watch(hasFreeGift, (eligible) => {
    if (!eligible) {
      selectedFreeGift.value = null
      freeGiftOptions.value = []
      try {
        localStorage.removeItem(FREE_GIFT_STORAGE_KEY)
      } catch {
        /* ignore */
      }
    }
  })

  watch(selectedFreeGift, (gift) => {
    try {
      if (gift) {
        localStorage.setItem(FREE_GIFT_STORAGE_KEY, JSON.stringify(gift))
      } else {
        localStorage.removeItem(FREE_GIFT_STORAGE_KEY)
      }
    } catch {
      /* ignore */
    }
  })

  function refreshFreeGiftOptions(catalog: SiteProduct[], configuredProductIds: string[] = []) {
    if (!hasFreeGift.value) {
      freeGiftOptions.value = []
      freeGiftOptionsCartKey = ''
      return
    }
    const excludeIds = lines.value.map((l) => l.productId)
    const cartKey = [...excludeIds, ...configuredProductIds].map(String).sort().join(',')

    if (selectedFreeGift.value) {
      if (!isFreeGiftEligible(selectedFreeGift.value, catalog, excludeIds)) {
        selectedFreeGift.value = null
      } else {
        const match = catalog.find((p) => String(p.id) === String(selectedFreeGift.value!.id))
        if (match) {
          selectedFreeGift.value = siteProductToFreeGiftOption(match)
        }
      }
    }

    if (freeGiftOptionsCartKey !== cartKey || freeGiftOptions.value.length === 0) {
      const configured = pickConfiguredFreeGifts(catalog, configuredProductIds, excludeIds)
      freeGiftOptions.value =
        configured.length > 0 ? configured : pickRandomFreeGifts(catalog, excludeIds)
      freeGiftOptionsCartKey = cartKey
    }

    freeGiftOptions.value = mergeFreeGiftOptions(
      freeGiftOptions.value,
      selectedFreeGift.value,
    )
  }

  function selectFreeGift(option: FreeGiftOption) {
    selectedFreeGift.value = option
  }

  function clearFreeGift() {
    selectedFreeGift.value = null
  }

  function productImageUrl(product: Product): string {
    const raw =
      product.imageUrl ?? (product as Product & { image?: string }).image ?? ''
    return resolveProductImageUrl(raw)
  }

  function refreshLineImagesFromCatalog(catalog: SiteProduct[]) {
    for (const line of lines.value) {
      if (line.customType) {
        const custom = resolveProductImageUrl(line.customPhotoUrl ?? line.imageUrl)
        if (custom) line.imageUrl = custom
        continue
      }
      const match = catalog.find((p) => p.id === String(line.productId))
      if (match?.image) {
        line.imageUrl = resolveProductImageUrl(match.image)
      } else if (line.imageUrl) {
        line.imageUrl = resolveProductImageUrl(line.imageUrl)
      }
    }
  }

  function addProduct(product: Product, quantity = 1) {
    const q = Math.max(1, quantity)
    const existing = lines.value.find((l) => l.productId === product.id)
    if (existing) {
      existing.quantity += q
      return
    }
    const imageUrl = productImageUrl(product)
    lines.value.push({
      productId: product.id,
      name: product.name,
      unitPrice: product.price,
      quantity: q,
      imageUrl: imageUrl || undefined,
    })
  }

  function addCustomProduct(payload: CustomCartPayload, quantity = 1) {
    const q = Math.max(1, payload.options?.quantity ?? quantity)
    const productId = `custom-${payload.type}-${Date.now()}`
    const photoUrl = resolveProductImageUrl(payload.photoUrl)
    lines.value.push({
      productId,
      name: payload.name,
      unitPrice: payload.unitPrice,
      quantity: q,
      imageUrl: photoUrl,
      customType: payload.type,
      customPhotoUrl: photoUrl,
      customPhotoPath: payload.photoPath,
      customZoom: payload.zoom ?? 1,
      customPosX: payload.posX ?? 50,
      customPosY: payload.posY ?? 50,
      customOptions: payload.options,
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
    clearFreeGift()
    freeGiftOptions.value = []
  }

  return {
    lines,
    totalQuantity,
    subtotal,
    discountAmount,
    subtotalAfterDiscount,
    shippingFee,
    total,
    milestoneState,
    hasFreeShipping,
    hasFreeGift,
    hasDiscount,
    freeGiftOptions,
    selectedFreeGift,
    freeGiftReady,
    refreshFreeGiftOptions,
    selectFreeGift,
    clearFreeGift,
    addProduct,
    addCustomProduct,
    setQuantity,
    removeLine,
    clear,
    refreshLineImagesFromCatalog,
  }
})
