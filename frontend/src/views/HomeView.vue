<script setup lang="ts">
import CategoryGrid from '@/components/home/CategoryGrid.vue'
import HeroEditorial from '@/components/home/HeroEditorial.vue'
import HowItWorks from '@/components/home/HowItWorks.vue'
import NewsletterSection from '@/components/home/NewsletterSection.vue'
import PersonalisedGrid from '@/components/home/PersonalisedGrid.vue'
import ProductCarousel from '@/components/home/ProductCarousel.vue'
import SaleCountdownSection from '@/components/home/SaleCountdownSection.vue'
import TrustStrip from '@/components/home/TrustStrip.vue'
import ProductCard from '@/components/product/ProductCard.vue'
import { ArrowRight, Sparkles } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useCatalogStore } from '@/stores/catalog'

const catalogStore = useCatalogStore()
const { favourites, magneticBookmarks } = storeToRefs(catalogStore)
</script>

<template>
  <div class="home">
    <HeroEditorial />
    <TrustStrip />

    <section class="tm-section">
      <div class="tm-container">
        <header class="section-head">
          <p class="section-eyebrow">Shop by category</p>
          <h2 class="section-title">Find your next favourite</h2>
          <p class="section-desc">
            Jump straight into bookmarks, cards, calendars, and more — the same structure shoppers
            see on top stationery sites.
          </p>
        </header>
        <CategoryGrid />
      </div>
    </section>

    <section class="tm-section">
      <div class="tm-container">
        <header class="section-head section-head--split">
          <div class="section-head__text">
            <p class="section-eyebrow">Personalise</p>
            <h2 class="section-title">Make it yours</h2>
            <p class="section-desc">
              Upload your photo and preview it on bookmarks, calendars, cards, or fridge magnets
              before you order.
            </p>
          </div>
          <RouterLink to="/personalise" class="home__personalise-cta tm-hover-lift">
            <Sparkles :size="18" :stroke-width="2.25" aria-hidden="true" />
            <span>Open personalise studio</span>
            <ArrowRight :size="17" :stroke-width="2.25" aria-hidden="true" />
          </RouterLink>
        </header>
        <PersonalisedGrid />
      </div>
    </section>

    <section class="tm-section home__cream">
      <div class="tm-container">
        <header class="section-head">
          <p class="section-eyebrow">Magnetic bookmarks</p>
          <h2 class="section-title">Mark every page</h2>
          <p class="section-desc">
            Fold-over magnetic clips that stay put — swipe through our bookmark picks.
          </p>
        </header>
        <ProductCarousel :products="magneticBookmarks" />
        <p class="home__viewall">
          <RouterLink
            :to="{ path: '/shop', query: { category: 'bookmarks', type: 'magnetic' } }"
            class="home__viewall-link"
          >
            Shop all magnetic bookmarks
          </RouterLink>
        </p>
      </div>
    </section>

    <SaleCountdownSection />

    <HowItWorks />

    <section class="tm-section home__cream">
      <div class="tm-container">
        <header class="section-head">
          <p class="section-eyebrow">Bestsellers</p>
          <h2 class="section-title">What readers & gifters love</h2>
          <p class="section-desc">
            A curated mix of magnetic bookmarks, cards, and small gifts — easy to add to cart with
            clear sale pricing.
          </p>
        </header>
        <div class="products-grid">
          <ProductCard v-for="p in favourites" :key="p.id" :product="p" />
        </div>
        <p class="home__viewall">
          <RouterLink to="/shop" class="home__viewall-link">View full shop</RouterLink>
        </p>
      </div>
    </section>

    <NewsletterSection />
  </div>
</template>

<style scoped>
.home {
  width: 100%;
}

.home__cream {
  background: var(--color-surface);
  border-top: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
}

.products-grid {
  display: grid;
  gap: 1.1rem;
  grid-template-columns: repeat(5, 1fr);
}

@media (max-width: 1100px) {
  .products-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 640px) {
  .products-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.home__viewall {
  margin: 2rem 0 0;
  text-align: center;
}

.home__viewall-link {
  display: inline-flex;
  min-height: var(--tap-min);
  align-items: center;
  padding: 0 1.5rem;
  border-radius: 999px;
  font-weight: 650;
  font-size: 0.95rem;
  background: transparent;
  color: var(--color-accent) !important;
  border: 2px solid var(--color-border-strong);
}

.home__viewall-link:hover {
  border-color: var(--color-accent);
}

.section-head--split {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem 2rem;
}

.section-head__text {
  flex: 1;
  min-width: 0;
  max-width: 38rem;
}

.home__personalise-cta {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  padding: 0.65rem 1.2rem;
  border-radius: 999px;
  background: var(--color-accent);
  color: #fff !important;
  font-size: 0.88rem;
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
  box-shadow: 0 4px 14px rgba(45, 92, 82, 0.25);
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.home__personalise-cta:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(45, 92, 82, 0.3);
}

@media (max-width: 720px) {
  .section-head--split {
    flex-direction: column;
    align-items: flex-start;
  }

  .home__personalise-cta {
    white-space: normal;
    text-align: center;
  }
}
</style>
