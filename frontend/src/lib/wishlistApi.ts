import { apiFetch, getApiBaseUrl } from '@/lib/api'
import type { SiteProduct } from '@/data/siteContent'

function apiPrefix(): string {
  return getApiBaseUrl() ? '/v1' : '/api/v1'
}

type WishlistResponse = {
  ok: true
  items?: SiteProduct[]
  productIds: string[]
}

export async function fetchWishlist(token: string): Promise<WishlistResponse> {
  return apiFetch<WishlistResponse>(`${apiPrefix()}/wishlist`, { authToken: token })
}

export async function addWishlistItem(token: string, productId: string): Promise<WishlistResponse> {
  return apiFetch<WishlistResponse>(`${apiPrefix()}/wishlist`, {
    method: 'POST',
    authToken: token,
    body: JSON.stringify({ productId }),
  })
}

export async function removeWishlistItem(token: string, productId: string): Promise<WishlistResponse> {
  return apiFetch<WishlistResponse>(`${apiPrefix()}/wishlist/${encodeURIComponent(productId)}`, {
    method: 'DELETE',
    authToken: token,
  })
}

export async function syncWishlist(token: string, productIds: string[]): Promise<WishlistResponse> {
  return apiFetch<WishlistResponse>(`${apiPrefix()}/wishlist/sync`, {
    method: 'POST',
    authToken: token,
    body: JSON.stringify({ productIds }),
  })
}
