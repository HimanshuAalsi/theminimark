<script setup lang="ts">
defineProps<{ label: string; checked: boolean }>()
const emit = defineEmits<{ change: [boolean] }>()
</script>

<template>
  <label class="bld-toggle-row">
    <span class="bld-toggle-row__label">{{ label }}</span>
    <span class="bld-toggle">
      <input type="checkbox" :checked="checked" @change="emit('change', ($event.target as HTMLInputElement).checked)" />
      <span class="bld-toggle__track" aria-hidden="true" />
      <span class="bld-toggle__thumb" aria-hidden="true" />
    </span>
  </label>
</template>
