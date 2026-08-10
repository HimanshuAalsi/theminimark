-- Advanced admin: categories, keywords, multi-images, inventory fields.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(64) NOT NULL,
  name VARCHAR(128) NOT NULL,
  description TEXT NULL,
  keywords TEXT NULL COMMENT 'SEO / search terms, comma-separated',
  image_path VARCHAR(512) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_categories_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id VARCHAR(32) NOT NULL,
  image_path VARCHAR(2048) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_product_images_product (product_id),
  KEY idx_product_images_sort (product_id, sort_order),
  CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'keywords'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE products
    ADD COLUMN keywords TEXT NULL AFTER description,
    ADD COLUMN sku VARCHAR(64) NULL AFTER sort_order,
    ADD COLUMN stock_quantity INT UNSIGNED NULL DEFAULT NULL AFTER sku,
    ADD COLUMN seo_title VARCHAR(255) NULL AFTER stock_quantity,
    ADD COLUMN seo_description VARCHAR(512) NULL AFTER seo_title',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO categories (slug, name, sort_order, keywords) VALUES
  ('bookmarks', 'Bookmarks', 10, 'bookmark, magnetic bookmark, reading gift'),
  ('cards', 'Greeting Cards', 20, 'card, greeting card, birthday card'),
  ('calendars', 'Calendars', 30, 'calendar, desk calendar, planner'),
  ('magnets', 'Magnets', 40, 'magnet, fridge magnet, photo magnet'),
  ('hampers', 'Gift Hampers', 50, 'hamper, gift set, gift box');

-- Backfill product_images from existing image_url
INSERT IGNORE INTO product_images (product_id, image_path, sort_order, is_primary)
SELECT id, image_url, 0, 1 FROM products WHERE image_url IS NOT NULL AND image_url != '';
