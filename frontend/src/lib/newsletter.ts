import { apiFetch, getApiBaseUrl } from '@/lib/api'

function apiPrefix(): string {
  return getApiBaseUrl() ? '/v1' : '/api/v1'
}

export async function submitNewsletter(email: string, source: string): Promise<{ ok: boolean; message: string }> {
  return apiFetch(`${apiPrefix()}/newsletter`, {
    method: 'POST',
    body: JSON.stringify({ email: email.trim(), source }),
  })
}
