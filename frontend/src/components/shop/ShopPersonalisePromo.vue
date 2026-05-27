<script setup lang="ts">
import { ArrowRight, Sparkles, Upload } from 'lucide-vue-next'
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { formatCurrency } from '@/lib/currency'
import type { PersonaliseProduct } from '@/data/personalise'

const props = withDefaults(
  defineProps<{
    promo: PersonaliseProduct
    /** Vertical layout for sticky shop sidebar */
    sidebar?: boolean
  }>(),
  { sidebar: false }
)

const fmt = formatCurrency

const studioLink = computed(() => ({
  path: '/personalise',
  query: { type: props.promo.id },
}))
</script>

<template>
  <aside
    class="shop-promo tm-hover-lift"
    :class="{ 'shop-promo--sidebar': sidebar }"
    aria-labelledby="shop-promo-title"
  >
    <div class="shop-promo__media">
      <img :src="promo.sampleImage" :alt="promo.label" class="shop-promo__img" loading="lazy" />
      <span class="shop-promo__badge">
        <Sparkles :size="13" aria-hidden="true" />
        Personalise
      </span>
    </div>

    <div class="shop-promo__body">
      <p class="shop-promo__eyebrow">Make it yours</p>
      <h2 id="shop-promo-title" class="shop-promo__title">{{ promo.label }}</h2>
      <p class="shop-promo__text">{{ promo.blurb }}</p>
      <p class="shop-promo__steps">
        <Upload :size="14" aria-hidden="true" />
        Upload your photo, preview on all four products, then add to cart.
      </p>
      <p class="shop-promo__price">
        From <strong>{{ fmt(promo.price) }}</strong>
        <span v-if="promo.compareAt > promo.price" class="shop-promo__was">{{
          fmt(promo.compareAt)
        }}</span>
      </p>
      <RouterLink :to="studioLink" class="shop-promo__cta">
        Open personalise studio
        <ArrowRight :size="17" :stroke-width="2.25" aria-hidden="true" />
      </RouterLink>
    </div>
  </aside>
</template>

<style scoped>
.shop-promo {
  display: grid;
  grid-template-columns: minmax(0, 11rem) minmax(0, 1fr);
  gap: 1.25rem;
  align-items: center;
  margin-bottom: 2rem;
  padding: 1rem 1.15rem;
  border-radius: var(--radius-lg);
  border: 1px solid rgba(45, 92, 82, 0.2);
  background:
    linear-gradient(135deg, rgba(45, 92, 82, 0.06) 0%, transparent 55%),
    var(--color-surface-elevated);
  box-shadow: var(--shadow-card);
}

.shop-promo--sidebar {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  margin-bottom: 0;
  padding: 1rem;
  width: 100%;
}

.shop-promo--sidebar .shop-promo__media {
  aspect-ratio: 4 / 3;
}

.shop-promo--sidebar .shop-promo__cta {
  display: flex;
  width: 100%;
  justify-content: center;
}

@media (max-width: 640px) {
  .shop-promo:not(.shop-promo--sidebar) {
    grid-template-columns: 1fr;
  }
}

.shop-promo__media {
  position: relative;
  border-radius: var(--radius-md);
  overflow: hidden;
  aspect-ratio: 1;
  border: 1px solid var(--color-border);
}

.shop-promo__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.shop-promo__badge {
  position: absolute;
  top: 0.5rem;
  left: 0.5rem;
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.28rem 0.55rem;
  border-radius: 999px;
  background: var(--color-accent);
  color: #fff;
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.shop-promo__eyebrow {
  margin: 0 0 0.3rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.shop-promo__title {
  margin: 0 0 0.4rem;
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 500;
  line-height: 1.2;
  color: var(--color-ink);
}

.shop-promo__text {
  margin: 0 0 0.5rem;
  font-size: 0.88rem;
  line-height: 1.5;
  color: var(--color-ink-muted);
}

.shop-promo__steps {
  display: flex;
  align-items: flex-start;
  gap: 0.4rem;
  margin: 0 0 0.65rem;
  font-size: 0.8rem;
  line-height: 1.45;
  color: var(--color-ink-faint);
}

.shop-promo__steps svg {
  flex-shrink: 0;
  margin-top: 0.1rem;
  color: var(--color-accent);
}

.shop-promo__price {
  margin: 0 0 0.75rem;
  font-size: 0.88rem;
  color: var(--color-ink-muted);
}

.shop-promo__price strong {
  font-size: 1.05rem;
  color: var(--color-ink);
}

.shop-promo__was {
  margin-left: 0.35rem;
  text-decoration: line-through;
  font-size: 0.82rem;
}

.shop-promo__cta {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-height: var(--tap-min);
  padding: 0 1.15rem;
  border-radius: 999px;
  background: var(--color-accent);
  color: #fff !important;
  font-size: 0.88rem;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 4px 14px rgba(45, 92, 82, 0.25);
  transition:
    filter 0.2s ease,
    transform 0.15s ease;
}

.shop-promo__cta:hover {
  filter: brightness(1.05);
  transform: translateY(-1px);
}
</style>
