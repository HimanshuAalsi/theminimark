<script setup lang="ts">
import { onMounted } from 'vue'
import { useCartUiStore } from '@/stores/cartUi'

const cartUi = useCartUiStore()

onMounted(() => {
  cartUi.open()
})
</script>

<template>
  <div class="cart-route" aria-hidden="true" />
</template>

<style scoped>
.cart-route {
  display: none;
}
</style>
