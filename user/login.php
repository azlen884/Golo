<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_user_logged_in()) {
    redirect('/user/dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    $res = attempt_user_login($identifier, $password);
    if ($res['success']) {
        $dest = $_SESSION['intended_url'] ?? '/user/dashboard.php';
        unset($_SESSION['intended_url']);
        set_flash('success', 'Welcome back to Apex Gaming!');
        redirect($dest);
    } else {
        $error = $res['message'];
    }
}

$pageTitle = 'Sign In — Apex Gaming Platform';
$activeNav = 'login';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto py-12">
    <div class="glass-card p-6 sm:p-8 rounded-3xl shadow-2xl relative overflow-hidden">
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-brand-600/10 border border-brand-500/20 text-brand-400 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
            </div>
            <h1 class="text-2xl font-black text-white">Player Sign In</h1>
            <p class="text-slate-400 text-xs mt-1">Access your account, balance and betting history</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs leading-relaxed animate-fadeIn">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/user/login.php" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Username or Email</label>
                <input type="text" name="identifier" required placeholder="player1 or player@example.com"
                       class="w-full px-4 py-3 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs placeholder:text-slate-500 focus:outline-none focus:border-brand-500 transition-colors">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-semibold text-slate-300">Password</label>
                    <a href="/user/forgot-password.php" class="text-[11px] text-brand-400 hover:text-brand-300">Forgot password?</a>
                </div>
                <input type="password" name="password" required placeholder="••••••••"
                       class="w-full px-4 py-3 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs placeholder:text-slate-500 focus:outline-none focus:border-brand-500 transition-colors">
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-brand-600/30 mt-2">
                Sign In to Platform
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-white/[0.06] text-center text-xs text-slate-400">
            Don't have an account? 
            <a href="/user/register.php" class="text-brand-400 hover:text-brand-300 font-semibold ml-1">Create Account &rarr;</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
