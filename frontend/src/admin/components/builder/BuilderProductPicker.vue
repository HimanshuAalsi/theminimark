<script setup lang="ts">
import { Loader2, Search, X } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { adminImageSrc, adminListProducts, type AdminProduct } from '@/admin/lib/adminApi'

const props = defineProps<{ modelValue: string[] }>()
const emit = defineEmits<{ 'update:modelValue': [ids: string[]] }>()

const open = ref(false)
const q = ref('')
const busy = ref(false)
const results = ref<AdminProduct[]>([])
const picked = ref<AdminProduct[]>([])

watch(
  () => props.modelValue,
  async (ids) => {
    if (!ids.length) {
      picked.value = []
      return
    }
    if (picked.value.map((p) => p.id).join() === ids.join()) return
    try {
      const res = await adminListProducts({ page: '1', perPage: '50', active: 'all' })
      picked.value = res.items.filter((p) => ids.includes(p.id))
    } catch {
      picked.value = ids.map((id) => ({ id, name: id } as AdminProduct))
    }
  },
  { immediate: true },
)

async function search() {
  busy.value = true
  try {
    const res = await adminListProducts({
      page: '1',
      perPage: '20',
      active: '1',
      q: q.value.trim(),
    })
    results.value = res.items
  } catch {
    results.value = []
  } finally {
    busy.value = false
  }
}

function toggle(p: AdminProduct) {
  const ids = new Set(props.modelValue)
  if (ids.has(p.id)) ids.delete(p.id)
  else ids.add(p.id)
  emit('update:modelValue', [...ids])
}

function remove(id: string) {
  emit(
    'update:modelValue',
    props.modelValue.filter((x) => x !== id),
  )
}

function openPicker() {
  open.value = true
  void search()
}
</script>

<template>
  <div class="ppick">
    <div v-if="picked.length" class="ppick__chips">
      <span v-for="p in picked" :key="p.id" class="ppick__chip">
        {{ p.name }}
        <button type="button" aria-label="Remove" @click="remove(p.id)"><X :size="10" /></button>
      </span>
    </div>
    <button type="button" class="ppick__trigger" @click="openPicker">
      <Search :size="14" />
      Pick products ({{ modelValue.length }})
    </button>

    <Teleport to="body">
      <div v-if="open" class="ppick__modal">
        <div class="ppick__dialog bld-root">
          <div class="ppick__head">
            <strong>Select products</strong>
            <button type="button" class="ppick__close" aria-label="Close" @click="open = false"><X :size="16" /></button>
          </div>
          <div class="ppick__search">
            <Search :size="14" />
            <input v-model="q" type="search" class="bld-input" placeholder="Search products…" @keyup.enter="search" />
            <button type="button" class="ppick__search-btn" :disabled="busy" @click="search">Search</button>
          </div>
          <Loader2 v-if="busy" :size="20" class="ppick__spin" />
          <ul v-else class="ppick__list">
            <li v-for="p in results" :key="p.id">
              <label class="ppick__row">
                <input type="checkbox" :checked="modelValue.includes(p.id)" @change="toggle(p)" />
                <img v-if="p.imagePath || p.imageUrl" :src="adminImageSrc(p.imagePath || p.imageUrl)" alt="" />
                <span>
                  <strong>{{ p.name }}</strong>
                  <small>{{ p.sku || p.slug }}</small>
                </span>
              </label>
            </li>
          </ul>
          <button type="button" class="ppick__done" @click="open = false">Done</button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
@import './builder-ui.css';

.ppick__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-bottom: 0.5rem;
}

.ppick__chip {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  border-radius: 999px;
  background: #ecfdf5;
  border: 1px solid #99f6e4;
  font-size: 0.6875rem;
  font-weight: 600;
  color: #0f766e;
}

.ppick__chip button {
  border: none;
  background: none;
  cursor: pointer;
  padding: 0;
  color: #64748b;
}

.ppick__trigger {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  width: 100%;
  justify-content: center;
  padding: 0.55rem 0.75rem;
  border: 1px dashed var(--bld-border-strong, #cbd5e1);
  border-radius: 9px;
  background: var(--bld-surface, #fff);
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--bld-focus, #0d9488);
  cursor: pointer;
  transition: background 0.12s, border-color 0.12s;
}

.ppick__trigger:hover {
  background: #f0fdfa;
  border-color: var(--bld-focus, #0d9488);
}

.ppick__modal {
  position: fixed;
  inset: 0;
  z-index: 500;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  display: grid;
  place-items: center;
  padding: 1rem;
}

.ppick__dialog {
  width: min(100%, 30rem);
  max-height: 82vh;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1.1rem;
  background: #fff;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 16px 48px rgba(15, 23, 42, 0.18);
}

.ppick__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.9375rem;
}

.ppick__close {
  display: grid;
  place-items: center;
  width: 2rem;
  height: 2rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  color: #64748b;
}

.ppick__search {
  display: flex;
  gap: 0.4rem;
  align-items: center;
  color: #64748b;
}

.ppick__search .bld-input {
  flex: 1;
}

.ppick__search-btn {
  padding: 0.55rem 0.85rem;
  border: none;
  border-radius: 8px;
  background: #0d9488;
  color: #fff;
  font-size: 0.8125rem;
  font-weight: 700;
  cursor: pointer;
}

.ppick__search-btn:disabled {
  opacity: 0.6;
}

.ppick__list {
  list-style: none;
  margin: 0;
  padding: 0;
  overflow-y: auto;
  max-height: 18rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.ppick__row {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.55rem 0.65rem;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  font-size: 0.8125rem;
}

.ppick__row:hover {
  background: #f8fafc;
}

.ppick__row img {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  object-fit: cover;
}

.ppick__row span {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.ppick__row small {
  color: #64748b;
  font-size: 0.6875rem;
}

.ppick__done {
  padding: 0.6rem;
  border: none;
  border-radius: 9px;
  background: #0f172a;
  color: #fff;
  font-size: 0.8125rem;
  font-weight: 700;
  cursor: pointer;
}

.ppick__spin {
  animation: spin 0.8s linear infinite;
  margin: auto;
  color: #0d9488;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
