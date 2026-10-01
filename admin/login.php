<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

if (is_admin_logged_in()) {
    redirect('/admin/dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    $res = attempt_admin_login($identifier, $password);
    if ($res['success']) {
        $dest = $_SESSION['admin_intended_url'] ?? '/admin/dashboard.php';
        unset($_SESSION['admin_intended_url']);
        set_flash('success', 'Authenticated as Administrator.');
        redirect($dest);
    } else {
        $error = $res['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Sign In — Apex Gaming Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-[#06080d] text-slate-200 min-h-screen flex items-center justify-center p-6 selection:bg-blue-600 selection:text-white">

    <div class="max-w-md w-full bg-slate-900/90 border border-slate-800 p-8 sm:p-10 rounded-3xl shadow-2xl backdrop-blur-xl relative overflow-hidden">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-blue-600/10 border border-blue-500/20 text-blue-400 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <h1 class="text-2xl font-black text-white">Administrator Console</h1>
            <p class="text-slate-400 text-xs mt-1">Authorized technical personnel only</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs leading-relaxed animate-fadeIn">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/login.php" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Admin Username or Email</label>
                <input type="text" name="identifier" required placeholder="admin or admin@apexgame.com"
                       class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs placeholder:text-slate-500 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold text-slate-300">Password</label>
                    <a href="/admin/forgot-password.php" class="text-[11px] text-blue-400 hover:text-blue-300">Forgot?</a>
                </div>
                <input type="password" name="password" required placeholder="••••••••"
                       class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs placeholder:text-slate-500 focus:outline-none focus:border-blue-500">
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-blue-600/30 mt-2">
                Authorize Session
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-800/80 text-center text-xs text-slate-500 space-y-3">
            <div class="p-2.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-300 text-[11px] font-mono">
                Admin: <strong class="text-white">admin</strong> &bull; Password: <strong class="text-white">admin123456</strong>
            </div>
            <div>
                <a href="/index.php" class="hover:text-slate-300 transition-colors">&larr; Return to Public Platform</a>
            </div>
        </div>
    </div>

</body>
</html>
