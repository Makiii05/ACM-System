<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/layout.php';
require_auth();

$pdo = getDB();
$locations = $pdo->query('SELECT DISTINCT location FROM candidates ORDER BY location')->fetchAll(PDO::FETCH_COLUMN);

$selected = trim($_GET['location'] ?? '');
$ballots  = [];

if ($selected !== '') {
    $stmt = $pdo->prepare('SELECT * FROM ballots WHERE city = ? ORDER BY ballot_id ASC');
    $stmt->execute([$selected]);
    $ballots = $stmt->fetchAll();
}

layout_header('Ballots', 'ballot');
$error   = flash_get('error');
$success = flash_get('success');
?>

<?php if ($error): ?>
<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-xs rounded"><?= e($success) ?></div>
<?php endif; ?>

<!-- Location selector -->
<div class="flex items-center gap-3 mb-5">
    <form method="GET" class="flex items-center gap-3">
        <label class="text-xs font-medium text-gray-600">Location:</label>
        <select name="location" id="ballot-location"
                class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-navy bg-white"
                onchange="this.form.submit()">
            <option value="">— select location —</option>
            <?php foreach ($locations as $loc): ?>
            <option value="<?= e($loc) ?>" <?= $selected === $loc ? 'selected' : '' ?>><?= e($loc) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selected && !empty($ballots)): ?>
    <form method="POST" action="<?= ROOT_URL ?>/ballot/export.php" class="inline">
        <input type="hidden" name="location" value="<?= e($selected) ?>">
        <button type="submit" class="bg-gold hover:bg-gold-500 text-navy-800 px-4 py-2 rounded text-xs font-medium transition-colors shadow-sm">
            ↑ Export Ballot Config
        </button>
    </form>
    <?php endif; ?>
</div>

<?php if ($selected === ''): ?>
<p class="text-xs text-gray-400">Select a location above to manage ballots.</p>
<?php else: ?>

<!-- Generate Ballots Form -->
<div class="bg-white border border-gray-200 rounded-lg p-5 mb-6 max-w-lg">
    <h3 class="text-sm font-semibold text-gray-700 mb-3">Generate Ballots</h3>
    <form method="POST" action="<?= ROOT_URL ?>/ballot/generate.php" class="flex items-end gap-3">
        <input type="hidden" name="city" value="<?= e($selected) ?>">
        <div class="flex-1">
            <label class="block text-xs text-gray-500 mb-1">Number of ballots to generate</label>
            <input type="number" name="count" min="1" max="500" required
                   placeholder="e.g. 10"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-navy">
        </div>
        <button type="submit" class="bg-navy text-white px-5 py-2 rounded text-sm hover:bg-navy-700 transition-colors shadow-sm">
            Generate
        </button>
    </form>
</div>

<?php if (empty($ballots)): ?>
<p class="text-xs text-gray-400">No ballots generated yet for <strong><?= e($selected) ?></strong>. Use the form above to generate ballots.</p>
<?php else: ?>

<!-- Ballot List -->
<form method="POST" action="<?= ROOT_URL ?>/ballot/pdf.php" id="ballot-print-form">
    <input type="hidden" name="location" value="<?= e($selected) ?>">
    
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-700">
            Ballots for <?= e($selected) ?>
            <span class="text-xs font-normal text-gray-400 ml-2">(<?= count($ballots) ?> total)</span>
        </h3>
        <button type="submit" class="bg-navy text-white px-4 py-2 rounded text-xs hover:bg-navy-700 transition-colors shadow-sm">
            ⎙ Print Selected as PDF
        </button>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-navy-50 border-b border-gray-200">
                    <th class="w-10 px-3 py-2 text-left">
                        <input type="checkbox" id="select-all-ballots" class="rounded text-navy focus:ring-navy"
                               onchange="document.querySelectorAll('input[name=\'ballot_ids[]\']').forEach(cb => cb.checked = this.checked)">
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ballot ID</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">City</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($ballots as $b): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-3 py-2">
                        <input type="checkbox" name="ballot_ids[]" value="<?= e($b['ballot_id']) ?>" class="rounded text-navy focus:ring-navy">
                    </td>
                    <td class="px-3 py-2 font-mono text-navy font-medium"><?= e($b['ballot_id']) ?></td>
                    <td class="px-3 py-2 text-gray-700"><?= e($b['city']) ?></td>
                    <td class="px-3 py-2">
                        <?php if ($b['status'] === 'sent'): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Sent
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Unsent
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 py-2 text-gray-500 text-xs"><?= e($b['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>

<?php endif; ?>
<?php endif; ?>

<?php layout_footer(); ?>
