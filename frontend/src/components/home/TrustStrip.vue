<script setup lang="ts">
import { Lock, MessageCircle, Truck, Undo2 } from 'lucide-vue-next'
import { trustItems } from '@/data/siteContent'

const icons = {
  truck: Truck,
  return: Undo2,
  lock: Lock,
  chat: MessageCircle,
} as const
</script>

<template>
  <section class="trust tm-animate-in" aria-label="Why shop with us">
    <div class="tm-container trust__grid">
      <div v-for="item in trustItems" :key="item.title" class="trust__item tm-hover-lift">
        <div class="trust__icon" aria-hidden="true">
          <component :is="icons[item.icon as keyof typeof icons] ?? MessageCircle" :size="22" :stroke-width="2" />
        </div>
        <div>
          <h3 class="trust__title">{{ item.title }}</h3>
          <p class="trust__text">{{ item.text }}</p>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.trust {
  background: var(--color-surface-elevated);
  border-bottom: 1px solid var(--color-border);
  padding: 1.25rem 0;
  box-shadow: 0 6px 28px rgba(20, 19, 18, 0.04);
}

.trust__grid {
  display: grid;
  gap: 1rem 1.35rem;
  grid-template-columns: repeat(4, 1fr);
}

@media (max-width: 900px) {
  .trust__grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 520px) {
  .trust__grid {
    grid-template-columns: 1fr;
  }
}

.trust__item {
  display: flex;
  gap: 0.85rem;
  align-items: flex-start;
  padding: 0.65rem 0.5rem;
  border-radius: var(--radius-md);
  transition: background 0.2s ease;
}

.trust__item:hover {
  background: var(--color-accent-soft);
}

.trust__icon {
  flex-shrink: 0;
  width: 46px;
  height: 46px;
  border-radius: var(--radius-sm);
  background: linear-gradient(145deg, var(--color-accent-soft), rgba(255, 255, 255, 0.5));
  color: var(--color-accent);
  display: grid;
  place-items: center;
  border: 1px solid rgba(45, 92, 82, 0.12);
  transition:
    transform 0.25s var(--ease-spring, ease),
    box-shadow 0.2s ease;
}

.trust__item:hover .trust__icon {
  transform: scale(1.06);
  box-shadow: var(--shadow-sm);
}

.trust__title {
  margin: 0 0 0.2rem;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-ink);
}

.trust__text {
  margin: 0;
  font-size: 0.875rem;
  color: var(--color-ink-muted);
  line-height: 1.45;
}
</style>
