<?php
require_once __DIR__ . '/../db.php';
require_auth();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        getDB()->prepare('DELETE FROM candidates WHERE id = ?')->execute([$id]);
        flash_set('success', 'Candidate deleted.');
    } catch (PDOException) {
        flash_set('error', 'Cannot delete — candidate has linked results.');
    }
}

header('Location: ' . ROOT_URL . '/candidates/index.php');
exit;
