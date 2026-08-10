<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next'
import type { Component } from 'vue'
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

export interface AdminNavLink {
  name: string
  label: string
  icon?: Component
  badge?: number
  /** Extra route names that should highlight this link (e.g. edit pages) */
  match?: string[]
}

const props = defineProps<{
  id: string
  label: string
  icon: Component
  items: AdminNavLink[]
  open: boolean
  badge?: number
}>()

defineEmits<{ toggle: [] }>()

const route = useRoute()

const hasActiveChild = computed(() =>
  props.items.some((item) => linkActive(item.name, item.match)),
)

function linkActive(name: string, match?: string[]) {
  const current = route.name as string
  if (current === name) return true
  return match?.includes(current) ?? false
}
</script>

<template>
  <div class="admin-nav-group" :class="{ 'admin-nav-group--open': open, 'admin-nav-group--active': hasActiveChild }">
    <button type="button" class="admin-nav-group__trigger" @click="$emit('toggle')">
      <component :is="icon" :size="16" />
      <span class="admin-nav-group__label">{{ label }}</span>
      <span v-if="badge" class="admin-nav__badge">{{ badge }}</span>
      <ChevronDown :size="14" class="admin-nav-group__chev" />
    </button>
    <div v-show="open" class="admin-nav-sub">
      <RouterLink
        v-for="item in items"
        :key="item.name"
        :to="{ name: item.name }"
        class="admin-nav-sub__link"
        :class="{ 'admin-nav-sub__link--active': linkActive(item.name, item.match) }"
      >
        <component :is="item.icon" v-if="item.icon" :size="14" />
        <span>{{ item.label }}</span>
        <span v-if="item.badge" class="admin-nav__badge">{{ item.badge }}</span>
      </RouterLink>
    </div>
  </div>
</template>
