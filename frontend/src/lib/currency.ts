export const STORE_CURRENCY = 'INR' as const

const formatterWhole = new Intl.NumberFormat('en-IN', {
  style: 'currency',
  currency: STORE_CURRENCY,
  maximumFractionDigits: 0,
})

const formatterPrecise = new Intl.NumberFormat('en-IN', {
  style: 'currency',
  currency: STORE_CURRENCY,
  minimumFractionDigits: 0,
  maximumFractionDigits: 2,
})

/** Format a store price in Indian Rupees (₹). */
export function formatCurrency(amount: number): string {
  const rounded = Math.round(amount * 100) / 100
  const useDecimals = Math.abs(rounded % 1) > 0.001
  return (useDecimals ? formatterPrecise : formatterWhole).format(rounded)
}

/** Line total — avoids float drift (e.g. 9 × 2 → ₹18, not ₹17). */
export function formatLineTotal(unitPrice: number, quantity: number): string {
  const total = Math.round(unitPrice * quantity * 100) / 100
  return formatCurrency(total)
}
