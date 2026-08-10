#!/usr/bin/env node
/**
 * Download category-relevant product images into frontend/public/products/.
 * Usage: node scripts/migrate-product-images.mjs [--force]
 */
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const root = path.join(__dirname, '..')
const sourcesPath = path.join(root, 'backend/data/product_image_sources.json')
const poolPath = path.join(root, 'backend/data/product_image_pool.json')
const productsDir = path.join(root, 'frontend/public/products')
const force = process.argv.includes('--force')

const sources = JSON.parse(fs.readFileSync(sourcesPath, 'utf8'))
const filenames = Object.keys(sources)

fs.mkdirSync(productsDir, { recursive: true })

async function download(url, dest) {
  if (!force && fs.existsSync(dest) && fs.statSync(dest).size > 8000) {
    console.log('skip (exists)', path.basename(dest))
    return
  }
  const res = await fetch(url, {
    headers: { Accept: 'image/*', 'User-Agent': 'TheMinimark-Asset-Sync/1.0' },
    redirect: 'follow',
  })
  if (!res.ok) throw new Error(`HTTP ${res.status} for ${url}`)
  const buf = Buffer.from(await res.arrayBuffer())
  if (buf.length < 3000) throw new Error(`Too small (${buf.length} bytes) for ${url}`)
  fs.writeFileSync(dest, buf)
  console.log('saved', path.basename(dest), '-', sources[path.basename(dest)].label, `(${buf.length} bytes)`)
}

console.log('Downloading category-relevant product images…')
for (const filename of filenames) {
  const { source } = sources[filename]
  await download(source, path.join(productsDir, filename))
}

const pool = filenames.map((name) => `/products/${name}`)
fs.writeFileSync(poolPath, JSON.stringify(pool, null, 2) + '\n', 'utf8')
console.log('Updated', poolPath)
console.log('Done.', pool.length, 'images in pool')
