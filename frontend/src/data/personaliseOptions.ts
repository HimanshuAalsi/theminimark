import type { PersonaliseType } from '@/data/personalise'

export type CalendarLayout = 'desk' | 'wall'
export type CardOccasion = 'birthday' | 'thank-you' | 'love' | 'congratulations' | 'other'
export type MagnetFinish = 'glossy' | 'matte'
export type BookmarkPack = 1 | 3 | 5

export interface PersonaliseOptions {
  quantity: number
  /** Custom text printed on bookmark (names or short quotes) */
  customText?: string
  /** Customer email for order updates about this design */
  contactEmail?: string
  packSize?: BookmarkPack
  calendarLayout?: CalendarLayout
  startMonth?: number
  year?: number
  occasion?: CardOccasion
  recipientName?: string
  insideMessage?: string
  finish?: MagnetFinish
  giftNote?: string
}

export interface PersonaliseFieldChoice {
  value: string | number
  label: string
}

export interface PersonaliseField {
  key: keyof PersonaliseOptions
  label: string
  type: 'select' | 'number' | 'text' | 'textarea' | 'month'
  choices?: PersonaliseFieldChoice[]
  min?: number
  max?: number
  placeholder?: string
  maxLength?: number
}

const MONTHS = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]

export const CARD_OCCASIONS: { value: CardOccasion; label: string }[] = [
  { value: 'birthday', label: 'Birthday' },
  { value: 'thank-you', label: 'Thank you' },
  { value: 'love', label: 'Love & romance' },
  { value: 'congratulations', label: 'Congratulations' },
  { value: 'other', label: 'Other' },
]

export function defaultPersonaliseOptions(type: PersonaliseType): PersonaliseOptions {
  const year = new Date().getFullYear()
  const base: PersonaliseOptions = { quantity: 1, year, giftNote: '' }
  switch (type) {
    case 'bookmark':
      return { ...base, packSize: 1, customText: '', contactEmail: '' }
    case 'card':
      return {
        ...base,
        occasion: 'birthday',
        recipientName: '',
        insideMessage: '',
        contactEmail: '',
      }
    case 'magnet':
      return { ...base, finish: 'glossy', contactEmail: '' }
    case 'calendar':
      return { ...base, calendarLayout: 'desk', startMonth: 1, year, contactEmail: '' }
    default:
      return base
  }
}

export function personaliseFieldsForType(type: PersonaliseType): PersonaliseField[] {
  const monthChoices = MONTHS.map((label, i) => ({ value: i + 1, label }))
  const sharedQty: PersonaliseField = {
    key: 'quantity',
    label: 'Quantity',
    type: 'number',
    min: 1,
    max: 99,
  }
  const giftNote: PersonaliseField = {
    key: 'giftNote',
    label: 'Order note for printer (optional)',
    type: 'textarea',
    placeholder: 'e.g. wrap as gift, deliver by date…',
    maxLength: 500,
  }

  switch (type) {
    case 'bookmark':
      return [
        sharedQty,
        {
          key: 'packSize',
          label: 'Pack size',
          type: 'select',
          choices: [
            { value: 1, label: 'Single bookmark' },
            { value: 3, label: 'Pack of 3' },
            { value: 5, label: 'Pack of 5' },
          ],
        },
        giftNote,
      ]
    case 'calendar':
      return [
        sharedQty,
        {
          key: 'calendarLayout',
          label: 'Format',
          type: 'select',
          choices: [
            { value: 'desk', label: 'Desk tent calendar' },
            { value: 'wall', label: 'Wall calendar' },
          ],
        },
        {
          key: 'startMonth',
          label: 'Start month',
          type: 'select',
          choices: monthChoices,
        },
        {
          key: 'year',
          label: 'Year',
          type: 'number',
          min: yearBounds().min,
          max: yearBounds().max,
        },
        giftNote,
      ]
    case 'card':
      return [
        sharedQty,
        {
          key: 'occasion',
          label: 'Occasion',
          type: 'select',
          choices: CARD_OCCASIONS.map((o) => ({ value: o.value, label: o.label })),
        },
        {
          key: 'recipientName',
          label: 'Recipient name (printed inside)',
          type: 'text',
          placeholder: 'e.g. Sarah',
          maxLength: 80,
        },
        {
          key: 'insideMessage',
          label: 'Message inside card',
          type: 'textarea',
          placeholder: 'Your personalised message…',
          maxLength: 400,
        },
        giftNote,
      ]
    case 'magnet':
      return [
        sharedQty,
        {
          key: 'finish',
          label: 'Finish',
          type: 'select',
          choices: [
            { value: 'glossy', label: 'Glossy' },
            { value: 'matte', label: 'Matte' },
          ],
        },
        giftNote,
      ]
    default:
      return [sharedQty, giftNote]
  }
}

function yearBounds(): { min: number; max: number } {
  const y = new Date().getFullYear()
  return { min: y, max: y + 2 }
}

export function personaliseOptionsSummary(
  type: PersonaliseType,
  options: PersonaliseOptions,
): string[] {
  const lines: string[] = []
  if (options.quantity > 1) lines.push(`Qty: ${options.quantity}`)
  if (type === 'bookmark') {
    if (options.customText?.trim()) {
      const t = options.customText.trim()
      lines.push(`Text: ${t.length > 48 ? t.slice(0, 48) + '…' : t}`)
    }
    if (options.contactEmail?.trim()) lines.push(`Email: ${options.contactEmail.trim()}`)
    if (options.packSize && options.packSize > 1) {
      lines.push(`Pack: ${options.packSize}`)
    }
  }
  if (type === 'calendar') {
    if (options.calendarLayout) {
      lines.push(`Format: ${options.calendarLayout === 'desk' ? 'Desk' : 'Wall'}`)
    }
    if (options.startMonth && options.year) {
      const m = MONTHS[options.startMonth - 1] ?? String(options.startMonth)
      lines.push(`Starts: ${m} ${options.year}`)
    }
  }
  if (type === 'card') {
    if (options.occasion) {
      const o = CARD_OCCASIONS.find((c) => c.value === options.occasion)
      lines.push(`Occasion: ${o?.label ?? options.occasion}`)
    }
    if (options.recipientName?.trim()) lines.push(`To: ${options.recipientName.trim()}`)
    if (options.insideMessage?.trim()) {
      const msg = options.insideMessage.trim()
      lines.push(`Message: ${msg.length > 60 ? msg.slice(0, 60) + '…' : msg}`)
    }
  }
  if (type === 'magnet' && options.finish) {
    lines.push(`Finish: ${options.finish}`)
  }
  if (options.giftNote?.trim()) {
    lines.push(`Note: ${options.giftNote.trim()}`)
  }
  return lines
}
