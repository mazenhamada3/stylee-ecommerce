<?php
// ─────────────────────────────────────────────
//  shared/functions.php
//  Shared utility functions used across all files
// ─────────────────────────────────────────────

/**
 * Send a JSON response and exit.
 */
function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Abort with 405 if the request method doesn't match.
 */
function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
    }
}

/**
 * Decode the raw JSON request body into an array.
 */
function input_json(): array
{
    return (array) json_decode(file_get_contents('php://input'), true);
}

/**
 * Return the session user's full row from DB, or null if not logged in.
 */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Return the currently authenticated user row, or abort 401.
 */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        json_response(['success' => false, 'message' => 'Login required.'], 401);
    }
    return $user;
}

/**
 * Abort with 403 if the current user is not an admin.
 */
function require_admin(): void
{
    $user = require_login();
    if (($user['role'] ?? '') !== 'admin') {
        json_response(['success' => false, 'message' => 'Admin access required.'], 403);
    }
}