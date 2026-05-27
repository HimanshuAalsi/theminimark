import { defineStore } from 'pinia'
import {
  authChangePassword,
  authLogin,
  authLogout,
  authMe,
  authPatchProfile,
  authRegister,
  type AuthUser,
} from '@/lib/authApi'

const TOKEN_KEY = 'tm_auth_token'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: null as string | null,
    user: null as AuthUser | null,
    initialized: false,
  }),
  getters: {
    isAuthenticated: (s) => s.token != null && s.user != null,
  },
  actions: {
    hydrateFromStorage() {
      if (typeof localStorage === 'undefined') return
      const t = localStorage.getItem(TOKEN_KEY)
      this.token = t
    },

    persistToken(token: string | null) {
      if (typeof localStorage === 'undefined') return
      if (token) localStorage.setItem(TOKEN_KEY, token)
      else localStorage.removeItem(TOKEN_KEY)
    },

    setSession(token: string, user: AuthUser) {
      this.token = token
      this.user = user
      this.persistToken(token)
    },

    clearSession() {
      this.token = null
      this.user = null
      this.persistToken(null)
    },

    async initialize() {
      if (this.initialized) return
      this.hydrateFromStorage()
      const t = this.token
      if (t) {
        try {
          const r = await authMe(t)
          if (r.ok && 'user' in r) {
            this.user = r.user
          } else {
            this.clearSession()
          }
        } catch {
          this.clearSession()
        }
      }
      this.initialized = true
    },

    async login(email: string, password: string) {
      const r = await authLogin({ email, password })
      if (!r.ok || !('token' in r)) {
        throw new Error(r.message || 'Sign in failed')
      }
      this.setSession(r.token, r.user)
    },

    async register(email: string, password: string, fullName: string) {
      const r = await authRegister({ email, password, fullName: fullName.trim() || undefined })
      if (!r.ok || !('token' in r)) {
        throw new Error(r.message || 'Could not create account')
      }
      this.setSession(r.token, r.user)
    },

    async logout() {
      const t = this.token
      await authLogout(t)
      this.clearSession()
    },

    async refreshMe() {
      const t = this.token
      if (!t) return
      const r = await authMe(t)
      if (r.ok && 'user' in r) {
        this.user = r.user
      } else {
        this.clearSession()
      }
    },

    async updateProfile(payload: { fullName?: string; email?: string; currentPassword?: string }) {
      const t = this.token
      if (!t) throw new Error('Not signed in')
      const r = await authPatchProfile(t, payload)
      if (!r.ok || !('user' in r)) {
        throw new Error(r.message || 'Could not update profile')
      }
      this.user = r.user
    },

    async changePassword(currentPassword: string, newPassword: string) {
      const t = this.token
      if (!t) throw new Error('Not signed in')
      const r = await authChangePassword(t, { currentPassword, newPassword })
      if (!r.ok) {
        throw new Error(r.message || 'Could not change password')
      }
      this.clearSession()
    },
  },
})
