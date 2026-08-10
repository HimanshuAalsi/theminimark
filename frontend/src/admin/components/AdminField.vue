<script setup lang="ts">
defineProps<{
  label?: string
  hint?: string
  error?: string
  required?: boolean
  span?: 3 | 4 | 6 | 8 | 12
}>()
</script>

<template>
  <div class="admin-field" :class="span ? `admin-field--span-${span}` : undefined">
    <label v-if="label" class="admin-field__label">
      {{ label }}
      <span v-if="required" class="admin-field__req" aria-hidden="true">*</span>
    </label>
    <div class="admin-field__control">
      <slot />
    </div>
    <p v-if="hint && !error" class="admin-field__hint">{{ hint }}</p>
    <p v-if="error" class="admin-field__error" role="alert">{{ error }}</p>
  </div>
</template>
