-- Subcategories per shop category + admin-configured free gift product slots.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS subcategories (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_slug VARCHAR(64) NOT NULL,
  slug VARCHAR(64) NOT NULL,
  name VARCHAR(128) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_subcategories_cat_slug (category_slug, slug),
  KEY idx_subcategories_category (category_slug, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'subcategory'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE products ADD COLUMN subcategory VARCHAR(64) NULL AFTER category, ADD KEY idx_products_subcategory (category, subcategory)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO subcategories (category_slug, slug, name, sort_order) VALUES
  ('bookmarks', 'magnetic', 'Magnetic bookmarks', 10),
  ('bookmarks', 'classic', 'Classic bookmarks', 20),
  ('cards', 'birthday', 'Birthday cards', 10),
  ('cards', 'thank-you', 'Thank you cards', 20),
  ('cards', 'love', 'Love cards', 30),
  ('cards', 'sorry', 'Sorry cards', 40),
  ('calendars', 'desk', 'Desk calendars', 10),
  ('calendars', 'wall', 'Wall calendars', 20),
  ('magnets', 'photo', 'Photo magnets', 10),
  ('magnets', 'quote', 'Quote magnets', 20),
  ('magnets', 'couple', 'Couple magnets', 30),
  ('hampers', 'mini', 'Mini hampers', 10),
  ('hampers', 'premium', 'Premium hampers', 20),
  ('hampers', 'gift-sets', 'Gift sets', 30);

UPDATE products SET subcategory = 'magnetic'
  WHERE category = 'bookmarks' AND (slug LIKE '%magnetic%' OR name LIKE '%magnetic%');
UPDATE products SET subcategory = 'classic'
  WHERE category = 'bookmarks' AND subcategory IS NULL;

UPDATE products SET subcategory = 'birthday'
  WHERE category = 'cards' AND slug LIKE '%birthday%';
UPDATE products SET subcategory = 'thank-you'
  WHERE category = 'cards' AND slug LIKE '%thank%';
UPDATE products SET subcategory = 'love'
  WHERE category = 'cards' AND slug LIKE '%love%';
UPDATE products SET subcategory = 'sorry'
  WHERE category = 'cards' AND slug LIKE '%sorry%';

UPDATE products SET subcategory = 'mini'
  WHERE category = 'hampers' AND slug LIKE '%mini%';
