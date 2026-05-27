# The Minimark — PHP / MySQL API

This folder powers the JSON API used by the Vue storefront: products, site copy (top banner), newsletter signups, and order intake (for fulfilment workflows you wire later).

## What you get

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/v1/health` | Liveness check (no database). |
| GET | `/v1/ready` | Confirms database connectivity. |
| GET | `/v1/site` | Returns JSON from `api/data/site-default.json` (edit on server for promos). |
| GET | `/v1/products` | Query: `category`, `q`, `sort` (`featured` \| `price-asc` \| `price-desc` \| `name`), `home_bestseller=1`, `home_secondary=1`. |
| GET | `/v1/products/{slug}` | Single active product. |
| POST | `/v1/newsletter` | JSON body `{ "email": "...", "source": "footer" }`. |
| POST | `/v1/orders` | JSON body `{ "customerEmail", "customerName?", "currency?", "lines": [{ "productId", "name", "unitPrice", "quantity" }], "notes?" }`. |
| POST | `/v1/auth/register` | `{ "email", "password", "fullName?" }` → `{ ok, user, token }` (201). |
| POST | `/v1/auth/login` | `{ "email", "password" }` → `{ ok, user, token }` (200) or 401. |
| POST | `/v1/auth/logout` | `Authorization: Bearer <token>` (optional). Always 200. |
| GET | `/v1/auth/me` | Bearer token → `{ ok, user }` or 401. |
| PATCH | `/v1/auth/me` | Bearer; body `{ "fullName"?, "email"?, "currentPassword"? }` — `currentPassword` required when changing `email`. |
| POST | `/v1/auth/change-password` | Bearer; `{ "currentPassword", "newPassword" }` — invalidates all sessions. |
| POST | `/v1/auth/forgot-password` | `{ "email" }` — always 200; stores reset token (email sending not included). |
| POST | `/v1/auth/reset-password` | `{ "token", "password" }` — consumes token, logs user out everywhere. |
| GET | `/v1/auth/install-status` | JSON: which MySQL database the API is using and whether `users` / `user_sessions` / `password_reset_tokens` exist. |

Product objects use camelCase (`compareAt`, `image`) so they map cleanly to the Vue `SiteProduct` shape.

### Product catalogue (Excel / CSV)

Manage products in Excel using the files in **`backend/data/`**:

| File | Purpose |
|------|---------|
| `products_catalog_TEMPLATE.csv` | Blank template + example rows for new products |
| `products_catalog.csv` | Full export of the current database (regenerate below) |
| `PRODUCTS_CATALOG_README.md` | Column definitions and valid values |

Export from MySQL (XAMPP running, database `theminimark`):

```bash
node backend/sql/export_products_csv.mjs
```

Import after editing (upsert by `id`):

```bash
php backend/api/tools/import_products_csv.php backend/data/products_catalog.csv
php backend/api/tools/import_products_csv.php backend/data/products_catalog.csv --dry-run
```

Extra columns you add in Excel (e.g. `sku`, `stock_quantity`) are kept in the sheet for your records; only the standard columns are imported unless you extend `import_products_csv.php`.

### Auth database

Fresh installs: **`sql/schema.sql`** already creates `users`, `user_sessions`, and `password_reset_tokens`.

Existing databases that were created before auth:

1. **Recommended (same DB as `config.local.php`):** from the **repository root** run:
   ```bash
   php backend/api/tools/apply-auth-migration.php
   ```
   Uses the API’s MySQL credentials, so it always targets the database your PHP app actually connects to.

2. **Or** import **`sql/migration_auth_tables.sql`** in phpMyAdmin — but you must select the **same** database name as `db.name` in `api/config.local.php` (local) or on the server. If you import on Hostinger but run PHP against `127.0.0.1` / another DB, tables will still be “missing”.

3. **Check:** open **`/api/v1/auth/install-status`** in the browser (with the Vite dev proxy) or **`https://yourdomain.com/api/v1/auth/install-status`** on hosting. You should see `"authTablesReady": true` after a successful migration.

Optional **`config.local.php`** `auth` block (see **`config.example.php`**): `expose_reset_token` may be `true` **only in local dev** so `POST /v1/auth/forgot-password` returns `debugResetToken` for testing without mail. Never enable in production.

To send real reset emails later, hook `tm_auth_forgot_password` (or a queue) with your SMTP provider; the API already stores a time-limited token server-side.

## Hostinger (typical)

1. In **hPanel → Databases**, create a MySQL database and user; note host, name, user, password.
2. Open **phpMyAdmin**, select that database, import **`sql/schema.sql`**, then **`sql/seed.sql`** (loads **12** legacy showcase products plus **100** generated catalogue rows across all shop categories). (Upgrading an older DB without user tables? Run **`sql/migration_auth_tables.sql`** instead of re-importing the full schema if you must keep data.) To regenerate the bulk block after editing templates: `node backend/sql/generate_extra_products.mjs` then `node backend/sql/merge_seed.mjs`.
3. Upload the **`api`** directory to your hosting (common pattern: `public_html/api/` so the site is at `https://yourdomain.com/api/...`).
4. Copy **`api/config.example.php`** to **`api/config.local.php`** and set the `db` array. Set **`api_base_path`** to **`/api`** when the site URL is `https://yourdomain.com/api/v1/...` (typical Hostinger subfolder). For local `php -S`, keep **`api_base_path`** as **`''`** (matches the bundled `config.example.php`).
5. Ensure **PHP 8.1+** is selected for that location.
6. If URLs like `https://yourdomain.com/api/v1/products` return 404, confirm **mod_rewrite** is on and `.htaccess` is allowed. Fallback URL: `https://yourdomain.com/api/index.php?path=v1/products`

## Local development

### PHP + Vite (any OS)

**Single command from the repository root** (starts PHP on **8888** and the Vite app in **frontend**):

```bash
npm install
npm run dev
```

Uses root `package.json` with `concurrently`. Stop with **Ctrl+C** (both processes exit).

**Database:** `backend/api/config.local.php` defaults to **127.0.0.1:3306** / **root** / empty password (typical for XAMPP / Laragon / a local MariaDB install). Create database **`theminimark`**, then import **`backend/sql/schema.sql`** and **`backend/sql/seed.sql`**.

- **Docker (optional):** install Docker Desktop, then run **`npm run dev:db`**. MySQL is published on the host as **127.0.0.1:3307** with password **`root`** — set **`port` => 3307** and **`pass` => 'root'`** in `config.local.php`, or keep **3306** if you use a local server instead.
- **Check ports:** from the repo root run **`npm run check:db`** (Windows PowerShell).

Manual split (same as before):

```bash
cd backend/api
copy config.example.php config.local.php
# Edit config.local.php — set api_base_path to "" when using PHP's built-in server.
php -S 127.0.0.1:8888 router.php
```

`router.php` forwards paths like `/v1/products` to `index.php`. Without it, the built-in server returns 404 for those URLs.

The Vue app proxies `/api` to this server (see `frontend/vite.config.ts`). With the proxy, leave **`VITE_API_BASE_URL`** empty and call paths like **`/api/v1/products`**. Open the app at **`http://localhost:5173`** (or the next free port Vite prints, e.g. **5174**).

### Windows + MariaDB in this repo

If the MariaDB service under `C:\Program Files\...` cannot write to its data directory (common under a non-admin account), use the project datadir under **`backend/mysql-data/`** (gitignored after first use):

1. One-time: run **`mariadb-install-db.exe`** from your MariaDB `bin` folder with `--datadir` set to `backend\mysql-data` (see MariaDB docs). A **`local.ini`** is provided next to the data dir with a **short path** `datadir` (no spaces) so `mysqld` starts reliably.
2. Start the server:  
   `"C:\Program Files\MariaDB 12.2\bin\mysqld.exe" --defaults-file=C:\Users\<you>\Desktop\minimark\theminimark\backend\mysql-data\local.ini`  
   (adjust the path to **`local.ini`**; use **8.3 short names** if your profile path contains spaces.)
3. Create DB and import:  
   `mysql -u root -e "CREATE DATABASE IF NOT EXISTS theminimark ..."` then pipe **`sql/schema.sql`** and **`sql/seed.sql`** into `mysql -u root theminimark`.
4. Ensure **`api/config.local.php`** points at **`127.0.0.1`**, database **`theminimark`**, user **`root`**, empty password (local only).

Stop MariaDB when finished: `taskkill /IM mysqld.exe /F` (Windows).

### Docker (optional)

From the repo root: **`docker compose up -d`** (requires Docker Desktop). Uses **`backend/docker/config.local.php`** mounted into the API container; MySQL data in a named volume.

**Re-running `schema.sql` / `seed.sql` after the first `docker compose up`:** MySQL only executes `/docker-entrypoint-initdb.d/` scripts when the data volume is **empty**. To reload products (e.g. after expanding `seed.sql`), either:

1. **`docker compose down -v`** then **`docker compose up -d`** (deletes DB volume and all data — dev only), or  
2. Import **`sql/seed.sql`** (or **`sql/seed_extra_products.sql`** if you already have the base 12 rows) manually into the running MySQL container, e.g.  
   `docker compose exec -T db mysql -uroot -proot theminimark < backend/sql/seed.sql`

The storefront refetches the product list whenever you open **Home** or **Shop** (no need to restart Vite or PHP for that).

**If `/v1/products` still shows `"count":12`:** the bulk rows were never inserted. With Docker up, from the repo root run:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/import-bulk-products-docker.ps1
```

Or manually:

`Get-Content backend/sql/seed_extra_products.sql -Raw | docker compose exec -T db mysql -uroot -proot theminimark`

(`seed_extra_products.sql` uses `INSERT IGNORE` so it is safe to run more than once.)

## Security notes

- Use strong database passwords; restrict DB user to this database only.
- Add HTTPS in production; consider rate limiting for `newsletter` and `orders` (not included here).
- `orders` stores submitted carts for manual or future payment processing—do not expose admin endpoints without authentication.
