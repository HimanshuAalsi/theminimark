<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import AdminPagination from '@/admin/components/AdminPagination.vue'
import { useAdminList } from '@/admin/composables/useAdminList'
import { adminListOrders, type AdminOrderSummary } from '@/admin/lib/adminApi'
import { formatCurrency } from '@/lib/currency'

const route = useRoute()
const list = useAdminList(25)
const items = ref<AdminOrderSummary[]>([])
const status = ref(String(route.query.status ?? 'all'))
const q = ref('')
const from = ref('')
const to = ref('')

async function load() {
  const res = await list.run(() =>
    adminListOrders(
      list.listParams({
        status: status.value,
        q: q.value,
        ...(from.value ? { from: from.value } : {}),
        ...(to.value ? { to: to.value } : {}),
      }),
    ),
  )
  if (res) {
    items.value = res.items
    list.setMeta(res.meta)
  }
}

function onFilterChange() {
  list.resetPage()
  void load()
}

watch(() => list.page.value, () => void load())

onMounted(load)
</script>

<template>
  <div>
    <h1 class="admin-page-title">Orders</h1>
    <div class="admin-toolbar">
      <input v-model="q" type="search" placeholder="Email, name, order #…" @keyup.enter="onFilterChange" />
      <select v-model="status" @change="onFilterChange">
        <option value="all">All statuses</option>
        <option value="pending">Pending</option>
        <option value="paid">Paid</option>
        <option value="processing">Processing</option>
        <option value="shipped">Shipped</option>
        <option value="delivered">Delivered</option>
        <option value="cancelled">Cancelled</option>
        <option value="refunded">Refunded</option>
      </select>
      <input v-model="from" type="date" title="From date" @change="onFilterChange" />
      <input v-model="to" type="date" title="To date" @change="onFilterChange" />
      <button type="button" class="admin-btn admin-btn--ghost" @click="onFilterChange">Apply</button>
    </div>
    <p v-if="list.error.value" class="admin-error">{{ list.error.value }}</p>
    <p class="admin-meta">{{ list.rangeLabel.value }}</p>
    <div class="admin-card admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Products</th>
            <th>Status</th>
            <th>Total</th>
            <th>Date</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="o in items" :key="o.id">
            <td>
              <RouterLink :to="{ name: 'admin-order', params: { id: String(o.id) } }">#{{ o.id }}</RouterLink>
            </td>
            <td>
              {{ o.customerName || '—' }}<br />
              <span class="admin-cell-muted">{{ o.customerEmail }}</span>
            </td>
            <td class="admin-order-products">
              <RouterLink :to="{ name: 'admin-order', params: { id: String(o.id) } }">
                {{ o.lineSummary || '—' }}
              </RouterLink>
            </td>
            <td><span class="admin-badge" :class="`admin-badge--${o.status}`">{{ o.status }}</span></td>
            <td>{{ formatCurrency(o.subtotal) }}</td>
            <td>{{ o.createdAt.slice(0, 16) }}</td>
            <td>
              <RouterLink
                :to="{ name: 'admin-order', params: { id: String(o.id) } }"
                class="admin-btn admin-btn--ghost admin-btn--sm"
              >
                Details
              </RouterLink>
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
  </div>
</template>
