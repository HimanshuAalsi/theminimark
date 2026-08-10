<script setup lang="ts">
import { ArrowLeft, CheckCircle2, Loader2, Lock, Package } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useCartUiStore } from '@/stores/cartUi'
import { ApiError } from '@/lib/api'
import CartFreeGiftPicker from '@/components/cart/CartFreeGiftPicker.vue'
import CartMilestoneBar from '@/components/cart/CartMilestoneBar.vue'
import { formatCurrency, STORE_CURRENCY } from '@/lib/currency'
import { useCatalogStore } from '@/stores/catalog'
import { useFreeGiftsStore } from '@/stores/freeGifts'
import { submitOrder } from '@/lib/orders'
import { validateCoupon } from '@/lib/coupons'
import { lookupPincode } from '@/lib/pincode'
import {
  normalizeIndianPhone,
  validateCheckoutForm,
} from '@/lib/checkoutValidation'
import {
  fetchRazorpayConfig,
  openRazorpayCheckout,
  startRazorpayCheckout,
  verifyRazorpayPayment,
} from '@/lib/payments'

const cart = useCartStore()
const catalog = useCatalogStore()
const freeGifts = useFreeGiftsStore()
const cartUi = useCartUiStore()
const authStore = useAuthStore()
const router = useRouter()

const email = ref('')
const fullName = ref('')
const addressLine1 = ref('')
const addressLine2 = ref('')
const landmark = ref('')
const pincode = ref('')
const city = ref('')
const state = ref('')
const phone = ref('')
const notes = ref('')
const pinLookupBusy = ref(false)
const pinLookupError = ref('')
const busy = ref(false)
const error = ref('')
const done = ref(false)
const paidOnline = ref(false)
const orderId = ref<number | null>(null)
const razorpayEnabled = ref(false)
const razorpaySetupHint = ref<string | null>(null)
const couponInput = ref('')
const appliedCoupon = ref<{ code: string; discountInr: number; description?: string } | null>(null)
const couponError = ref('')
const couponBusy = ref(false)

const fmt = formatCurrency

const subtotal = computed(() => cart.subtotal)
const discountAmount = computed(() =>
  appliedCoupon.value ? appliedCoupon.value.discountInr : cart.discountAmount,
)
const shippingFee = computed(() => cart.shippingFee)
const orderTotal = computed(() =>
  Math.max(0, subtotal.value - discountAmount.value) + shippingFee.value,
)
const usingCoupon = computed(() => appliedCoupon.value !== null)

async function applyCoupon() {
  couponError.value = ''
  const code = couponInput.value.trim()
  if (!code) return
  const em = email.value.trim()
  if (!em) {
    couponError.value = 'Enter your email first'
    return
  }
  couponBusy.value = true
  try {
    const res = await validateCoupon(code, em, subtotal.value)
    if (!res.ok) {
      appliedCoupon.value = null
      couponError.value = res.message ?? 'Invalid coupon'
      return
    }
    appliedCoupon.value = {
      code: res.coupon?.code ?? code.toUpperCase(),
      discountInr: res.discountInr ?? 0,
      description: res.coupon?.description,
    }
  } catch (e) {
    couponError.value = e instanceof Error ? e.message : 'Could not validate coupon'
  } finally {
    couponBusy.value = false
  }
}

function clearCoupon() {
  appliedCoupon.value = null
  couponInput.value = ''
  couponError.value = ''
}

onMounted(async () => {
  if (cart.lines.length === 0) {
    cartUi.open()
    router.replace('/shop')
    return
  }
  await catalog.ensureLoaded()
  await freeGifts.ensureLoaded()
  cart.refreshLineImagesFromCatalog(catalog.catalog)
  cart.refreshFreeGiftOptions(
    catalog.catalog,
    freeGifts.configuredOptions.map((o) => o.id),
  )
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
  try {
    const raw = localStorage.getItem('theminimark_hamper_note')
    if (raw) {
      const parsed = JSON.parse(raw) as { message?: string; phone?: string }
      const parts: string[] = []
      if (parsed.message) parts.push(`Hamper message: ${parsed.message}`)
      if (parsed.phone) parts.push(`Hamper contact: ${parsed.phone}`)
      if (parts.length) {
        notes.value = [notes.value.trim(), ...parts].filter(Boolean).join('\n')
      }
      localStorage.removeItem('theminimark_hamper_note')
    }
  } catch {
    /* ignore */
  }
})

watch(
  () => pincode.value.replace(/\D/g, ''),
  (digits) => {
    if (digits.length === 6) {
      void onPincodeLookup()
    }
  },
)

function shippingInput() {
  return {
    fullName: fullName.value,
    phone: phone.value,
    addressLine1: addressLine1.value,
    addressLine2: addressLine2.value,
    landmark: landmark.value,
    pincode: pincode.value,
    city: city.value,
    state: state.value,
  }
}

async function onPincodeLookup() {
  pinLookupError.value = ''
  const pin = pincode.value.replace(/\D/g, '')
  if (pin.length !== 6) return
  pinLookupBusy.value = true
  try {
    const res = await lookupPincode(pin)
    if (res.ok && res.city && res.state) {
      city.value = res.city
      state.value = res.state
    } else {
      pinLookupError.value = res.message ?? 'PIN code not found'
    }
  } finally {
    pinLookupBusy.value = false
  }
}

function onPhoneInput(event: Event) {
  const el = event.target as HTMLInputElement
  phone.value = normalizeIndianPhone(el.value)
}

function buildOrderPayload(em: string) {
  const noteParts = [notes.value.trim()].filter(Boolean)
  const gift = cart.selectedFreeGift
  const pin = pincode.value.replace(/\D/g, '').slice(0, 6)
  return {
    customerEmail: em,
    customerName: fullName.value.trim(),
    currency: STORE_CURRENCY,
    shipping: {
      phone: normalizeIndianPhone(phone.value),
      addressLine1: addressLine1.value.trim(),
      addressLine2: addressLine2.value.trim() || undefined,
      landmark: landmark.value.trim() || undefined,
      pincode: pin,
      city: city.value.trim(),
      state: state.value.trim(),
    },
    lines: cart.lines.map((l) => {
      const base = {
        productId: l.productId,
        name: l.name,
        unitPrice: l.unitPrice,
        quantity: l.quantity,
      }
      if (l.customType && l.customPhotoPath) {
        return {
          ...base,
          personalization: {
            type: l.customType,
            photoPath: l.customPhotoPath,
            zoom: l.customZoom ?? 1,
            posX: l.customPosX ?? 50,
            posY: l.customPosY ?? 50,
            options: l.customOptions ?? { quantity: l.quantity },
          },
        }
      }
      return base
    }),
    notes: noteParts.length ? noteParts.join('\n') : undefined,
    freeGift:
      cart.hasFreeGift && gift
        ? { productId: gift.id, name: gift.name }
        : undefined,
    couponCode: appliedCoupon.value?.code,
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
  pinLookupError.value = ''
  const em = email.value.trim()
  const validationError = validateCheckoutForm(em, shippingInput())
  if (validationError) {
    error.value = validationError
    return
  }
  if (cart.hasFreeGift && !cart.freeGiftReady) {
    error.value = 'Please pick your free gift before checkout.'
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
            <p class="checkout__guest">Fast guest checkout — no account required</p>
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
              <label class="checkout__label" for="co-name">Full name <span aria-hidden="true">*</span></label>
              <input
                id="co-name"
                v-model="fullName"
                type="text"
                class="checkout__input"
                required
                autocomplete="name"
                placeholder="Name for delivery"
              />
            </div>
            <div class="checkout__field">
              <label class="checkout__label" for="co-phone">Mobile number <span aria-hidden="true">*</span></label>
              <input
                id="co-phone"
                v-model="phone"
                type="tel"
                class="checkout__input"
                required
                autocomplete="tel"
                inputmode="numeric"
                maxlength="10"
                placeholder="10-digit number"
                @input="onPhoneInput"
              />
            </div>
            <div class="checkout__field">
              <label class="checkout__label" for="co-addr1">Address line 1 <span aria-hidden="true">*</span></label>
              <input
                id="co-addr1"
                v-model="addressLine1"
                type="text"
                class="checkout__input"
                required
                autocomplete="address-line1"
                placeholder="House / flat / building, street"
              />
            </div>
            <div class="checkout__field">
              <label class="checkout__label" for="co-addr2">Address line 2</label>
              <input
                id="co-addr2"
                v-model="addressLine2"
                type="text"
                class="checkout__input"
                autocomplete="address-line2"
                placeholder="Area, colony (optional)"
              />
            </div>
            <div class="checkout__field">
              <label class="checkout__label" for="co-landmark">Landmark</label>
              <input
                id="co-landmark"
                v-model="landmark"
                type="text"
                class="checkout__input"
                placeholder="Near school, shop, etc. (optional)"
              />
            </div>
            <div class="checkout__field-row">
              <div class="checkout__field checkout__field--pin">
                <label class="checkout__label" for="co-pin">PIN code <span aria-hidden="true">*</span></label>
                <input
                  id="co-pin"
                  v-model="pincode"
                  type="text"
                  class="checkout__input"
                  required
                  inputmode="numeric"
                  maxlength="6"
                  autocomplete="postal-code"
                  placeholder="6 digits"
                  @blur="onPincodeLookup"
                />
                <p v-if="pinLookupBusy" class="checkout__hint">Looking up city &amp; state…</p>
                <p v-else-if="pinLookupError" class="checkout__field-error">{{ pinLookupError }}</p>
              </div>
              <div class="checkout__field">
                <label class="checkout__label" for="co-city">City <span aria-hidden="true">*</span></label>
                <input
                  id="co-city"
                  v-model="city"
                  type="text"
                  class="checkout__input"
                  required
                  autocomplete="address-level2"
                  placeholder="Auto-filled from PIN"
                />
              </div>
            </div>
            <div class="checkout__field">
              <label class="checkout__label" for="co-state">State <span aria-hidden="true">*</span></label>
              <input
                id="co-state"
                v-model="state"
                type="text"
                class="checkout__input"
                required
                autocomplete="address-level1"
                placeholder="Auto-filled from PIN"
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
            <CartMilestoneBar :subtotal="cart.subtotal" />

            <div v-if="cart.hasFreeGift" class="checkout__gift-block">
              <CartFreeGiftPicker :options="cart.freeGiftOptions" />
            </div>

            <div class="checkout__coupon">
              <label class="checkout__coupon-label">Coupon code</label>
              <div class="checkout__coupon-row">
                <input
                  v-model="couponInput"
                  type="text"
                  placeholder="e.g. MINIFIRST10"
                  class="checkout__coupon-input"
                  :disabled="!!appliedCoupon"
                />
                <button
                  v-if="!appliedCoupon"
                  type="button"
                  class="btn btn--secondary checkout__coupon-btn"
                  :disabled="couponBusy"
                  @click="applyCoupon"
                >
                  Apply
                </button>
                <button
                  v-else
                  type="button"
                  class="btn btn--ghost checkout__coupon-btn"
                  @click="clearCoupon"
                >
                  Remove
                </button>
              </div>
              <p v-if="couponError" class="checkout__coupon-error">{{ couponError }}</p>
              <p v-else-if="appliedCoupon?.description" class="checkout__coupon-ok">{{ appliedCoupon.description }}</p>
            </div>

            <p class="checkout__total">
              Subtotal <strong>{{ fmt(subtotal) }}</strong>
            </p>
            <p v-if="discountAmount > 0" class="checkout__total checkout__total--discount">
              <template v-if="usingCoupon">Coupon ({{ appliedCoupon?.code }})</template>
              <template v-else>12% off</template>
              <strong>−{{ fmt(discountAmount) }}</strong>
            </p>
            <p v-if="cart.selectedFreeGift" class="checkout__total">
              Free gift
              <strong>{{ cart.selectedFreeGift.name }}</strong>
            </p>
            <p class="checkout__total">
              Shipping
              <strong>{{ shippingFee === 0 ? 'Free' : fmt(shippingFee) }}</strong>
            </p>
            <p class="checkout__total checkout__total--grand">
              Total <strong>{{ fmt(orderTotal) }}</strong>
            </p>
            <p v-if="cart.hasFreeGift && !cart.freeGiftReady" class="checkout__gift-warn">
              Pick your free gift above before paying.
            </p>
            <p class="checkout__fine">
              <Lock class="checkout__fine-ico" :size="14" :stroke-width="2" aria-hidden="true" />
              <template v-if="razorpayEnabled">Secure payment by Razorpay.</template>
              <template v-else>Rewards applied at checkout.</template>
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

.checkout__guest {
  margin: 0 0 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--tm-ink-muted);
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

.checkout__field-row {
  display: grid;
  gap: 1rem;
  grid-template-columns: minmax(7rem, 9rem) minmax(0, 1fr);
}

.checkout__hint {
  margin: 0.25rem 0 0;
  font-size: 0.8rem;
  color: var(--color-ink-muted);
}

.checkout__field-error {
  margin: 0.25rem 0 0;
  font-size: 0.8rem;
  color: var(--color-sale);
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
  background: var(--tm-gradient);
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
  font-family: var(--font-ui);
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  color: var(--color-ink);
}

.checkout__total--grand {
  padding-top: 0.65rem;
  margin-top: 0.35rem;
  border-top: 1px solid var(--color-border);
  font-size: 1.05rem;
}

.checkout__total--grand strong {
  font-size: 1.125rem;
  font-weight: 800;
}

.checkout__total--discount strong {
  color: var(--color-sale);
}

.checkout__gift-note {
  margin: 0 0 0.5rem;
  font-size: 0.82rem;
  font-weight: 650;
  color: var(--color-accent);
}

.checkout__gift-block {
  margin: 0.75rem 0;
  padding: 0.5rem 0.55rem;
  border-radius: 10px;
  background: var(--color-page);
  border: 1px solid var(--color-border);
}

.checkout__coupon {
  margin: 0.75rem 0 0.5rem;
}

.checkout__coupon-label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 600;
  margin-bottom: 0.35rem;
}

.checkout__coupon-row {
  display: flex;
  gap: 0.5rem;
}

.checkout__coupon-input {
  flex: 1;
  min-width: 0;
  padding: 0.5rem 0.65rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface);
  color: var(--color-ink);
}

.checkout__coupon-btn {
  flex-shrink: 0;
}

.checkout__coupon-error {
  margin: 0.35rem 0 0;
  font-size: 0.8125rem;
  color: var(--color-sale);
}

.checkout__coupon-ok {
  margin: 0.35rem 0 0;
  font-size: 0.8125rem;
  color: var(--color-accent);
}

.checkout__gift-block :deep(.gift) {
  padding-top: 0;
  border-top: none;
}

.checkout__gift-warn {
  margin: 0 0 0.65rem;
  padding: 0.5rem 0.6rem;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-sale);
  background: rgba(184, 69, 61, 0.08);
  line-height: 1.4;
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
  background: var(--tm-gradient);
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
