<script setup lang="ts">
import { computed, onMounted } from 'vue'
import type { RouteLocationRaw } from 'vue-router'
import { RouterLink } from 'vue-router'
import { storeToRefs } from 'pinia'
import BlogTeaserSection from '@/components/home/BlogTeaserSection.vue'
import CategoryGrid from '@/components/home/CategoryGrid.vue'
import HeroEditorial from '@/components/home/HeroEditorial.vue'
import HomeSectionHeader from '@/components/home/HomeSectionHeader.vue'
import HowItWorks from '@/components/home/HowItWorks.vue'
import ConnectSection from '@/components/home/ConnectSection.vue'
import MiniKnotsSection from '@/components/home/MiniKnotsSection.vue'
import CreateSetPromo from '@/components/home/CreateSetPromo.vue'
import PersonalisedGrid from '@/components/home/PersonalisedGrid.vue'
import ProductCarousel from '@/components/home/ProductCarousel.vue'
import ProductCard from '@/components/product/ProductCard.vue'
import SaleCountdownSection from '@/components/home/SaleCountdownSection.vue'
import TrustStrip from '@/components/home/TrustStrip.vue'
import { PERSONALISE_STOREFRONT_VISIBLE } from '@/data/personalise'
import { resolveHomeProducts } from '@/lib/homePageProducts'
import { boxStyleToCss, sanitizeHtml, styleClass } from '@/lib/homePageStyle'
import { homePageImageSrc } from '@/stores/homePage'
import { useCatalogStore } from '@/stores/catalog'
import type {
  HomeLayoutSegment,
  HomeProductCarouselSegment,
  HomeProductGridSegment,
  HomeSectionHeaderSegment,
  HomeSimpleSegment,
} from '@/types/homePageLayout'

const props = withDefaults(
  defineProps<{
    segment: HomeLayoutSegment
    /** Enable AOS on segment wrapper (hero should stay false) */
    animate?: boolean
    animateDelay?: number
  }>(),
  { animate: false, animateDelay: 0 },
)

const catalogStore = useCatalogStore()
const { catalog, favourites, magneticBookmarks } = storeToRefs(catalogStore)

const wrapStyle = computed(() => boxStyleToCss(props.segment.style))
const wrapClass = computed(() => styleClass(props.segment.style))

onMounted(() => {
  void catalogStore.ensureLoaded()
})

function productsFor(seg: HomeProductGridSegment | HomeProductCarouselSegment) {
  return resolveHomeProducts(seg, catalog.value, favourites.value, magneticBookmarks.value)
}

function asSimple(seg: HomeLayoutSegment): HomeSimpleSegment {
  return seg as HomeSimpleSegment
}

function asHeader(seg: HomeLayoutSegment): HomeSectionHeaderSegment {
  return seg as HomeSectionHeaderSegment
}

const safeHtml = computed(() => sanitizeHtml(asSimple(props.segment).html || ''))

/** Types that handle their own AOS (stagger / header line) */
const SELF_AOS = new Set(['section-header', 'category-grid', 'personalise-grid', 'trust', 'how-it-works'])

const wrapAos = computed(() => props.animate && !SELF_AOS.has(props.segment.type))

function routePath(to: RouteLocationRaw | undefined): string {
  if (!to) return ''
  if (typeof to === 'string') return to
  if (typeof to === 'object' && 'path' in to) return String(to.path ?? '')
  return ''
}

function isPersonaliseHeader(seg: HomeLayoutSegment): boolean {
  const h = asHeader(seg)
  if (routePath(h.cta?.to).includes('personalise')) return true
  return /personalise/i.test(h.eyebrow || '') || /make it yours/i.test(h.title || '')
}

const hidden = computed(() => {
  if (PERSONALISE_STOREFRONT_VISIBLE) return false
  if (props.segment.type === 'personalise-grid') return true
  if (props.segment.type === 'section-header') return isPersonaliseHeader(props.segment)
  return false
})
</script>

<template>
  <div
    v-if="!hidden"
    class="home-seg-wrap"
    :class="wrapClass"
    :style="wrapStyle"
    :data-aos="wrapAos ? 'fade-up' : undefined"
    :data-aos-delay="wrapAos && animateDelay ? String(animateDelay) : undefined"
  >
  <HeroEditorial v-if="segment.type === 'hero'" />
  <TrustStrip v-else-if="segment.type === 'trust'" />

  <HomeSectionHeader
    v-else-if="segment.type === 'section-header'"
    :eyebrow="asHeader(segment).eyebrow"
    :title="asHeader(segment).title"
    :description="asHeader(segment).description"
    :align="asHeader(segment).align"
    :title-size="asHeader(segment).titleSize"
    :cta="asHeader(segment).cta"
    :split="Boolean(asHeader(segment).cta?.label)"
  />

  <CategoryGrid v-else-if="segment.type === 'category-grid'" />
  <PersonalisedGrid v-else-if="segment.type === 'personalise-grid'" />
  <CreateSetPromo v-else-if="segment.type === 'create-set-promo'" />

  <template v-else-if="segment.type === 'product-grid'">
    <div
      class="products-grid tm-product-grid"
      :class="`products-grid--cols-${(segment as HomeProductGridSegment).columns ?? 5}`"
      :data-aos="wrapAos ? 'fade-up' : undefined"
      :data-aos-delay="wrapAos && animateDelay ? String(animateDelay + 80) : undefined"
    >
      <ProductCard v-for="p in productsFor(segment as HomeProductGridSegment)" :key="p.id" :product="p" />
    </div>
    <p v-if="(segment as HomeProductGridSegment).viewAllTo" class="home-seg-viewall">
      <RouterLink :to="(segment as HomeProductGridSegment).viewAllTo!" class="home-seg-viewall-link">
        {{ (segment as HomeProductGridSegment).viewAllLabel || 'View all' }}
      </RouterLink>
    </p>
  </template>

  <template v-else-if="segment.type === 'product-carousel'">
    <div
      :data-aos="wrapAos ? 'fade-up' : undefined"
      :data-aos-delay="wrapAos && animateDelay ? String(animateDelay + 80) : undefined"
    >
      <ProductCarousel :products="productsFor(segment as HomeProductCarouselSegment)" />
      <p v-if="(segment as HomeProductCarouselSegment).viewAllTo" class="home-seg-viewall">
        <RouterLink
          :to="(segment as HomeProductCarouselSegment).viewAllTo!"
          class="home-seg-viewall-link"
        >
          {{ (segment as HomeProductCarouselSegment).viewAllLabel || 'View all' }}
        </RouterLink>
      </p>
    </div>
  </template>

  <HowItWorks v-else-if="segment.type === 'how-it-works'" />

  <SaleCountdownSection
    v-else-if="segment.type === 'sale-countdown'"
    :end-at="asSimple(segment).endAt"
    :headline="asSimple(segment).headline"
    :subheadline="asSimple(segment).subheadline"
  />

  <ConnectSection v-else-if="segment.type === 'connect' || segment.type === 'newsletter'" />

  <MiniKnotsSection v-else-if="segment.type === 'mini-knots'" />

  <BlogTeaserSection
    v-else-if="segment.type === 'blog-teaser'"
    :limit="asSimple(segment).limit ?? 3"
  />

  <RouterLink
    v-else-if="segment.type === 'banner' && asSimple(segment).image"
    :to="asSimple(segment).href || '/shop'"
    class="home-banner"
  >
    <img
      :src="homePageImageSrc(asSimple(segment).image!)"
      :alt="asSimple(segment).alt || 'Promo'"
      loading="lazy"
    />
  </RouterLink>

  <div
    v-else-if="segment.type === 'spacer'"
    class="home-spacer"
    :class="`home-spacer--${asSimple(segment).height || 'md'}`"
    aria-hidden="true"
  />

  <div
    v-else-if="segment.type === 'html' && safeHtml"
    class="home-html-block"
    v-html="safeHtml"
  />
  </div>
</template>

<style scoped>
.products-grid--cols-3 {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.products-grid--cols-4 {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

@media (min-width: 1280px) {
  .products-grid--cols-5 {
    grid-template-columns: repeat(5, minmax(0, 1fr));
  }
}

.home-seg-viewall {
  margin: 1.5rem 0 0;
}

.home-seg-viewall-link {
  font-weight: 700;
  color: var(--tm-accent);
  text-decoration: none;
}

.home-banner {
  display: block;
  border-radius: var(--tm-radius-lg);
  overflow: hidden;
}

.home-banner img {
  width: 100%;
  display: block;
  object-fit: cover;
}

.home-spacer--sm {
  height: 1rem;
}

.home-spacer--md {
  height: 2rem;
}

.home-spacer--lg {
  height: 3.5rem;
}

.home-seg-wrap:empty {
  display: none;
}

.home-seg-wrap {
  min-width: 0;
  max-width: 100%;
}

.home-html-block :deep(a) {
  color: var(--tm-accent);
}
</style>
