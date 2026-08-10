/** Custom magnetic bookmark — copy, limits, and gallery for the personalise page */

export const BOOKMARK_TEXT_MAX_WORDS = 25

export const BOOKMARK_IMAGE_HINT =
  'Portrait photo works best — roughly 5×6 cm print area (591×709 px recommended).'

export const BOOKMARK_GALLERY = [
  '/products/magnetic-bookmarks.jpeg',
  '/products/classic-bookmarks.jpeg',
  '/products/magnetic-bookmarks.jpeg',
] as const

export const BOOKMARK_GUIDELINES = [
  'Use a clear, high-quality image — avoid busy backgrounds or tiny text in the photo.',
  'For a name, keep it short (about 12 words or fewer).',
  'For a quote instead of a name, stay within 25 words.',
  'Double-check spelling before you add to cart.',
  'Share an email below so we can reach you if anything looks unclear.',
  'Each custom bookmark is made to order — add multiples one at a time if you need different designs.',
] as const

export const BOOKMARK_POLICY_NOTE =
  'Custom bookmarks are made to your specification and are not eligible for return unless there is shipment damage or a print defect.'

export function countWords(text: string): number {
  const t = text.trim()
  if (!t) return 0
  return t.split(/\s+/).filter(Boolean).length
}

export function bookmarkTextValid(text: string): boolean {
  return countWords(text) <= BOOKMARK_TEXT_MAX_WORDS
}
