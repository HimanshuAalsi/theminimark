<script setup lang="ts">
import { Check, Gift, Percent, Truck } from 'lucide-vue-next'
import { computed } from 'vue'
import type { MilestoneId } from '@/lib/cartMilestones'
import { buildCartMilestoneState, nextMilestoneHint } from '@/lib/cartMilestones'
import { formatCurrency } from '@/lib/currency'

const props = withDefaults(
  defineProps<{
    subtotal: number
    compact?: boolean
  }>(),
  { compact: false },
)

const fmt = formatCurrency
const state = computed(() => buildCartMilestoneState(props.subtotal))
const hint = computed(() => nextMilestoneHint(state.value, fmt))

const icons: Record<MilestoneId, typeof Gift> = {
  gift: Gift,
  shipping: Truck,
  discount: Percent,
}

/** Partial fill on connector leading into this milestone. */
function segmentProgress(index: number): number {
  const milestones = state.value.milestones
  const current = milestones[index]
  const prevThreshold = index === 0 ? 0 : (milestones[index - 1]?.threshold ?? 0)
  const sub = state.value.subtotal
  if (!current) return 0
  if (sub >= current.threshold) return 100
  if (sub <= prevThreshold) return 0
  const span = current.threshold - prevThreshold
  return span > 0 ? Math.min(100, ((sub - prevThreshold) / span) * 100) : 0
}
</script>

<template>
  <section class="perks" :class="{ 'perks--compact': compact }" aria-label="Order rewards">
    <p class="perks__hint" :class="{ 'perks__hint--done': !state.nextMilestone }" role="status">
      {{ hint }}
    </p>

    <div class="perks__steps">
      <div
        v-for="(m, index) in state.milestones"
        :key="m.id"
        class="perks__step"
        :class="{
          'perks__step--done': m.unlocked,
          'perks__step--next': state.nextMilestone?.id === m.id,
        }"
      >
        <!-- Connector line (not on first step) -->
        <div
          v-if="index > 0"
          class="perks__connector"
          aria-hidden="true"
        >
          <div
            class="perks__connector-fill"
            :style="{ width: `${segmentProgress(index)}%` }"
          />
        </div>

        <div class="perks__node">
          <div class="perks__icon" :title="m.label">
            <span v-if="m.unlocked" class="perks__check" aria-hidden="true">
              <Check :size="9" :stroke-width="3" />
            </span>
            <component :is="icons[m.id]" :size="14" :stroke-width="2.25" aria-hidden="true" />
          </div>
          <span class="perks__label">{{ m.shortLabel }}</span>
          <span class="perks__at">{{ fmt(m.threshold) }}</span>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.perks {
  flex-shrink: 0;
  padding: 0.6rem 0.7rem 0.7rem;
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
}

.perks__hint {
  margin: 0 0 0.65rem;
  padding: 0.4rem 0.55rem;
  border-radius: 8px;
  font-family: var(--font-ui);
  font-size: 0.6875rem;
  font-weight: 600;
  line-height: 1.35;
  color: var(--color-ink-muted);
  background: var(--color-page);
}

.perks__hint--done {
  color: var(--color-accent);
  background: var(--color-accent-soft);
}

.perks__steps {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  align-items: start;
  gap: 0;
}

.perks__step {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 0;
}

/* Horizontal connector between steps — sits behind icons, below icon row */
.perks__connector {
  position: absolute;
  top: 1.05rem;
  right: 50%;
  left: -50%;
  height: 3px;
  z-index: 0;
  background: var(--color-border);
  border-radius: 999px;
  overflow: hidden;
}

.perks__connector-fill {
  height: 100%;
  border-radius: inherit;
  background: var(--color-accent);
  transition: width 0.45s var(--ease-out, ease);
}

.perks__node {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
  width: 100%;
  padding: 0 0.15rem;
}

.perks__icon {
  position: relative;
  display: grid;
  place-items: center;
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background: var(--color-surface);
  border: 2px solid var(--color-border);
  color: var(--color-ink-faint);
  box-shadow: 0 1px 3px rgba(20, 19, 18, 0.06);
  transition:
    border-color 0.25s ease,
    color 0.25s ease,
    background 0.25s ease;
}

.perks__step--done .perks__icon {
  border-color: var(--color-accent);
  background: #fff;
  color: var(--color-accent);
}

.perks__step--next .perks__icon {
  border-color: var(--color-accent);
  color: var(--color-accent);
  box-shadow:
    0 0 0 3px var(--color-accent-soft),
    0 1px 4px rgba(20, 19, 18, 0.08);
}

.perks__check {
  position: absolute;
  top: -5px;
  right: -5px;
  z-index: 2;
  display: grid;
  place-items: center;
  width: 0.8rem;
  height: 0.8rem;
  border-radius: 50%;
  background: var(--color-accent);
  color: #fff;
  border: 1.5px solid var(--color-surface);
}

.perks__label {
  font-family: var(--font-ui);
  font-size: 0.625rem;
  font-weight: 700;
  line-height: 1.2;
  text-align: center;
  color: var(--color-ink);
  white-space: nowrap;
}

.perks__step--done .perks__label {
  color: var(--color-accent);
}

.perks__at {
  font-family: var(--font-ui);
  font-size: 0.5625rem;
  font-weight: 500;
  font-variant-numeric: tabular-nums;
  color: var(--color-ink-faint);
  white-space: nowrap;
}

.perks--compact {
  padding: 0.45rem 0.65rem 0.5rem;
}

.perks--compact .perks__hint {
  margin-bottom: 0.45rem;
  padding: 0.3rem 0.45rem;
  font-size: 0.625rem;
}

.perks--compact .perks__icon {
  width: 1.65rem;
  height: 1.65rem;
}

.perks--compact .perks__connector {
  top: 0.875rem;
}

.perks--compact .perks__label {
  font-size: 0.5625rem;
}

.perks--compact .perks__at {
  font-size: 0.5rem;
}

@media (prefers-reduced-motion: reduce) {
  .perks__connector-fill {
    transition: none;
  }
}
</style>
