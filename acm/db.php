<?php

define('BASE_PATH', dirname(__DIR__));
define('DB_HOST', 'localhost');
define('DB_NAME', 'acm_local');
define('DB_USER', 'root');
define('DB_PASS', '');

// Must MATCH the Server sub-system EXACTLY
define('ENCRYPT_KEY',    'ACMSys2026!!SecretKey32CharLong!');
define('ENCRYPT_CIPHER', 'AES-256-CBC');

// Base URL for this sub-system — adjust if your Laragon path differs
define('ROOT_URL', '/vanilla_project/ACM_System/acm');

// ─── Database ────────────────────────────────────────────────────────────────

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}

// ─── Encryption ──────────────────────────────────────────────────────────────

function encrypt_data(string $json): string
{
    $iv        = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPT_CIPHER));
    $encrypted = openssl_encrypt($json, ENCRYPT_CIPHER, ENCRYPT_KEY, 0, $iv);
    return base64_encode($iv . '::' . $encrypted);
}

function decrypt_data(string $payload): string
{
    $decoded = base64_decode($payload);
    [$iv, $encrypted] = explode('::', $decoded, 2);
    return openssl_decrypt($encrypted, ENCRYPT_CIPHER, ENCRYPT_KEY, 0, $iv);
}

// ─── Auth ────────────────────────────────────────────────────────────────────

function require_auth(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . ROOT_URL . '/auth/login.php');
        exit;
    }
}

// ─── Flash messages ──────────────────────────────────────────────────────────

function flash_set(string $key, string $msg): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'][$key] = $msg;
}

function flash_get(string $key): ?string
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// ─── Utility ─────────────────────────────────────────────────────────────────

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
