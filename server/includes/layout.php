<?php

/**
 * layout_header($title, $active)
 * Outputs the full <html><head> + sidebar. Call at the top of every page.
 *
 * layout_footer()
 * Closes the main tag and body/html. Call at the bottom of every page.
 */

function layout_header(string $title, string $active = ''): void
{
    require_auth();

    $nav = [
        'dashboard'  => ['label' => 'Dashboard',       'url' => ROOT_URL . '/index.php'],
        'positions'  => ['label' => 'Positions',        'url' => ROOT_URL . '/positions/index.php'],
        'parties'    => ['label' => 'Parties',          'url' => ROOT_URL . '/parties/index.php'],
        'candidates' => ['label' => 'Candidates',       'url' => ROOT_URL . '/candidates/index.php'],
        'ballot'     => ['label' => 'Ballots',          'url' => ROOT_URL . '/ballot/print.php'],
        'results'    => ['label' => 'Results',          'url' => ROOT_URL . '/results/index.php'],
        '3g_uploads' => ['label' => '3G Uploads',       'url' => ROOT_URL . '/results/3g_uploads.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — ACM Server</title>
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
<body class="bg-gray-50 min-h-screen flex text-sm text-gray-800">

<!-- Sidebar -->
<aside class="w-52 bg-navy flex flex-col min-h-screen fixed top-0 left-0 z-10">
    <div class="px-4 py-4 border-b border-navy-700">
        <p class="text-base font-semibold text-white leading-tight mt-0.5">Server Portal</p>
    </div>
    <nav class="flex-1 py-2 overflow-y-auto">
        <?php foreach ($nav as $key => $item): ?>
            <a href="<?= $item['url'] ?>" 
               class="flex items-center gap-3 px-4 py-2.5 mx-2 my-1 rounded-lg text-sm transition-all duration-200
                      <?= $active === $key ? 'bg-gold text-navy-800 font-semibold shadow-sm' : 'text-navy-50 hover:bg-navy-700 hover:text-white' ?>">
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="px-4 py-3 border-t border-navy-700 text-xs text-navy-100">
        <p class="truncate"><?= e($_SESSION['username'] ?? 'admin') ?></p>
        <a href="<?= ROOT_URL ?>/auth/logout.php" class="text-gold-400 hover:text-gold-100 transition-colors">Logout</a>
    </div>
</aside>

<!-- Content -->
<div class="ml-52 flex-1 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-5"><?= e($title) ?></h2>
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
