<?php
require_once __DIR__ . '/../core.php';

$route = $_GET['route'] ?? '';

switch ($route) {

    /* ================= LOGIN ================= */
    case 'login':
        require_method('POST');

        $data = input_json();

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            json_response(["success" => false, "message" => "Missing fields"], 422);
        }

        $stmt = db()->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            json_response(["success" => false, "message" => "Invalid credentials"], 401);
        }

        $_SESSION['user_id'] = $user['id'];

        json_response([
            "success" => true,
            "user" => [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role']
            ]
        ]);

    /* ================= PRODUCT DETAILS ================= */
    case 'product':
    case 'product-details':
        require_method('GET');

        $id = $_GET['id'] ?? '';

        if (!$id) {
            json_response(["success" => false, "message" => "Product ID required"], 422);
        }

        // Get product
        $stmt = db()->prepare("
            SELECT id, name, category, gender, price, description
            FROM products
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            json_response(["success" => false, "message" => "Product not found"], 404);
        }

        // Get colors
        $stmt = db()->prepare("
            SELECT id, name, hex, photo
            FROM product_colors
            WHERE product_id = ?
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$id]);
        $colors = $stmt->fetchAll();

        foreach ($colors as &$color) {

            $stmt2 = db()->prepare("
                SELECT size_name, qty_stock
                FROM product_color_sizes
                WHERE color_id = ?
                ORDER BY FIELD(size_name, 'S','M','L','XL','XXL')
            ");
            $stmt2->execute([$color['id']]);

            $color['sizes'] = [];

            foreach ($stmt2->fetchAll() as $size) {
                $color['sizes'][] = [
                    "name" => $size['size_name'],
                    "qty" => (int)$size['qty_stock']
                ];
            }
        }

        $product['price'] = (float)$product['price'];
        $product['colors'] = $colors;

        json_response([
            "success" => true,
            "product" => $product
        ]);

    /* ================= DEFAULT ================= */
    default:
        json_response([
            "success" => false,
            "message" => "Salah route not found"
        ], 404);
}