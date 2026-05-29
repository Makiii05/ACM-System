<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_URL . '/positions/index.php');
    exit;
}

$name      = trim($_POST['name'] ?? '');
$max_votes = max(1, (int)($_POST['max_votes'] ?? 1));
$ranking   = (int)($_POST['ranking'] ?? 1);

if ($name === '') {
    flash_set('error', 'Name is required.');
    header('Location: ' . ROOT_URL . '/positions/index.php');
    exit;
}

try {
    getDB()->prepare('INSERT INTO positions (name, max_votes, ranking) VALUES (?,?,?)')
           ->execute([$name, $max_votes, $ranking]);
    flash_set('success', 'Position added.');
} catch (PDOException $e) {
    flash_set('error', 'Error adding position: ' . $e->getMessage());
}

header('Location: ' . ROOT_URL . '/positions/index.php');
exit;
