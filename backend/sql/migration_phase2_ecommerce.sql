-- Phase 2: coupons, order discount columns, staff roles, email log (optional)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS coupons (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL,
  description VARCHAR(255) NULL,
  discount_type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
  discount_value DECIMAL(10, 2) NOT NULL,
  min_order_inr DECIMAL(10, 2) NOT NULL DEFAULT 0,
  max_uses INT UNSIGNED NULL DEFAULT NULL,
  used_count INT UNSIGNED NOT NULL DEFAULT 0,
  first_order_only TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_coupons_code (code),
  KEY idx_coupons_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'coupon_code'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE orders
    ADD COLUMN coupon_code VARCHAR(32) NULL AFTER items_subtotal,
    ADD COLUMN coupon_discount DECIMAL(10, 2) NULL DEFAULT 0 AFTER coupon_code,
    ADD COLUMN refund_id VARCHAR(64) NULL AFTER payment_id',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO coupons (code, description, discount_type, discount_value, min_order_inr, first_order_only, is_active)
VALUES (
  'MINIFIRST10',
  '10% off your first order',
  'percent',
  10.00,
  0,
  1,
  1
);
