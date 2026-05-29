<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/layout.php';
require_auth();

$pdo = getDB();

// ── Fetch edit record ─────────────────────────────────────────────────────────
$edit = null;
if (isset($_GET['edit'])) {
    $s    = $pdo->prepare('SELECT * FROM positions WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

$positions = $pdo->query('SELECT * FROM positions ORDER BY ranking ASC, name')->fetchAll();

layout_header('Positions', 'positions');
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
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Max Votes</th>
                    <th class="px-4 py-3" data-no-sort></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($positions)): ?>
                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400 text-xs">No positions yet.</td></tr>
                <?php else: ?>
                <?php foreach ($positions as $p): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500"><?= (int)$p['ranking'] ?></td>
                    <td class="px-4 py-3 font-medium"><?= e($p['name']) ?></td>
                    <td class="px-4 py-3 text-center text-gray-600"><?= (int)$p['max_votes'] ?></td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="?edit=<?= $p['id'] ?>" class="text-xs text-blue-600 hover:underline">Edit</a>
                        <a href="delete.php?id=<?= $p['id'] ?>"
                           onclick="return confirm('Delete this position?')"
                           class="text-xs text-red-500 hover:underline">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Form -->
    <div class="w-64 bg-white border border-gray-200 rounded-lg p-5 shrink-0">
        <p class="text-xs font-medium text-gray-700 mb-4"><?= $edit ? 'Edit Position' : 'Add Position' ?></p>
        <form method="POST" action="<?= ROOT_URL ?>/positions/<?= $edit ? 'update.php' : 'create.php' ?>" class="space-y-3">
            <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Name</label>
                <input type="text" name="name" id="pos-name" required
                       value="<?= e($edit['name'] ?? '') ?>"
                       placeholder="e.g. President"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Max Votes</label>
                <input type="number" name="max_votes" id="pos-max" required min="1"
                       value="<?= (int)($edit['max_votes'] ?? 1) ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Ranking</label>
                <input type="number" name="ranking" id="pos-ranking" required min="1"
                       value="<?= (int)($edit['ranking'] ?? 1) ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400">
            </div>
            <div class="flex gap-2">
                <button type="submit" id="pos-save-btn"
                        class="flex-1 bg-navy text-white text-xs py-2 rounded hover:bg-navy-700 transition-colors">
                    <?= $edit ? 'Update' : 'Save' ?>
                </button>
                <?php if ($edit): ?>
                <a href="<?= ROOT_URL ?>/positions/index.php"
                   class="border border-gray-300 text-gray-600 text-xs py-2 px-3 rounded hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

</div>

<?php layout_footer(); ?>
