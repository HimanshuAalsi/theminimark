<script setup lang="ts">
import { Gift, Bookmark } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="set-promo">
    <article class="set-promo__card set-promo__card--bookmarks">
      <div class="set-promo__icon" aria-hidden="true">
        <Bookmark :size="22" :stroke-width="2" />
      </div>
      <h3 class="set-promo__title">Bookmark set</h3>
      <p class="set-promo__text">
        Build a set of magnetic &amp; classic bookmarks only. Save more as you add —
        up to <strong>20% off</strong> on a set of 8+.
      </p>
      <ul class="set-promo__tiers" aria-label="Bookmark set discounts">
        <li>2 → 5%</li>
        <li>4 → 10%</li>
        <li>6 → 15%</li>
        <li>8+ → 20%</li>
      </ul>
      <RouterLink class="set-promo__cta" :to="{ path: '/create-your-set', query: { mode: 'bookmarks' } }">
        Create bookmark set
      </RouterLink>
    </article>

    <article class="set-promo__card set-promo__card--hamper">
      <div class="set-promo__icon" aria-hidden="true">
        <Gift :size="22" :stroke-width="2" />
      </div>
      <h3 class="set-promo__title">Build a hamper</h3>
      <p class="set-promo__text">
        Mix any products into a custom gift box. Add at least 4 items for
        <strong>10% off</strong>, or 10+ for <strong>18% off</strong>.
      </p>
      <ul class="set-promo__tiers" aria-label="Hamper discounts">
        <li>4+ → 10%</li>
        <li>10+ → 18%</li>
      </ul>
      <RouterLink class="set-promo__cta" :to="{ path: '/create-your-set', query: { mode: 'hamper' } }">
        Build a hamper
      </RouterLink>
    </article>
  </div>
</template>

<style scoped>
.set-promo {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

@media (max-width: 700px) {
  .set-promo {
    grid-template-columns: 1fr;
  }
}

.set-promo__card {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  padding: 1.35rem 1.25rem 1.25rem;
  border: 1px solid var(--color-border, var(--tm-border));
  border-radius: var(--radius-lg, 14px);
  background: var(--color-surface-elevated, var(--tm-surface-2));
  box-shadow: var(--shadow-sm, 0 4px 16px rgba(20, 19, 18, 0.05));
}

.set-promo__icon {
  display: grid;
  place-items: center;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 12px;
  background: var(--tm-gradient-soft, rgba(45, 92, 82, 0.1));
  color: var(--color-accent, var(--tm-accent));
}

.set-promo__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 500;
  color: var(--color-ink, var(--tm-ink));
}

.set-promo__text {
  margin: 0;
  font-size: 0.9rem;
  line-height: 1.5;
  color: var(--color-ink-muted, var(--tm-ink-muted));
  flex: 1;
}

.set-promo__tiers {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.set-promo__tiers li {
  padding: 0.25rem 0.55rem;
  border-radius: 999px;
  background: var(--color-accent-soft, rgba(45, 92, 82, 0.1));
  color: var(--color-accent, var(--tm-accent));
  font-size: 0.72rem;
  font-weight: 700;
}

.set-promo__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-top: 0.35rem;
  min-height: 2.6rem;
  padding: 0 1.1rem;
  border-radius: 999px;
  background: var(--tm-gradient, var(--color-accent));
  color: var(--tm-on-accent, #fff) !important;
  font-size: 0.88rem;
  font-weight: 700;
  text-decoration: none;
  box-shadow: var(--tm-shadow-accent, none);
}

.set-promo__cta:hover {
  filter: brightness(1.05);
}
</style>
