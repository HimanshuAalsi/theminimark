import type { HomeBoxStyle, HomeContainerWidth, HomeSpacing } from '@/types/homePageLayout'

const MAX_W: Record<HomeContainerWidth, string> = {
  full: '100%',
  wide: 'min(100%, 90rem)',
  normal: 'min(100%, 72rem)',
  narrow: 'min(100%, 56rem)',
}

const SHADOW: Record<NonNullable<HomeBoxStyle['boxShadow']>, string> = {
  none: 'none',
  sm: '0 2px 8px rgba(15, 23, 42, 0.06)',
  md: '0 8px 24px rgba(15, 23, 42, 0.1)',
  lg: '0 16px 48px rgba(15, 23, 42, 0.14)',
}

function spacingCss(s?: HomeSpacing, prefix = 'padding'): Record<string, string> {
  if (!s) return {}
  const t = s.top ?? 0
  const r = s.right ?? t
  const b = s.bottom ?? t
  const l = s.left ?? r
  if (t === 0 && r === 0 && b === 0 && l === 0) return {}
  return { [prefix]: `${t}px ${r}px ${b}px ${l}px` }
}

export function boxStyleToCss(style?: HomeBoxStyle): Record<string, string> {
  if (!style) return {}
  const css: Record<string, string> = {
    ...spacingCss(style.padding, 'padding'),
    ...spacingCss(style.margin, 'margin'),
  }
  if (style.backgroundColor) css.backgroundColor = style.backgroundColor
  if (style.backgroundImage) {
    css.backgroundImage = `url(${style.backgroundImage})`
    css.backgroundSize = 'cover'
    css.backgroundPosition = 'center'
  }
  if (style.borderRadius != null && style.borderRadius > 0) {
    css.borderRadius = `${style.borderRadius}px`
  }
  if (style.borderWidth != null && style.borderWidth > 0) {
    css.borderWidth = `${style.borderWidth}px`
    css.borderStyle = 'solid'
    css.borderColor = style.borderColor || 'rgba(15,23,42,0.12)'
  }
  if (style.boxShadow && style.boxShadow !== 'none') css.boxShadow = SHADOW[style.boxShadow]
  if (style.minHeight != null && style.minHeight > 0) css.minHeight = `${style.minHeight}px`
  if (style.gap != null && style.gap > 0) css.gap = `${style.gap}px`
  if (style.textAlign) css.textAlign = style.textAlign
  return css
}

export function containerWidthCss(w?: HomeContainerWidth): Record<string, string> {
  /* Default: let .tm-container use --site-width + padding from theme.css */
  if (!w || w === 'normal') return {}
  if (w === 'full') return { maxWidth: 'none' }
  return { maxWidth: MAX_W[w], marginInline: 'auto' }
}

export function styleClass(style?: HomeBoxStyle): string[] {
  const c: string[] = []
  if (style?.customClass) c.push(style.customClass)
  if (style?.hideOnMobile) c.push('hp-hide-mobile')
  if (style?.hideOnDesktop) c.push('hp-hide-desktop')
  return c
}

export function emptySpacing(): HomeSpacing {
  return { top: 0, right: 0, bottom: 0, left: 0 }
}

export const PRESET_BACKGROUNDS = [
  { label: 'White', value: '#ffffff' },
  { label: 'Cream', value: '#faf6f0' },
  { label: 'Light gray', value: '#f1f5f9' },
  { label: 'Mint', value: '#ecfdf5' },
  { label: 'Dark', value: '#0f172a' },
  { label: 'Teal', value: '#0f766e' },
]

export const CONTAINER_OPTIONS: { v: HomeContainerWidth; l: string }[] = [
  { v: 'full', l: 'Full width' },
  { v: 'wide', l: 'Wide (1440px)' },
  { v: 'normal', l: 'Normal (1152px)' },
  { v: 'narrow', l: 'Narrow (896px)' },
]

/** Strip scripts and inline event handlers from custom HTML blocks */
export function sanitizeHtml(html: string): string {
  return html
    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
    .replace(/\son\w+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi, '')
}
