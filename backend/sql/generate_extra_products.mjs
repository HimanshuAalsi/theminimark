/**
 * Writes seed_extra_products.sql (100 rows). Then run: node backend/sql/merge_seed.mjs
 */
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

function esc(s) {
  return String(s).replace(/\\/g, '\\\\').replace(/'/g, "''")
}

const categories = ['bookmarks', 'cards', 'calendars', 'magnets', 'hampers']
const perCategory = 20
const total = categories.length * perCategory

const bookmarkTypes = [
  'Linen tassel',
  'Brass clip',
  'Pressed flower',
  'Leather tab',
  'Silk ribbon',
  'Wood veneer',
  'Origami crane',
  'Magnetic strip',
  'Quote card',
  'Monogram clip',
  'Vintage map',
  'Botanical print',
  'Minimal metal',
  'Embroidered felt',
  'Acrylic charm',
  'Washi wrap set',
  'Library due-date',
  'Seasonal leaf',
  'Constellation',
  'Watercolour wash',
]

const cardTypes = [
  'Birthday confetti',
  'Thank-you botanical',
  'Sympathy gentle',
  'New baby',
  'Wedding congrats',
  'Graduation cap',
  'Retirement toast',
  'Get well soon',
  'Good luck exam',
  'Housewarming key',
  'Anniversary gold',
  'Friendship quote',
  'Mothers day',
  'Fathers day',
  'Blank inside set',
  'Punny food',
  'Minimal line art',
  'Floral wreath',
  'Pet portrait frame',
  'Holiday snow',
]

const calendarTypes = [
  'Desk easel',
  'Wall grid',
  'Family planner',
  'Academic year',
  'Pocket monthly',
  'Minimal black',
  'Botanical year',
  'Watercolour seasons',
  'Moon phases',
  'Habit tracker',
  'Meal planner',
  'Stickers included',
  'Recycled paper',
  'Large print',
  'Compact purse',
  'Kitchen menu',
  'Garden sowing',
  'Student assignment',
  'Photo frame top',
  'Undated flexible',
]

const magnetTypes = [
  'Photo tile',
  'Quote round',
  'Alphabet letter',
  'State outline',
  'Emoji mini',
  'Vintage travel',
  'Botanical circle',
  'Chalkboard list',
  'Save the date',
  'Kids drawing',
  'Seasonal swap',
  'Strong neodymium',
  'Clear acrylic',
  'Wood slice',
  'Polaroid style',
  'Calendar strip',
  'Shopping list',
  'Herb markers',
  'Constellation set',
  'Rainbow pack',
]

const hamperTypes = [
  'Reader’s rest',
  'Desk refresh',
  'Tea & journal',
  'Birthday burst',
  'Thank-you trio',
  'New home',
  'Spa unwind',
  'Chocolate & card',
  'Stationery starter',
  'Holiday cheer',
  'Mindful morning',
  'Artist basics',
  'Kid craft box',
  'Couple date night',
  'Garden seeds',
  'Coffee corner',
  'Pet lover',
  'Graduation kit',
  'Wedding favour',
  'Corporate welcome',
]

const pick = (arr, i) => arr[i % arr.length]

function slugify(s) {
  return s
    .toLowerCase()
    .replace(/['’]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 80)
}

const rows = []
let idNum = 8001
let sort = 120

for (const cat of categories) {
  for (let i = 0; i < perCategory; i++) {
    const id = String(idNum++)
    let baseName
    let desc
    const sku = `TM-${id}`
    if (cat === 'bookmarks') {
      baseName = `${pick(bookmarkTypes, i + sort)} bookmark`
      desc = `Hand-finished ${pick(['cotton', 'linen', 'silk', 'kraft'], i)} accents; fits most paperback sizes. Sold as a single or pair where noted. ${pick(['Acid-free paper safe', 'Archival-friendly inks', 'Packaged in recycled sleeve'], i)}. Catalogue ${sku}.`
    } else if (cat === 'cards') {
      baseName = `${pick(cardTypes, i)} card`
      desc = `A6 folded card with ${pick(['recycled', 'FSC-certified', 'textured cotton'], i)} envelope. Blank inside unless described on pack. ${pick(['Coordinating sticker sheet', 'Gold foil detail', 'Rounded corners'], i)}. ${sku}.`
    } else if (cat === 'calendars') {
      baseName = `${pick(calendarTypes, i)} calendar`
      desc = `${pick(['12-month', '16-month academic', 'undated 52-week'], i)} layout with ${pick(['moon icons', 'stickers', 'habit dots', 'plain margins'], i)}. ${pick(['Wire-o binding', 'Perforated notes', 'US holidays marked'], i)} where applicable. ${sku}.`
    } else if (cat === 'magnets') {
      baseName = `${pick(magnetTypes, i)} magnet`
      desc = `Strong hold for ${pick(['fridge', 'locker', 'whiteboard'], i)}; ${pick(['55mm', '60mm', '75mm'], i)} face. Wipe clean. ${pick(['Set of 1', 'Set of 4 mini', 'Gift sleeve included'], i)}. ${sku}.`
    } else {
      baseName = `${pick(hamperTypes, i)} hamper`
      desc = `Curated gift box: ${pick(['cards', 'clips', 'tea', 'candles', 'notebook', 'pens'], i)} and ${pick(['ribbon wrap', 'kraft tray', 'reusable tin'], i)}. ${pick(['Ready to ship', 'Personal note card slot', 'Seasonal ribbon'], i)}. ${sku}.`
    }

    const variant = i + 1
    const name = `${baseName} - series ${variant}`
    const slug = slugify(`${cat}-${baseName}-${variant}-${id}`)
    const price = Number((6.5 + (i * 1.7) + (cat.length * 0.3)).toFixed(2))
    const compare = i % 3 === 0 ? Number((price + 8 + (i % 5)).toFixed(2)) : null
    const seed = esc(slug).replace(/[^a-z0-9-]/gi, 'x').slice(0, 60)
    const image = `https://picsum.photos/seed/${seed}/700/700`
    const hb = cat === 'bookmarks' && i < 3 ? 1 : cat === 'cards' && i < 2 ? 1 : 0
    const hs = (i + sort) % 7 === 0 || (i + sort) % 11 === 0 ? 1 : 0

    const cmp = compare === null ? 'NULL' : compare.toFixed(2)
    rows.push(
      `('${id}','${esc(slug)}','${esc(name)}','${esc(desc)}',${price.toFixed(2)},${cmp},'${cat}','${esc(image)}',${hb},${hs},${sort},1)`
    )
    sort += 1
  }
}

// INSERT IGNORE so this file can be piped into an existing DB that already has the 12 legacy rows.
const sql =
  'INSERT IGNORE INTO products (id, slug, name, description, price, compare_at, category, image_url, home_bestseller, home_secondary, sort_order, is_active) VALUES\n' +
  rows.join(',\n') +
  ';\n'

const out = path.join(__dirname, 'seed_extra_products.sql')
fs.writeFileSync(out, sql, 'utf8')
console.log('Wrote', out, rows.length, 'rows — then run: node backend/sql/merge_seed.mjs')
