import { ApiError, apiFetch, apiUrl, apiV1Prefix, getApiBaseUrl } from '@/lib/api'
import type { HomePageConfig } from '@/types/homePage'

function adminPath(suffix: string): string {
  const s = suffix.startsWith('/') ? suffix : `/${suffix}`
  return `${apiV1Prefix()}/admin${s}`
}

const TOKEN_KEY = 'tm_admin_token'

export interface AdminUser {
  id: number
  email: string
  fullName: string
  role: string
}

export interface AdminProductImage {
  id: number
  path: string
  url: string
  sortOrder: number
  isPrimary: boolean
}

export interface AdminCategory {
  id: number
  slug: string
  name: string
  description: string
  keywords: string
  imagePath: string
  imageUrl: string
  sortOrder: number
  isActive: boolean
  productCount?: number
}

export interface AdminProduct {
  id: string
  slug: string
  name: string
  description: string
  features?: string[]
  keywords: string
  price: number
  compareAt: number | null
  category: string
  subcategory?: string
  imageUrl: string
  imagePath: string
  images: AdminProductImage[]
  sku: string
  stockQuantity: number | null
  seoTitle: string
  seoDescription: string
  homeBestseller: boolean
  homeSecondary: boolean
  isActive: boolean
  sortOrder: number
  createdAt?: string
  updatedAt?: string
}

export interface AdminOrderSummary {
  id: number
  status: string
  customerEmail: string
  customerName: string | null
  currency: string
  subtotal: number
  itemsSubtotal: number | null
  couponCode?: string | null
  couponDiscount?: number | null
  refundId?: string | null
  createdAt: string
  lineCount: number
  lineSummary?: string | null
}

export interface AdminOrderDetail extends AdminOrderSummary {
  shippingPhone: string | null
  shippingAddress: string | null
  shippingLandmark: string | null
  shippingCity: string | null
  shippingState: string | null
  shippingPincode: string | null
  razorpayOrderId: string | null
  paymentId: string | null
  paidAt: string | null
  notes: string | null
  adminNotes: string | null
  parsedNotes: {
    shipping: string | null
    freeGift: string | null
    rewards: string[]
    customerNotes: string | null
  }
  lines: {
    id: number
    productId: string | null
    productName: string
    unitPrice: number
    quantity: number
    lineTotal: number
  }[]
  personalizations: AdminPersonalisation[]
  updatedAt: string | null
}

export interface AdminPersonalisation {
  id: number
  orderLineId: number
  orderId?: number
  orderStatus?: string
  customerEmail?: string
  customerName?: string | null
  orderCreatedAt?: string
  productType: string
  productName: string
  productId: string | null
  quantity: number
  unitPrice: number | null
  photoPath: string
  photoUrl: string
  zoom: number
  posX: number
  posY: number
  options: Record<string, unknown>
  createdAt: string
}

export interface AdminDashboardStats {
  products: { total: number; active: number }
  orders: { total: number; pending: number; paid: number; byStatus: Record<string, number> }
  revenue: { totalPaid: number; today: number; currency: string }
  newsletterSubscribers: number
  registeredCustomers: number
  inventory: { lowStock: number; outOfStock: number }
  ordersToFulfill: number
  recentOrders: AdminOrderSummary[]
  topProducts: { name: string; quantitySold: number; revenue: number }[]
  revenueByDay?: { date: string; revenue: number; orders: number }[]
}

export interface AdminCoupon {
  id: number
  code: string
  description: string | null
  discountType: 'percent' | 'fixed'
  discountValue: number
  minOrderInr: number
  maxUses: number | null
  usedCount: number
  firstOrderOnly: boolean
  isActive: boolean
  startsAt: string | null
  endsAt: string | null
  createdAt: string
}

export interface AdminStaffUser {
  id: number
  email: string
  fullName: string
  role: 'admin' | 'manager' | 'staff'
  createdAt: string
}

export interface AdminCustomer {
  id: number
  email: string
  fullName: string
  role: string
  orderCount: number
  orderRevenue: number
  createdAt: string
  recentOrders?: { id: number; status: string; subtotal: number; currency: string; createdAt: string }[]
}

export interface AdminNewsletterSubscriber {
  id: number
  email: string
  source: string | null
  createdAt: string
}

export interface AdminListMeta {
  total: number
  page: number
  perPage: number
  count?: number
}

export function getAdminToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

export function setAdminToken(token: string | null): void {
  try {
    if (token) localStorage.setItem(TOKEN_KEY, token)
    else localStorage.removeItem(TOKEN_KEY)
  } catch {
    /* ignore */
  }
}

function adminFetch<T>(path: string, init?: RequestInit): Promise<T> {
  return apiFetch<T>(path, { ...init, authToken: getAdminToken() })
}

export async function adminLogin(email: string, password: string) {
  const res = await apiFetch<{ ok: boolean; token?: string; user?: AdminUser; message?: string }>(
    adminPath('/login'),
    { method: 'POST', body: JSON.stringify({ email, password }) },
  )
  if (res.ok && res.token) setAdminToken(res.token)
  return res
}

export async function adminMe() {
  return adminFetch<{ ok: boolean; user?: AdminUser }>(adminPath('/me'))
}

export async function adminDashboard() {
  return adminFetch<{ ok: boolean; stats: AdminDashboardStats }>(adminPath('/dashboard'))
}

export async function adminListProducts(params: Record<string, string>) {
  const q = new URLSearchParams(params).toString()
  return adminFetch<{ ok: boolean; items: AdminProduct[]; meta: AdminListMeta }>(
    `${adminPath('/products')}?${q}`,
  )
}

export async function adminGetProduct(id: string) {
  return adminFetch<{ ok: boolean; product: AdminProduct }>(
    `${adminPath('/products')}/${encodeURIComponent(id)}`,
  )
}

export async function adminSaveProduct(body: Record<string, unknown>, id?: string) {
  if (id) {
    return adminFetch<{ ok: boolean; product?: AdminProduct; message?: string }>(
      `${adminPath('/products')}/${encodeURIComponent(id)}`,
      { method: 'PATCH', body: JSON.stringify(body) },
    )
  }
  return adminFetch<{ ok: boolean; product?: AdminProduct; message?: string }>(adminPath('/products'), {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function adminDeleteProduct(id: string) {
  return adminFetch<{ ok: boolean; message?: string }>(
    `${adminPath('/products')}/${encodeURIComponent(id)}`,
    { method: 'DELETE' },
  )
}

export type AdminProductBulkAction = 'activate' | 'deactivate' | 'delete'

export async function adminBulkProducts(ids: string[], action: AdminProductBulkAction) {
  return adminFetch<{ ok: boolean; message?: string; updated?: number; deleted?: number }>(
    adminPath('/products/bulk'),
    { method: 'PATCH', body: JSON.stringify({ ids, action }) },
  )
}

export async function adminUploadImage(
  file: File,
  folder: string,
  root: 'products' | 'site' | 'blog' = 'products',
) {
  const fd = new FormData()
  fd.append('file', file)
  fd.append('root', root)
  fd.append('folder', folder)
  const url = apiUrl(adminPath('/upload'))
  const token = getAdminToken()
  const headers = new Headers()
  if (token) headers.set('Authorization', `Bearer ${token}`)
  const res = await fetch(url, { method: 'POST', body: fd, headers })
  const text = await res.text()
  let data: { ok: boolean; path?: string; url?: string; message?: string } = { ok: false }
  if (text) {
    try {
      data = JSON.parse(text) as typeof data
    } catch {
      throw new ApiError('Invalid response', res.status)
    }
  }
  if (!res.ok) throw new ApiError(data.message ?? 'Upload failed', res.status, data)
  return data
}

export function adminImageSrc(pathOrUrl: string): string {
  if (!pathOrUrl) return ''
  if (pathOrUrl.startsWith('http')) return pathOrUrl
  const p = pathOrUrl.startsWith('/') ? pathOrUrl : `/${pathOrUrl}`
  const base = getApiBaseUrl()
  if (base) return `${base}/v1${p}`
  return `/api/v1${p}`
}

export async function adminListPersonalisations(params: Record<string, string>) {
  const q = new URLSearchParams(params).toString()
  return adminFetch<{ ok: boolean; items: AdminPersonalisation[]; meta: { total: number } }>(
    `${adminPath('/personalisation')}?${q}`,
  )
}

export async function adminListOrders(params: Record<string, string>) {
  const q = new URLSearchParams(params).toString()
  return adminFetch<{ ok: boolean; items: AdminOrderSummary[]; meta: AdminListMeta }>(
    `${adminPath('/orders')}?${q}`,
  )
}

export async function adminGetOrder(id: number) {
  return adminFetch<{ ok: boolean; order: AdminOrderDetail }>(`${adminPath('/orders')}/${id}`)
}

export async function adminUpdateOrder(id: number, body: Record<string, unknown>) {
  return adminFetch<{ ok: boolean; order?: AdminOrderDetail; message?: string }>(
    `${adminPath('/orders')}/${id}`,
    { method: 'PATCH', body: JSON.stringify(body) },
  )
}

export async function adminListCustomers(params: Record<string, string>) {
  const q = new URLSearchParams(params).toString()
  return adminFetch<{ ok: boolean; items: AdminCustomer[]; meta: AdminListMeta }>(
    `${adminPath('/customers')}?${q}`,
  )
}

export async function adminGetCustomer(id: number) {
  return adminFetch<{ ok: boolean; customer: AdminCustomer }>(`${adminPath('/customers')}/${id}`)
}

export async function adminListNewsletter(params: Record<string, string>) {
  const q = new URLSearchParams(params).toString()
  return adminFetch<{ ok: boolean; items: AdminNewsletterSubscriber[]; meta: AdminListMeta }>(
    `${adminPath('/newsletter')}?${q}`,
  )
}

export function adminNewsletterExportUrl(): string {
  return apiUrl(adminPath('/newsletter/export'))
}

export async function adminListCategories() {
  return adminFetch<{ ok: boolean; items: AdminCategory[] }>(adminPath('/categories'))
}

export async function adminGetCategory(id: number) {
  return adminFetch<{ ok: boolean; category: AdminCategory }>(`${adminPath('/categories')}/${id}`)
}

export async function adminSaveCategory(body: Partial<AdminCategory>, id?: number) {
  if (id) {
    return adminFetch<{ ok: boolean; category?: AdminCategory; message?: string }>(
      `${adminPath('/categories')}/${id}`,
      { method: 'PATCH', body: JSON.stringify(body) },
    )
  }
  return adminFetch<{ ok: boolean; category?: AdminCategory; message?: string }>(
    adminPath('/categories'),
    { method: 'POST', body: JSON.stringify(body) },
  )
}

export async function adminDeleteCategory(id: number) {
  return adminFetch<{ ok: boolean; message?: string }>(`${adminPath('/categories')}/${id}`, {
    method: 'DELETE',
  })
}

export function adminBulkTemplateUrl(): string {
  return apiUrl(adminPath('/bulk/template'))
}

export function adminBulkExportUrl(): string {
  return apiUrl(adminPath('/bulk/export'))
}

export async function adminGetHomePage() {
  return adminFetch<{ ok: boolean; homePage: HomePageConfig }>(adminPath('/home-page'))
}

export async function adminSaveHomePage(body: HomePageConfig) {
  return adminFetch<{ ok: boolean; homePage?: HomePageConfig; message?: string }>(
    adminPath('/home-page'),
    { method: 'PATCH', body: JSON.stringify(body) },
  )
}

export interface AdminSubcategory {
  id: number
  categorySlug: string
  slug: string
  name: string
  sortOrder: number
  isActive: boolean
}

export async function adminListSubcategories(categorySlug?: string) {
  const q = categorySlug ? `?category=${encodeURIComponent(categorySlug)}` : ''
  return adminFetch<{ ok: boolean; items: AdminSubcategory[] }>(`${adminPath('/subcategories')}${q}`)
}

export async function adminSaveSubcategory(body: Partial<AdminSubcategory>, id?: number) {
  if (id) {
    return adminFetch<{ ok: boolean; subcategory?: AdminSubcategory; message?: string }>(
      `${adminPath('/subcategories')}/${id}`,
      { method: 'PATCH', body: JSON.stringify(body) },
    )
  }
  return adminFetch<{ ok: boolean; subcategory?: AdminSubcategory; message?: string }>(
    adminPath('/subcategories'),
    { method: 'POST', body: JSON.stringify(body) },
  )
}

export async function adminDeleteSubcategory(id: number) {
  return adminFetch<{ ok: boolean; message?: string }>(`${adminPath('/subcategories')}/${id}`, {
    method: 'DELETE',
  })
}

export async function adminGetFreeGifts() {
  return adminFetch<{ ok: boolean; freeGifts: { productIds: string[] } }>(adminPath('/free-gifts'))
}

export async function adminSaveFreeGifts(productIds: string[]) {
  return adminFetch<{ ok: boolean; freeGifts?: { productIds: string[] }; message?: string }>(
    adminPath('/free-gifts'),
    { method: 'PATCH', body: JSON.stringify({ productIds }) },
  )
}

export interface AdminFeatureCollection {
  id: string
  name: string
  category: string
  features: string[]
  updatedAt?: string | null
}

export async function adminListFeatureCollections() {
  return adminFetch<{ ok: boolean; collections: AdminFeatureCollection[] }>(
    adminPath('/feature-collections'),
  )
}

export async function adminCreateFeatureCollection(body: {
  name: string
  category?: string
  features?: string[]
}) {
  return adminFetch<{
    ok: boolean
    collection?: AdminFeatureCollection
    collections?: AdminFeatureCollection[]
    message?: string
  }>(adminPath('/feature-collections'), { method: 'POST', body: JSON.stringify(body) })
}

export async function adminUpdateFeatureCollection(
  id: string,
  body: Partial<{ name: string; category: string; features: string[] }>,
) {
  return adminFetch<{
    ok: boolean
    collection?: AdminFeatureCollection
    collections?: AdminFeatureCollection[]
    message?: string
  }>(`${adminPath('/feature-collections')}/${encodeURIComponent(id)}`, {
    method: 'PATCH',
    body: JSON.stringify(body),
  })
}

export async function adminDeleteFeatureCollection(id: string) {
  return adminFetch<{ ok: boolean; collections?: AdminFeatureCollection[]; message?: string }>(
    `${adminPath('/feature-collections')}/${encodeURIComponent(id)}`,
    { method: 'DELETE' },
  )
}

export async function adminBulkImport(csv: File, imagesZip: File | null, dryRun: boolean) {
  const fd = new FormData()
  fd.append('csv', csv)
  if (imagesZip) fd.append('images_zip', imagesZip)
  if (dryRun) fd.append('dryRun', '1')
  const url = apiUrl(adminPath('/bulk/import'))
  const token = getAdminToken()
  const headers = new Headers()
  if (token) headers.set('Authorization', `Bearer ${token}`)
  const res = await fetch(url, { method: 'POST', body: fd, headers })
  const text = await res.text()
  let data: {
    ok: boolean
    imported?: number
    errors?: string[]
    warnings?: string[]
    message?: string
  } = { ok: false }
  if (text) {
    try {
      data = JSON.parse(text) as typeof data
    } catch {
      throw new ApiError(text.slice(0, 200) || 'Import failed — server returned invalid response', res.status)
    }
  }
  if (!res.ok) throw new ApiError(data.message ?? 'Import failed', res.status, data)
  return data
}

export async function adminListCoupons() {
  return adminFetch<{ ok: boolean; items: AdminCoupon[] }>(adminPath('/coupons'))
}

export async function adminCreateCoupon(body: Record<string, unknown>) {
  return adminFetch<{ ok: boolean; coupon?: AdminCoupon; message?: string }>(adminPath('/coupons'), {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function adminUpdateCoupon(id: number, body: Record<string, unknown>) {
  return adminFetch<{ ok: boolean; coupon?: AdminCoupon; message?: string }>(
    `${adminPath('/coupons')}/${id}`,
    { method: 'PATCH', body: JSON.stringify(body) },
  )
}

export async function adminDeleteCoupon(id: number) {
  return adminFetch<{ ok: boolean; message?: string }>(`${adminPath('/coupons')}/${id}`, {
    method: 'DELETE',
  })
}

export async function adminListStaff() {
  return adminFetch<{ ok: boolean; items: AdminStaffUser[] }>(adminPath('/staff'))
}

export async function adminCreateStaff(body: Record<string, unknown>) {
  return adminFetch<{ ok: boolean; user?: AdminStaffUser; message?: string }>(adminPath('/staff'), {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function adminUpdateStaff(id: number, body: Record<string, unknown>) {
  return adminFetch<{ ok: boolean; message?: string }>(`${adminPath('/staff')}/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(body),
  })
}

export async function adminRevenueAnalytics(days = 30) {
  return adminFetch<{ ok: boolean; items: { date: string; revenue: number; orders: number }[] }>(
    `${adminPath('/analytics/revenue')}?days=${days}`,
  )
}

export interface ConverterSettings {
  webpQuality: number
  maxDimension: number
  scopes: {
    products: boolean
    site: boolean
    personalise: boolean
  }
  reoptimizeExistingWebp: boolean
  updateDatabasePaths: boolean
}

export interface ConverterScanSummary {
  totalFiles: number
  totalBytes: number
  byExtension: Record<string, number>
}

export async function adminConverterStatus() {
  return adminFetch<{
    ok: boolean
    webpAvailable: boolean
    zipAvailable?: boolean
    settings: ConverterSettings
    summary: ConverterScanSummary
  }>(adminPath('/converter'))
}

export async function adminConverterGetSettings() {
  return adminFetch<{ ok: boolean; settings: ConverterSettings; webpAvailable: boolean }>(
    adminPath('/converter/settings'),
  )
}

export async function adminConverterSaveSettings(settings: Partial<ConverterSettings>) {
  return adminFetch<{ ok: boolean; settings?: ConverterSettings; message?: string }>(
    adminPath('/converter/settings'),
    { method: 'PATCH', body: JSON.stringify(settings) },
  )
}

export async function adminConverterScan() {
  return adminFetch<{
    ok: boolean
    webpAvailable: boolean
    zipAvailable?: boolean
    settings: ConverterSettings
    summary: ConverterScanSummary
    sample: { path: string; sizeBytes: number; format: string }[]
  }>(adminPath('/converter/scan'), { method: 'POST', body: JSON.stringify({}) })
}

export async function adminConverterRun(body: {
  dryRun?: boolean
  offset?: number
  limit?: number
}) {
  return adminFetch<{
    ok: boolean
    dryRun: boolean
    progress: { offset: number; nextOffset: number; limit: number; total: number; done: boolean }
    stats: {
      converted: number
      skipped: number
      failed: number
      bytesBefore: number
      bytesAfter: number
      bytesSaved: number
      dbRowsUpdated: number
    }
    items: { oldPath: string; newPath: string; bytesSaved: number }[]
    errors: string[]
    message?: string
  }>(adminPath('/converter/run'), { method: 'POST', body: JSON.stringify(body) })
}

export interface ConverterUploadResult {
  ok: boolean
  jobId?: string
  fileCount?: number
  zipBytes?: number
  stats?: { bytesBefore: number; bytesAfter: number; bytesSaved: number; failed: number }
  files?: {
    originalName: string
    webpName: string
    bytesBefore: number
    bytesAfter: number
    bytesSaved: number
  }[]
  errors?: string[]
  message?: string
}

export async function adminConverterUploadFiles(files: File[]): Promise<ConverterUploadResult> {
  const fd = new FormData()
  for (const file of files) {
    fd.append('files[]', file)
  }
  const url = apiUrl(adminPath('/converter/upload'))
  const token = getAdminToken()
  const headers = new Headers()
  if (token) headers.set('Authorization', `Bearer ${token}`)
  const res = await fetch(url, { method: 'POST', body: fd, headers })
  const text = await res.text()
  let data: ConverterUploadResult = { ok: false }
  if (text) {
    try {
      data = JSON.parse(text) as ConverterUploadResult
    } catch {
      throw new ApiError(text.slice(0, 200) || 'Invalid response', res.status)
    }
  }
  if (!res.ok) throw new ApiError(data.message ?? 'Upload failed', res.status, data)
  return data
}

export function adminConverterDownloadUrl(jobId: string): string {
  return apiUrl(adminPath(`/converter/download/${encodeURIComponent(jobId)}`))
}

export interface AdminBlogPost {
  id: number
  slug: string
  title: string
  excerpt: string
  contentHtml: string
  status: 'draft' | 'published' | 'scheduled'
  featuredImagePath: string | null
  featuredImageUrl: string | null
  authorName: string
  tags: string[]
  readingTimeMin: number | null
  publishedAt: string | null
  isFeatured: boolean
  metaTitle: string
  metaDescription: string
  metaKeywords: string
  canonicalUrl: string
  robotsIndex: boolean
  ogTitle: string
  ogDescription: string
  ogImagePath: string | null
  ogImageUrl: string | null
  ogType: string
  twitterCard: string
  createdAt: string
  updatedAt: string | null
}

export async function adminListBlogPosts(params: Record<string, string>) {
  const q = new URLSearchParams(params).toString()
  return adminFetch<{ ok: boolean; items: AdminBlogPost[]; meta: AdminListMeta }>(
    `${adminPath('/blog')}?${q}`,
  )
}

export async function adminGetBlogPost(id: number) {
  return adminFetch<{ ok: boolean; post: AdminBlogPost }>(`${adminPath('/blog')}/${id}`)
}

export async function adminSaveBlogPost(body: Record<string, unknown>, id?: number) {
  if (id) {
    return adminFetch<{ ok: boolean; post?: AdminBlogPost; message?: string }>(
      `${adminPath('/blog')}/${id}`,
      { method: 'PATCH', body: JSON.stringify(body) },
    )
  }
  return adminFetch<{ ok: boolean; post?: AdminBlogPost; message?: string }>(adminPath('/blog'), {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function adminDeleteBlogPost(id: number) {
  return adminFetch<{ ok: boolean; message?: string }>(`${adminPath('/blog')}/${id}`, {
    method: 'DELETE',
  })
}
