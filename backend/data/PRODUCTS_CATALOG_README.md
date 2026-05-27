# Product catalogue — Excel / CSV guide

Use these files to manage products in **Microsoft Excel**, **Google Sheets**, or **LibreOffice Calc**.

| File | Purpose |
|------|---------|
| `products_catalog_TEMPLATE.csv` | Empty template + 2 example rows — start here for new products |
| `products_catalog.csv` | Full export of your current database (regenerate with command below) |

## Open in Excel

1. Open Excel → **File → Open** → choose the `.csv` file.
2. If characters look wrong, use **Data → From Text/CSV** and pick **UTF-8**.
3. Save as `.xlsx` if you prefer: **File → Save As → Excel Workbook (.xlsx)**.
4. When saving back for import, prefer **CSV UTF-8 (Comma delimited)** so the site can read it.

## Column reference

### Required for the website (database)

| Column | Required | Example | Notes |
|--------|----------|---------|--------|
| `id` | Yes | `9001` | Unique ID. Letters, numbers, hyphen. Max 32 chars. |
| `slug` | Yes | `floral-magnetic-bookmark` | URL part: `/shop?…` and SEO. Lowercase, hyphens only. Must be unique. |
| `name` | Yes | `Floral Magnetic Bookmark` | Shown on product cards and cart. |
| `description` | No | `Botanical fold-over clip…` | Longer text on product detail (if you add PDP later). |
| `price_inr` | Yes | `399` | Selling price in **INR** (no ₹ symbol). |
| `compare_at_inr` | No | `499` | “Was” price for sale badge. Leave empty if none. |
| `category` | Yes | `bookmarks` | One of: `bookmarks`, `cards`, `calendars`, `magnets`, `hampers` |
| `image_url` | Yes | `https://…/photo.jpg` | Full HTTPS link to product image (square ~700×700 works well). |
| `home_bestseller` | Yes | `0` or `1` | `1` = show in home **Bestsellers** section (max ~10). |
| `home_magnetic_carousel` | Yes | `0` or `1` | `1` = show in home **Magnetic bookmarks** carousel. Product should be magnetic bookmark. |
| `sort_order` | Yes | `100` | Lower number = appears first in shop lists. |
| `is_active` | Yes | `1` | `1` = visible in shop, `0` = hidden. |

### Optional (your spreadsheet only)

These columns are included so you can plan inventory and metadata. They are **not** imported into MySQL yet unless you extend the import tool.

| Column | Example | Notes |
|--------|---------|--------|
| `sku` | `TM-9001` | Your internal SKU |
| `stock_quantity` | `50` | Stock count for your records |
| `tags` | `magnetic,gift` | Comma-separated tags |
| `bookmark_style` | `magnetic` | For bookmarks: `classic`, `magnetic`, or leave empty |
| `weight_grams` | `12` | Shipping weight |
| `internal_notes` | `Restock March` | Private notes, not shown on site |

You may add **new columns** to the right in Excel for anything else (supplier, cost price, etc.). Keep the first columns unchanged so a future import still works.

## Bookmark types (shop filters)

The shop filters bookmarks by **name/slug**, not a DB column:

- **Magnetic:** slug or name contains `magnetic`
- **Classic:** bookmark category and not magnetic

When adding bookmarks, put `magnetic` in the slug or name if they are magnetic clips.

## Regenerate export from database

With XAMPP MySQL running and database `theminimark`:

```bash
node backend/sql/export_products_csv.mjs
```

Custom output path:

```bash
node backend/sql/export_products_csv.mjs --out backend/data/my_products.csv
```

## Import edited CSV back into MySQL

After you edit the file:

```bash
php backend/api/tools/import_products_csv.php backend/data/products_catalog.csv
```

Options:

- `--dry-run` — validate only, no database changes
- `--deactivate-missing` — set `is_active=0` for products not listed in the CSV

Only the **required database columns** (through `is_active`) are imported. Extra Excel columns are ignored unless you extend `import_products_csv.php`.

## Tips

- Use **new unique `id`** values for new products (e.g. `9001`, `9002`, …).
- Do not duplicate `id` or `slug`.
- Prices: use numbers only (`399` not `₹399`).
- Use `1`/`0` (or `YES`/`NO` in import — script accepts both) for yes/no fields.
- Keep one row per product; no merged cells in Excel.
