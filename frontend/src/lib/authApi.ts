import { apiFetch, getApiBaseUrl } from '@/lib/api'

function apiPrefix(): string {
  return getApiBaseUrl() ? '/v1' : '/api/v1'
}

export type AuthUser = {
  id: number
  email: string
  fullName: string
}

type OkUser = { ok: true; user: AuthUser }
type OkToken = { ok: true; user: AuthUser; token: string }
type Fail = { ok: false; message?: string }

export async function authRegister(body: {
  email: string
  password: string
  fullName?: string
}): Promise<OkToken | Fail> {
  return apiFetch<OkToken | Fail>(`${apiPrefix()}/auth/register`, {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function authLogin(body: { email: string; password: string }): Promise<OkToken | Fail> {
  return apiFetch<OkToken | Fail>(`${apiPrefix()}/auth/login`, {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function authLogout(token: string | null): Promise<void> {
  if (!token) return
  try {
    await apiFetch<{ ok: boolean }>(`${apiPrefix()}/auth/logout`, {
      method: 'POST',
      authToken: token,
    })
  } catch {
    // still clear client session
  }
}

export async function authMe(token: string): Promise<OkUser | Fail> {
  return apiFetch<OkUser | Fail>(`${apiPrefix()}/auth/me`, { authToken: token })
}

export async function authPatchProfile(
  token: string,
  body: { fullName?: string; email?: string; currentPassword?: string }
): Promise<OkUser | Fail> {
  return apiFetch<OkUser | Fail>(`${apiPrefix()}/auth/me`, {
    method: 'PATCH',
    body: JSON.stringify(body),
    authToken: token,
  })
}

export async function authChangePassword(
  token: string,
  body: { currentPassword: string; newPassword: string }
): Promise<{ ok: true } | Fail> {
  return apiFetch<{ ok: true } | Fail>(`${apiPrefix()}/auth/change-password`, {
    method: 'POST',
    body: JSON.stringify(body),
    authToken: token,
  })
}

export async function authForgotPassword(email: string): Promise<{
  ok: boolean
  message: string
  debugResetToken?: string
}> {
  return apiFetch(`${apiPrefix()}/auth/forgot-password`, {
    method: 'POST',
    body: JSON.stringify({ email }),
  })
}

export async function authResetPassword(body: {
  token: string
  password: string
}): Promise<{ ok: true } | Fail> {
  return apiFetch<{ ok: true } | Fail>(`${apiPrefix()}/auth/reset-password`, {
    method: 'POST',
    body: JSON.stringify(body),
  })
}
