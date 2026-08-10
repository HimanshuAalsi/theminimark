-- Product feature bullets for PDP (JSON array of strings).
SET NAMES utf8mb4;

SET @col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'features'
);
SET @sql := IF(@col = 0,
  'ALTER TABLE products ADD COLUMN features JSON NULL AFTER description',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
