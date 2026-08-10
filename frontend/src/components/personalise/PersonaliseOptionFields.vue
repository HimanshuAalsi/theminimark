<script setup lang="ts">
import type { PersonaliseField, PersonaliseOptions } from '@/data/personaliseOptions'

const props = defineProps<{
  fields: PersonaliseField[]
  modelValue: PersonaliseOptions
}>()

const emit = defineEmits<{
  'update:modelValue': [value: PersonaliseOptions]
}>()

function patch(key: keyof PersonaliseOptions, raw: string | number) {
  const field = props.fields.find((f) => f.key === key)
  const isNum =
    field?.type === 'number' ||
    (field?.type === 'select' && field.choices?.some((c) => typeof c.value === 'number'))
  const value = isNum ? Number(raw) : raw
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}

function onSelect(key: keyof PersonaliseOptions, e: Event) {
  patch(key, (e.target as HTMLSelectElement).value)
}
</script>

<template>
  <div class="pof">
    <div v-for="field in fields" :key="field.key" class="pof__field">
      <label class="pof__label" :for="`pof-${field.key}`">{{ field.label }}</label>

      <select
        v-if="field.type === 'select' && field.choices"
        :id="`pof-${field.key}`"
        class="pof__input"
        :value="modelValue[field.key] ?? ''"
        @change="onSelect(field.key, $event)"
      >
        <option
          v-for="c in field.choices"
          :key="String(c.value)"
          :value="c.value"
        >
          {{ c.label }}
        </option>
      </select>

      <input
        v-else-if="field.type === 'number'"
        :id="`pof-${field.key}`"
        class="pof__input"
        type="number"
        :min="field.min"
        :max="field.max"
        :value="modelValue[field.key] ?? field.min ?? 1"
        @input="patch(field.key, Number(($event.target as HTMLInputElement).value))"
      />

      <input
        v-else-if="field.type === 'text'"
        :id="`pof-${field.key}`"
        class="pof__input"
        type="text"
        :placeholder="field.placeholder"
        :maxlength="field.maxLength"
        :value="(modelValue[field.key] as string) ?? ''"
        @input="patch(field.key, ($event.target as HTMLInputElement).value)"
      />

      <textarea
        v-else-if="field.type === 'textarea'"
        :id="`pof-${field.key}`"
        class="pof__input pof__input--area"
        rows="3"
        :placeholder="field.placeholder"
        :maxlength="field.maxLength"
        :value="(modelValue[field.key] as string) ?? ''"
        @input="patch(field.key, ($event.target as HTMLTextAreaElement).value)"
      />
    </div>
  </div>
</template>

<style scoped>
.pof {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.pof__label {
  display: block;
  margin-bottom: 0.35rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--color-ink-muted);
}

.pof__input {
  width: 100%;
  padding: 0.55rem 0.7rem;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  background: #fff;
  font-size: 0.88rem;
  color: var(--color-ink);
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.pof__input:focus {
  outline: none;
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px rgba(45, 92, 82, 0.12);
}

.pof__input--area {
  resize: vertical;
  min-height: 4.5rem;
  line-height: 1.45;
}
</style>
