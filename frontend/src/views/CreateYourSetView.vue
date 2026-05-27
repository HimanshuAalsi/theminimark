<script setup lang="ts">
import { Check, Loader2, PackagePlus, Sparkles, Tag, X } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { TransitionGroup } from 'vue'
import { formatCurrency, STORE_CURRENCY } from '@/lib/currency'
import {
  SET_DISCOUNT_TIERS,
  SET_SIZE_OPTIONS,
  discountedPrice,
  discountForSetSize,
  type SetSize,
} from '@/data/setBuilder'
import type { SiteProduct } from '@/data/siteContent'
import { useCartStore } from '@/stores/cart'
import { useCartUiStore } from '@/stores/cartUi'
import { useCatalogStore } from '@/stores/catalog'
import { storeToRefs } from 'pinia'

const catalogStore = useCatalogStore()
const { catalog, loading, ready } = storeToRefs(catalogStore)
const cart = useCartStore()
const cartUi = useCartUiStore()

void catalogStore.ensureLoaded()

const setSize = ref<SetSize>(4)
const selectedIds = ref<string[]>([])
const adding = ref(false)
const feedback = ref('')

const discountPct = computed(() => discountForSetSize(setSize.value))
const selectedProducts = computed(() =>
  catalog.value.filter((p) => selectedIds.value.includes(p.id))
)
const subtotalBefore = computed(() =>
  selectedProducts.value.reduce((n, p) => n + p.price, 0)
)
const subtotalAfter = computed(() =>
  selectedProducts.value.reduce((n, p) => n + discountedPrice(p.price, setSize.value), 0)
)
const canAdd = computed(
  () => selectedIds.value.length === setSize.value && selectedProducts.value.length > 0
)
const slotsLeft = computed(() => setSize.value - selectedIds.value.length)
const progressPct = computed(() =>
  Math.min(100, Math.round((selectedProducts.value.length / setSize.value) * 100))
)
const savings = computed(() => Math.max(0, subtotalBefore.value - subtotalAfter.value))
const emptySlots = computed(() =>
  Array.from({ length: Math.max(0, setSize.value - selectedProducts.value.length) })
)

const fmt = formatCurrency

function isSelected(id: string) {
  return selectedIds.value.includes(id)
}

function toggleProduct(product: SiteProduct) {
  feedback.value = ''
  const idx = selectedIds.value.indexOf(product.id)
  if (idx >= 0) {
    selectedIds.value.splice(idx, 1)
    return
  }
  if (selectedIds.value.length >= setSize.value) {
    feedback.value = `Your set holds ${setSize.value} items — remove one to swap.`
    return
  }
  selectedIds.value.push(product.id)
}

function removeFromSet(id: string) {
  feedback.value = ''
  selectedIds.value = selectedIds.value.filter((x) => x !== id)
}

function onSetSizeChange(size: SetSize) {
  setSize.value = size
  feedback.value = ''
  if (selectedIds.value.length > size) {
    selectedIds.value = selectedIds.value.slice(0, size)
  }
}

async function addSetToCart() {
  if (!canAdd.value) return
  adding.value = true
  feedback.value = ''
  try {
    const pct = discountPct.value
    for (const p of selectedProducts.value) {
      const price = discountedPrice(p.price, setSize.value)
      cart.addProduct({
        id: p.id,
        slug: p.slug,
        name: `${p.name} (Set of ${setSize.value}, −${pct}%)`,
        price,
        currency: STORE_CURRENCY,
        imageUrl: p.image,
      })
    }
    selectedIds.value = []
    feedback.value = `Added your set of ${setSize.value} to the cart with ${pct}% off.`
    cartUi.open()
  } finally {
    adding.value = false
  }
}
</script>

<template>
  <div class="set-page tm-section tm-animate-in">
    <div class="tm-container set-page__container">
      <div class="set-page__layout">
        <header class="set-page__head">
          <p class="set-page__eyebrow">
            <Sparkles :size="16" aria-hidden="true" />
            Build a bundle
          </p>
          <h1 class="set-page__title">Create your own set</h1>
          <p class="set-page__lead">
            Pick an even number of products — 2, 4, 6, or 8 — and save more as your set grows.
          </p>
        </header>

        <aside
          class="set-sidebar set-sidebar--desktop"
          :class="{ 'set-sidebar--complete': canAdd }"
          aria-label="Your set summary"
        >
          <div class="set-sidebar__glow" aria-hidden="true" />

          <header class="set-sidebar__head">
            <div class="set-sidebar__head-copy">
              <p class="set-sidebar__eyebrow">Your bundle</p>
              <h2 class="set-sidebar__title">Set of {{ setSize }}</h2>
            </div>
            <div
              class="set-sidebar__ring"
              :style="{ '--progress': `${progressPct}%` }"
              aria-hidden="true"
            >
              <svg viewBox="0 0 44 44">
                <circle class="set-sidebar__ring-track" cx="22" cy="22" r="18" />
                <circle class="set-sidebar__ring-fill" cx="22" cy="22" r="18" />
              </svg>
              <span class="set-sidebar__ring-label">{{ selectedProducts.length }}/{{ setSize }}</span>
            </div>
          </header>

          <div class="set-sidebar__bar" aria-hidden="true">
            <span class="set-sidebar__bar-fill" :style="{ width: `${progressPct}%` }" />
          </div>

          <div class="set-sidebar__badge">
            <Tag :size="14" :stroke-width="2.25" aria-hidden="true" />
            <span>{{ discountPct }}% bundle discount</span>
          </div>

          <div class="set-sidebar__body">
            <TransitionGroup name="set-item" tag="ul" class="set-sidebar__list">
              <li v-for="p in selectedProducts" :key="p.id" class="set-sidebar__item">
                <img :src="p.image" alt="" width="36" height="36" loading="lazy" />
                <div class="set-sidebar__item-copy">
                  <span class="set-sidebar__item-name">{{ p.name }}</span>
                  <span class="set-sidebar__item-price">
                    <s>{{ fmt(p.price) }}</s>
                    {{ fmt(discountedPrice(p.price, setSize)) }}
                  </span>
                </div>
                <button
                  type="button"
                  class="set-sidebar__item-remove"
                  aria-label="Remove from set"
                  @click="removeFromSet(p.id)"
                >
                  <X :size="14" :stroke-width="2.5" />
                </button>
              </li>
              <li
                v-for="(_, i) in emptySlots"
                :key="'slot-' + i"
                class="set-sidebar__slot"
              >
                <span class="set-sidebar__slot-dot" />
                <span>Pick item {{ selectedProducts.length + i + 1 }}</span>
              </li>
            </TransitionGroup>
          </div>

          <footer class="set-sidebar__foot">
            <dl class="set-sidebar__totals">
              <div class="set-sidebar__total-row">
                <dt>Regular total</dt>
                <dd>{{ fmt(subtotalBefore) }}</dd>
              </div>
              <div v-if="savings > 0" class="set-sidebar__total-row set-sidebar__total-row--save">
                <dt>You save</dt>
                <dd>−{{ fmt(savings) }}</dd>
              </div>
              <div class="set-sidebar__total-row set-sidebar__total-row--grand">
                <dt>Bundle total</dt>
                <dd>{{ fmt(subtotalAfter) }}</dd>
              </div>
            </dl>
            <button
              type="button"
              class="set-sidebar__cta tm-press"
              :disabled="!canAdd || adding"
              @click="addSetToCart"
            >
              <Loader2 v-if="adding" class="set-page__spin" :size="18" aria-hidden="true" />
              <PackagePlus v-else :size="18" :stroke-width="2.25" aria-hidden="true" />
              {{ adding ? 'Adding…' : canAdd ? `Add set of ${setSize} to cart` : `${slotsLeft} more to go` }}
            </button>
            <p class="set-sidebar__hint">Even sets only: {{ SET_SIZE_OPTIONS.join(', ') }} items.</p>
          </footer>
        </aside>

        <div class="set-page__main">
          <section class="set-page__tiers" aria-label="Set size and discount">
            <h2 class="set-page__h">Choose set size</h2>
            <div class="set-page__tier-grid">
              <button
                v-for="tier in SET_DISCOUNT_TIERS"
                :key="tier.size"
                type="button"
                class="set-page__tier"
                :class="{ 'set-page__tier--on': setSize === tier.size }"
                @click="onSetSizeChange(tier.size)"
              >
                <span class="set-page__tier-size">{{ tier.size }} items</span>
                <span class="set-page__tier-off">{{ tier.label }}</span>
              </button>
            </div>
            <p class="set-page__tier-note">
              Currently building a set of <strong>{{ setSize }}</strong> with
              <strong>{{ discountPct }}% off</strong> each item.
              <span v-if="slotsLeft > 0"> — {{ slotsLeft }} slot{{ slotsLeft === 1 ? '' : 's' }} left.</span>
              <span v-else> — set complete.</span>
            </p>
          </section>

          <section class="set-page__pick" aria-label="Pick products">
            <div class="set-page__pick-head">
              <h2 class="set-page__h">Pick your products</h2>
              <p v-if="!ready && loading" class="set-page__status">Loading catalogue…</p>
            </div>

            <p v-if="feedback" class="set-page__feedback" role="status">{{ feedback }}</p>

            <div v-if="ready" class="set-page__grid">
              <button
                v-for="p in catalog"
                :key="p.id"
                type="button"
                class="set-page__pick-card"
                :class="{ 'set-page__pick-card--on': isSelected(p.id) }"
                @click="toggleProduct(p)"
              >
                <span v-if="isSelected(p.id)" class="set-page__pick-check" aria-hidden="true">
                  <Check :size="14" :stroke-width="3" />
                </span>
                <img :src="p.image" :alt="p.name" width="200" height="200" loading="lazy" />
                <span class="set-page__pick-name">{{ p.name }}</span>
                <span class="set-page__pick-price">
                  <s>{{ fmt(p.price) }}</s>
                  {{ fmt(discountedPrice(p.price, setSize)) }}
                </span>
              </button>
            </div>
          </section>
        </div>
      </div>
    </div>

    <div class="set-page__dock" aria-label="Set checkout">
      <div class="set-page__dock-inner tm-container">
        <div class="set-page__dock-meta">
          <span class="set-page__dock-count">{{ selectedProducts.length }} / {{ setSize }} picked</span>
          <span class="set-page__dock-price">{{ fmt(subtotalAfter) }}</span>
          <span v-if="subtotalBefore > subtotalAfter" class="set-page__dock-save">
            Save {{ fmt(subtotalBefore - subtotalAfter) }}
          </span>
        </div>
        <button
          type="button"
          class="set-page__dock-cta tm-press"
          :disabled="!canAdd || adding"
          @click="addSetToCart"
        >
          <Loader2 v-if="adding" class="set-page__spin" :size="18" aria-hidden="true" />
          <PackagePlus v-else :size="18" :stroke-width="2.25" aria-hidden="true" />
          {{ adding ? 'Adding…' : canAdd ? 'Add to cart' : `${slotsLeft} more needed` }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.set-page {
  padding-top: 1.25rem;
  padding-bottom: 5.5rem;
}

.set-page__container {
  position: relative;
}

.set-page__layout {
  display: grid;
  gap: 1.5rem;
  align-items: start;
}

@media (min-width: 960px) {
  .set-page {
    padding-top: 0.85rem;
    padding-bottom: 4rem;
  }

  .set-page__layout {
    grid-template-columns: minmax(0, 1fr) min(19.5rem, 30%);
    grid-template-rows: auto auto;
    column-gap: 2rem;
    row-gap: 1.35rem;
  }

  .set-page__head {
    grid-column: 1;
    grid-row: 1;
    margin-bottom: 0;
  }

  .set-sidebar--desktop {
    grid-column: 2;
    grid-row: 1 / -1;
  }

  .set-page__main {
    grid-column: 1;
    grid-row: 2;
  }
}

.set-page__main {
  min-width: 0;
}

.set-page__head {
  max-width: 38rem;
  margin-bottom: 0.5rem;
}

.set-page__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0 0 0.5rem;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.set-page__title {
  margin: 0 0 0.5rem;
  font-family: var(--font-display);
  font-size: clamp(1.85rem, 3vw, 2.35rem);
  font-weight: 500;
}

.set-page__lead {
  margin: 0;
  color: var(--color-ink-muted);
  line-height: 1.6;
}

.set-page__h {
  margin: 0 0 0.85rem;
  font-family: var(--font-display);
  font-size: 1.15rem;
  font-weight: 500;
}

.set-page__tiers {
  margin-bottom: 2rem;
}

.set-page__tier-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(7rem, 1fr));
  gap: 0.65rem;
  max-width: 36rem;
}

.set-page__tier {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  padding: 0.85rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface-elevated);
  cursor: pointer;
  font: inherit;
  text-align: left;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.15s ease;
}

.set-page__tier:hover {
  transform: translateY(-1px);
}

.set-page__tier--on {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}

.set-page__tier-size {
  font-weight: 700;
  color: var(--color-ink);
}

.set-page__tier-off {
  font-size: 0.82rem;
  color: var(--color-accent);
  font-weight: 650;
}

.set-page__tier-note {
  margin: 0.85rem 0 0;
  font-size: 0.9rem;
  color: var(--color-ink-muted);
}

.set-page__pick {
  margin-bottom: 0;
}

.set-page__pick-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.set-page__status {
  margin: 0;
  font-size: 0.875rem;
  color: var(--color-ink-muted);
}

.set-page__feedback {
  margin: 0 0 1rem;
  padding: 0.65rem 0.85rem;
  border-radius: var(--radius-sm);
  background: var(--color-accent-soft);
  color: var(--color-ink);
  font-size: 0.875rem;
}

.set-page__grid {
  display: grid;
  gap: 0.85rem;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
}

.set-page__pick-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  padding: 0.65rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface-elevated);
  cursor: pointer;
  text-align: left;
  font: inherit;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.set-page__pick-card--on {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}

.set-page__pick-card img {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  border-radius: var(--radius-sm);
}

.set-page__pick-check {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 999px;
  background: var(--color-accent);
  color: #fff;
}

.set-page__pick-name {
  font-size: 0.8125rem;
  font-weight: 650;
  color: var(--color-ink);
  line-height: 1.35;
}

.set-page__pick-price {
  font-size: 0.8125rem;
  color: var(--color-accent);
  font-weight: 700;
}

.set-page__pick-price s {
  color: var(--color-ink-faint);
  font-weight: 500;
  margin-right: 0.35rem;
}

/* —— Set builder sidebar —— */
.set-sidebar--desktop {
  display: none;
}

@media (min-width: 960px) {
  .set-sidebar--desktop {
    display: flex;
    flex-direction: column;
    position: sticky;
    top: calc(var(--header-h, 68px) + 0.5rem);
    align-self: start;
    animation: set-sidebar-in 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
  }
}

@keyframes set-sidebar-in {
  from {
    opacity: 0;
    transform: translateX(1.25rem);
  }

  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.set-sidebar {
  position: relative;
  overflow: visible;
  border: 1px solid rgba(45, 92, 82, 0.14);
  border-radius: calc(var(--radius-md) + 4px);
  background: linear-gradient(165deg, #fffdfa 0%, var(--color-surface-elevated) 48%, #f7f3ec 100%);
  box-shadow:
    0 4px 6px rgba(20, 19, 18, 0.03),
    0 18px 40px rgba(45, 92, 82, 0.1);
}

.set-sidebar__glow {
  position: absolute;
  inset: -30% -20% auto;
  height: 9rem;
  background: radial-gradient(ellipse at 50% 0%, rgba(45, 92, 82, 0.16), transparent 68%);
  pointer-events: none;
}

.set-sidebar--complete .set-sidebar__glow {
  background: radial-gradient(ellipse at 50% 0%, rgba(196, 92, 60, 0.18), transparent 68%);
}

.set-sidebar__head {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 1rem 1.1rem 0.65rem;
}

.set-sidebar__eyebrow {
  margin: 0 0 0.2rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.set-sidebar__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 500;
  color: var(--color-ink);
}

.set-sidebar__ring {
  position: relative;
  width: 2.85rem;
  height: 2.85rem;
  flex-shrink: 0;
}

.set-sidebar__ring svg {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.set-sidebar__ring-track {
  fill: none;
  stroke: var(--color-border);
  stroke-width: 3.5;
}

.set-sidebar__ring-fill {
  fill: none;
  stroke: var(--color-accent);
  stroke-width: 3.5;
  stroke-linecap: round;
  stroke-dasharray: 113;
  stroke-dashoffset: calc(113 - (113 * var(--progress, 0) / 100));
  transition: stroke-dashoffset 0.45s cubic-bezier(0.22, 1, 0.36, 1);
}

.set-sidebar--complete .set-sidebar__ring-fill {
  stroke: var(--color-highlight);
}

.set-sidebar__ring-label {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.68rem;
  font-weight: 800;
  color: var(--color-ink-muted);
}

.set-sidebar__bar {
  position: relative;
  height: 3px;
  margin: 0 1.1rem 0.65rem;
  border-radius: 999px;
  background: var(--color-border);
  overflow: hidden;
}

.set-sidebar__bar-fill {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--color-accent), #3d7a6c);
  transition: width 0.45s cubic-bezier(0.22, 1, 0.36, 1);
}

.set-sidebar--complete .set-sidebar__bar-fill {
  background: linear-gradient(90deg, var(--color-highlight), var(--color-accent));
}

.set-sidebar__badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0 1.1rem 0.65rem;
  padding: 0.28rem 0.55rem;
  width: fit-content;
  border-radius: 999px;
  background: var(--color-accent-soft);
  color: var(--color-accent);
  font-size: 0.75rem;
  font-weight: 700;
}

.set-sidebar__body {
  flex: none;
  overflow: visible;
  padding: 0 0.85rem 0.65rem;
}

.set-sidebar__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.set-sidebar__item {
  display: grid;
  grid-template-columns: 2.25rem minmax(0, 1fr) auto;
  gap: 0.5rem;
  align-items: center;
  padding: 0.35rem 0.45rem;
  border-radius: var(--radius-sm);
  background: rgba(255, 255, 255, 0.72);
  border: 1px solid rgba(45, 92, 82, 0.1);
  box-shadow: 0 2px 8px rgba(20, 19, 18, 0.04);
}

.set-sidebar__item img {
  width: 3rem;
  height: 3rem;
  object-fit: cover;
  border-radius: 0.45rem;
}

.set-sidebar__item-copy {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.set-sidebar__item-name {
  font-size: 0.72rem;
  font-weight: 650;
  color: var(--color-ink);
  line-height: 1.25;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.set-sidebar__item-price {
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--color-accent);
}

.set-sidebar__item-price s {
  color: var(--color-ink-faint);
  font-weight: 500;
  margin-right: 0.25rem;
}

.set-sidebar__item-remove {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.65rem;
  height: 1.65rem;
  border: none;
  border-radius: 999px;
  background: transparent;
  color: var(--color-ink-faint);
  cursor: pointer;
  transition:
    background 0.2s ease,
    color 0.2s ease,
    transform 0.15s ease;
}

.set-sidebar__item-remove:hover {
  background: rgba(196, 92, 60, 0.12);
  color: var(--color-highlight);
  transform: scale(1.05);
}

.set-sidebar__slot {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.55rem;
  border-radius: var(--radius-sm);
  border: 1px dashed rgba(45, 92, 82, 0.22);
  color: var(--color-ink-faint);
  font-size: 0.78rem;
  font-weight: 600;
  animation: set-slot-pulse 2.4s ease-in-out infinite;
}

.set-sidebar__slot-dot {
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 999px;
  background: var(--color-border-strong);
}

@keyframes set-slot-pulse {
  0%,
  100% {
    opacity: 0.65;
  }

  50% {
    opacity: 1;
  }
}

.set-item-enter-active,
.set-item-leave-active {
  transition:
    opacity 0.28s ease,
    transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

.set-item-enter-from,
.set-item-leave-to {
  opacity: 0;
  transform: translateX(0.75rem) scale(0.96);
}

.set-item-move {
  transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}

.set-sidebar__foot {
  padding: 0.85rem 1.1rem 1rem;
  border-top: 1px solid rgba(45, 92, 82, 0.1);
  background: linear-gradient(180deg, transparent, rgba(255, 255, 255, 0.55));
}

.set-sidebar__totals {
  margin: 0 0 0.75rem;
  display: grid;
  gap: 0.35rem;
}

.set-sidebar__total-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  font-size: 0.8125rem;
}

.set-sidebar__total-row dt {
  color: var(--color-ink-muted);
}

.set-sidebar__total-row dd {
  margin: 0;
  font-weight: 700;
  color: var(--color-ink);
}

.set-sidebar__total-row--save dd {
  color: var(--color-highlight);
}

.set-sidebar__total-row--grand {
  margin-top: 0.25rem;
  padding-top: 0.55rem;
  border-top: 1px dashed var(--color-border);
  font-size: 0.9375rem;
}

.set-sidebar__total-row--grand dd {
  font-size: 1.15rem;
  color: var(--color-accent);
}

.set-sidebar__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  width: 100%;
  min-height: 2.65rem;
  border: none;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  color: #fff;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  box-shadow: 0 8px 22px rgba(45, 92, 82, 0.28);
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    filter 0.2s ease;
}

.set-sidebar__cta:hover:not(:disabled) {
  filter: brightness(1.04);
  transform: translateY(-1px);
  box-shadow: 0 10px 26px rgba(45, 92, 82, 0.32);
}

.set-sidebar__cta:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  box-shadow: none;
}

.set-sidebar--complete .set-sidebar__cta:not(:disabled) {
  background: linear-gradient(135deg, var(--color-highlight), var(--color-accent));
  animation: set-cta-glow 2s ease-in-out infinite;
}

@keyframes set-cta-glow {
  0%,
  100% {
    box-shadow: 0 8px 22px rgba(196, 92, 60, 0.25);
  }

  50% {
    box-shadow: 0 10px 28px rgba(196, 92, 60, 0.38);
  }
}

.set-sidebar__hint {
  margin: 0.7rem 0 0;
  font-size: 0.72rem;
  text-align: center;
  color: var(--color-ink-faint);
}

.set-page__spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.set-page__dock {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 90;
  padding: 0.65rem 0 calc(0.65rem + env(safe-area-inset-bottom, 0px));
  border-top: 1px solid var(--color-border);
  background: rgba(255, 252, 248, 0.94);
  backdrop-filter: blur(12px) saturate(1.15);
  -webkit-backdrop-filter: blur(12px) saturate(1.15);
  box-shadow: 0 -8px 28px rgba(20, 19, 18, 0.08);
}

@media (min-width: 960px) {
  .set-page__dock {
    display: none;
  }
}

.set-page__dock-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.85rem;
}

.set-page__dock-meta {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.set-page__dock-count {
  font-size: 0.78rem;
  font-weight: 650;
  color: var(--color-ink-muted);
}

.set-page__dock-price {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--color-accent);
  line-height: 1.2;
}

.set-page__dock-save {
  font-size: 0.72rem;
  color: var(--color-ink-faint);
}

.set-page__dock-cta {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  min-height: 2.75rem;
  padding: 0 1.1rem;
  border: none;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  color: #fff;
  font-weight: 700;
  font-size: 0.875rem;
  cursor: pointer;
  font-family: inherit;
  white-space: nowrap;
}

.set-page__dock-cta:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}
</style>
