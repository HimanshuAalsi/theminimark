# Maintenance page

A standalone **“Back soon”** page for The Minimark (matches shop fonts and colours).

## Enable on Hostinger

1. **Back up** your current `public_html/index.html` (rename to `index.html.shop`).
2. Copy **`deploy/maintenance/index.html`** → **`public_html/index.html`**.
3. Ensure **`public_html/main-logo.webp`** exists (same logo as the live shop).

Optional: hide the Vue app assets while maintaining — visitors only load this single HTML file.

## Restore the shop

1. Delete or rename `public_html/index.html`.
2. Restore from `index.html.shop`, or re-upload from `frontend/dist/index.html` after `npm run build --prefix frontend`.

## Customize

Edit `index.html`:

- **Email:** change `hello@theminimark.com` in the contact link.
- **Copy:** update the headline and lead paragraph.
- **Logo path:** default is `/main-logo.webp` at site root.
