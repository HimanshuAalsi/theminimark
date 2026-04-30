/** Align fields with your PHP / MySQL API responses. */
export interface Product {
  id: string | number
  slug: string
  name: string
  description?: string
  price: number
  currency?: string
  imageUrl?: string
  category?: string
}
