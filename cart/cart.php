<?php
// ─────────────────────────────────────────────
//  cart/cart.php
//  Handles cart checkout and order placement
// ─────────────────────────────────────────────

/**
 * Place an order for the logged-in user.
 * Expects: { items: [{ productId, size, colorName, qty }] }
 */
function checkout(array $data): void
{   
    $shippingType = $data['shipping'] ?? 'standard';
    $user  = require_login();
    $items = $data['items'] ?? [];

    if (!is_array($items) || count($items) === 0) {
        json_response(['success' => false, 'message' => 'Cart is empty.'], 422);
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $validated = [];
        $subtotal  = 0.0;

        // ── Validate every cart item & lock rows ─────────────────────────────
        foreach ($items as $item) {
            $productId = trim((string)($item['productId']  ?? ''));
            $size      = trim((string)($item['size']       ?? ''));
            $colorName = trim((string)($item['colorName']  ?? ''));
            $qty       = (int)($item['qty'] ?? 0);

            if ($productId === '' || $size === '' || $colorName === '' || $qty <= 0) {
                throw new RuntimeException('Invalid cart item.');
            }

            $stmt = $pdo->prepare('
                SELECT
                    p.id   AS product_id,
                    p.name,
                    p.category,
                    p.price,
                    pc.id   AS color_id,
                    pc.name AS color_name,
                    pc.hex  AS color_hex,
                    pc.photo,
                    pcs.qty_stock
                FROM products p
                JOIN product_colors      pc  ON pc.product_id = p.id  AND pc.name      = ?
                JOIN product_color_sizes pcs ON pcs.color_id  = pc.id AND pcs.size_name = ?
                WHERE p.id = ?
                LIMIT 1
                FOR UPDATE
            ');
            $stmt->execute([$colorName, $size, $productId]);
            $row = $stmt->fetch();

            if (!$row) {
                throw new RuntimeException('Product option not found.');
            }

            if ((int)$row['qty_stock'] < $qty) {
                throw new RuntimeException(
                    'Only ' . (int)$row['qty_stock'] . ' left for ' .
                    $row['name'] . ' / ' . $row['color_name'] . ' / size ' . $size . '.'
                );
            }

            $subtotal += (float)$row['price'] * $qty;

            $validated[] = [
                'product' => $row,
                'size'    => $size,
                'qty'     => $qty,
            ];
        }

        // ── Create order ──────────────────────────────────────────────────────
        $shipping = match ($shippingType) {
            'express'  => 25.00,
            'free'     => 0.00,
            default    => 12.00,
        };
        $total    = $subtotal + $shipping;

        $pdo->prepare('INSERT INTO orders (user_id, subtotal, shipping, total, status) VALUES (?, ?, ?, ?, "placed")')
            ->execute([$user['id'], $subtotal, $shipping, $total]);
        $orderId = (int) $pdo->lastInsertId();

        // ── Insert items & deduct stock ───────────────────────────────────────
        $insertItem  = $pdo->prepare('
            INSERT INTO order_items
                (order_id, product_id, product_name, category, size_name, color_name, color_hex, photo, unit_price, qty)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $updateStock = $pdo->prepare('
            UPDATE product_color_sizes SET qty_stock = qty_stock - ? WHERE color_id = ? AND size_name = ?
        ');

        foreach ($validated as $entry) {
            $p = $entry['product'];
            $insertItem->execute([
                $orderId, $p['product_id'], $p['name'], $p['category'],
                $entry['size'], $p['color_name'], $p['color_hex'],
                $p['photo'], $p['price'], $entry['qty'],
            ]);
            $updateStock->execute([$entry['qty'], $p['color_id'], $entry['size']]);
        }

        $pdo->commit();

        json_response([
            'success'  => true,
            'message'  => 'Order placed successfully.',
            'order_id' => $orderId,
            'total'    => $total,
        ], 201);

    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => $e->getMessage()], 422);
    }
}