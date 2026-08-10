<script setup lang="ts">
import HomeSegmentRenderer from '@/components/home/HomeSegmentRenderer.vue'
import { aosDelay } from '@/lib/aos'
import { boxStyleToCss, containerWidthCss, styleClass } from '@/lib/homePageStyle'
import type { HomeLayoutSection, HomePageLayout } from '@/types/homePageLayout'

defineProps<{ layout: HomePageLayout }>()

const STANDALONE = new Set([
  'hero',
  'trust',
  'how-it-works',
  'sale-countdown',
  'newsletter',
  'blog-teaser',
])

/** These segments attach AOS on their own children */
const SELF_AOS = new Set(['trust', 'how-it-works', 'category-grid', 'personalise-grid', 'section-header'])

function isStandalone(type: string): boolean {
  return STANDALONE.has(type)
}

function sectionSegments(sec: HomeLayoutSection) {
  const out: { seg: (typeof sec.rows)[0]['columns'][0]['segments'][0]; rowId: string; colId: string }[] = []
  for (const row of sec.rows) {
    for (const col of row.columns) {
      for (const seg of col.segments) {
        out.push({ seg, rowId: row.id, colId: col.id })
      }
    }
  }
  return out
}

function groupedSegments(sec: HomeLayoutSection) {
  return sectionSegments(sec).filter((x) => !isStandalone(x.seg.type))
}

function hasGrouped(sec: HomeLayoutSection): boolean {
  return groupedSegments(sec).length > 0
}

function colAlign(val?: string): Record<string, string> {
  if (val === 'center') return { alignSelf: 'center' }
  if (val === 'bottom') return { alignSelf: 'end' }
  return {}
}

function sectionDelay(sectionIndex: number): number {
  return aosDelay(sectionIndex, 40, 200)
}
</script>

<template>
  <div class="home-layout">
    <template v-for="(sec, si) in layout.sections" :key="sec.id">
      <template v-if="sec.enabled">
        <HomeSegmentRenderer
          v-for="item in sectionSegments(sec).filter((x) => isStandalone(x.seg.type))"
          :key="`solo-${item.seg.id}`"
          :segment="item.seg"
          :animate="item.seg.type !== 'hero' && !SELF_AOS.has(item.seg.type)"
          :animate-delay="item.seg.type === 'hero' ? 0 : sectionDelay(si)"
        />

        <section
          v-if="hasGrouped(sec)"
          class="tm-section home-layout__group"
          :class="[
            ...styleClass(sec.style),
            {
              'home-layout__group--cream': sec.theme === 'cream',
              'home-layout__group--dark': sec.theme === 'dark',
              'home-layout__group--custom': sec.theme === 'custom',
            },
          ]"
          :style="boxStyleToCss(sec.style)"
          data-aos="fade-up"
          :data-aos-delay="String(sectionDelay(si))"
        >
          <div class="tm-container home-layout__container" :style="containerWidthCss(sec.container)">
            <div
              v-for="row in sec.rows"
              :key="row.id"
              class="home-layout__row"
              :class="styleClass(row.style)"
              :style="boxStyleToCss(row.style)"
            >
              <div
                v-for="col in row.columns"
                :key="col.id"
                class="home-layout__col"
                :class="styleClass(col.style)"
                :style="{ gridColumn: `span ${col.span}`, ...boxStyleToCss(col.style), ...colAlign(col.valign) }"
              >
                <HomeSegmentRenderer
                  v-for="seg in col.segments.filter((s) => !isStandalone(s.type))"
                  :key="seg.id"
                  :segment="seg"
                  :animate="false"
                />
              </div>
            </div>
          </div>
        </section>
      </template>
    </template>
  </div>
</template>

<style scoped>
.home-layout {
  width: 100%;
}

.home-layout__container {
  box-sizing: border-box;
  padding-left: clamp(1rem, 4vw, 1.5rem);
  padding-right: clamp(1rem, 4vw, 1.5rem);
}

.home-layout__group--cream {
  background: var(--color-surface, var(--tm-surface));
  border-top: 1px solid var(--color-border, var(--tm-border));
  border-bottom: 1px solid var(--color-border, var(--tm-border));
}

.home-layout__group--dark {
  background: var(--tm-ink);
  color: var(--tm-surface);
}

.home-layout__row {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 1.25rem;
  align-items: start;
}

.home-layout__col {
  min-width: 0;
}
</style>

<style>
.hp-hide-mobile {
  display: block;
}

.hp-hide-desktop {
  display: none;
}

@media (min-width: 768px) {
  .hp-hide-mobile {
    display: none !important;
  }

  .hp-hide-desktop {
    display: block;
  }
}
</style>
