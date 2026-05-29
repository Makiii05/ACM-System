<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_URL . '/ballot/print.php');
    exit;
}

$city  = trim($_POST['city'] ?? '');
$count = (int)($_POST['count'] ?? 0);

if ($city === '' || $count < 1 || $count > 500) {
    flash_set('error', 'Invalid city or count (1–500).');
    header('Location: ' . ROOT_URL . '/ballot/print.php');
    exit;
}

// Build slug from city name: lowercase, remove commas, spaces → nothing, keep alphanumerics + hyphens
// e.g. "Lipa, City" → "lipa" (remove "city" and other common suffixes, keep main name)
$slug = strtolower(trim($city));
// Remove common suffixes like "city", "town", "municipality" etc.
$slug = preg_replace('/\b(city|town|municipality|province)\b/i', '', $slug);
// Remove all non-alphanumeric characters
$slug = preg_replace('/[^a-z0-9]/', '', $slug);
// Trim any remaining whitespace artifacts
$slug = trim($slug);

if ($slug === '') {
    $slug = preg_replace('/[^a-z0-9]/', '', strtolower($city));
}

$pdo = getDB();

// Find highest existing sequence number for this city
$stmt = $pdo->prepare("SELECT ballot_id FROM ballots WHERE city = ? ORDER BY ballot_id DESC LIMIT 1");
$stmt->execute([$city]);
$lastBallot = $stmt->fetchColumn();

$startSeq = 1;
if ($lastBallot) {
    // Extract the sequence number from the last ballot_id
    $parts = explode('-', $lastBallot);
    $lastNum = (int)end($parts);
    $startSeq = $lastNum + 1;
}

try {
    $pdo->beginTransaction();

    $stmtInsert = $pdo->prepare('INSERT INTO ballots (ballot_id, city, status) VALUES (?, ?, ?)');

    for ($i = 0; $i < $count; $i++) {
        $seq = $startSeq + $i;
        $ballotId = $slug . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
        $stmtInsert->execute([$ballotId, $city, 'unsent']);
    }

    $pdo->commit();
    flash_set('success', "Generated $count ballot(s) for " . e($city) . ".");
} catch (PDOException $e) {
    $pdo->rollBack();
    flash_set('error', 'Error generating ballots: ' . $e->getMessage());
}

header('Location: ' . ROOT_URL . '/ballot/print.php?location=' . urlencode($city));
exit;
