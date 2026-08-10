-- Replace legacy WordPress wp-content product image URLs with self-hosted /products paths.
-- Run once in phpMyAdmin on Hostinger after uploading frontend/public/products/ to public_html/products/

UPDATE products SET image_url = '/products/magnetic-bookmarks.jpeg'
WHERE image_url = 'https://theminimark.com/wp-content/uploads/2026/03/sticker-book-diy-4-Magnetic-bookmarks-700x700.jpeg';

UPDATE products SET image_url = '/products/classic-bookmarks.jpeg'
WHERE image_url = 'https://theminimark.com/wp-content/uploads/2026/03/Whimsical-Bookmark-Collection_-700x700.jpeg';

UPDATE products SET image_url = '/products/birthday-cards.jpeg'
WHERE image_url = 'https://theminimark.com/wp-content/uploads/2026/03/Panda-Pun-Birthday-Card-Have-a-panda-stic-Birthday-Panda-Birthday-Card-Birthday-Card-for-Friend-Birthday-Card-for-Nephew-Niece-700x700.jpeg';

UPDATE products SET image_url = '/products/thank-you-cards.jpeg'
WHERE image_url = 'https://theminimark.com/wp-content/uploads/2026/03/Set-of-3-thank-you-cards-two-toned-theme-thank-you-card-pack-handmade-thank-you-cards-card-assortment-thank-you-card-variety-pack-700x700.jpeg';

UPDATE products SET image_url = '/products/love-cards.jpeg'
WHERE image_url = 'https://theminimark.com/wp-content/uploads/2026/03/Youre-My-Favourite-Person-Card-_-Valentines-Card-_-Be-My-Valentine-_-Love-You-Card-_-Valentine-Card-_-Watercolour-Hearts-Card-_-With-Love-700x700.jpeg';

UPDATE products SET image_url = '/products/sorry-cards.jpeg'
WHERE image_url = 'https://theminimark.com/wp-content/uploads/2026/03/Im-Sorry-Card-Printable_-Rewind-Cassette-Tape-Design-digital-Download-Etsy-700x700.jpeg';

UPDATE products SET image_url = '/products/hampers.jpeg'
WHERE image_url = 'https://theminimark.com/wp-content/uploads/2026/03/Visit-Now_-Customize-a-Birthday-Hamper-for-Your-Bestie-700x700.jpeg';

UPDATE products SET image_url = '/products/mini-hamper.jpeg'
WHERE image_url = 'https://theminimark.com/wp-content/uploads/2026/03/gift-hamper-for-her-700x700.jpeg';

UPDATE products SET image_url = '/products/fridge-magnets.jpeg'
WHERE image_url = 'https://theminimark.com/wp-content/uploads/2026/03/download-43-700x700.jpeg';

UPDATE products SET image_url = '/products/couple-fridge-magnets.jpeg'
WHERE image_url = 'https://theminimark.com/wp-content/uploads/2026/03/Personalisierte-Save-the-Date-Kuhlschrankmagnet-Kalender-Hochzeit-Einladung-Ankundigung-Geschenk-700x700.jpeg';

UPDATE products SET image_url = '/products/calendars.jpeg'
WHERE image_url = 'https://theminimark.com/wp-content/uploads/2026/03/download-44-700x700.jpeg';

UPDATE products SET image_url = '/products/new-calendars.jpeg'
WHERE image_url = 'https://theminimark.com/wp-content/uploads/2026/03/download-45-700x700.jpeg';

-- Any remaining wp-content or picsum placeholders → rotate through pool (optional fallback)
UPDATE products SET image_url = '/products/magnetic-bookmarks.jpeg'
WHERE image_url LIKE '%picsum.photos%' OR image_url LIKE '%wp-content/uploads%';
