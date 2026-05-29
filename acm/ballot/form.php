<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/layout.php';
require_auth();

$pdo = getDB();

// Check if device is configured
$location = $pdo->query('SELECT location FROM candidates LIMIT 1')->fetchColumn();
if (!$location) {
    flash_set('error', 'Device is not configured. Please import a configuration first.');
    header('Location: ' . ROOT_URL . '/index.php');
    exit;
}

// Check if device is locked (already exported)
$exportLog = $pdo->query('SELECT is_exported FROM export_log ORDER BY id DESC LIMIT 1')->fetch();
if ($exportLog && $exportLog['is_exported']) {
    flash_set('error', 'Device has already exported results and is currently locked.');
    header('Location: ' . ROOT_URL . '/index.php');
    exit;
}

// ── Ballot ID verification ──────────────────────────────────────────────────
$ballot_id    = trim($_GET['ballot_id'] ?? $_POST['ballot_id'] ?? '');
$ballot_error = '';
$ballot_ok    = false;

if ($ballot_id !== '') {
    $stmt = $pdo->prepare('SELECT * FROM ballots WHERE ballot_id = ?');
    $stmt->execute([$ballot_id]);
    $ballot = $stmt->fetch();

    if (!$ballot) {
        $ballot_error = 'Ballot ID not found. Please check and try again.';
    } elseif ($ballot['status'] === 'cast') {
        $ballot_error = 'This ballot has already been cast.';
    } else {
        $ballot_ok = true;
    }
}

// Fetch structured ballot data (only when verified)
$groups = [];
if ($ballot_ok) {
    $stmt = $pdo->query(
        'SELECT c.id, c.name, p.name AS position_name, p.max_votes, c.party_name
         FROM candidates c
         JOIN positions p ON p.id = c.position_id
         ORDER BY p.ranking ASC, c.name'
    );

    foreach ($stmt->fetchAll() as $row) {
        $groups[$row['position_name']]['max_votes'] = $row['max_votes'];
        $groups[$row['position_name']]['candidates'][] = $row;
    }
}

layout_header('Open Ballot', 'ballot');
$success = flash_get('success');
?>

<?php if ($success): ?>
<div class="mb-5 p-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded shadow-sm">
    <?= e($success) ?>
</div>
<?php endif; ?>

<?php if (!$ballot_ok): ?>
<!-- ── Ballot ID Entry Gate ──────────────────────────────────────────────── -->
<div class="max-w-md mx-auto mt-12">
    <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm text-center">
        <div class="w-16 h-16 bg-navy-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-navy" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-1">Enter Ballot ID</h3>
        <p class="text-sm text-gray-500 mb-6">Please scan or type the ballot ID printed on the ballot paper.</p>

        <?php if ($ballot_error): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded">
            <?= e($ballot_error) ?>
        </div>
        <?php endif; ?>

        <form method="GET" class="space-y-4">
            <input type="text" name="ballot_id" id="ballot-id-input" required autofocus
                   value="<?= e($ballot_id) ?>"
                   placeholder="e.g. lipa-0001"
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 text-center text-lg font-mono tracking-wider focus:outline-none focus:ring-2 focus:ring-navy focus:border-navy">
            <button type="submit"
                    class="w-full bg-navy text-white font-semibold py-3 rounded-lg hover:bg-navy-700 transition-colors shadow-sm">
                Verify &amp; Proceed
            </button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ── Ballot Form ───────────────────────────────────────────────────────── -->
<div x-data="ballotForm()">
    <div class="flex justify-between items-end mb-6">
        <div>
            <p class="text-sm text-gray-500">Location: <strong class="text-gray-800"><?= e($location) ?></strong></p>
            <p class="text-xs text-gray-400 mt-0.5">Ballot ID: <strong class="text-navy font-mono"><?= e($ballot_id) ?></strong></p>
            <p class="text-xs text-gray-400 mt-1">Select candidates. Checkboxes enforce maximum limits automatically.</p>
        </div>
        <button type="button" @click="resetForm()" class="text-sm text-navy hover:text-navy-700 hover:underline">Clear Selections</button>
    </div>

    <form method="POST" action="<?= ROOT_URL ?>/ballot/submit.php" id="ballot-form" @submit.prevent="submitBallot">
        <input type="hidden" name="ballot_id" value="<?= e($ballot_id) ?>">
        <div class="space-y-6">
            <?php foreach ($groups as $posName => $group): ?>
            <?php 
                $max = (int)$group['max_votes'];
                $isRadio = $max === 1;
                $posIdSafe = preg_replace('/[^a-zA-Z0-9]/', '_', $posName);
            ?>
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="bg-navy-50 border-b border-gray-200 px-5 py-3 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800"><?= e($posName) ?></h3>
                    <span class="text-xs px-2.5 py-1 rounded-full <?= $isRadio ? 'bg-navy-100 text-navy' : 'bg-gold-50 text-gold-600' ?>">
                        Vote for <?= $max === 1 ? 'one' : "up to $max" ?>
                    </span>
                </div>
                
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($group['candidates'] as $c): ?>
                    <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-navy-50 hover:border-navy transition-colors has-[:checked]:bg-navy-50 has-[:checked]:border-navy has-[:checked]:ring-1 has-[:checked]:ring-navy">
                        <?php if ($isRadio): ?>
                            <input type="radio" name="votes[<?= e($posName) ?>]" value="<?= $c['id'] ?>" class="mt-1 text-navy focus:ring-navy">
                        <?php else: ?>
                            <input type="checkbox" name="votes[<?= e($posName) ?>][]" value="<?= $c['id'] ?>" 
                                   @change="handleCheckboxChange('<?= $posIdSafe ?>', <?= $max ?>, $event)"
                                   data-group="<?= $posIdSafe ?>"
                                   class="mt-1 text-gold focus:ring-gold rounded">
                        <?php endif; ?>
                        <div>
                            <div class="font-medium text-gray-900"><?= e($c['name']) ?></div>
                            <div class="text-xs text-gray-500 mt-0.5"><?= e($c['party_name']) ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 bg-white border border-gray-200 rounded-xl p-6 shadow-lg sticky bottom-6 z-20 flex justify-between items-center">
            <div>
                <h4 class="font-bold text-gray-800 text-lg">Ready to Submit?</h4>
                <p class="text-sm text-gray-500">Please review your selections carefully.</p>
            </div>
            <button type="submit" class="bg-navy hover:bg-navy-700 text-white font-bold py-3 px-8 rounded-lg shadow transition-colors text-lg">
                Cast Ballot
            </button>
        </div>
    </form>
</div>

<script>
function ballotForm() {
    return {
        handleCheckboxChange(group, max, event) {
            const checked = document.querySelectorAll(`input[data-group="${group}"]:checked`);
            if (checked.length > max) {
                event.target.checked = false;
                alert(`You can only select up to ${max} candidates for this position.`);
            }
        },
        resetForm() {
            if (confirm('Clear all current selections?')) {
                document.getElementById('ballot-form').reset();
            }
        },
        submitBallot(e) {
            if (confirm('Are you sure you want to cast this ballot? This action cannot be undone.')) {
                e.target.submit();
            }
        }
    }
}
</script>

<?php endif; ?>

<?php layout_footer(); ?>
