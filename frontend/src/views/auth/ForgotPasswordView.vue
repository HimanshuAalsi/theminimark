<script setup lang="ts">
import { KeyRound, Loader2 } from 'lucide-vue-next'
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import AuthPageShell from '@/components/auth/AuthPageShell.vue'
import { authForgotPassword } from '@/lib/authApi'

const email = ref('')
const busy = ref(false)
const done = ref(false)
const debugToken = ref<string | null>(null)

async function onSubmit() {
  busy.value = true
  debugToken.value = null
  try {
    const r = await authForgotPassword(email.value.trim())
    done.value = true
    if (typeof r.debugResetToken === 'string' && r.debugResetToken) {
      debugToken.value = r.debugResetToken
    }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <AuthPageShell title="Forgot password" eyebrow="Reset">
    <template v-if="!done">
      <p class="lead">
        Enter the email you used to register. If it matches an account, you can set a new password
        using the reset link (or the developer token below when enabled on the server).
      </p>
      <form class="form" @submit.prevent="onSubmit">
        <div class="field">
          <label class="label" for="fp-email">Email</label>
          <input id="fp-email" v-model="email" type="email" class="input" required autocomplete="email" />
        </div>
        <button type="submit" class="btn tm-press" :disabled="busy">
          <Loader2 v-if="busy" class="spin" :size="18" aria-hidden="true" />
          <KeyRound v-else :size="18" :stroke-width="2.25" aria-hidden="true" />
          {{ busy ? 'Sending…' : 'Send reset' }}
        </button>
      </form>
    </template>
    <template v-else>
      <p class="lead ok">
        If an account exists for that address, reset instructions have been recorded. Check your inbox
        when email delivery is configured on the server.
      </p>
      <div v-if="debugToken" class="devbox">
        <p class="devbox__title">Local / dev token (never enable in production)</p>
        <code class="devbox__code">{{ debugToken }}</code>
        <RouterLink class="devbox__link" :to="{ path: '/reset-password', query: { token: debugToken } }">
          Open reset page with this token
        </RouterLink>
      </div>
    </template>
    <template #below>
      <p><RouterLink to="/login">Back to sign in</RouterLink></p>
    </template>
  </AuthPageShell>
</template>

<style scoped>
.lead {
  margin: 0;
  font-size: 0.95rem;
  color: var(--color-ink-muted);
  line-height: 1.55;
}

.lead.ok {
  margin-bottom: 0.5rem;
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

.devbox {
  margin-top: 1rem;
  padding: 1rem;
  border-radius: var(--radius-sm);
  border: 1px dashed var(--color-border);
  background: var(--color-page);
}

.devbox__title {
  margin: 0 0 0.5rem;
  font-size: 0.8rem;
  font-weight: 650;
  color: var(--color-ink-muted);
}

.devbox__code {
  display: block;
  word-break: break-all;
  font-size: 0.75rem;
  line-height: 1.4;
  margin-bottom: 0.75rem;
}

.devbox__link {
  font-weight: 650;
  font-size: 0.9rem;
  color: var(--color-accent);
  text-decoration: none;
}

.devbox__link:hover {
  text-decoration: underline;
}
</style>
