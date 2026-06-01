<?php
// ─────────────────────────────────────────────
//  admin/admin.php
//  Admin: list orders, update status, restore stock
// ─────────────────────────────────────────────

/**
 * Return all orders with their items and customer info.
 */
function fetch_orders(): void
{
    $pdo  = db();
    $stmt = $pdo->query('
        SELECT
            o.id,
            o.subtotal,
            o.shipping,
            o.total,
            o.status,
            o.created_at,
            u.name  AS customer_name,
            u.email AS customer_email
        FROM orders o
        JOIN users u ON u.id = o.user_id
        ORDER BY o.created_at DESC, o.id DESC
    ');

    $orders = [];
    foreach ($stmt->fetchAll() as $row) {
        $row['id']       = (int)   $row['id'];
        $row['subtotal'] = (float) $row['subtotal'];
        $row['shipping'] = (float) $row['shipping'];
        $row['total']    = (float) $row['total'];
        $row['items']    = [];
        $orders[$row['id']] = $row;
    }

    // ── Attach line items ─────────────────────────────────────────────────────
    if ($orders) {
        $ids          = array_keys($orders);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $itemStmt     = $pdo->prepare("
            SELECT order_id, product_name, category, size_name, color_name, color_hex, photo, unit_price, qty
            FROM order_items
            WHERE order_id IN ($placeholders)
            ORDER BY id ASC
        ");
        $itemStmt->execute($ids);

        foreach ($itemStmt->fetchAll() as $item) {
            $orders[(int)$item['order_id']]['items'][] = [
                'product_name' => $item['product_name'],
                'category'     => $item['category'],
                'size_name'    => $item['size_name'],
                'color_name'   => $item['color_name'],
                'color_hex'    => $item['color_hex'],
                'photo'        => $item['photo'],
                'unit_price'   => (float) $item['unit_price'],
                'qty'          => (int)   $item['qty'],
            ];
        }
    }

    json_response(['success' => true, 'orders' => array_values($orders)]);
}

/**
 * Update an order's status to 'delivered' or 'cancelled'.
 * Expects: { order_id, status }
 */
function update_order_status(array $data): void
{
    $orderId = (int)($data['order_id'] ?? 0);
    $status  = trim((string)($data['status'] ?? ''));

    $allowed = ['delivered', 'cancelled'];
    if ($orderId <= 0 || !in_array($status, $allowed, true)) {
        json_response(['success' => false, 'message' => 'Order can only be marked as delivered or cancelled.'], 422);
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT id, status FROM orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new RuntimeException('Order not found.');
        }

        $currentStatus = (string)$order['status'];
        if ($currentStatus !== 'placed') {
            throw new RuntimeException('This order has already been ' . $currentStatus . '.');
        }

        // Restore stock only when cancelling
        if ($status === 'cancelled') {
            restore_order_stock($pdo, $orderId);
        }

        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')
            ->execute([$status, $orderId]);

        $pdo->commit();
        json_response(['success' => true, 'message' => 'Order marked as ' . $status . '.']);

    } catch (Throwable $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => $e->getMessage()], 422);
    }
}

/**
 * Add back stock quantities for all items in a cancelled order.
 */
function restore_order_stock(PDO $pdo, int $orderId): void
{
    $itemsStmt = $pdo->prepare('SELECT product_id, color_name, size_name, qty FROM order_items WHERE order_id = ?');
    $itemsStmt->execute([$orderId]);

    $restoreStmt = $pdo->prepare('
        UPDATE product_color_sizes pcs
        JOIN product_colors pc ON pc.id = pcs.color_id
        SET pcs.qty_stock = pcs.qty_stock + ?
        WHERE pc.product_id = ?
          AND pc.name       = ?
          AND pcs.size_name = ?
    ');

    foreach ($itemsStmt->fetchAll() as $item) {
        if (empty($item['product_id'])) continue;

        $restoreStmt->execute([
            (int) $item['qty'],
            $item['product_id'],
            $item['color_name'],
            $item['size_name'],
        ]);
    }
}