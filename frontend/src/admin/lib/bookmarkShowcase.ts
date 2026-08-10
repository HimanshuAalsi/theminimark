/** Client-side bookmark product mockups — flat art → photorealistic showcase images */

export interface ShowcaseVariationMeta {
  id: string
  name: string
  description: string
}

export interface ShowcaseOptions {
  /** Rounded bottom corner radius as % of bookmark width (2–18) */
  cornerRadiusPct?: number
  /** Square output size in px */
  outputSize?: number
}

export interface ShowcaseResult extends ShowcaseVariationMeta {
  canvas: HTMLCanvasElement
  dataUrl: string
}

export const BOOKMARK_VARIATIONS: ShowcaseVariationMeta[] = [
  {
    id: 'studio-clean',
    name: 'Studio White',
    description: 'E-commerce hero shot — infinity cove, soft floor shadow, subtle reflection.',
  },
  {
    id: 'angled-hero',
    name: 'Marble Flat Lay',
    description: 'Natural stone surface with directional light and realistic cast shadow.',
  },
  {
    id: 'open-book',
    name: 'Reading Moment',
    description: 'Open hardcover with bookmark tucked in — warm ambient light and bokeh.',
  },
  {
    id: 'desk-cozy',
    name: 'Walnut Desk',
    description: 'Lifestyle flat lay on rich wood with ceramic mug and window light.',
  },
  {
    id: 'floating-minimal',
    name: 'Soft Minimal',
    description: 'Muted neutral gradient with tight contact shadow — modern DTC look.',
  },
]

const DEFAULT_SIZE = 1600

export async function loadImageFromFile(file: File): Promise<HTMLImageElement> {
  const url = URL.createObjectURL(file)
  try {
    return await loadImage(url)
  } finally {
    URL.revokeObjectURL(url)
  }
}

export async function loadImage(src: string): Promise<HTMLImageElement> {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve(img)
    img.onerror = () => reject(new Error('Could not load image'))
    img.src = src
  })
}

export function generateAllShowcases(
  img: HTMLImageElement,
  options: ShowcaseOptions = {},
): ShowcaseResult[] {
  const size = options.outputSize ?? DEFAULT_SIZE
  const cornerRadiusPct = clamp(options.cornerRadiusPct ?? 8, 2, 18)

  const renderers: Record<string, (c: CanvasRenderingContext2D, s: number) => void> = {
    'studio-clean': (ctx, s) => renderStudioClean(ctx, s, img, cornerRadiusPct),
    'angled-hero': (ctx, s) => renderAngledHero(ctx, s, img, cornerRadiusPct),
    'open-book': (ctx, s) => renderOpenBook(ctx, s, img, cornerRadiusPct),
    'desk-cozy': (ctx, s) => renderDeskCozy(ctx, s, img, cornerRadiusPct),
    'floating-minimal': (ctx, s) => renderFloatingMinimal(ctx, s, img, cornerRadiusPct),
  }

  return BOOKMARK_VARIATIONS.map((meta) => {
    const canvas = createCanvas(size, size)
    const ctx = canvas.getContext('2d')
    if (!ctx) throw new Error('Canvas not supported')
    renderers[meta.id](ctx, size)
    return {
      ...meta,
      canvas,
      dataUrl: canvas.toDataURL('image/jpeg', 0.93),
    }
  })
}

function createCanvas(w: number, h: number): HTMLCanvasElement {
  const canvas = document.createElement('canvas')
  canvas.width = w
  canvas.height = h
  return canvas
}

function clamp(n: number, min: number, max: number): number {
  return Math.min(max, Math.max(min, n))
}

function bookmarkDimensions(img: HTMLImageElement, canvasSize: number, heightRatio: number) {
  const h = canvasSize * heightRatio
  const w = h * (img.naturalWidth / img.naturalHeight)
  return { w, h }
}

function cornerRadius(w: number, pct: number): number {
  return w * (pct / 100)
}

function bookmarkClipPath(
  ctx: CanvasRenderingContext2D,
  x: number,
  y: number,
  w: number,
  h: number,
  r: number,
) {
  const br = Math.min(r, w / 2, h * 0.2)
  ctx.beginPath()
  ctx.moveTo(x, y)
  ctx.lineTo(x + w, y)
  ctx.lineTo(x + w, y + h - br)
  ctx.quadraticCurveTo(x + w, y + h, x + w - br, y + h)
  ctx.lineTo(x + br, y + h)
  ctx.quadraticCurveTo(x, y + h, x, y + h - br)
  ctx.closePath()
}

function fillLinearGradient(
  ctx: CanvasRenderingContext2D,
  w: number,
  h: number,
  stops: [number, string][],
  angleDeg = 135,
) {
  const rad = (angleDeg * Math.PI) / 180
  const x1 = w / 2 - (Math.cos(rad) * w) / 2
  const y1 = h / 2 - (Math.sin(rad) * h) / 2
  const x2 = w / 2 + (Math.cos(rad) * w) / 2
  const y2 = h / 2 + (Math.sin(rad) * h) / 2
  const g = ctx.createLinearGradient(x1, y1, x2, y2)
  for (const [pos, color] of stops) g.addColorStop(pos, color)
  ctx.fillStyle = g
  ctx.fillRect(0, 0, w, h)
}

function roundRect(
  ctx: CanvasRenderingContext2D,
  x: number,
  y: number,
  w: number,
  h: number,
  r: number,
) {
  const rad = Math.min(r, w / 2, h / 2)
  ctx.beginPath()
  ctx.moveTo(x + rad, y)
  ctx.lineTo(x + w - rad, y)
  ctx.quadraticCurveTo(x + w, y, x + w, y + rad)
  ctx.lineTo(x + w, y + h - rad)
  ctx.quadraticCurveTo(x + w, y + h, x + w - rad, y + h)
  ctx.lineTo(x + rad, y + h)
  ctx.quadraticCurveTo(x, y + h, x, y + h - rad)
  ctx.lineTo(x, y + rad)
  ctx.quadraticCurveTo(x, y, x + rad, y)
  ctx.closePath()
}

/** Layered ground shadow — contact + ambient */
function drawGroundShadow(
  ctx: CanvasRenderingContext2D,
  cx: number,
  baseY: number,
  rx: number,
  ry: number,
  alpha = 0.35,
  angleDeg = 0,
) {
  ctx.save()
  ctx.translate(cx, baseY)
  ctx.rotate((angleDeg * Math.PI) / 180)

  const contact = ctx.createRadialGradient(0, 0, 0, 0, 0, rx)
  contact.addColorStop(0, `rgba(15, 18, 24, ${alpha * 0.9})`)
  contact.addColorStop(0.45, `rgba(15, 18, 24, ${alpha * 0.35})`)
  contact.addColorStop(1, 'rgba(15, 18, 24, 0)')
  ctx.fillStyle = contact
  ctx.beginPath()
  ctx.ellipse(0, 0, rx, ry, 0, 0, Math.PI * 2)
  ctx.fill()

  const ambient = ctx.createRadialGradient(0, ry * 0.3, 0, 0, ry * 0.3, rx * 1.6)
  ambient.addColorStop(0, `rgba(15, 18, 24, ${alpha * 0.25})`)
  ambient.addColorStop(1, 'rgba(15, 18, 24, 0)')
  ctx.fillStyle = ambient
  ctx.beginPath()
  ctx.ellipse(0, ry * 0.5, rx * 1.35, ry * 1.8, 0, 0, Math.PI * 2)
  ctx.fill()

  ctx.restore()
}

/** Subtle laminate + paper finish on top of artwork */
function drawBookmarkFinish(
  ctx: CanvasRenderingContext2D,
  x: number,
  y: number,
  w: number,
  h: number,
  r: number,
  lightAngle = -35,
) {
  ctx.save()
  bookmarkClipPath(ctx, x, y, w, h, r)
  ctx.clip()

  const rad = (lightAngle * Math.PI) / 180
  const gloss = ctx.createLinearGradient(
    x + Math.cos(rad) * w,
    y + Math.sin(rad) * h,
    x - Math.cos(rad) * w * 0.5,
    y - Math.sin(rad) * h * 0.5,
  )
  gloss.addColorStop(0, 'rgba(255, 255, 255, 0.38)')
  gloss.addColorStop(0.35, 'rgba(255, 255, 255, 0.08)')
  gloss.addColorStop(0.55, 'rgba(255, 255, 255, 0)')
  gloss.addColorStop(1, 'rgba(0, 0, 0, 0.04)')
  ctx.fillStyle = gloss
  ctx.fillRect(x, y, w, h)

  const grain = ctx.createLinearGradient(x, y, x + w, y + h)
  grain.addColorStop(0, 'rgba(255,255,255,0.03)')
  grain.addColorStop(0.5, 'rgba(0,0,0,0.02)')
  grain.addColorStop(1, 'rgba(255,255,255,0.02)')
  ctx.fillStyle = grain
  ctx.fillRect(x, y, w, h)

  const bottomShade = ctx.createLinearGradient(x, y + h * 0.7, x, y + h)
  bottomShade.addColorStop(0, 'rgba(0,0,0,0)')
  bottomShade.addColorStop(1, 'rgba(0,0,0,0.06)')
  ctx.fillStyle = bottomShade
  ctx.fillRect(x, y, w, h)

  ctx.restore()

  ctx.save()
  bookmarkClipPath(ctx, x, y, w, h, r)
  ctx.strokeStyle = 'rgba(255, 255, 255, 0.55)'
  ctx.lineWidth = Math.max(1, w * 0.004)
  ctx.stroke()
  ctx.strokeStyle = 'rgba(15, 18, 24, 0.07)'
  ctx.lineWidth = Math.max(1.2, w * 0.005)
  ctx.stroke()
  ctx.restore()
}

type BookmarkDrawOpts = {
  shadow?: { blur: number; ox: number; oy: number; color: string }
  lightAngle?: number
  groundShadow?: { cx: number; baseY: number; rx: number; ry: number; alpha?: number; angle?: number }
}

function drawBookmark(
  ctx: CanvasRenderingContext2D,
  img: HTMLImageElement,
  x: number,
  y: number,
  w: number,
  h: number,
  radiusPct: number,
  opts: BookmarkDrawOpts = {},
) {
  const r = cornerRadius(w, radiusPct)
  const shadow = opts.shadow ?? { blur: 36, ox: 4, oy: 18, color: 'rgba(15, 18, 24, 0.28)' }

  if (opts.groundShadow) {
    const gs = opts.groundShadow
    drawGroundShadow(ctx, gs.cx, gs.baseY, gs.rx, gs.ry, gs.alpha ?? 0.32, gs.angle ?? 0)
  }

  ctx.save()
  ctx.shadowColor = shadow.color
  ctx.shadowBlur = shadow.blur
  ctx.shadowOffsetX = shadow.ox
  ctx.shadowOffsetY = shadow.oy
  bookmarkClipPath(ctx, x, y, w, h, r)
  ctx.fillStyle = '#faf9f7'
  ctx.fill()
  ctx.restore()

  ctx.save()
  bookmarkClipPath(ctx, x, y, w, h, r)
  ctx.clip()
  ctx.drawImage(img, x, y, w, h)
  ctx.restore()

  drawBookmarkFinish(ctx, x, y, w, h, r, opts.lightAngle ?? -35)
}

function drawBookmarkRotated(
  ctx: CanvasRenderingContext2D,
  img: HTMLImageElement,
  cx: number,
  cy: number,
  w: number,
  h: number,
  radiusPct: number,
  angleDeg: number,
  opts?: BookmarkDrawOpts,
) {
  ctx.save()
  ctx.translate(cx, cy)
  ctx.rotate((angleDeg * Math.PI) / 180)
  drawBookmark(ctx, img, -w / 2, -h / 2, w, h, radiusPct, opts)
  ctx.restore()
}

function drawFilmGrain(ctx: CanvasRenderingContext2D, size: number, opacity = 0.035) {
  ctx.save()
  ctx.globalAlpha = opacity
  for (let i = 0; i < 2800; i++) {
    const px = (Math.sin(i * 12.9898) * 43758.5453) % 1
    const py = (Math.sin(i * 78.233) * 43758.5453) % 1
    const x = ((px < 0 ? -px : px) * size) | 0
    const y = ((py < 0 ? -py : py) * size) | 0
    const v = (Math.sin(i * 4.141) * 0.5 + 0.5) * 255
    ctx.fillStyle = `rgb(${v | 0},${v | 0},${v | 0})`
    ctx.fillRect(x, y, 1, 1)
  }
  ctx.restore()
}

function drawStudioCyclorama(ctx: CanvasRenderingContext2D, size: number) {
  fillLinearGradient(ctx, size, size, [
    [0, '#ffffff'],
    [0.42, '#f7f8fa'],
    [0.72, '#eceef2'],
    [1, '#dfe2e8'],
  ], 180)

  const floor = ctx.createLinearGradient(0, size * 0.58, 0, size)
  floor.addColorStop(0, 'rgba(200, 206, 216, 0)')
  floor.addColorStop(0.35, 'rgba(180, 188, 200, 0.18)')
  floor.addColorStop(1, 'rgba(160, 168, 180, 0.32)')
  ctx.fillStyle = floor
  ctx.fillRect(0, size * 0.55, size, size * 0.45)

  const key = ctx.createRadialGradient(size * 0.38, size * 0.22, 0, size * 0.42, size * 0.28, size * 0.75)
  key.addColorStop(0, 'rgba(255, 255, 255, 0.95)')
  key.addColorStop(0.55, 'rgba(255, 255, 255, 0.15)')
  key.addColorStop(1, 'rgba(255, 255, 255, 0)')
  ctx.fillStyle = key
  ctx.fillRect(0, 0, size, size)

  ctx.save()
  const vig = ctx.createRadialGradient(size / 2, size / 2, size * 0.35, size / 2, size / 2, size * 0.78)
  vig.addColorStop(0, 'rgba(0,0,0,0)')
  vig.addColorStop(1, 'rgba(30, 35, 45, 0.06)')
  ctx.fillStyle = vig
  ctx.fillRect(0, 0, size, size)
  ctx.restore()
}

function drawBookmarkReflection(
  ctx: CanvasRenderingContext2D,
  img: HTMLImageElement,
  x: number,
  y: number,
  w: number,
  h: number,
  radiusPct: number,
  gap: number,
) {
  const r = cornerRadius(w, radiusPct)
  const reflectY = y + h + gap

  ctx.save()
  ctx.translate(x, reflectY + h)
  ctx.scale(1, -1)
  ctx.globalAlpha = 0.11
  bookmarkClipPath(ctx, 0, 0, w, h, r)
  ctx.clip()
  const fade = ctx.createLinearGradient(0, 0, 0, h)
  fade.addColorStop(0, 'rgba(255,255,255,0.85)')
  fade.addColorStop(0.6, 'rgba(255,255,255,0.2)')
  fade.addColorStop(1, 'rgba(255,255,255,0)')
  ctx.drawImage(img, 0, 0, w, h)
  ctx.globalCompositeOperation = 'destination-in'
  ctx.fillStyle = fade
  ctx.fillRect(0, 0, w, h)
  ctx.restore()
}

function drawMarbleSurface(ctx: CanvasRenderingContext2D, size: number) {
  fillLinearGradient(ctx, size, size, [
    [0, '#f5f2ee'],
    [0.5, '#ebe6df'],
    [1, '#ddd6cc'],
  ], 155)

  ctx.save()
  ctx.globalAlpha = 0.22
  ctx.strokeStyle = '#b8aea2'
  ctx.lineWidth = size * 0.004
  for (let i = 0; i < 9; i++) {
    ctx.beginPath()
    const startX = size * (-0.05 + i * 0.12)
    ctx.moveTo(startX, size * 0.05)
    for (let t = 0; t <= 1; t += 0.08) {
      const px = startX + t * size * 1.1 + Math.sin(t * 8 + i) * size * 0.03
      const py = size * 0.05 + t * size * 0.92 + Math.cos(t * 6 + i * 1.3) * size * 0.025
      ctx.lineTo(px, py)
    }
    ctx.stroke()
  }
  ctx.restore()

  const light = ctx.createRadialGradient(size * 0.28, size * 0.18, 0, size * 0.32, size * 0.22, size * 0.62)
  light.addColorStop(0, 'rgba(255, 252, 248, 0.55)')
  light.addColorStop(1, 'rgba(255, 252, 248, 0)')
  ctx.fillStyle = light
  ctx.fillRect(0, 0, size, size)
}

function drawBokeh(ctx: CanvasRenderingContext2D, size: number, count: number, warm = true) {
  for (let i = 0; i < count; i++) {
    const t = i / count
    const bx = size * (0.08 + ((Math.sin(i * 2.17) * 0.5 + 0.5) * 0.84))
    const by = size * (0.06 + ((Math.cos(i * 3.41) * 0.5 + 0.5) * 0.55))
    const br = size * (0.04 + (Math.sin(i * 1.73) * 0.5 + 0.5) * 0.09)
    const g = ctx.createRadialGradient(bx, by, 0, bx, by, br)
    if (warm) {
      g.addColorStop(0, `rgba(255, 210, 160, ${0.12 + t * 0.08})`)
      g.addColorStop(1, 'rgba(255, 210, 160, 0)')
    } else {
      g.addColorStop(0, `rgba(180, 200, 255, ${0.08 + t * 0.06})`)
      g.addColorStop(1, 'rgba(180, 200, 255, 0)')
    }
    ctx.fillStyle = g
    ctx.beginPath()
    ctx.arc(bx, by, br, 0, Math.PI * 2)
    ctx.fill()
  }
}

function drawPageText(ctx: CanvasRenderingContext2D, x: number, y: number, w: number, h: number, lines: number) {
  const lineH = h / (lines + 2)
  for (let i = 0; i < lines; i++) {
    const ly = y + lineH * (i + 1.2)
    const lw = w * (0.55 + (Math.sin(i * 2.1) * 0.5 + 0.5) * 0.38)
    ctx.fillStyle = i % 5 === 0 ? 'rgba(30, 35, 45, 0.14)' : 'rgba(30, 35, 45, 0.07)'
    roundRect(ctx, x, ly, lw, lineH * 0.38, lineH * 0.12)
    ctx.fill()
  }
}

function drawOpenBookScene(ctx: CanvasRenderingContext2D, size: number) {
  const bookW = size * 0.78
  const bookH = size * 0.42
  const bookX = (size - bookW) / 2
  const bookY = size * 0.54
  const spineX = size / 2

  drawGroundShadow(ctx, size / 2, bookY + bookH + size * 0.02, bookW * 0.44, size * 0.018, 0.45)

  roundRect(ctx, bookX - size * 0.012, bookY - size * 0.008, bookW + size * 0.024, bookH + size * 0.016, size * 0.014)
  ctx.fillStyle = '#3d2a1f'
  ctx.fill()

  const pagePad = size * 0.022

  ctx.save()
  ctx.beginPath()
  ctx.moveTo(bookX + pagePad, bookY + pagePad * 0.6)
  ctx.lineTo(spineX - size * 0.008, bookY + pagePad * 0.45)
  ctx.lineTo(spineX - size * 0.008, bookY + bookH - pagePad)
  ctx.lineTo(bookX + pagePad, bookY + bookH - pagePad * 0.7)
  ctx.closePath()
  const leftPage = ctx.createLinearGradient(bookX, bookY, spineX, bookY)
  leftPage.addColorStop(0, '#f0ebe3')
  leftPage.addColorStop(1, '#e8e2d8')
  ctx.fillStyle = leftPage
  ctx.fill()
  drawPageText(ctx, bookX + pagePad * 1.8, bookY + pagePad, (spineX - bookX) - pagePad * 2.5, bookH - pagePad * 2, 7)
  ctx.restore()

  ctx.save()
  ctx.beginPath()
  ctx.moveTo(spineX + size * 0.008, bookY + pagePad * 0.45)
  ctx.lineTo(bookX + bookW - pagePad, bookY + pagePad * 0.6)
  ctx.lineTo(bookX + bookW - pagePad, bookY + bookH - pagePad * 0.7)
  ctx.lineTo(spineX + size * 0.008, bookY + bookH - pagePad)
  ctx.closePath()
  const rightPage = ctx.createLinearGradient(spineX, bookY, bookX + bookW, bookY)
  rightPage.addColorStop(0, '#faf7f2')
  rightPage.addColorStop(1, '#f3efe8')
  ctx.fillStyle = rightPage
  ctx.fill()
  drawPageText(ctx, spineX + pagePad, bookY + pagePad, (bookX + bookW - spineX) - pagePad * 2.2, bookH - pagePad * 2, 8)
  ctx.restore()

  const gutter = ctx.createLinearGradient(spineX - size * 0.02, bookY, spineX + size * 0.02, bookY)
  gutter.addColorStop(0, 'rgba(0,0,0,0)')
  gutter.addColorStop(0.5, 'rgba(20, 15, 10, 0.28)')
  gutter.addColorStop(1, 'rgba(0,0,0,0)')
  ctx.fillStyle = gutter
  ctx.fillRect(spineX - size * 0.015, bookY + pagePad * 0.5, size * 0.03, bookH - pagePad)

  ctx.save()
  ctx.strokeStyle = 'rgba(255,255,255,0.35)'
  ctx.lineWidth = 1
  ctx.beginPath()
  ctx.moveTo(bookX + pagePad, bookY + pagePad * 0.65)
  ctx.quadraticCurveTo(spineX, bookY + pagePad * 0.5, bookX + bookW - pagePad, bookY + pagePad * 0.65)
  ctx.stroke()
  ctx.restore()

  return { bookY, spineX }
}

function drawWalnutWood(ctx: CanvasRenderingContext2D, size: number) {
  fillLinearGradient(ctx, size, size, [
    [0, '#5c4033'],
    [0.45, '#6b4c3b'],
    [1, '#4a3328'],
  ], 168)

  for (let i = 0; i < 28; i++) {
    const y = size * (0.08 + i * 0.034)
    ctx.strokeStyle = `rgba(0,0,0,${0.03 + (i % 4) * 0.012})`
    ctx.lineWidth = 1 + (i % 3)
    ctx.beginPath()
    ctx.moveTo(-size * 0.05, y)
    ctx.bezierCurveTo(size * 0.25, y + size * 0.008, size * 0.55, y - size * 0.006, size * 1.05, y + size * 0.012)
    ctx.stroke()
  }

  ctx.save()
  ctx.globalAlpha = 0.14
  const knot = ctx.createRadialGradient(size * 0.72, size * 0.82, 0, size * 0.72, size * 0.82, size * 0.08)
  knot.addColorStop(0, '#2a1c16')
  knot.addColorStop(1, 'rgba(42,28,22,0)')
  ctx.fillStyle = knot
  ctx.beginPath()
  ctx.ellipse(size * 0.72, size * 0.82, size * 0.07, size * 0.04, 0.3, 0, Math.PI * 2)
  ctx.fill()
  ctx.restore()
}

function drawCeramicMug(ctx: CanvasRenderingContext2D, cx: number, cy: number, scale: number) {
  const r = 38 * scale
  ctx.save()
  drawGroundShadow(ctx, cx, cy + r * 0.95, r * 0.85, r * 0.12, 0.28)

  const body = ctx.createLinearGradient(cx - r, cy - r, cx + r, cy + r)
  body.addColorStop(0, '#f8f6f3')
  body.addColorStop(0.45, '#ece8e2')
  body.addColorStop(1, '#d8d2c9')
  ctx.fillStyle = body
  ctx.beginPath()
  ctx.ellipse(cx, cy, r * 0.92, r, 0, 0, Math.PI * 2)
  ctx.fill()

  ctx.fillStyle = '#c4a882'
  ctx.beginPath()
  ctx.ellipse(cx, cy - r * 0.55, r * 0.72, r * 0.22, 0, 0, Math.PI * 2)
  ctx.fill()

  ctx.strokeStyle = '#c9c3ba'
  ctx.lineWidth = r * 0.14
  ctx.lineCap = 'round'
  ctx.beginPath()
  ctx.arc(cx + r * 0.95, cy - r * 0.05, r * 0.55, Math.PI * 0.55, Math.PI * 1.45)
  ctx.stroke()

  ctx.fillStyle = 'rgba(255,255,255,0.45)'
  ctx.beginPath()
  ctx.ellipse(cx - r * 0.28, cy - r * 0.35, r * 0.18, r * 0.35, -0.4, 0, Math.PI * 2)
  ctx.fill()
  ctx.restore()
}

function renderStudioClean(
  ctx: CanvasRenderingContext2D,
  size: number,
  img: HTMLImageElement,
  radiusPct: number,
) {
  drawStudioCyclorama(ctx, size)

  const { w, h } = bookmarkDimensions(img, size, 0.6)
  const x = (size - w) / 2
  const y = size * 0.2

  drawGroundShadow(ctx, size / 2, y + h + size * 0.012, w * 0.42, size * 0.014, 0.28)
  drawBookmark(ctx, img, x, y, w, h, radiusPct, {
    lightAngle: -40,
    shadow: { blur: 38, ox: 0, oy: 20, color: 'rgba(15, 18, 24, 0.2)' },
  })
  drawBookmarkReflection(ctx, img, x, y, w, h, radiusPct, size * 0.018)
  drawFilmGrain(ctx, size, 0.025)
}

function renderAngledHero(
  ctx: CanvasRenderingContext2D,
  size: number,
  img: HTMLImageElement,
  radiusPct: number,
) {
  drawMarbleSurface(ctx, size)

  const angle = -11
  const { w, h } = bookmarkDimensions(img, size, 0.56)
  const cx = size * 0.5
  const cy = size * 0.44

  drawGroundShadow(ctx, cx + size * 0.04, cy + h * 0.48, w * 0.46, size * 0.016, 0.38, angle)

  drawBookmarkRotated(ctx, img, cx, cy, w, h, radiusPct, angle, {
    lightAngle: -50,
    shadow: { blur: 52, ox: 22, oy: 26, color: 'rgba(45, 38, 32, 0.32)' },
  })
  drawFilmGrain(ctx, size, 0.028)
}

function renderOpenBook(
  ctx: CanvasRenderingContext2D,
  size: number,
  img: HTMLImageElement,
  radiusPct: number,
) {
  fillLinearGradient(ctx, size, size, [
    [0, '#2a3544'],
    [0.4, '#1a2230'],
    [1, '#0d1118'],
  ], 165)

  drawBokeh(ctx, size, 14, true)

  const lamp = ctx.createRadialGradient(size * 0.5, size * 0.08, 0, size * 0.5, size * 0.35, size * 0.55)
  lamp.addColorStop(0, 'rgba(255, 220, 170, 0.22)')
  lamp.addColorStop(0.55, 'rgba(255, 200, 140, 0.06)')
  lamp.addColorStop(1, 'rgba(255, 200, 140, 0)')
  ctx.fillStyle = lamp
  ctx.fillRect(0, 0, size, size)

  const { bookY } = drawOpenBookScene(ctx, size)

  const { w, h } = bookmarkDimensions(img, size, 0.5)
  const bx = size / 2 - w / 2
  const by = bookY - h + size * 0.1

  drawBookmark(ctx, img, bx, by, w, h, radiusPct, {
    lightAngle: -25,
    groundShadow: { cx: size / 2, baseY: bookY + size * 0.008, rx: w * 0.28, ry: size * 0.01, alpha: 0.35 },
    shadow: { blur: 24, ox: 0, oy: 12, color: 'rgba(0, 0, 0, 0.45)' },
  })
  drawFilmGrain(ctx, size, 0.04)
}

function renderDeskCozy(
  ctx: CanvasRenderingContext2D,
  size: number,
  img: HTMLImageElement,
  radiusPct: number,
) {
  drawWalnutWood(ctx, size)

  const window = ctx.createLinearGradient(0, 0, size * 0.55, size * 0.45)
  window.addColorStop(0, 'rgba(255, 235, 200, 0.42)')
  window.addColorStop(0.65, 'rgba(255, 220, 170, 0.08)')
  window.addColorStop(1, 'rgba(255, 220, 170, 0)')
  ctx.fillStyle = window
  ctx.fillRect(0, 0, size, size)

  ctx.save()
  ctx.globalAlpha = 0.35
  ctx.fillStyle = '#2a1f18'
  roundRect(ctx, size * 0.06, size * 0.08, size * 0.22, size * 0.28, size * 0.012)
  ctx.fill()
  ctx.restore()

  drawCeramicMug(ctx, size * 0.78, size * 0.62, size / 1600)

  const { w, h } = bookmarkDimensions(img, size, 0.52)
  const cx = size * 0.44
  const cy = size * 0.48
  const angle = 6

  drawGroundShadow(ctx, cx, cy + h * 0.46, w * 0.44, size * 0.015, 0.42, angle)
  drawBookmarkRotated(ctx, img, cx, cy, w, h, radiusPct, angle, {
    lightAngle: -55,
    shadow: { blur: 40, ox: 14, oy: 22, color: 'rgba(0, 0, 0, 0.42)' },
  })
  drawFilmGrain(ctx, size, 0.032)
}

function renderFloatingMinimal(
  ctx: CanvasRenderingContext2D,
  size: number,
  img: HTMLImageElement,
  radiusPct: number,
) {
  fillLinearGradient(ctx, size, size, [
    [0, '#f7f6f4'],
    [0.45, '#eeede9'],
    [1, '#e2e0db'],
  ], 140)

  const bloom = ctx.createRadialGradient(size * 0.62, size * 0.28, 0, size * 0.62, size * 0.28, size * 0.42)
  bloom.addColorStop(0, 'rgba(255, 255, 255, 0.85)')
  bloom.addColorStop(1, 'rgba(255, 255, 255, 0)')
  ctx.fillStyle = bloom
  ctx.fillRect(0, 0, size, size)

  const accent = ctx.createRadialGradient(size * 0.22, size * 0.78, 0, size * 0.22, size * 0.78, size * 0.35)
  accent.addColorStop(0, 'rgba(45, 92, 82, 0.06)')
  accent.addColorStop(1, 'rgba(45, 92, 82, 0)')
  ctx.fillStyle = accent
  ctx.fillRect(0, 0, size, size)

  const { w, h } = bookmarkDimensions(img, size, 0.56)
  const cx = size / 2
  const cy = size * 0.44

  drawGroundShadow(ctx, cx, cy + h * 0.5 + size * 0.008, w * 0.34, size * 0.011, 0.22)
  drawBookmarkRotated(ctx, img, cx, cy, w, h, radiusPct, 0, {
    lightAngle: -42,
    shadow: { blur: 44, ox: 0, oy: 26, color: 'rgba(30, 35, 45, 0.18)' },
  })
  drawFilmGrain(ctx, size, 0.022)
}

export function downloadShowcase(result: ShowcaseResult, filename?: string) {
  const a = document.createElement('a')
  a.href = result.dataUrl
  a.download = filename ?? `bookmark-${result.id}.jpg`
  a.click()
}

export async function downloadAllShowcases(results: ShowcaseResult[]) {
  for (let i = 0; i < results.length; i++) {
    downloadShowcase(results[i])
    if (i < results.length - 1) {
      await new Promise((r) => setTimeout(r, 350))
    }
  }
}
