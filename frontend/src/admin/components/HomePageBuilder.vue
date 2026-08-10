<script setup lang="ts">
import {
  Copy,
  Eye,
  EyeOff,
  GripVertical,
  Layers,
  LayoutGrid,
  Library,
  Plus,
  Rows3,
  Trash2,
  Columns3,
} from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import BuilderInspector from '@/admin/components/builder/BuilderInspector.vue'
import {
  adminListCategories,
  adminListSubcategories,
  type AdminCategory,
  type AdminSubcategory,
} from '@/admin/lib/adminApi'
import {
  COLUMN_SPAN_OPTIONS,
  createSegment,
  hpId,
  moveItem,
  row,
  col,
  section,
  SEGMENT_CATALOG,
} from '@/lib/homePageLayout'
import type {
  BuilderSelectTarget,
  HomeLayoutColumn,
  HomeLayoutPreset,
  HomeLayoutRow,
  HomeLayoutSection,
  HomeLayoutSegment,
  HomePageLayout,
  HomeSegmentType,
} from '@/types/homePageLayout'
import type { HomePageConfig } from '@/types/homePage'
import '@/admin/components/builder/builder-ui.css'

const layout = defineModel<HomePageLayout>({ required: true })

const props = defineProps<{
  content?: HomePageConfig | null
  uploadingKey?: string | null
}>()

const emit = defineEmits<{
  'update:content': [HomePageConfig]
  imagePick: [key: string, event: Event]
}>()

const selectTarget = ref<BuilderSelectTarget | null>(null)
const dragSectionIdx = ref<number | null>(null)
const categories = ref<AdminCategory[]>([])
const subcategories = ref<AdminSubcategory[]>([])

const selectedSection = computed(() => {
  const id = selectTarget.value?.sectionId ?? layout.value.sections[0]?.id
  return layout.value.sections.find((s) => s.id === id) ?? null
})

const selectedRow = computed(() => {
  const t = selectTarget.value
  if (!selectedSection.value || !t || !('rowId' in t)) return null
  return selectedSection.value.rows.find((r) => r.id === t.rowId) ?? null
})

const selectedColumn = computed(() => {
  const t = selectTarget.value
  if (!selectedRow.value || !t || !('colId' in t)) return null
  return selectedRow.value.columns.find((c) => c.id === t.colId) ?? null
})

const selectedSegment = computed(() => {
  const t = selectTarget.value
  if (!selectedColumn.value || !t || !('segId' in t)) return null
  return selectedColumn.value.segments.find((s) => s.id === t.segId) ?? null
})

const presets = computed(() => layout.value.presets ?? [])

onMounted(async () => {
  try {
    const [catRes, subRes] = await Promise.all([adminListCategories(), adminListSubcategories()])
    categories.value = catRes.items.filter((c) => c.isActive)
    subcategories.value = subRes.items.filter((s) => s.isActive)
  } catch {
    /* admin may be offline in dev */
  }
  if (layout.value.sections[0] && !selectTarget.value) {
    selectTarget.value = { level: 'section', sectionId: layout.value.sections[0].id }
  }
})

function selectSection(id: string) {
  selectTarget.value = { level: 'section', sectionId: id }
}

function selectRow(sectionId: string, rowId: string) {
  selectTarget.value = { level: 'row', sectionId, rowId }
}

function selectColumn(sectionId: string, rowId: string, colId: string) {
  selectTarget.value = { level: 'column', sectionId, rowId, colId }
}

function selectSegment(sectionId: string, rowId: string, colId: string, segId: string) {
  selectTarget.value = { level: 'segment', sectionId, rowId, colId, segId }
}

function addSection() {
  const s = section('New section', [createSegment('section-header')])
  layout.value = { ...layout.value, sections: [...layout.value.sections, s] }
  selectSection(s.id)
}

function duplicateSection(sec: HomeLayoutSection) {
  const copy = structuredClone(sec)
  copy.id = hpId('sec')
  copy.label = `${sec.label} copy`
  const reid = (seg: HomeLayoutSegment) => ({ ...seg, id: hpId('seg') })
  copy.rows = copy.rows.map((r) => ({
    ...r,
    id: hpId('row'),
    columns: r.columns.map((c) => ({
      ...c,
      id: hpId('col'),
      segments: c.segments.map(reid),
    })),
  }))
  layout.value = { ...layout.value, sections: [...layout.value.sections, copy] }
}

function removeSection(id: string) {
  if (!confirm('Remove this section?')) return
  layout.value = {
    ...layout.value,
    sections: layout.value.sections.filter((s) => s.id !== id),
  }
  if (selectTarget.value?.sectionId === id) {
    const first = layout.value.sections[0]
    selectTarget.value = first ? { level: 'section', sectionId: first.id } : null
  }
}

function moveSection(from: number, to: number) {
  layout.value = { ...layout.value, sections: moveItem(layout.value.sections, from, to) }
}

function onSectionDragStart(i: number) {
  dragSectionIdx.value = i
}

function onSectionDrop(i: number) {
  if (dragSectionIdx.value === null) return
  moveSection(dragSectionIdx.value, i)
  dragSectionIdx.value = null
}

function toggleSection(id: string) {
  const sec = layout.value.sections.find((s) => s.id === id)
  if (sec) sec.enabled = !sec.enabled
}

function findRow(secId: string, rowId: string): HomeLayoutRow | undefined {
  return layout.value.sections.find((s) => s.id === secId)?.rows.find((r) => r.id === rowId)
}

function addRow(secId: string) {
  const sec = layout.value.sections.find((s) => s.id === secId)
  if (!sec) return
  const r = row([col(12, [])])
  sec.rows.push(r)
  selectRow(secId, r.id)
}

function addColumn(secId: string, rowId: string) {
  const r = findRow(secId, rowId)
  if (!r) return
  const used = r.columns.reduce((n, c) => n + c.span, 0)
  const span = used >= 12 ? 6 : ((12 - used >= 6 ? 6 : 12 - used) as HomeLayoutColumn['span'])
  if (used >= 12) {
    r.columns = [{ id: hpId('col'), span: 6, segments: r.columns.flatMap((c) => c.segments) }, col(6, [])]
    selectColumn(secId, rowId, r.columns[1].id)
  } else {
    const c = col(span, [])
    r.columns.push(c)
    selectColumn(secId, rowId, c.id)
  }
}

function addSegment(secId: string, rowId: string, colId: string, type: HomeSegmentType) {
  const r = findRow(secId, rowId)
  const c = r?.columns.find((x) => x.id === colId)
  if (!c) return
  const seg = createSegment(type)
  c.segments.push(seg)
  selectSegment(secId, rowId, colId, seg.id)
}

function removeSegment(secId: string, rowId: string, colId: string, segId: string) {
  const c = findRow(secId, rowId)?.columns.find((x) => x.id === colId)
  if (!c) return
  c.segments = c.segments.filter((s) => s.id !== segId)
  const t = selectTarget.value
  if (t && 'segId' in t && t.segId === segId) {
    selectTarget.value = { level: 'column', sectionId: secId, rowId, colId }
  }
}

function segmentLabel(type: string): string {
  return SEGMENT_CATALOG.find((s) => s.type === type)?.label ?? type
}

function patchTarget(patch: Record<string, unknown>) {
  const t = selectTarget.value
  if (!t) return
  const sec = layout.value.sections.find((s) => s.id === t.sectionId)
  if (!sec) return

  if (t.level === 'section') {
    Object.assign(sec, patch)
    return
  }
  const r = sec.rows.find((x) => x.id === t.rowId)
  if (!r) return
  if (t.level === 'row') {
    Object.assign(r, patch)
    return
  }
  const c = r.columns.find((x) => x.id === t.colId)
  if (!c) return
  if (t.level === 'column') {
    Object.assign(c, patch)
    return
  }
  const seg = c.segments.find((x) => x.id === t.segId)
  if (seg) Object.assign(seg, patch)
}

function savePreset() {
  const t = selectTarget.value
  if (!t) return
  const name = prompt('Component name')
  if (!name?.trim()) return

  let kind: HomeLayoutPreset['kind']
  let payload: HomeLayoutPreset['payload']

  if (t.level === 'section' && selectedSection.value) {
    kind = 'section'
    payload = structuredClone(selectedSection.value)
  } else if (t.level === 'row' && selectedRow.value) {
    kind = 'row'
    payload = structuredClone(selectedRow.value)
  } else if (t.level === 'segment' && selectedSegment.value) {
    kind = 'segment'
    payload = structuredClone(selectedSegment.value)
  } else {
    alert('Save sections, rows, or blocks as reusable components.')
    return
  }

  const preset: HomeLayoutPreset = { id: hpId('preset'), name: name.trim(), kind, payload }
  layout.value = {
    ...layout.value,
    presets: [...(layout.value.presets ?? []), preset],
  }
}

function insertPreset(preset: HomeLayoutPreset) {
  const sec = selectedSection.value
  if (!sec) return
  const clone = structuredClone(preset.payload)
  if (preset.kind === 'section') {
    const s = clone as HomeLayoutSection
    s.id = hpId('sec')
    s.label = preset.name
    layout.value = { ...layout.value, sections: [...layout.value.sections, s] }
    selectSection(s.id)
  } else if (preset.kind === 'row') {
    const r = clone as HomeLayoutRow
    r.id = hpId('row')
    r.columns = r.columns.map((c) => ({
      ...c,
      id: hpId('col'),
      segments: c.segments.map((s) => ({ ...s, id: hpId('seg') })),
    }))
    sec.rows.push(r)
    selectRow(sec.id, r.id)
  } else {
    const seg = clone as HomeLayoutSegment
    seg.id = hpId('seg')
    const lastRow = sec.rows[sec.rows.length - 1]
    const lastCol = lastRow?.columns[lastRow.columns.length - 1]
    if (lastCol) {
      lastCol.segments.push(seg)
      selectSegment(sec.id, lastRow.id, lastCol.id, seg.id)
    }
  }
}

function removePreset(id: string) {
  layout.value = {
    ...layout.value,
    presets: (layout.value.presets ?? []).filter((p) => p.id !== id),
  }
}

function isSelected(level: BuilderSelectTarget['level'], ids: Partial<Record<string, string>>) {
  const t = selectTarget.value
  if (!t || t.level !== level) return false
  return Object.entries(ids).every(([k, v]) => (t as Record<string, string>)[k] === v)
}
</script>

<template>
  <div class="bld-root hpb">
    <aside class="bld-sidebar">
      <div class="bld-sidebar__head">
        <Layers :size="16" />
        <strong>Structure</strong>
        <button type="button" class="admin-btn admin-btn--ghost admin-btn--icon admin-btn--sm" title="Add section" @click="addSection">
          <Plus :size="14" />
        </button>
      </div>
      <ul class="hpb__section-list">
        <li
          v-for="(sec, i) in layout.sections"
          :key="sec.id"
          class="bld-tree-item"
          :class="{
            'bld-tree-item--active': isSelected('section', { sectionId: sec.id }),
            'bld-tree-item--off': !sec.enabled,
          }"
          draggable="true"
          @dragstart="onSectionDragStart(i)"
          @dragover.prevent
          @drop="onSectionDrop(i)"
          @click="selectSection(sec.id)"
        >
          <GripVertical :size="14" class="hpb__grip" />
          <span class="hpb__section-item-label">{{ sec.label }}</span>
          <button type="button" class="admin-icon-btn" :title="sec.enabled ? 'Hide' : 'Show'" @click.stop="toggleSection(sec.id)">
            <EyeOff v-if="!sec.enabled" :size="13" />
            <Eye v-else :size="13" />
          </button>
        </li>
      </ul>

      <div v-if="presets.length" class="hpb__library">
        <div class="hpb__library-head">
          <Library :size="14" />
          <strong>Saved components</strong>
        </div>
        <ul>
          <li v-for="p in presets" :key="p.id">
            <button type="button" class="hpb__lib-btn" @click="insertPreset(p)">{{ p.name }}</button>
            <button type="button" class="admin-icon-btn admin-icon-btn--danger" @click="removePreset(p.id)">
              <Trash2 :size="11" />
            </button>
          </li>
        </ul>
      </div>
    </aside>

    <div class="bld-canvas">
      <template v-if="selectedSection">
        <div
          class="hpb__main-head"
          :class="{ 'hpb__main-head--sel': isSelected('section', { sectionId: selectedSection.id }) }"
          @click="selectSection(selectedSection.id)"
        >
          <input
            :value="selectedSection.label"
            class="hpb__title-input"
            @click.stop
            @input="patchTarget({ label: ($event.target as HTMLInputElement).value })"
          />
          <div class="hpb__main-actions">
            <button type="button" class="admin-icon-btn" title="Duplicate" @click.stop="duplicateSection(selectedSection)">
              <Copy :size="14" />
            </button>
            <button type="button" class="admin-icon-btn admin-icon-btn--danger" title="Delete section" @click.stop="removeSection(selectedSection.id)">
              <Trash2 :size="14" />
            </button>
          </div>
        </div>

        <div
          v-for="(r, ri) in selectedSection.rows"
          :key="r.id"
          class="bld-row-zone"
          :class="{ 'bld-row-zone--sel': isSelected('row', { sectionId: selectedSection.id, rowId: r.id }) }"
          @click="selectRow(selectedSection.id, r.id)"
        >
          <div class="hpb__row-label">
            <Rows3 :size="14" />
            Row {{ ri + 1 }}
            <span class="hpb__row-meta">{{ r.columns.reduce((n, c) => n + c.span, 0) }}/12 cols</span>
          </div>
          <div class="hpb__row-grid" @click.stop>
            <div
              v-for="c in r.columns"
              :key="c.id"
              class="bld-col-zone"
              :class="{ 'bld-col-zone--sel': isSelected('column', { sectionId: selectedSection.id, rowId: r.id, colId: c.id }) }"
              :style="{ gridColumn: `span ${c.span}` }"
              @click="selectColumn(selectedSection.id, r.id, c.id)"
            >
              <div class="hpb__col-head" @click.stop>
                <Columns3 :size="12" />
                <select
                  :value="c.span"
                  class="bld-chip-select"
                  @change="c.span = Number(($event.target as HTMLSelectElement).value) as HomeLayoutColumn['span']"
                >
                  <option v-for="sp in COLUMN_SPAN_OPTIONS" :key="sp" :value="sp">{{ sp }}/12</option>
                </select>
                <select
                  class="bld-chip-select"
                  @change="addSegment(selectedSection.id, r.id, c.id, ($event.target as HTMLSelectElement).value as HomeSegmentType); ($event.target as HTMLSelectElement).value = ''"
                >
                  <option value="">+ Block</option>
                  <option v-for="opt in SEGMENT_CATALOG" :key="opt.type" :value="opt.type">{{ opt.label }}</option>
                </select>
              </div>
              <div
                v-for="seg in c.segments"
                :key="seg.id"
                class="bld-block"
                :class="{ 'bld-block--active': isSelected('segment', { sectionId: selectedSection.id, rowId: r.id, colId: c.id, segId: seg.id }) }"
                @click.stop="selectSegment(selectedSection.id, r.id, c.id, seg.id)"
              >
                <LayoutGrid :size="13" />
                <span>{{ segmentLabel(seg.type) }}</span>
                <button
                  type="button"
                  class="admin-icon-btn admin-icon-btn--danger"
                  @click.stop="removeSegment(selectedSection.id, r.id, c.id, seg.id)"
                >
                  <Trash2 :size="12" />
                </button>
              </div>
              <p v-if="!c.segments.length" class="hpb__col-empty">Empty column — add a block</p>
            </div>
          </div>
          <button type="button" class="bld-add-btn" @click.stop="addColumn(selectedSection.id, r.id)">
            <Plus :size="12" /> Add column
          </button>
        </div>

        <button type="button" class="bld-add-btn" style="margin-top:0.5rem" @click="addRow(selectedSection.id)">
          <Plus :size="14" /> Add row
        </button>
      </template>
      <p v-else class="hpb__empty">Add a section to start building your landing page.</p>
    </div>

    <BuilderInspector
      :target="selectTarget"
      :section="selectedSection"
      :row="selectedRow"
      :column="selectedColumn"
      :segment="selectedSegment"
      :categories="categories"
      :subcategories="subcategories"
      :content="props.content"
      :uploading-key="props.uploadingKey"
      @patch="patchTarget"
      @save-preset="savePreset"
      @update:content="emit('update:content', $event)"
      @image-pick="(key, e) => emit('imagePick', key, e)"
    />
  </div>
</template>

<style scoped>
.hpb {
  display: grid;
  gap: 0.85rem;
  grid-template-columns: minmax(12rem, 15rem) 1fr minmax(20rem, 24rem);
  align-items: start;
}

@media (max-width: 1200px) {
  .hpb {
    grid-template-columns: 1fr;
  }
}

.hpb__section-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.hpb__grip {
  color: var(--bld-muted, #64748b);
  cursor: grab;
  flex-shrink: 0;
}

.hpb__section-item-label {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.hpb__library {
  margin-top: 1rem;
  padding-top: 0.85rem;
  border-top: 1px solid var(--bld-border, #e2e8f0);
}

.hpb__library-head {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 0.5rem;
  font-size: 0.6875rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--bld-muted, #64748b);
}

.hpb__library ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.hpb__library li {
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.hpb__lib-btn {
  flex: 1;
  text-align: left;
  padding: 0.45rem 0.6rem;
  border: 1px solid var(--bld-border, #e2e8f0);
  border-radius: 8px;
  background: var(--bld-bg, #f8fafc);
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: border-color 0.12s, background 0.12s;
}

.hpb__lib-btn:hover {
  border-color: var(--bld-focus, #0d9488);
  background: #ecfdf5;
}

.hpb__main-head {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
  margin-bottom: 1rem;
  padding: 0.5rem 0.65rem;
  border-radius: 10px;
  border: 1px solid transparent;
  cursor: pointer;
  transition: border-color 0.12s, background 0.12s;
}

.hpb__main-head--sel {
  border-color: var(--bld-focus, #0d9488);
  background: #ecfdf5;
}

.hpb__title-input {
  flex: 1;
  min-width: 10rem;
  border: none;
  background: transparent;
  font-size: 1.0625rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--bld-text, #0f172a);
}

.hpb__title-input:focus {
  outline: none;
}

.hpb__main-actions {
  display: flex;
  gap: 0.35rem;
  align-items: center;
}

.hpb__row-label {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.6875rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--bld-muted, #64748b);
  margin-bottom: 0.55rem;
}

.hpb__row-meta {
  margin-left: auto;
  font-weight: 700;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.06);
}

.hpb__row-grid {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 0.55rem;
}

.hpb__col-head {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
  align-items: center;
  margin-bottom: 0.4rem;
  color: var(--bld-muted, #64748b);
}

.hpb__col-empty {
  margin: 0;
  font-size: 0.6875rem;
  color: var(--bld-muted, #64748b);
  text-align: center;
  padding: 0.5rem 0;
}

.hpb__empty {
  color: var(--bld-muted, #64748b);
  font-size: 0.875rem;
  text-align: center;
  padding: 2rem 1rem;
}
</style>
