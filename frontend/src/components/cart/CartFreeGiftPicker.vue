<script setup lang="ts">
import { Check, ChevronDown, Gift } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import type { FreeGiftOption } from '@/lib/freeGift'
import { useCartStore } from '@/stores/cart'

const props = withDefaults(
  defineProps<{
    options: FreeGiftOption[]
    /** Tighter layout for cart drawer sidebar */
    compact?: boolean
  }>(),
  { compact: false },
)

const cart = useCartStore()
const showPicker = ref(!cart.selectedFreeGift)
const selectedId = computed(() => cart.selectedFreeGift?.id ?? null)

watch(
  () => cart.selectedFreeGift,
  (gift) => {
    if (gift) showPicker.value = false
  },
)

function select(option: FreeGiftOption) {
  cart.selectFreeGift(option)
  showPicker.value = false
}

function openPicker() {
  showPicker.value = true
}
</script>

<template>
  <section
    class="gift"
    :class="{ 'gift--compact': compact }"
    aria-label="Choose your free gift"
  >
    <div v-if="cart.selectedFreeGift && !showPicker" class="gift__chosen">
      <img
        v-if="cart.selectedFreeGift.image"
        :src="cart.selectedFreeGift.image"
        alt=""
        class="gift__chosen-img"
      />
      <span v-else class="gift__chosen-ph" aria-hidden="true"><Gift :size="16" /></span>
      <div class="gift__chosen-text">
        <span class="gift__chosen-label">Free gift</span>
        <span class="gift__chosen-name">{{ cart.selectedFreeGift.name }}</span>
      </div>
      <button type="button" class="gift__change" @click="openPicker">Change</button>
    </div>

    <template v-else>
      <div class="gift__head">
        <p class="gift__heading">
          <Gift :size="14" :stroke-width="2.5" aria-hidden="true" />
          Pick a free gift
        </p>
        <p v-if="!compact" class="gift__hint">Included with orders ₹199+ — choose one before checkout.</p>
      </div>

      <ul
        v-if="options.length"
        class="gift__grid"
        :class="{ 'gift__grid--strip': compact }"
        role="list"
      >
        <li v-for="opt in options" :key="opt.id" class="gift__cell">
          <button
            type="button"
            class="gift__card"
            :class="{ 'gift__card--on': selectedId === opt.id }"
            :aria-pressed="selectedId === opt.id"
            @click="select(opt)"
          >
            <span class="gift__thumb">
              <img v-if="opt.image" :src="opt.image" alt="" class="gift__img" loading="lazy" />
              <span v-else class="gift__ph" aria-hidden="true"><Gift :size="14" /></span>
              <span v-if="selectedId === opt.id" class="gift__check" aria-hidden="true">
                <Check :size="11" :stroke-width="3" />
              </span>
            </span>
            <span class="gift__name">{{ opt.name }}</span>
          </button>
        </li>
      </ul>
      <p v-else class="gift__loading">Loading gifts…</p>

      <button
        v-if="cart.selectedFreeGift && showPicker"
        type="button"
        class="gift__collapse"
        @click="showPicker = false"
      >
        <ChevronDown :size="14" aria-hidden="true" />
        Done
      </button>
    </template>
  </section>
</template>

<style scoped>
.gift {
  padding: 0.65rem 0.75rem;
  border-top: 1px solid var(--color-border);
  background: var(--color-surface);
}

.gift--compact {
  padding: 0.55rem 0.65rem;
  margin: 0;
  border-top: 1px dashed var(--color-border);
  background: transparent;
}

.gift__head {
  margin-bottom: 0.45rem;
}

.gift--compact .gift__head {
  margin-bottom: 0.35rem;
}

.gift__chosen {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.45rem;
  border-radius: 10px;
  background: var(--color-accent-soft);
  border: 1px solid rgba(45, 92, 82, 0.2);
}

.gift__chosen-img,
.gift__chosen-ph {
  flex-shrink: 0;
  width: 2.35rem;
  height: 2.35rem;
  border-radius: 7px;
  object-fit: cover;
}

.gift--compact .gift__chosen-img,
.gift--compact .gift__chosen-ph {
  width: 2.15rem;
  height: 2.15rem;
}

.gift__chosen-ph {
  display: grid;
  place-items: center;
  color: var(--color-accent);
  background: #fff;
}

.gift__chosen-text {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.05rem;
}

.gift__chosen-label {
  font-family: var(--font-ui);
  font-size: 0.5625rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.gift__chosen-name {
  font-family: var(--font-ui);
  font-size: 0.72rem;
  font-weight: 600;
  line-height: 1.25;
  color: var(--color-ink);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.gift__change {
  flex-shrink: 0;
  padding: 0.25rem 0.5rem;
  border: 1px solid var(--color-border);
  border-radius: 7px;
  background: #fff;
  font-family: var(--font-ui);
  font-size: 0.65rem;
  font-weight: 600;
  color: var(--color-ink-muted);
  cursor: pointer;
}

.gift__change:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
}

.gift__heading {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  margin: 0;
  font-family: var(--font-ui);
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-ink);
}

.gift--compact .gift__heading {
  font-size: 0.6875rem;
}

.gift__hint {
  margin: 0.15rem 0 0;
  font-size: 0.625rem;
  line-height: 1.35;
  color: var(--color-ink-faint);
}

.gift__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.4rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.gift__grid--strip {
  display: flex;
  flex-wrap: nowrap;
  gap: 0.35rem;
  overflow-x: auto;
  padding-bottom: 0.15rem;
  scrollbar-width: thin;
  -webkit-overflow-scrolling: touch;
}

.gift__grid--strip::-webkit-scrollbar {
  height: 4px;
}

.gift__cell {
  flex-shrink: 0;
}

.gift__grid--strip .gift__cell {
  width: 4.5rem;
}

.gift__card {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  width: 100%;
  padding: 0.3rem;
  border: 1.5px solid var(--color-border);
  border-radius: 9px;
  background: #fff;
  cursor: pointer;
  text-align: left;
  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease;
}

.gift__grid--strip .gift__card {
  padding: 0.25rem;
  border-radius: 8px;
}

.gift__card:hover {
  border-color: rgba(45, 92, 82, 0.35);
}

.gift__card--on {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 1px var(--color-accent-soft);
}

.gift__thumb {
  position: relative;
  display: block;
  aspect-ratio: 1;
  border-radius: 6px;
  overflow: hidden;
  background: var(--color-page);
}

.gift__grid--strip .gift__thumb {
  border-radius: 5px;
}

.gift__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.gift__ph {
  display: grid;
  place-items: center;
  width: 100%;
  height: 100%;
  color: var(--color-accent);
  background: var(--color-accent-soft);
}

.gift__check {
  position: absolute;
  right: 0.15rem;
  bottom: 0.15rem;
  display: grid;
  place-items: center;
  width: 1rem;
  height: 1rem;
  border-radius: 999px;
  color: #fff;
  background: var(--color-accent);
}

.gift__name {
  display: block;
  margin-top: 0.25rem;
  font-family: var(--font-ui);
  font-size: 0.6rem;
  font-weight: 600;
  line-height: 1.2;
  color: var(--color-ink);
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.gift__grid--strip .gift__name {
  margin-top: 0.2rem;
  font-size: 0.5625rem;
  -webkit-line-clamp: 2;
}

.gift__loading {
  margin: 0;
  font-size: 0.625rem;
  color: var(--color-ink-faint);
}

.gift__collapse {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.35rem;
  padding: 0;
  border: none;
  background: none;
  font-family: var(--font-ui);
  font-size: 0.65rem;
  font-weight: 600;
  color: var(--color-accent);
  cursor: pointer;
}

.gift__collapse:hover {
  text-decoration: underline;
}
</style>
