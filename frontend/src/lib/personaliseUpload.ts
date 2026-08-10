import { apiUrl, apiV1Prefix } from '@/lib/api'
import { resolveProductImageUrl } from '@/lib/productImage'

export async function uploadPersonalisePhoto(file: File): Promise<{
  path: string
  url: string
}> {
  const form = new FormData()
  form.append('file', file)
  const res = await fetch(apiUrl(`${apiV1Prefix()}/personalise/upload`), {
    method: 'POST',
    body: form,
  })
  const data = (await res.json()) as {
    ok?: boolean
    path?: string
    url?: string
    message?: string
  }
  if (!res.ok || !data.ok || !data.path) {
    throw new Error(data.message ?? 'Photo upload failed')
  }
  const path = data.path
  const url = data.url ? resolveProductImageUrl(data.url) : resolveProductImageUrl(path)
  return { path, url }
}
