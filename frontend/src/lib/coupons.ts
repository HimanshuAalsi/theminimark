import { apiFetch } from '@/lib/api'

function apiPrefix(): string {
  return import.meta.env.VITE_API_BASE_URL ? '/v1' : '/api/v1'
}

export interface CouponValidation {
  ok: boolean
  message?: string
  coupon?: {
    code: string
    description: string
    discountType: 'percent' | 'fixed'
    discountValue: number
  }
  discountInr?: number
}

export async function validateCoupon(
  code: string,
  customerEmail: string,
  itemsSubtotal: number,
): Promise<CouponValidation> {
  return apiFetch(`${apiPrefix()}/coupons/validate`, {
    method: 'POST',
    body: JSON.stringify({ code, customerEmail, itemsSubtotal }),
  })
}
