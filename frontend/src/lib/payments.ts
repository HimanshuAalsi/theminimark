import { apiFetch } from '@/lib/api'
import type { SubmitOrderPayload } from '@/lib/orders'
import type { RazorpaySuccessResponse } from '@/types/razorpay'

function apiPrefix(): string {
  return import.meta.env.VITE_API_BASE_URL ? '/v1' : '/api/v1'
}

export interface RazorpayPublicConfig {
  enabled: boolean
  keyId: string | null
  currency: string
  companyName: string
  credentialsValid?: boolean
  setupHint?: string | null
}

export interface RazorpayCheckoutSession {
  ok: boolean
  orderId?: number
  razorpayOrderId?: string
  amount?: number
  currency?: string
  keyId?: string
  companyName?: string
  customer?: { name: string; email: string }
  message: string
}

export async function fetchRazorpayConfig(): Promise<RazorpayPublicConfig> {
  return apiFetch<RazorpayPublicConfig>(`${apiPrefix()}/payments/razorpay/config`)
}

export async function startRazorpayCheckout(
  payload: SubmitOrderPayload,
): Promise<RazorpayCheckoutSession> {
  return apiFetch<RazorpayCheckoutSession>(`${apiPrefix()}/payments/razorpay/checkout`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function verifyRazorpayPayment(body: {
  orderId: number
  razorpayOrderId: string
  razorpayPaymentId: string
  razorpaySignature: string
}): Promise<{ ok: boolean; orderId?: number; message: string }> {
  return apiFetch(`${apiPrefix()}/payments/razorpay/verify`, {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

function loadRazorpayScript(): Promise<void> {
  if (typeof window !== 'undefined' && typeof window.Razorpay !== 'undefined') {
    return Promise.resolve()
  }
  return new Promise((resolve, reject) => {
    const existing = document.querySelector<HTMLScriptElement>(
      'script[src="https://checkout.razorpay.com/v1/checkout.js"]',
    )
    if (existing) {
      existing.addEventListener('load', () => resolve())
      existing.addEventListener('error', () => reject(new Error('Razorpay script failed')))
      return
    }
    const script = document.createElement('script')
    script.src = 'https://checkout.razorpay.com/v1/checkout.js'
    script.async = true
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('Could not load Razorpay checkout'))
    document.body.appendChild(script)
  })
}

export interface OpenRazorpayOptions {
  session: RazorpayCheckoutSession
  phone?: string
  onSuccess: (response: RazorpaySuccessResponse) => void | Promise<void>
  onDismiss?: () => void
}

export async function openRazorpayCheckout(options: OpenRazorpayOptions): Promise<void> {
  const { session, phone, onSuccess, onDismiss } = options
  if (!session.ok || !session.keyId || !session.razorpayOrderId || session.amount == null) {
    throw new Error(session.message || 'Invalid checkout session')
  }

  await loadRazorpayScript()

  const Razorpay = window.Razorpay
  if (!Razorpay) {
    throw new Error('Razorpay is not available')
  }

  const rzp = new Razorpay({
    key: session.keyId,
    amount: session.amount,
    currency: session.currency ?? 'INR',
    name: session.companyName ?? 'The Minimark',
    description: session.orderId != null ? `Order #${session.orderId}` : 'Order',
    order_id: session.razorpayOrderId,
    prefill: {
      name: session.customer?.name ?? '',
      email: session.customer?.email ?? '',
      contact: phone?.replace(/\D/g, '').slice(-10) ?? '',
    },
    theme: { color: '#2d5c52' },
    handler: (response: RazorpaySuccessResponse) => {
      void onSuccess(response)
    },
    modal: {
      ondismiss: () => {
        onDismiss?.()
      },
    },
  })

  rzp.on('payment.failed', () => {
    onDismiss?.()
  })

  rzp.open()
}
