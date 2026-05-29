<?php
require_once __DIR__ . '/db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['import_file'])) {
    header('Location: ' . ROOT_URL . '/index.php');
    exit;
}

$file = $_FILES['import_file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    flash_set('error', 'Upload error code: ' . $file['error']);
    header('Location: ' . ROOT_URL . '/index.php');
    exit;
}

$encryptedData = file_get_contents($file['tmp_name']);
$decryptedJson = decrypt_data($encryptedData);

if ($decryptedJson === false) {
    flash_set('error', 'Failed to decrypt. Invalid key or file format.');
    header('Location: ' . ROOT_URL . '/index.php');
    exit;
}

$data = json_decode($decryptedJson, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['positions']) || !isset($data['candidates'])) {
    flash_set('error', 'Invalid JSON format or missing data.');
    header('Location: ' . ROOT_URL . '/index.php');
    exit;
}

$pdo = getDB();
try {
    $pdo->beginTransaction();

    // Clear existing data before importing new config
    $pdo->exec('DELETE FROM results');
    $pdo->exec('DELETE FROM candidates');
    $pdo->exec('DELETE FROM positions');
    $pdo->exec('DELETE FROM ballots');

    // Import Positions (now with ranking)
    $stmtPos = $pdo->prepare('INSERT INTO positions (id, name, max_votes, ranking) VALUES (?, ?, ?, ?)');
    foreach ($data['positions'] as $pos) {
        // Fallback to 1 if ranking is not present in older exports
        $ranking = isset($pos['ranking']) ? (int)$pos['ranking'] : 1;
        $stmtPos->execute([(int)$pos['id'], $pos['name'], (int)$pos['max_votes'], $ranking]);
    }

    // Import Candidates
    $stmtCand = $pdo->prepare('INSERT INTO candidates (id, name, position_id, party_name, location) VALUES (?, ?, ?, ?, ?)');
    foreach ($data['candidates'] as $cand) {
        $stmtCand->execute([
            (int)$cand['id'],
            $cand['name'],
            (int)$cand['position_id'],
            $cand['party'],
            $cand['location']
        ]);
    }

    // Import Ballots (if present in the config)
    if (!empty($data['ballots'])) {
        $stmtBallot = $pdo->prepare('INSERT INTO ballots (ballot_id, city, status) VALUES (?, ?, ?)');
        foreach ($data['ballots'] as $ballot) {
            $stmtBallot->execute([
                $ballot['ballot_id'],
                $ballot['city'],
                'pending'  // All imported ballots start as 'pending' on the ACM
            ]);
        }
    }

    $pdo->commit();
    $ballotCount = !empty($data['ballots']) ? count($data['ballots']) : 0;
    $msg = 'Configuration imported successfully! Device is assigned to: ' . e($data['location']);
    if ($ballotCount > 0) {
        $msg .= " ($ballotCount ballots loaded)";
    }
    flash_set('success', $msg);
    flash_set('print_init', '1');
} catch (PDOException $e) {
    $pdo->rollBack();
    flash_set('error', 'Database error: ' . $e->getMessage());
}

header('Location: ' . ROOT_URL . '/index.php');
exit;
