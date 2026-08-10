<script setup lang="ts">
import { ArrowUpRight, Facebook, Instagram, MessageCircle, Sparkles, Youtube } from 'lucide-vue-next'
import { SITE_SOCIAL, SITE_WHATSAPP_CHANNEL_URL } from '@/data/siteContact'

const perks = ['New product drops', 'Exclusive deals', 'No spam, ever'] as const

const socialIcons = {
  instagram: Instagram,
  facebook: Facebook,
  youtube: Youtube,
} as const

const socialThemes: Record<string, { bg: string; color: string; ring: string }> = {
  instagram: {
    bg: 'linear-gradient(135deg, #fdf497 0%, #fd5949 46%, #d6249f 72%, #285AEB 100%)',
    color: '#fff',
    ring: 'rgba(214, 36, 159, 0.28)',
  },
  facebook: {
    bg: 'linear-gradient(135deg, #1877f2 0%, #0d65d9 100%)',
    color: '#fff',
    ring: 'rgba(24, 119, 242, 0.28)',
  },
  youtube: {
    bg: 'linear-gradient(135deg, #ff0000 0%, #cc0000 100%)',
    color: '#fff',
    ring: 'rgba(255, 0, 0, 0.22)',
  },
}
</script>

<template>
  <section class="connect tm-section" aria-labelledby="connect-title">
    <div class="tm-container connect__inner">
      <header class="connect__head">
        <p class="connect__eyebrow">
          <Sparkles :size="14" :stroke-width="2.25" aria-hidden="true" />
          Stay connected
        </p>
        <h2 id="connect-title" class="connect__headline">Follow The MiniMark</h2>
        <p class="connect__lede">
          Get updates on your phone or scroll with us — whichever you prefer.
        </p>
      </header>

      <div class="connect__grid">
        <article class="connect__wa">
          <div class="connect__wa-glow" aria-hidden="true" />
          <MessageCircle class="connect__wa-watermark" :size="120" :stroke-width="1.25" aria-hidden="true" />

          <div class="connect__wa-body">
            <span class="connect__wa-badge">
              <MessageCircle :size="18" :stroke-width="2.25" aria-hidden="true" />
              WhatsApp channel
            </span>
            <h3 class="connect__wa-title">Join for drops &amp; deals</h3>
            <p class="connect__wa-text">
              Occasional updates when we launch something new or run a sale — straight to your
              phone, not your inbox.
            </p>

            <ul class="connect__perks" aria-label="What you get">
              <li v-for="perk in perks" :key="perk">{{ perk }}</li>
            </ul>

            <a
              :href="SITE_WHATSAPP_CHANNEL_URL"
              target="_blank"
              rel="noopener noreferrer"
              class="connect__wa-btn tm-press"
            >
              Join WhatsApp channel
              <ArrowUpRight :size="18" :stroke-width="2.25" aria-hidden="true" />
            </a>
          </div>
        </article>

        <article class="connect__social-panel">
          <p class="connect__panel-label">Find us on social</p>

          <ul class="connect__social-list">
            <li v-for="item in SITE_SOCIAL" :key="item.id">
              <a
                :href="item.href"
                target="_blank"
                rel="noopener noreferrer"
                class="connect__social-link tm-hover-lift"
                :style="{ '--social-ring': socialThemes[item.id]?.ring ?? 'rgba(58, 143, 124, 0.2)' }"
              >
                <span
                  class="connect__social-icon"
                  :style="{
                    background: socialThemes[item.id]?.bg ?? 'var(--tm-gradient)',
                    color: socialThemes[item.id]?.color ?? '#fff',
                  }"
                  aria-hidden="true"
                >
                  <component :is="socialIcons[item.id as keyof typeof socialIcons]" :size="20" :stroke-width="2" />
                </span>

                <span class="connect__social-copy">
                  <span class="connect__social-label">{{ item.label }}</span>
                  <span class="connect__social-handle">{{ item.handle }}</span>
                </span>

                <ArrowUpRight class="connect__social-arrow" :size="18" :stroke-width="2" aria-hidden="true" />
              </a>
            </li>
          </ul>
        </article>
      </div>
    </div>
  </section>
</template>

<style scoped>
.connect {
  position: relative;
  background: transparent;
  border-top: 1px solid var(--color-border);
}

.connect__inner {
  position: relative;
}

.connect__head {
  max-width: 34rem;
  margin: 0 auto 2rem;
  text-align: center;
}

.connect__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0 0 0.5rem;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  background: var(--color-accent-soft);
  color: var(--color-accent);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.connect__headline {
  margin: 0 0 0.55rem;
  font-family: var(--font-display);
  font-size: clamp(1.75rem, 4vw, 2.35rem);
  font-weight: 500;
  line-height: 1.15;
  color: var(--color-ink);
}

.connect__lede {
  margin: 0;
  font-size: 1rem;
  line-height: 1.6;
  color: var(--color-ink-muted);
}

.connect__grid {
  display: grid;
  gap: 1.15rem;
  grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
  align-items: stretch;
}

@media (max-width: 860px) {
  .connect__grid {
    grid-template-columns: 1fr;
  }
}

/* WhatsApp card */
.connect__wa {
  position: relative;
  overflow: hidden;
  border-radius: var(--radius-xl);
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: linear-gradient(145deg, #1a4a42 0%, #236b5c 38%, #2d8a6e 72%, #3aad88 100%);
  box-shadow:
    0 24px 48px rgba(26, 74, 66, 0.28),
    inset 0 1px 0 rgba(255, 255, 255, 0.14);
  color: #f4fbf8;
}

.connect__wa-glow {
  position: absolute;
  inset: auto -20% -40% -10%;
  height: 70%;
  background: radial-gradient(circle, rgba(37, 211, 102, 0.35) 0%, transparent 68%);
  pointer-events: none;
}

.connect__wa-watermark {
  position: absolute;
  top: -1.5rem;
  right: -1rem;
  opacity: 0.08;
  color: #fff;
  pointer-events: none;
}

.connect__wa-body {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  padding: clamp(1.35rem, 3vw, 1.85rem);
  min-height: 100%;
}

.connect__wa-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  width: fit-content;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.18);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.connect__wa-title {
  margin: 0;
  font-family: var(--font-display);
  font-size: clamp(1.45rem, 2.8vw, 1.85rem);
  font-weight: 500;
  line-height: 1.2;
  color: #fff;
}

.connect__wa-text {
  margin: 0;
  max-width: 34ch;
  font-size: 0.95rem;
  line-height: 1.6;
  color: rgba(244, 251, 248, 0.82);
}

.connect__perks {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin: 0.15rem 0 0.35rem;
  padding: 0;
  list-style: none;
}

.connect__perks li {
  padding: 0.3rem 0.65rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.14);
  font-size: 0.75rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.92);
}

.connect__wa-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  width: fit-content;
  margin-top: auto;
  min-height: var(--tap-min);
  padding: 0 1.35rem;
  border-radius: 999px;
  background: #25d366;
  color: #fff !important;
  font-weight: 700;
  font-size: 0.92rem;
  text-decoration: none;
  box-shadow:
    0 8px 24px rgba(37, 211, 102, 0.38),
    inset 0 1px 0 rgba(255, 255, 255, 0.22);
  transition:
    transform 0.2s var(--ease-spring, ease),
    filter 0.2s ease,
    box-shadow 0.2s ease;
}

.connect__wa-btn:hover {
  filter: brightness(1.06);
  transform: translateY(-2px);
  box-shadow:
    0 12px 28px rgba(37, 211, 102, 0.42),
    inset 0 1px 0 rgba(255, 255, 255, 0.22);
}

/* Social panel */
.connect__social-panel {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  padding: clamp(1.25rem, 3vw, 1.65rem);
  border-radius: var(--radius-xl);
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  box-shadow: var(--shadow-card);
}

.connect__panel-label {
  margin: 0;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.connect__social-list {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  margin: 0;
  padding: 0;
  list-style: none;
  flex: 1;
}

.connect__social-link {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.85rem 0.95rem;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: transparent;
  text-decoration: none;
  transition:
    border-color 0.22s ease,
    box-shadow 0.22s ease,
    transform 0.2s var(--ease-spring, ease);
}

.connect__social-link:hover {
  background: var(--color-accent-soft);
  border-color: var(--social-ring, var(--color-accent-soft));
  box-shadow: 0 8px 22px var(--social-ring, rgba(58, 143, 124, 0.12));
}

.connect__social-icon {
  flex-shrink: 0;
  display: grid;
  place-items: center;
  width: 2.65rem;
  height: 2.65rem;
  border-radius: 14px;
  box-shadow: 0 4px 12px rgba(20, 19, 18, 0.12);
}

.connect__social-copy {
  display: flex;
  flex-direction: column;
  gap: 0.12rem;
  min-width: 0;
  flex: 1;
}

.connect__social-label {
  font-weight: 700;
  font-size: 0.95rem;
  color: var(--color-ink);
}

.connect__social-handle {
  font-size: 0.84rem;
  color: var(--color-ink-muted);
}

.connect__social-arrow {
  flex-shrink: 0;
  color: var(--color-ink-faint);
  transition:
    transform 0.2s var(--ease-spring, ease),
    color 0.2s ease;
}

.connect__social-link:hover .connect__social-arrow {
  transform: translate(2px, -2px);
  color: var(--color-accent);
}

[data-theme='dark'] .connect__social-panel {
  background: var(--tm-surface-2);
}

[data-theme='dark'] .connect__social-link:hover {
  background: var(--tm-accent-soft);
}

[data-theme='dark'] .connect__wa {
  border-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 24px 48px rgba(0, 0, 0, 0.35);
}
</style>
