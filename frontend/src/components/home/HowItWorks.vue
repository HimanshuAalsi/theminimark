<script setup lang="ts">
import { Grid3x3, Package, ShoppingBag } from 'lucide-vue-next'
import { howItWorks } from '@/data/siteContent'

const stepIcons = [Grid3x3, ShoppingBag, Package] as const
</script>

<template>
  <section class="hiw tm-section tm-animate-in">
    <div class="tm-container">
      <header class="section-head section-head--center">
        <p class="section-eyebrow">How it works</p>
        <h2 class="section-title">Shop in three simple steps</h2>
        <p class="section-desc">
          The same flow shoppers expect from modern stationery stores: browse, cart, checkout — without
          clutter.
        </p>
      </header>
      <ol class="hiw__steps">
        <li v-for="(s, i) in howItWorks" :key="s.step" class="hiw__step tm-hover-lift">
          <div class="hiw__icon-wrap" aria-hidden="true">
            <component :is="stepIcons[i] ?? Grid3x3" :size="24" :stroke-width="2" />
          </div>
          <span class="hiw__num">{{ s.step }}</span>
          <h3 class="hiw__title">{{ s.title }}</h3>
          <p class="hiw__text">{{ s.text }}</p>
        </li>
      </ol>
    </div>
  </section>
</template>

<style scoped>
.hiw {
  background: var(--color-surface-elevated);
  border-top: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.5);
}

.hiw__steps {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 1.35rem;
  grid-template-columns: repeat(3, 1fr);
}

@media (max-width: 768px) {
  .hiw__steps {
    grid-template-columns: 1fr;
  }
}

.hiw__step {
  padding: 1.5rem 1.35rem 1.4rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: linear-gradient(165deg, var(--color-page) 0%, #f0ebe3 100%);
  position: relative;
  transition:
    border-color 0.25s ease,
    box-shadow 0.25s ease;
}

.hiw__step:hover {
  border-color: rgba(45, 92, 82, 0.28);
  box-shadow: var(--shadow-card);
}

.hiw__icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-sm);
  background: var(--color-surface-elevated);
  color: var(--color-accent);
  display: grid;
  place-items: center;
  margin-bottom: 0.75rem;
  border: 1px solid var(--color-border);
  transition:
    transform 0.3s var(--ease-spring, ease),
    box-shadow 0.25s ease;
}

.hiw__step:hover .hiw__icon-wrap {
  transform: scale(1.05) rotate(-2deg);
  box-shadow: var(--shadow-sm);
}

.hiw__num {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: inline-flex;
  width: 1.75rem;
  height: 1.75rem;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  color: #fff;
  font-size: 0.8rem;
  font-weight: 800;
}

.hiw__title {
  margin: 0 0 0.5rem;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-ink);
}

.hiw__text {
  margin: 0;
  font-size: 0.92rem;
  color: var(--color-ink-muted);
  line-height: 1.55;
}

@media (prefers-reduced-motion: reduce) {
  .hiw__step:hover .hiw__icon-wrap {
    transform: none;
  }
}
</style>
