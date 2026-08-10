<script setup lang="ts">
import { ImagePlus, Plus, Trash2 } from 'lucide-vue-next'
import { adminImageSrc } from '@/admin/lib/adminApi'
import type {
  HomePageCategoryTile,
  HomePageConfig,
  HomePageHeroSlide,
  HomePageHowItWorksStep,
  HomePagePersonaliseCard,
  HomePageTrustIcon,
  HomePageTrustItem,
} from '@/types/homePage'

const props = withDefaults(
  defineProps<{
    modelValue: HomePageConfig
    uploadingKey?: string | null
    /** Show only one pool (for builder inspector) */
    focus?: 'hero' | 'category' | 'personalise' | 'trust' | 'how-it-works' | 'newsletter' | 'all'
  }>(),
  { uploadingKey: null, focus: 'all' },
)

const emit = defineEmits<{
  'update:modelValue': [HomePageConfig]
  imagePick: [key: string, event: Event]
}>()

const trustIcons: { v: HomePageTrustIcon; l: string }[] = [
  { v: 'truck', l: 'Delivery' },
  { v: 'payment', l: 'Payment' },
  { v: 'offer', l: 'Offer' },
  { v: 'return', l: 'Return' },
  { v: 'lock', l: 'Secure' },
  { v: 'chat', l: 'Support' },
]

function show(section: typeof props.focus) {
  return props.focus === 'all' || props.focus === section
}

function patch(partial: Partial<HomePageConfig>) {
  emit('update:modelValue', { ...props.modelValue, ...partial })
}

function patchHero(i: number, partial: Partial<HomePageHeroSlide>) {
  const slides = props.modelValue.heroSlides.map((s, idx) => (idx === i ? { ...s, ...partial } : s))
  patch({ heroSlides: slides })
}

function patchHeroCta(i: number, which: 'ctaPrimary' | 'ctaSecondary', field: 'label' | 'to', val: string) {
  const slide = props.modelValue.heroSlides[i]
  if (!slide) return
  patchHero(i, { [which]: { ...slide[which], [field]: val } })
}

function addHeroSlide() {
  if (props.modelValue.heroSlides.length >= 8) return
  const last = props.modelValue.heroSlides.at(-1)
  patch({
    heroSlides: [
      ...props.modelValue.heroSlides,
      {
        eyebrow: 'New slide',
        tabLabel: 'Category',
        title: 'Headline here',
        text: 'Short description for this slide.',
        image: last?.image ?? '/products/magnetic-bookmarks.jpeg',
        ctaPrimary: { label: 'Shop now', to: '/shop' },
        ctaSecondary: { label: 'Learn more', to: '/shop' },
      },
    ],
  })
}

function removeHeroSlide(i: number) {
  if (props.modelValue.heroSlides.length <= 1) return
  patch({ heroSlides: props.modelValue.heroSlides.filter((_, idx) => idx !== i) })
}

function patchCategory(i: number, partial: Partial<HomePageCategoryTile>) {
  const strip = props.modelValue.categoryStrip.map((c, idx) => (idx === i ? { ...c, ...partial } : c))
  patch({ categoryStrip: strip })
}

function addCategoryTile() {
  if (props.modelValue.categoryStrip.length >= 12) return
  patch({
    categoryStrip: [
      ...props.modelValue.categoryStrip,
      { title: 'New category', blurb: 'Short blurb', href: '/shop', image: '/products/magnetic-bookmarks.jpeg' },
    ],
  })
}

function removeCategoryTile(i: number) {
  if (props.modelValue.categoryStrip.length <= 1) return
  patch({ categoryStrip: props.modelValue.categoryStrip.filter((_, idx) => idx !== i) })
}

function patchPersonalise(i: number, partial: Partial<HomePagePersonaliseCard>) {
  const cards = props.modelValue.personaliseCards.map((c, idx) => (idx === i ? { ...c, ...partial } : c))
  patch({ personaliseCards: cards })
}

function addPersonaliseCard() {
  if (props.modelValue.personaliseCards.length >= 4) return
  const ids = ['bookmark', 'calendar', 'card', 'magnet'] as const
  const used = new Set(props.modelValue.personaliseCards.map((c) => c.id))
  const id = ids.find((x) => !used.has(x))
  if (!id) return
  patch({
    personaliseCards: [
      ...props.modelValue.personaliseCards,
      { id, shortLabel: 'New card', blurb: 'Description', image: '/products/magnetic-bookmarks.jpeg' },
    ],
  })
}

function removePersonaliseCard(i: number) {
  if (props.modelValue.personaliseCards.length <= 1) return
  patch({ personaliseCards: props.modelValue.personaliseCards.filter((_, idx) => idx !== i) })
}

function patchTrust(i: number, partial: Partial<HomePageTrustItem>) {
  const items = props.modelValue.trustItems.map((t, idx) => (idx === i ? { ...t, ...partial } : t))
  patch({ trustItems: items })
}

function addTrustItem() {
  if (props.modelValue.trustItems.length >= 6) return
  patch({
    trustItems: [...props.modelValue.trustItems, { title: 'New perk', text: 'Description', icon: 'chat' }],
  })
}

function removeTrustItem(i: number) {
  if (props.modelValue.trustItems.length <= 1) return
  patch({ trustItems: props.modelValue.trustItems.filter((_, idx) => idx !== i) })
}

function patchHowStep(i: number, partial: Partial<HomePageHowItWorksStep>) {
  const steps = props.modelValue.howItWorksSteps.map((s, idx) => (idx === i ? { ...s, ...partial } : s))
  patch({ howItWorksSteps: steps })
}

function addHowStep() {
  if (props.modelValue.howItWorksSteps.length >= 5) return
  const n = props.modelValue.howItWorksSteps.length + 1
  patch({
    howItWorksSteps: [
      ...props.modelValue.howItWorksSteps,
      { step: String(n), title: 'New step', text: 'Description', ctaLabel: 'Learn more', ctaTo: '/shop' },
    ],
  })
}

function removeHowStep(i: number) {
  if (props.modelValue.howItWorksSteps.length <= 1) return
  patch({ howItWorksSteps: props.modelValue.howItWorksSteps.filter((_, idx) => idx !== i) })
}

function imgSrc(path: string) {
  return path ? adminImageSrc(path) : ''
}
</script>

<template>
  <div class="hp-content">
    <!-- Hero slides -->
    <section v-if="show('hero')" class="hp-content__section">
      <div class="hp-content__head">
        <h2 class="hp-content__title">Hero carousel</h2>
        <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" :disabled="modelValue.heroSlides.length >= 8" @click="addHeroSlide">
          <Plus :size="14" /> Add slide
        </button>
      </div>
      <p class="hp-content__hint">Eyebrow, headline, body, tab label, image, and both buttons — everything in the hero you see on the homepage.</p>

      <article v-for="(slide, i) in modelValue.heroSlides" :key="i" class="hp-content__card">
        <div class="hp-content__card-head">
          <strong>Slide {{ i + 1 }} · {{ slide.tabLabel || 'Untitled' }}</strong>
          <button v-if="modelValue.heroSlides.length > 1" type="button" class="hp-content__icon-btn" title="Remove slide" @click="removeHeroSlide(i)">
            <Trash2 :size="14" />
          </button>
        </div>
        <div class="hp-content__grid hp-content__grid--media">
          <div class="hp-content__thumb">
            <img v-if="slide.image" :src="imgSrc(slide.image)" alt="" />
            <label class="hp-upload-btn hp-upload-btn--sm">
              <ImagePlus :size="14" />
              {{ uploadingKey === `hero-${i}` ? 'Uploading…' : 'Change image' }}
              <input type="file" accept="image/*" hidden @change="emit('imagePick', `hero-${i}`, $event)" />
            </label>
          </div>
          <div class="hp-content__fields">
            <label class="admin-field">
              <span class="admin-field__label">Tab label</span>
              <input class="admin-input" :value="slide.tabLabel" maxlength="40" @input="patchHero(i, { tabLabel: ($event.target as HTMLInputElement).value })" />
            </label>
            <label class="admin-field">
              <span class="admin-field__label">Eyebrow</span>
              <input class="admin-input" :value="slide.eyebrow" maxlength="80" @input="patchHero(i, { eyebrow: ($event.target as HTMLInputElement).value })" />
            </label>
            <label class="admin-field">
              <span class="admin-field__label">Headline</span>
              <input class="admin-input" :value="slide.title" maxlength="200" @input="patchHero(i, { title: ($event.target as HTMLInputElement).value })" />
            </label>
            <label class="admin-field">
              <span class="admin-field__label">Description</span>
              <textarea class="admin-input" rows="3" :value="slide.text" maxlength="500" @input="patchHero(i, { text: ($event.target as HTMLTextAreaElement).value })" />
            </label>
            <div class="hp-content__row">
              <label class="admin-field">
                <span class="admin-field__label">Primary button</span>
                <input class="admin-input" :value="slide.ctaPrimary.label" maxlength="60" @input="patchHeroCta(i, 'ctaPrimary', 'label', ($event.target as HTMLInputElement).value)" />
              </label>
              <label class="admin-field">
                <span class="admin-field__label">Primary link</span>
                <input class="admin-input" :value="slide.ctaPrimary.to" maxlength="200" @input="patchHeroCta(i, 'ctaPrimary', 'to', ($event.target as HTMLInputElement).value)" />
              </label>
            </div>
            <div class="hp-content__row">
              <label class="admin-field">
                <span class="admin-field__label">Secondary button</span>
                <input class="admin-input" :value="slide.ctaSecondary.label" maxlength="60" @input="patchHeroCta(i, 'ctaSecondary', 'label', ($event.target as HTMLInputElement).value)" />
              </label>
              <label class="admin-field">
                <span class="admin-field__label">Secondary link</span>
                <input class="admin-input" :value="slide.ctaSecondary.to" maxlength="200" @input="patchHeroCta(i, 'ctaSecondary', 'to', ($event.target as HTMLInputElement).value)" />
              </label>
            </div>
          </div>
        </div>
      </article>
    </section>

    <!-- Category tiles -->
    <section v-if="show('category')" class="hp-content__section">
      <div class="hp-content__head">
        <h2 class="hp-content__title">Category tiles</h2>
        <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" :disabled="modelValue.categoryStrip.length >= 12" @click="addCategoryTile">
          <Plus :size="14" /> Add tile
        </button>
      </div>
      <article v-for="(cat, i) in modelValue.categoryStrip" :key="i" class="hp-content__card hp-content__card--compact">
        <div class="hp-content__card-head">
          <strong>{{ cat.title || `Tile ${i + 1}` }}</strong>
          <button v-if="modelValue.categoryStrip.length > 1" type="button" class="hp-content__icon-btn" @click="removeCategoryTile(i)"><Trash2 :size="14" /></button>
        </div>
        <div class="hp-content__grid hp-content__grid--media">
          <div class="hp-content__thumb hp-content__thumb--sm">
            <img v-if="cat.image" :src="imgSrc(cat.image)" alt="" />
            <label class="hp-upload-btn hp-upload-btn--sm">
              <ImagePlus :size="14" />
              <input type="file" accept="image/*" hidden @change="emit('imagePick', `cat-${i}`, $event)" />
            </label>
          </div>
          <div class="hp-content__fields">
            <label class="admin-field"><span class="admin-field__label">Title</span><input class="admin-input" :value="cat.title" @input="patchCategory(i, { title: ($event.target as HTMLInputElement).value })" /></label>
            <label class="admin-field"><span class="admin-field__label">Blurb</span><input class="admin-input" :value="cat.blurb" @input="patchCategory(i, { blurb: ($event.target as HTMLInputElement).value })" /></label>
            <label class="admin-field"><span class="admin-field__label">Link</span><input class="admin-input" :value="cat.href" @input="patchCategory(i, { href: ($event.target as HTMLInputElement).value })" /></label>
          </div>
        </div>
      </article>
    </section>

    <!-- Personalise cards -->
    <section v-if="show('personalise')" class="hp-content__section">
      <div class="hp-content__head">
        <h2 class="hp-content__title">Custom bookmark card</h2>
        <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" :disabled="modelValue.personaliseCards.length >= 4" @click="addPersonaliseCard">
          <Plus :size="14" /> Add card
        </button>
      </div>
      <article v-for="(card, i) in modelValue.personaliseCards" :key="card.id" class="hp-content__card hp-content__card--compact">
        <div class="hp-content__card-head">
          <strong>{{ card.shortLabel }}</strong>
          <button v-if="modelValue.personaliseCards.length > 1" type="button" class="hp-content__icon-btn" @click="removePersonaliseCard(i)"><Trash2 :size="14" /></button>
        </div>
        <div class="hp-content__grid hp-content__grid--media">
          <div class="hp-content__thumb hp-content__thumb--sm">
            <img v-if="card.image" :src="imgSrc(card.image)" alt="" />
            <label class="hp-upload-btn hp-upload-btn--sm">
              <ImagePlus :size="14" />
              <input type="file" accept="image/*" hidden @change="emit('imagePick', `pers-${i}`, $event)" />
            </label>
          </div>
          <div class="hp-content__fields">
            <label class="admin-field"><span class="admin-field__label">Title</span><input class="admin-input" :value="card.shortLabel" @input="patchPersonalise(i, { shortLabel: ($event.target as HTMLInputElement).value })" /></label>
            <label class="admin-field"><span class="admin-field__label">Description</span><textarea class="admin-input" rows="2" :value="card.blurb" @input="patchPersonalise(i, { blurb: ($event.target as HTMLTextAreaElement).value })" /></label>
          </div>
        </div>
      </article>
    </section>

    <!-- Trust strip -->
    <section v-if="show('trust')" class="hp-content__section">
      <div class="hp-content__head">
        <h2 class="hp-content__title">Trust strip</h2>
        <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" :disabled="modelValue.trustItems.length >= 6" @click="addTrustItem">
          <Plus :size="14" /> Add item
        </button>
      </div>
      <article v-for="(item, i) in modelValue.trustItems" :key="i" class="hp-content__card hp-content__card--compact">
        <div class="hp-content__card-head">
          <strong>{{ item.title || `Item ${i + 1}` }}</strong>
          <button v-if="modelValue.trustItems.length > 1" type="button" class="hp-content__icon-btn" @click="removeTrustItem(i)"><Trash2 :size="14" /></button>
        </div>
        <div class="hp-content__fields">
          <label class="admin-field"><span class="admin-field__label">Title</span><input class="admin-input" :value="item.title" @input="patchTrust(i, { title: ($event.target as HTMLInputElement).value })" /></label>
          <label class="admin-field"><span class="admin-field__label">Text</span><textarea class="admin-input" rows="2" :value="item.text" @input="patchTrust(i, { text: ($event.target as HTMLTextAreaElement).value })" /></label>
          <label class="admin-field">
            <span class="admin-field__label">Icon</span>
            <select class="admin-input" :value="item.icon" @change="patchTrust(i, { icon: ($event.target as HTMLSelectElement).value as HomePageTrustIcon })">
              <option v-for="ic in trustIcons" :key="ic.v" :value="ic.v">{{ ic.l }}</option>
            </select>
          </label>
        </div>
      </article>
    </section>

    <!-- How it works -->
    <section v-if="show('how-it-works')" class="hp-content__section">
      <h2 class="hp-content__title">How it works</h2>
      <div class="hp-content__intro">
        <label class="admin-field"><span class="admin-field__label">Eyebrow</span><input class="admin-input" :value="modelValue.howItWorksIntro.eyebrow" @input="patch({ howItWorksIntro: { ...modelValue.howItWorksIntro, eyebrow: ($event.target as HTMLInputElement).value } })" /></label>
        <label class="admin-field"><span class="admin-field__label">Section title</span><input class="admin-input" :value="modelValue.howItWorksIntro.title" @input="patch({ howItWorksIntro: { ...modelValue.howItWorksIntro, title: ($event.target as HTMLInputElement).value } })" /></label>
        <label class="admin-field"><span class="admin-field__label">Section description</span><textarea class="admin-input" rows="2" :value="modelValue.howItWorksIntro.description" @input="patch({ howItWorksIntro: { ...modelValue.howItWorksIntro, description: ($event.target as HTMLTextAreaElement).value } })" /></label>
      </div>
      <div class="hp-content__head">
        <h3 class="hp-content__subtitle">Steps</h3>
        <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" :disabled="modelValue.howItWorksSteps.length >= 5" @click="addHowStep">
          <Plus :size="14" /> Add step
        </button>
      </div>
      <article v-for="(step, i) in modelValue.howItWorksSteps" :key="i" class="hp-content__card hp-content__card--compact">
        <div class="hp-content__card-head">
          <strong>Step {{ step.step }}</strong>
          <button v-if="modelValue.howItWorksSteps.length > 1" type="button" class="hp-content__icon-btn" @click="removeHowStep(i)"><Trash2 :size="14" /></button>
        </div>
        <div class="hp-content__fields">
          <label class="admin-field"><span class="admin-field__label">Step number</span><input class="admin-input" :value="step.step" maxlength="4" @input="patchHowStep(i, { step: ($event.target as HTMLInputElement).value })" /></label>
          <label class="admin-field"><span class="admin-field__label">Title</span><input class="admin-input" :value="step.title" @input="patchHowStep(i, { title: ($event.target as HTMLInputElement).value })" /></label>
          <label class="admin-field"><span class="admin-field__label">Description</span><textarea class="admin-input" rows="2" :value="step.text" @input="patchHowStep(i, { text: ($event.target as HTMLTextAreaElement).value })" /></label>
          <div class="hp-content__row">
            <label class="admin-field"><span class="admin-field__label">Button label</span><input class="admin-input" :value="step.ctaLabel" @input="patchHowStep(i, { ctaLabel: ($event.target as HTMLInputElement).value })" /></label>
            <label class="admin-field"><span class="admin-field__label">Button link</span><input class="admin-input" :value="step.ctaTo" @input="patchHowStep(i, { ctaTo: ($event.target as HTMLInputElement).value })" /></label>
          </div>
        </div>
      </article>
    </section>

    <!-- Newsletter -->
    <section v-if="show('newsletter')" class="hp-content__section">
      <h2 class="hp-content__title">Newsletter</h2>
      <div class="hp-content__fields">
        <label class="admin-field"><span class="admin-field__label">Eyebrow</span><input class="admin-input" :value="modelValue.newsletter.eyebrow" @input="patch({ newsletter: { ...modelValue.newsletter, eyebrow: ($event.target as HTMLInputElement).value } })" /></label>
        <label class="admin-field"><span class="admin-field__label">Title</span><input class="admin-input" :value="modelValue.newsletter.title" @input="patch({ newsletter: { ...modelValue.newsletter, title: ($event.target as HTMLInputElement).value } })" /></label>
        <label class="admin-field"><span class="admin-field__label">Description</span><textarea class="admin-input" rows="2" :value="modelValue.newsletter.description" @input="patch({ newsletter: { ...modelValue.newsletter, description: ($event.target as HTMLTextAreaElement).value } })" /></label>
        <div class="hp-content__row">
          <label class="admin-field"><span class="admin-field__label">Email placeholder</span><input class="admin-input" :value="modelValue.newsletter.placeholder" @input="patch({ newsletter: { ...modelValue.newsletter, placeholder: ($event.target as HTMLInputElement).value } })" /></label>
          <label class="admin-field"><span class="admin-field__label">Button label</span><input class="admin-input" :value="modelValue.newsletter.buttonLabel" @input="patch({ newsletter: { ...modelValue.newsletter, buttonLabel: ($event.target as HTMLInputElement).value } })" /></label>
        </div>
        <label class="admin-field"><span class="admin-field__label">Fine print</span><input class="admin-input" :value="modelValue.newsletter.finePrint" @input="patch({ newsletter: { ...modelValue.newsletter, finePrint: ($event.target as HTMLInputElement).value } })" /></label>
      </div>
    </section>
  </div>
</template>

<style scoped>
.hp-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.hp-content__section {
  padding: 1rem;
  border: 1px solid var(--admin-border);
  border-radius: 12px;
  background: #fff;
}

.hp-content__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.65rem;
}

.hp-content__title {
  margin: 0 0 0.35rem;
  font-size: 0.9375rem;
  font-weight: 700;
}

.hp-content__subtitle {
  margin: 0;
  font-size: 0.8125rem;
  font-weight: 700;
}

.hp-content__hint {
  margin: 0 0 0.85rem;
  font-size: 0.75rem;
  color: var(--admin-muted);
  line-height: 1.45;
}

.hp-content__card {
  padding: 0.85rem;
  margin-bottom: 0.65rem;
  border: 1px solid var(--admin-border);
  border-radius: 10px;
  background: var(--admin-surface, #fafafa);
}

.hp-content__card:last-child {
  margin-bottom: 0;
}

.hp-content__card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.65rem;
  font-size: 0.8125rem;
}

.hp-content__icon-btn {
  display: grid;
  place-items: center;
  width: 1.75rem;
  height: 1.75rem;
  border: 1px solid var(--admin-border);
  border-radius: 8px;
  background: #fff;
  color: #b91c1c;
  cursor: pointer;
}

.hp-content__grid {
  display: grid;
  gap: 0.85rem;
}

.hp-content__grid--media {
  grid-template-columns: minmax(7rem, 9rem) minmax(0, 1fr);
}

@media (max-width: 640px) {
  .hp-content__grid--media {
    grid-template-columns: 1fr;
  }
}

.hp-content__thumb img {
  width: 100%;
  aspect-ratio: 4/3;
  object-fit: cover;
  border-radius: 8px;
  margin-bottom: 0.35rem;
}

.hp-content__thumb--sm img {
  aspect-ratio: 1;
}

.hp-content__fields {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.hp-content__row {
  display: grid;
  gap: 0.55rem;
  grid-template-columns: 1fr 1fr;
}

@media (max-width: 720px) {
  .hp-content__row {
    grid-template-columns: 1fr;
  }
}

.hp-content__intro {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  margin-bottom: 0.85rem;
  padding-bottom: 0.85rem;
  border-bottom: 1px solid var(--admin-border);
}
</style>
