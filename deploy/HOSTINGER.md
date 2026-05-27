# Deploy The Minimark on Hostinger Premium Web Hosting

This guide deploys:

- **Vue storefront** → `public_html/` (your domain root)
- **PHP API** → `public_html/api/`
- **MySQL** → Hostinger database (phpMyAdmin)

Replace `yourdomain.com` with your real domain (e.g. `theminimark.com`).

---

## What you need before starting

| Item | Notes |
|------|--------|
| Hostinger Premium plan | hPanel access |
| Domain | Connected to hosting (or use temporary Hostinger URL first) |
| Your PC | Node.js 20+ for building the frontend once |
| FTP or File Manager | To upload files |

You **do not** run Vite or `npm run dev` on Hostinger. You build on your computer, then upload static files.

---

## Part 1 — Build the site on your computer

### 1.1 Install dependencies (once)

Open terminal in the project folder (`theminimark`):

```bash
npm run install:all
```

### 1.2 Production build (frontend)

```bash
npm run build --prefix frontend
```

This creates **`frontend/dist/`** with `index.html`, `assets/`, and copies of public files like `main-logo.webp`.

### 1.3 (Optional) Set API URL at build time

**Recommended for this project:** leave empty — the app calls `/api/v1/...` on the same domain.

Only if your API lives on a **different** subdomain, create **`frontend/.env.production`**:

```env
VITE_API_BASE_URL=https://api.yourdomain.com
```

Then run `npm run build --prefix frontend` again.

---

## Part 2 — Hostinger hPanel setup

### 2.1 Log in

1. Go to [https://hpanel.hostinger.com](https://hpanel.hostinger.com)
2. Open your **Premium Web Hosting** plan
3. Click **Websites** → select your site → **Manage**

### 2.2 Turn on SSL (HTTPS)

1. **Security** → **SSL**
2. Install **Free SSL** (Let’s Encrypt) for your domain
3. Enable **Force HTTPS** when available

### 2.3 PHP version

1. **Advanced** → **PHP Configuration** (or **PHP** in site tools)
2. Select **PHP 8.2** or **8.3** (minimum **8.1**)
3. Save

---

## Part 3 — Create MySQL database

### 3.1 Create database + user

1. hPanel → **Databases** → **MySQL Databases**
2. Create a new database, e.g. `u123456789_minimark`
3. Create a user with a **strong password**
4. **Add user to database** with **All privileges**
5. Write down:

| Setting | Example (yours will differ) |
|---------|------------------------------|
| Host | `localhost` (Hostinger often uses this) |
| Database name | `u123456789_minimark` |
| Username | `u123456789_minimark` |
| Password | (your password) |

> On some Hostinger plans the host is `localhost`. If connection fails, check **Databases** → your DB → **Remote MySQL** / connection details in hPanel.

### 3.2 Import tables (phpMyAdmin)

1. hPanel → **Databases** → **phpMyAdmin** → open your database
2. Click **Import** tab
3. Import in this order (from your project on PC):

| Order | File on your PC |
|-------|------------------|
| 1 | `backend/sql/schema.sql` |
| 2 | `backend/sql/seed.sql` |

Wait until each import shows success.

**Optional** (only if you already had an older DB without extra tables):

- `backend/sql/wishlist.sql`
- `backend/sql/magnetic_bookmarks.sql`

For a **brand-new** database, `schema.sql` + `seed.sql` is enough.

### 3.3 (Optional) Import your real products from Excel

1. Edit `backend/data/products_catalog.csv` on your PC
2. Upload the CSV to the server (e.g. `api/data/products_catalog.csv`)
3. In hPanel **Terminal** (if enabled) or via SSH:

```bash
php api/tools/import_products_csv.php api/data/products_catalog.csv --dry-run
php api/tools/import_products_csv.php api/data/products_catalog.csv
```

Or re-import via phpMyAdmin using smaller SQL exports.

---

## Part 4 — Upload files to `public_html`

Use **File Manager** (hPanel → **Files** → **File Manager** → `public_html`) or **FTP** (FileZilla).

### 4.1 Target folder structure on server

```text
public_html/
├── .htaccess              ← from deploy/public_html.htaccess (SPA routing)
├── index.html             ← from frontend/dist/
├── main-logo.webp         ← from frontend/dist/ or frontend/public/
├── assets/                ← entire folder from frontend/dist/assets/
└── api/
    ├── .htaccess          ← from backend/api/.htaccess
    ├── index.php
    ├── router.php
    ├── bootstrap.php
    ├── config.local.php   ← you create this (see below)
    ├── config.example.php
    ├── data/
    │   └── site-default.json
    ├── lib/                 ← all PHP files in backend/api/lib/
    └── tools/               ← optional (import scripts)
```

### 4.2 Upload storefront (Vue)

Upload **everything inside** `frontend/dist/` into **`public_html/`** (not the `dist` folder itself).

- `index.html` must sit directly in `public_html/`
- `assets/` folder must be next to `index.html`

### 4.3 Upload API (PHP)

Upload the whole **`backend/api/`** folder to **`public_html/api/`**.

**Do not upload** `config.local.php` from your PC if it has local XAMPP passwords. Create a **new** file on the server (next step).

### 4.4 SPA routing file

Copy **`deploy/public_html.htaccess`** from the project to **`public_html/.htaccess`**.

If a `.htaccess` already exists in `public_html`, merge the rewrite rules or back up the old file first.

---

## Part 5 — API configuration on server

### 5.1 Create `public_html/api/config.local.php`

In File Manager, copy `config.example.php` → `config.local.php` and edit:

```php
<?php
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'u123456789_minimark',      // your DB name
        'user' => 'u123456789_minimark',      // your DB user
        'pass' => 'YOUR_STRONG_PASSWORD',
        'charset' => 'utf8mb4',
    ],
    'api_base_path' => '/api',
    'cors_origin' => 'https://yourdomain.com',
    'auth' => [
        'session_ttl_days' => 30,
        'reset_ttl_hours' => 1,
        'expose_reset_token' => false,
    ],
];
```

| Key | Production value |
|-----|------------------|
| `api_base_path` | **`/api`** (API URL is `https://yourdomain.com/api/v1/...`) |
| `cors_origin` | Your real site URL, or `*` for testing only |
| `expose_reset_token` | **`false`** always on live site |

**Security:** `config.local.php` must never be committed to Git. It is already in `.gitignore`.

### 5.2 File permissions

Folders: `755`, files: `644`. PHP must be able to read `config.local.php` and `data/`.

---

## Part 6 — Test after upload

Open these URLs in the browser (replace domain):

| Test | URL | Expected |
|------|-----|----------|
| API health | `https://yourdomain.com/api/v1/health` | JSON `"ok": true` or similar |
| Database | `https://yourdomain.com/api/v1/ready` | Success / connected |
| Products | `https://yourdomain.com/api/v1/products` | JSON with product list |
| Auth tables | `https://yourdomain.com/api/v1/auth/install-status` | `"authTablesReady": true` |
| Shop | `https://yourdomain.com/shop` | Shop page (not 404) |
| Home | `https://yourdomain.com/` | Homepage with products |

If **shop** shows demo/fallback products only, the API is not connected — fix `config.local.php` and DB import.

If **/shop** returns **404**, fix root `.htaccess` (Part 4.4).

If **/api/v1/products** returns **404**, check `public_html/api/.htaccess` exists and **mod_rewrite** is enabled.

Fallback API URL (no rewrite):

`https://yourdomain.com/api/index.php?path=v1/products`

---

## Online payments (India — Razorpay)

See **`deploy/RAZORPAY.md`**: run `migration_payment_razorpay.sql`, add API keys to `config.local.php`, rebuild frontend.

---

## Part 7 — Go live checklist

- [ ] SSL enabled (padlock in browser)
- [ ] `config.local.php` on server with Hostinger DB credentials
- [ ] `api_base_path` = `/api`
- [ ] Database imported (`schema.sql` + `seed.sql` or your CSV)
- [ ] `frontend/dist` uploaded to `public_html`
- [ ] `backend/api` uploaded to `public_html/api`
- [ ] Root `.htaccess` for Vue routes
- [ ] Register / login tested
- [ ] Add to cart + checkout tested
- [ ] Remove or protect any test PHP tools if you uploaded `api/tools/`

---

## Part 8 — Updating the site later

1. On PC: change code → `npm run build --prefix frontend`
2. Upload **only** changed files:
   - Replace `public_html/index.html`
   - Replace `public_html/assets/` (easiest: delete old `assets` folder, upload new one)
3. API changes: upload changed PHP files under `public_html/api/`
4. Database changes: run new `.sql` migrations in phpMyAdmin or use import script

---

## Troubleshooting

### Blank page or white screen

- Browser **F12** → **Console** for errors
- Confirm `assets/` folder uploaded and paths in `index.html` match

### Products show old/demo data only

- API not reachable; check `https://yourdomain.com/api/v1/products`
- Wrong `config.local.php` database name or password

### CORS errors in browser console

- Set `cors_origin` in `config.local.php` to `https://yourdomain.com`
- Or build with `VITE_API_BASE_URL` pointing to same host

### 500 error on API

- hPanel → **Logs** → **Error log**
- Usually wrong DB password or missing `config.local.php`

### Forgot-password email not sent

- Expected: this project stores reset tokens in DB but does not send email yet. Wire SMTP in PHP later or use Hostinger email + custom script.

---

## Quick reference — local vs production

| | Local (`npm run dev`) | Hostinger production |
|--|----------------------|----------------------|
| Storefront | `http://localhost:5173` | `https://yourdomain.com` |
| API | Proxied `/api` → PHP :8888 | `https://yourdomain.com/api` |
| `api_base_path` | `''` | `'/api'` |
| Database | XAMPP `theminimark` | Hostinger MySQL name |

---

For product Excel workflow after go-live, see **`backend/data/PRODUCTS_CATALOG_README.md`**.
