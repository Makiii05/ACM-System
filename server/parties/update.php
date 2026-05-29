<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_URL . '/parties/index.php');
    exit;
}

$id   = (int)($_POST['id'] ?? 0);
$code = trim($_POST['code'] ?? '');
$name = trim($_POST['name'] ?? '');

if ($name === '' || $code === '' || $id <= 0) {
    flash_set('error', 'Party code, name, and ID are required.');
    header('Location: ' . ROOT_URL . '/parties/index.php');
    exit;
}

try {
    getDB()->prepare('UPDATE parties SET code=?, name=? WHERE id=?')
           ->execute([$code, $name, $id]);
    flash_set('success', 'Party updated.');
} catch (PDOException $e) {
    flash_set('error', 'Error updating party: ' . $e->getMessage());
}

header('Location: ' . ROOT_URL . '/parties/index.php');
exit;
