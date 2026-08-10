<script setup lang="ts">
import { Moon, Sun } from 'lucide-vue-next'
import { useThemeStore } from '@/stores/theme'

const theme = useThemeStore()
</script>

<template>
  <button
    type="button"
    class="theme-toggle"
    :aria-label="theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    @click="theme.toggle()"
  >
    <Sun v-if="theme.isDark" :size="18" :stroke-width="2" />
    <Moon v-else :size="18" :stroke-width="2" />
  </button>
</template>

<style scoped>
.theme-toggle {
  display: grid;
  place-items: center;
  width: var(--tm-tap);
  height: var(--tm-tap);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-full);
  background: var(--tm-surface-2);
  color: var(--tm-ink-muted);
  cursor: pointer;
  transition:
    color var(--tm-duration) var(--tm-ease),
    border-color var(--tm-duration) var(--tm-ease);
}

.theme-toggle:hover {
  color: var(--tm-accent-b);
  border-color: var(--tm-accent-soft);
  background: var(--tm-gradient-soft);
}
</style>
