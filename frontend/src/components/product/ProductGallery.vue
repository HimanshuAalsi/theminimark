<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import ProductImage from '@/components/product/ProductImage.vue'

const props = defineProps<{
  images: string[]
  alt: string
  productId: string
}>()

const active = ref(0)

const gallery = computed(() => (props.images.length > 0 ? props.images : ['']))

watch(
  () => props.images,
  () => {
    active.value = 0
  },
)

function go(delta: number) {
  const n = gallery.value.length
  if (n <= 1) return
  active.value = (active.value + delta + n) % n
}

function pick(i: number) {
  active.value = i
}
</script>

<template>
  <div class="pgal">
    <div v-if="gallery.length > 1" class="pgal__thumbs" role="tablist" aria-label="Product images">
      <button
        v-for="(src, i) in gallery"
        :key="`${src}-${i}`"
        type="button"
        role="tab"
        class="pgal__thumb"
        :class="{ 'pgal__thumb--on': i === active }"
        :aria-selected="i === active"
        :aria-label="`Image ${i + 1} of ${gallery.length}`"
        @click="pick(i)"
      >
        <ProductImage :src="src" :alt="''" :fallback-key="`${productId}-${i}`" width="80" height="80" />
      </button>
    </div>

    <div class="pgal__stage">
      <button
        v-if="gallery.length > 1"
        type="button"
        class="pgal__nav pgal__nav--prev"
        aria-label="Previous image"
        @click="go(-1)"
      >
        <ChevronLeft :size="20" :stroke-width="2.25" />
      </button>

      <div class="pgal__main">
        <ProductImage
          :key="active"
          :src="gallery[active]"
          :alt="alt"
          :fallback-key="productId"
          width="800"
          height="800"
        />
      </div>

      <button
        v-if="gallery.length > 1"
        type="button"
        class="pgal__nav pgal__nav--next"
        aria-label="Next image"
        @click="go(1)"
      >
        <ChevronRight :size="20" :stroke-width="2.25" />
      </button>

      <span v-if="gallery.length > 1" class="pgal__counter" aria-live="polite">
        {{ active + 1 }} / {{ gallery.length }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.pgal {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 0.85rem;
  align-items: start;
}

@media (max-width: 640px) {
  .pgal {
    grid-template-columns: 1fr;
  }
}

.pgal__thumbs {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-height: 28rem;
  overflow-y: auto;
  padding-right: 0.15rem;
  scrollbar-width: thin;
}

@media (max-width: 640px) {
  .pgal__thumbs {
    flex-direction: row;
    max-height: none;
    overflow-x: auto;
    overflow-y: hidden;
    order: 2;
    padding-right: 0;
    padding-bottom: 0.15rem;
  }
}

.pgal__thumb {
  flex-shrink: 0;
  width: 4.25rem;
  height: 4.25rem;
  padding: 0;
  border: 2px solid var(--color-border);
  border-radius: var(--radius-sm);
  overflow: hidden;
  background: var(--color-surface-elevated);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.pgal__thumb--on {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 2px var(--color-accent-soft);
}

.pgal__thumb :deep(img) {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.pgal__stage {
  position: relative;
  border-radius: var(--radius-xl);
  overflow: hidden;
  background: linear-gradient(145deg, #ebe6df, #f5f1ea);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-float);
}

.pgal__main {
  aspect-ratio: 1;
  display: grid;
  place-items: center;
}

.pgal__img :deep(img),
.pgal__main :deep(img) {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.pgal__nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 2;
  display: grid;
  place-items: center;
  width: 2.35rem;
  height: 2.35rem;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  background: var(--tm-glass);
  color: var(--color-ink);
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition:
    border-color 0.2s ease,
    color 0.2s ease;
}

.pgal__nav:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
}

.pgal__nav--prev {
  left: 0.65rem;
}

.pgal__nav--next {
  right: 0.65rem;
}

.pgal__counter {
  position: absolute;
  bottom: 0.65rem;
  right: 0.75rem;
  padding: 0.25rem 0.55rem;
  border-radius: 999px;
  background: rgba(20, 19, 18, 0.55);
  color: #fff;
  font-size: 0.72rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}
</style>
