export interface PincodeLookupResult {
  ok: boolean
  city?: string
  state?: string
  message?: string
}

/** Look up city/state from a 6-digit Indian PIN via India Post API. */
export async function lookupPincode(pincode: string): Promise<PincodeLookupResult> {
  const pin = pincode.replace(/\D/g, '').slice(0, 6)
  if (!/^\d{6}$/.test(pin)) {
    return { ok: false, message: 'Enter a valid 6-digit PIN code' }
  }

  try {
    const res = await fetch(`https://api.postalpincode.in/pincode/${pin}`)
    if (!res.ok) {
      return { ok: false, message: 'Could not look up PIN code. Try again.' }
    }
    const data = (await res.json()) as Array<{
      Status?: string
      Message?: string
      PostOffice?: Array<{ District?: string; State?: string }>
    }>
    const block = data[0]
    if (!block || block.Status !== 'Success' || !block.PostOffice?.length) {
      return { ok: false, message: block?.Message ?? 'PIN code not found' }
    }
    const office = block.PostOffice[0]
    const city = office.District?.trim()
    const state = office.State?.trim()
    if (!city || !state) {
      return { ok: false, message: 'Could not resolve city/state for this PIN' }
    }
    return { ok: true, city, state }
  } catch {
    return { ok: false, message: 'Network error looking up PIN code' }
  }
}
