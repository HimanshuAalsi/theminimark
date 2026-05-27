import fs from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const base = __dirname

const origPath = join(base, 'seed_original_head.sql')
// First 12 products only — keep in sync with repo if those rows change
const orig = fs.readFileSync(origPath, 'utf8')
const extra = fs.readFileSync(join(base, 'seed_extra_products.sql'), 'utf8')
const merged =
  orig.trimEnd() +
  '\n\n-- 100 additional catalogue products (regenerate: node backend/sql/generate_extra_products.mjs)\n' +
  extra.trimStart()

fs.writeFileSync(join(base, 'seed.sql'), merged, 'utf8')
console.log('Wrote seed.sql', merged.split('\n').length, 'lines')
