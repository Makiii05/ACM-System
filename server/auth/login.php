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
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: ' . ROOT_URL . '/index.php');
            exit;
        }
        $error = 'Invalid username or password.';
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
    <title>Login — ACM Server</title>
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
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm w-full max-w-sm p-8">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-800 mt-1 text-center">Server Portal</h1>
        </div>

        <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded">
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label for="username" class="block text-xs font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" id="username" required autocomplete="username"
                       value="<?= e($_POST['username'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
            </div>
            <div>
                <label for="password" class="block text-xs font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" id="password" required autocomplete="current-password"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
            </div>
            <button type="submit" id="login-btn"
                    class="w-full bg-navy text-white py-2 rounded text-sm hover:bg-navy-700 transition-colors">
                Login
            </button>
        </form>
    </div>
</body>
</html>
