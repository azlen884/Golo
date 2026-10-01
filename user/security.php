<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $newPassConf = $_POST['new_password_confirmation'] ?? '';

        if (!password_verify($currentPass, $user['password_hash'])) {
            $error = 'The current password you provided is incorrect.';
        } elseif (!validate_password($newPass)) {
            $error = 'New password must be at least 8 characters long.';
        } elseif ($newPass !== $newPassConf) {
            $error = 'New password and confirmation do not match.';
        } else {
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $up = $pdo->prepare("UPDATE users SET password_hash = :p, updated_at = NOW() WHERE id = :uid");
            $up->execute([':p' => $newHash, ':uid' => $userId]);

            log_security_event('password_change', $userId, 'User updated account password', 'medium');
            send_notification($userId, 'Security Alert: Password Changed', 'Your account password was updated successfully.', 'warning', '/user/security.php');

            set_flash('success', 'Your password has been securely updated.');
            redirect('/user/security.php');
        }
    } elseif ($action === 'toggle_2fa') {
        $currentState = (int)$user['two_factor_enabled'];
        $newState = $currentState ? 0 : 1;

        $up = $pdo->prepare("UPDATE users SET two_factor_enabled = :st, updated_at = NOW() WHERE id = :uid");
        $up->execute([':st' => $newState, ':uid' => $userId]);

        log_security_event('2fa_toggled', $userId, "2FA status changed to {$newState}", 'medium');
        set_flash('success', 'Two-factor authentication preference saved.');
        redirect('/user/security.php');
    }
}

$pageTitle = 'Security & 2FA — Apex Gaming Platform';
$activeNav = 'security';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-white">Account Security</h1>
            <p class="text-xs text-slate-400 mt-1">Credentials, two-factor authentication, and login activity</p>
        </div>
        <a href="/user/login-activity.php" class="px-3 py-1.5 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.08] text-xs font-semibold text-slate-300">
            Login Logs &rarr;
        </a>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Password Change Card -->
    <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
        <div>
            <h3 class="text-base font-bold text-white">Update Password</h3>
            <p class="text-xs text-slate-400 mt-0.5">Ensure you choose a strong password containing letters and numbers</p>
        </div>

        <form method="POST" action="/user/security.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Current Password</label>
                <input type="password" name="current_password" required placeholder="••••••••"
                       class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">New Password</label>
                    <input type="password" name="new_password" required placeholder="Min 8 characters"
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" required placeholder="Confirm new password"
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-brand-600/30">
                Update Password
            </button>
        </form>
    </div>

    <!-- 2FA Settings Card -->
    <div class="glass-card p-6 sm:p-8 rounded-3xl flex items-center justify-between gap-6">
        <div>
            <h3 class="text-base font-bold text-white">Two-Factor Authentication (2FA)</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-md">
                Add an extra layer of protection to prevent unauthorized withdrawals and account access.
            </p>
        </div>

        <form method="POST" action="/user/security.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle_2fa">
            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all <?= $user['two_factor_enabled'] ? 'bg-rose-600/20 text-rose-300 hover:bg-rose-600/30' : 'bg-emerald-600 text-white hover:bg-emerald-500 shadow-lg shadow-emerald-600/20' ?>">
                <?= $user['two_factor_enabled'] ? 'Disable 2FA' : 'Enable 2FA' ?>
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
