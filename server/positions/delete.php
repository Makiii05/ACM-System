<?php
require_once __DIR__ . '/../db.php';
require_auth();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        getDB()->prepare('DELETE FROM positions WHERE id = ?')->execute([$id]);
        flash_set('success', 'Position deleted.');
    } catch (PDOException) {
        flash_set('error', 'Cannot delete — position is in use by candidates.');
    }
}

header('Location: ' . ROOT_URL . '/positions/index.php');
exit;
