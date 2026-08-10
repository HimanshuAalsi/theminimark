import { onUnmounted, watch, type Ref } from 'vue'

export interface SeoMetaInput {
  title?: string
  description?: string
  keywords?: string | null
  canonicalUrl?: string
  robotsIndex?: boolean
  og?: {
    title?: string
    description?: string
    imageUrl?: string | null
    type?: string
    url?: string
  }
  twitterCard?: string
  jsonLd?: Record<string, unknown> | null
}

const MANAGED_ATTR = 'data-tm-seo'

function upsertMeta(name: string, content: string, attr: 'name' | 'property' = 'name') {
  if (!content) return
  let el = document.head.querySelector<HTMLMetaElement>(`meta[${attr}="${name}"][${MANAGED_ATTR}]`)
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute(attr, name)
    el.setAttribute(MANAGED_ATTR, '1')
    document.head.appendChild(el)
  }
  el.content = content
}

function upsertLink(rel: string, href: string) {
  if (!href) return
  let el = document.head.querySelector<HTMLLinkElement>(`link[rel="${rel}"][${MANAGED_ATTR}]`)
  if (!el) {
    el = document.createElement('link')
    el.rel = rel
    el.setAttribute(MANAGED_ATTR, '1')
    document.head.appendChild(el)
  }
  el.href = href
}

function removeManaged() {
  document.head.querySelectorAll(`[${MANAGED_ATTR}]`).forEach((el) => el.remove())
}

export function applySeoMeta(meta: SeoMetaInput) {
  removeManaged()

  if (meta.title) {
    document.title = meta.title.includes('The Minimark') ? meta.title : `${meta.title} | The Minimark`
  }

  if (meta.description) upsertMeta('description', meta.description)
  if (meta.keywords) upsertMeta('keywords', meta.keywords)
  if (meta.robotsIndex === false) upsertMeta('robots', 'noindex, nofollow')
  else if (meta.robotsIndex === true) upsertMeta('robots', 'index, follow')

  if (meta.canonicalUrl) upsertLink('canonical', meta.canonicalUrl)

  const og = meta.og ?? {}
  if (og.title ?? meta.title) upsertMeta('og:title', og.title ?? meta.title ?? '', 'property')
  if (og.description ?? meta.description) {
    upsertMeta('og:description', og.description ?? meta.description ?? '', 'property')
  }
  if (og.imageUrl) upsertMeta('og:image', og.imageUrl, 'property')
  if (og.type) upsertMeta('og:type', og.type, 'property')
  if (og.url ?? meta.canonicalUrl) upsertMeta('og:url', og.url ?? meta.canonicalUrl ?? '', 'property')
  upsertMeta('og:site_name', 'The Minimark', 'property')

  if (meta.twitterCard) upsertMeta('twitter:card', meta.twitterCard)
  if (og.title ?? meta.title) upsertMeta('twitter:title', og.title ?? meta.title ?? '')
  if (og.description ?? meta.description) {
    upsertMeta('twitter:description', og.description ?? meta.description ?? '')
  }
  if (og.imageUrl) upsertMeta('twitter:image', og.imageUrl)

  if (meta.jsonLd) {
    const script = document.createElement('script')
    script.type = 'application/ld+json'
    script.setAttribute(MANAGED_ATTR, '1')
    script.textContent = JSON.stringify(meta.jsonLd)
    document.head.appendChild(script)
  }
}

export function useSeoMeta(source: Ref<SeoMetaInput | null | undefined>) {
  watch(
    source,
    (meta) => {
      if (meta) applySeoMeta(meta)
    },
    { immediate: true, deep: true },
  )

  onUnmounted(() => {
    removeManaged()
  })
}
