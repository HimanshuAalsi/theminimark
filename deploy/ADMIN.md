# Admin portal

The Minimark admin portal lives at **`/admin`** in the Vue app. It uses the same PHP API with admin-only routes under `/v1/admin/*`.

## Setup (one time)

1. **Apply database migration** (phpMyAdmin or CLI):

   Import `backend/sql/migration_admin_portal.sql` into your shop database, or run:

   ```bash
   php backend/api/tools/apply-admin-migration.php
   ```

2. **Create an admin account:**

   ```bash
   php backend/api/tools/create_admin.php you@email.com "YourSecurePassword12" "Your Name"
   ```

3. **Uploads folder** — ensure `backend/uploads/` is writable by PHP (products images save to `backend/uploads/products/{category}/`).

4. **Production** — set `public_base_url` in `backend/api/config.local.php` to your site origin (e.g. `https://theminimark.com`) so image URLs resolve correctly.

## Sign in

Open **`https://your-domain.com/admin/login`** (local: `http://localhost:5174/admin/login`).

Use the admin email/password from step 2. Customer accounts cannot access admin routes.

## Features

| Section | What you can do |
|--------|------------------|
| **Dashboard** | Revenue, order counts, recent orders, top products |
| **Products** | Create, edit, delete; upload images to `/uploads/products/{category}/` |
| **Orders** | List, filter, open detail; update status, admin notes, shipping fields |
| **Customers** | Registered users, order count, revenue per email |
| **Newsletter** | Subscriber list and search |
| **Categories** | Add/edit categories with keywords, images, sort order |
| **Bulk import** | CSV + optional ZIP; template download; export catalog |

## Bulk import CSV

Reference file: `backend/data/products_bulk_import_TEMPLATE.csv`

Download from admin: **Bulk import → Download template CSV**

| Column | Required | Notes |
|--------|----------|--------|
| id | Yes | Unique product ID |
| slug, name | Yes | URL slug and display name |
| price_inr | Yes | Selling price |
| category_slug | Yes | Must exist under Categories |
| keywords | No | Comma-separated search/SEO terms |
| image_urls | Yes | Multiple URLs/paths separated by `\|` — first = main image |
| sku, stock_quantity | No | Inventory tracking |
| seo_title, seo_description | No | SEO meta |

**Extra image rows:** Same `id`, leave name/slug empty, put another path in `image_urls` (Shopify-style).

**ZIP upload:** Optional `images_zip` — filenames in CSV (e.g. `photo.jpg`) match files inside the ZIP.

Run migration before using advanced features:

```bash
php backend/api/tools/apply-advanced-migration.php
```

## API (for reference)

All require `Authorization: Bearer <token>` except `POST /v1/admin/login`.

- `GET /v1/admin/dashboard`
- `GET|POST /v1/admin/products`, `GET|PATCH|DELETE /v1/admin/products/{id}`
- `POST /v1/admin/upload` — multipart: `file`, `root=products`, `folder={category}`
- `GET /v1/admin/orders`, `GET|PATCH /v1/admin/orders/{id}`
- `GET /v1/admin/customers`, `GET /v1/admin/newsletter`
- `GET /v1/uploads/products/{category}/{filename}` — serves stored images

## Hostinger deploy

Upload:

- `backend/api/` (including new `lib/` files and `admin_routes.php`)
- `backend/uploads/` (writable; can start empty except `.gitkeep`)
- Rebuilt `frontend/dist/` (includes `/admin` routes)

Run migration + `create_admin.php` on the server or via phpMyAdmin + SQL user update (`role = 'admin'`).
