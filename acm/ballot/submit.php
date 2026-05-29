<?php
require_once __DIR__ . '/../db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_URL . '/ballot/form.php');
    exit;
}

$votes     = $_POST['votes'] ?? [];
$ballot_id = trim($_POST['ballot_id'] ?? '');

if (empty($votes)) {
    flash_set('error', 'No votes were selected. Ballot was not cast.');
    header('Location: ' . ROOT_URL . '/ballot/form.php');
    exit;
}

if ($ballot_id === '') {
    flash_set('error', 'Missing ballot ID.');
    header('Location: ' . ROOT_URL . '/ballot/form.php');
    exit;
}

$candidateIds = [];

// Flatten votes arrays/strings into a single list of IDs
foreach ($votes as $positionVotes) {
    if (is_array($positionVotes)) {
        foreach ($positionVotes as $id) {
            $candidateIds[] = (int)$id;
        }
    } else {
        $candidateIds[] = (int)$positionVotes;
    }
}

$candidateIds = array_filter(array_unique($candidateIds));

if (empty($candidateIds)) {
    flash_set('error', 'Invalid vote data.');
    header('Location: ' . ROOT_URL . '/ballot/form.php');
    exit;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    // Check if device is locked
    $exportLog = $pdo->query('SELECT * FROM export_log ORDER BY id DESC LIMIT 1')->fetch();
    if ($exportLog && $exportLog['is_exported']) {
        $pdo->rollBack();
        flash_set('error', 'Device has already exported results and is currently locked.');
        header('Location: ' . ROOT_URL . '/ballot/form.php');
        exit;
    }

    // Verify ballot is still pending (prevent double-cast)
    $stmtBallot = $pdo->prepare('SELECT status FROM ballots WHERE ballot_id = ? FOR UPDATE');
    $stmtBallot->execute([$ballot_id]);
    $ballotRow = $stmtBallot->fetch();

    if (!$ballotRow || $ballotRow['status'] !== 'pending') {
        $pdo->rollBack();
        flash_set('error', 'Ballot is invalid or has already been cast.');
        header('Location: ' . ROOT_URL . '/ballot/form.php');
        exit;
    }

    $stmt = $pdo->prepare('
        INSERT INTO results (candidate_id, total_votes) 
        VALUES (?, 1)
        ON DUPLICATE KEY UPDATE total_votes = total_votes + 1
    ');

    foreach ($candidateIds as $cid) {
        $stmt->execute([$cid]);
    }

    // Mark ballot as cast
    $stmtCast = $pdo->prepare('UPDATE ballots SET status = ? WHERE ballot_id = ?');
    $stmtCast->execute(['cast', $ballot_id]);

    // Update export_log to ensure a new uniqid for the current state
    $newUniqid = uniqid('acm_', true);
    if ($exportLog) {
        $stmtUpdate = $pdo->prepare('UPDATE export_log SET uniqid = ? WHERE id = ?');
        $stmtUpdate->execute([$newUniqid, $exportLog['id']]);
    } else {
        $stmtInsert = $pdo->prepare('INSERT INTO export_log (uniqid) VALUES (?)');
        $stmtInsert->execute([$newUniqid]);
    }

    $pdo->commit();
    flash_set('success', 'Ballot ' . e($ballot_id) . ' successfully cast! Device is ready for the next voter.');
} catch (PDOException $e) {
    $pdo->rollBack();
    flash_set('error', 'Database error: ' . $e->getMessage());
}

header('Location: ' . ROOT_URL . '/ballot/form.php');
exit;
