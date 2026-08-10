<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { ShopSubcategory } from '@/types/shopCategory'
import UiButton from '@/components/ui/UiButton.vue'
import UiSheet from '@/components/ui/UiSheet.vue'
import { SHOP_CATEGORIES } from '@/data/siteContent'

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  category: string
  subcategory: string
  sort: 'featured' | 'price-asc' | 'price-desc' | 'name'
  subcategories: ShopSubcategory[]
}>()

const emit = defineEmits<{
  apply: [
    payload: {
      category: string
      subcategory: string
      sort: 'featured' | 'price-asc' | 'price-desc' | 'name'
    },
  ]
}>()

const draftCategory = ref(props.category)
const draftSubcategory = ref(props.subcategory)
const draftSort = ref(props.sort)

watch(
  () => props.category,
  (v) => {
    draftCategory.value = v
  },
)
watch(
  () => props.subcategory,
  (v) => {
    draftSubcategory.value = v
  },
)
watch(
  () => props.sort,
  (v) => {
    draftSort.value = v
  },
)

watch(open, (isOpen) => {
  if (isOpen) {
    draftCategory.value = props.category
    draftSubcategory.value = props.subcategory
    draftSort.value = props.sort
  }
})

const draftSubs = computed(() => {
  if (!draftCategory.value || draftCategory.value === 'all') return []
  return props.subcategories
})

function selectCategory(id: string) {
  draftCategory.value = id
  draftSubcategory.value = ''
}

function apply() {
  emit('apply', {
    category: draftCategory.value,
    subcategory: draftSubcategory.value,
    sort: draftSort.value,
  })
  open.value = false
}

function reset() {
  draftCategory.value = 'all'
  draftSubcategory.value = ''
  draftSort.value = 'featured'
}
</script>

<template>
  <UiSheet v-model:open="open" title="Filters" variant="bottom">
    <div class="filter-sheet">
      <fieldset class="filter-sheet__group">
        <legend>Sort</legend>
        <select v-model="draftSort" class="filter-sheet__select">
          <option value="featured">Featured</option>
          <option value="price-asc">Price: low to high</option>
          <option value="price-desc">Price: high to low</option>
          <option value="name">Name A–Z</option>
        </select>
      </fieldset>

      <fieldset class="filter-sheet__group">
        <legend>Category</legend>
        <div class="filter-sheet__chips">
          <button
            v-for="c in SHOP_CATEGORIES"
            :key="c.id"
            type="button"
            class="filter-sheet__chip"
            :class="{ 'filter-sheet__chip--on': draftCategory === c.id }"
            @click="selectCategory(c.id)"
          >
            {{ c.label }}
          </button>
        </div>
      </fieldset>

      <fieldset v-if="draftSubs.length" class="filter-sheet__group">
        <legend>Type</legend>
        <div class="filter-sheet__chips">
          <button
            type="button"
            class="filter-sheet__chip filter-sheet__chip--sm"
            :class="{ 'filter-sheet__chip--on': !draftSubcategory }"
            @click="draftSubcategory = ''"
          >
            All
          </button>
          <button
            v-for="sub in draftSubs"
            :key="sub.slug"
            type="button"
            class="filter-sheet__chip filter-sheet__chip--sm"
            :class="{ 'filter-sheet__chip--on': draftSubcategory === sub.slug }"
            @click="draftSubcategory = sub.slug"
          >
            {{ sub.name }}
          </button>
        </div>
      </fieldset>
    </div>

    <template #footer>
      <div class="filter-sheet__actions">
        <UiButton variant="ghost" @click="reset">Reset</UiButton>
        <UiButton block @click="apply">Show results</UiButton>
      </div>
    </template>
  </UiSheet>
</template>

<style scoped>
.filter-sheet {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.filter-sheet__group {
  margin: 0;
  padding: 0;
  border: none;
}

.filter-sheet__group legend {
  margin-bottom: 0.5rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--tm-ink-faint);
}

.filter-sheet__select {
  width: 100%;
  min-height: var(--tm-tap);
  padding: 0 0.85rem;
  border-radius: var(--tm-radius-sm);
  border: 1px solid var(--tm-border);
  background: var(--tm-surface);
  font: inherit;
  color: var(--tm-ink);
}

.filter-sheet__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.filter-sheet__chip {
  min-height: 38px;
  padding: 0 0.9rem;
  border-radius: var(--tm-radius-full);
  border: 1px solid var(--tm-border);
  background: var(--tm-surface);
  font: inherit;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--tm-ink-muted);
  cursor: pointer;
  transition:
    border-color var(--tm-duration) var(--tm-ease),
    background var(--tm-duration) var(--tm-ease),
    color var(--tm-duration) var(--tm-ease);
}

.filter-sheet__chip--sm {
  min-height: 34px;
  font-size: 0.8125rem;
}

.filter-sheet__chip--on {
  border-color: var(--tm-accent);
  background: var(--tm-accent-soft);
  color: var(--tm-accent);
}

.filter-sheet__actions {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.5rem;
  align-items: center;
}
</style>
