<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_URL . '/parties/index.php');
    exit;
}

$code = trim($_POST['code'] ?? '');
$name = trim($_POST['name'] ?? '');

if ($name === '' || $code === '') {
    flash_set('error', 'Party code and name are required.');
    header('Location: ' . ROOT_URL . '/parties/index.php');
    exit;
}

try {
    getDB()->prepare('INSERT INTO parties (code, name) VALUES (?, ?)')
           ->execute([$code, $name]);
    flash_set('success', 'Party added.');
} catch (PDOException $e) {
    flash_set('error', 'Error adding party: ' . $e->getMessage());
}

header('Location: ' . ROOT_URL . '/parties/index.php');
exit;
