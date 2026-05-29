<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/db.php';
require_auth();

use Dompdf\Dompdf;
use Dompdf\Options;

$type = $_GET['type'] ?? 'final';
$isInit = ($type === 'init');

$pdo = getDB();

// Fetch Location
$location = $pdo->query('SELECT location FROM candidates LIMIT 1')->fetchColumn() ?: 'Unknown Location';

// Date/Time
$printDate = date('Y-m-d H:i:s');

// Fetch results
$positions = $pdo->query('SELECT * FROM positions ORDER BY ranking ASC, name ASC')->fetchAll();

$candidateStmt = $pdo->prepare('
    SELECT c.name, c.party_name, COALESCE(r.total_votes, 0) as votes
    FROM candidates c
    LEFT JOIN results r ON c.id = r.candidate_id
    WHERE c.position_id = ?
    ORDER BY votes DESC, c.name ASC
');

$totalVotes = 0;

// Build HTML
$html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 15mm 20mm; }
    body { font-family: "Courier New", Courier, monospace; font-size: 12px; margin: 0; padding: 0; color: #000; }
    .header { text-align: center; border-bottom: 2px dashed #000; padding-bottom: 15px; margin-bottom: 15px; }
    .title { font-size: 16px; font-weight: bold; margin: 0 0 5px 0; text-transform: uppercase; }
    .info-row { margin-bottom: 5px; }
    .info-label { font-weight: bold; }
    .section-title { font-weight: bold; margin-top: 15px; border-bottom: 1px solid #000; padding-bottom: 2px; }
    table { width: 100%; border-collapse: collapse; margin-top: 5px; }
    th, td { text-align: left; padding: 4px 0; border-bottom: 1px dotted #ccc; }
    th { font-weight: bold; }
    .text-right { text-align: right; }
    .footer { margin-top: 30px; text-align: center; border-top: 2px dashed #000; padding-top: 15px; font-size: 10px; }
    .sig-container { margin-top: 40px; width: 100%; }
    .sig-line { border-top: 1px solid #000; width: 200px; text-align: center; padding-top: 5px; display: inline-block; }
    .cert-box { margin-top: 10px; padding: 10px; border: 1px solid #000; text-align: center; font-weight: bold; }
    .total-row { margin-top: 20px; text-align: right; font-size: 14px; font-weight: bold; }
</style>
</head>
<body>';

$html .= '<div class="header">';
$html .= '<h1 class="title">ELECTION RETURN</h1>';
$html .= '<h2 style="font-size: 14px; margin: 0;">' . ($isInit ? '(INITIALIZATION - ZERO REPORT)' : '(FINAL RESULTS)') . '</h2>';
$html .= '</div>';

$html .= '<div>';
$html .= '<div class="info-row"><span class="info-label">Polling Place / Location:</span> ' . htmlspecialchars($location) . '&nbsp;&nbsp;&nbsp;&nbsp;<span class="info-label">Date &amp; Time:</span> ' . htmlspecialchars($printDate) . '</div>';
$html .= '<div class="info-row"><span class="info-label">Report Type:</span> ' . ($isInit ? 'Initialization (Before Voting)' : 'Final Transmission (After Voting)') . '</div>';

if ($isInit) {
    $html .= '<div class="cert-box">CERTIFICATION THAT SYSTEM CONTAINS NO VOTES</div>';
}
$html .= '</div>';

$html .= '<div style="margin-top: 20px;">';
foreach ($positions as $pos) {
    $html .= '<div class="section-title">' . htmlspecialchars($pos['name']) . '</div>';
    $html .= '<table>';
    $html .= '<thead><tr><th style="width:50%;">Candidate Name</th><th style="width:30%;">Party</th><th style="width:20%;" class="text-right">Votes Received</th></tr></thead>';
    $html .= '<tbody>';

    $candidateStmt->execute([$pos['id']]);
    $candidates = $candidateStmt->fetchAll();
    foreach ($candidates as $cand) {
        $v = $isInit ? 0 : (int)$cand['votes'];
        $totalVotes += $v;
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($cand['name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($cand['party_name']) . '</td>';
        $html .= '<td class="text-right">' . $v . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
}
$html .= '</div>';

$html .= '<div class="total-row">Total Votes Cast: ' . $totalVotes . '</div>';

$html .= '<table class="sig-container" style="margin-top:40px; border:none;"><tr>';
$html .= '<td style="text-align:center; border:none; padding-top:30px;"><div style="border-top:1px solid #000; width:200px; display:inline-block; padding-top:5px;">Election Officer 1 Signature</div></td>';
$html .= '<td style="text-align:center; border:none; padding-top:30px;"><div style="border-top:1px solid #000; width:200px; display:inline-block; padding-top:5px;">Election Officer 2 Signature</div></td>';
$html .= '</tr></table>';

$html .= '<div class="footer"><p>ACM Voting System - Generated electronically. End of report.</p></div>';

$html .= '</body></html>';

// Generate PDF with DomPDF
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Courier');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$reportType = $isInit ? 'init' : 'final';
$filename = 'election_return_' . $reportType . '_' . preg_replace('/[^a-z0-9]/i', '_', $location) . '_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ['Attachment' => false]);
exit;
