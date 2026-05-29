<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['import_file'])) {
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

$file = $_FILES['import_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    flash_set('error', 'Upload error code: ' . $file['error']);
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

$encryptedData = file_get_contents($file['tmp_name']);
$decryptedJson = decrypt_data($encryptedData);

if ($decryptedJson === false) {
    flash_set('error', 'Failed to decrypt. Invalid key or file format.');
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

$data = json_decode($decryptedJson, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['results'])) {
    flash_set('error', 'Invalid JSON format or missing results data.');
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

if (!isset($data['export_uniqid'])) {
    flash_set('error', 'Invalid export file. Missing unique identifier.');
    header('Location: ' . ROOT_URL . '/results/index.php');
    exit;
}

$pdo = getDB();
try {
    $pdo->beginTransaction();

    // Check for duplicate import
    $stmtCheck = $pdo->prepare('SELECT id FROM import_log WHERE export_uniqid = ?');
    $stmtCheck->execute([$data['export_uniqid']]);
    if ($stmtCheck->fetch()) {
        $pdo->rollBack();
        flash_set('error', 'This result file has already been imported.');
        header('Location: ' . ROOT_URL . '/results/index.php');
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO results (candidate_id, total_votes) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE total_votes = total_votes + VALUES(total_votes)'
    );

    foreach ($data['results'] as $result) {
        $stmt->execute([(int)$result['candidate_id'], (int)$result['total_votes']]);
    }

    // Log the import
    $stmtLog = $pdo->prepare('INSERT INTO import_log (export_uniqid) VALUES (?)');
    $stmtLog->execute([$data['export_uniqid']]);

    $pdo->commit();
    flash_set('success', 'Results imported successfully! ' . count($data['results']) . ' records processed.');
} catch (PDOException $e) {
    $pdo->rollBack();
    flash_set('error', 'Database error: ' . $e->getMessage());
}

header('Location: ' . ROOT_URL . '/results/index.php');
exit;
