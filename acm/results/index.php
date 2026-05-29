<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/layout.php';
require_auth();

$pdo = getDB();

// Fetch local totals
$stmt = $pdo->query(
    'SELECT c.id, c.name AS cname,
            p.name AS position_name,
            c.party_name,
            COALESCE(r.total_votes, 0) AS votes
     FROM candidates c
     JOIN positions p ON p.id = c.position_id
     LEFT JOIN results r ON r.candidate_id = c.id
     ORDER BY p.ranking ASC, votes DESC, c.name'
);  
$results = $stmt->fetchAll();

$groups = [];
foreach ($results as $row) {
    $pos = $row['position_name'];
    if (!isset($groups[$pos])) {
        $groups[$pos] = ['total_position_votes' => 0, 'candidates' => []];
    }
    $groups[$pos]['total_position_votes'] += $row['votes'];
    $groups[$pos]['candidates'][] = $row;
}

$location = $pdo->query('SELECT location FROM candidates LIMIT 1')->fetchColumn() ?: 'Unknown';
$total_votes = $pdo->query('SELECT COALESCE(SUM(total_votes), 0) FROM results')->fetchColumn();

// Fetch export status
$exportLog = $pdo->query('SELECT is_exported FROM export_log ORDER BY id DESC LIMIT 1')->fetch();
$is_exported = $exportLog ? (bool)$exportLog['is_exported'] : false;
$can_export = ($total_votes > 0 && !$is_exported);

layout_header('Local Result', 'results');
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <p class="text-sm text-gray-500">Location: <strong class="text-gray-800"><?= e($location) ?></strong></p>
        <p class="text-xs text-gray-400 mt-1">Live view of locally cast votes on this device.</p>
    </div>
    <div class="flex gap-2">
        <button onclick="window.location.reload();" 
                class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50 transition-colors shadow-sm flex items-center gap-2">
            <span>↻</span> Refresh Result
        </button>
        <form method="POST" action="<?= ROOT_URL ?>/results/upload.php" <?= $can_export ? 'onsubmit="window.open(\'' . ROOT_URL . '/election_return.php?type=final\', \'_blank\')"' : '' ?>>
            <button type="submit" <?= !$can_export ? 'disabled' : '' ?>
                    class="bg-navy hover:bg-navy-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm transition-colors text-sm flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.2 15c.7-1.2 1-2.5.7-3.9-.6-2-2.4-3.5-4.4-3.5h-1.2c-.7-3-3.2-5.2-6.2-5.6-3-.3-5.9 1.3-7.3 4-1.2 2.5-1 6.5.5 8.8m8.7-1.6V21"/><path d="M16 16l-4-4-4 4"/></svg>
                3G Upload
            </button>
        </form>
        <form method="POST" action="<?= ROOT_URL ?>/results/download.php" <?= $can_export ? 'onsubmit="window.open(\'' . ROOT_URL . '/election_return.php?type=final\', \'_blank\')"' : '' ?>>
            <button type="submit" <?= !$can_export ? 'disabled' : '' ?>
                    class="bg-gold hover:bg-gold-500 text-navy-800 font-medium py-2 px-4 rounded-lg shadow-sm transition-colors text-sm flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <span>↑</span> Export Results
            </button>
        </form>
    </div>
</div>

<?php $error = flash_get('error'); if ($error): ?>
<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded"><?= e($error) ?></div>
<?php endif; ?>

<?php if (empty($groups)): ?>
<div class="bg-white border border-gray-200 rounded-xl p-8 text-center text-gray-500 shadow-sm">
    No candidates loaded. Please import a configuration first.
</div>
<?php else: ?>

<div class="space-y-6">
    <?php foreach ($groups as $position => $data): ?>
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
        <div class="bg-navy-50 border-b border-gray-200 px-5 py-3 flex justify-between items-center">
            <h3 class="font-bold text-gray-800"><?= e($position) ?></h3>
            <span class="text-xs text-gray-500 bg-white border border-gray-200 px-3 py-1 rounded-full shadow-sm">
                Total Local Votes: <strong class="text-gray-800"><?= number_format($data['total_position_votes']) ?></strong>
            </span>
        </div>
        
        <div class="p-5">
            <?php foreach ($data['candidates'] as $c): ?>
            <?php
                $pct = $data['total_position_votes'] > 0 
                       ? round(($c['votes'] / $data['total_position_votes']) * 100, 1) 
                       : 0;
            ?>
            <div class="mb-4 last:mb-0">
                <div class="flex justify-between items-end mb-1">
                    <div>
                        <span class="font-medium text-gray-900"><?= e($c['cname']) ?></span>
                        <span class="text-xs text-gray-500">(<?= e($c['party_name']) ?>)</span>
                    </div>
                    <div class="text-right">
                        <span class="font-bold text-gray-800"><?= number_format($c['votes']) ?></span>
                        <span class="text-xs text-gray-500 ml-2 w-10 inline-block"><?= number_format($pct, 1) ?>%</span>
                    </div>
                </div>
                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="bg-navy h-full rounded-full transition-all duration-500" style="width: <?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php layout_footer(); ?>
