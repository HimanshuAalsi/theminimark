/**
 * HTTP helpers for your Hostinger PHP API.
 * Set `VITE_API_BASE_URL` when the API runs on another origin (e.g. local PHP on :8080).
 */

export class ApiError extends Error {
  readonly status: number
  readonly body?: unknown

  constructor(message: string, status: number, body?: unknown) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.body = body
  }
}

export function getApiBaseUrl(): string {
  const raw = import.meta.env.VITE_API_BASE_URL
  if (typeof raw !== 'string' || !raw.trim()) return ''
  return raw.replace(/\/+$/, '')
}

export function apiUrl(path: string): string {
  const base = getApiBaseUrl()
  const p = path.startsWith('/') ? path : `/${path}`
  if (!base) return p
  return `${base}${p}`
}

export type ApiFetchOptions = RequestInit & { authToken?: string | null }

const UNREACHABLE =
  'Cannot reach the API. From the repo root run `npm run dev` (starts PHP on :8888 and Vite), or in one terminal `cd backend/api` then `php -S 127.0.0.1:8888 router.php`.'

function isGatewayStatus(status: number): boolean {
  return status === 502 || status === 503 || status === 504
}

export async function apiFetch<T>(path: string, init?: ApiFetchOptions): Promise<T> {
  const { authToken, ...rest } = init ?? {}
  const url = apiUrl(path)
  const headers = new Headers(rest.headers)
  if (!headers.has('Accept')) headers.set('Accept', 'application/json')
  if (rest.body != null && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }
  if (authToken) {
    headers.set('Authorization', `Bearer ${authToken}`)
  }

  let res: Response
  try {
    res = await fetch(url, { ...rest, headers })
  } catch {
    throw new ApiError(UNREACHABLE, 0)
  }

  const text = await res.text()
  let data: unknown = undefined
  if (text) {
    try {
      data = JSON.parse(text) as unknown
    } catch {
      data = text
    }
  }

  if (!res.ok) {
    const fromJson =
      typeof data === 'object' && data !== null && 'message' in data
        ? String((data as { message: unknown }).message)
        : null
    if (isGatewayStatus(res.status)) {
      const fallback =
        'API is not running or the connection was refused. Start PHP on 127.0.0.1:8888 (from the repo root: `npm run dev`).'
      throw new ApiError(fromJson && fromJson.trim() !== '' ? fromJson : fallback, res.status, data)
    }
    const msg =
      fromJson ??
      (typeof data === 'string' && data.trim().startsWith('<')
        ? UNREACHABLE
        : res.statusText || 'Request failed')
    throw new ApiError(msg, res.status, data)
  }

  return data as T
}
