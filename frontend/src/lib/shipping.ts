/** @deprecated Import from `@/lib/cartMilestones` — re-exported for compatibility. */
export {
  MILESTONE_FREE_SHIPPING_INR as FREE_SHIPPING_MIN_INR,
  SHIPPING_FEE_INR,
  shippingFeeForSubtotal,
  discountAmountForSubtotal,
  MILESTONE_FREE_GIFT_INR,
  MILESTONE_DISCOUNT_INR,
  MILESTONE_DISCOUNT_PERCENT,
} from '@/lib/cartMilestones'

import { MILESTONE_FREE_SHIPPING_INR } from '@/lib/cartMilestones'

export function amountForFreeShipping(subtotal: number): number {
  if (subtotal >= MILESTONE_FREE_SHIPPING_INR) return 0
  return Math.max(0, MILESTONE_FREE_SHIPPING_INR - subtotal)
}

export function qualifiesForFreeShipping(subtotal: number): boolean {
  return subtotal >= MILESTONE_FREE_SHIPPING_INR
}
