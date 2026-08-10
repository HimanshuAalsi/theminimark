<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { POLICY_NAV, policyBySlug } from '@/data/policies'

const route = useRoute()

const doc = computed(() => policyBySlug(String(route.params.slug ?? '')))
</script>

<template>
  <div v-if="doc" class="policy tm-section tm-animate-in">
    <div class="tm-container policy__layout">
      <aside class="policy__nav" aria-label="Policies">
        <h2 class="policy__nav-title">Policies</h2>
        <ul class="policy__nav-list">
          <li v-for="item in POLICY_NAV" :key="item.slug">
            <RouterLink
              :to="`/policies/${item.slug}`"
              class="policy__nav-link"
              :class="{ 'policy__nav-link--on': item.slug === doc.slug }"
            >
              {{ item.label }}
            </RouterLink>
          </li>
        </ul>
      </aside>

      <article class="policy__article">
        <header class="policy__head">
          <p class="policy__brand">The Minimark</p>
          <h1 class="policy__title">{{ doc.title }}</h1>
          <p class="policy__updated">Last updated: {{ doc.lastUpdated }}</p>
          <p v-if="doc.intro" class="policy__intro">{{ doc.intro }}</p>
        </header>

        <section v-for="(section, i) in doc.sections" :key="i" class="policy__section">
          <h2 v-if="section.heading" class="policy__h">{{ section.heading }}</h2>
          <p v-for="(para, j) in section.paragraphs" :key="`p-${j}`" class="policy__p">{{ para }}</p>
          <ul v-if="section.list?.length" class="policy__ul">
            <li v-for="(item, k) in section.list" :key="`l-${k}`">{{ item }}</li>
          </ul>
        </section>
      </article>
    </div>
  </div>

  <div v-else class="policy tm-section">
    <div class="tm-container">
      <h1>Policy not found</h1>
      <p><RouterLink to="/policies/refund">View our policies</RouterLink></p>
    </div>
  </div>
</template>

<style scoped>
.policy__layout {
  display: grid;
  gap: 2.5rem;
  grid-template-columns: minmax(0, 14rem) minmax(0, 1fr);
  align-items: start;
}

@media (max-width: 768px) {
  .policy__layout {
    grid-template-columns: 1fr;
  }
}

.policy__nav-title {
  margin: 0 0 0.75rem;
  font-family: var(--font-display);
  font-size: 1.1rem;
}

.policy__nav-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.policy__nav-link {
  display: block;
  padding: 0.45rem 0;
  font-size: 0.92rem;
  color: var(--color-ink-muted);
  text-decoration: none;
}

.policy__nav-link--on {
  color: var(--color-accent);
  font-weight: 700;
}

.policy__brand {
  margin: 0 0 0.35rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.policy__title {
  margin: 0 0 0.35rem;
  font-family: var(--font-display);
  font-size: clamp(1.75rem, 4vw, 2.35rem);
  font-weight: 500;
}

.policy__updated {
  margin: 0 0 1rem;
  font-size: 0.88rem;
  color: var(--color-ink-faint);
}

.policy__intro {
  margin: 0 0 1.5rem;
  font-size: 1.05rem;
  line-height: 1.6;
  color: var(--color-ink-muted);
}

.policy__section + .policy__section {
  margin-top: 1.75rem;
  padding-top: 1.75rem;
  border-top: 1px solid var(--color-border);
}

.policy__h {
  margin: 0 0 0.65rem;
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 500;
}

.policy__p {
  margin: 0 0 0.75rem;
  line-height: 1.65;
  color: var(--color-ink-muted);
}

.policy__ul {
  margin: 0;
  padding-left: 1.25rem;
  line-height: 1.65;
  color: var(--color-ink-muted);
}

.policy__ul li + li {
  margin-top: 0.35rem;
}
</style>
