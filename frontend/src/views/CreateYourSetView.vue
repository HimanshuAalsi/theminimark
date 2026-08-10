<script setup lang="ts">
import {
  Bookmark,
  Check,
  Gift,
  Loader2,
  PackagePlus,
  Sparkles,
  Tag,
  X,
} from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { TransitionGroup } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { formatCurrency, STORE_CURRENCY } from '@/lib/currency'
import {
  BOOKMARK_SET_TIERS,
  HAMPER_MAX_ITEMS,
  HAMPER_MIN_ITEMS,
  HAMPER_TIER_NOTES,
  applyDiscount,
  bookmarkDiscountForSize,
  bookmarkMaxForTier,
  bookmarkMinForTier,
  hamperDiscountForCount,
  isBookmarkOpenEnded,
  setModeFromQuery,
  type BookmarkSetSize,
  type SetMode,
} from '@/data/setBuilder'
import type { SiteProduct } from '@/data/siteContent'
import { useCartStore } from '@/stores/cart'
import { useCartUiStore } from '@/stores/cartUi'
import { useCatalogStore } from '@/stores/catalog'
import { storeToRefs } from 'pinia'

import { whatsappOrderLink } from '@/data/siteContact'

const HAMPER_NOTE_KEY = 'theminimark_hamper_note'

const route = useRoute()
const router = useRouter()
const catalogStore = useCatalogStore()
const { catalog, loading, ready } = storeToRefs(catalogStore)
const cart = useCartStore()
const cartUi = useCartUiStore()

void catalogStore.ensureLoaded()

const mode = ref<SetMode>(setModeFromQuery(route.query.mode))
const bookmarkSize = ref<BookmarkSetSize>(4)
const selectedIds = ref<string[]>([])
const adding = ref(false)
const feedback = ref('')
const hamperMessage = ref('')
const hamperPhone = ref('')

watch(
  () => route.query.mode,
  (m) => {
    mode.value = setModeFromQuery(m)
    selectedIds.value = []
    feedback.value = ''
  },
)

const isBookmarks = computed(() => mode.value === 'bookmarks')
const bookmarkOpenEnded = computed(
  () => isBookmarks.value && isBookmarkOpenEnded(bookmarkSize.value),
)
const bookmarkMin = computed(() => bookmarkMinForTier(bookmarkSize.value))
const bookmarkMax = computed(() => bookmarkMaxForTier(bookmarkSize.value))

const pickCatalog = computed(() => {
  if (isBookmarks.value) {
    return catalog.value.filter((p) => p.category === 'bookmarks')
  }
  return catalog.value
})

const discountPct = computed(() => {
  if (isBookmarks.value) return bookmarkDiscountForSize(bookmarkSize.value)
  return hamperDiscountForCount(selectedIds.value.length)
})

const selectedProducts = computed(() =>
  selectedIds.value
    .map((id) => catalog.value.find((p) => p.id === id))
    .filter((p): p is SiteProduct => Boolean(p)),
)

const subtotalBefore = computed(() => selectedProducts.value.reduce((n, p) => n + p.price, 0))
const subtotalAfter = computed(() =>
  selectedProducts.value.reduce((n, p) => n + applyDiscount(p.price, discountPct.value), 0),
)
const savings = computed(() => Math.max(0, subtotalBefore.value - subtotalAfter.value))

const canAdd = computed(() => {
  if (selectedProducts.value.length === 0) return false
  if (isBookmarks.value) {
    if (bookmarkOpenEnded.value) return selectedIds.value.length >= bookmarkMin.value
    return selectedIds.value.length === bookmarkSize.value
  }
  return selectedIds.value.length >= HAMPER_MIN_ITEMS
})

const isFull = computed(() => {
  if (isBookmarks.value) return selectedIds.value.length >= bookmarkMax.value
  return selectedIds.value.length >= HAMPER_MAX_ITEMS
})

const slotsLeft = computed(() => {
  if (isBookmarks.value) {
    return Math.max(0, bookmarkMin.value - selectedIds.value.length)
  }
  return Math.max(0, HAMPER_MIN_ITEMS - selectedIds.value.length)
})

const progressPct = computed(() => {
  if (isBookmarks.value) {
    const goal = bookmarkOpenEnded.value
      ? Math.max(bookmarkMin.value, selectedProducts.value.length)
      : bookmarkSize.value
    return Math.min(100, Math.round((selectedProducts.value.length / Math.max(1, goal)) * 100))
  }
  const goal = Math.max(HAMPER_MIN_ITEMS, selectedProducts.value.length)
  return Math.min(100, Math.round((selectedProducts.value.length / Math.max(HAMPER_MIN_ITEMS, goal)) * 100))
})

const emptySlots = computed(() => {
  if (!isBookmarks.value) return []
  // For 8+, only show empty slots until the minimum (8) is reached
  const target = bookmarkMin.value
  return Array.from({ length: Math.max(0, target - selectedProducts.value.length) })
})

const ctaLabel = computed(() => {
  if (adding.value) return 'Adding…'
  if (canAdd.value) {
    if (isBookmarks.value) {
      return bookmarkOpenEnded.value
        ? `Add ${selectedIds.value.length} bookmarks to cart`
        : `Add set of ${bookmarkSize.value} to cart`
    }
    return `Add hamper (${selectedIds.value.length} items) to cart`
  }
  if (isBookmarks.value) {
    return `Pick ${slotsLeft.value} more`
  }
  if (selectedIds.value.length === 0) return `Add at least ${HAMPER_MIN_ITEMS} products`
  return `Add ${slotsLeft.value} more for discount`
})

const fmt = formatCurrency

function setMode(next: SetMode) {
  mode.value = next
  selectedIds.value = []
  feedback.value = ''
  router.replace({ query: { ...route.query, mode: next === 'hamper' ? 'hamper' : undefined } })
}

function isSelected(id: string) {
  return selectedIds.value.includes(id)
}

function unitPrice(p: SiteProduct) {
  return applyDiscount(p.price, discountPct.value)
}

function toggleProduct(product: SiteProduct) {
  feedback.value = ''
  const idx = selectedIds.value.indexOf(product.id)
  if (idx >= 0) {
    selectedIds.value.splice(idx, 1)
    return
  }
  if (isBookmarks.value) {
    if (selectedIds.value.length >= bookmarkMax.value) {
      feedback.value = bookmarkOpenEnded.value
        ? `You can add up to ${bookmarkMax.value} bookmarks in one set.`
        : `Your set of ${bookmarkSize.value} is full. Remove one item to swap, then tap Add to cart.`
      return
    }
  } else if (selectedIds.value.length >= HAMPER_MAX_ITEMS) {
    feedback.value = `Hampers hold up to ${HAMPER_MAX_ITEMS} products.`
    return
  }
  selectedIds.value.push(product.id)
}

function removeFromSet(id: string) {
  feedback.value = ''
  selectedIds.value = selectedIds.value.filter((x) => x !== id)
}

function onBookmarkSizeChange(size: BookmarkSetSize) {
  bookmarkSize.value = size
  feedback.value = ''
  const max = bookmarkMaxForTier(size)
  if (selectedIds.value.length > max) {
    selectedIds.value = selectedIds.value.slice(0, max)
  }
}

async function addSetToCart() {
  if (!canAdd.value) return
  adding.value = true
  feedback.value = ''
  try {
    const pct = discountPct.value
    const count = selectedProducts.value.length
    const label = isBookmarks.value
      ? bookmarkOpenEnded.value && count > 8
        ? `Bookmark set of ${count} (8+)`
        : `Bookmark set of ${count}`
      : `Custom hamper (${count})`
    for (const p of selectedProducts.value) {
      const price = applyDiscount(p.price, pct)
      cart.addProduct({
        id: p.id,
        slug: p.slug,
        name: pct > 0 ? `${p.name} (${label}, −${pct}%)` : `${p.name} (${label})`,
        price,
        currency: STORE_CURRENCY,
        imageUrl: p.image,
      })
    }
    if (!isBookmarks.value && (hamperMessage.value.trim() || hamperPhone.value.trim())) {
      try {
        localStorage.setItem(
          HAMPER_NOTE_KEY,
          JSON.stringify({
            message: hamperMessage.value.trim(),
            phone: hamperPhone.value.trim(),
          }),
        )
      } catch {
        /* ignore */
      }
    }
    selectedIds.value = []
    feedback.value =
      pct > 0
        ? `Added ${count} items with ${pct}% off.`
        : `Added ${count} items to your cart.`
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
            Choose a bookmark-only set with tiered savings, or build a mixed hamper from the whole shop.
          </p>

          <div class="set-page__modes" role="tablist" aria-label="Bundle type">
            <button
              type="button"
              role="tab"
              class="set-page__mode"
              :class="{ 'set-page__mode--on': isBookmarks }"
              :aria-selected="isBookmarks"
              @click="setMode('bookmarks')"
            >
              <Bookmark :size="16" :stroke-width="2.25" aria-hidden="true" />
              Bookmark set
            </button>
            <button
              type="button"
              role="tab"
              class="set-page__mode"
              :class="{ 'set-page__mode--on': !isBookmarks }"
              :aria-selected="!isBookmarks"
              @click="setMode('hamper')"
            >
              <Gift :size="16" :stroke-width="2.25" aria-hidden="true" />
              Build a hamper
            </button>
          </div>
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
              <h2 class="set-sidebar__title">
                <template v-if="isBookmarks">
                  <template v-if="bookmarkOpenEnded">
                    Set of 8+ · {{ selectedProducts.length }} picked
                  </template>
                  <template v-else>Set of {{ bookmarkSize }}</template>
                </template>
                <template v-else>Hamper · {{ selectedProducts.length }} items</template>
              </h2>
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
              <span class="set-sidebar__ring-label">
                <template v-if="isBookmarks && !bookmarkOpenEnded"
                  >{{ selectedProducts.length }}/{{ bookmarkSize }}</template
                >
                <template v-else>{{ selectedProducts.length }}</template>
              </span>
            </div>
          </header>

          <div class="set-sidebar__bar" aria-hidden="true">
            <span class="set-sidebar__bar-fill" :style="{ width: `${progressPct}%` }" />
          </div>

          <div class="set-sidebar__badge">
            <Tag :size="14" :stroke-width="2.25" aria-hidden="true" />
            <span v-if="discountPct > 0">{{ discountPct }}% bundle discount</span>
            <span v-else-if="isBookmarks">Pick your set size</span>
            <span v-else>Add {{ HAMPER_MIN_ITEMS }}+ for 10% off</span>
          </div>

          <div class="set-sidebar__body">
            <TransitionGroup name="set-item" tag="ul" class="set-sidebar__list">
              <li v-for="p in selectedProducts" :key="p.id" class="set-sidebar__item">
                <img :src="p.image" alt="" width="36" height="36" loading="lazy" />
                <div class="set-sidebar__item-copy">
                  <span class="set-sidebar__item-name">{{ p.name }}</span>
                  <span class="set-sidebar__item-price">
                    <s v-if="discountPct > 0">{{ fmt(p.price) }}</s>
                    {{ fmt(unitPrice(p)) }}
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
            <p v-if="!isBookmarks && selectedProducts.length === 0" class="set-sidebar__empty">
              Add any products — discount unlocks at {{ HAMPER_MIN_ITEMS }}.
            </p>
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
              :class="{ 'set-sidebar__cta--ready': canAdd }"
              :disabled="!canAdd || adding"
              @click="addSetToCart"
            >
              <Loader2 v-if="adding" class="set-page__spin" :size="18" aria-hidden="true" />
              <PackagePlus v-else :size="18" :stroke-width="2.25" aria-hidden="true" />
              {{ ctaLabel }}
            </button>
            <p class="set-sidebar__hint">
              <template v-if="isBookmarks && bookmarkOpenEnded">
                Pick at least 8 bookmarks — you can keep adding more. Each gets 20% off.
              </template>
              <template v-else-if="isBookmarks">
                Fill all {{ bookmarkSize }} slots, then tap Add to cart.
              </template>
              <template v-else>
                Minimum {{ HAMPER_MIN_ITEMS }} products · {{ HAMPER_TIER_NOTES[0].label }} ·
                {{ HAMPER_TIER_NOTES[1].label }}
              </template>
            </p>
          </footer>
        </aside>

        <div class="set-page__main">
          <section v-if="isBookmarks" class="set-page__tiers" aria-label="Set size and discount">
            <h2 class="set-page__h">Choose set size</h2>
            <div class="set-page__tier-grid">
              <button
                v-for="tier in BOOKMARK_SET_TIERS"
                :key="tier.size"
                type="button"
                class="set-page__tier"
                :class="{ 'set-page__tier--on': bookmarkSize === tier.size }"
                @click="onBookmarkSizeChange(tier.size)"
              >
                <span class="set-page__tier-size">{{ tier.sizeLabel }}</span>
                <span class="set-page__tier-off">{{ tier.label }}</span>
              </button>
            </div>
            <p class="set-page__tier-note">
              <template v-if="bookmarkOpenEnded">
                Building an <strong>8+ set</strong> with <strong>20% off</strong>.
                Add at least 8 bookmarks — keep adding as many as you like.
                <span v-if="slotsLeft > 0"> — {{ slotsLeft }} more to unlock Add.</span>
                <span v-else-if="canAdd"> — ready to add {{ selectedProducts.length }} to cart (or keep picking).</span>
              </template>
              <template v-else>
                Building a <strong>set of {{ bookmarkSize }}</strong> bookmarks with
                <strong>{{ discountPct }}% off</strong>.
                <span v-if="slotsLeft > 0"> — {{ slotsLeft }} slot{{ slotsLeft === 1 ? '' : 's' }} left.</span>
                <span v-else-if="canAdd"> — set complete. Use <strong>Add set to cart</strong> below.</span>
              </template>
            </p>
          </section>

          <section v-else class="set-page__tiers" aria-label="Hamper discounts">
            <h2 class="set-page__h">Hamper savings</h2>
            <div class="set-page__tier-grid set-page__tier-grid--hamper">
              <div
                v-for="tier in HAMPER_TIER_NOTES"
                :key="tier.min"
                class="set-page__tier set-page__tier--static"
                :class="{ 'set-page__tier--on': discountPct === tier.discountPercent }"
              >
                <span class="set-page__tier-size">{{ tier.min }}+ items</span>
                <span class="set-page__tier-off">{{ tier.discountPercent }}% off</span>
              </div>
            </div>
            <p class="set-page__tier-note">
              Mix any products. Discount applies automatically once you reach
              {{ HAMPER_MIN_ITEMS }} items (currently
              <strong>{{ selectedProducts.length }}</strong>
              ·
              <strong>{{ discountPct || 0 }}% off</strong>).
            </p>
          </section>

          <section v-if="!isBookmarks" class="set-page__hamper-note" aria-label="Hamper message">
            <h2 class="set-page__h">Custom message or team contact</h2>
            <p class="set-page__hamper-lead">
              Add a gift note for your hamper, or leave your number so our team can help curate it.
            </p>
            <label class="set-page__field">
              <span class="set-page__label">Gift message (optional)</span>
              <textarea
                v-model="hamperMessage"
                class="set-page__textarea"
                rows="3"
                maxlength="500"
                placeholder="Write a short message to include with the hamper…"
              />
            </label>
            <label class="set-page__field">
              <span class="set-page__label">Your phone / WhatsApp (optional)</span>
              <input
                v-model="hamperPhone"
                type="tel"
                class="set-page__input"
                placeholder="10-digit mobile number"
                autocomplete="tel"
              />
            </label>
            <p class="set-page__hamper-contact">
              Prefer to chat first?
              <a
                :href="whatsappOrderLink('Hi! I\'d like help building a custom hamper.')"
                target="_blank"
                rel="noopener noreferrer"
              >
                Message our team on WhatsApp
              </a>
            </p>
          </section>

          <section class="set-page__pick" aria-label="Pick products">
            <div class="set-page__pick-head">
              <h2 class="set-page__h">
                {{ isBookmarks ? 'Pick bookmarks only' : 'Pick from all products' }}
              </h2>
              <p v-if="!ready && loading" class="set-page__status">Loading catalogue…</p>
              <p v-else-if="isFull && isBookmarks" class="set-page__status set-page__status--ok">
                Reached the pick limit — remove one to swap, or add to cart.
              </p>
              <p
                v-else-if="bookmarkOpenEnded && canAdd"
                class="set-page__status set-page__status--ok"
              >
                8+ unlocked — keep adding bookmarks, or add to cart now.
              </p>
            </div>

            <p v-if="feedback" class="set-page__feedback" role="status">{{ feedback }}</p>

            <div v-if="ready" class="set-page__grid">
              <button
                v-for="p in pickCatalog"
                :key="p.id"
                type="button"
                class="set-page__pick-card"
                :class="{
                  'set-page__pick-card--on': isSelected(p.id),
                  'set-page__pick-card--locked': isFull && !isSelected(p.id),
                }"
                :disabled="isFull && !isSelected(p.id)"
                @click="toggleProduct(p)"
              >
                <span v-if="isSelected(p.id)" class="set-page__pick-check" aria-hidden="true">
                  <Check :size="14" :stroke-width="3" />
                </span>
                <img :src="p.image" :alt="p.name" width="200" height="200" loading="lazy" />
                <span class="set-page__pick-name">{{ p.name }}</span>
                <span class="set-page__pick-price">
                  <s v-if="discountPct > 0 && (isSelected(p.id) || canAdd || isBookmarks)">{{ fmt(p.price) }}</s>
                  {{
                    fmt(
                      isBookmarks || selectedIds.length >= HAMPER_MIN_ITEMS || isSelected(p.id)
                        ? unitPrice(p)
                        : p.price,
                    )
                  }}
                </span>
              </button>
            </div>
            <p v-else-if="ready && pickCatalog.length === 0" class="set-page__status">
              No products available for this mode yet.
            </p>
          </section>
        </div>
      </div>
    </div>

    <div class="set-page__dock" aria-label="Set checkout">
      <div class="set-page__dock-inner tm-container">
        <div class="set-page__dock-meta">
          <span class="set-page__dock-count">
            <template v-if="isBookmarks && bookmarkOpenEnded"
              >{{ selectedProducts.length }} picked (8+)</template
            >
            <template v-else-if="isBookmarks"
              >{{ selectedProducts.length }} / {{ bookmarkSize }} picked</template
            >
            <template v-else>{{ selectedProducts.length }} picked</template>
          </span>
          <span class="set-page__dock-price">{{ fmt(subtotalAfter) }}</span>
          <span v-if="savings > 0" class="set-page__dock-save">Save {{ fmt(savings) }}</span>
        </div>
        <button
          type="button"
          class="set-page__dock-cta tm-press"
          :class="{ 'set-page__dock-cta--ready': canAdd }"
          :disabled="!canAdd || adding"
          @click="addSetToCart"
        >
          <Loader2 v-if="adding" class="set-page__spin" :size="18" aria-hidden="true" />
          <PackagePlus v-else :size="18" :stroke-width="2.25" aria-hidden="true" />
          {{ adding ? 'Adding…' : canAdd ? 'Add to cart' : ctaLabel }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.set-page {
  --set-surface: var(--color-surface-elevated, var(--tm-surface-2, #fff));
  --set-page-bg: var(--color-page, var(--tm-page, #f7f3ec));
  --set-ink: var(--color-ink, var(--tm-ink, #141312));
  --set-muted: var(--color-ink-muted, var(--tm-ink-muted, #5c5854));
  --set-faint: var(--color-ink-faint, var(--tm-ink-faint, #8a857e));
  --set-border: var(--color-border, var(--tm-border, #e4ddd3));
  --set-accent: var(--color-accent, var(--tm-accent, #2d5c52));
  --set-accent-soft: var(--color-accent-soft, rgba(45, 92, 82, 0.12));

  padding-top: 1.25rem;
  padding-bottom: 5.5rem;
  background: var(--set-page-bg);
  color: var(--set-ink);
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
  max-width: 40rem;
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
  color: var(--set-accent);
}

.set-page__title {
  margin: 0 0 0.5rem;
  font-family: var(--font-display);
  font-size: clamp(1.85rem, 3vw, 2.35rem);
  font-weight: 500;
  color: var(--set-ink);
}

.set-page__lead {
  margin: 0 0 1rem;
  color: var(--set-muted);
  line-height: 1.6;
}

.set-page__modes {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  padding: 0.25rem;
  border-radius: 999px;
  border: 1px solid var(--set-border);
  background: var(--set-surface);
}

.set-page__mode {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.55rem 0.95rem;
  border: none;
  border-radius: 999px;
  background: transparent;
  color: var(--set-muted);
  font: inherit;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
}

.set-page__mode--on {
  background: var(--tm-gradient, var(--set-accent));
  color: var(--tm-on-accent, #fff);
}

.set-page__h {
  margin: 0 0 0.85rem;
  font-family: var(--font-display);
  font-size: 1.15rem;
  font-weight: 500;
  color: var(--set-ink);
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

.set-page__tier-grid--hamper {
  max-width: 20rem;
}

.set-page__tier {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  padding: 0.85rem 0.75rem;
  border: 1px solid var(--set-border);
  border-radius: var(--radius-md, 12px);
  background: var(--set-surface);
  color: var(--set-ink);
  cursor: pointer;
  font: inherit;
  text-align: left;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.15s ease;
}

.set-page__tier--static {
  cursor: default;
}

.set-page__tier:hover:not(.set-page__tier--static) {
  transform: translateY(-1px);
}

.set-page__tier--on {
  border-color: var(--set-accent);
  box-shadow: 0 0 0 3px var(--set-accent-soft);
}

.set-page__tier-size {
  font-weight: 700;
  color: var(--set-ink);
}

.set-page__tier-off {
  font-size: 0.82rem;
  color: var(--set-accent);
  font-weight: 650;
}

.set-page__tier-note {
  margin: 0.85rem 0 0;
  font-size: 0.9rem;
  color: var(--set-muted);
}

.set-page__hamper-note {
  margin: 1.5rem 0 0;
  padding: 1.15rem 1.2rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--set-border);
  background: var(--set-surface);
}

.set-page__hamper-lead {
  margin: 0 0 1rem;
  font-size: 0.92rem;
  color: var(--set-muted);
  line-height: 1.5;
}

.set-page__field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 0.85rem;
}

.set-page__label {
  font-size: 0.82rem;
  font-weight: 600;
}

.set-page__textarea,
.set-page__input {
  width: 100%;
  padding: 0.65rem 0.75rem;
  border: 1px solid var(--set-border);
  border-radius: var(--radius-md);
  font: inherit;
  background: var(--set-bg);
  color: var(--set-ink);
}

.set-page__hamper-contact {
  margin: 0;
  font-size: 0.88rem;
  color: var(--set-muted);
}

.set-page__hamper-contact a {
  color: var(--set-accent);
  font-weight: 600;
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
  color: var(--set-muted);
}

.set-page__status--ok {
  color: var(--set-accent);
  font-weight: 650;
}

.set-page__feedback {
  margin: 0 0 1rem;
  padding: 0.65rem 0.85rem;
  border-radius: var(--radius-sm, 8px);
  background: var(--set-accent-soft);
  color: var(--set-ink);
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
  border: 1px solid var(--set-border);
  border-radius: var(--radius-md, 12px);
  background: var(--set-surface);
  color: var(--set-ink);
  cursor: pointer;
  text-align: left;
  font: inherit;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    opacity 0.2s ease;
}

.set-page__pick-card--on {
  border-color: var(--set-accent);
  box-shadow: 0 0 0 3px var(--set-accent-soft);
}

.set-page__pick-card--locked {
  opacity: 0.45;
  cursor: not-allowed;
}

.set-page__pick-card img {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  border-radius: var(--radius-sm, 8px);
  background: var(--set-border);
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
  background: var(--set-accent);
  color: #fff;
}

.set-page__pick-name {
  font-size: 0.8125rem;
  font-weight: 650;
  color: var(--set-ink);
  line-height: 1.35;
}

.set-page__pick-price {
  font-size: 0.8125rem;
  color: var(--set-accent);
  font-weight: 700;
}

.set-page__pick-price s {
  color: var(--set-faint);
  font-weight: 500;
  margin-right: 0.35rem;
}

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
  }
}

.set-sidebar {
  position: relative;
  overflow: hidden;
  border: 1px solid var(--set-border);
  border-radius: calc(var(--radius-md, 12px) + 4px);
  background: var(--set-surface);
  box-shadow: 0 12px 32px rgba(20, 19, 18, 0.08);
}

.set-sidebar__glow {
  position: absolute;
  inset: -30% -20% auto;
  height: 9rem;
  background: radial-gradient(ellipse at 50% 0%, var(--set-accent-soft), transparent 68%);
  pointer-events: none;
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
  color: var(--set-accent);
}

.set-sidebar__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 500;
  color: var(--set-ink);
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
  stroke: var(--set-border);
  stroke-width: 3.5;
}

.set-sidebar__ring-fill {
  fill: none;
  stroke: var(--set-accent);
  stroke-width: 3.5;
  stroke-linecap: round;
  stroke-dasharray: 113;
  stroke-dashoffset: calc(113 - (113 * var(--progress, 0) / 100));
  transition: stroke-dashoffset 0.45s cubic-bezier(0.22, 1, 0.36, 1);
}

.set-sidebar__ring-label {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  font-size: 0.65rem;
  font-weight: 800;
  color: var(--set-ink);
}

.set-sidebar__bar {
  margin: 0 1.1rem 0.75rem;
  height: 0.35rem;
  border-radius: 999px;
  background: var(--set-border);
  overflow: hidden;
}

.set-sidebar__bar-fill {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: var(--tm-gradient, var(--set-accent));
  transition: width 0.35s ease;
}

.set-sidebar__badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0 1.1rem 0.85rem;
  padding: 0.35rem 0.65rem;
  border-radius: 999px;
  background: var(--set-accent-soft);
  color: var(--set-accent);
  font-size: 0.75rem;
  font-weight: 700;
}

.set-sidebar__body {
  padding: 0 1.1rem 0.5rem;
  min-height: 8rem;
}

.set-sidebar__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.set-sidebar__item,
.set-sidebar__slot {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.4rem 0.45rem;
  border-radius: 10px;
  background: var(--set-page-bg);
  border: 1px solid transparent;
}

.set-sidebar__item img {
  width: 36px;
  height: 36px;
  object-fit: cover;
  border-radius: 8px;
  flex-shrink: 0;
}

.set-sidebar__item-copy {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.set-sidebar__item-name {
  font-size: 0.78rem;
  font-weight: 650;
  color: var(--set-ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.set-sidebar__item-price {
  font-size: 0.72rem;
  color: var(--set-accent);
  font-weight: 700;
}

.set-sidebar__item-price s {
  color: var(--set-faint);
  font-weight: 500;
  margin-right: 0.25rem;
}

.set-sidebar__item-remove {
  display: grid;
  place-items: center;
  width: 1.6rem;
  height: 1.6rem;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--set-muted);
  cursor: pointer;
}

.set-sidebar__slot {
  border-style: dashed;
  border-color: var(--set-border);
  color: var(--set-faint);
  font-size: 0.78rem;
}

.set-sidebar__slot-dot {
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 999px;
  border: 1.5px dashed var(--set-border);
}

.set-sidebar__empty {
  margin: 0.5rem 0 0;
  font-size: 0.8rem;
  color: var(--set-muted);
}

.set-sidebar__foot {
  padding: 0.85rem 1.1rem 1.1rem;
  border-top: 1px solid var(--set-border);
}

.set-sidebar__totals {
  margin: 0 0 0.85rem;
}

.set-sidebar__total-row {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  margin: 0 0 0.35rem;
  font-size: 0.82rem;
  color: var(--set-muted);
}

.set-sidebar__total-row dt,
.set-sidebar__total-row dd {
  margin: 0;
}

.set-sidebar__total-row--save {
  color: var(--set-accent);
  font-weight: 700;
}

.set-sidebar__total-row--grand {
  margin-top: 0.35rem;
  padding-top: 0.45rem;
  border-top: 1px dashed var(--set-border);
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--set-ink);
}

.set-sidebar__cta,
.set-page__dock-cta {
  display: inline-flex;
  width: 100%;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  min-height: 2.85rem;
  padding: 0 1rem;
  border: none;
  border-radius: 999px;
  background: var(--tm-gradient, var(--set-accent));
  color: var(--tm-on-accent, #fff);
  font: inherit;
  font-size: 0.9rem;
  font-weight: 700;
  cursor: pointer;
  opacity: 0.55;
}

.set-sidebar__cta--ready,
.set-page__dock-cta--ready {
  opacity: 1;
  box-shadow: var(--tm-shadow-accent, 0 8px 20px rgba(45, 92, 82, 0.25));
}

.set-sidebar__cta:disabled,
.set-page__dock-cta:disabled {
  cursor: not-allowed;
}

.set-sidebar__hint {
  margin: 0.65rem 0 0;
  font-size: 0.72rem;
  line-height: 1.45;
  color: var(--set-faint);
}

.set-page__spin {
  animation: set-spin 0.8s linear infinite;
}

@keyframes set-spin {
  to {
    transform: rotate(360deg);
  }
}

.set-page__dock {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 40;
  padding: 0.75rem 0 calc(0.75rem + env(safe-area-inset-bottom, 0));
  background: color-mix(in srgb, var(--set-surface) 92%, transparent);
  border-top: 1px solid var(--set-border);
  backdrop-filter: blur(10px);
}

@media (min-width: 960px) {
  .set-page__dock {
    display: none;
  }
}

.set-page__dock-inner {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.set-page__dock-meta {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 0.45rem 0.75rem;
}

.set-page__dock-count {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--set-muted);
}

.set-page__dock-price {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--set-ink);
}

.set-page__dock-save {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--set-accent);
}

.set-page__dock-cta {
  width: auto;
  min-width: 9.5rem;
  flex-shrink: 0;
}

.set-item-enter-active,
.set-item-leave-active {
  transition: all 0.2s ease;
}

.set-item-enter-from,
.set-item-leave-to {
  opacity: 0;
  transform: translateX(0.5rem);
}
</style>
