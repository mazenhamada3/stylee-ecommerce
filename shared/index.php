<?php
// ─────────────────────────────────────────────
//  shared/index.php  ← main entry point / router
// ─────────────────────────────────────────────

require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/../login/login.php';
require __DIR__ . '/../products/products.php';
require __DIR__ . '/../cart/cart.php';
require __DIR__ . '/../admin/admin.php';

// ── Resolve route ─────────────────────────────────────────────────────────────
$requestUri = $_SERVER['REQUEST_URI'];
$route      = trim(parse_url($requestUri, PHP_URL_PATH), '/');
$route      = str_replace('api/', '', $route);

if ($route === '') {
    $route = 'products';
}

// ── Dispatch ──────────────────────────────────────────────────────────────────
try {
    $route = ltrim($route, '/');

    switch ($route) {

        // ── Public routes ─────────────────────────────────────────────────────

        case 'products':
            require_method('GET');
            $id = $_GET['id'] ?? null;
            json_response(['success' => true, 'products' => fetch_products($id)]);

        case 'register':
            require_method('POST');
            register_user(input_json());
            break;

        case 'login':
            require_method('POST');
            login_user(input_json());
            break;

        case 'logout':
            require_method('POST');
            session_destroy();
            json_response(['success' => true, 'message' => 'Logged out.']);

        case 'me':
            require_method('GET');
            json_response(['success' => true, 'user' => current_user()]);

        case 'checkout':
            require_method('POST');
            checkout(input_json());
            break;

        // ── Admin routes ──────────────────────────────────────────────────────

        case 'admin/products':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require_admin();
                create_product(input_json());
                break;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                require_admin();
                delete_product($_GET['id'] ?? '');
                break;
            }
            json_response(['success' => false, 'message' => 'Method not allowed.'], 405);

        case 'admin/orders':
            require_admin();
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                fetch_orders();
                break;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
                update_order_status(input_json());
                break;
            }
            json_response(['success' => false, 'message' => 'Method not allowed.'], 405);

        case 'admin/reset':
            require_method('POST');
            require_admin();
            reset_demo_products();
            break;

        // ── Fallback ──────────────────────────────────────────────────────────

        default:
            json_response(['success' => false, 'message' => 'Unknown API route.'], 404);
    }

} catch (Throwable $e) {
    error_log($e->getMessage());
    json_response(['success' => false, 'message' => 'Server error. Check PHP/MySQL configuration.'], 500);
}