DROP DATABASE IF EXISTS stylee_store;

CREATE DATABASE stylee_store
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE stylee_store;

-- ================= USERS =================
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================= PRODUCTS =================
CREATE TABLE products (
  id VARCHAR(120) PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  category VARCHAR(120) NOT NULL,
  gender ENUM('men', 'women', 'both') NOT NULL DEFAULT 'both',
  price DECIMAL(10,2) NOT NULL,
  description TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================= COLORS =================
CREATE TABLE product_colors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id VARCHAR(120) NOT NULL,
  name VARCHAR(80) NOT NULL,
  hex VARCHAR(20) NOT NULL DEFAULT '#111111',
  photo TEXT NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY unique_product_color (product_id, name),
  CONSTRAINT fk_product_colors_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================= SIZES (FIXED) =================
CREATE TABLE product_color_sizes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  color_id INT UNSIGNED NOT NULL,
  size_name VARCHAR(20) NOT NULL,
  size_order INT UNSIGNED NOT NULL DEFAULT 0,
  qty_stock INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY unique_color_size (color_id, size_name),
  CONSTRAINT fk_product_color_sizes_color
    FOREIGN KEY (color_id) REFERENCES product_colors(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================= ORDERS =================
CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  shipping DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL,
  status ENUM('placed', 'delivered', 'cancelled') NOT NULL DEFAULT 'placed',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================= ORDER ITEMS =================
CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id VARCHAR(120) NULL,
  product_name VARCHAR(180) NOT NULL,
  category VARCHAR(120) NOT NULL,
  size_name VARCHAR(20) NOT NULL,
  color_name VARCHAR(80) NOT NULL,
  color_hex VARCHAR(20) NOT NULL,
  photo TEXT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  qty INT UNSIGNED NOT NULL,
  CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product
    FOREIGN KEY (product_id) REFERENCES products(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================= ADMIN =================
INSERT INTO users (name, email, password_hash, role)
VALUES (
  'STYLEE Admin',
  'admin@stylee.com',
  '$2y$12$rgNmSPZwqECsElbXN30VAey8qE6Qu97mNwja1nQi.mnhlzemz9NEa',
  'admin'
);

-- ================= PRODUCTS =================
INSERT INTO products (id, name, category, gender, price, description) VALUES
('puffer-jacket', 'Premium Puffer Jacket', 'Jackets', 'men', 179.00, 'Oversized premium puffer with quilted paneling. Water-resistant shell and streetwear fit.'),
('bomber-camo-set', 'Bomber & Camo Set', 'Sets', 'women', 189.00, 'Relaxed streetwear set with oversized bomber jacket and camo pants.'),
('urban-black-ensemble', 'Urban Black Ensemble', 'Outerwear', 'both', 249.00, 'Layered urban outfit made for daily streetwear styling.');

-- ================= COLORS =================
INSERT INTO product_colors (id, product_id, name, hex, photo, sort_order) VALUES
(1, 'puffer-jacket', 'Black', '#000000', 'https://images.unsplash.com/photo-1611312449408-fcece27cdbb7?w=900', 0),
(2, 'puffer-jacket', 'Navy', '#263949', 'https://images.unsplash.com/photo-1523398002811-999ca8dec234?w=900', 1),
(3, 'puffer-jacket', 'Brown', '#71430f', 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900', 2),
(4, 'bomber-camo-set', 'Olive', '#556b2f', 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900', 0),
(5, 'bomber-camo-set', 'Black', '#111111', 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?w=900', 1),
(6, 'urban-black-ensemble', 'Black', '#000000', 'https://images.unsplash.com/photo-1523398002811-999ca8dec234?w=900', 0),
(7, 'urban-black-ensemble', 'Grey', '#777777', 'https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?w=900', 1);

-- ================= SIZES =================
INSERT INTO product_color_sizes (color_id, size_name, size_order, qty_stock) VALUES
(1, 'S', 1, 8), (1, 'M', 2, 10), (1, 'L', 3, 7), (1, 'XL', 4, 5), (1, 'XXL', 5, 3),
(2, 'S', 1, 3), (2, 'M', 2, 6), (2, 'L', 3, 4), (2, 'XL', 4, 2), (2, 'XXL', 5, 1),
(3, 'S', 1, 5), (3, 'M', 2, 4), (3, 'L', 3, 3), (3, 'XL', 4, 0), (3, 'XXL', 5, 0),
(4, 'S', 1, 6), (4, 'M', 2, 8), (4, 'L', 3, 5), (4, 'XL', 4, 4), (4, 'XXL', 5, 2),
(5, 'S', 1, 2), (5, 'M', 2, 5), (5, 'L', 3, 2), (5, 'XL', 4, 1), (5, 'XXL', 5, 0),
(6, 'S', 1, 4), (6, 'M', 2, 6), (6, 'L', 3, 5), (6, 'XL', 4, 3), (6, 'XXL', 5, 1),
(7, 'S', 1, 1), (7, 'M', 2, 2), (7, 'L', 3, 3), (7, 'XL', 4, 0), (7, 'XXL', 5, 0);