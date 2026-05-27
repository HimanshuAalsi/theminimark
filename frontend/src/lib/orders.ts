import { apiFetch, getApiBaseUrl } from '@/lib/api'

function apiPrefix(): string {
  return getApiBaseUrl() ? '/v1' : '/api/v1'
}

export interface OrderLinePayload {
  productId: string | number
  name: string
  unitPrice: number
  quantity: number
}

export interface SubmitOrderPayload {
  customerEmail: string
  customerName?: string
  currency?: string
  lines: OrderLinePayload[]
  notes?: string
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
