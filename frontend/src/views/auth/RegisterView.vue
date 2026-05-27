<script setup lang="ts">
import { Loader2, UserPlus } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import AuthPageShell from '@/components/auth/AuthPageShell.vue'
import { ApiError, apiUrl, getApiBaseUrl } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const fullName = ref('')
const email = ref('')
const password = ref('')
const password2 = ref('')
const error = ref('')
const busy = ref(false)

const installStatusUrl = computed(() => {
  const p = getApiBaseUrl() ? '/v1/auth/install-status' : '/api/v1/auth/install-status'
  return apiUrl(p)
})

const showDbHelp = computed(() =>
  /account tables|config db\.name|DATABASE\(\)/i.test(error.value)
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
    await auth.register(email.value, password.value, fullName.value)
    await router.replace('/account')
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
  <AuthPageShell title="Create account" eyebrow="Join">
    <form class="form" @submit.prevent="onSubmit">
      <div class="field">
        <label class="label" for="reg-name">Full name</label>
        <input
          id="reg-name"
          v-model="fullName"
          type="text"
          class="input"
          autocomplete="name"
        />
      </div>
      <div class="field">
        <label class="label" for="reg-email">Email</label>
        <input
          id="reg-email"
          v-model="email"
          type="email"
          class="input"
          autocomplete="email"
          required
        />
      </div>
      <div class="field">
        <label class="label" for="reg-password">Password</label>
        <input
          id="reg-password"
          v-model="password"
          type="password"
          class="input"
          autocomplete="new-password"
          required
          minlength="8"
        />
        <span class="hint">At least 8 characters.</span>
      </div>
      <div class="field">
        <label class="label" for="reg-password2">Confirm password</label>
        <input
          id="reg-password2"
          v-model="password2"
          type="password"
          class="input"
          autocomplete="new-password"
          required
        />
      </div>
      <p v-if="error" class="err" role="alert">{{ error }}</p>
      <p v-if="showDbHelp" class="diag">
        <a :href="installStatusUrl" target="_blank" rel="noopener noreferrer">Open install-status (JSON)</a>
        — confirms which database this API uses. Then run
        <code class="diag__code">php backend/api/tools/apply-auth-migration.php</code>
        from the project root (same machine as the API).
      </p>
      <button type="submit" class="btn tm-press" :disabled="busy">
        <Loader2 v-if="busy" class="spin" :size="18" aria-hidden="true" />
        <UserPlus v-else :size="18" :stroke-width="2.25" aria-hidden="true" />
        {{ busy ? 'Creating…' : 'Create account' }}
      </button>
    </form>
    <template #below>
      <p>Already have an account? <RouterLink to="/login">Sign in</RouterLink></p>
    </template>
  </AuthPageShell>
</template>

<style scoped>
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

.hint {
  font-size: 0.8rem;
  color: var(--color-ink-faint);
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

.diag {
  margin: 0;
  font-size: 0.85rem;
  line-height: 1.5;
  color: var(--color-ink-muted);
}

.diag a {
  font-weight: 650;
  color: var(--color-accent);
}

.diag__code {
  font-size: 0.78rem;
  padding: 0.15rem 0.35rem;
  border-radius: 4px;
  background: var(--color-page);
  border: 1px solid var(--color-border);
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
