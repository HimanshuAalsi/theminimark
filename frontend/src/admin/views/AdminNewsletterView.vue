<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { Download } from 'lucide-vue-next'
import AdminPagination from '@/admin/components/AdminPagination.vue'
import { useAdminList } from '@/admin/composables/useAdminList'
import {
  adminListNewsletter,
  adminNewsletterExportUrl,
  getAdminToken,
  type AdminNewsletterSubscriber,
} from '@/admin/lib/adminApi'

const list = useAdminList(50)
const items = ref<AdminNewsletterSubscriber[]>([])
const q = ref('')

async function load() {
  const res = await list.run(() => adminListNewsletter(list.listParams({ q: q.value })))
  if (res) {
    items.value = res.items
    list.setMeta(res.meta)
  }
}

async function exportCsv() {
  const token = getAdminToken()
  const res = await fetch(adminNewsletterExportUrl(), {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  })
  const blob = await res.blob()
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = `newsletter_${new Date().toISOString().slice(0, 10)}.csv`
  a.click()
  URL.revokeObjectURL(a.href)
}

watch(() => list.page.value, () => void load())

onMounted(load)
</script>

<template>
  <div>
    <div class="admin-page-head">
      <h1 class="admin-page-title">Newsletter</h1>
      <button type="button" class="admin-btn admin-btn--ghost" @click="exportCsv">
        <Download :size="16" />
        Export CSV
      </button>
    </div>
    <div class="admin-toolbar">
      <input v-model="q" type="search" placeholder="Search email…" @keyup.enter="() => { list.resetPage(); load() }" />
      <button type="button" class="admin-btn admin-btn--ghost" @click="load">Refresh</button>
    </div>
    <p class="admin-meta">{{ list.rangeLabel.value }} subscribers</p>
    <p v-if="list.error.value" class="admin-error">{{ list.error.value }}</p>
    <div class="admin-card admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Email</th>
            <th>Source</th>
            <th>Subscribed</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in items" :key="s.id">
            <td>{{ s.email }}</td>
            <td>{{ s.source || '—' }}</td>
            <td>{{ s.createdAt.slice(0, 16) }}</td>
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
