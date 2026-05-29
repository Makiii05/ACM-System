<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/layout.php';
require_auth();

$pdo = getDB();

$stmt = $pdo->query('SELECT * FROM api_uploads ORDER BY created_at DESC');
$uploads = $stmt->fetchAll();

layout_header('3G Uploads', 'results');
$success = flash_get('success');
$error = flash_get('error');
?>

<?php if ($success): ?>
<div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-xs rounded"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded"><?= e($error) ?></div>
<?php endif; ?>

<div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
    <div class="bg-navy-50 border-b border-gray-200 px-4 py-3 flex justify-between items-center">
        <h3 class="font-semibold text-gray-800">Recent 3G Upload Attempts</h3>
        <button onclick="window.location.reload();" class="text-xs text-gray-600 hover:text-gray-900 bg-white border border-gray-300 px-3 py-1 rounded shadow-sm">↻ Refresh</button>
    </div>
    
    <?php if (empty($uploads)): ?>
    <div class="p-6 text-center text-gray-500 text-sm">
        No 3G uploads received yet.
    </div>
    <?php else: ?>
    <div data-table-wrapper>
        <table class="w-full text-sm" data-sortable-table>
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Expected Votes</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Actual Votes</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider" data-no-sort>Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($uploads as $u): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600" data-sort-value="<?= strtotime($u['created_at']) ?>"><?= date('M j, Y h:i A', strtotime($u['created_at'])) ?></td>
                    <td class="px-4 py-3 font-medium"><?= e($u['location']) ?></td>
                    <td class="px-4 py-3 text-center"><?= number_format($u['expected_votes']) ?></td>
                    <td class="px-4 py-3 text-center font-semibold <?= $u['expected_votes'] == $u['actual_votes'] ? 'text-green-600' : 'text-red-600' ?>">
                        <?= number_format($u['actual_votes']) ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <?php if ($u['status'] === 'approved'): ?>
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Approved</span>
                        <?php elseif ($u['status'] === 'pending'): ?>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">Pending Approval</span>
                        <?php elseif ($u['status'] === 'incomplete'): ?>
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">Incomplete</span>
                        <?php else: ?>
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold"><?= ucfirst(e($u['status'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <?php if ($u['status'] === 'pending'): ?>
                        <form method="POST" action="<?= ROOT_URL ?>/results/approve_upload.php" class="inline-block" onsubmit="return confirm('Approve this upload and tally the results?');">
                            <input type="hidden" name="upload_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="bg-navy text-white px-3 py-1 rounded text-xs hover:bg-navy-700 transition-colors">Approve</button>
                        </form>
                        <?php else: ?>
                            <button disabled class="bg-gray-200 text-gray-400 px-3 py-1 rounded text-xs cursor-not-allowed">Approve</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php layout_footer(); ?>
