export const STORE_CURRENCY = 'INR' as const

const formatter = new Intl.NumberFormat('en-IN', {
  style: 'currency',
  currency: STORE_CURRENCY,
  maximumFractionDigits: 0,
})

/** Format a store price in Indian Rupees (₹). */
export function formatCurrency(amount: number): string {
  return formatter.format(amount)
}
