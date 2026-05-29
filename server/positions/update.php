<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_URL . '/positions/index.php');
    exit;
}

$id        = (int)($_POST['id'] ?? 0);
$name      = trim($_POST['name'] ?? '');
$max_votes = max(1, (int)($_POST['max_votes'] ?? 1));
$ranking   = (int)($_POST['ranking'] ?? 1);

if ($name === '' || $id <= 0) {
    flash_set('error', 'Name and ID are required.');
    header('Location: ' . ROOT_URL . '/positions/index.php');
    exit;
}

try {
    getDB()->prepare('UPDATE positions SET name=?, max_votes=?, ranking=? WHERE id=?')
           ->execute([$name, $max_votes, $ranking, $id]);
    flash_set('success', 'Position updated.');
} catch (PDOException $e) {
    flash_set('error', 'Error updating position: ' . $e->getMessage());
}

header('Location: ' . ROOT_URL . '/positions/index.php');
exit;
