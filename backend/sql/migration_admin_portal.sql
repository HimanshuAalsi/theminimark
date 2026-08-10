-- Admin portal: roles, order details, product uploads linkage.
-- Run on existing DB after backup. Safe to re-run (checks information_schema).

SET NAMES utf8mb4;

-- Admin role on users
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT \'customer\' AFTER full_name, ADD KEY idx_users_role (role)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Order fields for admin / shipping
SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'items_subtotal'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE orders
    ADD COLUMN items_subtotal DECIMAL(12, 2) NULL AFTER subtotal,
    ADD COLUMN shipping_phone VARCHAR(32) NULL AFTER customer_name,
    ADD COLUMN shipping_address TEXT NULL AFTER shipping_phone,
    ADD COLUMN shipping_city VARCHAR(128) NULL AFTER shipping_address,
    ADD COLUMN admin_notes TEXT NULL AFTER notes,
    ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill items_subtotal from subtotal where missing (approximate for old rows)
UPDATE orders SET items_subtotal = subtotal WHERE items_subtotal IS NULL;
