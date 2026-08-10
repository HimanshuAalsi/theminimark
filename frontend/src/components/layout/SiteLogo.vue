<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useHomePageStore } from '@/stores/homePage'
import { useThemeStore } from '@/stores/theme'
import logoFallback from '@/assets/main-logo.webp'
import logoLight from '@/assets/logolight.png'

withDefaults(
  defineProps<{
    size?: 'header' | 'footer' | 'auth' | 'sm'
    linked?: boolean
  }>(),
  {
    size: 'header',
    linked: false,
  }
)

const homePage = useHomePageStore()
const theme = useThemeStore()
const { logoSrc } = storeToRefs(homePage)

const logo = computed(() =>
  theme.isDark ? logoLight : logoSrc.value || logoFallback,
)
</script>

<template>
  <RouterLink v-if="linked" to="/" class="site-logo-link">
    <img
      :src="logo"
      alt="The Minimark — bookmarks and paper goods"
      class="site-logo"
      :class="`site-logo--${size}`"
      width="154"
      height="34"
      decoding="async"
    />
  </RouterLink>
  <img
    v-else
    :src="logo"
    alt="The Minimark — bookmarks and paper goods"
    class="site-logo"
    :class="`site-logo--${size}`"
    width="154"
    height="34"
      decoding="async"
  />
</template>

<style scoped>
.site-logo-link {
  display: inline-flex;
  align-items: center;
  color: inherit;
  text-decoration: none;
  line-height: 0;
}

.site-logo {
  display: block;
  width: auto;
  max-width: 100%;
  object-fit: contain;
}

/* Mobile: slightly larger for tap targets; desktop: ~30% smaller than original */
.site-logo--header {
  height: clamp(1.65rem, 5.5vw, 2.05rem);
}

.site-logo--footer {
  height: clamp(1.75rem, 6vw, 2.2rem);
  margin-bottom: 0.75rem;
}

.site-logo--auth {
  height: clamp(1.85rem, 6.5vw, 2.35rem);
  margin: 0 auto 1rem;
}

.site-logo--sm {
  height: 1.65rem;
}

@media (min-width: 768px) {
  .site-logo--header {
    height: clamp(1.4rem, 3.15vw, 1.85rem);
  }

  .site-logo--footer {
    height: clamp(1.575rem, 3.5vw, 2rem);
  }

  .site-logo--auth {
    height: clamp(1.65rem, 4.2vw, 2.1rem);
  }

  .site-logo--sm {
    height: 1.4rem;
  }
}
</style>
