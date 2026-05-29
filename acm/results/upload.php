<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

$pdo = getDB();

// Check if there are any results
$total_votes = (int) $pdo->query('SELECT COALESCE(SUM(total_votes), 0) FROM results')->fetchColumn();

if ($total_votes === 0) {
    flash_set('error', 'No votes have been cast yet. Nothing to upload.');
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

// Get export log uniqid
$exportLog = $pdo->query('SELECT * FROM export_log ORDER BY id DESC LIMIT 1')->fetch();
if (!$exportLog) {
    flash_set('error', 'Export log missing. Cannot upload results.');
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

if ($exportLog['is_exported']) {
    flash_set('error', 'Results have already been exported. Device is locked.');
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

$results = $pdo->query(
    'SELECT r.candidate_id, r.total_votes, c.name AS candidate_name
     FROM results r
     JOIN candidates c ON c.id = r.candidate_id'
)->fetchAll();

$location = $pdo->query('SELECT location FROM candidates LIMIT 1')->fetchColumn() ?: 'Unknown';

$payloadArray = [
    'exported_at'    => date('c'),
    'location'       => $location,
    'export_uniqid'  => $exportLog['uniqid'],
    'expected_votes' => $total_votes,
    'results'        => $results
];

$payload = json_encode($payloadArray);
$encrypted = encrypt_data($payload);

// Send via 3G API
$apiUrl = 'http://localhost/vanilla_project/ACM_System/server/api/upload.php';

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
// Send as form data
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['payload' => $encrypted]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    flash_set('error', '3G Upload failed (Connection error): ' . $curlError);
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

if ($httpCode === 200) {
    $resData = json_decode($response, true);
    if (isset($resData['status']) && $resData['status'] === 'success') {
        // Mark as exported/locked
        $stmt = $pdo->prepare('UPDATE export_log SET is_exported = 1 WHERE id = ?');
        $stmt->execute([$exportLog['id']]);
        
        flash_set('success', '3G Upload successful. ' . ($resData['message'] ?? 'Device is now locked.'));
    } else {
        flash_set('error', '3G Upload error from server: ' . ($resData['message'] ?? 'Unknown API error.'));
    }
} else {
    flash_set('error', '3G Upload failed with HTTP Code: ' . $httpCode);
}

header('Location: ' . ROOT_URL . '/results/index.php');
exit;
