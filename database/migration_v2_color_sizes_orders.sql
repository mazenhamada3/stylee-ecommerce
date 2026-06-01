-- Migration for projects already imported before the color-specific stock update.
-- This preserves users/orders and rebuilds color-level sizes from the old shared product_sizes table.
USE stylee_store;

CREATE TABLE IF NOT EXISTS product_color_sizes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  color_id INT UNSIGNED NOT NULL,
  size_name VARCHAR(20) NOT NULL,
  qty_stock INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY unique_color_size (color_id, size_name),
  CONSTRAINT fk_product_color_sizes_color
    FOREIGN KEY (color_id) REFERENCES product_colors(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO product_color_sizes (color_id, size_name, qty_stock)
SELECT pc.id, ps.size_name, ps.qty_stock
FROM product_colors pc
JOIN product_sizes ps ON ps.product_id = pc.product_id
ON DUPLICATE KEY UPDATE qty_stock = VALUES(qty_stock);

ALTER TABLE orders
  MODIFY status ENUM('placed', 'confirmed', 'processing', 'completed', 'cancelled', 'delivered') NOT NULL DEFAULT 'placed';

UPDATE orders
SET status = 'delivered'
WHERE status IN ('confirmed', 'processing', 'completed');

ALTER TABLE orders
  MODIFY status ENUM('placed', 'delivered', 'cancelled') NOT NULL DEFAULT 'placed';
