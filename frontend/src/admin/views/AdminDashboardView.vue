<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { AlertTriangle, Package, ShoppingBag, TrendingUp } from 'lucide-vue-next'
import { adminDashboard, type AdminDashboardStats } from '@/admin/lib/adminApi'
import { formatCurrency } from '@/lib/currency'

const stats = ref<AdminDashboardStats | null>(null)
const error = ref('')
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await adminDashboard()
    stats.value = res.stats
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load dashboard'
  } finally {
    loading.value = false
  }
})

const fmt = formatCurrency

const chartMax = computed(() => {
  if (!stats.value?.revenueByDay?.length) return 1
  return Math.max(...stats.value.revenueByDay.map((d) => d.revenue), 1)
})
</script>

<template>
  <div>
    <h1 class="admin-page-title">Dashboard</h1>
    <p v-if="error" class="admin-error">{{ error }}</p>
    <p v-if="loading" class="admin-meta">Loading…</p>

    <template v-if="stats">
      <!-- Action alerts -->
      <div v-if="stats.ordersToFulfill || stats.inventory.lowStock || stats.inventory.outOfStock" class="admin-alerts">
        <RouterLink
          v-if="stats.ordersToFulfill"
          :to="{ name: 'admin-orders', query: { status: 'paid' } }"
          class="admin-alert admin-alert--info"
        >
          <Package :size="18" aria-hidden="true" />
          <span><strong>{{ stats.ordersToFulfill }}</strong> orders need fulfillment</span>
        </RouterLink>
        <RouterLink
          v-if="stats.inventory.lowStock"
          :to="{ name: 'admin-inventory' }"
          class="admin-alert admin-alert--warn"
        >
          <AlertTriangle :size="18" aria-hidden="true" />
          <span><strong>{{ stats.inventory.lowStock }}</strong> products low on stock</span>
        </RouterLink>
        <RouterLink
          v-if="stats.inventory.outOfStock"
          :to="{ name: 'admin-inventory', query: { filter: 'out' } }"
          class="admin-alert admin-alert--danger"
        >
          <ShoppingBag :size="18" aria-hidden="true" />
          <span><strong>{{ stats.inventory.outOfStock }}</strong> products out of stock</span>
        </RouterLink>
      </div>

      <div class="admin-stats">
        <div class="admin-stat admin-stat--highlight">
          <div class="admin-stat__label"><TrendingUp :size="14" /> Revenue (paid)</div>
          <div class="admin-stat__value">{{ fmt(stats.revenue.totalPaid) }}</div>
        </div>
        <div class="admin-stat">
          <div class="admin-stat__label">Today</div>
          <div class="admin-stat__value">{{ fmt(stats.revenue.today) }}</div>
        </div>
        <div class="admin-stat">
          <div class="admin-stat__label">Orders</div>
          <div class="admin-stat__value">{{ stats.orders.total }}</div>
        </div>
        <div class="admin-stat">
          <div class="admin-stat__label">Pending payment</div>
          <div class="admin-stat__value">{{ stats.orders.pending }}</div>
        </div>
        <div class="admin-stat">
          <div class="admin-stat__label">Products active</div>
          <div class="admin-stat__value">{{ stats.products.active }} / {{ stats.products.total }}</div>
        </div>
        <div class="admin-stat">
          <div class="admin-stat__label">Customers</div>
          <div class="admin-stat__value">{{ stats.registeredCustomers }}</div>
        </div>
        <div class="admin-stat">
          <div class="admin-stat__label">Subscribers</div>
          <div class="admin-stat__value">{{ stats.newsletterSubscribers }}</div>
        </div>
      </div>

      <!-- Quick actions -->
      <div class="admin-quick-actions">
        <RouterLink :to="{ name: 'admin-product-new' }" class="admin-btn">+ Add product</RouterLink>
        <RouterLink :to="{ name: 'admin-orders' }" class="admin-btn admin-btn--ghost">View orders</RouterLink>
        <RouterLink :to="{ name: 'admin-bulk' }" class="admin-btn admin-btn--ghost">Bulk import</RouterLink>
        <RouterLink :to="{ name: 'admin-home-page' }" class="admin-btn admin-btn--ghost">Edit landing page</RouterLink>
      </div>

      <div v-if="stats.revenueByDay?.length" class="admin-card" style="margin-bottom: 1.25rem">
        <h2 class="admin-card-title">Revenue — last 30 days</h2>
        <div class="admin-revenue-chart">
          <div
            v-for="d in stats.revenueByDay"
            :key="d.date"
            class="admin-revenue-chart__bar-wrap"
            :title="`${d.date}: ${fmt(d.revenue)} (${d.orders} orders)`"
          >
            <div
              class="admin-revenue-chart__bar"
              :style="{ height: `${Math.max(4, (d.revenue / chartMax) * 100)}%` }"
            />
            <span class="admin-revenue-chart__label">{{ d.date.slice(5) }}</span>
          </div>
        </div>
      </div>

      <div class="admin-dashboard-grid">
        <div class="admin-card">
          <div class="admin-card-head">
            <h2 class="admin-card-title">Recent orders</h2>
            <RouterLink :to="{ name: 'admin-orders' }" class="admin-card-link">View all</RouterLink>
          </div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Customer</th>
                  <th>Products</th>
                  <th>Status</th>
                  <th>Total</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="o in stats.recentOrders" :key="o.id">
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
                  <td>{{ fmt(o.subtotal) }}</td>
                  <td>{{ o.createdAt.slice(0, 16) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="admin-meta admin-meta--hint">Click an order ID or product list to open full details, shipping, and payment info.</p>
        </div>

        <div class="admin-card">
          <h2 class="admin-card-title">Order breakdown</h2>
          <ul class="admin-status-list">
            <li v-for="(count, status) in stats.orders.byStatus" :key="status">
              <span class="admin-badge" :class="`admin-badge--${status}`">{{ status }}</span>
              <span>{{ count }}</span>
            </li>
          </ul>

          <h2 class="admin-card-title" style="margin-top: 1.25rem">Top products</h2>
          <ul class="admin-top-list">
            <li v-for="(p, i) in stats.topProducts" :key="i">
              <span>{{ p.name }}</span>
              <span class="admin-muted">{{ p.quantitySold }} sold · {{ fmt(p.revenue) }}</span>
            </li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>
