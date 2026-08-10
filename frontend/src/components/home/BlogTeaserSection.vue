<script setup lang="ts">
import { ArrowRight, Clock } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { fetchBlogPosts, type BlogPostSummary } from '@/lib/blog'

const props = withDefaults(
  defineProps<{
    limit?: number
    embedded?: boolean
    showHeader?: boolean
  }>(),
  { limit: 3, embedded: false, showHeader: true },
)

const posts = ref<BlogPostSummary[]>([])
const loaded = ref(false)

onMounted(async () => {
  try {
    const res = await fetchBlogPosts({ perPage: props.limit })
    posts.value = res.items
  } catch {
    posts.value = []
  } finally {
    loaded.value = true
  }
})

function formatDate(iso: string | null) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString(undefined, { dateStyle: 'medium' })
}

const visible = computed(() => loaded.value && posts.value.length > 0)
</script>

<template>
  <section v-if="visible" class="tm-section blog-teaser" :class="{ 'blog-teaser--embedded': embedded }">
    <div class="tm-container">
      <header v-if="showHeader" class="section-head section-head--split">
        <div class="section-head__text">
          <p class="section-eyebrow">From the journal</p>
          <h2 class="section-title">Latest on the blog</h2>
          <p class="section-desc">Reading tips, gift ideas, and stories from The Minimark.</p>
        </div>
        <RouterLink to="/blog" class="blog-teaser__cta tm-hover-lift">
          <span>View all articles</span>
          <ArrowRight :size="17" :stroke-width="2.25" aria-hidden="true" />
        </RouterLink>
      </header>

      <div class="blog-teaser__grid">
        <article v-for="post in posts" :key="post.id" class="blog-teaser__card">
          <RouterLink :to="{ name: 'blog-post', params: { slug: post.slug } }" class="blog-teaser__link">
            <div v-if="post.featuredImageUrl" class="blog-teaser__media">
              <img :src="post.featuredImageUrl" :alt="post.title" loading="lazy" />
            </div>
            <div class="blog-teaser__body">
              <h3>{{ post.title }}</h3>
              <p v-if="post.excerpt">{{ post.excerpt }}</p>
              <p class="blog-teaser__meta">
                <Clock :size="14" aria-hidden="true" />
                {{ post.readingTimeMin ?? 3 }} min
                <span v-if="post.publishedAt"> · {{ formatDate(post.publishedAt) }}</span>
              </p>
            </div>
          </RouterLink>
        </article>
      </div>
    </div>
  </section>
</template>

<style scoped>
.blog-teaser--embedded {
  padding-top: 0;
  padding-bottom: 0;
}

.blog-teaser__cta {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.65rem 1rem;
  border-radius: 999px;
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-ink);
  text-decoration: none;
}

.blog-teaser__grid {
  display: grid;
  gap: 1.25rem;
  grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr));
}

.blog-teaser__card {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  background: var(--color-surface-elevated);
  transition: transform 0.2s var(--ease-out, ease), box-shadow 0.2s ease;
}

.blog-teaser__card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
}

.blog-teaser__link {
  display: flex;
  flex-direction: column;
  height: 100%;
  color: inherit;
  text-decoration: none;
}

.blog-teaser__media {
  aspect-ratio: 16 / 10;
  overflow: hidden;
}

.blog-teaser__media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.blog-teaser__body {
  padding: 1rem 1.1rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  flex: 1;
}

.blog-teaser__body h3 {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.1rem;
  font-weight: 500;
  line-height: 1.35;
}

.blog-teaser__body p {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.55;
  color: var(--color-ink-muted);
}

.blog-teaser__meta {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-top: auto !important;
  padding-top: 0.5rem;
  font-size: 0.75rem !important;
}
</style>
