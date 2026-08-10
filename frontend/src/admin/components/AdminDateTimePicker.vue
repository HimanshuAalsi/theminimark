<script setup lang="ts">
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: Date | null
    placeholder?: string
    minDate?: Date
    disabled?: boolean
    clearable?: boolean
  }>(),
  { placeholder: 'Pick date & time', clearable: true },
)

const emit = defineEmits<{ 'update:modelValue': [Date | null] }>()

const inner = computed({
  get: () => props.modelValue,
  set: (v: Date | null) => emit('update:modelValue', v),
})
</script>

<template>
  <VueDatePicker
    v-model="inner"
    class="admin-dp"
    :placeholder="placeholder"
    :min-date="minDate"
    :disabled="disabled"
    :clearable="clearable"
    enable-time-picker
    auto-apply
    teleport="body"
    menu-class-name="admin-dp-menu"
    format="dd MMM yyyy, hh:mm a"
    preview-format="dd MMM yyyy, hh:mm a"
    :dark="false"
  />
</template>

<style scoped>
.admin-dp {
  width: 100%;
  display: block;
}

.admin-dp :deep(.dp__main) {
  width: 100%;
}

.admin-dp :deep(.dp__input_wrap) {
  width: 100%;
}

.admin-dp :deep(.dp__input) {
  width: 100%;
  min-height: 2.75rem;
  padding: 0.65rem 0.85rem 0.65rem 2.75rem;
  border: 2px solid var(--admin-border, #cbd5e1);
  border-radius: 12px;
  font-family: inherit;
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--admin-ink, #0f172a);
  background: #fff;
  box-sizing: border-box;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.admin-dp :deep(.dp__input:focus) {
  border-color: var(--admin-border-focus, #0d9488);
  box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.18);
}

.admin-dp :deep(.dp__input_icon) {
  left: 0.85rem;
  color: var(--admin-muted, #64748b);
}

.admin-dp :deep(.dp__clear_icon) {
  right: 0.65rem;
}
</style>

<style>
/* Datepicker menu above modals (teleported to body) */
.admin-dp-menu {
  z-index: 500 !important;
  border-radius: 14px !important;
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.18) !important;
  border: 1px solid #cbd5e1 !important;
  font-family: 'DM Sans', system-ui, sans-serif !important;
}
</style>
