<script setup lang="ts">
import { ArrowLeft, CheckCircle2, Loader2, Lock, Package } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useCartUiStore } from '@/stores/cartUi'
import { ApiError } from '@/lib/api'
import { formatCurrency, STORE_CURRENCY } from '@/lib/currency'
import { submitOrder } from '@/lib/orders'
import {
  fetchRazorpayConfig,
  openRazorpayCheckout,
  startRazorpayCheckout,
  verifyRazorpayPayment,
} from '@/lib/payments'

const cart = useCartStore()
const cartUi = useCartUiStore()
const authStore = useAuthStore()
const router = useRouter()

const email = ref('')
const fullName = ref('')
const address = ref('')
const city = ref('')
const phone = ref('')
const notes = ref('')
const busy = ref(false)
const error = ref('')
const done = ref(false)
const paidOnline = ref(false)
const orderId = ref<number | null>(null)
const razorpayEnabled = ref(false)
const razorpaySetupHint = ref<string | null>(null)

const fmt = formatCurrency

const subtotal = computed(() => cart.subtotal)

onMounted(async () => {
  if (cart.lines.length === 0) {
    cartUi.open()
    router.replace('/shop')
    return
  }
  await authStore.initialize()
  try {
    const payCfg = await fetchRazorpayConfig()
    razorpayEnabled.value = payCfg.enabled
    razorpaySetupHint.value = payCfg.setupHint ?? null
  } catch {
    razorpayEnabled.value = false
    razorpaySetupHint.value = null
  }
  if (authStore.user) {
    if (!email.value.trim()) {
      email.value = authStore.user.email
    }
    if (!fullName.value.trim() && authStore.user.fullName) {
      fullName.value = authStore.user.fullName
    }
  }
})

function buildOrderPayload(em: string) {
  const extra = [address.value.trim(), city.value.trim()].filter(Boolean).join(' · ')
  const noteParts = [notes.value.trim(), extra ? `Ship: ${extra}` : ''].filter(Boolean)
  return {
    customerEmail: em,
    customerName: fullName.value.trim() || undefined,
    currency: STORE_CURRENCY,
    lines: cart.lines.map((l) => ({
      productId: l.productId,
      name: l.name,
      unitPrice: l.unitPrice,
      quantity: l.quantity,
    })),
    notes: noteParts.length ? noteParts.join('\n') : undefined,
  }
}

function checkoutErrorMessage(e: unknown, fallback: string): string {
  if (e instanceof ApiError) {
    return typeof e.body === 'object' && e.body !== null && 'message' in e.body
      ? String((e.body as { message: unknown }).message)
      : fallback
  }
  if (e instanceof Error && e.message) {
    return e.message
  }
  return 'Network error. Try again when you are online.'
}

async function onSubmit() {
  error.value = ''
  const em = email.value.trim()
  if (!em) {
    error.value = 'Email is required.'
    return
  }
  if (busy.value) return
  busy.value = true
  const payload = buildOrderPayload(em)

  try {
    if (razorpayEnabled.value) {
      const session = await startRazorpayCheckout(payload)
      if (!session.ok) {
        error.value = session.message || 'Could not start payment.'
        return
      }
      await openRazorpayCheckout({
        session,
        phone: phone.value.trim(),
        onDismiss: () => {
          busy.value = false
        },
        onSuccess: async (response) => {
          try {
            const verified = await verifyRazorpayPayment({
              orderId: session.orderId!,
              razorpayOrderId: response.razorpay_order_id,
              razorpayPaymentId: response.razorpay_payment_id,
              razorpaySignature: response.razorpay_signature,
            })
            if (verified.ok) {
              orderId.value = verified.orderId ?? session.orderId ?? null
              paidOnline.value = true
              done.value = true
              cart.clear()
            } else {
              error.value = verified.message || 'Payment could not be verified.'
            }
          } catch (e) {
            error.value = checkoutErrorMessage(e, 'Payment verification failed.')
          } finally {
            busy.value = false
          }
        },
      })
      return
    }

    const res = await submitOrder(payload)
    if (res.ok) {
      orderId.value = res.orderId ?? null
      paidOnline.value = false
      done.value = true
      cart.clear()
    } else {
      error.value = res.message || 'Order could not be placed.'
    }
  } catch (e) {
    error.value = checkoutErrorMessage(
      e,
      razorpayEnabled.value
        ? 'Payment could not be started. Is the API configured?'
        : 'Order could not be placed. Is the API running?',
    )
    busy.value = false
  } finally {
    if (!razorpayEnabled.value) {
      busy.value = false
    }
  }
}
</script>

<template>
  <div class="checkout tm-section tm-animate-in">
    <div class="tm-container checkout__grid">
      <template v-if="done">
        <div class="checkout__card checkout__card--wide checkout__card--success tm-hover-lift">
          <div class="checkout__success-icon" aria-hidden="true">
            <CheckCircle2 :size="44" :stroke-width="1.75" />
          </div>
          <p class="checkout__eyebrow">Thank you</p>
          <h1 class="checkout__title">We received your order</h1>
          <p v-if="paidOnline && orderId != null" class="checkout__lead">
            Order <strong>#{{ orderId }}</strong> is paid. We will email you shipping updates soon.
          </p>
          <p v-else-if="orderId != null" class="checkout__lead">
            Reference <strong>#{{ orderId }}</strong> — we will follow up by email with payment and shipping
            details.
          </p>
          <p v-else class="checkout__lead">We will follow up by email shortly.</p>
          <RouterLink to="/shop" class="checkout__btn tm-press">Continue shopping</RouterLink>
        </div>
      </template>

      <template v-else>
        <div class="checkout__main">
          <header class="checkout__head">
            <p class="checkout__eyebrow">Checkout</p>
            <h1 class="checkout__title">Contact &amp; shipping</h1>
            <p v-if="razorpayEnabled" class="checkout__lead">
              Pay securely with UPI, cards, netbanking, or wallets via Razorpay (INR).
            </p>
            <p v-else class="checkout__lead">
              Your order is saved for fulfilment — we will contact you by email for payment (online checkout can be
              enabled in store settings).
            </p>
          </header>

          <form class="checkout__form" @submit.prevent="onSubmit">
            <div class="checkout__field">
              <label class="checkout__label" for="co-email">Email <span aria-hidden="true">*</span></label>
              <input
                id="co-email"
                v-model="email"
                type="email"
                class="checkout__input"
                required
                autocomplete="email"
                placeholder="you@example.com"
              />
            </div>
            <div class="checkout__field">
              <label class="checkout__label" for="co-name">Full name</label>
              <input
                id="co-name"
                v-model="fullName"
                type="text"
                class="checkout__input"
                autocomplete="name"
                placeholder="Name on the order"
              />
            </div>
            <div class="checkout__field">
              <label class="checkout__label" for="co-addr">Street address</label>
              <input
                id="co-addr"
                v-model="address"
                type="text"
                class="checkout__input"
                autocomplete="street-address"
                placeholder="Optional for now"
              />
            </div>
            <div class="checkout__field">
              <label class="checkout__label" for="co-city">City / region</label>
              <input
                id="co-city"
                v-model="city"
                type="text"
                class="checkout__input"
                autocomplete="address-level2"
              />
            </div>
            <div v-if="razorpayEnabled" class="checkout__field">
              <label class="checkout__label" for="co-phone">Mobile (for UPI)</label>
              <input
                id="co-phone"
                v-model="phone"
                type="tel"
                class="checkout__input"
                autocomplete="tel"
                inputmode="numeric"
                placeholder="10-digit number"
              />
            </div>
            <div class="checkout__field">
              <label class="checkout__label" for="co-notes">Order notes</label>
              <textarea
                id="co-notes"
                v-model="notes"
                class="checkout__textarea"
                rows="3"
                placeholder="Gift message, delivery instructions…"
              />
            </div>

            <p v-if="razorpaySetupHint && !razorpayEnabled" class="checkout__error" role="status">
              {{ razorpaySetupHint }}
            </p>
            <p v-if="error" class="checkout__error" role="alert">{{ error }}</p>

            <div class="checkout__actions">
              <button
                type="button"
                class="checkout__back tm-press"
                @click="cartUi.open(); router.back()"
              >
                <ArrowLeft :size="17" :stroke-width="2.25" aria-hidden="true" />
                Back to cart
              </button>
              <button type="submit" class="checkout__submit tm-press" :disabled="busy">
                <Loader2 v-if="busy" class="checkout__spin" :size="18" aria-hidden="true" />
                {{
                  busy
                    ? razorpayEnabled
                      ? 'Opening payment…'
                      : 'Submitting…'
                    : razorpayEnabled
                      ? 'Pay with Razorpay'
                      : 'Place order'
                }}
              </button>
            </div>
          </form>
        </div>

        <aside class="checkout__aside" aria-label="Order summary">
          <div class="checkout__card tm-hover-lift">
            <h2 class="checkout__sum-title">
              <Package class="checkout__sum-ico" :size="20" :stroke-width="2" aria-hidden="true" />
              Summary
            </h2>
            <ul class="checkout__lines">
              <li v-for="line in cart.lines" :key="String(line.productId)" class="checkout__line">
                <span class="checkout__line-name">{{ line.name }} × {{ line.quantity }}</span>
                <span class="checkout__line-price">{{ fmt(line.unitPrice * line.quantity) }}</span>
              </li>
            </ul>
            <p class="checkout__total">
              Subtotal <strong>{{ fmt(subtotal) }}</strong>
            </p>
            <p class="checkout__fine">
              <Lock class="checkout__fine-ico" :size="14" :stroke-width="2" aria-hidden="true" />
              <template v-if="razorpayEnabled">Secure payment by Razorpay. Shipping updates by email.</template>
              <template v-else>Taxes and shipping confirmed by email.</template>
            </p>
          </div>
        </aside>
      </template>
    </div>
  </div>
</template>

<style scoped>
.checkout {
  padding-top: 1.5rem;
  padding-bottom: 3.5rem;
}

.checkout__grid {
  display: grid;
  gap: 2rem;
  align-items: start;
}

@media (min-width: 900px) {
  .checkout__grid {
    grid-template-columns: minmax(0, 1fr) minmax(280px, 340px);
  }
}

.checkout__main {
  min-width: 0;
}

.checkout__head {
  margin-bottom: 1.5rem;
}

.checkout__eyebrow {
  margin: 0 0 0.35rem;
  font-size: 0.8rem;
  font-weight: 650;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.checkout__title {
  margin: 0 0 0.5rem;
  font-family: var(--font-display);
  font-size: clamp(1.45rem, 2.5vw, 1.85rem);
  font-weight: 500;
  color: var(--color-ink);
}

.checkout__lead {
  margin: 0;
  font-size: 0.95rem;
  color: var(--color-ink-muted);
  line-height: var(--leading);
  max-width: 40rem;
}

.checkout__form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-width: 32rem;
}

.checkout__field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.checkout__label {
  font-size: 0.875rem;
  font-weight: 650;
  color: var(--color-ink);
}

.checkout__input,
.checkout__textarea {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  padding: 0.65rem 0.85rem;
  font: inherit;
  font-size: 1rem;
  background: var(--color-surface-elevated);
  color: var(--color-ink);
  min-height: var(--tap-min);
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.checkout__input:hover,
.checkout__textarea:hover {
  border-color: rgba(45, 92, 82, 0.22);
}

.checkout__textarea {
  min-height: 5rem;
  resize: vertical;
}

.checkout__input:focus-visible,
.checkout__textarea:focus-visible {
  outline: 2px solid var(--color-accent);
  outline-offset: 2px;
}

.checkout__error {
  margin: 0;
  font-size: 0.9rem;
  color: var(--color-sale);
}

.checkout__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
  margin-top: 0.5rem;
}

.checkout__back {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: none;
  background: transparent;
  font: inherit;
  cursor: pointer;
  font-weight: 650;
  color: var(--color-ink-muted);
  text-decoration: none;
  padding: 0.35rem 0.15rem;
  border-radius: var(--radius-sm);
  transition:
    color 0.2s ease,
    background 0.2s ease;
}

.checkout__back:hover {
  color: var(--color-accent);
  background: var(--color-accent-soft);
}

.checkout__submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border: none;
  border-radius: 999px;
  padding: 0 1.75rem;
  min-height: var(--tap-min);
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  color: #fff;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  font-family: inherit;
  box-shadow: 0 4px 16px rgba(45, 92, 82, 0.28);
  transition:
    filter 0.2s ease,
    transform 0.15s ease;
}

.checkout__submit:hover:not(:disabled) {
  filter: brightness(1.06);
  transform: translateY(-1px);
}

.checkout__spin {
  animation: checkout-spin 0.85s linear infinite;
}

@keyframes checkout-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .checkout__spin {
    animation: none;
  }
}

.checkout__submit:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.checkout__aside {
  position: sticky;
  top: calc(var(--header-h) + 1rem);
}

.checkout__card {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 1.25rem 1.35rem;
  background: var(--color-surface-elevated);
  box-shadow: var(--shadow-card);
}

.checkout__card--wide {
  max-width: 36rem;
}

.checkout__card--success {
  text-align: center;
  padding: 2rem 1.5rem;
}

.checkout__success-icon {
  display: inline-flex;
  margin-bottom: 1rem;
  color: var(--color-accent);
}

.checkout__sum-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 1rem;
  font-family: var(--font-display);
  font-size: 1.15rem;
  font-weight: 500;
  color: var(--color-ink);
}

.checkout__sum-ico {
  flex-shrink: 0;
  color: var(--color-accent);
}

.checkout__lines {
  list-style: none;
  margin: 0 0 1rem;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.checkout__line {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  font-size: 0.9rem;
  color: var(--color-ink-muted);
}

.checkout__line-name {
  min-width: 0;
}

.checkout__line-price {
  flex-shrink: 0;
  font-weight: 600;
  color: var(--color-ink);
}

.checkout__total {
  margin: 0 0 0.5rem;
  padding-top: 0.85rem;
  border-top: 1px solid var(--color-border);
  font-size: 1rem;
  color: var(--color-ink-muted);
}

.checkout__total strong {
  color: var(--color-ink);
}

.checkout__fine {
  display: flex;
  align-items: flex-start;
  gap: 0.35rem;
  margin: 0;
  font-size: 0.8rem;
  color: var(--color-ink-faint);
  line-height: 1.45;
}

.checkout__fine-ico {
  flex-shrink: 0;
  margin-top: 0.12rem;
  opacity: 0.75;
}

.checkout__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-top: 1.25rem;
  min-height: var(--tap-min);
  padding: 0 1.5rem;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  color: #fff !important;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 4px 16px rgba(45, 92, 82, 0.28);
  transition:
    filter 0.2s ease,
    transform 0.15s ease;
}

.checkout__btn:hover {
  filter: brightness(1.06);
  transform: translateY(-1px);
}
</style>
