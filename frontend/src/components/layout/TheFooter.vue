<script setup lang="ts">
import { Facebook, Instagram, Loader2, Pin, Send, Twitter } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'
import SiteLogo from '@/components/layout/SiteLogo.vue'
import { useNewsletterForm } from '@/composables/useNewsletterForm'

const { email, feedback, busy, submit: footerSubmit } = useNewsletterForm('footer')
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
            <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="site-footer__soc">
              <Facebook :size="18" :stroke-width="1.75" />
            </a>
            <a href="#" target="_blank" rel="noopener noreferrer" aria-label="X" class="site-footer__soc">
              <Twitter :size="18" :stroke-width="1.75" />
            </a>
            <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="site-footer__soc">
              <Instagram :size="18" :stroke-width="1.75" />
            </a>
            <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Pinterest" class="site-footer__soc">
              <Pin :size="18" :stroke-width="1.75" />
            </a>
          </div>
        </div>

        <div class="site-footer__col">
          <h3 class="site-footer__h">Quick links</h3>
          <ul class="site-footer__list">
            <li><RouterLink to="/account">My account</RouterLink></li>
            <li><RouterLink to="/cart">Shopping Cart</RouterLink></li>
            <li><RouterLink to="/wishlist">Wishlist</RouterLink></li>
            <li><RouterLink to="/shop">Product Compare</RouterLink></li>
          </ul>
        </div>

        <div class="site-footer__col">
          <h3 class="site-footer__h">Information</h3>
          <ul class="site-footer__list">
            <li><RouterLink to="/shop">Privacy policy</RouterLink></li>
            <li><RouterLink to="/cart">Refund policy</RouterLink></li>
            <li><RouterLink to="/shop">Shipping &amp; Return</RouterLink></li>
            <li><RouterLink to="/shop">Term &amp; conditions</RouterLink></li>
          </ul>
        </div>

        <div class="site-footer__col">
          <h2 class="site-footer__h site-footer__h--lg">Newsletter</h2>
          <p class="site-footer__p">
            Occasional new arrivals and offers—unsubscribe anytime. No spam.
          </p>
          <form class="site-footer__form" @submit="footerSubmit">
            <div class="site-footer__field">
              <input
                v-model="email"
                type="email"
                class="site-footer__input"
                placeholder="Enter your email address..."
                autocomplete="email"
              />
              <button type="submit" class="site-footer__submit tm-press" aria-label="Subscribe" :disabled="busy">
                <Loader2 v-if="busy" :size="18" class="site-footer__spin" aria-hidden="true" />
                <Send v-else :size="18" :stroke-width="2" aria-hidden="true" />
              </button>
            </div>
            <p v-if="feedback" class="site-footer__feedback" role="status">{{ feedback }}</p>
          </form>
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
          src="https://theminimark.com/wp-content/uploads/2026/03/payment_icon.svg"
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

.site-footer__field {
  display: flex;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--color-surface-elevated);
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.site-footer__field:focus-within {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}

.site-footer__input {
  flex: 1;
  border: none;
  padding: 12px 12px;
  font: inherit;
  font-size: 14px;
}

.site-footer__submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  border: none;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  color: #fff;
  cursor: pointer;
  transition:
    filter 0.2s ease,
    transform 0.15s ease;
}

.site-footer__submit:hover:not(:disabled) {
  filter: brightness(1.06);
}

.site-footer__spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.site-footer__submit:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.site-footer__feedback {
  margin: 0.65rem 0 0;
  font-size: 0.8125rem;
  color: var(--color-ink-muted);
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
