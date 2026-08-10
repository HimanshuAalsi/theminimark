import { apiFetch, getApiBaseUrl } from '@/lib/api'

function apiPrefix(): string {
  return getApiBaseUrl() ? '/v1' : '/api/v1'
}

import type { PersonaliseOptions } from '@/data/personaliseOptions'
import type { PersonaliseType } from '@/data/personalise'

export interface OrderLinePersonalization {
  type: PersonaliseType
  photoPath: string
  zoom: number
  posX: number
  posY: number
  options: PersonaliseOptions
}

export interface OrderLinePayload {
  productId: string | number
  name: string
  unitPrice: number
  quantity: number
  personalization?: OrderLinePersonalization
}

export interface FreeGiftOrderPayload {
  productId: string
  name: string
}

export interface OrderShippingPayload {
  phone: string
  addressLine1: string
  addressLine2?: string
  landmark?: string
  pincode: string
  city: string
  state: string
}

export interface SubmitOrderPayload {
  customerEmail: string
  customerName?: string
  currency?: string
  lines: OrderLinePayload[]
  notes?: string
  freeGift?: FreeGiftOrderPayload
  couponCode?: string
  shipping: OrderShippingPayload
}

export async function submitOrder(payload: SubmitOrderPayload): Promise<{
  ok: boolean
  orderId?: number
  message: string
}> {
  return apiFetch(`${apiPrefix()}/orders`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export interface TrackedOrderLine {
  id: number
  name: string
  quantity: number
}

export interface TrackedOrder {
  id: number
  status: string
  createdAt: string
  subtotal: number
  currency: string
  itemCount: number
  lines: TrackedOrderLine[]
}

export async function trackOrder(
  orderId: number,
  email: string,
): Promise<{ ok: boolean; message: string; order?: TrackedOrder }> {
  const params = new URLSearchParams({
    orderId: String(orderId),
    email: email.trim(),
  })
  return apiFetch(`${apiPrefix()}/orders/track?${params.toString()}`)
}
