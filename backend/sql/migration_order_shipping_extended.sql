-- Extended shipping fields for orders (run once on existing databases).
ALTER TABLE orders ADD COLUMN shipping_landmark VARCHAR(255) NULL AFTER shipping_address;
ALTER TABLE orders ADD COLUMN shipping_state VARCHAR(128) NULL AFTER shipping_city;
ALTER TABLE orders ADD COLUMN shipping_pincode VARCHAR(10) NULL AFTER shipping_state;
