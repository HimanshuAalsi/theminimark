<script setup lang="ts">
import { Link2, Unlink2 } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import type { HomeSpacing } from '@/types/homePageLayout'

const model = defineModel<HomeSpacing>({ required: true })
const linked = ref(true)

watch(
  () => model.value,
  (v) => {
    if (!v) return
    linked.value = v.top === v.right && v.right === v.bottom && v.bottom === v.left
  },
  { immediate: true, deep: true },
)

const pct = computed(() => {
  const v = model.value.top ?? 0
  return `${Math.min(100, (v / 120) * 100)}%`
})

function patch(key: keyof HomeSpacing, val: number) {
  const n = Math.max(0, Math.min(200, val))
  if (linked.value) {
    model.value = { top: n, right: n, bottom: n, left: n }
  } else {
    model.value = { ...model.value, [key]: n }
  }
}

function patchAll(val: number) {
  patch('top', val)
}

function toggleLink() {
  linked.value = !linked.value
  if (linked.value && model.value.top != null) {
    const v = model.value.top
    model.value = { top: v, right: v, bottom: v, left: v }
  }
}
</script>

<template>
  <div class="bld-range">
    <div class="bld-range__head">
      <span class="bld-range__label"><slot /></span>
      <div class="bld-range__actions">
        <button
          type="button"
          class="bld-range__link"
          :class="{ 'bld-range__link--on': linked }"
          :title="linked ? 'Unlink sides' : 'Link all sides'"
          @click="toggleLink"
        >
          <Link2 v-if="linked" :size="13" />
          <Unlink2 v-else :size="13" />
        </button>
      </div>
    </div>

    <div v-if="linked" class="bld-range__row">
      <input
        type="range"
        class="bld-range__slider"
        min="0"
        max="120"
        :style="{ '--pct': pct }"
        :value="model.top ?? 0"
        @input="patchAll(Number(($event.target as HTMLInputElement).value))"
      />
      <input
        type="number"
        min="0"
        max="200"
        class="bld-range__value"
        :value="model.top ?? 0"
        @input="patchAll(Number(($event.target as HTMLInputElement).value))"
      />
    </div>

    <div v-else class="bld-box">
      <div class="bld-box__cell" style="grid-column: 2; grid-row: 1">
        <span class="bld-box__tag">Top</span>
        <input type="number" min="0" max="200" class="bld-box__input" :value="model.top ?? 0" @input="patch('top', Number(($event.target as HTMLInputElement).value))" />
      </div>
      <div class="bld-box__cell" style="grid-column: 1; grid-row: 2">
        <span class="bld-box__tag">Left</span>
        <input type="number" min="0" max="200" class="bld-box__input" :value="model.left ?? 0" @input="patch('left', Number(($event.target as HTMLInputElement).value))" />
      </div>
      <div class="bld-box__cell bld-box__cell--center">Box</div>
      <div class="bld-box__cell" style="grid-column: 3; grid-row: 2">
        <span class="bld-box__tag">Right</span>
        <input type="number" min="0" max="200" class="bld-box__input" :value="model.right ?? 0" @input="patch('right', Number(($event.target as HTMLInputElement).value))" />
      </div>
      <div class="bld-box__cell" style="grid-column: 2; grid-row: 3">
        <span class="bld-box__tag">Bottom</span>
        <input type="number" min="0" max="200" class="bld-box__input" :value="model.bottom ?? 0" @input="patch('bottom', Number(($event.target as HTMLInputElement).value))" />
      </div>
    </div>
  </div>
</template>

<style scoped>
@import './builder-ui.css';
</style>
