<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import AdminPagination from '@/admin/components/AdminPagination.vue'
import { useAdminList } from '@/admin/composables/useAdminList'
import {
  adminDeleteBlogPost,
  adminImageSrc,
  adminListBlogPosts,
  type AdminBlogPost,
} from '@/admin/lib/adminApi'

const list = useAdminList(20)
const items = ref<AdminBlogPost[]>([])
const q = ref('')
const status = ref('all')

async function load() {
  const res = await list.run(() =>
    adminListBlogPosts(list.listParams({ q: q.value, status: status.value })),
  )
  if (res) {
    items.value = res.items
    list.setMeta(res.meta)
  }
}

async function remove(post: AdminBlogPost) {
  if (!confirm(`Delete "${post.title}"? This cannot be undone.`)) return
  try {
    await adminDeleteBlogPost(post.id)
    await load()
  } catch (e) {
    list.error.value = e instanceof Error ? e.message : 'Delete failed'
  }
}

function onFilterChange() {
  list.resetPage()
  void load()
}

function statusBadge(s: AdminBlogPost['status']) {
  if (s === 'published') return 'admin-badge admin-badge--ok'
  if (s === 'scheduled') return 'admin-badge admin-badge--warn'
  return 'admin-badge'
}

function formatDate(iso: string | null) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  })
}

watch(() => list.page.value, () => void load())

onMounted(() => void load())
</script>

<template>
  <div>
    <div class="admin-page-head">
      <h1 class="admin-page-title">Blog</h1>
      <div class="admin-actions">
        <RouterLink :to="{ name: 'admin-blog-new' }" class="admin-btn">+ New article</RouterLink>
      </div>
    </div>

    <div class="admin-toolbar">
      <input v-model="q" type="search" placeholder="Search title, slug, excerpt…" @keyup.enter="onFilterChange" />
      <select v-model="status" @change="onFilterChange">
        <option value="all">All statuses</option>
        <option value="published">Published</option>
        <option value="draft">Draft</option>
        <option value="scheduled">Scheduled</option>
      </select>
      <button type="button" class="admin-btn admin-btn--ghost" :disabled="list.busy.value" @click="load">
        Refresh
      </button>
    </div>

    <p v-if="list.error.value" class="admin-error">{{ list.error.value }}</p>
    <p class="admin-meta">{{ list.rangeLabel.value }}</p>

    <div class="admin-card admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th></th>
            <th>Title</th>
            <th>Status</th>
            <th>Author</th>
            <th>Published</th>
            <th>Read</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="post in items" :key="post.id">
            <td>
              <img
                v-if="post.featuredImageUrl || post.featuredImagePath"
                :src="post.featuredImageUrl || adminImageSrc(post.featuredImagePath!)"
                alt=""
                width="48"
                height="36"
                class="admin-thumb admin-thumb--wide"
              />
            </td>
            <td>
              <strong>{{ post.title }}</strong>
              <br />
              <span class="admin-cell-muted">/blog/{{ post.slug }}</span>
              <span v-if="post.isFeatured" class="admin-badge admin-badge--ok" style="margin-left: 0.35rem">
                Featured
              </span>
            </td>
            <td><span :class="statusBadge(post.status)">{{ post.status }}</span></td>
            <td>{{ post.authorName || '—' }}</td>
            <td>{{ formatDate(post.publishedAt) }}</td>
            <td>{{ post.readingTimeMin ? `${post.readingTimeMin} min` : '—' }}</td>
            <td class="admin-table__actions">
              <RouterLink :to="{ name: 'admin-blog-edit', params: { id: post.id } }" class="admin-btn admin-btn--ghost admin-btn--sm">
                Edit
              </RouterLink>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm admin-btn--danger" @click="remove(post)">
                Delete
              </button>
            </td>
          </tr>
          <tr v-if="!items.length && !list.busy.value">
            <td colspan="7" class="admin-empty">No blog posts yet. Create your first article.</td>
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

<style scoped>
.admin-thumb--wide {
  width: 48px;
  height: 36px;
  object-fit: cover;
  border-radius: 6px;
}
</style>
