<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="notfound">
    <h1 class="notfound__title">Page not found</h1>
    <p class="notfound__text">That URL doesn’t exist. Try the shop or home.</p>
    <RouterLink to="/" class="notfound__link">Back to home</RouterLink>
  </div>
</template>

<style scoped>
.notfound__title {
  margin: 0 0 0.5rem;
  font-size: 1.5rem;
  font-weight: 650;
  color: var(--color-text);
}

.notfound__text {
  margin: 0 0 1rem;
  color: var(--color-text-muted);
}

.notfound__link {
  font-weight: 600;
  color: var(--color-accent);
  text-decoration: none;
}

.notfound__link:hover {
  text-decoration: underline;
}
</style>
