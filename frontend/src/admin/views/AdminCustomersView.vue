<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import AdminPagination from '@/admin/components/AdminPagination.vue'
import { useAdminList } from '@/admin/composables/useAdminList'
import {
  adminGetCustomer,
  adminListCustomers,
  type AdminCustomer,
} from '@/admin/lib/adminApi'
import { formatCurrency } from '@/lib/currency'

const list = useAdminList(25)
const items = ref<AdminCustomer[]>([])
const q = ref('')
const selected = ref<AdminCustomer | null>(null)
const detailOpen = ref(false)
const detailLoading = ref(false)

async function load() {
  const res = await list.run(() => adminListCustomers(list.listParams({ q: q.value })))
  if (res) {
    items.value = res.items
    list.setMeta(res.meta)
  }
}

async function openCustomer(c: AdminCustomer) {
  detailOpen.value = true
  detailLoading.value = true
  selected.value = c
  try {
    const res = await adminGetCustomer(c.id)
    selected.value = res.customer
  } catch (e) {
    list.error.value = e instanceof Error ? e.message : 'Could not load customer'
  } finally {
    detailLoading.value = false
  }
}

function closeDetail() {
  detailOpen.value = false
  selected.value = null
}

watch(() => list.page.value, () => void load())

onMounted(load)
</script>

<template>
  <div>
    <h1 class="admin-page-title">Customers</h1>
    <div class="admin-toolbar">
      <input v-model="q" type="search" placeholder="Search email or name…" @keyup.enter="() => { list.resetPage(); load() }" />
      <button type="button" class="admin-btn admin-btn--ghost" @click="load">Refresh</button>
    </div>
    <p v-if="list.error.value" class="admin-error">{{ list.error.value }}</p>
    <p class="admin-meta">{{ list.rangeLabel.value }}</p>
    <div class="admin-card admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Email</th>
            <th>Name</th>
            <th>Role</th>
            <th>Orders</th>
            <th>Revenue</th>
            <th>Joined</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in items" :key="c.id">
            <td>{{ c.email }}</td>
            <td>{{ c.fullName || '—' }}</td>
            <td>{{ c.role }}</td>
            <td>{{ c.orderCount }}</td>
            <td>{{ formatCurrency(c.orderRevenue) }}</td>
            <td>{{ c.createdAt.slice(0, 10) }}</td>
            <td>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" @click="openCustomer(c)">
                View
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <AdminPagination
      :page="list.page.value"
      :total-pages="list.totalPages.value"
      :range-label="list.rangeLabel.value"
      :busy="list.busy.value"
      @prev="list.goToPage(list.page.value - 1)"
      @next="list.goToPage(list.page.value + 1)"
    />

    <div v-if="detailOpen" class="admin-drawer-backdrop" @click.self="closeDetail">
      <div class="admin-drawer" role="dialog" aria-label="Customer details">
        <button type="button" class="admin-drawer__close" aria-label="Close" @click="closeDetail">×</button>
        <p v-if="detailLoading" class="admin-meta">Loading…</p>
        <template v-else-if="selected">
          <h2 class="admin-card-title">{{ selected.fullName || selected.email }}</h2>
          <p class="admin-meta">{{ selected.email }}</p>
          <div class="admin-stats admin-stats--compact">
            <div class="admin-stat">
              <div class="admin-stat__label">Orders</div>
              <div class="admin-stat__value">{{ selected.orderCount }}</div>
            </div>
            <div class="admin-stat">
              <div class="admin-stat__label">Revenue</div>
              <div class="admin-stat__value">{{ formatCurrency(selected.orderRevenue) }}</div>
            </div>
          </div>
          <h3 class="admin-card-title">Recent orders</h3>
          <ul v-if="selected.recentOrders?.length" class="admin-top-list">
            <li v-for="o in selected.recentOrders" :key="o.id">
              <RouterLink :to="{ name: 'admin-order', params: { id: String(o.id) } }">#{{ o.id }}</RouterLink>
              <span class="admin-badge" :class="`admin-badge--${o.status}`">{{ o.status }}</span>
              <span>{{ formatCurrency(o.subtotal) }}</span>
            </li>
          </ul>
          <p v-else class="admin-meta">No orders yet.</p>
        </template>
      </div>
    </div>
  </div>
</template>
