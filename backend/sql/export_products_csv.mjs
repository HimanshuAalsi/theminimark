/**
 * Export products from local MySQL to Excel-friendly CSV (UTF-8 with BOM).
 *
 * Usage (from repo root):
 *   node backend/sql/export_products_csv.mjs
 *   node backend/sql/export_products_csv.mjs --out backend/data/my_export.csv
 */

import { execSync } from 'node:child_process'
import { writeFileSync, mkdirSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const root = join(__dirname, '..', '..')
const defaultOut = join(root, 'backend', 'data', 'products_catalog.csv')

const outPath = process.argv.includes('--out')
  ? process.argv[process.argv.indexOf('--out') + 1]
  : defaultOut

const HEADER = [
  'id',
  'slug',
  'name',
  'description',
  'price_inr',
  'compare_at_inr',
  'category',
  'image_url',
  'home_bestseller',
  'home_magnetic_carousel',
  'sort_order',
  'is_active',
  'sku',
  'stock_quantity',
  'tags',
  'bookmark_style',
  'weight_grams',
  'internal_notes',
]

function csvCell(value) {
  if (value === null || value === undefined) return ''
  const s = String(value)
  if (/[",\r\n]/.test(s)) return `"${s.replace(/"/g, '""')}"`
  return s
}

function rowToCsv(row) {
  return HEADER.map((col) => csvCell(row[col] ?? '')).join(',')
}

function mysqlPath() {
  const candidates = [
    'C:\\xampp\\mysql\\bin\\mysql.exe',
    'mysql',
  ]
  for (const p of candidates) {
    try {
      execSync(`"${p}" --version`, { stdio: 'pipe' })
      return p
    } catch {
      /* try next */
    }
  }
  return null
}

function fetchRows() {
  const mysql = mysqlPath()
  if (!mysql) {
    throw new Error('MySQL client not found. Start XAMPP MySQL or add mysql to PATH.')
  }
  const sql = `SELECT id, slug, name, description, price, compare_at, category, image_url,
    home_bestseller, home_secondary, sort_order, is_active
    FROM products ORDER BY sort_order ASC, name ASC`
  const cmd = `"${mysql}" -u root theminimark -N -B -e "${sql.replace(/\s+/g, ' ')}"`
  const raw = execSync(cmd, { encoding: 'utf8', maxBuffer: 10 * 1024 * 1024 })
  const lines = raw.trim().split(/\r?\n/).filter(Boolean)
  return lines.map((line) => {
    const parts = line.split('\t')
    return {
      id: parts[0],
      slug: parts[1],
      name: parts[2],
      description: parts[3] === 'NULL' ? '' : parts[3],
      price_inr: parts[4],
      compare_at_inr: parts[5] === 'NULL' ? '' : parts[5],
      category: parts[6],
      image_url: parts[7],
      home_bestseller: parts[8],
      home_magnetic_carousel: parts[9],
      sort_order: parts[10],
      is_active: parts[11],
      sku: '',
      stock_quantity: '',
      tags: '',
      bookmark_style: '',
      weight_grams: '',
      internal_notes: '',
    }
  })
}

try {
  const rows = fetchRows()
  mkdirSync(dirname(outPath), { recursive: true })
  const bom = '\uFEFF'
  const body = [HEADER.join(','), ...rows.map(rowToCsv)].join('\r\n')
  writeFileSync(outPath, bom + body, 'utf8')
  console.log(`Exported ${rows.length} products to:\n${outPath}`)
} catch (e) {
  console.error(e.message || e)
  process.exit(1)
}
