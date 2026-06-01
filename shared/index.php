<?php
// ─────────────────────────────────────────────
//  shared/index.php  ← main entry point / router
// ─────────────────────────────────────────────

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../login/login.php';
require_once __DIR__ . '/../products/products.php';
require_once __DIR__ . '/../cart/cart.php';
require_once __DIR__ . '/../admin/admin.php';

// ── Resolve route ─────────────────────────────────────────────────────────────
$route = trim($_GET['route'] ?? 'products');
if ($route === '') {
    $route = 'products';
}

// ── Dispatch ──────────────────────────────────────────────────────────────────
try {

    switch ($route) {

        // ── Public routes ─────────────────────────────────────────────────────

        case 'products':
            require_method('GET');
            $id = $_GET['id'] ?? null;
            json_response(['success' => true, 'products' => fetch_products($id)]);
            break;

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
            break;

        case 'me':
            require_method('GET');
            json_response(['success' => true, 'user' => current_user()]);
            break;

        case 'checkout':
            require_method('POST');
            checkout(input_json());
            break;

        // ── Admin routes ──────────────────────────────────────────────────────

        case 'admin/products':
            require_admin();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                create_product(input_json());
                break;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                delete_product($_GET['id'] ?? '');
                break;
            }
            json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
            break;

       case 'admin/orders':
            require_admin();
            require_method('GET');
            fetch_orders();
            break;

        case 'admin/order-status':
            require_admin();
            require_method('POST');
            update_order_status(input_json());
            break;

        case 'admin/reset':
            require_method('POST');
            require_admin();
            reset_demo_products();
            break;

        // ── Fallback ──────────────────────────────────────────────────────────

        default:
            json_response([
                'success' => false,
                'message' => 'Unknown API route: ' . $route
            ], 404);
    }

} catch (Throwable $e) {
    error_log($e->getMessage());
    json_response(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}