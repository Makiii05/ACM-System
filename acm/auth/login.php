<?php
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ROOT_URL . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $stmt = getDB()->prepare('SELECT id, username, password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['pending_admin_id']  = $user['id'];
            $_SESSION['pending_admin_username'] = $user['username'];
            header('Location: verify_fingerprints.php');
            exit;
        }
        $error = 'Invalid admin credentials.';
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Login — ACM</title>
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
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center text-sm">
    <div class="bg-white border border-gray-200 rounded-xl shadow-lg w-full max-w-sm p-8">
        <div class="mb-6 text-center">
            <h1 class="text-xl font-bold text-gray-800 mt-1">Voting Device</h1>
        </div>

        <?php if ($error): ?>
        <div class="mb-5 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded text-center">
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label for="username" class="block text-xs font-medium text-gray-700 mb-1.5">Admin ID</label>
                <input type="text" name="username" id="username" required autocomplete="username"
                       value="<?= e($_POST['username'] ?? '') ?>"
                       class="w-full bg-white border border-gray-300 rounded px-3 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-navy focus:border-navy transition-shadow">
            </div>
            <div>
                <label for="password" class="block text-xs font-medium text-gray-700 mb-1.5">Passcode</label>
                <input type="password" name="password" id="password" required autocomplete="current-password"
                       class="w-full bg-white border border-gray-300 rounded px-3 py-2.5 text-gray-800 focus:outline-none focus:ring-2 focus:ring-navy focus:border-navy transition-shadow">
            </div>
            <button type="submit" id="login-btn"
                    class="w-full bg-navy text-white font-medium py-2.5 rounded hover:bg-navy-700 transition-colors shadow-md">
                Unlock Device
            </button>
        </form>
    </div>
</body>
</html>
