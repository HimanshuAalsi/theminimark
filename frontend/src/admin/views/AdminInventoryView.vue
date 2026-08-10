<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { RouterLink } from 'vue-router'
import AdminPagination from '@/admin/components/AdminPagination.vue'
import { useAdminList } from '@/admin/composables/useAdminList'
import { adminImageSrc, adminListProducts, type AdminProduct } from '@/admin/lib/adminApi'
import { formatCurrency } from '@/lib/currency'

const route = useRoute()
const list = useAdminList(50)
const items = ref<AdminProduct[]>([])
const filter = ref<'low' | 'out' | 'all'>('low')

async function load() {
  const params: Record<string, string> = list.listParams()
  if (filter.value === 'low') params.lowStock = '1'
  const res = await list.run(() => adminListProducts(params))
  if (res) {
    items.value =
      filter.value === 'out'
        ? res.items.filter((p) => p.stockQuantity === 0)
        : res.items
    list.setMeta({ ...res.meta, total: filter.value === 'out' ? items.value.length : res.meta.total })
  }
}

watch(() => list.page.value, () => void load())
watch(filter, () => {
  list.resetPage()
  void load()
})

onMounted(() => {
  const q = route.query.filter
  if (q === 'out') filter.value = 'out'
  void load()
})
</script>

<template>
  <div>
    <div class="admin-page-head">
      <h1 class="admin-page-title">Inventory</h1>
      <RouterLink :to="{ name: 'admin-products' }" class="admin-btn admin-btn--ghost">All products</RouterLink>
    </div>

    <p class="admin-meta">
      Track stock levels. Set stock quantity on each product edit screen. Low stock = 5 or fewer units.
    </p>

    <div class="admin-toolbar">
      <select v-model="filter">
        <option value="low">Low stock (≤5)</option>
        <option value="out">Out of stock</option>
        <option value="all">All tracked stock</option>
      </select>
      <button type="button" class="admin-btn admin-btn--ghost" @click="load">Refresh</button>
    </div>

    <p v-if="list.error.value" class="admin-error">{{ list.error.value }}</p>

    <div class="admin-card admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th></th>
            <th>Product</th>
            <th>SKU</th>
            <th>Stock</th>
            <th>Price</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="items.length === 0">
            <td colspan="6" class="admin-empty-cell">No products match this filter.</td>
          </tr>
          <tr v-for="p in items" :key="p.id">
            <td>
              <img
                v-if="p.imageUrl"
                :src="adminImageSrc(p.imagePath || p.imageUrl)"
                alt=""
                width="36"
                height="36"
                class="admin-thumb"
              />
            </td>
            <td>
              <strong>{{ p.name }}</strong>
              <br />
              <span class="admin-cell-muted">{{ p.category }}</span>
            </td>
            <td>{{ p.sku || '—' }}</td>
            <td>
              <span
                :class="{
                  'admin-stock--low': p.stockQuantity !== null && p.stockQuantity > 0 && p.stockQuantity <= 5,
                  'admin-stock--out': p.stockQuantity === 0,
                }"
              >
                {{ p.stockQuantity ?? '—' }}
              </span>
            </td>
            <td>{{ formatCurrency(p.price) }}</td>
            <td>
              <RouterLink
                :to="{ name: 'admin-product-edit', params: { id: p.id } }"
                class="admin-btn admin-btn--ghost admin-btn--sm"
              >
                Update stock
              </RouterLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <AdminPagination
      v-if="filter !== 'out'"
      :page="list.page.value"
      :total-pages="list.totalPages.value"
      :range-label="list.rangeLabel.value"
      :busy="list.busy.value"
      @prev="list.goToPage(list.page.value - 1)"
      @next="list.goToPage(list.page.value + 1)"
    />
  </div>
</template>
