import type {
  HomeBoxStyle,
  HomeColumnSpan,
  HomeLayoutColumn,
  HomeLayoutRow,
  HomeLayoutSection,
  HomeLayoutSegment,
  HomePageLayout,
  HomeProductSource,
  HomeSegmentType,
  HomeSpacing,
} from '@/types/homePageLayout'
import { PERSONALISE_STOREFRONT_VISIBLE } from '@/data/personalise'

export function hpId(prefix = 'hp'): string {
  return `${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 7)}`
}

export function seg(type: HomeSegmentType, extra: Record<string, unknown> = {}): HomeLayoutSegment {
  return { id: hpId('seg'), type, ...extra } as HomeLayoutSegment
}

export function col(span: HomeColumnSpan, segments: HomeLayoutSegment[]): HomeLayoutColumn {
  return { id: hpId('col'), span, segments }
}

export function row(columns: HomeLayoutColumn[]): HomeLayoutRow {
  return { id: hpId('row'), columns }
}

export function section(
  label: string,
  segments: HomeLayoutSegment[],
  opts: Partial<Pick<HomeLayoutSection, 'theme' | 'enabled'>> = {},
): HomeLayoutSection {
  return {
    id: hpId('sec'),
    label,
    enabled: opts.enabled ?? true,
    theme: opts.theme ?? 'default',
    rows: [row([col(12, segments)])],
  }
}

export function headerSeg(
  eyebrow: string,
  title: string,
  description: string,
  cta?: { label: string; to: string },
): HomeLayoutSegment {
  return {
    id: hpId('seg'),
    type: 'section-header',
    eyebrow,
    title,
    description,
    align: 'left',
    ...(cta ? { cta } : {}),
  }
}

export function productGrid(
  source: HomeProductSource,
  opts: Partial<{
    category: string
    subcategory: string
    productIds: string[]
    limit: number
    columns: 3 | 4 | 5
    viewAllLabel: string
    viewAllTo: string
  }> = {},
): HomeLayoutSegment {
  return {
    id: hpId('seg'),
    type: 'product-grid',
    source,
    limit: opts.limit ?? 10,
    columns: opts.columns ?? 5,
    ...opts,
  } as HomeLayoutSegment
}

export function productCarousel(
  source: HomeProductSource,
  opts: Partial<{
    category: string
    subcategory: string
    productIds: string[]
    limit: number
    viewAllLabel: string
    viewAllTo: string
  }> = {},
): HomeLayoutSegment {
  return {
    id: hpId('seg'),
    type: 'product-carousel',
    source,
    limit: opts.limit ?? 12,
    ...opts,
  } as HomeLayoutSegment
}

/** Default layout — sections ordered per site brief */
export function createDefaultLayout(): HomePageLayout {
  return {
    version: 1,
    sections: [
      section('Hero', [seg('hero')]),
      section('Trust strip', [seg('trust')]),
      section('Bestsellers', [
        headerSeg(
          'Bestsellers',
          'What readers & gifters love',
          'A curated mix of bookmarks, cards, calendars, magnets, and gift boxes — not bookmarks alone.',
          { label: 'Explore more', to: '/shop' },
        ),
        productGrid('bestsellers', {
          limit: 10,
          columns: 5,
          viewAllLabel: 'View full shop',
          viewAllTo: '/shop',
        }),
      ], { theme: 'cream' }),
      section('Create your own set', [
        headerSeg(
          'Bundles & hampers',
          'Create your own set',
          'Bookmark-only sets with bigger savings as you go, or mix any products into a custom hamper.',
        ),
        seg('create-set-promo'),
      ]),
      section('Categories', [
        headerSeg(
          'Shop by category',
          'A page for every collection',
          'Open bookmarks, cards, calendars, magnets, or hampers on their own dedicated shop page.',
        ),
        seg('category-grid'),
      ]),
      // Hidden on storefront — set PERSONALISE_STOREFRONT_VISIBLE = true to restore; /personalise stays live
      // section('Personalise', [
      //   headerSeg(
      //     'Personalise',
      //     'Make it yours',
      //     'Upload your photo and optional text — we print it on a custom magnetic bookmark.',
      //     { label: 'Design your bookmark', to: '/personalise' },
      //   ),
      //   seg('personalise-grid'),
      // ]),
      section('Stay connected', [seg('connect')]),
      section('Just Mini Knots', [
        headerSeg(
          'Handmade add-ons',
          'Just Mini Knots',
          'Crochet pieces and little extras — perfect to tuck into a custom hamper.',
        ),
        seg('mini-knots'),
      ], { theme: 'cream' }),
      section('Blog', [seg('blog-teaser', { limit: 3 })]),
    ],
  }
}

export function ensureLayout(raw: unknown): HomePageLayout {
  if (!raw || typeof raw !== 'object') return createDefaultLayout()
  const o = raw as HomePageLayout
  if (o.version !== 1 || !Array.isArray(o.sections) || o.sections.length === 0) {
    return createDefaultLayout()
  }
  return migrateLayout({
    version: 1,
    sections: o.sections.map(normalizeSection).filter((s) => s.rows.length > 0),
    presets: Array.isArray(o.presets) ? o.presets.slice(0, 32) : undefined,
  })
}

/** Upgrade saved layouts: newsletter → connect, inject Mini Knots if missing, drop sale countdown. */
function migrateLayout(layout: HomePageLayout): HomePageLayout {
  let hasConnect = false
  let hasMiniKnots = false

  layout.sections = layout.sections
    .map((section) => {
      const rows = section.rows
        .map((row) => ({
          ...row,
          columns: row.columns.map((col) => ({
            ...col,
            segments: col.segments
              .map((s) => {
                if (s.type === 'newsletter') {
                  hasConnect = true
                  return { ...s, type: 'connect' as const }
                }
                if (s.type === 'connect') hasConnect = true
                if (s.type === 'mini-knots') hasMiniKnots = true
                return s
              })
              .filter((s) => {
                if (s.type === 'sale-countdown') return false
                if (!PERSONALISE_STOREFRONT_VISIBLE && s.type === 'personalise-grid') return false
                return true
              }),
          })),
        }))
        .filter((row) => row.columns.some((col) => col.segments.length > 0))

      return { ...section, rows }
    })
    .filter((section) => {
      if (/sale\s*countdown/i.test(section.label)) return false
      if (!PERSONALISE_STOREFRONT_VISIBLE && /personalise/i.test(section.label)) return false
      return section.rows.length > 0
    })

  for (const section of layout.sections) {
    if (/mini\s*knot/i.test(section.label)) hasMiniKnots = true
    for (const row of section.rows) {
      for (const col of row.columns) {
        for (const s of col.segments) {
          if (s.type === 'connect') hasConnect = true
          if (s.type === 'mini-knots') hasMiniKnots = true
        }
      }
    }
    if (/newsletter/i.test(section.label)) {
      section.label = 'Stay connected'
    }
  }

  if (!hasMiniKnots) {
    layout.sections.push(
      section('Just Mini Knots', [
        headerSeg(
          'Handmade add-ons',
          'Just Mini Knots',
          'Crochet pieces and little extras — perfect to tuck into a custom hamper.',
        ),
        seg('mini-knots'),
      ], { theme: 'cream' }),
    )
  }

  if (!hasConnect) {
    const blogIdx = layout.sections.findIndex((s) => /blog/i.test(s.label))
    const insertAt = blogIdx >= 0 ? blogIdx : layout.sections.length
    layout.sections.splice(insertAt, 0, section('Stay connected', [seg('connect')]))
  }

  return layout
}

function normalizeSpacing(s?: HomeSpacing): HomeSpacing | undefined {
  if (!s || typeof s !== 'object') return undefined
  const top = clampNum(s.top, 0, 200)
  const right = clampNum(s.right, 0, 200)
  const bottom = clampNum(s.bottom, 0, 200)
  const left = clampNum(s.left, 0, 200)
  if (top === 0 && right === 0 && bottom === 0 && left === 0) return undefined
  return { top, right, bottom, left }
}

function clampNum(v: unknown, min: number, max: number): number {
  const n = Number(v)
  if (!Number.isFinite(n)) return min
  return Math.min(max, Math.max(min, Math.round(n)))
}

function normalizeStyle(s?: HomeBoxStyle): HomeBoxStyle | undefined {
  if (!s || typeof s !== 'object') return undefined
  const out: HomeBoxStyle = {}
  const pad = normalizeSpacing(s.padding)
  const mar = normalizeSpacing(s.margin)
  if (pad) out.padding = pad
  if (mar) out.margin = mar
  if (s.backgroundColor && typeof s.backgroundColor === 'string') out.backgroundColor = s.backgroundColor.slice(0, 32)
  if (s.backgroundImage && typeof s.backgroundImage === 'string') out.backgroundImage = s.backgroundImage.slice(0, 300)
  if (s.borderRadius != null) out.borderRadius = clampNum(s.borderRadius, 0, 64)
  if (s.borderWidth != null) out.borderWidth = clampNum(s.borderWidth, 0, 8)
  if (s.borderColor && typeof s.borderColor === 'string') out.borderColor = s.borderColor.slice(0, 32)
  if (s.boxShadow === 'sm' || s.boxShadow === 'md' || s.boxShadow === 'lg') out.boxShadow = s.boxShadow
  if (s.minHeight != null) out.minHeight = clampNum(s.minHeight, 0, 800)
  if (s.gap != null) out.gap = clampNum(s.gap, 0, 80)
  if (s.textAlign === 'center' || s.textAlign === 'right') out.textAlign = s.textAlign
  if (s.customClass && typeof s.customClass === 'string') out.customClass = s.customClass.slice(0, 80)
  if (s.hideOnMobile) out.hideOnMobile = true
  if (s.hideOnDesktop) out.hideOnDesktop = true
  return Object.keys(out).length ? out : undefined
}

function normalizeSection(s: HomeLayoutSection): HomeLayoutSection {
  const theme =
    s.theme === 'cream' || s.theme === 'dark' || s.theme === 'custom' ? s.theme : 'default'
  const container =
    s.container === 'full' || s.container === 'wide' || s.container === 'narrow' || s.container === 'normal'
      ? s.container
      : undefined
  return {
    id: s.id || hpId('sec'),
    label: s.label || 'Section',
    enabled: s.enabled !== false,
    theme,
    container,
    style: normalizeStyle(s.style),
    rows: (s.rows ?? []).map(normalizeRow).filter((r) => r.columns.length > 0),
  }
}

function normalizeRow(r: HomeLayoutRow): HomeLayoutRow {
  const columns = (r.columns ?? [])
    .map(normalizeColumn)
    .filter((c) => c.segments.length > 0)
  const spanTotal = columns.reduce((n, c) => n + c.span, 0)
  if (spanTotal !== 12 && columns.length === 1) {
    columns[0].span = 12
  }
  return { id: r.id || hpId('row'), style: normalizeStyle(r.style), columns }
}

function normalizeColumn(c: HomeLayoutColumn): HomeLayoutColumn {
  const span = ([3, 4, 6, 8, 12] as const).includes(c.span as HomeColumnSpan)
    ? (c.span as HomeColumnSpan)
    : 12
  const valign = c.valign === 'center' || c.valign === 'bottom' ? c.valign : undefined
  return {
    id: c.id || hpId('col'),
    span,
    valign,
    style: normalizeStyle(c.style),
    segments: (c.segments ?? []).filter((s) => s?.type),
  }
}

export const SEGMENT_CATALOG: {
  type: HomeSegmentType
  label: string
  description: string
  group: 'content' | 'products' | 'marketing' | 'layout'
}[] = [
  { type: 'hero', label: 'Hero carousel', description: 'Full-width hero with slides', group: 'content' },
  { type: 'trust', label: 'Trust strip', description: 'Delivery & quality badges', group: 'content' },
  { type: 'section-header', label: 'Section heading', description: 'Eyebrow, title, description, optional CTA', group: 'layout' },
  { type: 'category-grid', label: 'Category tiles', description: 'Shop-by-category grid (from content pool)', group: 'content' },
  { type: 'personalise-grid', label: 'Custom bookmark', description: 'Link to bookmark personalisation', group: 'content' },
  { type: 'create-set-promo', label: 'Create set / hamper', description: 'Bookmark set + build-a-hamper promo cards', group: 'content' },
  { type: 'product-grid', label: 'Product grid', description: 'Grid by category, bestsellers, or hand-picked IDs', group: 'products' },
  { type: 'product-carousel', label: 'Product carousel', description: 'Horizontal scroll of products', group: 'products' },
  { type: 'how-it-works', label: 'How it works', description: '3-step explainer', group: 'content' },
  { type: 'sale-countdown', label: 'Sale countdown', description: 'Promo timer banner', group: 'marketing' },
  { type: 'connect', label: 'WhatsApp & social', description: 'Channel link and social handles', group: 'marketing' },
  { type: 'mini-knots', label: 'Just Mini Knots', description: 'Crochet hamper add-ons', group: 'content' },
  { type: 'newsletter', label: 'Newsletter signup', description: 'Email capture block (legacy)', group: 'marketing' },
  { type: 'blog-teaser', label: 'Blog posts', description: 'Latest articles teaser', group: 'content' },
  { type: 'banner', label: 'Image banner', description: 'Single clickable promo image', group: 'marketing' },
  { type: 'spacer', label: 'Spacer', description: 'Vertical breathing room', group: 'layout' },
  { type: 'html', label: 'Custom HTML', description: 'Embed code or custom markup', group: 'layout' },
]

export const COLUMN_SPAN_OPTIONS: HomeColumnSpan[] = [3, 4, 6, 8, 12]

export function rowSpanTotal(r: HomeLayoutRow): number {
  return r.columns.reduce((n, c) => n + c.span, 0)
}

export function moveItem<T>(list: T[], from: number, to: number): T[] {
  if (from === to || from < 0 || to < 0 || from >= list.length || to >= list.length) return list
  const next = [...list]
  const [item] = next.splice(from, 1)
  next.splice(to, 0, item)
  return next
}

export function createSegment(type: HomeSegmentType): HomeLayoutSegment {
  switch (type) {
    case 'section-header':
      return {
        id: hpId('seg'),
        type: 'section-header',
        eyebrow: 'Eyebrow',
        title: 'Section title',
        description: '',
        align: 'left',
      }
    case 'product-grid':
      return productGrid('bestsellers', { limit: 8, columns: 4 })
    case 'product-carousel':
      return productCarousel('category', { category: 'bookmarks', limit: 12 })
    case 'sale-countdown':
      return seg('sale-countdown', {
        endAt: new Date(Date.now() + 7 * 86400000).toISOString(),
        headline: 'Limited-time offers',
        subheadline: 'Extra savings on bookmarks & gift sets',
      })
    case 'blog-teaser':
      return seg('blog-teaser', { limit: 3 })
    case 'connect':
      return seg('connect')
    case 'mini-knots':
      return seg('mini-knots')
    case 'banner':
      return seg('banner', { image: '', href: '/shop', alt: 'Promo' })
    case 'spacer':
      return seg('spacer', { height: 'md' })
    case 'html':
      return seg('html', { html: '<p>Custom content</p>' })
    default:
      return seg(type)
  }
}
