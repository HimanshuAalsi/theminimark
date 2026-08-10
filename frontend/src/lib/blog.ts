import { apiFetch, apiV1Prefix } from '@/lib/api'

export interface BlogPostSummary {
  id: number
  slug: string
  title: string
  excerpt: string | null
  featuredImageUrl: string | null
  authorName: string | null
  tags: string[]
  readingTimeMin: number | null
  publishedAt: string | null
  isFeatured: boolean
}

export interface BlogSeoPayload {
  metaTitle: string
  metaDescription: string
  metaKeywords: string | null
  canonicalUrl: string
  robotsIndex: boolean
}

export interface BlogOpenGraphPayload {
  title: string
  description: string
  imageUrl: string | null
  type: string
}

export interface BlogPostDetail extends BlogPostSummary {
  contentHtml: string
  seo: BlogSeoPayload
  openGraph: BlogOpenGraphPayload
  twitterCard: string
  updatedAt: string | null
}

export interface BlogListMeta {
  total: number
  page: number
  perPage: number
  count: number
}

export async function fetchBlogPosts(params: Record<string, string | number | undefined> = {}) {
  const q = new URLSearchParams()
  for (const [k, v] of Object.entries(params)) {
    if (v !== undefined && v !== '') q.set(k, String(v))
  }
  const suffix = q.toString() ? `?${q}` : ''
  return apiFetch<{ ok: boolean; items: BlogPostSummary[]; meta: BlogListMeta }>(
    `${apiV1Prefix()}/blog${suffix}`,
  )
}

export async function fetchBlogPostBySlug(slug: string) {
  return apiFetch<{ ok: boolean; post: BlogPostDetail }>(
    `${apiV1Prefix()}/blog/${encodeURIComponent(slug)}`,
  )
}
