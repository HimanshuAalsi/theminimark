import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import {
  adminLogin,
  adminMe,
  getAdminToken,
  setAdminToken,
  type AdminUser,
} from '@/admin/lib/adminApi'

export const useAdminAuthStore = defineStore('adminAuth', () => {
  const user = ref<AdminUser | null>(null)
  const ready = ref(false)

  const isAuthenticated = computed(() => user.value !== null && user.value.role === 'admin')

  async function initialize() {
    if (ready.value) return
    const token = getAdminToken()
    if (!token) {
      ready.value = true
      return
    }
    try {
      const res = await adminMe()
      if (res.ok && res.user?.role === 'admin') {
        user.value = res.user
      } else {
        setAdminToken(null)
      }
    } catch {
      setAdminToken(null)
    } finally {
      ready.value = true
    }
  }

  async function login(email: string, password: string) {
    const res = await adminLogin(email, password)
    if (!res.ok || !res.user) {
      throw new Error(res.message ?? 'Login failed')
    }
    user.value = res.user
    return res
  }

  function logout() {
    setAdminToken(null)
    user.value = null
  }

  return { user, ready, isAuthenticated, initialize, login, logout }
})
