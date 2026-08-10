<script setup lang="ts">
import {
  Bookmark,
  FileText,
  FolderTree,
  Gift,
  ImageDown,
  LayoutDashboard,
  LayoutTemplate,
  ListChecks,
  LogOut,
  Mail,
  Menu,
  Package,
  ShoppingBag,
  Sparkles,
  Tag,
  Upload,
  UserCog,
  Users,
  Warehouse,
  X,
} from 'lucide-vue-next'
import type { Component } from 'vue'
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import AdminNavGroup, { type AdminNavLink } from '@/admin/components/AdminNavGroup.vue'
import { adminDashboard } from '@/admin/lib/adminApi'
import { useAdminAuthStore } from '@/admin/stores/adminAuth'
import { useMediaQuery } from '@/composables/useMediaQuery'

interface NavGroupDef {
  id: string
  label: string
  icon: Component
  items: AdminNavLink[]
  visible?: boolean
  badge?: number
}

const auth = useAdminAuthStore()
const router = useRouter()
const route = useRoute()
const ordersBadge = ref(0)
const stockBadge = ref(0)
const navOpen = ref(false)
const openGroups = ref<Set<string>>(new Set(['shop']))

const isMobile = useMediaQuery('(max-width: 1023px)')

const role = computed(() => auth.user?.role ?? 'staff')
const canCoupons = computed(() => role.value === 'admin' || role.value === 'manager')
const canStaff = computed(() => role.value === 'admin')
const canBulk = computed(() => role.value !== 'staff')
const canContent = computed(() => role.value !== 'staff')

const pageTitle = computed(() => (route.meta.title as string) || 'Admin')

const navGroups = computed((): NavGroupDef[] => {
  const groups: NavGroupDef[] = [
    {
      id: 'shop',
      label: 'Shop',
      icon: ShoppingBag,
      badge: stockBadge.value || undefined,
      items: [
        { name: 'admin-products', label: 'Products', icon: ShoppingBag, match: ['admin-product-new', 'admin-product-edit'] },
        ...(canContent.value
          ? [{ name: 'admin-feature-collections', label: 'Feature collections', icon: ListChecks }]
          : []),
        { name: 'admin-categories', label: 'Categories', icon: FolderTree, match: ['admin-category-new', 'admin-category-edit'] },
        { name: 'admin-inventory', label: 'Inventory', icon: Warehouse, badge: stockBadge.value || undefined },
        ...(canContent.value ? [{ name: 'admin-free-gifts', label: 'Free gifts', icon: Gift }] : []),
        ...(canBulk.value
          ? [
              { name: 'admin-bulk', label: 'Bulk import', icon: Upload },
              { name: 'admin-converter', label: 'WebP converter', icon: ImageDown },
            ]
          : []),
        ...(canContent.value ? [{ name: 'admin-bookmark-showcase', label: 'Bookmark showcase', icon: Bookmark }] : []),
      ],
    },
    {
      id: 'sales',
      label: 'Sales',
      icon: Package,
      badge: ordersBadge.value || undefined,
      items: [
        { name: 'admin-orders', label: 'Orders', icon: Package, badge: ordersBadge.value || undefined, match: ['admin-order'] },
        ...(canCoupons.value ? [{ name: 'admin-coupons', label: 'Coupons', icon: Tag }] : []),
        { name: 'admin-personalisation', label: 'Personalisations', icon: Sparkles },
      ],
    },
    {
      id: 'customers',
      label: 'Customers',
      icon: Users,
      items: [
        { name: 'admin-customers', label: 'All customers', icon: Users },
        { name: 'admin-newsletter', label: 'Newsletter', icon: Mail },
        ...(canStaff.value ? [{ name: 'admin-staff', label: 'Staff accounts', icon: UserCog }] : []),
      ],
    },
  ]

  if (canContent.value) {
    groups.push({
      id: 'website',
      label: 'Website',
      icon: LayoutTemplate,
      items: [
        { name: 'admin-home-page', label: 'Landing page', icon: LayoutTemplate },
        { name: 'admin-blog', label: 'Blog', icon: FileText, match: ['admin-blog-new', 'admin-blog-edit'] },
      ],
    })
  }

  return groups
})

const tabItems = computed(() => {
  const items = [
    { name: 'admin-dashboard', label: 'Home', icon: LayoutDashboard },
    { name: 'admin-products', label: 'Products', icon: ShoppingBag },
    { name: 'admin-orders', label: 'Orders', icon: Package, badge: ordersBadge.value },
  ]
  if (canContent.value) {
    items.push({ name: 'admin-blog', label: 'Blog', icon: FileText })
  }
  return items
})

function groupHasActiveRoute(group: NavGroupDef): boolean {
  const current = route.name as string
  return group.items.some((item) => current === item.name || item.match?.includes(current))
}

function syncOpenGroups() {
  const next = new Set(openGroups.value)
  for (const group of navGroups.value) {
    if (groupHasActiveRoute(group)) next.add(group.id)
  }
  openGroups.value = next
}

function toggleGroup(id: string) {
  const next = new Set(openGroups.value)
  if (next.has(id)) next.delete(id)
  else next.add(id)
  openGroups.value = next
}

function isGroupOpen(id: string) {
  return openGroups.value.has(id)
}

watch(() => route.name, syncOpenGroups, { immediate: true })

watch(
  () => route.fullPath,
  () => {
    navOpen.value = false
  },
)

onMounted(async () => {
  try {
    const res = await adminDashboard()
    ordersBadge.value = res.stats.ordersToFulfill ?? 0
    stockBadge.value = (res.stats.inventory?.lowStock ?? 0) + (res.stats.inventory?.outOfStock ?? 0)
  } catch {
    /* optional */
  }
})

function logout() {
  auth.logout()
  router.push({ name: 'admin-login' })
}

function goTab(name: string) {
  router.push({ name })
}
</script>

<template>
  <div class="admin-root" :class="{ 'admin-root--nav-open': navOpen }">
    <div v-if="isMobile && navOpen" class="admin-backdrop" aria-hidden="true" @click="navOpen = false" />

    <div class="admin-shell">
      <aside class="admin-sidebar" :class="{ 'admin-sidebar--open': navOpen }">
        <div class="admin-sidebar__top">
          <div class="admin-sidebar__brand">
            The Minimark
            <span>Admin</span>
          </div>
          <button v-if="isMobile" type="button" class="admin-sidebar__close" aria-label="Close menu" @click="navOpen = false">
            <X :size="20" />
          </button>
        </div>

        <nav class="admin-nav">
          <RouterLink :to="{ name: 'admin-dashboard' }" class="admin-nav__top">
            <LayoutDashboard :size="16" />
            Dashboard
          </RouterLink>

          <AdminNavGroup
            v-for="group in navGroups"
            :key="group.id"
            :id="group.id"
            :label="group.label"
            :icon="group.icon"
            :items="group.items"
            :badge="group.badge"
            :open="isGroupOpen(group.id)"
            @toggle="toggleGroup(group.id)"
          />
        </nav>

        <div class="admin-sidebar__foot">
          <p v-if="auth.user" class="admin-sidebar__user">{{ auth.user.email }} · {{ auth.user.role }}</p>
          <button type="button" class="admin-btn admin-btn--ghost admin-btn--block" @click="logout">
            <LogOut :size="14" />
            Sign out
          </button>
        </div>
      </aside>

      <div class="admin-content-wrap">
        <header v-if="isMobile" class="admin-mobile-head">
          <button type="button" class="admin-mobile-head__menu" aria-label="Open menu" @click="navOpen = true">
            <Menu :size="22" />
          </button>
          <h1 class="admin-mobile-head__title">{{ pageTitle }}</h1>
          <span class="admin-mobile-head__spacer" aria-hidden="true" />
        </header>

        <main class="admin-main">
          <RouterView />
        </main>
      </div>
    </div>

    <nav v-if="isMobile" class="admin-tabbar" aria-label="Admin quick nav">
      <button
        v-for="item in tabItems"
        :key="item.name"
        type="button"
        class="admin-tabbar__item"
        :class="{ 'admin-tabbar__item--active': route.name === item.name }"
        @click="goTab(item.name)"
      >
        <span class="admin-tabbar__icon-wrap">
          <component :is="item.icon" :size="20" :stroke-width="route.name === item.name ? 2.5 : 2" />
          <span v-if="item.badge" class="admin-tabbar__badge">{{ item.badge > 9 ? '9+' : item.badge }}</span>
        </span>
        <span>{{ item.label }}</span>
      </button>
      <button type="button" class="admin-tabbar__item" :class="{ 'admin-tabbar__item--active': navOpen }" @click="navOpen = true">
        <Menu :size="20" />
        <span>Menu</span>
      </button>
    </nav>
  </div>
</template>
