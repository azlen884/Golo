<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $email = trim($_POST['email'] ?? '');
    log_security_event('admin_pwd_reset_request', null, "Admin password reset requested for {$email}", 'medium');
    $sent = true;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Recovery — Apex Gaming Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-[#06080d] text-slate-200 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-slate-900 border border-slate-800 p-8 rounded-3xl text-center space-y-4">
        <h1 class="text-xl font-bold text-white">Administrator Recovery</h1>
        <?php if ($sent): ?>
            <p class="text-xs text-slate-400">If an administrator account exists with that address, an audit notification has been generated.</p>
        <?php else: ?>
            <p class="text-xs text-slate-400">For security reasons, root administrators can reset passwords directly via server CLI or through existing active super-admin sessions.</p>
            <form method="POST" action="/admin/forgot-password.php" class="space-y-4">
                <?= csrf_field() ?>
                <input type="email" name="email" required placeholder="admin@apexgame.com"
                       class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs">
                <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-600 text-white font-semibold text-xs">
                    Dispatch Recovery Notice
                </button>
            </form>
        <?php endif; ?>
        <div class="pt-4 border-t border-slate-800">
            <a href="/admin/login.php" class="text-xs text-blue-400">&larr; Return to Admin Sign In</a>
        </div>
    </div>
</body>
</html>
