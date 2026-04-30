<script setup lang="ts">
import TheFooter from '@/components/layout/TheFooter.vue'
import TheHeader from '@/components/layout/TheHeader.vue'
</script>

<template>
  <div class="layout">
    <TheHeader />
    <main class="layout__main">
      <RouterView />
    </main>
    <TheFooter />
  </div>
</template>

<style scoped>
.layout {
  min-height: 100svh;
  display: flex;
  flex-direction: column;
}

.layout__main {
  flex: 1;
  width: 100%;
  max-width: var(--content-max);
  margin: 0 auto;
  padding: 1.5rem 1.25rem 3rem;
  box-sizing: border-box;
}
</style>
