<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { adminListPersonalisations, type AdminPersonalisation } from '@/admin/lib/adminApi'
import { formatCurrency } from '@/lib/currency'

const items = ref<AdminPersonalisation[]>([])
const total = ref(0)
const typeFilter = ref('all')
const query = ref('')
const error = ref('')
const loading = ref(true)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await adminListPersonalisations({
      type: typeFilter.value,
      q: query.value.trim(),
      perPage: '50',
    })
    items.value = res.items
    total.value = res.meta.total
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to load'
  } finally {
    loading.value = false
  }
}

function optionLines(p: AdminPersonalisation): string[] {
  const o = p.options
  const lines: string[] = []
  if (o.quantity && Number(o.quantity) > 1) lines.push(`Qty ${o.quantity}`)
  if (o.packSize) lines.push(`Pack ${o.packSize}`)
  if (o.calendarLayout) lines.push(String(o.calendarLayout))
  if (o.startMonth && o.year) lines.push(`Start ${o.startMonth}/${o.year}`)
  if (o.occasion) lines.push(String(o.occasion))
  if (o.recipientName) lines.push(`To: ${o.recipientName}`)
  if (o.insideMessage) lines.push(String(o.insideMessage).slice(0, 80))
  if (o.finish) lines.push(String(o.finish))
  if (o.giftNote) lines.push(String(o.giftNote).slice(0, 60))
  return lines
}

onMounted(load)
</script>

<template>
  <div>
    <h1 class="admin-page-title">Custom personalisations</h1>
    <p class="admin-page-lead">
      Photos and options submitted from the Personalise studio, linked to orders.
    </p>

    <div class="admin-toolbar" style="margin-bottom: 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem">
      <select v-model="typeFilter" class="admin-input" style="width: auto" @change="load">
        <option value="all">All products</option>
        <option value="bookmark">Bookmarks</option>
        <option value="calendar">Calendars</option>
        <option value="card">Cards</option>
        <option value="magnet">Magnets</option>
      </select>
      <input
        v-model="query"
        type="search"
        class="admin-input"
        placeholder="Search email or order #"
        style="min-width: 14rem"
        @keyup.enter="load"
      />
      <button type="button" class="admin-btn" @click="load">Search</button>
    </div>

    <p v-if="error" class="admin-error">{{ error }}</p>
    <p v-else-if="loading" style="color: var(--admin-muted)">Loading…</p>
    <p v-else-if="!items.length" class="admin-empty">No personalisation orders yet.</p>

    <div v-else class="admin-pers-grid">
      <article v-for="p in items" :key="p.id" class="admin-card admin-pers-card">
        <a :href="p.photoUrl" target="_blank" rel="noopener" class="admin-pers-photo">
          <img :src="p.photoUrl" alt="Customer photo" loading="lazy" />
        </a>
        <div class="admin-pers-body">
          <p class="admin-pers-type">{{ p.productType }} · {{ p.productName }}</p>
          <p v-if="p.orderId" class="admin-pers-order">
            <RouterLink :to="{ name: 'admin-order', params: { id: p.orderId } }">
              Order #{{ p.orderId }}
            </RouterLink>
            <span class="admin-badge" :class="`admin-badge--${p.orderStatus}`">{{ p.orderStatus }}</span>
          </p>
          <p class="admin-pers-customer">{{ p.customerEmail }}</p>
          <p v-if="p.unitPrice != null" class="admin-pers-price">
            {{ formatCurrency(p.unitPrice) }} × {{ p.quantity }}
          </p>
          <ul class="admin-pers-meta">
            <li>Zoom {{ p.zoom }} · Position {{ Math.round(p.posX) }}% / {{ Math.round(p.posY) }}%</li>
            <li v-for="(line, i) in optionLines(p)" :key="i">{{ line }}</li>
          </ul>
        </div>
      </article>
    </div>

    <p v-if="items.length" style="margin-top: 1rem; font-size: 0.8125rem; color: var(--admin-muted)">
      Showing {{ items.length }} of {{ total }}
    </p>
  </div>
</template>

<style scoped>
.admin-page-lead {
  margin: -0.5rem 0 1.25rem;
  font-size: 0.875rem;
  color: var(--admin-muted);
  max-width: 36rem;
}

.admin-pers-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

.admin-pers-card {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  padding: 0;
}

.admin-pers-photo {
  display: block;
  aspect-ratio: 4 / 3;
  background: #f0f0ee;
  overflow: hidden;
}

.admin-pers-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.admin-pers-body {
  padding: 0.85rem 1rem 1rem;
}

.admin-pers-type {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 700;
  text-transform: capitalize;
}

.admin-pers-order {
  margin: 0.35rem 0 0;
  font-size: 0.8125rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.admin-pers-customer {
  margin: 0.25rem 0;
  font-size: 0.75rem;
  color: var(--admin-muted);
}

.admin-pers-price {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 600;
}

.admin-pers-meta {
  margin: 0.5rem 0 0;
  padding-left: 1rem;
  font-size: 0.6875rem;
  color: var(--admin-muted);
  line-height: 1.45;
}
</style>
