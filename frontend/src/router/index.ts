import { createRouter, createWebHistory } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { useAdminAuthStore } from '@/admin/stores/adminAuth'
import { useAuthStore } from '@/stores/auth'
import { useCatalogStore } from '@/stores/catalog'
import { useCartUiStore } from '@/stores/cartUi'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/admin/login',
      name: 'admin-login',
      component: () => import('@/admin/views/AdminLoginView.vue'),
      meta: { title: 'Admin sign in', guestAdmin: true },
    },
    {
      path: '/admin',
      component: () => import('@/admin/layouts/AdminLayout.vue'),
      meta: { requiresAdmin: true },
      children: [
        { path: '', redirect: { name: 'admin-dashboard' } },
        {
          path: 'dashboard',
          name: 'admin-dashboard',
          component: () => import('@/admin/views/AdminDashboardView.vue'),
          meta: { title: 'Admin dashboard' },
        },
        {
          path: 'products',
          name: 'admin-products',
          component: () => import('@/admin/views/AdminProductsView.vue'),
          meta: { title: 'Products' },
        },
        {
          path: 'bulk',
          name: 'admin-bulk',
          component: () => import('@/admin/views/AdminBulkUploadView.vue'),
          meta: { title: 'Bulk import' },
        },
        {
          path: 'converter',
          name: 'admin-converter',
          component: () => import('@/admin/views/AdminConverterView.vue'),
          meta: { title: 'WebP converter' },
        },
        {
          path: 'bookmark-showcase',
          name: 'admin-bookmark-showcase',
          component: () => import('@/admin/views/AdminBookmarkShowcaseView.vue'),
          meta: { title: 'Bookmark showcase' },
        },
        {
          path: 'inventory',
          name: 'admin-inventory',
          component: () => import('@/admin/views/AdminInventoryView.vue'),
          meta: { title: 'Inventory' },
        },
        {
          path: 'categories',
          name: 'admin-categories',
          component: () => import('@/admin/views/AdminCategoriesView.vue'),
          meta: { title: 'Categories' },
        },
        {
          path: 'categories/new',
          name: 'admin-category-new',
          component: () => import('@/admin/views/AdminCategoryEditView.vue'),
          meta: { title: 'New category' },
        },
        {
          path: 'categories/:id',
          name: 'admin-category-edit',
          component: () => import('@/admin/views/AdminCategoryEditView.vue'),
          meta: { title: 'Edit category' },
        },
        {
          path: 'products/new',
          name: 'admin-product-new',
          component: () => import('@/admin/views/AdminProductEditView.vue'),
          meta: { title: 'New product' },
        },
        {
          path: 'products/:id',
          name: 'admin-product-edit',
          component: () => import('@/admin/views/AdminProductEditView.vue'),
          meta: { title: 'Edit product' },
        },
        {
          path: 'orders',
          name: 'admin-orders',
          component: () => import('@/admin/views/AdminOrdersView.vue'),
          meta: { title: 'Orders' },
        },
        {
          path: 'orders/:id',
          name: 'admin-order',
          component: () => import('@/admin/views/AdminOrderDetailView.vue'),
          meta: { title: 'Order detail' },
        },
        {
          path: 'personalisation',
          name: 'admin-personalisation',
          component: () => import('@/admin/views/AdminPersonalisationView.vue'),
          meta: { title: 'Personalisations' },
        },
        {
          path: 'customers',
          name: 'admin-customers',
          component: () => import('@/admin/views/AdminCustomersView.vue'),
          meta: { title: 'Customers' },
        },
        {
          path: 'newsletter',
          name: 'admin-newsletter',
          component: () => import('@/admin/views/AdminNewsletterView.vue'),
          meta: { title: 'Newsletter' },
        },
        {
          path: 'home-page',
          name: 'admin-home-page',
          component: () => import('@/admin/views/AdminHomePageView.vue'),
          meta: { title: 'Landing page' },
        },
        {
          path: 'free-gifts',
          name: 'admin-free-gifts',
          component: () => import('@/admin/views/AdminFreeGiftsView.vue'),
          meta: { title: 'Free gifts' },
        },
        {
          path: 'feature-collections',
          name: 'admin-feature-collections',
          component: () => import('@/admin/views/AdminFeatureCollectionsView.vue'),
          meta: { title: 'Feature collections' },
        },
        {
          path: 'blog',
          name: 'admin-blog',
          component: () => import('@/admin/views/AdminBlogView.vue'),
          meta: { title: 'Blog' },
        },
        {
          path: 'blog/new',
          name: 'admin-blog-new',
          component: () => import('@/admin/views/AdminBlogEditView.vue'),
          meta: { title: 'New article' },
        },
        {
          path: 'blog/:id',
          name: 'admin-blog-edit',
          component: () => import('@/admin/views/AdminBlogEditView.vue'),
          meta: { title: 'Edit article' },
        },
        {
          path: 'coupons',
          name: 'admin-coupons',
          component: () => import('@/admin/views/AdminCouponsView.vue'),
          meta: { title: 'Coupons' },
        },
        {
          path: 'staff',
          name: 'admin-staff',
          component: () => import('@/admin/views/AdminStaffView.vue'),
          meta: { title: 'Staff' },
        },
      ],
    },
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
          path: 'shop/:category',
          name: 'shop-category',
          component: () => import('@/views/ShopView.vue'),
          meta: { title: 'Shop' },
        },
        {
          path: 'products/:slug',
          name: 'product',
          component: () => import('@/views/ProductView.vue'),
          meta: { title: 'Product' },
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
          meta: { title: 'Custom magnetic bookmark' },
        },
        {
          path: 'wishlist',
          name: 'wishlist',
          component: () => import('@/views/WishlistView.vue'),
          meta: { title: 'Wishlist' },
        },
        {
          path: 'blog',
          name: 'blog',
          component: () => import('@/views/BlogView.vue'),
          meta: { title: 'Blog' },
        },
        {
          path: 'blog/:slug',
          name: 'blog-post',
          component: () => import('@/views/BlogPostView.vue'),
          meta: { title: 'Article' },
        },
        {
          path: 'track-order',
          name: 'track-order',
          component: () => import('@/views/TrackOrderView.vue'),
          meta: { title: 'Track order' },
        },
        {
          path: 'policies/:slug',
          name: 'policy',
          component: () => import('@/views/PolicyView.vue'),
          meta: { title: 'Policy' },
          props: true,
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
          meta: { title: 'Checkout', hideBottomNav: true },
        },
        {
          path: 'login',
          name: 'login',
          component: () => import('@/views/auth/LoginView.vue'),
          meta: { title: 'Sign in', guestOnly: true, hideBottomNav: true },
        },
        {
          path: 'register',
          name: 'register',
          component: () => import('@/views/auth/RegisterView.vue'),
          meta: { title: 'Create account', guestOnly: true, hideBottomNav: true },
        },
        {
          path: 'forgot-password',
          name: 'forgot-password',
          component: () => import('@/views/auth/ForgotPasswordView.vue'),
          meta: { title: 'Forgot password', hideBottomNav: true },
        },
        {
          path: 'reset-password',
          name: 'reset-password',
          component: () => import('@/views/auth/ResetPasswordView.vue'),
          meta: { title: 'Reset password', hideBottomNav: true },
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
  if (to.path.startsWith('/admin')) {
    const adminAuth = useAdminAuthStore()
    await adminAuth.initialize()
    if (to.meta.requiresAdmin && !adminAuth.isAuthenticated) {
      next({ name: 'admin-login', query: { redirect: to.fullPath } })
      return
    }
    if (to.meta.guestAdmin && adminAuth.isAuthenticated) {
      next({ name: 'admin-dashboard' })
      return
    }
    next()
    return
  }

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
  const suffix = to.path.startsWith('/admin') ? 'Admin | The Minimark' : 'The Minimark'
  document.title = title ? `${title} | ${suffix}` : suffix

  if (to.name === 'cart') {
    useCartUiStore().open()
  }

  if (
    to.name === 'home' ||
    to.name === 'shop' ||
    to.name === 'shop-category' ||
    to.name === 'product' ||
    to.name === 'create-your-set' ||
    to.name === 'personalise' ||
    to.name === 'wishlist'
  ) {
    void useCatalogStore().ensureLoaded({ refresh: true })
  }
})

export default router
