<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import ProductCard from '@/components/product/ProductCard.vue'
import { MINI_KNOTS_CATEGORY } from '@/data/siteContent'
import { useCatalogStore } from '@/stores/catalog'

const catalogStore = useCatalogStore()
const { catalog, ready } = storeToRefs(catalogStore)

onMounted(() => {
  void catalogStore.ensureLoaded()
})

const products = computed(() =>
  catalog.value.filter((p) => p.category === MINI_KNOTS_CATEGORY),
)
</script>

<template>
  <section class="mini-knots" aria-labelledby="mini-knots-title">
    <div class="mini-knots__body">
      <div v-if="ready && products.length" class="mini-knots__grid tm-product-grid">
        <ProductCard v-for="p in products" :key="p.id" :product="p" />
      </div>
      <p v-else-if="ready" class="mini-knots__empty">
        Just Mini Knots crochet add-ons are coming soon — check back for new handmade pieces.
      </p>
    </div>
  </section>
</template>

<style scoped>
.mini-knots__body {
  min-width: 0;
}

.mini-knots__grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fill, minmax(10.5rem, 1fr));
}

@media (min-width: 900px) {
  .mini-knots__grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

.mini-knots__empty {
  margin: 0;
  color: var(--color-ink-muted);
  font-size: 0.95rem;
}
</style>
