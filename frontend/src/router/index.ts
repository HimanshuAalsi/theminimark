import { createRouter, createWebHistory } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { useCatalogStore } from '@/stores/catalog'
import { useCartUiStore } from '@/stores/cartUi'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: DefaultLayout,
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('@/views/HomeView.vue'),
          meta: { title: 'Home' },
        },
        {
          path: 'shop',
          name: 'shop',
          component: () => import('@/views/ShopView.vue'),
          meta: { title: 'Shop' },
        },
        {
          path: 'create-your-set',
          name: 'create-your-set',
          component: () => import('@/views/CreateYourSetView.vue'),
          meta: { title: 'Create your own set' },
        },
        {
          path: 'personalise',
          name: 'personalise',
          component: () => import('@/views/PersonaliseView.vue'),
          meta: { title: 'Personalise' },
        },
        {
          path: 'wishlist',
          name: 'wishlist',
          component: () => import('@/views/WishlistView.vue'),
          meta: { title: 'Wishlist' },
        },
        {
          path: 'cart',
          name: 'cart',
          component: () => import('@/views/CartView.vue'),
          meta: { title: 'Cart' },
        },
        {
          path: 'checkout',
          name: 'checkout',
          component: () => import('@/views/CheckoutView.vue'),
          meta: { title: 'Checkout' },
        },
        {
          path: 'login',
          name: 'login',
          component: () => import('@/views/auth/LoginView.vue'),
          meta: { title: 'Sign in', guestOnly: true },
        },
        {
          path: 'register',
          name: 'register',
          component: () => import('@/views/auth/RegisterView.vue'),
          meta: { title: 'Create account', guestOnly: true },
        },
        {
          path: 'forgot-password',
          name: 'forgot-password',
          component: () => import('@/views/auth/ForgotPasswordView.vue'),
          meta: { title: 'Forgot password' },
        },
        {
          path: 'reset-password',
          name: 'reset-password',
          component: () => import('@/views/auth/ResetPasswordView.vue'),
          meta: { title: 'Reset password' },
        },
        {
          path: 'account',
          name: 'account',
          component: () => import('@/views/auth/ProfileView.vue'),
          meta: { title: 'Account', requiresAuth: true },
        },
        {
          path: ':pathMatch(.*)*',
          name: 'not-found',
          component: () => import('@/views/NotFoundView.vue'),
          meta: { title: 'Not found' },
        },
      ],
    },
  ],
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach(async (to, _from, next) => {
  const auth = useAuthStore()
  await auth.initialize()
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }
  if (to.meta.guestOnly && auth.isAuthenticated) {
    next({ name: 'account' })
    return
  }
  next()
})

router.afterEach((to) => {
  const title = to.meta.title as string | undefined
  document.title = title ? `${title} | The Minimark` : 'The Minimark'

  if (to.name === 'cart') {
    useCartUiStore().open()
  }

  if (
    to.name === 'home' ||
    to.name === 'shop' ||
    to.name === 'create-your-set' ||
    to.name === 'personalise' ||
    to.name === 'wishlist'
  ) {
    void useCatalogStore().ensureLoaded({ refresh: true })
  }
})

export default router
