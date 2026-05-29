<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/layout.php';
require_auth();

$pdo = getDB();
$success = flash_get('success');
$error   = flash_get('error');

$locations = $pdo->query('SELECT DISTINCT location FROM candidates ORDER BY location')->fetchAll(PDO::FETCH_COLUMN);

$selected = trim($_GET['location'] ?? '');
$resultsData = [];

if ($selected !== '') {
    // Get all candidates for the location with their current votes
    $stmt = $pdo->prepare(
        'SELECT c.id, c.name AS cname,
                p.name AS position_name, p.max_votes,
                pt.name AS party_name,
                COALESCE(r.total_votes, 0) AS votes
         FROM candidates c
         JOIN positions p  ON p.id  = c.position_id
         JOIN parties   pt ON pt.id = c.party_id
         LEFT JOIN results r ON r.candidate_id = c.id
         WHERE c.location = ?
         ORDER BY p.ranking ASC, votes DESC, c.name'
    );
    $stmt->execute([$selected]);
    
    // Group by position and calculate total votes per position
    foreach ($stmt->fetchAll() as $row) {
        $pos = $row['position_name'];
        if (!isset($resultsData[$pos])) {
            $resultsData[$pos] = ['total_position_votes' => 0, 'candidates' => []];
        }
        $resultsData[$pos]['total_position_votes'] += $row['votes'];
        $resultsData[$pos]['candidates'][] = $row;
    }
}

layout_header('Results', 'results');
?>

<?php if ($success): ?>
<div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-xs rounded"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded"><?= e($error) ?></div>
<?php endif; ?>

<!-- Import Form -->
<div class="bg-white border border-gray-200 rounded-lg p-5 mb-6 flex items-center justify-between">
    <div>
        <h3 class="font-medium text-gray-800 text-sm">Import Results</h3>
        <p class="text-xs text-gray-500 mt-1">Upload the encrypted <code>.json</code> results file exported from the ACM device.</p>
    </div>
    <form method="POST" action="<?= ROOT_URL ?>/results/import.php" enctype="multipart/form-data" class="flex items-center gap-3">
        <input type="file" name="import_file" accept=".json" required
               class="border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
        <button type="submit" class="bg-navy text-white text-xs px-4 py-2 rounded hover:bg-navy-700 transition-colors">
            ↑ Upload & Process
        </button>
    </form>
</div>

<!-- Location selector and Refresh -->
<div class="flex items-center justify-between mb-5">
    <form method="GET" class="flex items-center gap-3">
        <label class="text-xs font-medium text-gray-600">Location:</label>
        <select name="location" id="results-location"
                class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white"
                onchange="this.form.submit()">
            <option value="">— select location —</option>
            <?php foreach ($locations as $loc): ?>
            <option value="<?= e($loc) ?>" <?= $selected === $loc ? 'selected' : '' ?>><?= e($loc) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    
    <button onclick="window.location.reload();" id="refresh-btn"
            class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-2 rounded text-xs hover:bg-gray-200 transition-colors flex items-center gap-2">
        <span>↻</span> Refresh Results
    </button>
</div>

<?php if ($selected === ''): ?>
<p class="text-xs text-gray-400">Select a location to view results.</p>
<?php elseif (empty($resultsData)): ?>
<p class="text-xs text-gray-400">No candidates found for <strong><?= e($selected) ?></strong>.</p>
<?php else: ?>

<div class="space-y-6">
    <?php foreach ($resultsData as $position => $data): ?>
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="bg-navy-50 border-b border-gray-200 px-4 py-3 flex justify-between items-center">
            <h3 class="font-semibold text-gray-800"><?= e($position) ?></h3>
            <span class="text-xs text-gray-500">Total Votes: <strong><?= number_format($data['total_position_votes']) ?></strong></span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider">Candidate</th>
                    <th class="text-left px-4 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider">Party</th>
                    <th class="text-right px-4 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider">Votes</th>
                    <th class="text-right px-4 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider w-32">%</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($data['candidates'] as $c): ?>
                <?php
                    $pct = $data['total_position_votes'] > 0 
                           ? round(($c['votes'] / $data['total_position_votes']) * 100, 2) 
                           : 0;
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium"><?= e($c['cname']) ?></td>
                    <td class="px-4 py-3 text-gray-600 text-xs"><?= e($c['party_name']) ?></td>
                    <td class="px-4 py-3 text-right font-medium"><?= number_format($c['votes']) ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <span class="text-xs text-gray-500 w-10"><?= number_format($pct, 2) ?>%</span>
                            <div class="w-16 h-2 bg-gray-200 rounded overflow-hidden">
                                <div class="bg-navy h-full" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php layout_footer(); ?>
