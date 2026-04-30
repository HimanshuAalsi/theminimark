<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="hero">
    <p class="hero__eyebrow">Stationery &amp; paper goods</p>
    <h1 class="hero__title">Small things that make reading and planning nicer.</h1>
    <p class="hero__lead">
      Magnetic bookmarks, wall calendars, stickers, and more — built for your desk and your
      shelves.
    </p>
    <div class="hero__actions">
      <RouterLink to="/shop" class="btn btn--primary"> Shop collection </RouterLink>
      <RouterLink to="/cart" class="btn btn--ghost"> View cart </RouterLink>
    </div>
  </section>

  <section class="tiles" aria-label="Categories">
    <article class="tile">
      <h2 class="tile__title">Magnetic bookmarks</h2>
      <p class="tile__text">Slim, strong magnets — mark your page without bulk.</p>
    </article>
    <article class="tile">
      <h2 class="tile__title">Calendars</h2>
      <p class="tile__text">Month-at-a-glance layouts you can actually read.</p>
    </article>
    <article class="tile">
      <h2 class="tile__title">Stickers</h2>
      <p class="tile__text">Decals and sheets for planners, laptops, and gifts.</p>
    </article>
  </section>
</template>

<style scoped>
.hero {
  padding: 0.5rem 0 2rem;
}

.hero__eyebrow {
  margin: 0 0 0.75rem;
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.hero__title {
  margin: 0 0 1rem;
  font-size: clamp(1.75rem, 4vw, 2.35rem);
  font-weight: 650;
  letter-spacing: -0.03em;
  line-height: 1.15;
  color: var(--color-text);
}

.hero__lead {
  margin: 0 0 1.75rem;
  max-width: 36rem;
  font-size: 1.05rem;
  line-height: 1.55;
  color: var(--color-text-muted);
}

.hero__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.65rem 1.1rem;
  border-radius: 0.6rem;
  font-size: 0.95rem;
  font-weight: 600;
  text-decoration: none;
  border: 1px solid transparent;
  cursor: pointer;
  transition:
    background 0.15s,
    color 0.15s,
    border-color 0.15s;
}

.btn--primary {
  background: var(--color-accent);
  color: var(--color-accent-contrast);
}

.btn--primary:hover {
  filter: brightness(1.05);
}

.btn--ghost {
  background: transparent;
  color: var(--color-text);
  border-color: var(--color-border);
}

.btn--ghost:hover {
  background: var(--color-surface);
}

.tiles {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  padding-top: 0.5rem;
}

.tile {
  padding: 1.25rem 1.25rem 1.35rem;
  border-radius: 0.85rem;
  border: 1px solid var(--color-border);
  background: var(--color-surface);
}

.tile__title {
  margin: 0 0 0.5rem;
  font-size: 1.05rem;
  font-weight: 650;
  color: var(--color-text);
}

.tile__text {
  margin: 0;
  font-size: 0.92rem;
  line-height: 1.45;
  color: var(--color-text-muted);
}
</style>
