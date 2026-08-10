export interface ShopSubcategory {
  id: number
  categorySlug: string
  slug: string
  name: string
  sortOrder: number
  isActive: boolean
}

export interface ShopCategoryWithSubs {
  id: number
  slug: string
  name: string
  description: string
  keywords: string
  imagePath: string
  imageUrl: string
  sortOrder: number
  isActive: boolean
  productCount?: number
  subcategories: ShopSubcategory[]
}

export const STATIC_SUBCATEGORIES: Record<string, ShopSubcategory[]> = {
  bookmarks: [
    { id: 1, categorySlug: 'bookmarks', slug: 'magnetic', name: 'Magnetic bookmarks', sortOrder: 10, isActive: true },
    { id: 2, categorySlug: 'bookmarks', slug: 'classic', name: 'Classic bookmarks', sortOrder: 20, isActive: true },
  ],
  cards: [
    { id: 3, categorySlug: 'cards', slug: 'birthday', name: 'Birthday cards', sortOrder: 10, isActive: true },
    { id: 4, categorySlug: 'cards', slug: 'thank-you', name: 'Thank you cards', sortOrder: 20, isActive: true },
    { id: 5, categorySlug: 'cards', slug: 'love', name: 'Love cards', sortOrder: 30, isActive: true },
    { id: 6, categorySlug: 'cards', slug: 'sorry', name: 'Sorry cards', sortOrder: 40, isActive: true },
  ],
  calendars: [
    { id: 7, categorySlug: 'calendars', slug: 'desk', name: 'Desk calendars', sortOrder: 10, isActive: true },
    { id: 8, categorySlug: 'calendars', slug: 'wall', name: 'Wall calendars', sortOrder: 20, isActive: true },
  ],
  magnets: [
    { id: 9, categorySlug: 'magnets', slug: 'photo', name: 'Photo magnets', sortOrder: 10, isActive: true },
    { id: 10, categorySlug: 'magnets', slug: 'quote', name: 'Quote magnets', sortOrder: 20, isActive: true },
    { id: 11, categorySlug: 'magnets', slug: 'couple', name: 'Couple magnets', sortOrder: 30, isActive: true },
  ],
  hampers: [
    { id: 12, categorySlug: 'hampers', slug: 'mini', name: 'Mini hampers', sortOrder: 10, isActive: true },
    { id: 13, categorySlug: 'hampers', slug: 'premium', name: 'Premium hampers', sortOrder: 20, isActive: true },
    { id: 14, categorySlug: 'hampers', slug: 'gift-sets', name: 'Gift sets', sortOrder: 30, isActive: true },
  ],
}

export function staticCategories(): ShopCategoryWithSubs[] {
  const slugs = ['bookmarks', 'cards', 'calendars', 'magnets', 'hampers'] as const
  const names: Record<string, string> = {
    bookmarks: 'Bookmarks',
    cards: 'Greeting Cards',
    calendars: 'Calendars',
    magnets: 'Magnets',
    hampers: 'Gift Hampers',
  }
  return slugs.map((slug, i) => ({
    id: i + 1,
    slug,
    name: names[slug],
    description: '',
    keywords: '',
    imagePath: '',
    imageUrl: '',
    sortOrder: (i + 1) * 10,
    isActive: true,
    subcategories: STATIC_SUBCATEGORIES[slug] ?? [],
  }))
}
