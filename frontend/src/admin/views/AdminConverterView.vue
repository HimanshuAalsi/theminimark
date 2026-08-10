<script setup lang="ts">
import { Download, ImageDown, Loader2, RefreshCw, Save, ScanSearch, Upload } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import {
  adminConverterDownloadUrl,
  adminConverterRun,
  adminConverterSaveSettings,
  adminConverterScan,
  adminConverterStatus,
  adminConverterUploadFiles,
  getAdminToken,
  type ConverterSettings,
  type ConverterScanSummary,
  type ConverterUploadResult,
} from '@/admin/lib/adminApi'

const webpAvailable = ref(true)
const summary = ref<ConverterScanSummary | null>(null)
const sample = ref<{ path: string; sizeBytes: number; format: string }[]>([])
const settings = ref<ConverterSettings>({
  webpQuality: 92,
  maxDimension: 2560,
  scopes: { products: true, site: true, personalise: false },
  reoptimizeExistingWebp: false,
  updateDatabasePaths: true,
})

const error = ref('')
const info = ref('')
const saving = ref(false)
const scanning = ref(false)
const running = ref(false)
const dryRun = ref(true)
const batchSize = ref(15)
const progressPct = ref(0)
const runStats = ref<{ converted: number; bytesSaved: number; dbRowsUpdated: number; failed: number } | null>(null)
const lastErrors = ref<string[]>([])

const uploadInput = ref<HTMLInputElement | null>(null)
const pickedFiles = ref<File[]>([])
const uploading = ref(false)
const uploadResult = ref<ConverterUploadResult | null>(null)
const zipAvailable = ref(true)

function onPickFiles(e: Event) {
  const list = (e.target as HTMLInputElement).files
  pickedFiles.value = list ? Array.from(list) : []
  uploadResult.value = null
}

async function uploadAndConvert() {
  if (pickedFiles.value.length === 0) return
  uploading.value = true
  error.value = ''
  info.value = ''
  uploadResult.value = null
  try {
    const res = await adminConverterUploadFiles(pickedFiles.value)
    uploadResult.value = res
    info.value = `Converted ${res.fileCount ?? 0} file(s) — ready to download ZIP`
    pickedFiles.value = []
    if (uploadInput.value) uploadInput.value.value = ''
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Upload failed'
  } finally {
    uploading.value = false
  }
}

async function downloadZip() {
  const jobId = uploadResult.value?.jobId
  if (!jobId) return
  const token = getAdminToken()
  const res = await fetch(adminConverterDownloadUrl(jobId), {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  })
  if (!res.ok) {
    error.value = 'Download failed — the ZIP may have expired (24h). Convert again.'
    return
  }
  const blob = await res.blob()
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = `webp-converted-${new Date().toISOString().slice(0, 10)}.zip`
  a.click()
  URL.revokeObjectURL(a.href)
}

const pickedLabel = computed(() => {
  if (!pickedFiles.value.length) return 'No files selected'
  const n = pickedFiles.value.length
  const bytes = pickedFiles.value.reduce((s, f) => s + f.size, 0)
  return `${n} file${n === 1 ? '' : 's'} · ${fmtBytes(bytes)}`
})

function fmtBytes(n: number): string {
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  return `${(n / (1024 * 1024)).toFixed(2)} MB`
}

const extSummary = computed(() => {
  if (!summary.value?.byExtension) return ''
  return Object.entries(summary.value.byExtension)
    .map(([ext, n]) => `${ext}: ${n}`)
    .join(' · ')
})

async function loadStatus() {
  error.value = ''
  const res = await adminConverterStatus()
  webpAvailable.value = res.webpAvailable
  zipAvailable.value = res.zipAvailable ?? true
  settings.value = res.settings
  summary.value = res.summary
}

async function saveSettings() {
  saving.value = true
  error.value = ''
  info.value = ''
  try {
    const res = await adminConverterSaveSettings(settings.value)
    if (!res.ok) {
      error.value = res.message ?? 'Could not save settings'
      return
    }
    if (res.settings) settings.value = res.settings
    info.value = 'Settings saved. New uploads and bulk conversion will use these values.'
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Save failed'
  } finally {
    saving.value = false
  }
}

async function scan() {
  scanning.value = true
  error.value = ''
  info.value = ''
  try {
    const res = await adminConverterScan()
    webpAvailable.value = res.webpAvailable
    zipAvailable.value = res.zipAvailable ?? true
    settings.value = res.settings
    summary.value = res.summary
    sample.value = res.sample
    info.value = `Found ${res.summary.totalFiles} file(s) — ${fmtBytes(res.summary.totalBytes)} total`
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Scan failed'
  } finally {
    scanning.value = false
  }
}

async function runConversion() {
  if (!summary.value?.totalFiles && !confirm('Run scan first? No files counted yet. Continue anyway?')) {
    await scan()
    if (!summary.value?.totalFiles) return
  }
  if (!dryRun.value && !confirm('Convert images to WebP on the server? Original JPG/PNG files will be removed.')) {
    return
  }

  running.value = true
  error.value = ''
  info.value = ''
  lastErrors.value = []
  runStats.value = null
  progressPct.value = 0

  let offset = 0
  let totalConverted = 0
  let totalSaved = 0
  let totalDb = 0
  let totalFailed = 0
  const allErrors: string[] = []

  try {
    for (;;) {
      const res = await adminConverterRun({
        dryRun: dryRun.value,
        offset,
        limit: batchSize.value,
      })
      if (!res.ok) {
        error.value = res.message ?? 'Conversion failed'
        break
      }

      totalConverted += res.stats.converted
      totalSaved += res.stats.bytesSaved
      totalDb += res.stats.dbRowsUpdated
      totalFailed += res.stats.failed
      allErrors.push(...res.errors)

      const { total, nextOffset, done } = res.progress
      progressPct.value = total > 0 ? Math.round((nextOffset / total) * 100) : 100

      if (done) {
        runStats.value = {
          converted: totalConverted,
          bytesSaved: totalSaved,
          dbRowsUpdated: totalDb,
          failed: totalFailed,
        }
        lastErrors.value = allErrors.slice(0, 20)
        info.value = dryRun.value
          ? `Dry run complete — ${totalConverted} file(s) would be converted`
          : `Done — ${totalConverted} converted, ${fmtBytes(totalSaved)} saved`
        await loadStatus()
        break
      }
      offset = nextOffset
    }
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Conversion failed'
  } finally {
    running.value = false
  }
}

onMounted(() => {
  loadStatus().catch((e) => {
    error.value = e instanceof Error ? e.message : 'Failed to load converter'
  })
})
</script>

<template>
  <div>
    <div class="admin-page-head">
      <h1 class="admin-page-title">WebP converter</h1>
    </div>

    <p class="admin-meta">
      Upload external images for WebP conversion (download ZIP), or convert files already on the server.
    </p>

    <p v-if="!webpAvailable" class="admin-error">
      WebP is not available on this server. Enable PHP GD with WebP support in Hostinger.
    </p>
    <p v-if="error" class="admin-error">{{ error }}</p>
    <p v-if="info" class="admin-meta" style="color: var(--admin-accent)">{{ info }}</p>

    <!-- External upload → ZIP -->
    <div class="admin-card conv-upload-card">
      <h2 class="admin-card-title">Upload &amp; download ZIP</h2>
      <p class="admin-meta">
        Pick JPG, PNG, or GIF from your computer. Files are converted with the settings below and
        packaged as a ZIP — nothing is added to the live shop until you upload them yourself.
      </p>
      <p v-if="!zipAvailable" class="admin-error">ZIP export requires PHP ZipArchive on the server.</p>

      <input
        ref="uploadInput"
        type="file"
        accept="image/jpeg,image/png,image/gif,image/webp"
        multiple
        hidden
        @change="onPickFiles"
      />

      <div class="conv-upload-row">
        <button
          type="button"
          class="admin-btn admin-btn--ghost"
          :disabled="uploading"
          @click="uploadInput?.click()"
        >
          <Upload :size="16" />
          Choose files
        </button>
        <span class="admin-meta">{{ pickedLabel }}</span>
        <button
          type="button"
          class="admin-btn"
          :disabled="uploading || pickedFiles.length === 0 || !webpAvailable || !zipAvailable"
          @click="uploadAndConvert"
        >
          <Loader2 v-if="uploading" :size="16" class="conv-spin" />
          <ImageDown v-else :size="16" />
          {{ uploading ? 'Converting…' : 'Convert &amp; prepare ZIP' }}
        </button>
      </div>
      <p class="admin-meta">Up to 50 files, 8 MB each, 80 MB total per batch.</p>

      <div v-if="uploadResult?.ok" class="conv-upload-result">
        <p>
          <strong>{{ uploadResult.fileCount }}</strong> WebP file(s) ·
          <strong>{{ fmtBytes(uploadResult.stats?.bytesSaved ?? 0) }}</strong> saved vs originals ·
          ZIP {{ fmtBytes(uploadResult.zipBytes ?? 0) }}
        </p>
        <button type="button" class="admin-btn" @click="downloadZip">
          <Download :size="16" />
          Download ZIP
        </button>
        <ul v-if="uploadResult.files?.length" class="conv-sample">
          <li v-for="(f, i) in uploadResult.files" :key="i">
            <code>{{ f.originalName }}</code>
            <span class="admin-meta">→ {{ f.webpName }} · saved {{ fmtBytes(f.bytesSaved) }}</span>
          </li>
        </ul>
        <ul v-if="uploadResult.errors?.length" class="conv-errors">
          <li v-for="(err, i) in uploadResult.errors" :key="i">{{ err }}</li>
        </ul>
      </div>
    </div>

    <div class="admin-card conv-grid">
      <section class="conv-section">
        <h2 class="admin-card-title">Settings</h2>
        <p class="admin-meta">Saved to server and applied to product uploads + bulk conversion.</p>

        <div class="admin-form">
          <div class="admin-field">
            <label>WebP quality ({{ settings.webpQuality }})</label>
            <input v-model.number="settings.webpQuality" type="range" min="80" max="100" step="1" />
            <span class="admin-meta">80 = smaller files · 100 = maximum quality</span>
          </div>
          <div class="admin-field">
            <label>Max dimension (px)</label>
            <input v-model.number="settings.maxDimension" type="number" min="800" max="4096" step="100" />
            <span class="admin-meta">Only downscale if longest edge exceeds this (camera photos).</span>
          </div>

          <fieldset class="conv-scopes">
            <legend>Folders to include</legend>
            <label class="admin-check">
              <input v-model="settings.scopes.products" type="checkbox" />
              Product images
            </label>
            <label class="admin-check">
              <input v-model="settings.scopes.site" type="checkbox" />
              Site / banners / home
            </label>
            <label class="admin-check">
              <input v-model="settings.scopes.personalise" type="checkbox" />
              Personalise drafts
            </label>
          </fieldset>

          <label class="admin-check">
            <input v-model="settings.reoptimizeExistingWebp" type="checkbox" />
            Re-compress existing WebP files
          </label>
          <label class="admin-check">
            <input v-model="settings.updateDatabasePaths" type="checkbox" />
            Update database paths when extension changes (.jpg → .webp)
          </label>

          <button type="button" class="admin-btn" :disabled="saving" @click="saveSettings">
            <Save :size="16" />
            {{ saving ? 'Saving…' : 'Save settings' }}
          </button>
        </div>
      </section>

      <section class="conv-section">
        <h2 class="admin-card-title">Scan &amp; convert</h2>

        <div v-if="summary" class="conv-stats">
          <div class="conv-stat">
            <span class="conv-stat__label">Files to process</span>
            <strong>{{ summary.totalFiles }}</strong>
          </div>
          <div class="conv-stat">
            <span class="conv-stat__label">Total size</span>
            <strong>{{ fmtBytes(summary.totalBytes) }}</strong>
          </div>
        </div>
        <p v-if="extSummary" class="admin-meta">{{ extSummary }}</p>

        <div class="admin-toolbar" style="margin-top: 0.75rem">
          <button type="button" class="admin-btn admin-btn--ghost" :disabled="scanning" @click="scan">
            <ScanSearch :size="16" />
            {{ scanning ? 'Scanning…' : 'Scan uploads' }}
          </button>
          <button type="button" class="admin-btn admin-btn--ghost" @click="loadStatus">
            <RefreshCw :size="16" />
            Refresh
          </button>
        </div>

        <label class="admin-check" style="margin-top: 1rem">
          <input v-model="dryRun" type="checkbox" />
          Dry run (preview only — no files changed)
        </label>

        <div class="admin-field" style="margin-top: 0.5rem">
          <label>Batch size per request</label>
          <input v-model.number="batchSize" type="number" min="5" max="50" />
        </div>

        <button
          type="button"
          class="admin-btn"
          style="margin-top: 0.75rem"
          :disabled="running || !webpAvailable"
          @click="runConversion"
        >
          <Loader2 v-if="running" :size="16" class="conv-spin" />
          <ImageDown v-else :size="16" />
          {{ running ? 'Converting…' : dryRun ? 'Run dry conversion' : 'Convert to WebP' }}
        </button>

        <div v-if="running || progressPct > 0" class="conv-progress">
          <div class="conv-progress__bar" :style="{ width: `${progressPct}%` }" />
          <span class="admin-meta">{{ progressPct }}%</span>
        </div>

        <div v-if="runStats" class="conv-results">
          <p><strong>{{ runStats.converted }}</strong> converted · <strong>{{ fmtBytes(runStats.bytesSaved) }}</strong> saved</p>
          <p v-if="runStats.dbRowsUpdated" class="admin-meta">{{ runStats.dbRowsUpdated }} database row(s) updated</p>
          <p v-if="runStats.failed" class="admin-error">{{ runStats.failed }} failed</p>
          <ul v-if="lastErrors.length" class="conv-errors">
            <li v-for="(err, i) in lastErrors" :key="i">{{ err }}</li>
          </ul>
        </div>

        <ul v-if="sample.length" class="conv-sample">
          <li v-for="(f, i) in sample" :key="i">
            <code>{{ f.path }}</code>
            <span class="admin-meta">{{ f.format }} · {{ fmtBytes(f.sizeBytes) }}</span>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<style scoped>
.conv-grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));
}

.conv-upload-card {
  margin-bottom: 1.25rem;
}

.conv-upload-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.75rem;
}

.conv-upload-result {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--admin-border);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.75rem;
}

.conv-section {
  min-width: 0;
}

.conv-scopes {
  border: 1px solid var(--admin-border);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  margin: 0.5rem 0 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.conv-scopes legend {
  font-size: 0.8125rem;
  font-weight: 600;
  padding: 0 0.25rem;
}

.conv-stats {
  display: flex;
  gap: 1.5rem;
  margin-top: 0.5rem;
}

.conv-stat {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.conv-stat__label {
  font-size: 0.75rem;
  color: var(--admin-muted);
}

.conv-progress {
  margin-top: 0.75rem;
  height: 8px;
  background: var(--admin-border);
  border-radius: 4px;
  position: relative;
  overflow: hidden;
}

.conv-progress__bar {
  height: 100%;
  background: var(--admin-accent);
  transition: width 0.2s ease;
}

.conv-progress span {
  display: block;
  margin-top: 0.35rem;
}

.conv-results {
  margin-top: 1rem;
  font-size: 0.875rem;
}

.conv-errors {
  margin: 0.5rem 0 0;
  padding-left: 1.1rem;
  font-size: 0.75rem;
  color: var(--admin-danger, #c0392b);
}

.conv-sample {
  margin: 1rem 0 0;
  padding: 0;
  list-style: none;
  font-size: 0.75rem;
  max-height: 12rem;
  overflow: auto;
}

.conv-sample li {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  padding: 0.35rem 0;
  border-bottom: 1px solid var(--admin-border);
}

.conv-sample code {
  word-break: break-all;
}

.conv-spin {
  animation: conv-spin 0.8s linear infinite;
}

@keyframes conv-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
