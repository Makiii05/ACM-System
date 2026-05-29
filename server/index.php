<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';
require_auth();

$pdo   = getDB();
$stats = [
    'positions'   => $pdo->query('SELECT COUNT(*) FROM positions')->fetchColumn(),
    'parties'     => $pdo->query('SELECT COUNT(*) FROM parties')->fetchColumn(),
    'candidates'  => $pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn(),
    'total_votes' => $pdo->query('SELECT COALESCE(SUM(total_votes),0) FROM results')->fetchColumn(),
];

layout_header('Dashboard', 'dashboard');
?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $cards = [
        ['Positions',   $stats['positions'],   'border-l-navy'],
        ['Parties',     $stats['parties'],     'border-l-gold'],
        ['Candidates',  $stats['candidates'],  'border-l-navy-700'],
        ['Total Votes', $stats['total_votes'], 'border-l-gold-500'],
    ]; ?>
    <?php foreach ($cards as [$label, $value, $accent]): ?>
    <div class="bg-white border border-gray-200 border-l-4 <?= $accent ?> rounded-lg p-4">
        <p class="text-xs text-gray-500"><?= $label ?></p>
        <p class="text-2xl font-semibold text-gray-800 mt-1"><?= number_format((int)$value) ?></p>
    </div>
    <?php endforeach; ?>
</div>

<?php layout_footer(); ?>
