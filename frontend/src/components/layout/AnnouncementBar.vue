<script setup lang="ts">
import { Megaphone } from 'lucide-vue-next'
import { storeToRefs } from 'pinia'
import { useSiteStore } from '@/stores/site'

const site = useSiteStore()
const { announcement } = storeToRefs(site)
</script>

<template>
  <div class="announce" role="region" aria-label="Store updates">
    <p class="announce__text">
      <Megaphone class="announce__ico" :size="15" :stroke-width="2.25" aria-hidden="true" />
      <span>{{ announcement }}</span>
    </p>
  </div>
</template>

<style scoped>
.announce {
  position: relative;
  overflow: hidden;
  background: linear-gradient(105deg, #1a2825 0%, #2d4a42 40%, #1e3330 100%);
  color: #f4f1eb;
  text-align: center;
  padding: 0.55rem 1rem;
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.03em;
}

.announce::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    110deg,
    transparent 0%,
    rgba(255, 255, 255, 0.07) 45%,
    transparent 90%
  );
  background-size: 200% 100%;
  animation: announce-sweep 4.5s ease-in-out infinite;
  pointer-events: none;
}

@keyframes announce-sweep {
  0%,
  100% {
    background-position: 100% 0;
  }
  50% {
    background-position: -100% 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .announce::after {
    animation: none;
    opacity: 0;
  }
}

.announce__text {
  position: relative;
  z-index: 1;
  margin: 0 auto;
  max-width: 56rem;
  line-height: 1.45;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.announce__ico {
  flex-shrink: 0;
  opacity: 0.92;
  color: #e8b4a0;
}
</style>
