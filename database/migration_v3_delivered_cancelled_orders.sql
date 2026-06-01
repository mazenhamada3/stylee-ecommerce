-- Migration for projects already imported before the delivered/cancelled order update.
-- This keeps existing data, converts old confirmed/processing/completed orders to delivered,
-- and restricts future admin actions to placed, delivered, or cancelled.
USE stylee_store;

ALTER TABLE orders
  MODIFY status ENUM('placed', 'confirmed', 'processing', 'completed', 'cancelled', 'delivered') NOT NULL DEFAULT 'placed';

UPDATE orders
SET status = 'delivered'
WHERE status IN ('confirmed', 'processing', 'completed');

ALTER TABLE orders
  MODIFY status ENUM('placed', 'delivered', 'cancelled') NOT NULL DEFAULT 'placed';
