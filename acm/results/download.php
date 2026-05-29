<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

$pdo = getDB();

// Check if there are any results
$count = $pdo->query('SELECT COUNT(*) FROM results')->fetchColumn();

if ($count == 0) {
    flash_set('error', 'No votes have been cast yet. Nothing to export.');
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

$results = $pdo->query(
    'SELECT r.candidate_id, r.total_votes, c.name AS candidate_name
     FROM results r
     JOIN candidates c ON c.id = r.candidate_id'
)->fetchAll();

// Get export log uniqid
$exportLog = $pdo->query('SELECT * FROM export_log ORDER BY id DESC LIMIT 1')->fetch();
if (!$exportLog) {
    flash_set('error', 'Export log missing. Cannot export results.');
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

if ($exportLog['is_exported']) {
    flash_set('error', 'Results have already been exported. Device is locked.');
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

$location = $pdo->query('SELECT location FROM candidates LIMIT 1')->fetchColumn() ?: 'Unknown';

$payload = json_encode([
    'exported_at'   => date('c'),
    'location'      => $location,
    'export_uniqid' => $exportLog['uniqid'],
    'results'       => $results
]);

// Mark as exported
$stmt = $pdo->prepare('UPDATE export_log SET is_exported = 1 WHERE id = ?');
$stmt->execute([$exportLog['id']]);

$encrypted = encrypt_data($payload);
$filename  = 'acm_results_' . preg_replace('/[^a-z0-9]/i', '_', $location) . '_' . date('Ymd_His') . '.json';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($encrypted));
echo $encrypted;
exit;
