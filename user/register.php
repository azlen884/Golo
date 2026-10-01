<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_user_logged_in()) {
    redirect('/user/dashboard.php');
}

$refCode = $_GET['ref'] ?? '';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirmation'] ?? '';
    $referral = trim($_POST['referral_code'] ?? '');

    if ($password !== $passwordConfirm) {
        $error = 'Password and confirmation password do not match.';
    } else {
        $res = register_user($username, $email, $password, $referral);
        if ($res['success']) {
            set_flash('success', 'Registration complete! Your player wallet is ready.');
            redirect('/user/dashboard.php');
        } else {
            $error = $res['message'];
        }
    }
}

$pageTitle = 'Register Player — Apex Gaming Platform';
$activeNav = 'register';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto py-10">
    <div class="glass-card p-6 sm:p-8 rounded-3xl shadow-2xl relative overflow-hidden">
        <div class="text-center mb-6">
            <div class="w-12 h-12 rounded-2xl bg-brand-600/10 border border-brand-500/20 text-brand-400 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            </div>
            <h1 class="text-2xl font-black text-white">Join Apex Gaming</h1>
            <p class="text-slate-400 text-xs mt-1">Instant wallet provisioning & provably fair rounds</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs leading-relaxed animate-fadeIn">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/user/register.php" class="space-y-3.5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Username</label>
                <input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>" placeholder="alphanumeric, e.g. player77"
                       class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs placeholder:text-slate-500 focus:outline-none focus:border-brand-500 transition-colors">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Email Address</label>
                <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@domain.com"
                       class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs placeholder:text-slate-500 focus:outline-none focus:border-brand-500 transition-colors">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Password</label>
                    <input type="password" name="password" required placeholder="Min 8 chars"
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs placeholder:text-slate-500 focus:outline-none focus:border-brand-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" required placeholder="Repeat password"
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs placeholder:text-slate-500 focus:outline-none focus:border-brand-500 transition-colors">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Referral Code <span class="text-slate-500 font-normal">(Optional)</span></label>
                <input type="text" name="referral_code" value="<?= e($refCode ?: ($_POST['referral_code'] ?? '')) ?>" placeholder="e.g. A9B8C7"
                       class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs placeholder:text-slate-500 focus:outline-none focus:border-brand-500 uppercase transition-colors">
            </div>

            <div class="pt-2 text-[11px] text-slate-400">
                By registering, you confirm you are at least 18 years of age and agree to the 
                <a href="/pages/terms.php" target="_blank" class="text-brand-400 hover:underline">Terms of Service</a>.
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-brand-600/30">
                Register & Initialize Wallet
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-white/[0.06] text-center text-xs text-slate-400">
            Already registered? 
            <a href="/user/login.php" class="text-brand-400 hover:text-brand-300 font-semibold ml-1">Sign In &rarr;</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
