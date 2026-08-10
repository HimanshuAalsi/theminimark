export interface HomePageCta {
  label: string
  to: string
}

export interface HomePageHeroSlide {
  eyebrow: string
  tabLabel: string
  title: string
  text: string
  image: string
  ctaPrimary: HomePageCta
  ctaSecondary: HomePageCta
}

export interface HomePageCategoryTile {
  title: string
  blurb: string
  href: string
  image: string
}

export interface HomePagePersonaliseCard {
  id: 'bookmark' | 'calendar' | 'card' | 'magnet'
  shortLabel: string
  blurb: string
  image: string
}

export type HomePageTrustIcon = 'truck' | 'return' | 'payment' | 'offer' | 'lock' | 'chat'

export interface HomePageTrustItem {
  title: string
  text: string
  icon: HomePageTrustIcon
}

export interface HomePageHowItWorksStep {
  step: string
  title: string
  text: string
  ctaLabel: string
  ctaTo: string
}

export interface HomePageSectionIntro {
  eyebrow: string
  title: string
  description: string
}

export interface HomePageNewsletter {
  eyebrow: string
  title: string
  description: string
  placeholder: string
  buttonLabel: string
  finePrint: string
}

export interface HomePageConfig {
  announcement: string
  logoImage: string
  heroSlides: HomePageHeroSlide[]
  categoryStrip: HomePageCategoryTile[]
  personaliseCards: HomePagePersonaliseCard[]
  trustItems: HomePageTrustItem[]
  howItWorksIntro: HomePageSectionIntro
  howItWorksSteps: HomePageHowItWorksStep[]
  newsletter: HomePageNewsletter
  layout?: import('@/types/homePageLayout').HomePageLayout
}
