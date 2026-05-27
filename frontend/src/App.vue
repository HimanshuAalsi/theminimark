<script setup lang="ts">
import { onMounted } from 'vue'
import { useSiteStore } from '@/stores/site'
import { useWishlistStore } from '@/stores/wishlist'

onMounted(() => {
  void useSiteStore().hydrate()
  void useWishlistStore().ensureLoaded()
})
</script>

<template>
  <RouterView />
</template>
