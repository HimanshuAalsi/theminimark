/** Spacing & visual style — Elementor-style box model */

export type HomeContainerWidth = 'full' | 'wide' | 'normal' | 'narrow'
export type HomeSectionTheme = 'default' | 'cream' | 'dark' | 'custom'
export type HomeColumnSpan = 3 | 4 | 6 | 8 | 12

export interface HomeSpacing {
  top?: number
  right?: number
  bottom?: number
  left?: number
}

export interface HomeBoxStyle {
  padding?: HomeSpacing
  margin?: HomeSpacing
  backgroundColor?: string
  backgroundImage?: string
  backgroundOverlay?: string
  borderRadius?: number
  borderWidth?: number
  borderColor?: string
  boxShadow?: 'none' | 'sm' | 'md' | 'lg'
  minHeight?: number
  gap?: number
  textAlign?: 'left' | 'center' | 'right'
  customClass?: string
  hideOnMobile?: boolean
  hideOnDesktop?: boolean
}

export type HomeSegmentType =
  | 'hero'
  | 'trust'
  | 'section-header'
  | 'category-grid'
  | 'personalise-grid'
  | 'create-set-promo'
  | 'product-grid'
  | 'product-carousel'
  | 'how-it-works'
  | 'sale-countdown'
  | 'newsletter'
  | 'connect'
  | 'mini-knots'
  | 'blog-teaser'
  | 'banner'
  | 'spacer'
  | 'html'

export type HomeProductSource =
  | 'bestsellers'
  | 'magnetic'
  | 'secondary'
  | 'category'
  | 'custom'
  | 'sale'

export interface HomeSegmentCta {
  label: string
  to: string
}

export interface HomeStyled {
  style?: HomeBoxStyle
}

export interface HomeSectionHeaderSegment extends HomeStyled {
  id: string
  type: 'section-header'
  eyebrow?: string
  title?: string
  description?: string
  align?: 'left' | 'center'
  titleSize?: 'sm' | 'md' | 'lg' | 'xl'
  cta?: HomeSegmentCta
}

export interface HomeProductSegmentBase extends HomeStyled {
  id: string
  source: HomeProductSource
  category?: string
  subcategory?: string
  productIds?: string[]
  limit?: number
  columns?: 3 | 4 | 5
  viewAllLabel?: string
  viewAllTo?: string
}

export interface HomeProductGridSegment extends HomeProductSegmentBase {
  type: 'product-grid'
}

export interface HomeProductCarouselSegment extends HomeProductSegmentBase {
  type: 'product-carousel'
}

export interface HomeSimpleSegment extends HomeStyled {
  id: string
  type: Exclude<
    HomeSegmentType,
    'section-header' | 'product-grid' | 'product-carousel'
  >
  image?: string
  href?: string
  alt?: string
  endAt?: string
  headline?: string
  subheadline?: string
  limit?: number
  height?: 'sm' | 'md' | 'lg'
  /** html block */
  html?: string
}

export type HomeLayoutSegment =
  | HomeSectionHeaderSegment
  | HomeProductGridSegment
  | HomeProductCarouselSegment
  | HomeSimpleSegment

export interface HomeLayoutColumn extends HomeStyled {
  id: string
  span: HomeColumnSpan
  valign?: 'top' | 'center' | 'bottom'
  segments: HomeLayoutSegment[]
}

export interface HomeLayoutRow extends HomeStyled {
  id: string
  columns: HomeLayoutColumn[]
}

export interface HomeLayoutSection extends HomeStyled {
  id: string
  label: string
  enabled: boolean
  theme: HomeSectionTheme
  container?: HomeContainerWidth
  rows: HomeLayoutRow[]
}

export interface HomeLayoutPreset {
  id: string
  name: string
  kind: 'section' | 'row' | 'segment'
  payload: HomeLayoutSection | HomeLayoutRow | HomeLayoutSegment
}

export interface HomePageLayout {
  version: 1
  sections: HomeLayoutSection[]
  presets?: HomeLayoutPreset[]
}

export type BuilderSelectTarget =
  | { level: 'section'; sectionId: string }
  | { level: 'row'; sectionId: string; rowId: string }
  | { level: 'column'; sectionId: string; rowId: string; colId: string }
  | { level: 'segment'; sectionId: string; rowId: string; colId: string; segId: string }
