<script setup lang="ts">
import { Clock, Search, TrendingUp, X } from 'lucide-vue-next'
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import ProductImage from '@/components/product/ProductImage.vue'
import { formatCurrency } from '@/lib/currency'
import { useSearchUiStore } from '@/stores/searchUi'

const router = useRouter()
const searchUi = useSearchUiStore()
const { panelOpen, query, recent, suggestions } = storeToRefs(searchUi)

const fmt = formatCurrency

const showRecent = computed(() => query.value.trim().length < 2 && recent.value.length > 0)
const showSuggestions = computed(() => suggestions.value.length > 0)

function go(term: string) {
  const t = term.trim()
  if (!t) return
  searchUi.addRecent(t)
  searchUi.close()
  router.push({ path: '/shop', query: { q: t } })
}

function onSubmit(e: Event) {
  e.preventDefault()
  go(query.value)
}

function pickProduct(slug: string, name: string) {
  searchUi.addRecent(name)
  searchUi.close()
  router.push({ name: 'product', params: { slug } })
}
</script>

<template>
  <Teleport to="body">
    <Transition name="search-panel">
      <div v-if="panelOpen" class="search-panel" role="presentation">
        <button type="button" class="search-panel__backdrop" aria-label="Close search" @click="searchUi.close()" />
        <div class="search-panel__sheet" role="dialog" aria-modal="true" aria-label="Search">
          <form class="search-panel__bar" role="search" @submit="onSubmit">
            <Search :size="20" :stroke-width="2" aria-hidden="true" />
            <input
              v-model="query"
              type="search"
              class="search-panel__input"
              placeholder="Search products…"
              autocomplete="off"
              autofocus
            />
            <button type="button" class="search-panel__clear" aria-label="Close" @click="searchUi.close()">
              <X :size="20" />
            </button>
          </form>

          <div class="search-panel__body">
            <section v-if="showRecent" class="search-panel__section">
              <div class="search-panel__section-head">
                <h3><Clock :size="14" /> Recent</h3>
                <button type="button" class="search-panel__link" @click="searchUi.clearRecent()">Clear</button>
              </div>
              <ul class="search-panel__chips" role="list">
                <li v-for="term in recent" :key="term">
                  <button type="button" class="search-panel__chip" @click="go(term)">{{ term }}</button>
                  <button
                    type="button"
                    class="search-panel__chip-remove"
                    :aria-label="`Remove ${term}`"
                    @click="searchUi.removeRecent(term)"
                  >
                    <X :size="12" />
                  </button>
                </li>
              </ul>
            </section>

            <section v-if="showSuggestions" class="search-panel__section">
              <h3 class="search-panel__section-head solo">
                <TrendingUp :size="14" /> Suggestions
              </h3>
              <ul class="search-panel__results" role="list">
                <li v-for="p in suggestions" :key="p.id">
                  <button type="button" class="search-panel__result" @click="pickProduct(p.slug, p.name)">
                    <div class="search-panel__thumb-wrap">
                      <ProductImage :src="p.image" :alt="p.name" width="48" height="48" />
                    </div>
                    <span class="search-panel__result-text">
                      <strong>{{ p.name }}</strong>
                      <small>{{ fmt(p.price) }}</small>
                    </span>
                  </button>
                </li>
              </ul>
            </section>

            <p v-else-if="query.trim().length >= 2" class="search-panel__hint">No matches — try another term.</p>
            <p v-else-if="!showRecent" class="search-panel__hint">Type to search bookmarks, cards, magnets & more.</p>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.search-panel {
  position: fixed;
  inset: 0;
  z-index: 220;
  display: flex;
  flex-direction: column;
}

.search-panel__backdrop {
  position: absolute;
  inset: 0;
  border: none;
  background: var(--tm-overlay);
  cursor: pointer;
}

.search-panel__sheet {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  max-height: 100dvh;
  margin-top: env(safe-area-inset-top, 0);
  background: var(--tm-surface-2);
  border-bottom: 1px solid var(--tm-border);
  box-shadow: var(--tm-shadow-lg);
}

.search-panel__bar {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 1rem;
  border-bottom: 1px solid var(--tm-border);
  color: var(--tm-accent);
}

.search-panel__input {
  flex: 1;
  border: none;
  background: none;
  font: inherit;
  font-size: 1.05rem;
  color: var(--tm-ink);
  min-width: 0;
}

.search-panel__clear {
  display: grid;
  place-items: center;
  width: var(--tm-tap);
  height: var(--tm-tap);
  border: none;
  border-radius: var(--tm-radius-full);
  background: var(--tm-accent-muted);
  color: var(--tm-ink-muted);
  cursor: pointer;
}

.search-panel__body {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  -webkit-overflow-scrolling: touch;
}

.search-panel__section {
  margin-bottom: 1.25rem;
}

.search-panel__section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.65rem;
}

.search-panel__section-head.solo {
  margin-bottom: 0.65rem;
}

.search-panel__section-head h3 {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--tm-ink-faint);
}

.search-panel__link {
  border: none;
  background: none;
  font: inherit;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--tm-accent);
  cursor: pointer;
}

.search-panel__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.search-panel__chips li {
  display: inline-flex;
  align-items: center;
  gap: 0.15rem;
  background: var(--tm-surface);
  border: 1px solid var(--tm-border);
  border-radius: var(--tm-radius-full);
  overflow: hidden;
}

.search-panel__chip {
  border: none;
  background: none;
  padding: 0.45rem 0.65rem 0.45rem 0.85rem;
  font: inherit;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--tm-ink);
  cursor: pointer;
}

.search-panel__chip-remove {
  display: grid;
  place-items: center;
  width: 1.75rem;
  height: 1.75rem;
  border: none;
  background: none;
  color: var(--tm-ink-faint);
  cursor: pointer;
}

.search-panel__results {
  margin: 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.search-panel__result {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 0.5rem;
  border: none;
  border-radius: var(--tm-radius-md);
  background: var(--tm-surface);
  text-align: left;
  cursor: pointer;
  transition: background var(--tm-duration) var(--tm-ease);
}

.search-panel__result:hover {
  background: var(--tm-accent-muted);
}

.search-panel__thumb-wrap {
  width: 48px;
  height: 48px;
  border-radius: var(--tm-radius-sm);
  overflow: hidden;
  flex-shrink: 0;
}

.search-panel__thumb-wrap :deep(img) {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.search-panel__result-text {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  min-width: 0;
}

.search-panel__result-text strong {
  font-size: 0.9rem;
  color: var(--tm-ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.search-panel__result-text small {
  font-size: 0.8rem;
  color: var(--tm-accent);
  font-weight: 650;
}

.search-panel__hint {
  margin: 0;
  font-size: 0.9rem;
  color: var(--tm-ink-muted);
  text-align: center;
  padding: 2rem 0;
}

.search-panel-enter-active,
.search-panel-leave-active {
  transition: opacity var(--tm-duration) var(--tm-ease);
}

.search-panel-enter-active .search-panel__sheet,
.search-panel-leave-active .search-panel__sheet {
  transition: transform var(--tm-duration-slow) var(--tm-ease);
}

.search-panel-enter-from,
.search-panel-leave-to {
  opacity: 0;
}

.search-panel-enter-from .search-panel__sheet,
.search-panel-leave-to .search-panel__sheet {
  transform: translateY(-12px);
}
</style>
