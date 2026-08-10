<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import AnnouncementBar from '@/components/layout/AnnouncementBar.vue'
import AppBottomNav from '@/components/layout/AppBottomNav.vue'
import TheFooter from '@/components/layout/TheFooter.vue'
import CartDrawer from '@/components/cart/CartDrawer.vue'
import SearchPanel from '@/components/search/SearchPanel.vue'
import TheHeader from '@/components/layout/TheHeader.vue'
import { useIsMobileApp } from '@/composables/useMediaQuery'
import { storeToRefs } from 'pinia'
import { useCatalogStore } from '@/stores/catalog'

const route = useRoute()
const catalog = useCatalogStore()
const { devLoadNotice } = storeToRefs(catalog)
const isMobileApp = useIsMobileApp()

const showBottomNav = computed(() => !route.meta.hideBottomNav)
const isImmersive = computed(() => Boolean(route.meta.hideBottomNav))

watch(
  isMobileApp,
  (mobile) => {
    document.documentElement.classList.toggle('layout-app', mobile)
    document.body.classList.toggle('layout-app', mobile)
  },
  { immediate: true }
)
</script>

<template>
  <div
    class="layout"
    :class="{
      'layout--app': isMobileApp,
      'layout--immersive': isMobileApp && isImmersive,
    }"
  >
    <AnnouncementBar v-if="!isMobileApp" />
    <TheHeader :app-mode="isMobileApp" />
    <p v-if="devLoadNotice" class="layout__api-notice tm-container" role="status">
      <span class="layout__api-text">{{ devLoadNotice }}</span>
      <button type="button" class="layout__api-dismiss" @click="catalog.dismissDevNotice()">
        Dismiss
      </button>
    </p>
    <main class="layout__main">
      <RouterView v-slot="{ Component }">
        <Transition name="page" mode="out-in">
          <component :is="Component" />
        </Transition>
      </RouterView>
    </main>
    <TheFooter v-if="!isMobileApp" />
    <AppBottomNav v-if="isMobileApp && showBottomNav" />
    <CartDrawer />
    <SearchPanel />
  </div>
</template>

<style scoped>
.layout {
  min-height: 100svh;
  display: flex;
  flex-direction: column;
  isolation: isolate;
}

.layout__main {
  flex: 1;
  width: 100%;
  box-sizing: border-box;
}

.layout__api-notice {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.6rem 0 0.65rem;
  font-size: 0.86rem;
  line-height: 1.45;
  color: var(--color-ink-muted);
}

.layout__api-text {
  padding-top: 0.2rem;
}

.layout__api-dismiss {
  flex-shrink: 0;
  min-height: var(--tap-min);
  padding: 0 0.85rem;
  border-radius: 999px;
  border: 1px solid var(--color-border-strong);
  background: var(--color-surface-elevated);
  font-weight: 600;
  font-size: 0.8rem;
  cursor: pointer;
  color: var(--color-ink);
}

.layout__api-dismiss:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
}
</style>
