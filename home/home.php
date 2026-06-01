<?php
require_once __DIR__ . '/../core.php';

$route = $_GET['route'] ?? '';

switch ($route) {

    /* ================= HOME PRODUCTS ================= */
    case 'home-products':
    case 'products':
        require_method('GET');

        $id = $_GET['id'] ?? null;

        if ($id) {
            $stmt = db()->prepare("
                SELECT id, name, category, gender, price, description
                FROM products
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch();

            json_response([
                "success" => true,
                "product" => $product
            ]);
        }

        $stmt = db()->query("
            SELECT id, name, category, gender, price, description
            FROM products
            ORDER BY created_at DESC
        ");

        json_response([
            "success" => true,
            "products" => $stmt->fetchAll()
        ]);

    /* ================= CURRENT USER ================= */
    case 'me':
        require_method('GET');

        json_response([
            "success" => true,
            "user" => current_user()
        ]);

    /* ================= LOGOUT ================= */
    case 'logout':
        require_method('POST');

        session_destroy();

        json_response([
            "success" => true,
            "message" => "Logged out successfully"
        ]);

    /* ================= DEFAULT ================= */
    default:
        json_response([
            "success" => false,
            "message" => "Home route not found"
        ], 404);
}