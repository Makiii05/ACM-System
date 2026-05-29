<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_URL . '/candidates/index.php');
    exit;
}

$name        = trim($_POST['name'] ?? '');
$position_id = (int)($_POST['position_id'] ?? 0);
$party_id    = (int)($_POST['party_id'] ?? 0);
$location    = trim($_POST['location'] ?? '');

if ($name === '' || $position_id === 0 || $party_id === 0 || $location === '') {
    flash_set('error', 'All fields are required.');
    header('Location: ' . ROOT_URL . '/candidates/index.php');
    exit;
}

try {
    getDB()->prepare('INSERT INTO candidates (name, position_id, party_id, location) VALUES (?,?,?,?)')
           ->execute([$name, $position_id, $party_id, $location]);
    flash_set('success', 'Candidate added.');
} catch (PDOException $e) {
    flash_set('error', 'Error adding candidate: ' . $e->getMessage());
}

header('Location: ' . ROOT_URL . '/candidates/index.php');
exit;
