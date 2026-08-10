<script setup lang="ts">
import { ExternalLink, ImagePlus, Trash2 } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminDateTimePicker from '@/admin/components/AdminDateTimePicker.vue'
import AdminField from '@/admin/components/AdminField.vue'
import BlogRichEditor from '@/admin/components/BlogRichEditor.vue'
import {
  adminGetBlogPost,
  adminImageSrc,
  adminSaveBlogPost,
  adminUploadImage,
} from '@/admin/lib/adminApi'

const route = useRoute()
const router = useRouter()
const isNew = computed(() => route.name === 'admin-blog-new')
const tab = ref<'content' | 'seo' | 'social' | 'publish'>('content')
type PublishMode = 'draft' | 'now' | 'schedule'
const publishMode = ref<PublishMode>('draft')
const scheduleDate = ref<Date | null>(null)

const form = ref({
  title: '',
  slug: '',
  excerpt: '',
  contentHtml: '',
  status: 'draft' as 'draft' | 'published' | 'scheduled',
  featuredImagePath: '',
  authorName: '',
  tagsInput: '',
  isFeatured: false,
  metaTitle: '',
  metaDescription: '',
  metaKeywords: '',
  canonicalUrl: '',
  robotsIndex: true,
  ogTitle: '',
  ogDescription: '',
  ogImagePath: '',
  ogType: 'article',
  twitterCard: 'summary_large_image',
})

const error = ref('')
const busy = ref(false)
const uploadingFeatured = ref(false)
const uploadingOg = ref(false)
const featuredInput = ref<HTMLInputElement | null>(null)
const ogInput = ref<HTMLInputElement | null>(null)

const featuredPreview = computed(() =>
  form.value.featuredImagePath ? adminImageSrc(form.value.featuredImagePath) : '',
)
const ogPreview = computed(() => (form.value.ogImagePath ? adminImageSrc(form.value.ogImagePath) : ''))

const seoPreviewTitle = computed(() => form.value.metaTitle || form.value.title || 'Article title')
const seoPreviewDesc = computed(
  () => form.value.metaDescription || form.value.excerpt || 'Meta description preview for search engines.',
)

function slugify(text: string) {
  return text
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

function tagsArray() {
  return form.value.tagsInput
    .split(',')
    .map((t) => t.trim())
    .filter(Boolean)
}

async function uploadEditorImage(file: File) {
  const res = await adminUploadImage(file, 'posts', 'blog')
  return res.url ?? (res.path ? adminImageSrc(res.path) : null)
}

async function onFeaturedFile(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  uploadingFeatured.value = true
  error.value = ''
  try {
    const res = await adminUploadImage(file, 'posts', 'blog')
    if (res.path) form.value.featuredImagePath = res.path
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Upload failed'
  } finally {
    uploadingFeatured.value = false
  }
}

async function onOgFile(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  uploadingOg.value = true
  error.value = ''
  try {
    const res = await adminUploadImage(file, 'posts', 'blog')
    if (res.path) form.value.ogImagePath = res.path
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Upload failed'
  } finally {
    uploadingOg.value = false
  }
}

function fillSeoFromContent() {
  if (!form.value.metaTitle) form.value.metaTitle = form.value.title
  if (!form.value.metaDescription) form.value.metaDescription = form.value.excerpt.slice(0, 160)
  if (!form.value.ogTitle) form.value.ogTitle = form.value.metaTitle || form.value.title
  if (!form.value.ogDescription) form.value.ogDescription = form.value.metaDescription || form.value.excerpt
  if (!form.value.ogImagePath && form.value.featuredImagePath) {
    form.value.ogImagePath = form.value.featuredImagePath
  }
}

function parseApiDate(iso: string | null): Date | null {
  if (!iso) return null
  const normalized = iso.includes('T') ? iso : iso.replace(' ', 'T') + 'Z'
  const d = new Date(normalized)
  return Number.isNaN(d.getTime()) ? null : d
}

function formatApiDate(d: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getUTCFullYear()}-${pad(d.getUTCMonth() + 1)}-${pad(d.getUTCDate())} ${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}:${pad(d.getUTCSeconds())}`
}

function setPublishMode(mode: PublishMode) {
  publishMode.value = mode
  if (mode === 'schedule' && !scheduleDate.value) {
    const d = new Date()
    d.setHours(d.getHours() + 2, 0, 0, 0)
    scheduleDate.value = d
  }
}

async function load() {
  if (isNew.value) return
  const res = await adminGetBlogPost(Number(route.params.id))
  const p = res.post
  if (p.status === 'scheduled') {
    publishMode.value = 'schedule'
    scheduleDate.value = parseApiDate(p.publishedAt)
  } else if (p.status === 'published') {
    publishMode.value = 'now'
  } else {
    publishMode.value = 'draft'
  }
  form.value = {
    title: p.title,
    slug: p.slug,
    excerpt: p.excerpt,
    contentHtml: p.contentHtml,
    status: p.status,
    featuredImagePath: p.featuredImagePath ?? '',
    authorName: p.authorName,
    tagsInput: (p.tags ?? []).join(', '),
    isFeatured: p.isFeatured,
    metaTitle: p.metaTitle ?? '',
    metaDescription: p.metaDescription ?? '',
    metaKeywords: p.metaKeywords ?? '',
    canonicalUrl: p.canonicalUrl ?? '',
    robotsIndex: p.robotsIndex,
    ogTitle: p.ogTitle ?? '',
    ogDescription: p.ogDescription ?? '',
    ogImagePath: p.ogImagePath ?? '',
    ogType: p.ogType || 'article',
    twitterCard: p.twitterCard || 'summary_large_image',
  }
}

function payload() {
  let status: 'draft' | 'published' | 'scheduled' = 'draft'
  let publishedAt: string | null = null

  if (publishMode.value === 'now') {
    status = 'published'
  } else if (publishMode.value === 'schedule') {
    status = 'scheduled'
    publishedAt = scheduleDate.value ? formatApiDate(scheduleDate.value) : null
  }

  return {
    title: form.value.title.trim(),
    slug: form.value.slug.trim() || slugify(form.value.title),
    excerpt: form.value.excerpt.trim(),
    contentHtml: form.value.contentHtml,
    status,
    featuredImagePath: form.value.featuredImagePath || null,
    authorName: form.value.authorName.trim(),
    tags: tagsArray(),
    publishedAt,
    isFeatured: form.value.isFeatured,
    metaTitle: form.value.metaTitle.trim(),
    metaDescription: form.value.metaDescription.trim(),
    metaKeywords: form.value.metaKeywords.trim(),
    canonicalUrl: form.value.canonicalUrl.trim(),
    robotsIndex: form.value.robotsIndex,
    ogTitle: form.value.ogTitle.trim(),
    ogDescription: form.value.ogDescription.trim(),
    ogImagePath: form.value.ogImagePath || null,
    ogType: form.value.ogType,
    twitterCard: form.value.twitterCard,
  }
}

async function save() {
  error.value = ''
  if (!form.value.title.trim()) {
    error.value = 'Title is required'
    tab.value = 'content'
    return
  }
  if (publishMode.value === 'schedule' && !scheduleDate.value) {
    error.value = 'Pick a schedule date & time'
    tab.value = 'publish'
    return
  }
  if (publishMode.value === 'schedule' && scheduleDate.value && scheduleDate.value.getTime() <= Date.now()) {
    error.value = 'Schedule time must be in the future'
    tab.value = 'publish'
    return
  }
  busy.value = true
  try {
    const id = isNew.value ? undefined : Number(route.params.id)
    const res = await adminSaveBlogPost(payload(), id)
    if (!res.ok) {
      error.value = res.message ?? 'Save failed'
      return
    }
    if (isNew.value && res.post) {
      router.replace({ name: 'admin-blog-edit', params: { id: res.post.id } })
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Save failed'
  } finally {
    busy.value = false
  }
}

async function publishNow() {
  publishMode.value = 'now'
  tab.value = 'publish'
  await save()
}

const previewUrl = computed(() => {
  const slug = form.value.slug || slugify(form.value.title)
  return slug ? `/blog/${slug}` : ''
})

const canPreview = computed(() => publishMode.value === 'now')

onMounted(() => void load())
</script>

<template>
  <div>
    <div class="admin-page-head">
      <h1 class="admin-page-title">{{ isNew ? 'New article' : 'Edit article' }}</h1>
      <div class="admin-actions">
        <a
          v-if="previewUrl && canPreview"
          :href="previewUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="admin-btn admin-btn--ghost"
        >
          <ExternalLink :size="14" />
          View live
        </a>
        <button type="button" class="admin-btn admin-btn--ghost" :disabled="busy" @click="publishNow">
          Publish now
        </button>
        <button type="button" class="admin-btn" :disabled="busy" @click="save">
          {{ busy ? 'Saving…' : 'Save article' }}
        </button>
      </div>
    </div>

    <div class="blog-tabs" role="tablist">
      <button type="button" role="tab" :class="{ 'is-active': tab === 'content' }" @click="tab = 'content'">
        Content
      </button>
      <button type="button" role="tab" :class="{ 'is-active': tab === 'seo' }" @click="tab = 'seo'">
        SEO
      </button>
      <button type="button" role="tab" :class="{ 'is-active': tab === 'social' }" @click="tab = 'social'">
        Social / OG
      </button>
      <button type="button" role="tab" :class="{ 'is-active': tab === 'publish' }" @click="tab = 'publish'">
        Publish
      </button>
    </div>

    <div class="admin-card admin-form admin-form--wide">
      <section v-show="tab === 'content'" class="blog-panel">
        <div class="admin-form-grid">
          <div class="admin-field admin-field--span2">
            <label>Title *</label>
            <input v-model="form.title" required placeholder="How to choose the perfect bookmark" />
          </div>
          <div class="admin-field">
            <label>Slug</label>
            <input v-model="form.slug" placeholder="auto-from-title" />
          </div>
          <div class="admin-field">
            <label>Author</label>
            <input v-model="form.authorName" placeholder="The Minimark Team" />
          </div>
        </div>

        <div class="admin-field">
          <label>Excerpt</label>
          <textarea
            v-model="form.excerpt"
            rows="3"
            maxlength="500"
            placeholder="Short summary shown on the blog index and in search previews (recommended 120–160 chars)."
          />
          <p class="admin-field-hint">{{ form.excerpt.length }}/500 characters</p>
        </div>

        <div class="admin-field">
          <label>Tags (comma-separated)</label>
          <input v-model="form.tagsInput" placeholder="bookmarks, reading, gifts" />
        </div>

        <div class="admin-field">
          <label>Featured image</label>
          <input ref="featuredInput" type="file" accept="image/*" hidden @change="onFeaturedFile" />
          <div class="blog-image-row">
            <button
              type="button"
              class="admin-btn admin-btn--ghost"
              :disabled="uploadingFeatured"
              @click="featuredInput?.click()"
            >
              <ImagePlus :size="15" />
              {{ uploadingFeatured ? 'Uploading…' : 'Upload featured image' }}
            </button>
            <button
              v-if="form.featuredImagePath"
              type="button"
              class="admin-btn admin-btn--ghost admin-btn--danger"
              @click="form.featuredImagePath = ''"
            >
              <Trash2 :size="14" />
              Remove
            </button>
          </div>
          <img v-if="featuredPreview" :src="featuredPreview" alt="" class="blog-preview-img" />
        </div>

        <div class="admin-field">
          <label>Article body *</label>
          <BlogRichEditor v-model="form.contentHtml" :on-upload-image="uploadEditorImage" />
        </div>
      </section>

      <section v-show="tab === 'seo'" class="blog-panel">
        <div class="admin-actions" style="margin-bottom: 1rem">
          <button type="button" class="admin-btn admin-btn--ghost" @click="fillSeoFromContent">
            Auto-fill from content
          </button>
        </div>

        <div class="seo-preview">
          <p class="seo-preview__url">theminimark.com › blog › {{ form.slug || 'your-slug' }}</p>
          <p class="seo-preview__title">{{ seoPreviewTitle }}</p>
          <p class="seo-preview__desc">{{ seoPreviewDesc }}</p>
        </div>

        <div class="admin-field">
          <label>Meta title</label>
          <input v-model="form.metaTitle" maxlength="255" placeholder="Defaults to article title" />
        </div>
        <div class="admin-field">
          <label>Meta description</label>
          <textarea
            v-model="form.metaDescription"
            rows="3"
            maxlength="512"
            placeholder="155–160 characters ideal for Google snippets"
          />
        </div>
        <div class="admin-field">
          <label>Meta keywords</label>
          <input v-model="form.metaKeywords" placeholder="bookmark gifts, stationery blog, reading tips" />
        </div>
        <div class="admin-field">
          <label>Canonical URL</label>
          <input v-model="form.canonicalUrl" placeholder="Leave empty to use /blog/slug on your domain" />
        </div>
        <label class="admin-check">
          <input v-model="form.robotsIndex" type="checkbox" />
          Allow search engines to index this article (robots: index)
        </label>
      </section>

      <section v-show="tab === 'social'" class="blog-panel">
        <p class="admin-meta" style="margin-bottom: 1rem">
          Open Graph and Twitter Card tags control how your article appears when shared on Facebook, LinkedIn, X, and WhatsApp.
        </p>

        <div class="admin-form-grid">
          <div class="admin-field">
            <label>OG title</label>
            <input v-model="form.ogTitle" placeholder="Defaults to meta title" />
          </div>
          <div class="admin-field">
            <label>OG type</label>
            <select v-model="form.ogType">
              <option value="article">article</option>
              <option value="website">website</option>
            </select>
          </div>
        </div>
        <div class="admin-field">
          <label>OG description</label>
          <textarea v-model="form.ogDescription" rows="2" maxlength="512" />
        </div>
        <div class="admin-field">
          <label>OG image (1200×630 recommended)</label>
          <input ref="ogInput" type="file" accept="image/*" hidden @change="onOgFile" />
          <div class="blog-image-row">
            <button type="button" class="admin-btn admin-btn--ghost" :disabled="uploadingOg" @click="ogInput?.click()">
              <ImagePlus :size="15" />
              {{ uploadingOg ? 'Uploading…' : 'Upload OG image' }}
            </button>
            <button
              v-if="form.ogImagePath"
              type="button"
              class="admin-btn admin-btn--ghost"
              @click="form.ogImagePath = form.featuredImagePath"
            >
              Use featured image
            </button>
          </div>
          <img v-if="ogPreview" :src="ogPreview" alt="" class="blog-preview-img blog-preview-img--og" />
        </div>
        <div class="admin-field">
          <label>Twitter card type</label>
          <select v-model="form.twitterCard">
            <option value="summary_large_image">summary_large_image (recommended)</option>
            <option value="summary">summary</option>
          </select>
        </div>
      </section>

      <section v-show="tab === 'publish'" class="blog-panel">
        <p class="admin-meta" style="margin-bottom: 1rem">Choose how this article goes live on the storefront.</p>

        <div class="admin-publish-modes">
          <button
            type="button"
            class="admin-publish-mode"
            :class="{ 'admin-publish-mode--active': publishMode === 'draft' }"
            @click="setPublishMode('draft')"
          >
            <span class="admin-publish-mode__title">Save draft</span>
            <span class="admin-publish-mode__desc">Hidden from the public blog</span>
          </button>
          <button
            type="button"
            class="admin-publish-mode"
            :class="{ 'admin-publish-mode--active': publishMode === 'now' }"
            @click="setPublishMode('now')"
          >
            <span class="admin-publish-mode__title">Publish now</span>
            <span class="admin-publish-mode__desc">Live immediately on save</span>
          </button>
          <button
            type="button"
            class="admin-publish-mode"
            :class="{ 'admin-publish-mode--active': publishMode === 'schedule' }"
            @click="setPublishMode('schedule')"
          >
            <span class="admin-publish-mode__title">Schedule</span>
            <span class="admin-publish-mode__desc">Pick date &amp; time to go live</span>
          </button>
        </div>

        <AdminField
          v-if="publishMode === 'schedule'"
          label="Schedule for"
          hint="Article appears on the blog when this time is reached (stored in UTC)."
          :span="12"
        >
          <AdminDateTimePicker v-model="scheduleDate" placeholder="Select publish date & time" :min-date="new Date()" />
        </AdminField>

        <label class="admin-check" style="margin-top: 1rem">
          <input v-model="form.isFeatured" type="checkbox" />
          Feature on blog homepage
        </label>
      </section>

      <p v-if="error" class="admin-error">{{ error }}</p>
      <div class="admin-actions" style="margin-top: 1.25rem">
        <button type="button" class="admin-btn" :disabled="busy" @click="save">
          {{ busy ? 'Saving…' : 'Save article' }}
        </button>
        <button type="button" class="admin-btn admin-btn--ghost" @click="router.back()">Cancel</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-form--wide {
  max-width: 52rem;
}
.admin-field--span2 {
  grid-column: span 2;
}
@media (max-width: 640px) {
  .admin-field--span2 {
    grid-column: span 1;
  }
}
.admin-form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr));
  gap: 1rem;
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
  margin-top: 0.75rem;
}
.blog-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-bottom: 1rem;
}
.blog-tabs button {
  padding: 0.45rem 0.85rem;
  border: 1px solid var(--admin-border);
  border-radius: 999px;
  background: #fff;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--admin-muted);
  cursor: pointer;
}
.blog-tabs button.is-active {
  background: var(--admin-accent);
  border-color: var(--admin-accent);
  color: #fff;
}
.blog-panel {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.blog-image-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}
.blog-preview-img {
  display: block;
  margin-top: 0.65rem;
  max-width: 100%;
  width: 16rem;
  border-radius: 10px;
  border: 1px solid var(--admin-border);
}
.blog-preview-img--og {
  width: 20rem;
}
.seo-preview {
  padding: 1rem;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid var(--admin-border);
  margin-bottom: 1rem;
}
.seo-preview__url {
  margin: 0 0 0.25rem;
  font-size: 0.75rem;
  color: #15803d;
}
.seo-preview__title {
  margin: 0 0 0.25rem;
  font-size: 1.125rem;
  color: #1a0dab;
  line-height: 1.3;
}
.seo-preview__desc {
  margin: 0;
  font-size: 0.8125rem;
  color: #4d5156;
  line-height: 1.45;
}
</style>
