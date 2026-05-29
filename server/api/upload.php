<?php
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

$payload = $_POST['payload'] ?? '';

if (empty($payload)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No payload provided.']);
    exit;
}

$decryptedJson = decrypt_data($payload);

if ($decryptedJson === false) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Failed to decrypt payload.']);
    exit;
}

$data = json_decode($decryptedJson, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['results']) || !isset($data['export_uniqid'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON format or missing essential data.']);
    exit;
}

$pdo = getDB();

// Check if this export_uniqid was already successfully imported
$stmtCheck = $pdo->prepare('SELECT id FROM import_log WHERE export_uniqid = ?');
$stmtCheck->execute([$data['export_uniqid']]);
if ($stmtCheck->fetch()) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Results have already been imported.']);
    exit;
}

// Check if there's already a pending or approved 3g upload for this export_uniqid
$stmtApiCheck = $pdo->prepare("SELECT id, status FROM api_uploads WHERE export_uniqid = ? AND status != 'rejected'");
$stmtApiCheck->execute([$data['export_uniqid']]);
$existingUpload = $stmtApiCheck->fetch();

// If we are overriding an incomplete upload, that's fine, we will update it or create a new one.
// Let's just create a new record or update the existing one if it's incomplete.

$location = $data['location'] ?? 'Unknown';
$expected_votes = (int) ($data['expected_votes'] ?? 0);
$export_uniqid = $data['export_uniqid'];

$actual_votes = 0;
foreach ($data['results'] as $res) {
    $actual_votes += (int) $res['total_votes'];
}

$status = ($actual_votes === $expected_votes) ? 'pending' : 'incomplete';

// Save JSON to disk
$dir = __DIR__ . '/../uploads/3g_data';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
$filename = 'acm_3g_' . preg_replace('/[^a-z0-9]/i', '_', $location) . '_' . date('Ymd_His') . '.json';
$filepath = $dir . '/' . $filename;
file_put_contents($filepath, $decryptedJson);

// Save to DB
if ($existingUpload) {
    if ($existingUpload['status'] === 'approved') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'This payload has already been approved and tallied.']);
        exit;
    }
    // Update existing upload
    $stmtUpdate = $pdo->prepare('UPDATE api_uploads SET expected_votes = ?, actual_votes = ?, status = ?, file_path = ?, created_at = NOW() WHERE id = ?');
    $stmtUpdate->execute([$expected_votes, $actual_votes, $status, $filepath, $existingUpload['id']]);
} else {
    // Insert new
    $stmtInsert = $pdo->prepare('INSERT INTO api_uploads (location, export_uniqid, expected_votes, actual_votes, status, file_path) VALUES (?, ?, ?, ?, ?, ?)');
    $stmtInsert->execute([$location, $export_uniqid, $expected_votes, $actual_votes, $status, $filepath]);
}

if ($status === 'incomplete') {
    echo json_encode(['status' => 'success', 'message' => 'Upload received but marked as INCOMPLETE due to vote count mismatch.']);
} else {
    echo json_encode(['status' => 'success', 'message' => 'Upload received completely and is pending approval.']);
}
exit;
