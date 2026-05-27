<script setup lang="ts">
import { Loader2, ShieldCheck } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import AuthPageShell from '@/components/auth/AuthPageShell.vue'
import { ApiError } from '@/lib/api'
import { authResetPassword } from '@/lib/authApi'

const route = useRoute()
const router = useRouter()

const token = ref('')
const password = ref('')
const password2 = ref('')
const error = ref('')
const busy = ref(false)

const hasToken = computed(() => token.value.trim().length >= 32)

watch(
  () => route.query.token,
  (t) => {
    token.value = typeof t === 'string' ? t : ''
  },
  { immediate: true }
)

async function onSubmit() {
  error.value = ''
  if (password.value !== password2.value) {
    error.value = 'Passwords do not match.'
    return
  }
  if (password.value.length < 8) {
    error.value = 'Password must be at least 8 characters.'
    return
  }
  busy.value = true
  try {
    await authResetPassword({ token: token.value.trim(), password: password.value })
    await router.replace({ path: '/login', query: { reset: '1' } })
  } catch (e) {
    if (e instanceof ApiError) {
      const b = e.body as { message?: unknown } | undefined
      error.value = typeof b?.message === 'string' ? b.message : e.message
    } else if (e instanceof Error) {
      error.value = e.message
    } else {
      error.value = 'Reset failed.'
    }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthPageShell title="Set new password" eyebrow="Reset">
    <p v-if="!hasToken" class="warn">
      Missing or invalid reset link. Request a new one from
      <RouterLink to="/forgot-password">forgot password</RouterLink>.
    </p>
    <form v-else class="form" @submit.prevent="onSubmit">
      <div class="field">
        <label class="label" for="rp-pass">New password</label>
        <input
          id="rp-pass"
          v-model="password"
          type="password"
          class="input"
          autocomplete="new-password"
          required
          minlength="8"
        />
      </div>
      <div class="field">
        <label class="label" for="rp-pass2">Confirm password</label>
        <input
          id="rp-pass2"
          v-model="password2"
          type="password"
          class="input"
          autocomplete="new-password"
          required
        />
      </div>
      <p v-if="error" class="err" role="alert">{{ error }}</p>
      <button type="submit" class="btn tm-press" :disabled="busy">
        <Loader2 v-if="busy" class="spin" :size="18" aria-hidden="true" />
        <ShieldCheck v-else :size="18" :stroke-width="2.25" aria-hidden="true" />
        {{ busy ? 'Saving…' : 'Update password' }}
      </button>
    </form>
    <template #below>
      <p><RouterLink to="/login">Back to sign in</RouterLink></p>
    </template>
  </AuthPageShell>
</template>

<style scoped>
.warn {
  margin: 0;
  font-size: 0.95rem;
  color: var(--color-ink-muted);
  line-height: 1.5;
}

.warn :deep(a) {
  color: var(--color-accent);
  font-weight: 650;
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
