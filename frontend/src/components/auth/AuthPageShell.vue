<script setup lang="ts">
import SiteLogo from '@/components/layout/SiteLogo.vue'

defineProps<{
  title: string
  eyebrow?: string
}>()
</script>

<template>
  <div class="auth tm-section tm-animate-in">
    <div class="tm-container auth__wrap">
      <SiteLogo size="auth" linked />
      <div class="auth__card tm-hover-lift">
        <p v-if="eyebrow" class="auth__eyebrow">{{ eyebrow }}</p>
        <h1 class="auth__title">{{ title }}</h1>
        <div class="auth__body">
          <slot />
        </div>
      </div>
      <div v-if="$slots.below" class="auth__below">
        <slot name="below" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth {
  padding-top: 1.5rem;
  padding-bottom: 3.5rem;
}

.auth__wrap {
  max-width: 28rem;
  margin: 0 auto;
  text-align: center;
}

.auth__card {
  text-align: left;
  padding: 1.75rem 1.5rem;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  box-shadow: var(--shadow-card);
}

.auth__eyebrow {
  margin: 0 0 0.35rem;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.auth__title {
  margin: 0 0 1.25rem;
  font-family: var(--font-display);
  font-size: clamp(1.35rem, 2.5vw, 1.65rem);
  font-weight: 500;
  color: var(--color-ink);
}

.auth__body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.auth__below {
  margin-top: 1.25rem;
  text-align: center;
  font-size: 0.9rem;
  color: var(--color-ink-muted);
}

.auth__below :deep(a) {
  font-weight: 650;
  color: var(--color-accent);
  text-decoration: none;
}

.auth__below :deep(a:hover) {
  text-decoration: underline;
}
</style>
