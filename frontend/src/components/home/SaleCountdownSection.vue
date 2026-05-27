<script setup lang="ts">
import { Zap } from 'lucide-vue-next'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

const end = ref(Date.now() + 7 * 24 * 60 * 60 * 1000)
const now = ref(Date.now())
let id: ReturnType<typeof setInterval>

const parts = computed(() => {
  const ms = Math.max(0, end.value - now.value)
  const s = Math.floor(ms / 1000)
  const days = Math.floor(s / 86400)
  const hours = Math.floor((s % 86400) / 3600)
  const minutes = Math.floor((s % 3600) / 60)
  const seconds = s % 60
  const pad = (n: number) => String(n).padStart(2, '0')
  return {
    days: pad(days),
    hours: pad(hours),
    minutes: pad(minutes),
    seconds: pad(seconds),
  }
})

onMounted(() => {
  id = setInterval(() => {
    now.value = Date.now()
  }, 1000)
})

onUnmounted(() => clearInterval(id))
</script>

<template>
  <section class="sale tm-animate-in">
    <div class="sale__glow" aria-hidden="true" />
    <div class="tm-container sale__inner">
      <p class="sale__eyebrow">
        <Zap :size="15" :stroke-width="2.25" class="sale__eyebrow-ico" aria-hidden="true" />
        Limited time · Up to 20% off
      </p>
      <h2 class="sale__title">Stationery &amp; bookmark sale</h2>

      <div class="sale__timer" aria-label="Sale ends in">
        <div class="sale__unit">
          <div class="sale__digits">
            <span>{{ parts.days[0] }}</span><span>{{ parts.days[1] }}</span>
          </div>
          <div class="sale__label">Days</div>
        </div>
        <div class="sale__unit">
          <div class="sale__digits">
            <span>{{ parts.hours[0] }}</span><span>{{ parts.hours[1] }}</span>
          </div>
          <div class="sale__label">Hours</div>
        </div>
        <div class="sale__unit">
          <div class="sale__digits">
            <span>{{ parts.minutes[0] }}</span><span>{{ parts.minutes[1] }}</span>
          </div>
          <div class="sale__label">Minutes</div>
        </div>
        <div class="sale__unit">
          <div class="sale__digits">
            <span>{{ parts.seconds[0] }}</span><span>{{ parts.seconds[1] }}</span>
          </div>
          <div class="sale__label">Seconds</div>
        </div>
      </div>

      <RouterLink to="/shop" class="sale__cta tm-press">
        Shop the sale
        <Zap :size="17" :stroke-width="2" class="sale__cta-ico" aria-hidden="true" />
      </RouterLink>
    </div>
  </section>
</template>

<style scoped>
.sale {
  position: relative;
  padding: clamp(2rem, 5vw, 3rem) 0;
  border-top: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
  overflow: hidden;
  background: var(--color-surface-elevated);
}

.sale__glow {
  position: absolute;
  inset: -40% -20%;
  background: radial-gradient(circle at 50% 80%, rgba(196, 92, 60, 0.12), transparent 55%);
  pointer-events: none;
}

.sale__inner {
  position: relative;
  text-align: center;
}

.sale__eyebrow {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  margin: 0 0 0.5rem;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--color-highlight);
}

.sale__eyebrow-ico {
  color: var(--color-highlight);
}

.sale__title {
  margin: 0 0 1.75rem;
  font-family: var(--font-display);
  font-weight: 500;
  font-size: clamp(1.45rem, 3vw, 1.85rem);
  color: var(--color-ink);
}

.sale__timer {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 1.5rem 2rem;
  margin-bottom: 1.75rem;
}

.sale__unit {
  min-width: 72px;
}

.sale__digits {
  display: flex;
  gap: 4px;
  justify-content: center;
  margin-bottom: 6px;
}

.sale__digits span {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 44px;
  background: linear-gradient(180deg, #fff 0%, var(--color-page) 100%);
  border: 1px solid var(--color-border);
  font-size: 22px;
  font-weight: 700;
  color: var(--color-ink);
  border-radius: var(--radius-sm);
  transition:
    transform 0.2s var(--ease-out, ease),
    box-shadow 0.2s ease;
}

.sale__digits span:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-sm);
}

@media (prefers-reduced-motion: reduce) {
  .sale__digits span:hover {
    transform: none;
  }
}

.sale__label {
  font-size: 13px;
  color: var(--color-ink-muted);
  text-transform: capitalize;
}

.sale__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  min-width: 200px;
  min-height: var(--tap-min);
  padding: 0 28px;
  background: linear-gradient(135deg, var(--color-highlight), #9a4034);
  color: #fff !important;
  font-weight: 700;
  font-size: 1rem;
  border-radius: 999px;
  text-decoration: none;
  box-shadow: 0 6px 22px rgba(196, 92, 60, 0.35);
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    filter 0.2s ease;
}

.sale__cta:hover {
  filter: brightness(1.05);
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(196, 92, 60, 0.4);
}

.sale__cta-ico {
  opacity: 0.95;
}
</style>
