<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import HomeAmbience from '@/components/home/HomeAmbience.vue'
import HomeLayoutRenderer from '@/components/home/HomeLayoutRenderer.vue'
import { destroyAos, initAos, refreshAos } from '@/lib/aos'
import { ensureLayout } from '@/lib/homePageLayout'
import { useCatalogStore } from '@/stores/catalog'
import { useHomePageStore } from '@/stores/homePage'

const catalogStore = useCatalogStore()
const homePage = useHomePageStore()
const { config } = storeToRefs(homePage)

const layout = computed(() => ensureLayout(config.value.layout))

async function bootAos() {
  await initAos()
  await nextTick()
  refreshAos()
}

onMounted(async () => {
  void catalogStore.ensureLoaded({ refresh: true })
  await bootAos()
})

onUnmounted(() => {
  destroyAos()
})

watch(layout, async () => {
  await nextTick()
  refreshAos()
})
</script>

<template>
  <div class="home">
    <HomeAmbience />
    <div class="home__content">
      <HomeLayoutRenderer :layout="layout" />
    </div>
  </div>
</template>

<style scoped>
.home {
  position: relative;
  width: 100%;
  overflow-x: clip;
  background: var(--tm-page, #f6f4f0);
}

.home__content {
  position: relative;
  z-index: 1;
}
</style>
