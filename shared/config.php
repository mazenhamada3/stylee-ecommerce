<?php

// =============================
// CONFIG (SAFE CLEAN VERSION)
// =============================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================
// DATABASE
// =============================
function db(): PDO
{
    static $pdo = null;

    if ($pdo) return $pdo;

    $host    = "127.0.0.1";
    $db      = "stylee_store";
    $user    = "root";
    $pass    = "";
    $charset = "utf8mb4";

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}