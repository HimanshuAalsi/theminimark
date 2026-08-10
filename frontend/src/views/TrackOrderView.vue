<script setup lang="ts">
import { Loader2, PackageSearch } from 'lucide-vue-next'
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { formatCurrency } from '@/lib/currency'
import { trackOrder, type TrackedOrder } from '@/lib/orders'

const orderIdInput = ref('')
const email = ref('')
const busy = ref(false)
const error = ref('')
const result = ref<TrackedOrder | null>(null)

const fmt = formatCurrency

const STATUS_LABELS: Record<string, string> = {
  pending: 'Order placed — awaiting payment',
  paid: 'Paid — preparing your order',
  processing: 'Processing — being packed',
  shipped: 'Shipped — on the way',
  delivered: 'Delivered',
  cancelled: 'Cancelled',
  refunded: 'Refunded',
}

function statusLabel(status: string): string {
  return STATUS_LABELS[status] ?? status
}

async function onSubmit(e: Event) {
  e.preventDefault()
  error.value = ''
  result.value = null
  const id = Number.parseInt(orderIdInput.value.trim(), 10)
  if (!Number.isFinite(id) || id < 1) {
    error.value = 'Enter a valid order number.'
    return
  }
  if (!email.value.trim()) {
    error.value = 'Enter the email used at checkout.'
    return
  }
  busy.value = true
  try {
    const res = await trackOrder(id, email.value.trim())
    if (!res.ok || !res.order) {
      error.value = res.message || 'Order not found. Check your order number and email.'
      return
    }
    result.value = res.order
  } catch {
    error.value = 'Could not look up your order. Try again in a moment.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="track tm-section tm-animate-in">
    <div class="tm-container track__inner">
      <header class="track__head">
        <p class="track__eyebrow">
          <PackageSearch :size="18" aria-hidden="true" />
          Order status
        </p>
        <h1 class="track__title">Track your order</h1>
        <p class="track__lead">
          Enter your order number and the email you used at checkout. We'll show the latest status.
        </p>
      </header>

      <form class="track__form" @submit="onSubmit">
        <div class="track__fields">
          <label class="track__label">
            Order number
            <input
              v-model="orderIdInput"
              type="text"
              inputmode="numeric"
              class="track__input"
              placeholder="e.g. 1042"
              autocomplete="off"
            />
          </label>
          <label class="track__label">
            Email
            <input
              v-model="email"
              type="email"
              class="track__input"
              placeholder="you@example.com"
              autocomplete="email"
            />
          </label>
        </div>
        <button type="submit" class="track__submit tm-press" :disabled="busy">
          <Loader2 v-if="busy" :size="18" class="track__spin" aria-hidden="true" />
          {{ busy ? 'Looking up…' : 'Track order' }}
        </button>
        <p v-if="error" class="track__error" role="alert">{{ error }}</p>
      </form>

      <section v-if="result" class="track__result" aria-live="polite">
        <div class="track__result-head">
          <h2 class="track__result-title">Order #{{ result.id }}</h2>
          <p class="track__status">{{ statusLabel(result.status) }}</p>
        </div>
        <dl class="track__meta">
          <div>
            <dt>Placed</dt>
            <dd>{{ new Date(result.createdAt).toLocaleString() }}</dd>
          </div>
          <div>
            <dt>Total</dt>
            <dd>{{ fmt(result.subtotal) }}</dd>
          </div>
          <div>
            <dt>Items</dt>
            <dd>{{ result.itemCount }}</dd>
          </div>
        </dl>
        <ul v-if="result.lines.length" class="track__lines">
          <li v-for="line in result.lines" :key="line.id">
            {{ line.quantity }} × {{ line.name }}
          </li>
        </ul>
        <p class="track__help">
          Questions?
          <RouterLink to="/policies/shipping">Shipping policy</RouterLink>
          ·
          <RouterLink to="/policies/refund">Returns</RouterLink>
        </p>
      </section>
    </div>
  </div>
</template>

<style scoped>
.track__inner {
  max-width: 36rem;
}

.track__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0 0 0.5rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.track__title {
  margin: 0 0 0.5rem;
  font-family: var(--font-display);
  font-size: clamp(1.75rem, 4vw, 2.25rem);
}

.track__lead {
  margin: 0 0 1.5rem;
  color: var(--color-ink-muted);
  line-height: 1.55;
}

.track__fields {
  display: grid;
  gap: 1rem;
  margin-bottom: 1rem;
}

.track__label {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.88rem;
  font-weight: 600;
}

.track__input {
  padding: 0.65rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font: inherit;
  background: var(--color-surface);
}

.track__submit {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  min-height: var(--tap-min);
  padding: 0 1.35rem;
  border: none;
  border-radius: 999px;
  background: var(--tm-gradient);
  color: #fff;
  font-weight: 700;
  cursor: pointer;
}

.track__submit:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.track__spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.track__error {
  margin: 0.75rem 0 0;
  color: var(--color-sale);
  font-size: 0.9rem;
}

.track__result {
  margin-top: 2rem;
  padding: 1.25rem 1.35rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
}

.track__result-title {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.35rem;
}

.track__status {
  margin: 0.35rem 0 0;
  font-weight: 700;
  color: var(--color-accent);
}

.track__meta {
  display: grid;
  gap: 0.75rem;
  margin: 1.25rem 0;
}

.track__meta dt {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--color-ink-faint);
}

.track__meta dd {
  margin: 0.15rem 0 0;
}

.track__lines {
  margin: 0;
  padding-left: 1.1rem;
  font-size: 0.92rem;
  color: var(--color-ink-muted);
}

.track__help {
  margin: 1.25rem 0 0;
  font-size: 0.88rem;
  color: var(--color-ink-muted);
}
</style>
