/** Cart reward milestones (INR, based on items subtotal before discount). */
export const MILESTONE_FREE_GIFT_INR = 199
export const MILESTONE_FREE_SHIPPING_INR = 499
export const MILESTONE_DISCOUNT_INR = 699
export const MILESTONE_DISCOUNT_PERCENT = 12

/** Progress bar scale — last milestone defines 100% fill. */
export const MILESTONE_TRACK_MAX_INR = MILESTONE_DISCOUNT_INR

export const SHIPPING_FEE_INR = 70

export type MilestoneId = 'gift' | 'shipping' | 'discount'

export interface CartMilestoneDef {
  id: MilestoneId
  threshold: number
  label: string
  shortLabel: string
}

export const CART_MILESTONES: CartMilestoneDef[] = [
  { id: 'gift', threshold: MILESTONE_FREE_GIFT_INR, label: 'Free gift', shortLabel: 'Gift' },
  {
    id: 'shipping',
    threshold: MILESTONE_FREE_SHIPPING_INR,
    label: 'Free delivery',
    shortLabel: 'Ship',
  },
  {
    id: 'discount',
    threshold: MILESTONE_DISCOUNT_INR,
    label: '12% off',
    shortLabel: '12% off',
  },
]

export interface MilestoneStatus extends CartMilestoneDef {
  unlocked: boolean
  positionPercent: number
  amountAway: number
}

export interface CartMilestoneState {
  subtotal: number
  progressPercent: number
  milestones: MilestoneStatus[]
  nextMilestone: MilestoneStatus | null
  unlockedCount: number
}

export function shippingFeeForSubtotal(subtotal: number): number {
  if (subtotal <= 0) return 0
  return subtotal >= MILESTONE_FREE_SHIPPING_INR ? 0 : SHIPPING_FEE_INR
}

export function discountAmountForSubtotal(subtotal: number): number {
  if (subtotal < MILESTONE_DISCOUNT_INR) return 0
  return Math.round(subtotal * (MILESTONE_DISCOUNT_PERCENT / 100))
}

export function buildCartMilestoneState(subtotal: number): CartMilestoneState {
  const progressPercent = Math.min(
    100,
    MILESTONE_TRACK_MAX_INR > 0 ? (subtotal / MILESTONE_TRACK_MAX_INR) * 100 : 0,
  )

  const milestones: MilestoneStatus[] = CART_MILESTONES.map((m) => ({
    ...m,
    unlocked: subtotal >= m.threshold,
    positionPercent: (m.threshold / MILESTONE_TRACK_MAX_INR) * 100,
    amountAway: Math.max(0, m.threshold - subtotal),
  }))

  const nextMilestone = milestones.find((m) => !m.unlocked) ?? null
  const unlockedCount = milestones.filter((m) => m.unlocked).length

  return {
    subtotal,
    progressPercent,
    milestones,
    nextMilestone,
    unlockedCount,
  }
}

export function nextMilestoneHint(
  state: CartMilestoneState,
  format: (n: number) => string,
): string | null {
  const next = state.nextMilestone
  if (!next) {
    return 'All rewards unlocked — nice work!'
  }
  const amount = format(next.amountAway)
  switch (next.id) {
    case 'gift':
      return `Add ${amount} more for a free gift`
    case 'shipping':
      return `Add ${amount} more for free delivery`
    case 'discount':
      return `Add ${amount} more for 12% off`
    default:
      return null
  }
}
