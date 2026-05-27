<script setup lang="ts">
import { Mail, Send } from 'lucide-vue-next'
import { useNewsletterForm } from '@/composables/useNewsletterForm'

const { email, feedback, busy, submit } = useNewsletterForm('home')
</script>

<template>
  <section class="news tm-section tm-animate-in">
    <div class="tm-container news__inner">
      <header class="section-head section-head--center">
        <p class="section-eyebrow">Newsletter</p>
        <h2 class="section-title">Get offers &amp; new arrivals</h2>
        <p class="section-desc">
          Occasional emails — no spam. Unsubscribe anytime (pattern used by indie stationery brands).
        </p>
      </header>
      <form class="news__form" @submit="submit">
        <div class="news__row">
          <span class="news__icon" aria-hidden="true">
            <Mail :size="20" :stroke-width="2" />
          </span>
          <input
            v-model="email"
            type="email"
            name="email"
            class="news__input"
            placeholder="Your email"
            autocomplete="email"
          />
          <button type="submit" class="news__btn tm-press" :disabled="busy">
            <Send v-if="!busy" :size="18" :stroke-width="2.25" class="news__btn-ico" aria-hidden="true" />
            <span>{{ busy ? 'Sending…' : 'Subscribe' }}</span>
          </button>
        </div>
        <p v-if="feedback" class="news__feedback" role="status">{{ feedback }}</p>
        <p class="news__fine">We respect your inbox. No third-party ads.</p>
      </form>
    </div>
  </section>
</template>

<style scoped>
.news {
  background: linear-gradient(185deg, var(--color-page) 0%, #e5e0d6 100%);
  border-top: 1px solid var(--color-border);
}

.news__inner {
  max-width: 520px;
  margin: 0 auto;
}

.news__form {
  margin-top: 0.5rem;
}

.news__row {
  display: flex;
  flex-wrap: nowrap;
  align-items: stretch;
  gap: 0;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  overflow: hidden;
  background: var(--color-surface-elevated);
  box-shadow: var(--shadow-card);
  transition:
    border-color 0.25s ease,
    box-shadow 0.25s ease;
}

.news__row:focus-within {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}

.news__icon {
  display: flex;
  align-items: center;
  padding-left: 1rem;
  color: var(--color-accent);
  flex-shrink: 0;
}

.news__input {
  flex: 1;
  min-width: 120px;
  border: none;
  padding: 0.85rem 0.75rem;
  font: inherit;
  font-size: 1rem;
  min-height: var(--tap-min);
  background: transparent;
}

.news__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  border: none;
  padding: 0 1.25rem;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  color: #fff;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  font-family: inherit;
  transition:
    filter 0.2s ease,
    transform 0.15s ease;
}

.news__btn:hover:not(:disabled) {
  filter: brightness(1.06);
}

.news__btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.news__feedback {
  margin: 0.75rem 0 0;
  text-align: center;
  font-size: 0.875rem;
  color: var(--color-ink-muted);
}

.news__fine {
  margin: 0.85rem 0 0;
  text-align: center;
  font-size: 0.8rem;
  color: var(--color-ink-faint);
}
</style>
