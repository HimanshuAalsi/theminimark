import type { PersonaliseType } from '@/data/personalise'

export interface PersonaliseColorSwatch {
  id: string
  label: string
  hex: string
}

export interface MagnetSizeOption {
  id: '2x3' | '4x4' | 'custom'
  label: string
  dimensions: string
  price: number
  compareAt: number
  hint: string
}

export interface CalendarDesign {
  id: string
  label: string
  shortLabel: string
  image: string
  layout: 'desk' | 'wall'
  price: number
  compareAt: number
  /** Visual style applied to all 12 month cards */
  variant: 'minimal' | 'floral' | 'classic' | 'grid' | 'pastel' | 'bold' | 'gift' | 'family'
  accent: string
  photoBg: string
  labelBg: string
}

export const PERSONALISE_NAV: { id: PersonaliseType; label: string }[] = [
  { id: 'bookmark', label: 'Bookmark' },
  { id: 'magnet', label: 'Fridge Magnet' },
  { id: 'card', label: 'Cards' },
  { id: 'calendar', label: 'Calendar' },
]

export const BOOKMARK_ACCENT_COLORS: PersonaliseColorSwatch[] = [
  { id: 'navy', label: 'Navy', hex: '#1e3a5f' },
  { id: 'lime', label: 'Lime', hex: '#c4d600' },
  { id: 'purple', label: 'Purple', hex: '#5b2c6f' },
  { id: 'wine', label: 'Wine', hex: '#722f37' },
  { id: 'mustard', label: 'Mustard', hex: '#c9a227' },
  { id: 'cyan', label: 'Cyan', hex: '#0891b2' },
  { id: 'forest', label: 'Forest', hex: '#2d5a27' },
  { id: 'blush', label: 'Blush', hex: '#e8a0bf' },
  { id: 'crimson', label: 'Crimson', hex: '#b91c1c' },
  { id: 'teal', label: 'Teal', hex: '#0d9488' },
  { id: 'pink', label: 'Pink', hex: '#f4b8d0' },
  { id: 'coral', label: 'Coral', hex: '#f08080' },
  { id: 'lavender', label: 'Lavender', hex: '#c8b6e2' },
  { id: 'mint', label: 'Mint', hex: '#a8e6cf' },
  { id: 'peach', label: 'Peach', hex: '#ffd4b8' },
  { id: 'sky', label: 'Sky', hex: '#87ceeb' },
  { id: 'gold', label: 'Gold', hex: '#d4af37' },
  { id: 'charcoal', label: 'Charcoal', hex: '#36454f' },
  { id: 'rose', label: 'Rose', hex: '#e91e8c' },
  { id: 'cream', label: 'Cream', hex: '#fff5e6' },
]

export const CALENDAR_MONTHS = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
] as const

export const MAGNET_FRAME_COUNT = 5

export type MagnetSlotType = 'photo' | 'text'

export interface MagnetStripSlot {
  type: MagnetSlotType
  label: string
}

export interface MagnetStripFrame {
  id: string
  label: string
  hint: string
  price: number
  compareAt: number
  slots: MagnetStripSlot[]
  /** Visual variant for the live strip preview */
  variant: 'quad' | 'triple' | 'triple-text' | 'quad-text' | 'split'
}

/** Magnetic photo strip — five frame designs with 3–4 photo slots (some include text). */
export const MAGNET_STRIP_FRAMES: MagnetStripFrame[] = [
  {
    id: 'strip-f1',
    label: 'Frame 1',
    hint: 'Four equal photo panels — classic vertical strip.',
    price: 449,
    compareAt: 549,
    variant: 'quad',
    slots: [
      { type: 'photo', label: 'Photo 1' },
      { type: 'photo', label: 'Photo 2' },
      { type: 'photo', label: 'Photo 3' },
      { type: 'photo', label: 'Photo 4' },
    ],
  },
  {
    id: 'strip-f2',
    label: 'Frame 2',
    hint: 'Four photos with a slim border between each panel.',
    price: 449,
    compareAt: 549,
    variant: 'split',
    slots: [
      { type: 'photo', label: 'Photo 1' },
      { type: 'photo', label: 'Photo 2' },
      { type: 'photo', label: 'Photo 3' },
      { type: 'photo', label: 'Photo 4' },
    ],
  },
  {
    id: 'strip-f3',
    label: 'Frame 3',
    hint: 'Three larger photo panels — fewer, bigger moments.',
    price: 399,
    compareAt: 499,
    variant: 'triple',
    slots: [
      { type: 'photo', label: 'Photo 1' },
      { type: 'photo', label: 'Photo 2' },
      { type: 'photo', label: 'Photo 3' },
    ],
  },
  {
    id: 'strip-f4',
    label: 'Frame 4',
    hint: 'Three photos plus a short caption line at the bottom.',
    price: 449,
    compareAt: 549,
    variant: 'triple-text',
    slots: [
      { type: 'photo', label: 'Photo 1' },
      { type: 'photo', label: 'Photo 2' },
      { type: 'photo', label: 'Photo 3' },
      { type: 'text', label: 'Caption' },
    ],
  },
  {
    id: 'strip-f5',
    label: 'Frame 5',
    hint: 'Four photos with a name or quote printed beneath.',
    price: 499,
    compareAt: 599,
    variant: 'quad-text',
    slots: [
      { type: 'photo', label: 'Photo 1' },
      { type: 'photo', label: 'Photo 2' },
      { type: 'photo', label: 'Photo 3' },
      { type: 'photo', label: 'Photo 4' },
      { type: 'text', label: 'Caption' },
    ],
  },
]

export const MAGNET_SIZES: MagnetSizeOption[] = [
  {
    id: '2x3',
    label: '2 × 3"',
    dimensions: '2 × 3 inch · portrait',
    price: 349,
    compareAt: 449,
    hint: 'Classic photo magnet — great for fridges & lockers.',
  },
  {
    id: '4x4',
    label: '4 × 4"',
    dimensions: '4 × 4 inch · square',
    price: 449,
    compareAt: 549,
    hint: 'Bigger square magnet — bold photos & quotes.',
  },
  {
    id: 'custom',
    label: 'Custom size',
    dimensions: 'Tell us your size',
    price: 549,
    compareAt: 699,
    hint: 'Need a special size? Upload & note dimensions — we’ll confirm on WhatsApp.',
  },
]

export const CALENDAR_DESIGNS: CalendarDesign[] = [
  { id: 'd1', label: 'Design 1 · Minimal desk', shortLabel: 'Design 1', image: '/products/calendars.jpeg', layout: 'desk', price: 599, compareAt: 749, variant: 'minimal', accent: '#2d5a27', photoBg: '#fff', labelBg: '#ececec' },
  { id: 'd2', label: 'Design 2 · Floral desk', shortLabel: 'Design 2', image: '/products/new-calendars.jpeg', layout: 'desk', price: 599, compareAt: 749, variant: 'floral', accent: '#e91e8c', photoBg: '#fff8fb', labelBg: '#f4d4e4' },
  { id: 'd3', label: 'Design 3 · Classic wall', shortLabel: 'Design 3', image: '/products/calendars.jpeg', layout: 'wall', price: 649, compareAt: 799, variant: 'classic', accent: '#1e3a5f', photoBg: '#fff', labelBg: '#d8dce0' },
  { id: 'd4', label: 'Design 4 · Photo grid', shortLabel: 'Design 4', image: '/products/new-calendars.jpeg', layout: 'wall', price: 649, compareAt: 799, variant: 'grid', accent: '#5b2c6f', photoBg: '#fafafa', labelBg: '#e0d4ec' },
  { id: 'd5', label: 'Design 5 · Pastel desk', shortLabel: 'Design 5', image: '/products/calendars.jpeg', layout: 'desk', price: 599, compareAt: 749, variant: 'pastel', accent: '#0891b2', photoBg: '#f0faff', labelBg: '#c8e6f5' },
  { id: 'd6', label: 'Design 6 · Bold wall', shortLabel: 'Design 6', image: '/products/new-calendars.jpeg', layout: 'wall', price: 649, compareAt: 799, variant: 'bold', accent: '#b91c1c', photoBg: '#fff', labelBg: '#f5d0d0' },
  { id: 'd7', label: 'Design 7 · Gift desk', shortLabel: 'Design 7', image: '/products/calendars.jpeg', layout: 'desk', price: 599, compareAt: 749, variant: 'gift', accent: '#c9a227', photoBg: '#fffef5', labelBg: '#f0e6c8' },
  { id: 'd8', label: 'Design 8 · Family wall', shortLabel: 'Design 8', image: '/products/new-calendars.jpeg', layout: 'wall', price: 649, compareAt: 799, variant: 'family', accent: '#0d9488', photoBg: '#f5fffc', labelBg: '#c8ebe3' },
]

export const STUDIO_PERKS = [
  'Made to order in India',
  'Secure checkout · Razorpay',
  'Free delivery above ₹499',
  'Unboxing video required for returns',
] as const

export function magnetPriceForSize(sizeId: MagnetSizeOption['id']): MagnetSizeOption {
  return MAGNET_SIZES.find((s) => s.id === sizeId) ?? MAGNET_SIZES[0]
}

export function magnetStripFrameByIndex(index: number): MagnetStripFrame {
  return MAGNET_STRIP_FRAMES[index] ?? MAGNET_STRIP_FRAMES[0]
}

export function magnetStripSlotKey(frameIndex: number, slotIndex: number): string {
  return `${frameIndex}-${slotIndex}`
}

export function calendarDesignById(id: string): CalendarDesign {
  return CALENDAR_DESIGNS.find((d) => d.id === id) ?? CALENDAR_DESIGNS[0]
}

export function calendarMonthKey(designId: string, monthIndex: number): string {
  return `${designId}-m${monthIndex}`
}

export function typeFromQuery(v: unknown): PersonaliseType {
  if (v === 'magnet' || v === 'card' || v === 'calendar' || v === 'bookmark') return v
  return 'bookmark'
}
