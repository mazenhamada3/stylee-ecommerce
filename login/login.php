<?php
// ─────────────────────────────────────────────
//  login/login.php
//  Handles user registration and login
// ─────────────────────────────────────────────

/**
 * Register a new customer account.
 * Expects: { name, email, password }
 */
function register_user(array $data): void
{
    $name     = trim((string)($data['name']     ?? ''));
    $email    = strtolower(trim((string)($data['email']    ?? '')));
    $password = (string)($data['password'] ?? '');

    // ── Validation ───────────────────────────────────────────────────────────
    if ($name === '' || $email === '' || $password === '') {
        json_response(['success' => false, 'message' => 'Name, email and password are required.'], 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Please enter a valid email.'], 422);
    }

    if (strlen($password) < 6) {
        json_response(['success' => false, 'message' => 'Password must be at least 6 characters.'], 422);
    }

    // ── Duplicate check ──────────────────────────────────────────────────────
    $pdo  = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        json_response(['success' => false, 'message' => 'This email is already registered.'], 409);
    }

    // ── Insert & start session ───────────────────────────────────────────────
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "customer")');
    $stmt->execute([$name, $email, $hash]);

    $_SESSION['user_id'] = (int) $pdo->lastInsertId();

    json_response([
        'success' => true,
        'message' => 'Account created successfully.',
        'user'    => current_user(),
    ], 201);
}

/**
 * Log in an existing user.
 * Expects: { email, password }
 */
function login_user(array $data): void
{
    $email    = strtolower(trim((string)($data['email']    ?? '')));
    $password = (string)($data['password'] ?? '');

    if ($email === '' || $password === '') {
        json_response(['success' => false, 'message' => 'Email and password are required.'], 422);
    }

    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        json_response(['success' => false, 'message' => 'Invalid email or password.'], 401);
    }

    $_SESSION['user_id'] = (int) $user['id'];

    json_response([
        'success' => true,
        'message' => 'Logged in successfully.',
        'user'    => current_user(),
    ]);
}