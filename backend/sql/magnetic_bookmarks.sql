-- Extra magnetic bookmark products for shop + home carousel.
-- Run on existing DBs after schema.sql / seed.sql.

INSERT IGNORE INTO products (id, slug, name, description, price, compare_at, category, image_url, home_bestseller, home_secondary, sort_order, is_active) VALUES
('8101', 'magnetic-bookmark-whimsical-set', 'Whimsical Magnetic Bookmark Set', 'Fold-over magnetic clips with playful illustrated designs.', 449.00, 549.00, 'bookmarks', 'https://theminimark.com/wp-content/uploads/2026/03/Whimsical-Bookmark-Collection_-700x700.jpeg', 0, 0, 11, 1),
('8102', 'magnetic-bookmark-floral', 'Floral Magnetic Bookmark', 'Botanical magnetic bookmark — stays put on every page.', 399.00, 499.00, 'bookmarks', 'https://picsum.photos/seed/magnetic-bookmark-floral-8102/700/700', 0, 0, 12, 1),
('8103', 'magnetic-bookmark-animal-friends', 'Animal Friends Magnetic Bookmark', 'Cute animal magnetic clip for readers of all ages.', 399.00, 499.00, 'bookmarks', 'https://picsum.photos/seed/magnetic-bookmark-animal-8103/700/700', 0, 0, 13, 1),
('8104', 'magnetic-bookmark-literary-quotes', 'Literary Quotes Magnetic Bookmark', 'Quote-print magnetic bookmark for book lovers.', 379.00, 479.00, 'bookmarks', 'https://picsum.photos/seed/magnetic-bookmark-quotes-8104/700/700', 0, 0, 14, 1),
('8105', 'magnetic-bookmark-minimal-line', 'Minimal Line Magnetic Bookmark', 'Clean line-art magnetic bookmark — slim and sturdy.', 349.00, 449.00, 'bookmarks', 'https://picsum.photos/seed/magnetic-bookmark-minimal-8105/700/700', 0, 0, 15, 1),
('8106', 'magnetic-bookmark-vintage-maps', 'Vintage Maps Magnetic Bookmark', 'Map-inspired magnetic clip for travel readers.', 429.00, 529.00, 'bookmarks', 'https://picsum.photos/seed/magnetic-bookmark-vintage-8106/700/700', 0, 0, 16, 1),
('8107', 'magnetic-bookmark-watercolor', 'Watercolor Magnetic Bookmark', 'Soft watercolor magnetic bookmark with glossy finish.', 399.00, 499.00, 'bookmarks', 'https://picsum.photos/seed/magnetic-bookmark-watercolor-8107/700/700', 0, 0, 17, 1);
