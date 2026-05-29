<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['upload_id'])) {
    header('Location: ' . ROOT_URL . '/results/3g_uploads.php');
    exit;
}

$upload_id = (int) $_POST['upload_id'];
$pdo = getDB();

try {
    $pdo->beginTransaction();

    // Lock the row for update
    $stmt = $pdo->prepare("SELECT * FROM api_uploads WHERE id = ? FOR UPDATE");
    $stmt->execute([$upload_id]);
    $upload = $stmt->fetch();

    if (!$upload) {
        throw new Exception('Upload record not found.');
    }

    if ($upload['status'] !== 'pending') {
        throw new Exception('This upload cannot be approved. Current status: ' . $upload['status']);
    }

    // Check if already imported
    $stmtCheck = $pdo->prepare('SELECT id FROM import_log WHERE export_uniqid = ?');
    $stmtCheck->execute([$upload['export_uniqid']]);
    if ($stmtCheck->fetch()) {
        // Mark as rejected or approved? It's already in the system.
        $stmtUpdate = $pdo->prepare("UPDATE api_uploads SET status = 'rejected' WHERE id = ?");
        $stmtUpdate->execute([$upload_id]);
        throw new Exception('These results have already been imported into the system.');
    }

    // Read the file
    if (!file_exists($upload['file_path'])) {
        throw new Exception('Data file is missing from the server.');
    }

    $jsonContent = file_get_contents($upload['file_path']);
    $data = json_decode($jsonContent, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($data['results'])) {
        throw new Exception('Data file is corrupted or empty.');
    }

    // Tally the votes
    $stmtTally = $pdo->prepare(
        'INSERT INTO results (candidate_id, total_votes) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE total_votes = total_votes + VALUES(total_votes)'
    );

    foreach ($data['results'] as $result) {
        $stmtTally->execute([(int)$result['candidate_id'], (int)$result['total_votes']]);
    }

    // Log the import
    $stmtLog = $pdo->prepare('INSERT INTO import_log (export_uniqid) VALUES (?)');
    $stmtLog->execute([$upload['export_uniqid']]);

    // Update upload status
    $stmtApprove = $pdo->prepare("UPDATE api_uploads SET status = 'approved' WHERE id = ?");
    $stmtApprove->execute([$upload_id]);

    $pdo->commit();
    flash_set('success', 'Upload approved! ' . count($data['results']) . ' records have been tallied successfully.');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_set('error', $e->getMessage());
}

header('Location: ' . ROOT_URL . '/results/3g_uploads.php');
exit;
