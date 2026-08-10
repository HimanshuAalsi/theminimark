<script setup lang="ts">
import { Download, FileSpreadsheet, Upload } from 'lucide-vue-next'
import { ref } from 'vue'
import {
  adminBulkExportUrl,
  adminBulkImport,
  adminBulkTemplateUrl,
  getAdminToken,
} from '@/admin/lib/adminApi'

const csvFile = ref<File | null>(null)
const zipFile = ref<File | null>(null)
const dryRun = ref(true)
const busy = ref(false)
const result = ref('')
const errors = ref<string[]>([])
const warnings = ref<string[]>([])

function onCsv(e: Event) {
  csvFile.value = (e.target as HTMLInputElement).files?.[0] ?? null
}
function onZip(e: Event) {
  zipFile.value = (e.target as HTMLInputElement).files?.[0] ?? null
}

async function downloadTemplate() {
  const token = getAdminToken()
  const res = await fetch(adminBulkTemplateUrl(), {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  })
  const blob = await res.blob()
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = 'products_bulk_import_TEMPLATE.csv'
  a.click()
  URL.revokeObjectURL(a.href)
}

async function downloadExport() {
  const token = getAdminToken()
  const res = await fetch(adminBulkExportUrl(), {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  })
  const blob = await res.blob()
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = `products_export_${new Date().toISOString().slice(0, 10)}.csv`
  a.click()
  URL.revokeObjectURL(a.href)
}

async function runImport() {
  if (!csvFile.value) {
    result.value = 'Choose a CSV file first.'
    return
  }
  busy.value = true
  result.value = ''
  errors.value = []
  warnings.value = []
  try {
    const res = await adminBulkImport(csvFile.value, zipFile.value, dryRun.value)
    result.value = res.message ?? 'Done'
    errors.value = res.errors ?? []
    warnings.value = res.warnings ?? []
  } catch (e) {
    result.value = e instanceof Error ? e.message : 'Import failed'
    const body = (e as { body?: { errors?: string[] } }).body
    if (body?.errors) errors.value = body.errors
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div>
    <h1 class="admin-page-title">Bulk product import</h1>

    <div class="admin-card admin-bulk-help">
      <h2 class="admin-card-title">CSV format (reference template)</h2>
      <p class="admin-meta" style="margin-top: 0">
        Download the template, fill one row per product. Use <strong>image_urls</strong> with multiple
        paths separated by <code>|</code> (first image = main). You can also add extra rows with the same
        <code>id</code> and only <code>image_urls</code> filled (Shopify-style).
      </p>
      <ul class="admin-bulk-list">
        <li><strong>category_slug</strong> — must match a category in Admin → Categories</li>
        <li><strong>keywords</strong> — comma-separated search terms (SEO)</li>
        <li><strong>image_urls</strong> — <code>/uploads/...</code>, full https URL, or filename from ZIP</li>
        <li>Optional: sku, stock_quantity, seo_title, seo_description</li>
      </ul>
      <div class="admin-actions">
        <button type="button" class="admin-btn admin-btn--ghost" @click="downloadTemplate">
          <Download :size="16" />
          Download template CSV
        </button>
        <button type="button" class="admin-btn admin-btn--ghost" @click="downloadExport">
          <FileSpreadsheet :size="16" />
          Export current catalog
        </button>
      </div>
    </div>

    <div class="admin-card" style="margin-top: 1rem">
      <h2 class="admin-card-title">Upload</h2>
      <div class="admin-form" style="max-width: 32rem">
        <div class="admin-field">
          <label>Products CSV *</label>
          <input type="file" accept=".csv,text/csv" @change="onCsv" />
        </div>
        <div class="admin-field">
          <label>Images ZIP (optional)</label>
          <input type="file" accept=".zip,application/zip" @change="onZip" />
          <p class="admin-field-hint">Filenames in ZIP must match CSV image references (e.g. sample-1.jpg).</p>
        </div>
        <label class="admin-check">
          <input v-model="dryRun" type="checkbox" />
          Validate only (dry run) — recommended first
        </label>
        <button type="button" class="admin-btn" :disabled="busy" @click="runImport">
          <Upload :size="16" />
          {{ busy ? 'Working…' : dryRun ? 'Validate CSV' : 'Import products' }}
        </button>
        <p v-if="result" class="admin-result">{{ result }}</p>
        <ul v-if="errors.length" class="admin-error-list">
          <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
        </ul>
        <ul v-if="warnings.length" class="admin-warn-list">
          <li v-for="(w, i) in warnings" :key="i">{{ w }}</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-bulk-help code {
  font-size: 0.75rem;
  background: #f1f5f9;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
}
.admin-bulk-list {
  margin: 0.75rem 0 1rem;
  padding-left: 1.2rem;
  font-size: 0.8125rem;
  line-height: 1.5;
  color: var(--admin-muted);
}
.admin-field-hint {
  margin: 0.35rem 0 0;
  font-size: 0.6875rem;
  color: var(--admin-muted);
}
.admin-check {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.8125rem;
  font-weight: 600;
}
.admin-result {
  margin: 0;
  font-weight: 600;
  color: var(--admin-primary);
}
.admin-error-list {
  margin: 0;
  padding: 0.65rem 0.85rem 0.65rem 1.5rem;
  background: #fef2f2;
  border-radius: 8px;
  font-size: 0.75rem;
  color: #b91c1c;
}
.admin-warn-list {
  margin: 0;
  padding: 0.65rem 0.85rem 0.65rem 1.5rem;
  background: #fffbeb;
  border-radius: 8px;
  font-size: 0.75rem;
  color: #b45309;
}
</style>
