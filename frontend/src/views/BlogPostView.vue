<script setup lang="ts">
import { ArrowLeft, Clock } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useSeoMeta } from '@/composables/useSeoMeta'
import { ApiError } from '@/lib/api'
import { fetchBlogPostBySlug, type BlogPostDetail } from '@/lib/blog'

const route = useRoute()
const post = ref<BlogPostDetail | null>(null)
const loading = ref(true)
const error = ref('')
const notFound = ref(false)

const slug = computed(() => String(route.params.slug ?? ''))

const seoInput = computed(() => {
  if (!post.value) return null
  const p = post.value
  return {
    title: p.seo.metaTitle,
    description: p.seo.metaDescription,
    keywords: p.seo.metaKeywords,
    canonicalUrl: p.seo.canonicalUrl,
    robotsIndex: p.seo.robotsIndex,
    og: {
      title: p.openGraph.title,
      description: p.openGraph.description,
      imageUrl: p.openGraph.imageUrl,
      type: p.openGraph.type,
      url: p.seo.canonicalUrl,
    },
    twitterCard: p.twitterCard,
    jsonLd: {
      '@context': 'https://schema.org',
      '@type': 'BlogPosting',
      headline: p.title,
      description: p.excerpt ?? p.seo.metaDescription,
      image: p.openGraph.imageUrl ? [p.openGraph.imageUrl] : undefined,
      author: p.authorName ? { '@type': 'Person', name: p.authorName } : undefined,
      datePublished: p.publishedAt ?? undefined,
      dateModified: p.updatedAt ?? p.publishedAt ?? undefined,
      mainEntityOfPage: { '@type': 'WebPage', '@id': p.seo.canonicalUrl },
    },
  }
})

useSeoMeta(seoInput)

function formatDate(iso: string | null) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString(undefined, { dateStyle: 'long' })
}

async function load() {
  loading.value = true
  error.value = ''
  notFound.value = false
  post.value = null
  try {
    const res = await fetchBlogPostBySlug(slug.value)
    post.value = res.post
  } catch (e) {
    if (e instanceof ApiError && e.status === 404) {
      notFound.value = true
    } else {
      error.value = e instanceof Error ? e.message : 'Could not load article'
    }
  } finally {
    loading.value = false
  }
}

watch(slug, () => void load(), { immediate: true })
</script>

<template>
  <article class="blog-article">
    <div class="tm-container blog-article__wrap">
      <RouterLink to="/blog" class="blog-back">
        <ArrowLeft :size="16" aria-hidden="true" />
        Back to blog
      </RouterLink>

      <div v-if="loading" class="blog-article__loading">Loading article…</div>

      <div v-else-if="notFound" class="blog-article__empty">
        <h1>Article not found</h1>
        <p>This post may have been removed or the link is incorrect.</p>
        <RouterLink to="/blog" class="tm-btn">Browse all articles</RouterLink>
      </div>

      <div v-else-if="error" class="blog-article__empty" role="alert">
        <p>{{ error }}</p>
      </div>

      <template v-else-if="post">
        <header class="blog-article__head">
          <div v-if="post.tags.length" class="blog-article__tags">
            <RouterLink
              v-for="t in post.tags"
              :key="t"
              :to="{ path: '/blog', query: { tag: t } }"
              class="blog-article__tag"
            >
              {{ t }}
            </RouterLink>
          </div>
          <h1 class="blog-article__title">{{ post.title }}</h1>
          <p v-if="post.excerpt" class="blog-article__excerpt">{{ post.excerpt }}</p>
          <p class="blog-article__meta">
            <span v-if="post.authorName">{{ post.authorName }}</span>
            <span v-if="post.publishedAt"> · {{ formatDate(post.publishedAt) }}</span>
            <span class="blog-article__read">
              <Clock :size="14" aria-hidden="true" />
              {{ post.readingTimeMin ?? 3 }} min read
            </span>
          </p>
        </header>

        <figure v-if="post.featuredImageUrl" class="blog-article__hero">
          <img :src="post.featuredImageUrl" :alt="post.title" />
        </figure>

        <div class="blog-prose" v-html="post.contentHtml" />
      </template>
    </div>
  </article>
</template>

<style scoped>
.blog-article {
  padding: 1.5rem 0 3.5rem;
}

.blog-article__wrap {
  max-width: 46rem;
}

.blog-back {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-accent);
}

.blog-article__loading,
.blog-article__empty {
  padding: 3rem 0;
  text-align: center;
  color: var(--color-ink-muted);
}

.blog-article__head {
  margin-bottom: 1.75rem;
}

.blog-article__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-bottom: 0.85rem;
}

.blog-article__tag {
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  background: var(--color-accent-soft);
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-accent);
}

.blog-article__title {
  margin: 0 0 0.85rem;
  font-family: var(--font-display);
  font-size: clamp(2rem, 4.5vw, 2.75rem);
  font-weight: 500;
  line-height: 1.15;
  color: var(--color-ink);
}

.blog-article__excerpt {
  margin: 0 0 1rem;
  font-size: 1.125rem;
  line-height: 1.6;
  color: var(--color-ink-muted);
}

.blog-article__meta {
  margin: 0;
  font-size: 0.875rem;
  color: var(--color-ink-muted);
}

.blog-article__read {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.blog-article__hero {
  margin: 0 0 2rem;
  border-radius: var(--radius-lg);
  overflow: hidden;
  border: 1px solid var(--color-border);
}

.blog-article__hero img {
  display: block;
  width: 100%;
  height: auto;
}

.blog-prose :deep(h2) {
  margin: 2rem 0 0.75rem;
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: 500;
  color: var(--color-ink);
}

.blog-prose :deep(h3) {
  margin: 1.5rem 0 0.55rem;
  font-size: 1.2rem;
  font-weight: 600;
}

.blog-prose :deep(p) {
  margin: 0 0 1rem;
  font-size: 1.02rem;
  line-height: 1.75;
  color: var(--color-ink);
}

.blog-prose :deep(ul),
.blog-prose :deep(ol) {
  margin: 0 0 1rem;
  padding-left: 1.35rem;
  line-height: 1.7;
}

.blog-prose :deep(blockquote) {
  margin: 1.25rem 0;
  padding: 0.65rem 0 0.65rem 1rem;
  border-left: 3px solid var(--color-accent);
  color: var(--color-ink-muted);
  font-style: italic;
}

.blog-prose :deep(a) {
  color: var(--color-accent);
  text-decoration: underline;
}

.blog-prose :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: var(--radius-md);
  margin: 1rem 0;
}

.blog-prose :deep(pre) {
  overflow-x: auto;
  padding: 1rem;
  border-radius: var(--radius-md);
  background: #0f172a;
  color: #e2e8f0;
  font-size: 0.875rem;
}

.blog-prose :deep(code) {
  font-size: 0.9em;
}

.blog-prose :deep(mark) {
  background: #fef08a;
  padding: 0 0.15rem;
}
</style>
