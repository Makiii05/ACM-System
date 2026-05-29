<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_URL . '/candidates/index.php');
    exit;
}

$id          = (int)($_POST['id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$position_id = (int)($_POST['position_id'] ?? 0);
$party_id    = (int)($_POST['party_id'] ?? 0);
$location    = trim($_POST['location'] ?? '');

if ($name === '' || $position_id === 0 || $party_id === 0 || $location === '' || $id <= 0) {
    flash_set('error', 'All fields and ID are required.');
    header('Location: ' . ROOT_URL . '/candidates/index.php');
    exit;
}

try {
    getDB()->prepare('UPDATE candidates SET name=?, position_id=?, party_id=?, location=? WHERE id=?')
           ->execute([$name, $position_id, $party_id, $location, $id]);
    flash_set('success', 'Candidate updated.');
} catch (PDOException $e) {
    flash_set('error', 'Error updating candidate: ' . $e->getMessage());
}

header('Location: ' . ROOT_URL . '/candidates/index.php');
exit;
