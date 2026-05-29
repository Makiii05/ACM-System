<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_URL . '/ballot/print.php');
    exit;
}

$pdo = getDB();
$location = trim($_POST['location'] ?? '');

if ($location === '') {
    flash_set('error', 'Please select a location to export.');
    header('Location: ' . ROOT_URL . '/ballot/print.php');
    exit;
}

// Fetch positions that have candidates in this location
$stmt = $pdo->prepare(
    'SELECT DISTINCT p.id, p.name, p.max_votes, p.ranking
     FROM positions p
     JOIN candidates c ON c.position_id = p.id
     WHERE c.location = ?
     ORDER BY p.ranking ASC'
);
$stmt->execute([$location]);
$positions = $stmt->fetchAll();

// Fetch candidates
$stmt = $pdo->prepare(
    'SELECT c.id, c.name, c.position_id, c.location,
            pt.name AS party, pt.code AS party_code
     FROM candidates c
     JOIN parties pt ON pt.id = c.party_id
     JOIN positions p ON p.id = c.position_id
     WHERE c.location = ?
     ORDER BY p.ranking ASC, c.name'
);
$stmt->execute([$location]);
$candidates = $stmt->fetchAll();

// Fetch unsent ballots for this location
$stmtBallots = $pdo->prepare('SELECT ballot_id, city, status FROM ballots WHERE city = ? AND status = ?');
$stmtBallots->execute([$location, 'unsent']);
$ballots = $stmtBallots->fetchAll();

$payload = json_encode([
    'location'    => $location,
    'exported_at' => date('c'),
    'positions'   => $positions,
    'candidates'  => $candidates,
    'ballots'     => $ballots,
]);

// Mark exported ballots as 'sent'
$stmtUpdate = $pdo->prepare('UPDATE ballots SET status = ? WHERE city = ? AND status = ?');
$stmtUpdate->execute(['sent', $location, 'unsent']);

$encrypted = encrypt_data($payload);
$filename  = 'acm_candidates_' . preg_replace('/[^a-z0-9]/i', '_', $location) . '_' . date('Ymd_His') . '.json';

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($encrypted));
echo $encrypted;
exit;
