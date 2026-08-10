# Storefront design system

Premium mobile-first UI layer for The Minimark Vue app.

## Structure

| Path | Purpose |
|------|---------|
| `src/styles/tokens.css` | Light/dark design tokens (`--tm-*`) |
| `src/stores/theme.ts` | Dark mode (light / dark / system) |
| `src/stores/searchUi.ts` | Search panel, recent searches, suggestions |
| `src/components/ui/` | Reusable primitives (Button, Sheet, Skeleton, EmptyState) |
| `src/components/search/SearchPanel.vue` | Full-screen mobile search |
| `src/components/shop/ShopFilterSheet.vue` | Bottom filter drawer (mobile) |
| `src/components/layout/MobileStickyBuyBar.vue` | Sticky add-to-cart (product page) |
| `src/components/layout/ThemeToggle.vue` | Light/dark toggle in header |

## Usage

- **Theme:** Sun/moon button in header; persisted in `localStorage`.
- **Search (mobile):** Tap search in app header → panel with recent + live suggestions.
- **Shop filters (mobile):** Tap **Filters** on shop → bottom sheet.
- **Product page:** Sticky buy bar on viewports ≤900px.

Existing pages keep their routes and Pinia stores; styling uses `--tm-*` tokens with legacy `--color-*` aliases.
