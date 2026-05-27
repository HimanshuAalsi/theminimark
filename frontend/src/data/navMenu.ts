import type { RouteLocationRaw } from 'vue-router'
import { SHOP_CATEGORIES } from '@/data/siteContent'

export interface NavLink {
  label: string
  to: RouteLocationRaw
  description?: string
}

export interface NavItem {
  id: string
  label: string
  to?: RouteLocationRaw
  children?: NavLink[]
}

export const HEADER_NAV: NavItem[] = [
  {
    id: 'bookmarks',
    label: 'Bookmarks',
    children: [
      {
        label: 'Classic',
        description: 'Paper & clip bookmarks',
        to: { path: '/shop', query: { category: 'bookmarks', type: 'classic' } },
      },
      {
        label: 'Magnetic',
        description: 'Fold-over magnetic clips',
        to: { path: '/shop', query: { category: 'bookmarks', type: 'magnetic' } },
      },
    ],
  },
  {
    id: 'fridge-magnets',
    label: 'Fridge Magnets',
    to: { path: '/shop', query: { category: 'magnets' } },
  },
  {
    id: 'create-set',
    label: 'Create Your Own Set',
    to: '/create-your-set',
  },
  {
    id: 'shop',
    label: 'Shop',
    children: [
      {
        label: 'All categories',
        description: 'Browse everything',
        to: '/shop',
      },
      ...SHOP_CATEGORIES.filter((c) => c.id !== 'all').map((c) => ({
      label: c.id === 'magnets' ? 'Fridge Magnets' : c.label,
      description: c.blurb,
      to: { path: '/shop', query: { category: c.id } },
      })),
    ],
  },
]
