<script setup lang="ts">
import { KeyRound, Loader2, LogOut, Save, User } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import SiteLogo from '@/components/layout/SiteLogo.vue'
import { ApiError } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const fullName = ref('')
const email = ref('')
const currentPwEmail = ref('')
const profileError = ref('')
const profileOk = ref('')
const profileBusy = ref(false)

const curPass = ref('')
const newPass = ref('')
const newPass2 = ref('')
const passError = ref('')
const passBusy = ref(false)

const logoutBusy = ref(false)

const user = computed(() => auth.user)

watch(
  user,
  (u) => {
    if (u) {
      fullName.value = u.fullName
      email.value = u.email
    }
  },
  { immediate: true }
)

async function saveProfile() {
  profileError.value = ''
  profileOk.value = ''
  profileBusy.value = true
  try {
    const payload: { fullName: string; email?: string; currentPassword?: string } = {
      fullName: fullName.value.trim(),
    }
    const trimmedEmail = email.value.trim()
    if (trimmedEmail && trimmedEmail !== user.value?.email) {
      payload.email = trimmedEmail
      payload.currentPassword = currentPwEmail.value
    }
    await auth.updateProfile(payload)
    currentPwEmail.value = ''
    profileOk.value = 'Profile saved.'
  } catch (e) {
    if (e instanceof ApiError) {
      const b = e.body as { message?: unknown } | undefined
      profileError.value = typeof b?.message === 'string' ? b.message : e.message
    } else if (e instanceof Error) {
      profileError.value = e.message
    } else {
      profileError.value = 'Could not save.'
    }
  } finally {
    profileBusy.value = false
  }
}

async function changePassword() {
  passError.value = ''
  if (newPass.value !== newPass2.value) {
    passError.value = 'New passwords do not match.'
    return
  }
  if (newPass.value.length < 8) {
    passError.value = 'Password must be at least 8 characters.'
    return
  }
  passBusy.value = true
  try {
    await auth.changePassword(curPass.value, newPass.value)
    await router.replace({ path: '/login', query: { pwd: '1' } })
  } catch (e) {
    if (e instanceof ApiError) {
      const b = e.body as { message?: unknown } | undefined
      passError.value = typeof b?.message === 'string' ? b.message : e.message
    } else if (e instanceof Error) {
      passError.value = e.message
    } else {
      passError.value = 'Could not change password.'
    }
  } finally {
    passBusy.value = false
  }
}

async function doLogout() {
  logoutBusy.value = true
  try {
    await auth.logout()
    await router.replace('/')
  } finally {
    logoutBusy.value = false
  }
}
</script>

<template>
  <div v-if="user" class="profile tm-section tm-animate-in">
    <div class="tm-container">
      <header class="profile__head">
        <SiteLogo size="auth" linked />
        <p class="profile__eyebrow">Account</p>
        <h1 class="profile__title">Hi, {{ user.fullName || user.email }}</h1>
        <p class="profile__lead">Update your details, change your password, or sign out on this device.</p>
      </header>

      <div class="profile__grid">
        <section class="card tm-hover-lift">
          <h2 class="card__h">
            <User :size="20" :stroke-width="2" class="card__ico" aria-hidden="true" />
            Profile
          </h2>
          <form class="form" @submit.prevent="saveProfile">
            <div class="field">
              <label class="label" for="pf-name">Full name</label>
              <input id="pf-name" v-model="fullName" type="text" class="input" autocomplete="name" />
            </div>
            <div class="field">
              <label class="label" for="pf-email">Email</label>
              <input id="pf-email" v-model="email" type="email" class="input" autocomplete="email" />
              <p v-if="email.trim() !== user.email" class="hint">
                Enter your current password to confirm this email change.
              </p>
            </div>
            <div v-if="email.trim() !== user.email" class="field">
              <label class="label" for="pf-cur">Current password</label>
              <input
                id="pf-cur"
                v-model="currentPwEmail"
                type="password"
                class="input"
                autocomplete="current-password"
              />
            </div>
            <p v-if="profileError" class="err" role="alert">{{ profileError }}</p>
            <p v-if="profileOk" class="ok" role="status">{{ profileOk }}</p>
            <button type="submit" class="btn btn--secondary tm-press" :disabled="profileBusy">
              <Loader2 v-if="profileBusy" class="spin" :size="18" aria-hidden="true" />
              <Save v-else :size="18" :stroke-width="2.25" aria-hidden="true" />
              {{ profileBusy ? 'Saving…' : 'Save profile' }}
            </button>
          </form>
        </section>

        <section class="card tm-hover-lift">
          <h2 class="card__h">
            <KeyRound :size="20" :stroke-width="2" class="card__ico" aria-hidden="true" />
            Password
          </h2>
          <p class="card__p">Changing your password signs you out everywhere for security.</p>
          <form class="form" @submit.prevent="changePassword">
            <div class="field">
              <label class="label" for="pw-cur">Current password</label>
              <input id="pw-cur" v-model="curPass" type="password" class="input" autocomplete="current-password" />
            </div>
            <div class="field">
              <label class="label" for="pw-new">New password</label>
              <input id="pw-new" v-model="newPass" type="password" class="input" autocomplete="new-password" />
            </div>
            <div class="field">
              <label class="label" for="pw-new2">Confirm new password</label>
              <input id="pw-new2" v-model="newPass2" type="password" class="input" autocomplete="new-password" />
            </div>
            <p v-if="passError" class="err" role="alert">{{ passError }}</p>
            <button type="submit" class="btn tm-press" :disabled="passBusy">
              <Loader2 v-if="passBusy" class="spin" :size="18" aria-hidden="true" />
              {{ passBusy ? 'Updating…' : 'Change password' }}
            </button>
          </form>
        </section>

        <section class="card card--side tm-hover-lift">
          <h2 class="card__h">Session</h2>
          <p class="card__p">You stay signed in on this browser until you log out or change your password.</p>
          <button type="button" class="btn btn--ghost tm-press" :disabled="logoutBusy" @click="doLogout">
            <Loader2 v-if="logoutBusy" class="spin" :size="18" aria-hidden="true" />
            <LogOut v-else :size="18" :stroke-width="2.25" aria-hidden="true" />
            {{ logoutBusy ? 'Signing out…' : 'Sign out' }}
          </button>
          <p class="fine">
            <RouterLink to="/shop">Continue shopping</RouterLink>
          </p>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>
.profile {
  padding-top: 1.5rem;
  padding-bottom: 3.5rem;
}

.profile__head {
  margin-bottom: 1.75rem;
  max-width: 36rem;
}

.profile__eyebrow {
  margin: 0 0 0.35rem;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.profile__title {
  margin: 0 0 0.5rem;
  font-family: var(--font-display);
  font-size: clamp(1.45rem, 2.5vw, 1.85rem);
  font-weight: 500;
  color: var(--color-ink);
}

.profile__lead {
  margin: 0;
  color: var(--color-ink-muted);
  line-height: 1.55;
}

.profile__grid {
  display: grid;
  gap: 1.25rem;
  align-items: start;
}

@media (min-width: 900px) {
  .profile__grid {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(240px, 280px);
  }

  .card--side {
    grid-column: 3;
    grid-row: 1 / span 2;
  }
}

.card {
  padding: 1.35rem 1.25rem;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  box-shadow: var(--shadow-card);
}

.card__h {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 1rem;
  font-family: var(--font-display);
  font-size: 1.1rem;
  font-weight: 500;
  color: var(--color-ink);
}

.card__ico {
  flex-shrink: 0;
  color: var(--color-accent);
}

.card__p {
  margin: 0 0 1rem;
  font-size: 0.9rem;
  color: var(--color-ink-muted);
  line-height: 1.5;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
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
  margin: 0;
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
}

.input:focus-visible {
  outline: none;
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}

.err {
  margin: 0;
  font-size: 0.88rem;
  color: var(--color-sale);
}

.ok {
  margin: 0;
  font-size: 0.88rem;
  color: var(--color-accent);
  font-weight: 600;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  min-height: var(--tap-min);
  border: none;
  border-radius: 999px;
  padding: 0 1.25rem;
  font-weight: 700;
  font-size: 0.95rem;
  font-family: inherit;
  cursor: pointer;
  align-self: flex-start;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  color: #fff;
  box-shadow: 0 4px 16px rgba(45, 92, 82, 0.28);
}

.btn--secondary {
  background: var(--color-surface);
  color: var(--color-ink);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
}

.btn--secondary:hover:not(:disabled) {
  border-color: rgba(45, 92, 82, 0.35);
  background: var(--color-accent-soft);
}

.btn--ghost {
  background: transparent;
  color: var(--color-ink-muted);
  border: 1px solid var(--color-border);
  box-shadow: none;
}

.btn--ghost:hover:not(:disabled) {
  color: var(--color-sale);
  border-color: rgba(196, 74, 74, 0.35);
  background: rgba(196, 74, 74, 0.06);
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

.fine {
  margin: 1rem 0 0;
  font-size: 0.88rem;
}

.fine :deep(a) {
  font-weight: 650;
  color: var(--color-accent);
  text-decoration: none;
}

.fine :deep(a:hover) {
  text-decoration: underline;
}
</style>
