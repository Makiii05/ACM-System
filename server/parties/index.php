<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/layout.php';
require_auth();

$pdo = getDB();

// ── Fetch edit record ─────────────────────────────────────────────────────────
$edit = null;
if (isset($_GET['edit'])) {
    $s    = $pdo->prepare('SELECT * FROM parties WHERE id = ?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

$parties = $pdo->query('SELECT * FROM parties ORDER BY name')->fetchAll();

layout_header('Parties', 'parties');
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
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Party Name</th>
                    <th class="px-4 py-3" data-no-sort></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($parties)): ?>
                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400 text-xs">No parties yet.</td></tr>
                <?php else: ?>
                <?php foreach ($parties as $p): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-500 font-medium"><?= e($p['code']) ?></td>
                    <td class="px-4 py-3 font-medium"><?= e($p['name']) ?></td>
                    <td class="px-4 py-3 text-right space-x-2">
                        <a href="?edit=<?= $p['id'] ?>" class="text-xs text-blue-600 hover:underline">Edit</a>
                        <a href="delete.php?id=<?= $p['id'] ?>"
                           onclick="return confirm('Delete this party?')"
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
        <p class="text-xs font-medium text-gray-700 mb-4"><?= $edit ? 'Edit Party' : 'Add Party' ?></p>
        <form method="POST" action="<?= ROOT_URL ?>/parties/<?= $edit ? 'update.php' : 'create.php' ?>" class="space-y-3">
            <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Party Code</label>
                <input type="text" name="code" id="party-code" required
                       value="<?= e($edit['code'] ?? '') ?>"
                       placeholder="e.g. IDP"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Party Name</label>
                <input type="text" name="name" id="party-name" required
                       value="<?= e($edit['name'] ?? '') ?>"
                       placeholder="e.g. Independent Party"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400">
            </div>
            <div class="flex gap-2">
                <button type="submit" id="party-save-btn"
                        class="flex-1 bg-navy text-white text-xs py-2 rounded hover:bg-navy-700 transition-colors">
                    <?= $edit ? 'Update' : 'Save' ?>
                </button>
                <?php if ($edit): ?>
                <a href="<?= ROOT_URL ?>/parties/index.php"
                   class="border border-gray-300 text-gray-600 text-xs py-2 px-3 rounded hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

</div>

<?php layout_footer(); ?>
