<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { adminDeleteCategory, adminListCategories, type AdminCategory } from '@/admin/lib/adminApi'

const items = ref<AdminCategory[]>([])
const error = ref('')

async function load() {
  try {
    const res = await adminListCategories()
    items.value = res.items
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load'
  }
}

async function remove(cat: AdminCategory) {
  if (!confirm(`Delete category "${cat.name}"?`)) return
  try {
    await adminDeleteCategory(cat.id)
    await load()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Delete failed'
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="admin-page-head">
      <h1 class="admin-page-title">Categories</h1>
      <RouterLink :to="{ name: 'admin-category-new' }" class="admin-btn">+ New category</RouterLink>
    </div>
    <p class="admin-meta">Manage shop categories, SEO keywords, and sort order.</p>
    <p v-if="error" class="admin-error">{{ error }}</p>
    <div class="admin-card admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Slug</th>
            <th>Keywords</th>
            <th>Products</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in items" :key="c.id">
            <td><strong>{{ c.name }}</strong></td>
            <td>{{ c.slug }}</td>
            <td class="admin-cell-truncate">{{ c.keywords || '—' }}</td>
            <td>{{ c.productCount ?? 0 }}</td>
            <td>{{ c.isActive ? 'Active' : 'Hidden' }}</td>
            <td>
              <div class="admin-actions">
                <RouterLink
                  :to="{ name: 'admin-category-edit', params: { id: String(c.id) } }"
                  class="admin-btn admin-btn--ghost"
                >
                  Edit
                </RouterLink>
                <button
                  type="button"
                  class="admin-btn admin-btn--danger"
                  :disabled="(c.productCount ?? 0) > 0"
                  :title="(c.productCount ?? 0) > 0 ? 'Remove products first' : ''"
                  @click="remove(c)"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.admin-cell-truncate {
  max-width: 12rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.75rem;
  color: var(--admin-muted);
}
</style>
