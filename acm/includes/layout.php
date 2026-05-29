<?php
function layout_header(string $title, string $active = ''): void
{
    require_auth();

    $isConfigured = false;
    try {
        $isConfigured = (bool) getDB()->query('SELECT location FROM candidates LIMIT 1')->fetchColumn();
    } catch (PDOException $e) {}

    $nav = [
        'dashboard' => ['label' => 'Dashboard',     'url' => ROOT_URL . '/index.php', 'disabled' => false],
        'ballot'    => ['label' => 'Open Ballot',   'url' => ROOT_URL . '/ballot/form.php', 'disabled' => !$isConfigured],
        'results'   => ['label' => 'Local Result',  'url' => ROOT_URL . '/results/index.php', 'disabled' => false],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — ACM Device</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    navy:  { DEFAULT: '#004179', 50: '#e6eef6', 100: '#b3cce3', 600: '#004179', 700: '#003566', 800: '#002a52' },
                    gold:  { DEFAULT: '#f3c404', 50: '#fef9e7', 100: '#fdf0b8', 400: '#f3c404', 500: '#d9af03', 600: '#bfa003' },
                }
            }
        }
    }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="<?= rtrim(dirname(ROOT_URL), '/') ?>/table.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex text-sm text-gray-800">

<!-- Sidebar -->
<aside class="w-52 bg-navy flex flex-col min-h-screen fixed top-0 left-0 z-10">
    <div class="px-4 py-4 border-b border-navy-700">
        <p class="text-base font-semibold text-white leading-tight mt-0.5">Voting Device</p>
    </div>
    <nav class="flex-1 py-2 overflow-y-auto">
        <?php foreach ($nav as $key => $item): ?>
        <a href="<?= $item['disabled'] ? '#' : $item['url'] ?>"
           class="flex items-center gap-3 px-4 py-2.5 mx-2 my-1 rounded-lg text-sm transition-all duration-200
                  <?= $item['disabled'] ? 'opacity-50 cursor-not-allowed text-navy-100' : ($active === $key ? 'bg-gold text-navy-800 font-semibold shadow-sm' : 'text-navy-50 hover:bg-navy-700 hover:text-white') ?>">
            <span><?= e($item['label']) ?></span>
            <?php if ($item['disabled']): ?>
            <span class="ml-auto text-[10px] uppercase tracking-widest text-navy-100">Locked</span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="px-4 py-3 border-t border-navy-700 text-xs text-navy-100">
        <p class="truncate mb-1">User: <?= e($_SESSION['username'] ?? 'admin') ?></p>
        <a href="<?= ROOT_URL ?>/auth/logout.php" class="text-gold-400 hover:text-gold-100 transition-colors">Logout</a>
    </div>
</aside>

<!-- Content -->
<div class="ml-52 flex-1 p-8">
    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
        <span class="w-1.5 h-6 bg-gold rounded-full inline-block"></span>
        <?= e($title) ?>
    </h2>
    <?php
}

function layout_footer(): void
{
    ?>
</div>
</body>
</html>
    <?php
}
