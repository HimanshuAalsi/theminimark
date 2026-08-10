-- Run after schema.sql on the same database (no USE statement).

INSERT INTO products (id, slug, name, description, price, compare_at, category, image_url, home_bestseller, home_secondary, sort_order, is_active) VALUES
('7345', 'magnetic-bookmarks', 'Magnetic Bookmarks', NULL, 39.00, 49.00, 'bookmarks', '/products/magnetic-bookmarks.jpeg', 1, 0, 10, 1),
('7356', 'classic-bookmarks', 'Classic Bookmarks', NULL, 39.00, 49.00, 'bookmarks', '/products/classic-bookmarks.jpeg', 1, 0, 20, 1),
('7357', 'birthday-cards', 'Birthday Cards', NULL, 39.00, 49.00, 'cards', '/products/birthday-cards.jpeg', 1, 0, 30, 1),
('7358', 'thank-you-cards', 'Thank You Cards', NULL, 39.00, 49.00, 'cards', '/products/thank-you-cards.jpeg', 1, 0, 40, 1),
('7359', 'love-cards', 'Love Cards', NULL, 39.00, 49.00, 'cards', '/products/love-cards.jpeg', 1, 0, 50, 1),
('7360', 'sorry-cards', 'Sorry Cards', NULL, 39.00, 49.00, 'cards', '/products/sorry-cards.jpeg', 1, 0, 60, 1),
('7361', 'hampers', 'Hampers', NULL, 39.00, 49.00, 'hampers', '/products/hampers.jpeg', 1, 1, 70, 1),
('7362', 'mini-hamper', 'Mini Hamper', NULL, 39.00, 49.00, 'hampers', '/products/mini-hamper.jpeg', 1, 1, 80, 1),
('7363', 'fridge-magnets', 'Fridge Magnets', NULL, 39.00, 49.00, 'magnets', '/products/fridge-magnets.jpeg', 1, 1, 90, 1),
('7365', 'couple-fridge-magnets', 'Couple Fridge Magnets', NULL, 39.00, 49.00, 'magnets', '/products/couple-fridge-magnets.jpeg', 1, 1, 100, 1),
('7367', 'calendars', 'Calendars', NULL, 39.00, 49.00, 'calendars', '/products/calendars.jpeg', 0, 1, 110, 1),
('7370', 'new-calendars', 'New Calendars', NULL, 39.00, 49.00, 'calendars', '/products/new-calendars.jpeg', 0, 1, 5, 1);
