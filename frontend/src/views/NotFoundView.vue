<script setup lang="ts">
import { Home, Store } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'
import SiteLogo from '@/components/layout/SiteLogo.vue'
</script>

<template>
  <div class="notfound tm-section tm-animate-in">
    <div class="tm-container notfound__wrap">
      <SiteLogo size="auth" linked />
      <div class="notfound__card tm-hover-lift">
        <p class="notfound__code">404</p>
        <h1 class="notfound__title">Page not found</h1>
        <p class="notfound__text">That URL doesn’t exist. Try the shop or home.</p>
        <div class="notfound__actions">
          <RouterLink to="/" class="notfound__btn notfound__btn--primary tm-press">
            <Home :size="18" :stroke-width="2.25" aria-hidden="true" />
            Back to home
          </RouterLink>
          <RouterLink to="/shop" class="notfound__btn notfound__btn--ghost tm-press">
            <Store :size="18" :stroke-width="2.25" aria-hidden="true" />
            Browse shop
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.notfound__wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-top: 1rem;
  padding-bottom: 3rem;
}

.notfound__card {
  max-width: 26rem;
  width: 100%;
  text-align: center;
  padding: 2.25rem 1.75rem;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  box-shadow: var(--shadow-card);
}

.notfound__icon {
  display: inline-flex;
  margin-bottom: 0.75rem;
  padding: 0.85rem;
  border-radius: 50%;
  color: var(--color-accent);
  background: var(--color-accent-soft);
}

.notfound__code {
  margin: 0 0 0.35rem;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--color-ink-faint);
}

.notfound__title {
  margin: 0 0 0.5rem;
  font-family: var(--font-display);
  font-size: clamp(1.5rem, 3vw, 1.85rem);
  font-weight: 500;
  color: var(--color-ink);
}

.notfound__text {
  margin: 0 0 1.5rem;
  color: var(--color-ink-muted);
  line-height: 1.55;
}

.notfound__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  justify-content: center;
}

.notfound__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  min-height: var(--tap-min);
  padding: 0 1.2rem;
  border-radius: 999px;
  font-weight: 700;
  font-size: 0.95rem;
  text-decoration: none;
  transition:
    filter 0.2s ease,
    transform 0.15s ease,
    background 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease;
}

.notfound__btn--primary {
  color: #fff !important;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  box-shadow: 0 4px 16px rgba(45, 92, 82, 0.28);
}

.notfound__btn--primary:hover {
  filter: brightness(1.06);
  transform: translateY(-1px);
}

.notfound__btn--ghost {
  color: var(--color-ink-muted) !important;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
}

.notfound__btn--ghost:hover {
  color: var(--color-accent) !important;
  border-color: rgba(45, 92, 82, 0.3);
  background: var(--color-accent-soft);
  transform: translateY(-1px);
}
</style>
