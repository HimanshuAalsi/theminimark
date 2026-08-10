-- Custom product personalisation linked to order lines (run once on existing DBs).

CREATE TABLE IF NOT EXISTS order_line_personalisation (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_line_id BIGINT UNSIGNED NOT NULL,
  product_type VARCHAR(32) NOT NULL,
  photo_path VARCHAR(512) NOT NULL,
  zoom DECIMAL(4, 2) NOT NULL DEFAULT 1.00,
  pos_x DECIMAL(5, 2) NOT NULL DEFAULT 50.00,
  pos_y DECIMAL(5, 2) NOT NULL DEFAULT 50.00,
  options_json JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ol_personalisation_line (order_line_id),
  KEY idx_ol_personalisation_type (product_type),
  KEY idx_ol_personalisation_created (created_at),
  CONSTRAINT fk_ol_personalisation_line FOREIGN KEY (order_line_id) REFERENCES order_lines (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
