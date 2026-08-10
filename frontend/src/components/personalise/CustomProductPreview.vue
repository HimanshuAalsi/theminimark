<script setup lang="ts">
import PersonaliseStudio3D from '@/components/personalise/PersonaliseStudio3D.vue'
import type { PersonaliseType } from '@/data/personalise'

withDefaults(
  defineProps<{
    type: PersonaliseType
    photoUrl?: string | null
    zoom?: number
    posX?: number
    posY?: number
    compact?: boolean
  }>(),
  {
    photoUrl: null,
    zoom: 1,
    posX: 50,
    posY: 50,
    compact: false,
  },
)
</script>

<template>
  <PersonaliseStudio3D
    :type="type"
    :photo-url="photoUrl"
    :zoom="zoom"
    :pos-x="posX"
    :pos-y="posY"
    :compact="compact"
    :interactive="!compact"
  />
</template>
