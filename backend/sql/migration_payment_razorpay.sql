-- Run once in phpMyAdmin on existing databases (after schema.sql).
-- New installs: columns are already in schema.sql.

ALTER TABLE orders
  ADD COLUMN razorpay_order_id VARCHAR(64) NULL AFTER status,
  ADD COLUMN payment_id VARCHAR(64) NULL AFTER razorpay_order_id,
  ADD COLUMN paid_at DATETIME NULL AFTER payment_id;

ALTER TABLE orders
  ADD UNIQUE KEY uk_orders_razorpay_order (razorpay_order_id);
