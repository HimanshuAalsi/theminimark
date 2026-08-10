<script setup lang="ts">
import { Facebook, Instagram, MessageCircle, Youtube } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'
import SiteLogo from '@/components/layout/SiteLogo.vue'
import { SITE_SOCIAL, SITE_WHATSAPP_CHANNEL_URL } from '@/data/siteContact'
</script>

<template>
  <footer class="site-footer">
    <div class="site-footer__main">
      <div class="tm-container site-footer__grid">
        <div class="site-footer__col">
          <SiteLogo size="footer" linked />
          <p class="site-footer__p">
            The Minimark brings you bookmarks, stationery, and small gifts with clear pricing and
            straightforward shipping—made for readers and everyday desks.
          </p>
          <div class="site-footer__social" role="list">
            <a
              v-for="item in SITE_SOCIAL"
              :key="item.id"
              :href="item.href"
              target="_blank"
              rel="noopener noreferrer"
              :aria-label="item.label"
              class="site-footer__soc"
            >
              <Instagram v-if="item.id === 'instagram'" :size="18" :stroke-width="1.75" />
              <Facebook v-else-if="item.id === 'facebook'" :size="18" :stroke-width="1.75" />
              <Youtube v-else-if="item.id === 'youtube'" :size="18" :stroke-width="1.75" />
            </a>
          </div>
        </div>

        <div class="site-footer__col">
          <h3 class="site-footer__h">Quick links</h3>
          <ul class="site-footer__list">
            <li><RouterLink to="/account">My account</RouterLink></li>
            <li><RouterLink to="/cart">Shopping cart</RouterLink></li>
            <li><RouterLink to="/track-order">Track order</RouterLink></li>
            <li><RouterLink to="/blog">Blog</RouterLink></li>
            <li><RouterLink to="/wishlist">Wishlist</RouterLink></li>
            <li><RouterLink to="/create-your-set">Create your own set</RouterLink></li>
          </ul>
        </div>

        <div class="site-footer__col">
          <h3 class="site-footer__h">Information</h3>
          <ul class="site-footer__list">
            <li><RouterLink to="/policies/privacy">Privacy policy</RouterLink></li>
            <li><RouterLink to="/policies/refund">Refund &amp; return policy</RouterLink></li>
            <li><RouterLink to="/policies/shipping">Shipping policy</RouterLink></li>
            <li><RouterLink to="/policies/terms">Terms of service</RouterLink></li>
          </ul>
        </div>

        <div class="site-footer__col">
          <h2 class="site-footer__h site-footer__h--lg">Stay connected</h2>
          <p class="site-footer__p">
            Join our WhatsApp channel for new products and offers — not a newsletter.
          </p>
          <a
            :href="SITE_WHATSAPP_CHANNEL_URL"
            target="_blank"
            rel="noopener noreferrer"
            class="site-footer__wa tm-press"
          >
            <MessageCircle :size="18" :stroke-width="2" aria-hidden="true" />
            Join WhatsApp channel
          </a>
          <ul class="site-footer__handles">
            <li v-for="item in SITE_SOCIAL" :key="item.id">
              <a :href="item.href" target="_blank" rel="noopener noreferrer">
                {{ item.label }} · {{ item.handle }}
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="site-footer__bottom">
      <div class="tm-container site-footer__bottom-inner">
        <div class="site-footer__bottom-brand">
          <SiteLogo size="sm" linked />
          <p class="site-footer__copy">© 2026 The Minimark. All rights reserved.</p>
        </div>
        <img
          src="/payment-methods.svg"
          width="192"
          height="14"
          alt="Payment methods"
          loading="lazy"
        />
      </div>
    </div>
  </footer>
</template>

<style scoped>
.site-footer__main {
  padding: 3rem 0 2.5rem;
  border-top: 1px solid var(--color-border);
  background: linear-gradient(180deg, var(--color-surface-elevated) 0%, var(--color-page) 100%);
}

.site-footer__grid {
  display: grid;
  gap: 2rem;
  grid-template-columns: 1.4fr 1fr 1fr 1.2fr;
}

@media (max-width: 1024px) {
  .site-footer__grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .site-footer__grid {
    grid-template-columns: 1fr;
  }
}

.site-footer__h {
  margin: 0 0 1rem;
  font-family: var(--font-display);
  font-size: 1.05rem;
  font-weight: 500;
  color: var(--color-ink);
}

.site-footer__h--lg {
  font-size: 1.35rem;
}

.site-footer__p {
  margin: 0 0 1rem;
  font-size: 14px;
  line-height: 1.55;
  color: var(--color-ink-muted);
}

.site-footer__social {
  display: flex;
  gap: 12px;
  margin-top: 0.5rem;
}

.site-footer__soc {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--color-surface-elevated);
  color: var(--color-ink);
  border: 1px solid var(--color-border);
  transition:
    color 0.2s ease,
    transform 0.2s var(--ease-out, ease),
    box-shadow 0.2s ease,
    border-color 0.2s ease;
}

.site-footer__soc:hover {
  color: var(--color-accent);
  border-color: rgba(45, 92, 82, 0.25);
  transform: translateY(-3px);
  box-shadow: var(--shadow-sm);
}

.site-footer__list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.site-footer__list li {
  margin-bottom: 0.65rem;
}

.site-footer__list a {
  font-size: 14px;
  color: var(--color-ink-muted);
}

.site-footer__list a:hover {
  color: var(--color-accent-hover);
}

.site-footer__wa {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  min-height: var(--tap-min);
  padding: 0 1.1rem;
  margin-bottom: 1rem;
  border-radius: 999px;
  background: #25d366;
  color: #fff !important;
  font-size: 0.88rem;
  font-weight: 700;
  text-decoration: none;
}

.site-footer__handles {
  list-style: none;
  margin: 0;
  padding: 0;
}

.site-footer__handles li {
  margin-bottom: 0.45rem;
}

.site-footer__handles a {
  font-size: 0.88rem;
  color: var(--color-ink-muted);
}

.site-footer__handles a:hover {
  color: var(--color-accent);
}

.site-footer__bottom {
  padding: 1.25rem 0 2rem;
  border-top: 1px solid var(--color-border);
  background: var(--color-surface);
}

.site-footer__bottom-inner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.site-footer__bottom-brand {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem 1rem;
}

.site-footer__copy {
  margin: 0;
  font-size: 14px;
  color: var(--color-ink-muted);
}

.site-footer__bottom img {
  display: block;
  max-width: 100%;
  height: auto;
}
</style>
