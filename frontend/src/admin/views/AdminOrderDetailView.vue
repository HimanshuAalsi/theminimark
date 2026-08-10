<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { adminGetOrder, adminUpdateOrder, type AdminOrderDetail } from '@/admin/lib/adminApi'
import { formatCurrency } from '@/lib/currency'

const route = useRoute()
const order = ref<AdminOrderDetail | null>(null)
const loading = ref(true)
const status = ref('')
const adminNotes = ref('')
const shippingPhone = ref('')
const shippingAddress = ref('')
const shippingCity = ref('')
const error = ref('')
const saved = ref(false)
const refundBusy = ref(false)

async function refundOrder() {
  if (!order.value || !confirm('Issue full Razorpay refund and mark order as refunded?')) return
  refundBusy.value = true
  error.value = ''
  try {
    const res = await adminUpdateOrder(order.value.id, { refundPayment: true })
    if (!res.ok) {
      error.value = res.message ?? 'Refund failed'
      return
    }
    order.value = res.order ?? order.value
    status.value = order.value.status
    saved.value = true
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Refund failed'
  } finally {
    refundBusy.value = false
  }
}

async function load() {
  loading.value = true
  error.value = ''
  const id = Number(route.params.id)
  try {
    const res = await adminGetOrder(id)
    order.value = res.order
    status.value = res.order.status
    adminNotes.value = res.order.adminNotes ?? ''
    shippingPhone.value = res.order.shippingPhone ?? ''
    shippingAddress.value = res.order.shippingAddress ?? ''
    shippingCity.value = res.order.shippingCity ?? ''
  } catch (e) {
    order.value = null
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!order.value) return
  error.value = ''
  saved.value = false
  try {
    const res = await adminUpdateOrder(order.value.id, {
      status: status.value,
      adminNotes: adminNotes.value,
      shippingPhone: shippingPhone.value,
      shippingAddress: shippingAddress.value,
      shippingCity: shippingCity.value,
    })
    if (!res.ok) {
      error.value = res.message ?? 'Update failed'
      return
    }
    order.value = res.order ?? order.value
    saved.value = true
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Update failed'
  }
}

onMounted(() => {
  void load()
})
</script>

<template>
  <div>
    <p class="admin-back">
      <RouterLink :to="{ name: 'admin-orders' }">← Back to orders</RouterLink>
    </p>

    <p v-if="loading" class="admin-meta">Loading order…</p>
    <p v-else-if="error && !order" class="admin-error">{{ error }}</p>

    <template v-if="order">
    <h1 class="admin-page-title">Order #{{ order.id }}</h1>
    <div style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr))">
      <div class="admin-card">
        <h2 style="margin: 0 0 0.75rem; font-size: 1rem">Customer</h2>
        <p style="margin: 0.25rem 0; font-size: 0.875rem"><strong>{{ order.customerName || '—' }}</strong></p>
        <p style="margin: 0.25rem 0; font-size: 0.875rem">{{ order.customerEmail }}</p>
        <p v-if="order.parsedNotes.shipping" style="margin: 0.75rem 0 0; font-size: 0.8125rem">
          <strong>Shipping (notes):</strong> {{ order.parsedNotes.shipping }}
        </p>
        <div v-if="order.shippingPhone || order.shippingAddress || order.shippingCity || order.shippingPincode" style="margin-top: 0.75rem; font-size: 0.8125rem">
          <p v-if="order.shippingPhone" style="margin: 0.2rem 0"><strong>Phone:</strong> {{ order.shippingPhone }}</p>
          <p v-if="order.shippingAddress" style="margin: 0.2rem 0; white-space: pre-wrap"><strong>Address:</strong> {{ order.shippingAddress }}</p>
          <p v-if="order.shippingLandmark" style="margin: 0.2rem 0"><strong>Landmark:</strong> {{ order.shippingLandmark }}</p>
          <p v-if="order.shippingCity || order.shippingState || order.shippingPincode" style="margin: 0.2rem 0">
            <strong>Location:</strong>
            {{ [order.shippingCity, order.shippingState].filter(Boolean).join(', ') }}
            <span v-if="order.shippingPincode"> — PIN {{ order.shippingPincode }}</span>
          </p>
        </div>
        <p v-if="order.parsedNotes.freeGift" style="margin: 0.35rem 0; font-size: 0.8125rem">
          <strong>Free gift:</strong> {{ order.parsedNotes.freeGift }}
        </p>
      </div>
      <div class="admin-card">
        <h2 style="margin: 0 0 0.75rem; font-size: 1rem">Payment</h2>
        <p style="margin: 0.25rem 0; font-size: 0.8125rem">Status: <span class="admin-badge" :class="`admin-badge--${order.status}`">{{ order.status }}</span></p>
        <p style="margin: 0.25rem 0; font-size: 0.8125rem">Total: <strong>{{ formatCurrency(order.subtotal) }}</strong> {{ order.currency }}</p>
        <p v-if="order.itemsSubtotal" style="margin: 0.25rem 0; font-size: 0.8125rem">Items subtotal: {{ formatCurrency(order.itemsSubtotal) }}</p>
        <p v-if="order.couponCode" style="margin: 0.25rem 0; font-size: 0.8125rem">
          Coupon: <strong>{{ order.couponCode }}</strong>
          <span v-if="order.couponDiscount"> (−{{ formatCurrency(order.couponDiscount) }})</span>
        </p>
        <p v-if="order.refundId" style="margin: 0.25rem 0; font-size: 0.6875rem; color: var(--admin-muted)">Refund: {{ order.refundId }}</p>
        <p v-if="order.razorpayOrderId" style="margin: 0.25rem 0; font-size: 0.6875rem; color: var(--admin-muted)">Razorpay: {{ order.razorpayOrderId }}</p>
        <p v-if="order.paymentId" style="margin: 0.25rem 0; font-size: 0.6875rem">Payment ID: {{ order.paymentId }}</p>
        <p v-if="order.paidAt" style="margin: 0.25rem 0; font-size: 0.6875rem">Paid: {{ order.paidAt }}</p>
      </div>
    </div>

    <div
      v-if="order.personalizations?.length"
      class="admin-card"
      style="margin-top: 1rem"
    >
      <h2 style="margin: 0 0 0.75rem; font-size: 1rem">Custom personalisations</h2>
      <div class="admin-pers-order-grid">
        <div
          v-for="p in order.personalizations"
          :key="p.id"
          class="admin-pers-order-item"
        >
          <a :href="p.photoUrl" target="_blank" rel="noopener">
            <img :src="p.photoUrl" alt="Custom photo" loading="lazy" />
          </a>
          <div>
            <p style="margin: 0; font-size: 0.8125rem; font-weight: 700">
              {{ p.productType }} — {{ p.productName }}
            </p>
            <p style="margin: 0.25rem 0; font-size: 0.75rem; color: var(--admin-muted)">
              Crop: zoom {{ p.zoom }}, position {{ Math.round(p.posX) }}% / {{ Math.round(p.posY) }}%
            </p>
            <pre
              v-if="Object.keys(p.options).length"
              style="margin: 0.35rem 0 0; font-size: 0.6875rem; white-space: pre-wrap; font-family: inherit"
            >{{ JSON.stringify(p.options, null, 2) }}</pre>
          </div>
        </div>
      </div>
    </div>

    <div class="admin-card" style="margin-top: 1rem">
      <h2 style="margin: 0 0 0.75rem; font-size: 1rem">Line items</h2>
      <p v-if="!order.lines.length" class="admin-meta">No products recorded for this order.</p>
      <table v-else class="admin-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Unit</th>
            <th>Line total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ln in order.lines" :key="ln.id">
            <td>{{ ln.productName }} <span v-if="ln.productId" style="color: var(--admin-muted); font-size: 0.6875rem">({{ ln.productId }})</span></td>
            <td>{{ ln.quantity }}</td>
            <td>{{ formatCurrency(ln.unitPrice) }}</td>
            <td>{{ formatCurrency(ln.lineTotal) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="order.parsedNotes.rewards.length" class="admin-card" style="margin-top: 1rem">
      <h2 style="margin: 0 0 0.5rem; font-size: 1rem">Rewards applied</h2>
      <ul style="margin: 0; padding-left: 1.1rem; font-size: 0.8125rem">
        <li v-for="(r, i) in order.parsedNotes.rewards" :key="i">{{ r }}</li>
      </ul>
    </div>

    <div v-if="order.notes" class="admin-card" style="margin-top: 1rem">
      <h2 style="margin: 0 0 0.5rem; font-size: 1rem">Order notes</h2>
      <pre style="margin: 0; font-size: 0.75rem; white-space: pre-wrap; font-family: inherit">{{ order.notes }}</pre>
    </div>

    <div class="admin-card" style="margin-top: 1rem">
      <h2 style="margin: 0 0 0.75rem; font-size: 1rem">Update order</h2>
      <div class="admin-form">
        <div class="admin-field">
          <label>Status</label>
          <select v-model="status">
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
            <option value="refunded">Refunded</option>
          </select>
        </div>
        <div class="admin-field">
          <label>Shipping phone</label>
          <input v-model="shippingPhone" type="tel" autocomplete="off" />
        </div>
        <div class="admin-field">
          <label>Shipping address</label>
          <textarea v-model="shippingAddress" rows="2" />
        </div>
        <div class="admin-field">
          <label>City / PIN</label>
          <input v-model="shippingCity" type="text" />
        </div>
        <div class="admin-field">
          <label>Admin notes (internal)</label>
          <textarea v-model="adminNotes" />
        </div>
        <button type="button" class="admin-btn" @click="save">Save changes</button>
        <button
          v-if="order.paymentId && order.status !== 'refunded' && !order.refundId"
          type="button"
          class="admin-btn admin-btn--ghost"
          style="margin-left: 0.5rem"
          :disabled="refundBusy"
          @click="refundOrder"
        >
          {{ refundBusy ? 'Refunding…' : 'Refund via Razorpay' }}
        </button>
        <p v-if="saved" style="color: var(--admin-accent); font-size: 0.8125rem">Saved.</p>
        <p v-if="error" class="admin-error">{{ error }}</p>
      </div>
    </div>
    </template>
  </div>
</template>

<style scoped>
.admin-pers-order-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.admin-pers-order-item {
  display: grid;
  grid-template-columns: 8rem 1fr;
  gap: 1rem;
  align-items: start;
}

.admin-pers-order-item img {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid var(--admin-border);
}

@media (max-width: 520px) {
  .admin-pers-order-item {
    grid-template-columns: 1fr;
  }
}
</style>
