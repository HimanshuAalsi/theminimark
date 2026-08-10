<script setup lang="ts">
import { ImageIcon, ShoppingBag } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { resolveProductImageUrl } from '@/lib/productImage'

const props = defineProps<{
  src?: string
  alt: string
  isCustom?: boolean
  compact?: boolean
}>()

const failed = ref(false)

const resolvedSrc = computed(() => resolveProductImageUrl(props.src))

watch(
  () => props.src,
  () => {
    failed.value = false
  },
)

const showImage = computed(() => Boolean(resolvedSrc.value) && !failed.value)
</script>

<template>
  <div class="cart-thumb" :class="{ 'cart-thumb--custom': isCustom, 'cart-thumb--compact': compact }">
    <img
      v-if="showImage"
      :src="resolvedSrc"
      :alt="alt"
      class="cart-thumb__img"
      loading="lazy"
      decoding="async"
      referrerpolicy="no-referrer-when-downgrade"
      @error="failed = true"
    />
    <div v-else class="cart-thumb__placeholder" aria-hidden="true">
      <ImageIcon v-if="isCustom" :size="compact ? 16 : 20" :stroke-width="1.5" />
      <ShoppingBag v-else :size="compact ? 16 : 20" :stroke-width="1.5" />
    </div>
  </div>
</template>

<style scoped>
.cart-thumb {
  width: 4.5rem;
  height: 4.5rem;
  flex-shrink: 0;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  overflow: hidden;
  background: var(--color-page);
}

.cart-thumb--compact {
  width: 3rem;
  height: 3rem;
  border-radius: 8px;
}

.cart-thumb--custom {
  background: var(--color-accent-soft);
}

.cart-thumb__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.cart-thumb__placeholder {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  color: var(--color-accent);
  background: var(--color-accent-soft);
}
</style>
