<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/layout.php';
require_auth();

$pdo = getDB();

// ── Fetch edit record ─────────────────────────────────────────────────────────
$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare('SELECT * FROM candidates WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

$candidates = $pdo->query(
    'SELECT c.id, c.name, c.location,
            p.name AS position_name, 
            pt.code AS party_code
     FROM candidates c
     JOIN positions p  ON p.id  = c.position_id
     JOIN parties   pt ON pt.id = c.party_id
     ORDER BY p.ranking ASC, c.name'
)->fetchAll();

$positions = $pdo->query('SELECT id, name FROM positions ORDER BY name')->fetchAll();
$parties   = $pdo->query('SELECT id, name FROM parties ORDER BY name')->fetchAll();

// Distinct locations already in DB (for datalist)
$locations = $pdo->query('SELECT DISTINCT location FROM candidates ORDER BY location')->fetchAll(PDO::FETCH_COLUMN);

layout_header('Candidates', 'candidates');
$success = flash_get('success');
$error   = flash_get('error');
?>

<?php if ($success): ?>
<div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-xs rounded"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded"><?= e($error) ?></div>
<?php endif; ?>

<div class="flex gap-5 items-start">

    <!-- Table -->
    <div class="flex-1 bg-white border border-gray-200 rounded-lg overflow-hidden" data-table-wrapper>
        <table class="w-full text-sm" data-sortable-table>
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Party</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-4 py-3" data-no-sort></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($candidates)): ?>
                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400 text-xs">No candidates yet.</td></tr>
                <?php else: ?>
                <?php foreach ($candidates as $c): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium"><?= e($c['name']) ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= e($c['position_name']) ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= e($c['party_code']) ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= e($c['location']) ?></td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="?edit=<?= $c['id'] ?>" class="text-xs text-blue-600 hover:underline">Edit</a>
                        <a href="delete.php?id=<?= $c['id'] ?>"
                           onclick="return confirm('Delete this candidate?')"
                           class="text-xs text-red-500 hover:underline">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Form -->
    <div class="w-68 bg-white border border-gray-200 rounded-lg p-5 shrink-0" style="width:17rem">
        <p class="text-xs font-medium text-gray-700 mb-4"><?= $edit ? 'Edit Candidate' : 'Add Candidate' ?></p>
        <form method="POST" action="<?= ROOT_URL ?>/candidates/<?= $edit ? 'update.php' : 'create.php' ?>" class="space-y-3">
            <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Full Name</label>
                <input type="text" name="name" id="cand-name" required
                       value="<?= e($edit['name'] ?? '') ?>"
                       placeholder="e.g. Juan dela Cruz"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Position</label>
                <select name="position_id" id="cand-position" required
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                    <option value="">— select —</option>
                    <?php foreach ($positions as $pos): ?>
                    <option value="<?= $pos['id'] ?>"
                        <?= ($edit['position_id'] ?? '') == $pos['id'] ? 'selected' : '' ?>>
                        <?= e($pos['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Party</label>
                <select name="party_id" id="cand-party" required
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                    <option value="">— select —</option>
                    <?php foreach ($parties as $pt): ?>
                    <option value="<?= $pt['id'] ?>"
                        <?= ($edit['party_id'] ?? '') == $pt['id'] ? 'selected' : '' ?>>
                        <?= e($pt['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Location (geographic scope)</label>
                <input type="text" name="location" id="cand-location" required
                       list="locations-list"
                       value="<?= e($edit['location'] ?? '') ?>"
                       placeholder="e.g. Philippines"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400">
                <datalist id="locations-list">
                    <?php foreach ($locations as $loc): ?>
                    <option value="<?= e($loc) ?>">
                    <?php endforeach; ?>
                </datalist>
                <p class="text-[10px] text-gray-400 mt-1">Philippines / Manila City / Barangay 123</p>
            </div>

            <div class="flex gap-2">
                <button type="submit" id="cand-save-btn"
                        class="flex-1 bg-navy text-white text-xs py-2 rounded hover:bg-navy-700 transition-colors">
                    <?= $edit ? 'Update' : 'Save' ?>
                </button>
                <?php if ($edit): ?>
                <a href="<?= ROOT_URL ?>/candidates/index.php"
                   class="border border-gray-300 text-gray-600 text-xs py-2 px-3 rounded hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

</div>

<?php layout_footer(); ?>
