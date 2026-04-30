<template>
  <footer class="footer">
    <div class="footer__inner">
      <p class="footer__copy">© {{ year }} The Minimark</p>
      <p class="footer__note">Bookmarks, calendars, stickers &amp; more.</p>
    </div>
  </footer>
</template>

<script setup lang="ts">
const year = new Date().getFullYear()
</script>

<style scoped>
.footer {
  border-top: 1px solid var(--color-border);
  margin-top: auto;
}

.footer__inner {
  max-width: var(--content-max);
  margin: 0 auto;
  padding: 1.25rem 1.25rem 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.footer__copy {
  margin: 0;
  font-size: 0.9rem;
  color: var(--color-text-muted);
}

.footer__note {
  margin: 0;
  font-size: 0.85rem;
  color: var(--color-text-muted);
  opacity: 0.9;
}
</style>
