<script setup lang="ts">
import { Clock, Search } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { fetchBlogPosts, type BlogPostSummary } from '@/lib/blog'

const route = useRoute()
const router = useRouter()

const items = ref<BlogPostSummary[]>([])
const meta = ref({ total: 0, page: 1, perPage: 12, count: 0 })
const loading = ref(true)
const error = ref('')
const q = ref(String(route.query.q ?? ''))
const tag = ref(String(route.query.tag ?? ''))

const featured = computed(() => items.value.filter((p) => p.isFeatured))
const regular = computed(() => items.value.filter((p) => !p.isFeatured))
const totalPages = computed(() => Math.max(1, Math.ceil(meta.value.total / meta.value.perPage)))

const allTags = computed(() => {
  const set = new Set<string>()
  for (const post of items.value) {
    for (const t of post.tags) set.add(t)
  }
  return [...set].sort()
})

function formatDate(iso: string | null) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString(undefined, { dateStyle: 'long' })
}

async function load(page = meta.value.page) {
  loading.value = true
  error.value = ''
  try {
    const res = await fetchBlogPosts({
      page,
      perPage: 12,
      q: q.value || undefined,
      tag: tag.value || undefined,
    })
    items.value = res.items
    meta.value = res.meta
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Could not load blog'
    items.value = []
  } finally {
    loading.value = false
  }
}

function onSearch(e: Event) {
  e.preventDefault()
  router.push({ query: { q: q.value || undefined, tag: tag.value || undefined } })
}

function setTag(t: string) {
  tag.value = tag.value === t ? '' : t
  router.push({ query: { q: q.value || undefined, tag: tag.value || undefined } })
}

function goPage(p: number) {
  if (p < 1 || p > totalPages.value) return
  void load(p)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

watch(
  () => [route.query.q, route.query.tag] as const,
  ([newQ, newTag]) => {
    q.value = String(newQ ?? '')
    tag.value = String(newTag ?? '')
    void load(1)
  },
)

onMounted(() => void load(1))
</script>

<template>
  <div class="blog-page">
    <header class="blog-hero">
      <div class="tm-container blog-hero__inner">
        <p class="blog-hero__eyebrow">The Minimark Journal</p>
        <h1 class="blog-hero__title">Stories, tips &amp; inspiration</h1>
        <p class="blog-hero__lead">
          Reading habits, gift ideas, and behind-the-scenes from our bookmark and stationery studio.
        </p>
        <form class="blog-search" @submit="onSearch">
          <Search :size="18" aria-hidden="true" />
          <input v-model="q" type="search" placeholder="Search articles…" aria-label="Search blog" />
          <button type="submit" class="tm-press">Search</button>
        </form>
      </div>
    </header>

    <div class="tm-container blog-body">
      <p v-if="error" class="blog-error" role="alert">{{ error }}</p>

      <div v-if="allTags.length" class="blog-tags">
        <button
          v-for="t in allTags"
          :key="t"
          type="button"
          class="blog-tag"
          :class="{ 'blog-tag--active': tag === t }"
          @click="setTag(t)"
        >
          #{{ t }}
        </button>
      </div>

      <div v-if="loading" class="blog-grid blog-grid--loading">
        <div v-for="n in 6" :key="n" class="blog-card blog-card--skeleton" />
      </div>

      <template v-else>
        <section v-if="featured.length && !q && !tag" class="blog-featured">
          <h2 class="blog-section-title">Featured</h2>
          <div class="blog-grid blog-grid--featured">
            <article v-for="post in featured" :key="post.id" class="blog-card blog-card--featured">
              <RouterLink :to="{ name: 'blog-post', params: { slug: post.slug } }" class="blog-card__link">
                <div v-if="post.featuredImageUrl" class="blog-card__media">
                  <img :src="post.featuredImageUrl" :alt="post.title" loading="lazy" />
                </div>
                <div class="blog-card__body">
                  <h3>{{ post.title }}</h3>
                  <p v-if="post.excerpt">{{ post.excerpt }}</p>
                  <p class="blog-card__meta">
                    <Clock :size="14" aria-hidden="true" />
                    {{ post.readingTimeMin ?? 3 }} min read
                    <span v-if="post.publishedAt"> · {{ formatDate(post.publishedAt) }}</span>
                  </p>
                </div>
              </RouterLink>
            </article>
          </div>
        </section>

        <section>
          <h2 v-if="featured.length && !q && !tag" class="blog-section-title">Latest</h2>
          <div v-if="(q || tag ? items : regular).length" class="blog-grid">
            <article v-for="post in q || tag ? items : regular" :key="post.id" class="blog-card">
              <RouterLink :to="{ name: 'blog-post', params: { slug: post.slug } }" class="blog-card__link">
                <div v-if="post.featuredImageUrl" class="blog-card__media">
                  <img :src="post.featuredImageUrl" :alt="post.title" loading="lazy" />
                </div>
                <div class="blog-card__body">
                  <div v-if="post.tags.length" class="blog-card__tags">
                    <span v-for="t in post.tags.slice(0, 2)" :key="t">{{ t }}</span>
                  </div>
                  <h3>{{ post.title }}</h3>
                  <p v-if="post.excerpt">{{ post.excerpt }}</p>
                  <p class="blog-card__meta">
                    <Clock :size="14" aria-hidden="true" />
                    {{ post.readingTimeMin ?? 3 }} min
                    <span v-if="post.authorName"> · {{ post.authorName }}</span>
                  </p>
                </div>
              </RouterLink>
            </article>
          </div>
          <p v-else class="blog-empty">
            No articles published yet.
            <RouterLink to="/blog">Visit the blog</RouterLink>
            — new stories will appear here once you publish from the admin panel.
          </p>
        </section>

        <nav v-if="totalPages > 1" class="blog-pagination" aria-label="Blog pagination">
          <button type="button" :disabled="meta.page <= 1" @click="goPage(meta.page - 1)">Previous</button>
          <span>Page {{ meta.page }} of {{ totalPages }}</span>
          <button type="button" :disabled="meta.page >= totalPages" @click="goPage(meta.page + 1)">Next</button>
        </nav>
      </template>
    </div>
  </div>
</template>

<style scoped>
.blog-page {
  padding-bottom: 3rem;
}

.blog-hero {
  padding: 3rem 0 2.5rem;
  background: linear-gradient(165deg, var(--color-surface-elevated) 0%, var(--color-page) 55%);
  border-bottom: 1px solid var(--color-border);
}

.blog-hero__inner {
  max-width: 42rem;
}

.blog-hero__eyebrow {
  margin: 0 0 0.5rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.blog-hero__title {
  margin: 0 0 0.75rem;
  font-family: var(--font-display);
  font-size: clamp(2rem, 4vw, 2.75rem);
  font-weight: 500;
  line-height: 1.15;
  color: var(--color-ink);
}

.blog-hero__lead {
  margin: 0 0 1.5rem;
  font-size: 1.05rem;
  line-height: 1.6;
  color: var(--color-ink-muted);
}

.blog-search {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.35rem 0.35rem 0.35rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface-elevated);
  color: var(--color-ink-muted);
}

.blog-search input {
  flex: 1;
  border: none;
  background: transparent;
  font: inherit;
  color: var(--color-ink);
}

.blog-search button {
  border: none;
  border-radius: calc(var(--radius-md) - 2px);
  padding: 0.55rem 1rem;
  background: var(--tm-gradient);
  color: #fff;
  font-weight: 600;
  cursor: pointer;
}

.blog-body {
  padding-top: 2rem;
}

.blog-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin-bottom: 1.5rem;
}

.blog-tag {
  border: 1px solid var(--color-border);
  border-radius: 999px;
  padding: 0.3rem 0.75rem;
  background: var(--color-surface-elevated);
  font-size: 0.8125rem;
  color: var(--color-ink-muted);
  cursor: pointer;
}

.blog-tag--active,
.blog-tag:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
}

.blog-section-title {
  margin: 0 0 1rem;
  font-family: var(--font-display);
  font-size: 1.35rem;
  font-weight: 500;
}

.blog-grid {
  display: grid;
  gap: 1.25rem;
  grid-template-columns: repeat(12, minmax(0, 1fr));
}

.blog-card {
  grid-column: span 12;
}

@media (min-width: 640px) {
  .blog-card {
    grid-column: span 6;
  }
}

@media (min-width: 1024px) {
  .blog-card {
    grid-column: span 4;
  }

  .blog-card--featured {
    grid-column: span 6;
  }
}

.blog-grid--featured {
  margin-bottom: 2.5rem;
}

.blog-grid--featured .blog-card--featured:first-child {
  grid-column: span 12;
}

@media (min-width: 768px) {
  .blog-grid--featured .blog-card--featured:first-child {
    grid-column: span 8;
  }

  .blog-grid--featured .blog-card--featured:first-child .blog-card__media {
    aspect-ratio: 21 / 9;
  }
}

.blog-grid--loading {
  grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr));
}

.blog-grid--loading .blog-card {
  grid-column: auto;
}

.blog-card {
  border: 1px solid var(--color-border);
  border-radius: 16px;
  overflow: hidden;
  background: var(--color-surface-elevated);
  box-shadow: var(--shadow-sm);
  transition:
    transform 0.22s var(--ease-out, ease),
    box-shadow 0.22s ease;
}

.blog-card:active {
  transform: scale(0.985);
}

.blog-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
}

.blog-card__link {
  display: flex;
  flex-direction: column;
  height: 100%;
  color: inherit;
  text-decoration: none;
}

.blog-card__media {
  aspect-ratio: 16 / 10;
  overflow: hidden;
  background: var(--color-surface);
}

.blog-card__media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.blog-card__body {
  padding: 1rem 1.1rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  flex: 1;
}

.blog-card__body h3 {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.15rem;
  font-weight: 500;
  line-height: 1.35;
  color: var(--color-ink);
}

.blog-card__body p {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.55;
  color: var(--color-ink-muted);
}

.blog-card__meta {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-top: auto !important;
  padding-top: 0.5rem;
  font-size: 0.75rem !important;
}

.blog-card__tags {
  display: flex;
  gap: 0.35rem;
}

.blog-card__tags span {
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--color-accent);
}

.blog-card--featured .blog-card__body h3 {
  font-size: 1.35rem;
}

.blog-card--skeleton {
  min-height: 16rem;
  background: linear-gradient(90deg, var(--color-surface) 25%, var(--color-surface-elevated) 50%, var(--color-surface) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite;
}

@keyframes shimmer {
  to {
    background-position: -200% 0;
  }
}

.blog-empty,
.blog-error {
  text-align: center;
  padding: 2rem;
  color: var(--color-ink-muted);
}

.blog-error {
  color: #b91c1c;
}

.blog-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 2rem;
}

.blog-pagination button {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 0.5rem 1rem;
  background: var(--color-surface-elevated);
  cursor: pointer;
}

.blog-pagination button:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
</style>
