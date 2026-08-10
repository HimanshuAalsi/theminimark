import type { RouteLocationRaw } from 'vue-router'

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

/** Primary header navigation — per site brief */
export const HEADER_NAV: NavItem[] = [
  {
    id: 'shop',
    label: 'Shop',
    children: [
      {
        label: 'Bookmarks',
        description: 'Magnetic & classic clips',
        to: '/shop/bookmarks',
      },
      {
        label: 'Fridge Magnets',
        description: 'Photo & quote magnets',
        to: '/shop/magnets',
      },
      {
        label: 'All Products',
        description: 'Browse the full catalogue',
        to: '/shop',
      },
    ],
  },
  {
    id: 'create-set',
    label: 'Create Your Own Set',
    to: '/create-your-set',
  },
  {
    id: 'blog',
    label: 'Blog',
    to: '/blog',
  },
]
