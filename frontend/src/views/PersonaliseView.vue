<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PersonaliseCalendarStudio from '@/components/personalise/PersonaliseCalendarStudio.vue'
import PersonaliseFrontBackStudio from '@/components/personalise/PersonaliseFrontBackStudio.vue'
import { defaultPhotoTransform, type PhotoTransform } from '@/composables/usePhotoEditor'
import PersonaliseMagnetStudio from '@/components/personalise/PersonaliseMagnetStudio.vue'
import PersonaliseOrderPanel from '@/components/personalise/PersonaliseOrderPanel.vue'
import PersonaliseTypeNav from '@/components/personalise/PersonaliseTypeNav.vue'
import {
  BOOKMARK_IMAGE_HINT,
  BOOKMARK_POLICY_NOTE,
  BOOKMARK_TEXT_MAX_WORDS,
  bookmarkTextValid,
  countWords,
} from '@/data/bookmarkCustom'
import { personaliseProduct, type PersonaliseType } from '@/data/personalise'
import { defaultPersonaliseOptions, personaliseOptionsSummary } from '@/data/personaliseOptions'
import {
  BOOKMARK_ACCENT_COLORS,
  calendarDesignById,
  calendarMonthKey,
  magnetStripFrameByIndex,
  magnetStripSlotKey,
  typeFromQuery,
  CALENDAR_MONTHS,
} from '@/data/personaliseStudio'
import { usePersonaliseUpload } from '@/composables/usePersonaliseUpload'
import { uploadPersonalisePhoto } from '@/lib/personaliseUpload'
import { useCartStore } from '@/stores/cart'
import { useCartUiStore } from '@/stores/cartUi'

const route = useRoute()
const router = useRouter()
const cart = useCartStore()
const cartUi = useCartUiStore()

const activeType = ref<PersonaliseType>(typeFromQuery(route.query.type))
const options = ref(defaultPersonaliseOptions(activeType.value))
const accentColor = ref(BOOKMARK_ACCENT_COLORS.find((c) => c.id === 'pink')?.hex ?? '#f4b8d0')
const frontColor = ref('#4a1942')
const frontTransform = ref<PhotoTransform>(defaultPhotoTransform())
const backTransform = ref<PhotoTransform>(defaultPhotoTransform())
const activeFrame = ref(0)
const selectedDesignId = ref('d1')

const frontUpload = usePersonaliseUpload()
const backUpload = usePersonaliseUpload()

const stripSlotPhotos = ref<Record<string, string | null>>({})
const stripSlotPaths = ref<Record<string, string | null>>({})
const stripSlotTexts = ref<Record<string, string>>({})

const monthPhotos = ref<Record<string, string | null>>({})
const monthPaths = ref<Record<string, string | null>>({})

const uploadingSlot = ref<string | null>(null)
const uploadingMonth = ref<string | null>(null)

const frontPreview = computed(() => frontUpload.previewUrl.value)
const backPreview = computed(() => backUpload.previewUrl.value)
const frontUploading = computed(() => frontUpload.uploading.value)
const backUploading = computed(() => backUpload.uploading.value)
const uploadErrorMsg = computed(
  () => formError.value || frontUpload.error.value || backUpload.error.value,
)

const formError = ref('')
const adding = ref(false)
const added = ref(false)

watch(
  () => route.query.type,
  (t) => {
    activeType.value = typeFromQuery(t)
  },
)

watch(activeType, (t) => {
  options.value = defaultPersonaliseOptions(t)
  formError.value = ''
  added.value = false
  router.replace({ query: { ...route.query, type: t === 'bookmark' ? undefined : t } })
})

const product = computed(() => personaliseProduct(activeType.value))

const calendarDesign = computed(() => calendarDesignById(selectedDesignId.value))

const magnetFrame = computed(() => magnetStripFrameByIndex(activeFrame.value))

const effectivePrice = computed(() => {
  if (activeType.value === 'magnet') return magnetFrame.value.price
  if (activeType.value === 'calendar') return calendarDesign.value.price
  return product.value.price
})

const effectiveCompareAt = computed(() => {
  if (activeType.value === 'magnet') return magnetFrame.value.compareAt
  if (activeType.value === 'calendar') return calendarDesign.value.compareAt
  return product.value.compareAt
})

const orderDetails = computed(() => {
  const lines = personaliseOptionsSummary(activeType.value, options.value)
  if (activeType.value === 'bookmark' || activeType.value === 'card') {
    lines.unshift(`Front: ${frontColor.value}`)
    lines.unshift(`Back: ${accentColor.value}`)
    if (backUpload.previewUrl.value) lines.push('Back photo uploaded')
  }
  if (activeType.value === 'magnet') {
    lines.unshift(`${magnetFrame.value.label} · magnetic photo strip`)
  }
  if (activeType.value === 'calendar') {
    lines.unshift(calendarDesign.value.label)
  }
  return lines
})

const wordCount = computed(() => countWords(options.value.customText ?? ''))
const textOverLimit = computed(() => wordCount.value > BOOKMARK_TEXT_MAX_WORDS)

const canAdd = computed(() => {
  if (activeType.value === 'bookmark' || activeType.value === 'card') {
    return frontUpload.ready() && !textOverLimit.value
  }
  if (activeType.value === 'magnet') {
    const frame = magnetFrame.value
    return frame.slots.every((slot, si) => {
      if (slot.type !== 'photo') return true
      return Boolean(stripSlotPaths.value[magnetStripSlotKey(activeFrame.value, si)])
    })
  }
  if (activeType.value === 'calendar') {
    return CALENDAR_MONTHS.every((_, mi) =>
      Boolean(monthPaths.value[calendarMonthKey(selectedDesignId.value, mi)]),
    )
  }
  return false
})

async function handleFrontUpload(file: File) {
  formError.value = ''
  added.value = false
  frontTransform.value = defaultPhotoTransform()
  await frontUpload.acceptFile(file)
}

async function handleBackUpload(file: File) {
  formError.value = ''
  added.value = false
  backTransform.value = defaultPhotoTransform()
  await backUpload.acceptFile(file)
}

async function handleMonthUpload(monthIndex: number, file: File) {
  formError.value = ''
  added.value = false
  const key = calendarMonthKey(selectedDesignId.value, monthIndex)
  uploadingMonth.value = key
  try {
    if (!file.type.startsWith('image/')) {
      formError.value = 'Please choose an image file.'
      return
    }
    const uploaded = await uploadPersonalisePhoto(file)
    monthPhotos.value = { ...monthPhotos.value, [key]: uploaded.url }
    monthPaths.value = { ...monthPaths.value, [key]: uploaded.path }
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Upload failed.'
  } finally {
    uploadingMonth.value = null
  }
}

async function handleSlotUpload(frameIndex: number, slotIndex: number, file: File) {
  formError.value = ''
  added.value = false
  const key = magnetStripSlotKey(frameIndex, slotIndex)
  uploadingSlot.value = key
  try {
    if (!file.type.startsWith('image/')) {
      formError.value = 'Please choose an image file.'
      return
    }
    const uploaded = await uploadPersonalisePhoto(file)
    stripSlotPhotos.value = { ...stripSlotPhotos.value, [key]: uploaded.url }
    stripSlotPaths.value = { ...stripSlotPaths.value, [key]: uploaded.path }
  } catch (e) {
    formError.value = e instanceof Error ? e.message : 'Upload failed.'
  } finally {
    uploadingSlot.value = null
  }
}

function handleSlotText(frameIndex: number, slotIndex: number, text: string) {
  const key = magnetStripSlotKey(frameIndex, slotIndex)
  stripSlotTexts.value = { ...stripSlotTexts.value, [key]: text }
  added.value = false
}

function onCalendarDesign(design: { id: string; layout: 'desk' | 'wall' }) {
  selectedDesignId.value = design.id
  options.value = {
    ...options.value,
    calendarLayout: design.layout,
  }
}

async function addToCart() {
  formError.value = ''
  if (!canAdd.value) {
    formError.value = 'Please upload your photo(s) before adding to cart.'
    return
  }
  if (activeType.value === 'bookmark' && options.value.customText?.trim() && !bookmarkTextValid(options.value.customText)) {
    formError.value = `Keep text to ${BOOKMARK_TEXT_MAX_WORDS} words or fewer.`
    return
  }

  adding.value = true
  try {
    let photoUrl = frontUpload.previewUrl.value!
    let photoPath = frontUpload.photoPath.value!
    let price = effectivePrice.value
    let name = product.value.label
    const giftParts: string[] = []

    if (activeType.value === 'magnet') {
      const frame = magnetFrame.value
      const fi = activeFrame.value
      let firstPhotoUrl: string | null = null
      let firstPhotoPath: string | null = null
      const slotPaths: Record<string, string> = {}
      const slotTexts: Record<string, string> = {}

      frame.slots.forEach((slot, si) => {
        const key = magnetStripSlotKey(fi, si)
        if (slot.type === 'photo') {
          const path = stripSlotPaths.value[key]
          const url = stripSlotPhotos.value[key]
          if (path && url) {
            slotPaths[slot.label] = path
            if (!firstPhotoPath) {
              firstPhotoPath = path
              firstPhotoUrl = url
            }
          }
        } else {
          const text = stripSlotTexts.value[key]?.trim()
          if (text) slotTexts[slot.label] = text
        }
      })

      photoUrl = firstPhotoUrl!
      photoPath = firstPhotoPath!
      price = frame.price
      name = `Magnetic photo strip · ${frame.label}`
      giftParts.push(`Frame: ${frame.id}`)
      if (Object.keys(slotPaths).length) {
        giftParts.push(`Strip photos: ${JSON.stringify(slotPaths)}`)
      }
      if (Object.keys(slotTexts).length) {
        giftParts.push(`Strip text: ${JSON.stringify(slotTexts)}`)
      }
    }

    if (activeType.value === 'calendar') {
      const design = calendarDesign.value
      const monthPathsPayload: Record<string, string> = {}
      let firstPhotoUrl: string | null = null
      let firstPhotoPath: string | null = null

      CALENDAR_MONTHS.forEach((month, mi) => {
        const key = calendarMonthKey(design.id, mi)
        const path = monthPaths.value[key]
        const url = monthPhotos.value[key]
        if (path && url) {
          monthPathsPayload[month] = path
          if (!firstPhotoPath) {
            firstPhotoPath = path
            firstPhotoUrl = url
          }
        }
      })

      photoUrl = firstPhotoUrl!
      photoPath = firstPhotoPath!
      price = design.price
      name = `Custom calendar · ${design.label}`
      giftParts.push(`Design: ${design.id}`)
      giftParts.push(`Layout: ${design.layout}`)
      giftParts.push(`Month photos: ${JSON.stringify(monthPathsPayload)}`)
      options.value = {
        ...options.value,
        calendarLayout: design.layout,
      }
    }

    if (activeType.value === 'bookmark' || activeType.value === 'card') {
      giftParts.push(`Front colour ${frontColor.value}`)
      giftParts.push(`Back colour ${accentColor.value}`)
    }
    if (backUpload.photoPath.value) {
      giftParts.push(`Back photo: ${backUpload.photoPath.value}`)
      giftParts.push(
        `Back crop: z${backTransform.value.zoom} @ ${backTransform.value.posX},${backTransform.value.posY}`,
      )
    }

    cart.addCustomProduct({
      type: activeType.value,
      name,
      unitPrice: price,
      photoUrl,
      photoPath,
      zoom: frontTransform.value.zoom,
      posX: frontTransform.value.posX,
      posY: frontTransform.value.posY,
      options: {
        ...options.value,
        giftNote: [options.value.giftNote, ...giftParts].filter(Boolean).join('\n'),
      },
      quantity: options.value.quantity,
    })
    added.value = true
  } finally {
    adding.value = false
  }
}
</script>

<template>
  <div class="ps-page">
    <div class="tm-container ps-page__inner">
      <header class="ps-page__head">
        <h1 class="ps-page__title">Create your own</h1>
        <p class="ps-page__lead">
          Pick a product, upload your photos, and add to cart — bookmarks, magnets, cards &amp;
          calendars.
        </p>
      </header>

      <div class="ps-page__layout">
        <PersonaliseTypeNav :active="activeType" @select="activeType = $event" />

        <div class="ps-page__workspace">
          <PersonaliseFrontBackStudio
            v-if="activeType === 'bookmark' || activeType === 'card'"
            :mode="activeType"
            :front-photo-url="frontPreview"
            :back-photo-url="backPreview"
            :accent-color="accentColor"
            :front-color="frontColor"
            :front-transform="frontTransform"
            :back-transform="backTransform"
            :front-uploading="frontUploading"
            :back-uploading="backUploading"
            @update:accent-color="accentColor = $event"
            @update:front-color="frontColor = $event"
            @update:front-transform="frontTransform = $event"
            @update:back-transform="backTransform = $event"
            @front-upload="handleFrontUpload"
            @back-upload="handleBackUpload"
          />

          <PersonaliseMagnetStudio
            v-else-if="activeType === 'magnet'"
            :active-frame="activeFrame"
            :slot-photos="stripSlotPhotos"
            :slot-texts="stripSlotTexts"
            :uploading-slot="uploadingSlot"
            @update:active-frame="activeFrame = $event"
            @slot-upload="handleSlotUpload"
            @update:slot-text="handleSlotText"
          />

          <PersonaliseCalendarStudio
            v-else
            :selected-design-id="selectedDesignId"
            :month-photos="monthPhotos"
            :uploading-month="uploadingMonth"
            @update:selected-design-id="selectedDesignId = $event"
            @select-design="onCalendarDesign"
            @month-upload="handleMonthUpload"
          />
        </div>

        <PersonaliseOrderPanel
          :product="product"
          :price="effectivePrice"
          :compare-at="effectiveCompareAt"
          :quantity="options.quantity"
          :can-add="canAdd"
          :adding="adding"
          :added="added"
          :error="uploadErrorMsg"
          :details="orderDetails"
          @update:quantity="options = { ...options, quantity: $event }"
          @add="addToCart"
          @open-cart="cartUi.open()"
        >
          <template v-if="activeType === 'bookmark'">
            <label class="ps-field">
              <span class="ps-field__label">Your text <small>optional · max {{ BOOKMARK_TEXT_MAX_WORDS }} words</small></span>
              <textarea
                v-model="options.customText"
                class="ps-field__input"
                rows="2"
                placeholder="Name or short quote…"
              />
              <span class="ps-field__hint" :class="{ 'ps-field__hint--over': textOverLimit }">
                {{ wordCount }} / {{ BOOKMARK_TEXT_MAX_WORDS }} words
              </span>
            </label>
            <p class="ps-field__note">{{ BOOKMARK_IMAGE_HINT }}</p>
          </template>

          <template v-else-if="activeType === 'card'">
            <label class="ps-field">
              <span class="ps-field__label">Message inside</span>
              <textarea v-model="options.insideMessage" class="ps-field__input" rows="3" placeholder="Your message…" />
            </label>
            <label class="ps-field">
              <span class="ps-field__label">Recipient name</span>
              <input v-model="options.recipientName" type="text" class="ps-field__input" placeholder="Printed inside" />
            </label>
          </template>

          <template v-else-if="activeType === 'magnet'">
            <p class="ps-field__note">{{ magnetFrame.hint }}</p>
          </template>

          <template v-else>
            <p class="ps-field__note">
              {{ calendarDesign.layout === 'desk' ? 'Desk' : 'Wall' }} calendar ·
              {{ calendarDesign.label }} · upload one photo per month (all 12 required).
            </p>
          </template>

          <label class="ps-field">
            <span class="ps-field__label">Email for design questions <small>optional</small></span>
            <input
              v-model="options.contactEmail"
              type="email"
              class="ps-field__input"
              placeholder="you@email.com"
              autocomplete="email"
            />
          </label>

          <p v-if="activeType === 'bookmark'" class="ps-policy">{{ BOOKMARK_POLICY_NOTE }}</p>
        </PersonaliseOrderPanel>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ps-page {
  padding: 1rem 0 2.5rem;
  background: var(--color-page);
}

.ps-page__head {
  margin-bottom: 0.85rem;
}

.ps-page__title {
  margin: 0 0 0.2rem;
  font-family: var(--font-display);
  font-size: clamp(1.45rem, 2.5vw, 1.85rem);
}

.ps-page__lead {
  margin: 0;
  max-width: 36rem;
  font-size: 0.88rem;
  color: var(--color-ink-muted);
  line-height: 1.45;
}

.ps-page__layout {
  display: grid;
  grid-template-columns: 8.25rem minmax(0, 1fr) 17.5rem;
  gap: 0.85rem;
  align-items: start;
}

@media (max-width: 1100px) {
  .ps-page__layout {
    grid-template-columns: 1fr;
  }
}

.ps-page__workspace {
  min-width: 0;
}

.ps-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 0.85rem;
}

.ps-field__label {
  font-size: 0.82rem;
  font-weight: 600;
}

.ps-field__label small {
  font-weight: 500;
  color: var(--color-ink-faint);
}

.ps-field__input {
  width: 100%;
  padding: 0.55rem 0.65rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font: inherit;
  background: var(--color-page);
}

.ps-field__hint {
  font-size: 0.75rem;
  color: var(--color-ink-faint);
}

.ps-field__hint--over {
  color: var(--color-sale);
  font-weight: 700;
}

.ps-field__note {
  margin: 0 0 0.85rem;
  font-size: 0.8rem;
  line-height: 1.45;
  color: var(--color-ink-muted);
}

.ps-policy {
  margin: 0.5rem 0 0;
  font-size: 0.72rem;
  line-height: 1.45;
  color: var(--color-ink-faint);
}
</style>
