import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { apiFetch, getApiBaseUrl } from '@/lib/api'
import { resolveProductImageUrl } from '@/lib/productImage'
import {
  announcement as defaultAnnouncement,
  categoryStrip as defaultCategoryStrip,
  heroSlides as defaultHeroSlides,
  howItWorks as defaultHowItWorksSteps,
  howItWorksIntro as defaultHowItWorksIntro,
  newsletterSection as defaultNewsletter,
  trustItems as defaultTrustItems,
} from '@/data/siteContent'
import { bookmarkPersonaliseCard } from '@/data/personalise'
import type {
  HomePageCategoryTile,
  HomePageConfig,
  HomePageHeroSlide,
  HomePageHowItWorksStep,
  HomePageNewsletter,
  HomePagePersonaliseCard,
  HomePageSectionIntro,
  HomePageTrustItem,
} from '@/types/homePage'
import { ensureLayout } from '@/lib/homePageLayout'
import logoFallback from '@/assets/main-logo.webp'

function apiPrefix(): string {
  return getApiBaseUrl() ? '/v1' : '/api/v1'
}

function defaultCardsHeroSlide(): HomePageHeroSlide {
  return {
    eyebrow: 'Greeting cards',
    tabLabel: 'Cards',
    title: 'Say it beautifully for every occasion',
    text: 'Birthday, thank you, and love cards with rich papers — ready to post or pair with a small gift.',
    image: '/products/birthday-cards.jpeg',
    ctaPrimary: { label: 'Shop now', to: '/shop/cards' },
    ctaSecondary: { label: 'Explore More', to: '/shop' },
  }
}

function defaultMagnetsHeroSlide(): HomePageHeroSlide {
  return {
    eyebrow: 'Fridge & desk',
    tabLabel: 'Fridge Magnets',
    title: 'Photo magnets for your fridge & locker',
    text: 'Glossy custom photo magnets and quote pieces — gift-ready picks that stick around.',
    image: '/products/fridge-magnets.jpeg',
    ctaPrimary: { label: 'Shop now', to: '/shop/magnets' },
    ctaSecondary: { label: 'Explore More', to: '/shop' },
  }
}

/** Drop Hampers slides; ensure Cards + Fridge Magnets exist; single Shop now CTA. */
function sanitizeHeroSlides(slides: HomePageHeroSlide[]): HomePageHeroSlide[] {
  const withoutHampers = slides.filter((s) => !/hamper/i.test(s.tabLabel || '') && !/hamper/i.test(s.title || ''))
  const list = withoutHampers.length > 0 ? withoutHampers : [...defaultHeroSlides.map((s) => ({ ...s, ctaPrimary: { ...s.ctaPrimary }, ctaSecondary: { ...s.ctaSecondary } }))]

  const hasCards = list.some((s) => /^cards?$/i.test((s.tabLabel || '').trim()))
  if (!hasCards) {
    const bmIdx = list.findIndex((s) => /bookmark/i.test(s.tabLabel || ''))
    if (bmIdx >= 0) list.splice(bmIdx + 1, 0, defaultCardsHeroSlide())
    else list.unshift(defaultCardsHeroSlide())
  }

  const hasMagnets = list.some((s) => /magnet/i.test(s.tabLabel || '') || /magnet/i.test(s.eyebrow || ''))
  if (!hasMagnets) {
    const calIdx = list.findIndex((s) => /calendar/i.test(s.tabLabel || ''))
    if (calIdx >= 0) list.splice(calIdx + 1, 0, defaultMagnetsHeroSlide())
    else list.push(defaultMagnetsHeroSlide())
  }

  return list.map((s) => {
    const tab = (s.tabLabel || '').toLowerCase()
    let shopTo = s.ctaPrimary?.to || '/shop'
    if (/bookmark/i.test(tab)) shopTo = '/shop/bookmarks'
    else if (/card/i.test(tab)) shopTo = '/shop/cards'
    else if (/calendar/i.test(tab)) shopTo = '/shop/calendars'
    else if (/magnet/i.test(tab)) shopTo = '/shop/magnets'
    else if (shopTo.includes('personalise') || /browse/i.test(s.ctaPrimary?.label || '')) {
      shopTo = shopTo.includes('category=') ? shopTo.replace('/shop?', '/shop/').replace('category=', '').split('&')[0] : '/shop'
      if (shopTo.startsWith('/shop?')) shopTo = '/shop'
    }
    return {
      ...s,
      tabLabel: /magnet/i.test(tab) ? 'Fridge Magnets' : s.tabLabel,
      ctaPrimary: { label: 'Shop now', to: shopTo },
      ctaSecondary: { label: 'Explore More', to: '/shop' },
    }
  })
}

/** Drop Gift Combos and other retired tiles from saved admin config. */
function sanitizeCategoryStrip(tiles: HomePageCategoryTile[]): HomePageCategoryTile[] {
  const filtered = tiles.filter(
    (t) => !/gift\s*combo/i.test(t.title || '') && !/combo/i.test(t.blurb || ''),
  )
  return filtered.length > 0 ? filtered : defaultCategoryStrip.map((c) => ({ ...c }))
}

function defaultPersonaliseCards(): HomePagePersonaliseCard[] {
  const b = bookmarkPersonaliseCard()
  return [
    {
      id: 'bookmark',
      shortLabel: b.shortLabel,
      blurb: b.blurb,
      image: b.sampleImage,
    },
  ]
}

function defaultConfig(): HomePageConfig {
  return {
    announcement: defaultAnnouncement,
    logoImage: '',
    heroSlides: sanitizeHeroSlides(
      defaultHeroSlides.map((s) => ({
        ...s,
        ctaPrimary: { ...s.ctaPrimary },
        ctaSecondary: { ...s.ctaSecondary },
      })),
    ),
    categoryStrip: sanitizeCategoryStrip(defaultCategoryStrip.map((c) => ({ ...c }))),
    personaliseCards: defaultPersonaliseCards(),
    trustItems: defaultTrustItems.map((t) => ({ ...t })),
    howItWorksIntro: { ...defaultHowItWorksIntro },
    howItWorksSteps: defaultHowItWorksSteps.map((s) => ({ ...s })),
    newsletter: { ...defaultNewsletter },
    layout: ensureLayout(undefined),
  }
}

function mergeConfig(saved: Partial<HomePageConfig>): HomePageConfig {
  const base = defaultConfig()
  const out: HomePageConfig = { ...base }

  if (typeof saved.announcement === 'string' && saved.announcement.trim() !== '') {
    out.announcement = saved.announcement.trim()
  }
  if (typeof saved.logoImage === 'string') {
    out.logoImage = saved.logoImage.trim()
  }
  if (Array.isArray(saved.heroSlides) && saved.heroSlides.length > 0) {
    out.heroSlides = sanitizeHeroSlides(saved.heroSlides as HomePageHeroSlide[])
  } else {
    out.heroSlides = sanitizeHeroSlides(base.heroSlides)
  }
  if (Array.isArray(saved.categoryStrip) && saved.categoryStrip.length > 0) {
    out.categoryStrip = sanitizeCategoryStrip(saved.categoryStrip as HomePageCategoryTile[])
  } else {
    out.categoryStrip = sanitizeCategoryStrip(base.categoryStrip)
  }
  if (Array.isArray(saved.personaliseCards) && saved.personaliseCards.length > 0) {
    out.personaliseCards = saved.personaliseCards as HomePagePersonaliseCard[]
  }
  if (Array.isArray(saved.trustItems) && saved.trustItems.length > 0) {
    out.trustItems = saved.trustItems as HomePageTrustItem[]
  }
  if (saved.howItWorksIntro && typeof saved.howItWorksIntro === 'object') {
    out.howItWorksIntro = { ...base.howItWorksIntro, ...saved.howItWorksIntro } as HomePageSectionIntro
  }
  if (Array.isArray(saved.howItWorksSteps) && saved.howItWorksSteps.length > 0) {
    out.howItWorksSteps = saved.howItWorksSteps as HomePageHowItWorksStep[]
  }
  if (saved.newsletter && typeof saved.newsletter === 'object') {
    out.newsletter = { ...base.newsletter, ...saved.newsletter } as HomePageNewsletter
  }
  out.layout = ensureLayout(saved.layout ?? base.layout)
  return out
}

export function homePageImageSrc(path: string): string {
  if (!path) return ''
  const resolved = resolveProductImageUrl(path)
  return resolved || path
}

export const useHomePageStore = defineStore('homePage', () => {
  const config = ref<HomePageConfig>(defaultConfig())
  const hydrated = ref(false)

  const announcement = computed(() => config.value.announcement)
  const logoSrc = computed(() => {
    const custom = config.value.logoImage
    if (custom) return homePageImageSrc(custom)
    return logoFallback
  })
  const heroSlides = computed(() => config.value.heroSlides)
  const categoryStrip = computed(() => config.value.categoryStrip)
  const personaliseCards = computed(() => config.value.personaliseCards)
  const trustItems = computed(() => config.value.trustItems)
  const howItWorksIntro = computed(() => config.value.howItWorksIntro)
  const howItWorksSteps = computed(() => config.value.howItWorksSteps)
  const newsletter = computed(() => config.value.newsletter)
  const layout = computed(() => ensureLayout(config.value.layout))

  async function hydrate(): Promise<void> {
    if (hydrated.value) return
    hydrated.value = true
    try {
      const data = await apiFetch<HomePageConfig>(`${apiPrefix()}/site/home`)
      config.value = mergeConfig(data)
    } catch {
      /* bundled defaults when API offline */
    }
  }

  function applySaved(saved: HomePageConfig): void {
    config.value = mergeConfig(saved)
  }

  return {
    config,
    hydrated,
    announcement,
    logoSrc,
    heroSlides,
    categoryStrip,
    personaliseCards,
    trustItems,
    howItWorksIntro,
    howItWorksSteps,
    newsletter,
    layout,
    hydrate,
    applySaved,
  }
})
