<script setup lang="ts">
import { Loader2, LogIn } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import AuthPageShell from '@/components/auth/AuthPageShell.vue'
import { ApiError } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')
const error = ref('')
const busy = ref(false)

const flash = computed(() => {
  if (route.query.reset === '1') return 'Your password was updated. Sign in with the new one.'
  if (route.query.pwd === '1') return 'Password changed. Please sign in again.'
  return ''
})

async function onSubmit() {
  error.value = ''
  busy.value = true
  try {
    await auth.login(email.value, password.value)
    const redir = typeof route.query.redirect === 'string' ? route.query.redirect : ''
    await router.replace(redir && redir.startsWith('/') ? redir : '/account')
  } catch (e) {
    if (e instanceof ApiError) {
      const b = e.body as { message?: unknown } | undefined
      error.value = typeof b?.message === 'string' ? b.message : e.message
    } else if (e instanceof Error) {
      error.value = e.message
    } else {
      error.value = 'Something went wrong.'
    }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthPageShell title="Sign in" eyebrow="Account">
    <p v-if="flash" class="flash" role="status">{{ flash }}</p>
    <form class="form" @submit.prevent="onSubmit">
      <div class="field">
        <label class="label" for="login-email">Email</label>
        <input
          id="login-email"
          v-model="email"
          type="email"
          class="input"
          autocomplete="email"
          required
        />
      </div>
      <div class="field">
        <label class="label" for="login-password">Password</label>
        <input
          id="login-password"
          v-model="password"
          type="password"
          class="input"
          autocomplete="current-password"
          required
        />
      </div>
      <p v-if="error" class="err" role="alert">{{ error }}</p>
      <button type="submit" class="btn tm-press" :disabled="busy">
        <Loader2 v-if="busy" class="spin" :size="18" aria-hidden="true" />
        <LogIn v-else :size="18" :stroke-width="2.25" aria-hidden="true" />
        {{ busy ? 'Signing in…' : 'Sign in' }}
      </button>
    </form>
    <template #below>
      <p>
        <RouterLink to="/forgot-password">Forgot password?</RouterLink>
        ·
        <RouterLink to="/register">Create an account</RouterLink>
      </p>
    </template>
  </AuthPageShell>
</template>

<style scoped>
.flash {
  margin: 0 0 0.25rem;
  padding: 0.65rem 0.85rem;
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  line-height: 1.45;
  color: var(--color-ink);
  background: var(--color-accent-soft);
  border: 1px solid rgba(45, 92, 82, 0.2);
}

.form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.label {
  font-size: 0.875rem;
  font-weight: 650;
  color: var(--color-ink);
}

.input {
  min-height: var(--tap-min);
  padding: 0 0.85rem;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  font: inherit;
  font-size: 1rem;
  color: var(--color-ink);
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.input:focus-visible {
  outline: none;
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}

.err {
  margin: 0;
  font-size: 0.9rem;
  color: var(--color-sale);
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  min-height: var(--tap-min);
  border: none;
  border-radius: 999px;
  padding: 0 1.35rem;
  font-weight: 700;
  font-size: 1rem;
  font-family: inherit;
  cursor: pointer;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  color: #fff;
  box-shadow: 0 4px 16px rgba(45, 92, 82, 0.28);
  transition:
    filter 0.2s ease,
    opacity 0.2s ease;
}

.btn:hover:not(:disabled) {
  filter: brightness(1.06);
}

.btn:disabled {
  opacity: 0.75;
  cursor: not-allowed;
}

.spin {
  animation: spin 0.85s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .spin {
    animation: none;
  }
}
</style>
