<script setup lang="ts">
import { Lock } from 'lucide-vue-next'
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdminAuthStore } from '@/admin/stores/adminAuth'

const auth = useAdminAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const error = ref('')
const busy = ref(false)

async function onSubmit() {
  error.value = ''
  busy.value = true
  try {
    await auth.login(email.value.trim(), password.value)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/admin'
    await router.replace(redirect)
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Login failed'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="admin-login-page">
    <form class="admin-login-card" @submit.prevent="onSubmit">
      <p class="admin-login-card__eyebrow">The Minimark</p>
      <h1 class="admin-login-card__brand">Admin sign in</h1>
      <p class="admin-login-card__sub">Manage products, orders, and store data.</p>

      <div class="admin-form">
        <div class="admin-field">
          <label for="admin-email">Email</label>
          <input
            id="admin-email"
            v-model="email"
            type="email"
            required
            autocomplete="username"
            placeholder="you@example.com"
          />
        </div>
        <div class="admin-field">
          <label for="admin-pass">Password</label>
          <input
            id="admin-pass"
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            placeholder="••••••••"
          />
        </div>
        <button type="submit" class="admin-btn admin-btn--block" :disabled="busy">
          <Lock :size="16" :stroke-width="2.5" aria-hidden="true" />
          {{ busy ? 'Signing in…' : 'Sign in' }}
        </button>
        <p v-if="error" class="admin-error" role="alert">{{ error }}</p>
      </div>
    </form>
  </div>
</template>

<style scoped>
.admin-login-card__eyebrow {
  margin: 0 0 0.25rem;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--admin-accent);
}
</style>
