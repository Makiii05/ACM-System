<?php
require_once __DIR__ . '/../db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['pending_admin_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDB();
$fingerprints = $pdo->query('SELECT * FROM fingerprints ORDER BY id ASC LIMIT 2')->fetchAll();

if (count($fingerprints) < 2) {
    // If not enough fingerprints are registered, just log them in or redirect them back to login.
    // For this flow, we'll allow them in and show a warning, or force them to register.
    // Let's redirect them to register if < 2.
    header('Location: register_fingerprints.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_login') {
    // Both fingerprints verified on frontend
    $_SESSION['user_id'] = $_SESSION['pending_admin_id'];
    $_SESSION['username'] = $_SESSION['pending_admin_username'];
    unset($_SESSION['pending_admin_id']);
    unset($_SESSION['pending_admin_username']);
    
    header('Location: ' . ROOT_URL . '/index.php');
    exit;
}

// Convert credential IDs to pass to frontend
$cred1 = $fingerprints[0];
$cred2 = $fingerprints[1];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Fingerprints — ACM</title>
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
    <style>
        .step-active { border-color: #004179; background-color: #e6eef6; }
        .step-done { border-color: #10b981; background-color: #ecfdf5; }
        .step-locked { border-color: #e5e7eb; background-color: #f9fafb; opacity: 0.6; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center text-sm">
    <div class="bg-white border border-gray-200 rounded-xl shadow-lg w-full max-w-md p-8">
        <div class="mb-6 text-center">
            <h1 class="text-xl font-bold text-gray-800 mt-1">Officer Authentication</h1>
            <p class="text-xs text-gray-500 mt-2">Two authorized personnel must verify their fingerprints to unlock the device.</p>
        </div>

        <div class="space-y-4 mb-6">
            <!-- Step 1 -->
            <div id="step1" class="border-2 rounded-lg p-4 step-active transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-800">1. <?= e($cred1['owner_name']) ?></h3>
                        <p class="text-xs text-gray-500 mt-0.5" id="status1">Awaiting scan...</p>
                    </div>
                    <button id="scan-btn-1" class="bg-navy text-white px-3 py-1.5 rounded text-xs hover:bg-navy-700 transition">Scan</button>
                </div>
            </div>

            <!-- Step 2 -->
            <div id="step2" class="border-2 rounded-lg p-4 step-locked transition-all">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-800">2. <?= e($cred2['owner_name']) ?></h3>
                        <p class="text-xs text-gray-500 mt-0.5" id="status2">Locked</p>
                    </div>
                    <button id="scan-btn-2" class="bg-gray-400 text-white px-3 py-1.5 rounded text-xs cursor-not-allowed" disabled>Scan</button>
                </div>
            </div>
        </div>

        <form id="verify-form" method="POST">
            <input type="hidden" name="action" value="complete_login">
        </form>

        <div class="text-center mt-4">
            <a href="login.php" class="text-xs text-red-500 hover:underline">Cancel & Return to Login</a>
        </div>
    </div>

    <script>
    function base64urlToBuffer(base64url) {
        const padding = '='.repeat((4 - base64url.length % 4) % 4);
        const base64 = (base64url + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray.buffer;
    }

    const cred1_id = "<?= e($cred1['credential_id']) ?>";
    const cred2_id = "<?= e($cred2['credential_id']) ?>";

    async function verifyFingerprint(credIdString, btnId, statusId, stepId, nextStepId, nextBtnId) {
        if (!window.PublicKeyCredential) {
            alert("WebAuthn is not supported.");
            return false;
        }

        const challenge = new Uint8Array(32);
        window.crypto.getRandomValues(challenge);

        const credIdBuffer = base64urlToBuffer(credIdString);

        const publicKey = {
            challenge: challenge,
            allowCredentials: [{
                type: "public-key",
                id: credIdBuffer,
                transports: ["internal"]
            }],
            userVerification: "required",
            timeout: 60000
        };

        try {
            document.getElementById(statusId).innerText = "Prompting scanner...";
            const assertion = await navigator.credentials.get({ publicKey });
            
            // Success
            document.getElementById(statusId).innerText = "Verified \u2713";
            document.getElementById(statusId).className = "text-xs text-green-600 mt-0.5 font-bold";
            document.getElementById(stepId).className = "border-2 rounded-lg p-4 step-done transition-all";
            document.getElementById(btnId).style.display = "none";

            if (nextStepId) {
                document.getElementById(nextStepId).className = "border-2 rounded-lg p-4 step-active transition-all";
                const nBtn = document.getElementById(nextBtnId);
                nBtn.disabled = false;
                nBtn.className = "bg-navy text-white px-3 py-1.5 rounded text-xs hover:bg-navy-700 transition";
                document.getElementById("status2").innerText = "Awaiting scan...";
            }

            return true;
        } catch (err) {
            console.error(err);
            document.getElementById(statusId).innerText = "Scan failed. Try again.";
            alert("Verification failed: " + err.message);
            return false;
        }
    }

    document.getElementById('scan-btn-1').addEventListener('click', async () => {
        const ok = await verifyFingerprint(cred1_id, 'scan-btn-1', 'status1', 'step1', 'step2', 'scan-btn-2');
    });

    document.getElementById('scan-btn-2').addEventListener('click', async () => {
        const ok = await verifyFingerprint(cred2_id, 'scan-btn-2', 'status2', 'step2', null, null);
        if (ok) {
            // Both verified, submit form to login
            setTimeout(() => {
                document.getElementById('verify-form').submit();
            }, 1000);
        }
    });
    </script>
</body>
</html>
