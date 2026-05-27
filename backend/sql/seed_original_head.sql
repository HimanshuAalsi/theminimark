-- Run after schema.sql on the same database (no USE statement).

INSERT INTO products (id, slug, name, description, price, compare_at, category, image_url, home_bestseller, home_secondary, sort_order, is_active) VALUES
('7345', 'magnetic-bookmarks', 'Magnetic Bookmarks', NULL, 39.00, 49.00, 'bookmarks', 'https://theminimark.com/wp-content/uploads/2026/03/sticker-book-diy-4-Magnetic-bookmarks-700x700.jpeg', 1, 0, 10, 1),
('7356', 'classic-bookmarks', 'Classic Bookmarks', NULL, 39.00, 49.00, 'bookmarks', 'https://theminimark.com/wp-content/uploads/2026/03/Whimsical-Bookmark-Collection_-700x700.jpeg', 1, 0, 20, 1),
('7357', 'birthday-cards', 'Birthday Cards', NULL, 39.00, 49.00, 'cards', 'https://theminimark.com/wp-content/uploads/2026/03/Panda-Pun-Birthday-Card-Have-a-panda-stic-Birthday-Panda-Birthday-Card-Birthday-Card-for-Friend-Birthday-Card-for-Nephew-Niece-700x700.jpeg', 1, 0, 30, 1),
('7358', 'thank-you-cards', 'Thank You Cards', NULL, 39.00, 49.00, 'cards', 'https://theminimark.com/wp-content/uploads/2026/03/Set-of-3-thank-you-cards-two-toned-theme-thank-you-card-pack-handmade-thank-you-cards-card-assortment-thank-you-card-variety-pack-700x700.jpeg', 1, 0, 40, 1),
('7359', 'love-cards', 'Love Cards', NULL, 39.00, 49.00, 'cards', 'https://theminimark.com/wp-content/uploads/2026/03/Youre-My-Favourite-Person-Card-_-Valentines-Card-_-Be-My-Valentine-_-Love-You-Card-_-Valentine-Card-_-Watercolour-Hearts-Card-_-With-Love-700x700.jpeg', 1, 0, 50, 1),
('7360', 'sorry-cards', 'Sorry Cards', NULL, 39.00, 49.00, 'cards', 'https://theminimark.com/wp-content/uploads/2026/03/Im-Sorry-Card-Printable_-Rewind-Cassette-Tape-Design-digital-Download-Etsy-700x700.jpeg', 1, 0, 60, 1),
('7361', 'hampers', 'Hampers', NULL, 39.00, 49.00, 'hampers', 'https://theminimark.com/wp-content/uploads/2026/03/Visit-Now_-Customize-a-Birthday-Hamper-for-Your-Bestie-700x700.jpeg', 1, 1, 70, 1),
('7362', 'mini-hamper', 'Mini Hamper', NULL, 39.00, 49.00, 'hampers', 'https://theminimark.com/wp-content/uploads/2026/03/gift-hamper-for-her-700x700.jpeg', 1, 1, 80, 1),
('7363', 'fridge-magnets', 'Fridge Magnets', NULL, 39.00, 49.00, 'magnets', 'https://theminimark.com/wp-content/uploads/2026/03/download-43-700x700.jpeg', 1, 1, 90, 1),
('7365', 'couple-fridge-magnets', 'Couple Fridge Magnets', NULL, 39.00, 49.00, 'magnets', 'https://theminimark.com/wp-content/uploads/2026/03/Personalisierte-Save-the-Date-Kuhlschrankmagnet-Kalender-Hochzeit-Einladung-Ankundigung-Geschenk-700x700.jpeg', 1, 1, 100, 1),
('7367', 'calendars', 'Calendars', NULL, 39.00, 49.00, 'calendars', 'https://theminimark.com/wp-content/uploads/2026/03/download-44-700x700.jpeg', 0, 1, 110, 1),
('7370', 'new-calendars', 'New Calendars', NULL, 39.00, 49.00, 'calendars', 'https://theminimark.com/wp-content/uploads/2026/03/download-45-700x700.jpeg', 0, 1, 5, 1);
