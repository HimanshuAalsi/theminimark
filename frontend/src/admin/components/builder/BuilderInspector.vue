<script setup lang="ts">
import { BookmarkPlus, MousePointerClick, Palette } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import BuilderProductPicker from '@/admin/components/builder/BuilderProductPicker.vue'
import BuilderSpacingControl from '@/admin/components/builder/BuilderSpacingControl.vue'
import BuilderToggle from '@/admin/components/builder/BuilderToggle.vue'
import HomeContentPoolEditors from '@/admin/components/homeContent/HomeContentPoolEditors.vue'
import type { AdminCategory, AdminSubcategory } from '@/admin/lib/adminApi'
import { CONTAINER_OPTIONS, emptySpacing, PRESET_BACKGROUNDS } from '@/lib/homePageStyle'
import { SEGMENT_CATALOG } from '@/lib/homePageLayout'
import type {
  BuilderSelectTarget,
  HomeBoxStyle,
  HomeLayoutColumn,
  HomeLayoutRow,
  HomeLayoutSection,
  HomeLayoutSegment,
  HomeProductSource,
  HomeSectionHeaderSegment,
  HomeProductGridSegment,
  HomeProductCarouselSegment,
  HomeSimpleSegment,
} from '@/types/homePageLayout'
import type { HomePageConfig } from '@/types/homePage'
import './builder-ui.css'

const POOL_SEGMENTS = {
  hero: 'hero',
  trust: 'trust',
  'category-grid': 'category',
  'personalise-grid': 'personalise',
  'how-it-works': 'how-it-works',
  newsletter: 'newsletter',
} as const

const props = defineProps<{
  target: BuilderSelectTarget | null
  section: HomeLayoutSection | null
  row: HomeLayoutRow | null
  column: HomeLayoutColumn | null
  segment: HomeLayoutSegment | null
  categories: AdminCategory[]
  subcategories: AdminSubcategory[]
  content?: HomePageConfig | null
  uploadingKey?: string | null
}>()

const emit = defineEmits<{
  patch: [Record<string, unknown>]
  savePreset: []
  'update:content': [HomePageConfig]
  imagePick: [key: string, event: Event]
}>()

const tab = ref<'content' | 'style' | 'advanced'>('content')

watch(
  () => props.target?.level,
  () => {
    tab.value = 'content'
  },
)

const level = computed(() => props.target?.level ?? null)

const title = computed(() => {
  if (!level.value) return 'Inspector'
  if (level.value === 'section') return props.section?.label || 'Section'
  if (level.value === 'row') return 'Row'
  if (level.value === 'column') return `Column (${props.column?.span ?? 12}/12)`
  if (props.segment) {
    return SEGMENT_CATALOG.find((s) => s.type === props.segment!.type)?.label ?? props.segment.type
  }
  return 'Block'
})

const styleModel = computed({
  get: (): HomeBoxStyle => {
    const node =
      level.value === 'section'
        ? props.section
        : level.value === 'row'
          ? props.row
          : level.value === 'column'
            ? props.column
            : props.segment
    return { ...(node?.style ?? {}) }
  },
  set: (v: HomeBoxStyle) => emit('patch', { style: v }),
})

function patchStyle(key: keyof HomeBoxStyle, val: unknown) {
  emit('patch', { style: { ...styleModel.value, [key]: val } })
}

const padding = computed({
  get: () => styleModel.value.padding ?? emptySpacing(),
  set: (v) => patchStyle('padding', v),
})

const margin = computed({
  get: () => styleModel.value.margin ?? emptySpacing(),
  set: (v) => patchStyle('margin', v),
})

const productSources: { v: HomeProductSource; l: string }[] = [
  { v: 'bestsellers', l: 'Bestsellers' },
  { v: 'magnetic', l: 'Magnetic bookmarks' },
  { v: 'secondary', l: 'Homepage secondary' },
  { v: 'category', l: 'By category' },
  { v: 'custom', l: 'Hand-picked products' },
  { v: 'sale', l: 'On sale' },
]

function isProductSeg(seg: HomeLayoutSegment): seg is HomeProductGridSegment | HomeProductCarouselSegment {
  return seg.type === 'product-grid' || seg.type === 'product-carousel'
}

function onCategoryChange(slug: string) {
  emit('patch', { category: slug, subcategory: '' })
}

const subcatsForCategory = computed(() => {
  const seg = props.segment
  if (!seg || !isProductSeg(seg) || !seg.category) return []
  return props.subcategories.filter((s) => s.categorySlug === seg.category)
})

function isSwatchActive(value: string) {
  return (styleModel.value.backgroundColor || '#ffffff').toLowerCase() === value.toLowerCase()
}

const poolFocus = computed(() => {
  const seg = props.segment
  if (!seg) return null
  return POOL_SEGMENTS[seg.type as keyof typeof POOL_SEGMENTS] ?? null
})
</script>

<template>
  <aside class="bld-root bld-inspector">
    <div class="bld-inspector__head">
      <span v-if="level" class="bld-inspector__badge">{{ level }}</span>
      <div class="bld-inspector__title-row">
        <h3 class="bld-inspector__title">{{ title }}</h3>
        <button v-if="target" type="button" class="bld-inspector__save" @click="emit('savePreset')">
          <BookmarkPlus :size="14" />
          Save
        </button>
      </div>
    </div>

    <div v-if="target" class="bld-tabs">
      <button type="button" class="bld-tabs__btn" :class="{ 'bld-tabs__btn--on': tab === 'content' }" @click="tab = 'content'">Content</button>
      <button type="button" class="bld-tabs__btn" :class="{ 'bld-tabs__btn--on': tab === 'style' }" @click="tab = 'style'">Style</button>
      <button type="button" class="bld-tabs__btn" :class="{ 'bld-tabs__btn--on': tab === 'advanced' }" @click="tab = 'advanced'">Advanced</button>
    </div>

    <div v-if="!target" class="bld-inspector__empty">
      <div class="bld-inspector__empty-icon"><MousePointerClick :size="20" /></div>
      <p><strong>Select an element</strong> in the canvas to edit its content, spacing, and style.</p>
      <p class="bld-field__hint">Sections → rows → columns → blocks</p>
    </div>

    <!-- CONTENT -->
    <div v-else-if="tab === 'content'" class="bld-inspector__body">
      <template v-if="level === 'section' && section">
        <div class="bld-panel">
          <label class="bld-field">
            <span class="bld-field__label">Section label</span>
            <input class="bld-input" :value="section.label" @input="emit('patch', { label: ($event.target as HTMLInputElement).value })" />
          </label>
          <label class="bld-field">
            <span class="bld-field__label">Background theme</span>
            <select class="bld-select" :value="section.theme" @change="emit('patch', { theme: ($event.target as HTMLSelectElement).value })">
              <option value="default">Default</option>
              <option value="cream">Cream</option>
              <option value="dark">Dark</option>
              <option value="custom">Custom (Style tab)</option>
            </select>
          </label>
          <label class="bld-field">
            <span class="bld-field__label">Container width</span>
            <select class="bld-select" :value="section.container || 'normal'" @change="emit('patch', { container: ($event.target as HTMLSelectElement).value })">
              <option v-for="o in CONTAINER_OPTIONS" :key="o.v" :value="o.v">{{ o.l }}</option>
            </select>
          </label>
          <BuilderToggle :checked="section.enabled" label="Visible on site" @change="emit('patch', { enabled: $event })" />
        </div>
      </template>

      <template v-else-if="level === 'column' && column">
        <div class="bld-panel">
          <label class="bld-field">
            <span class="bld-field__label">Column width</span>
            <select class="bld-select" :value="column.span" @change="emit('patch', { span: Number(($event.target as HTMLSelectElement).value) })">
              <option :value="3">3/12 — 25%</option>
              <option :value="4">4/12 — 33%</option>
              <option :value="6">6/12 — 50%</option>
              <option :value="8">8/12 — 66%</option>
              <option :value="12">12/12 — 100%</option>
            </select>
          </label>
          <label class="bld-field">
            <span class="bld-field__label">Vertical align</span>
            <select class="bld-select" :value="column.valign || 'top'" @change="emit('patch', { valign: ($event.target as HTMLSelectElement).value })">
              <option value="top">Top</option>
              <option value="center">Center</option>
              <option value="bottom">Bottom</option>
            </select>
          </label>
        </div>
      </template>

      <template v-else-if="level === 'row'">
        <div class="bld-panel">
          <p class="bld-field__hint">Rows group columns side-by-side. Open the <strong>Style</strong> tab for spacing and backgrounds.</p>
        </div>
      </template>

      <template v-else-if="segment">
        <div class="bld-panel">
          <template v-if="segment.type === 'section-header'">
            <label class="bld-field"><span class="bld-field__label">Eyebrow</span><input class="bld-input" :value="(segment as HomeSectionHeaderSegment).eyebrow" @input="emit('patch', { eyebrow: ($event.target as HTMLInputElement).value })" /></label>
            <label class="bld-field"><span class="bld-field__label">Title</span><input class="bld-input" :value="(segment as HomeSectionHeaderSegment).title" @input="emit('patch', { title: ($event.target as HTMLInputElement).value })" /></label>
            <label class="bld-field"><span class="bld-field__label">Description</span><textarea class="bld-textarea" rows="3" :value="(segment as HomeSectionHeaderSegment).description" @input="emit('patch', { description: ($event.target as HTMLTextAreaElement).value })" /></label>
            <div class="bld-panel__grid">
              <label class="bld-field">
                <span class="bld-field__label">Title size</span>
                <select class="bld-select" :value="(segment as HomeSectionHeaderSegment).titleSize || 'md'" @change="emit('patch', { titleSize: ($event.target as HTMLSelectElement).value })">
                  <option value="sm">Small</option><option value="md">Medium</option><option value="lg">Large</option><option value="xl">Extra large</option>
                </select>
              </label>
              <label class="bld-field">
                <span class="bld-field__label">Alignment</span>
                <select class="bld-select" :value="(segment as HomeSectionHeaderSegment).align || 'left'" @change="emit('patch', { align: ($event.target as HTMLSelectElement).value })">
                  <option value="left">Left</option><option value="center">Center</option>
                </select>
              </label>
            </div>
            <label class="bld-field"><span class="bld-field__label">CTA label</span><input class="bld-input" :value="(segment as HomeSectionHeaderSegment).cta?.label" @input="emit('patch', { cta: { label: ($event.target as HTMLInputElement).value, to: (segment as HomeSectionHeaderSegment).cta?.to || '/shop' } })" /></label>
            <label class="bld-field"><span class="bld-field__label">CTA link</span><input class="bld-input" :value="(segment as HomeSectionHeaderSegment).cta?.to" @input="emit('patch', { cta: { label: (segment as HomeSectionHeaderSegment).cta?.label || 'Shop', to: ($event.target as HTMLInputElement).value } })" /></label>
          </template>

          <template v-else-if="isProductSeg(segment)">
            <label class="bld-field">
              <span class="bld-field__label">Product source</span>
              <select class="bld-select" :value="segment.source" @change="emit('patch', { source: ($event.target as HTMLSelectElement).value })">
                <option v-for="s in productSources" :key="s.v" :value="s.v">{{ s.l }}</option>
              </select>
            </label>
            <template v-if="segment.source === 'category'">
              <label class="bld-field">
                <span class="bld-field__label">Category</span>
                <select class="bld-select" :value="segment.category || ''" @change="onCategoryChange(($event.target as HTMLSelectElement).value)">
                  <option value="">Select category…</option>
                  <option v-for="c in categories" :key="c.slug" :value="c.slug">{{ c.name }}</option>
                </select>
              </label>
              <label v-if="subcatsForCategory.length" class="bld-field">
                <span class="bld-field__label">Subcategory</span>
                <select class="bld-select" :value="segment.subcategory || ''" @change="emit('patch', { subcategory: ($event.target as HTMLSelectElement).value })">
                  <option value="">All in category</option>
                  <option v-for="s in subcatsForCategory" :key="s.slug" :value="s.slug">{{ s.name }}</option>
                </select>
              </label>
            </template>
            <div v-if="segment.source === 'custom'" class="bld-field">
              <span class="bld-field__label">Products</span>
              <BuilderProductPicker :model-value="segment.productIds || []" @update:model-value="emit('patch', { productIds: $event })" />
            </div>
            <div class="bld-panel__grid">
              <label class="bld-field">
                <span class="bld-field__label">Limit</span>
                <div class="bld-num-wrap"><input type="number" min="1" max="24" class="bld-input" :value="segment.limit" @input="emit('patch', { limit: Number(($event.target as HTMLInputElement).value) })" /></div>
              </label>
              <label v-if="segment.type === 'product-grid'" class="bld-field">
                <span class="bld-field__label">Grid columns</span>
                <select class="bld-select" :value="segment.columns" @change="emit('patch', { columns: Number(($event.target as HTMLSelectElement).value) })">
                  <option :value="3">3</option><option :value="4">4</option><option :value="5">5</option>
                </select>
              </label>
            </div>
            <label class="bld-field"><span class="bld-field__label">View all label</span><input class="bld-input" :value="segment.viewAllLabel" @input="emit('patch', { viewAllLabel: ($event.target as HTMLInputElement).value })" /></label>
            <label class="bld-field"><span class="bld-field__label">View all link</span><input class="bld-input" :value="segment.viewAllTo" placeholder="/shop" @input="emit('patch', { viewAllTo: ($event.target as HTMLInputElement).value })" /></label>
          </template>

          <template v-else-if="segment.type === 'sale-countdown'">
            <label class="bld-field"><span class="bld-field__label">End date</span><input type="datetime-local" class="bld-input" :value="(segment as HomeSimpleSegment).endAt?.slice(0, 16)" @input="emit('patch', { endAt: new Date(($event.target as HTMLInputElement).value).toISOString() })" /></label>
            <label class="bld-field"><span class="bld-field__label">Headline</span><input class="bld-input" :value="(segment as HomeSimpleSegment).headline" @input="emit('patch', { headline: ($event.target as HTMLInputElement).value })" /></label>
            <label class="bld-field"><span class="bld-field__label">Subheadline</span><input class="bld-input" :value="(segment as HomeSimpleSegment).subheadline" @input="emit('patch', { subheadline: ($event.target as HTMLInputElement).value })" /></label>
          </template>

          <template v-else-if="segment.type === 'blog-teaser'">
            <label class="bld-field">
              <span class="bld-field__label">Posts to show</span>
              <div class="bld-num-wrap"><input type="number" min="1" max="6" class="bld-input" :value="(segment as HomeSimpleSegment).limit" @input="emit('patch', { limit: Number(($event.target as HTMLInputElement).value) })" /></div>
            </label>
          </template>

          <template v-else-if="segment.type === 'banner'">
            <label class="bld-field"><span class="bld-field__label">Image path</span><input class="bld-input" :value="(segment as HomeSimpleSegment).image" placeholder="/uploads/..." @input="emit('patch', { image: ($event.target as HTMLInputElement).value })" /></label>
            <label class="bld-field"><span class="bld-field__label">Link</span><input class="bld-input" :value="(segment as HomeSimpleSegment).href" @input="emit('patch', { href: ($event.target as HTMLInputElement).value })" /></label>
            <label class="bld-field"><span class="bld-field__label">Alt text</span><input class="bld-input" :value="(segment as HomeSimpleSegment).alt" @input="emit('patch', { alt: ($event.target as HTMLInputElement).value })" /></label>
          </template>

          <template v-else-if="segment.type === 'spacer'">
            <label class="bld-field">
              <span class="bld-field__label">Height</span>
              <select class="bld-select" :value="(segment as HomeSimpleSegment).height || 'md'" @change="emit('patch', { height: ($event.target as HTMLSelectElement).value })">
                <option value="sm">Small</option><option value="md">Medium</option><option value="lg">Large</option>
              </select>
            </label>
          </template>

          <template v-else-if="segment.type === 'html'">
            <label class="bld-field">
              <span class="bld-field__label">Custom HTML</span>
              <textarea class="bld-textarea" rows="8" :value="(segment as HomeSimpleSegment).html" @input="emit('patch', { html: ($event.target as HTMLTextAreaElement).value })" />
            </label>
            <p class="bld-field__hint">Scripts are stripped on the public site.</p>
          </template>

          <p v-else-if="segment.type === 'create-set-promo'" class="bld-field__hint">
            Promo cards for <strong>Bookmark set</strong> and <strong>Build a hamper</strong>. Open
            <code>/create-your-set</code> to change pricing tiers in code if needed.
          </p>
          <p v-else-if="poolFocus && content" class="bld-field__hint">
            Edit text, images, and links below. Changes apply site-wide for this block.
          </p>
          <HomeContentPoolEditors
            v-if="poolFocus && content"
            :model-value="content"
            :uploading-key="uploadingKey"
            :focus="poolFocus"
            @update:model-value="emit('update:content', $event)"
            @image-pick="(key, e) => emit('imagePick', key, e)"
          />
          <p v-else-if="poolFocus && !content" class="bld-field__hint">
            Open the <strong>Content pools</strong> tab to edit this block.
          </p>
        </div>
      </template>
    </div>

    <!-- STYLE -->
    <div v-else-if="tab === 'style'" class="bld-inspector__body">
      <div class="bld-panel">
        <p class="bld-panel__title"><Palette :size="12" style="display:inline;vertical-align:-2px;margin-right:4px" /> Background</p>
        <label class="bld-field">
          <span class="bld-field__label">Color</span>
          <div class="bld-swatches">
            <button
              v-for="bg in PRESET_BACKGROUNDS"
              :key="bg.value"
              type="button"
              class="bld-swatch"
              :class="{ 'bld-swatch--active': isSwatchActive(bg.value) }"
              :style="{ background: bg.value }"
              :title="bg.label"
              @click="patchStyle('backgroundColor', bg.value)"
            />
            <label class="bld-color-picker" title="Custom color">
              <input type="color" :value="styleModel.backgroundColor || '#ffffff'" @input="patchStyle('backgroundColor', ($event.target as HTMLInputElement).value)" />
            </label>
          </div>
        </label>
        <label class="bld-field">
          <span class="bld-field__label">Background image URL</span>
          <input class="bld-input" :value="styleModel.backgroundImage || ''" placeholder="/uploads/banner.jpg" @input="patchStyle('backgroundImage', ($event.target as HTMLInputElement).value)" />
        </label>
      </div>

      <div class="bld-panel">
        <p class="bld-panel__title">Spacing</p>
        <BuilderSpacingControl v-model="padding">Padding</BuilderSpacingControl>
        <div style="height:0.75rem" />
        <BuilderSpacingControl v-model="margin">Margin</BuilderSpacingControl>
      </div>

      <div class="bld-panel">
        <p class="bld-panel__title">Border & layout</p>
        <div class="bld-panel__grid">
          <label class="bld-field">
            <span class="bld-field__label">Radius</span>
            <div class="bld-num-wrap"><input type="number" min="0" max="64" class="bld-input" :value="styleModel.borderRadius ?? 0" @input="patchStyle('borderRadius', Number(($event.target as HTMLInputElement).value))" /><span class="bld-num-wrap__unit">px</span></div>
          </label>
          <label class="bld-field">
            <span class="bld-field__label">Border</span>
            <div class="bld-num-wrap"><input type="number" min="0" max="8" class="bld-input" :value="styleModel.borderWidth ?? 0" @input="patchStyle('borderWidth', Number(($event.target as HTMLInputElement).value))" /><span class="bld-num-wrap__unit">px</span></div>
          </label>
          <label class="bld-field">
            <span class="bld-field__label">Border color</span>
            <div class="bld-color-field">
              <label class="bld-color-field__preview" :style="{ background: styleModel.borderColor || '#e2e8f0' }">
                <input type="color" :value="styleModel.borderColor || '#e2e8f0'" @input="patchStyle('borderColor', ($event.target as HTMLInputElement).value)" />
              </label>
              <input class="bld-input" :value="styleModel.borderColor || '#e2e8f0'" @input="patchStyle('borderColor', ($event.target as HTMLInputElement).value)" />
            </div>
          </label>
          <label class="bld-field">
            <span class="bld-field__label">Shadow</span>
            <select class="bld-select" :value="styleModel.boxShadow || 'none'" @change="patchStyle('boxShadow', ($event.target as HTMLSelectElement).value)">
              <option value="none">None</option><option value="sm">Small</option><option value="md">Medium</option><option value="lg">Large</option>
            </select>
          </label>
          <label class="bld-field">
            <span class="bld-field__label">Min height</span>
            <div class="bld-num-wrap"><input type="number" min="0" max="800" class="bld-input" :value="styleModel.minHeight ?? 0" @input="patchStyle('minHeight', Number(($event.target as HTMLInputElement).value))" /><span class="bld-num-wrap__unit">px</span></div>
          </label>
          <label class="bld-field">
            <span class="bld-field__label">Gap</span>
            <div class="bld-num-wrap"><input type="number" min="0" max="80" class="bld-input" :value="styleModel.gap ?? 0" @input="patchStyle('gap', Number(($event.target as HTMLInputElement).value))" /><span class="bld-num-wrap__unit">px</span></div>
          </label>
        </div>
        <label class="bld-field">
          <span class="bld-field__label">Text align</span>
          <select class="bld-select" :value="styleModel.textAlign || 'left'" @change="patchStyle('textAlign', ($event.target as HTMLSelectElement).value)">
            <option value="left">Left</option><option value="center">Center</option><option value="right">Right</option>
          </select>
        </label>
      </div>
    </div>

    <!-- ADVANCED -->
    <div v-else class="bld-inspector__body">
      <div class="bld-panel">
        <label class="bld-field">
          <span class="bld-field__label">CSS class</span>
          <input class="bld-input" :value="styleModel.customClass || ''" placeholder="my-custom-block" @input="patchStyle('customClass', ($event.target as HTMLInputElement).value)" />
        </label>
        <BuilderToggle :checked="!!styleModel.hideOnMobile" label="Hide on mobile" @change="patchStyle('hideOnMobile', $event)" />
        <BuilderToggle :checked="!!styleModel.hideOnDesktop" label="Hide on desktop" @change="patchStyle('hideOnDesktop', $event)" />
      </div>
    </div>
  </aside>
</template>
