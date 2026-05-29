<?php
require_once __DIR__ . '/db.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reset_device'])) {
    header('Location: ' . ROOT_URL . '/index.php');
    exit;
}

$pdo = getDB();
try {
    $pdo->beginTransaction();
    // Delete in reverse dependency order
    $pdo->exec('DELETE FROM results');
    $pdo->exec('DELETE FROM candidates');
    $pdo->exec('DELETE FROM positions');
    $pdo->exec('DELETE FROM ballots');
    $pdo->exec('DELETE FROM export_log');
    $pdo->commit();
    flash_set('success', 'Device memory cleared successfully.');
} catch (PDOException $e) {
    $pdo->rollBack();
    flash_set('error', 'Error clearing device: ' . $e->getMessage());
}

header('Location: ' . ROOT_URL . '/index.php');
exit;
