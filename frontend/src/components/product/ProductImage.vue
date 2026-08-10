<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { DEFAULT_PRODUCT_IMAGE, productDisplayImage } from '@/lib/productImage'

const props = withDefaults(
  defineProps<{
    src?: string | null
    alt: string
    fallbackKey?: string
    width?: number | string
    height?: number | string
    loading?: 'lazy' | 'eager'
    eager?: boolean
  }>(),
  {
    loading: 'lazy',
  },
)

const failed = ref(false)

const displaySrc = computed(() =>
  failed.value
    ? DEFAULT_PRODUCT_IMAGE
    : productDisplayImage(props.src, props.fallbackKey ?? props.alt),
)

watch(
  () => props.src,
  () => {
    failed.value = false
  },
)
</script>

<template>
  <img
    :src="displaySrc"
    :alt="alt"
    :width="width"
    :height="height"
    :loading="eager ? 'eager' : loading"
    decoding="async"
    referrerpolicy="no-referrer-when-downgrade"
    @error="failed = true"
  />
</template>
