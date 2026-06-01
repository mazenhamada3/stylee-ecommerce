<?php

require_once __DIR__ . '/config.php';

// =============================
// RESPONSE HELPERS
// =============================

if (!function_exists('json_response')) {
    function json_response($data, $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

function input_json(): array
{
    $raw  = file_get_contents("php://input");
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        json_response(["success" => false, "message" => "Method not allowed"], 405);
    }
}

// =============================
// AUTH HELPERS
// =============================

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) return null;

    $stmt = db()->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);

    return $stmt->fetch() ?: null;
}

// =============================
// STOCK HELPERS
// =============================

/**
 * Restore stock for a cancelled order.
 */
function restore_order_stock(PDO $pdo, int $orderId): void
{
    $itemsStmt = $pdo->prepare(
        'SELECT product_id, color_name, size_name, qty FROM order_items WHERE order_id = ?'
    );
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