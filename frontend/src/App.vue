<script setup lang="ts">
import { onMounted } from 'vue'
import { useSiteStore } from '@/stores/site'
import { useThemeStore } from '@/stores/theme'
import { useWishlistStore } from '@/stores/wishlist'

onMounted(() => {
  useThemeStore().init()
  void useSiteStore().hydrate()
  void useWishlistStore().ensureLoaded()
})
</script>

<template>
  <RouterView />
</template>
