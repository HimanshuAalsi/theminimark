export interface CheckoutShippingInput {
  fullName: string
  phone: string
  addressLine1: string
  addressLine2: string
  landmark: string
  pincode: string
  city: string
  state: string
}

export function normalizeIndianPhone(raw: string): string {
  let digits = raw.replace(/\D/g, '')
  if (digits.length === 12 && digits.startsWith('91')) {
    digits = digits.slice(2)
  }
  if (digits.length === 11 && digits.startsWith('0')) {
    digits = digits.slice(1)
  }
  return digits.slice(0, 10)
}

export function isValidIndianPhone(phone: string): boolean {
  return /^[6-9]\d{9}$/.test(normalizeIndianPhone(phone))
}

export function isValidPincode(pincode: string): boolean {
  return /^\d{6}$/.test(pincode.replace(/\D/g, ''))
}

export function validateCheckoutForm(
  email: string,
  shipping: CheckoutShippingInput,
): string | null {
  if (!email.trim()) {
    return 'Email is required.'
  }
  if (shipping.fullName.trim().length < 2) {
    return 'Full name is required.'
  }
  if (!isValidIndianPhone(shipping.phone)) {
    return 'Enter a valid 10-digit mobile number (starts with 6–9).'
  }
  if (shipping.addressLine1.trim().length < 5) {
    return 'House / flat / street address is required.'
  }
  const pin = shipping.pincode.replace(/\D/g, '')
  if (!/^\d{6}$/.test(pin)) {
    return 'Enter a valid 6-digit PIN code.'
  }
  if (shipping.city.trim().length < 2) {
    return 'City is required — enter PIN code to auto-fill, or type manually.'
  }
  if (shipping.state.trim().length < 2) {
    return 'State is required — enter PIN code to auto-fill, or type manually.'
  }
  return null
}
