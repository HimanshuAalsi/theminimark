<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost'
    size?: 'md' | 'lg'
    type?: 'button' | 'submit'
    disabled?: boolean
    block?: boolean
  }>(),
  { variant: 'primary', size: 'md', type: 'button', disabled: false, block: false },
)
</script>

<template>
  <button
    :type="type"
    class="ui-btn"
    :class="[`ui-btn--${variant}`, `ui-btn--${size}`, { 'ui-btn--block': block }]"
    :disabled="disabled"
  >
    <slot />
  </button>
</template>

<style scoped>
.ui-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  border: none;
  border-radius: var(--tm-radius-full);
  font: inherit;
  font-weight: 650;
  cursor: pointer;
  transition:
    background var(--tm-duration) var(--tm-ease),
    color var(--tm-duration) var(--tm-ease),
    border-color var(--tm-duration) var(--tm-ease),
    transform 0.15s var(--tm-ease);
}

.ui-btn:active:not(:disabled) {
  transform: scale(0.98);
}

.ui-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.ui-btn--md {
  min-height: var(--tm-tap);
  padding: 0 1.15rem;
  font-size: 0.9rem;
}

.ui-btn--lg {
  min-height: 48px;
  padding: 0 1.35rem;
  font-size: 0.95rem;
}

.ui-btn--block {
  width: 100%;
}

.ui-btn--primary {
  background: var(--tm-gradient);
  color: var(--tm-on-accent);
  box-shadow: var(--tm-shadow-accent);
}

.ui-btn--primary:hover:not(:disabled) {
  background: var(--tm-gradient-hover);
}

.ui-btn--secondary {
  background: var(--tm-surface-2);
  color: var(--tm-ink);
  border: 1px solid var(--tm-border-strong);
}

.ui-btn--secondary:hover:not(:disabled) {
  border-color: var(--tm-accent);
  color: var(--tm-accent);
}

.ui-btn--ghost {
  background: transparent;
  color: var(--tm-ink-muted);
  border: 1px solid var(--tm-border);
}

.ui-btn--ghost:hover:not(:disabled) {
  background: var(--tm-accent-muted);
  color: var(--tm-accent);
  border-color: var(--tm-accent-soft);
}
</style>
