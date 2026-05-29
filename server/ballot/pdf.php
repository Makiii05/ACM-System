<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../db.php';
require_auth();

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ROOT_URL . '/ballot/print.php');
    exit;
}

$ballot_ids = $_POST['ballot_ids'] ?? [];
$location   = trim($_POST['location'] ?? '');

if (empty($ballot_ids) || $location === '') {
    flash_set('error', 'Please select at least one ballot to print.');
    header('Location: ' . ROOT_URL . '/ballot/print.php?location=' . urlencode($location));
    exit;
}

$pdo = getDB();

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

// Fetch candidates grouped by position
$stmtCand = $pdo->prepare(
    'SELECT c.id, c.name, c.position_id, c.location,
            pt.name AS party, pt.code AS party_code
     FROM candidates c
     JOIN parties pt ON pt.id = c.party_id
     JOIN positions p ON p.id = c.position_id
     WHERE c.location = ?
     ORDER BY p.ranking ASC, c.name'
);
$stmtCand->execute([$location]);
$allCandidates = $stmtCand->fetchAll();

$groups = [];
foreach ($allCandidates as $c) {
    $groups[$c['position_id']][] = $c;
}

// Build HTML for all ballot pages
$html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 20mm 15mm; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 0; }
    .ballot-page { page-break-after: always; }
    .ballot-page:last-child { page-break-after: auto; }
    .header { text-align: center; border-bottom: 2px solid #004179; padding-bottom: 10px; margin-bottom: 15px; }
    .header h1 { font-size: 18px; color: #004179; margin: 0 0 3px 0; text-transform: uppercase; letter-spacing: 1px; }
    .header p { margin: 2px 0; font-size: 10px; color: #555; }
    .ballot-id-badge { display: inline-block; background: #f3c404; color: #004179; font-weight: bold; font-size: 12px; padding: 4px 14px; border-radius: 4px; margin-top: 6px; font-family: monospace; letter-spacing: 1px; }
    .position-block { margin-bottom: 12px; }
    .position-title { font-size: 11px; font-weight: bold; color: #004179; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-bottom: 6px; }
    .position-note { font-size: 9px; font-weight: normal; color: #666; text-transform: none; letter-spacing: 0; }
    .candidate-row { display: flex; align-items: center; padding: 3px 0; }
    .checkbox { width: 12px; height: 12px; border: 1.5px solid #004179; display: inline-block; margin-right: 8px; flex-shrink: 0; }
    .candidate-name { font-size: 11px; font-weight: 600; }
    .candidate-party { font-size: 9px; color: #666; margin-left: 5px; }
    .footer { text-align: center; border-top: 1px solid #ccc; padding-top: 8px; margin-top: 15px; font-size: 8px; color: #999; }
    table.candidates { width: 100%; border-collapse: collapse; }
    table.candidates td { padding: 3px 4px; vertical-align: middle; }
</style>
</head>
<body>';

foreach ($ballot_ids as $bid) {
    $html .= '<div class="ballot-page">';
    $html .= '<div class="header">';
    $html .= '<p style="font-size:9px; text-transform:uppercase; letter-spacing:2px; color:#999;">Official Ballot</p>';
    $html .= '<h1>ACM Election System</h1>';
    $html .= '<p>Location: <strong>' . htmlspecialchars($location) . '</strong></p>';
    $html .= '<div class="ballot-id-badge">' . htmlspecialchars($bid) . '</div>';
    $html .= '</div>';

    foreach ($positions as $pos) {
        $max = (int)$pos['max_votes'];
        $html .= '<div class="position-block">';
        $html .= '<div class="position-title">' . htmlspecialchars($pos['name']);
        $html .= ' <span class="position-note">(Vote for ' . ($max === 1 ? 'one' : "up to $max") . ')</span>';
        $html .= '</div>';

        $html .= '<table class="candidates">';
        $posCandidates = $groups[$pos['id']] ?? [];
        foreach ($posCandidates as $c) {
            $html .= '<tr>';
            $html .= '<td style="width:20px;"><div class="checkbox"></div></td>';
            $html .= '<td><span class="candidate-name">' . htmlspecialchars($c['name']) . '</span>';
            $partyLabel = $c['party_code'] ?: $c['party'];
            $html .= ' <span class="candidate-party">(' . htmlspecialchars($partyLabel) . ')</span></td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '</div>';
    }

    $html .= '<div class="footer">ACM Voting System — Ballot ID: ' . htmlspecialchars($bid) . ' — Generated ' . date('Y-m-d H:i') . '</div>';
    $html .= '</div>';
}

$html .= '</body></html>';

// Generate PDF
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'ballots_' . preg_replace('/[^a-z0-9]/i', '_', $location) . '_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ['Attachment' => false]);
exit;
