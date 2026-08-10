<script setup lang="ts">
import { X } from 'lucide-vue-next'
import { onMounted, onUnmounted, watch } from 'vue'

const props = defineProps<{
  open: boolean
  title?: string
  /** Bottom sheet on mobile, side panel on desktop */
  variant?: 'bottom' | 'side'
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

function close() {
  emit('update:open', false)
}

function onKey(e: KeyboardEvent) {
  if (e.key === 'Escape' && props.open) close()
}

watch(
  () => props.open,
  (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
  },
)

onMounted(() => document.addEventListener('keydown', onKey))
onUnmounted(() => {
  document.removeEventListener('keydown', onKey)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="ui-sheet">
      <div v-if="open" class="ui-sheet" :class="`ui-sheet--${variant ?? 'bottom'}`" role="presentation">
        <button type="button" class="ui-sheet__backdrop" aria-label="Close" @click="close" />
        <div class="ui-sheet__panel" role="dialog" aria-modal="true" :aria-label="title">
          <div v-if="title || $slots.header" class="ui-sheet__head">
            <slot name="header">
              <h2 class="ui-sheet__title">{{ title }}</h2>
            </slot>
            <button type="button" class="ui-sheet__close" aria-label="Close" @click="close">
              <X :size="20" :stroke-width="2" />
            </button>
          </div>
          <div class="ui-sheet__body">
            <slot />
          </div>
          <div v-if="$slots.footer" class="ui-sheet__foot">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.ui-sheet {
  position: fixed;
  inset: 0;
  z-index: 200;
  display: flex;
  pointer-events: none;
}

.ui-sheet--bottom {
  align-items: flex-end;
  justify-content: center;
}

.ui-sheet--side {
  align-items: stretch;
  justify-content: flex-end;
}

.ui-sheet__backdrop {
  position: absolute;
  inset: 0;
  border: none;
  background: var(--tm-overlay);
  pointer-events: auto;
  cursor: pointer;
}

.ui-sheet__panel {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  max-height: min(92dvh, 640px);
  width: 100%;
  pointer-events: auto;
  background: var(--tm-surface-2);
  border: 1px solid var(--tm-border);
  box-shadow: var(--tm-shadow-lg);
}

.ui-sheet--bottom .ui-sheet__panel {
  border-radius: var(--tm-radius-xl) var(--tm-radius-xl) 0 0;
  padding-bottom: env(safe-area-inset-bottom, 0);
}

.ui-sheet--side .ui-sheet__panel {
  max-width: min(22rem, 100vw);
  max-height: 100dvh;
  border-radius: var(--tm-radius-xl) 0 0 var(--tm-radius-xl);
}

.ui-sheet__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.15rem 0.5rem;
  flex-shrink: 0;
}

.ui-sheet__title {
  margin: 0;
  font-family: var(--tm-font-display);
  font-size: 1.15rem;
  font-weight: 500;
  color: var(--tm-ink);
}

.ui-sheet__close {
  display: grid;
  place-items: center;
  width: var(--tm-tap);
  height: var(--tm-tap);
  margin: -0.35rem -0.35rem -0.35rem 0;
  border: none;
  border-radius: var(--tm-radius-full);
  background: transparent;
  color: var(--tm-ink-muted);
  cursor: pointer;
}

.ui-sheet__body {
  flex: 1;
  overflow-y: auto;
  padding: 0.5rem 1.15rem 1.15rem;
  -webkit-overflow-scrolling: touch;
}

.ui-sheet__foot {
  flex-shrink: 0;
  padding: 0.75rem 1.15rem calc(0.75rem + env(safe-area-inset-bottom, 0));
  border-top: 1px solid var(--tm-border);
}

.ui-sheet-enter-active .ui-sheet__backdrop,
.ui-sheet-leave-active .ui-sheet__backdrop {
  transition: opacity var(--tm-duration) var(--tm-ease);
}

.ui-sheet-enter-active .ui-sheet__panel,
.ui-sheet-leave-active .ui-sheet__panel {
  transition: transform var(--tm-duration-slow) var(--tm-ease);
}

.ui-sheet-enter-from .ui-sheet__backdrop,
.ui-sheet-leave-to .ui-sheet__backdrop {
  opacity: 0;
}

.ui-sheet--bottom.ui-sheet-enter-from .ui-sheet__panel,
.ui-sheet--bottom.ui-sheet-leave-to .ui-sheet__panel {
  transform: translateY(100%);
}

.ui-sheet--side.ui-sheet-enter-from .ui-sheet__panel,
.ui-sheet--side.ui-sheet-leave-to .ui-sheet__panel {
  transform: translateX(100%);
}
</style>
