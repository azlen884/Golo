<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$token = $_GET['token'] ?? '';
$error = null;
$success = false;

$validSession = isset($_SESSION['pwd_reset_token']) &&
                hash_equals($_SESSION['pwd_reset_token'], $token) &&
                ($_SESSION['pwd_reset_expiry'] ?? 0) > time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    if (!$validSession) {
        $error = 'The password reset token is invalid or has expired.';
    } else {
        $newPass = $_POST['new_password'] ?? '';
        $newPassConf = $_POST['confirm_password'] ?? '';

        if (!validate_password($newPass)) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($newPass !== $newPassConf) {
            $error = 'Passwords do not match.';
        } else {
            $uid = (int)$_SESSION['pwd_reset_uid'];
            $hash = password_hash($newPass, PASSWORD_BCRYPT);

            $pdo = get_db();
            $up = $pdo->prepare("UPDATE users SET password_hash = :p, updated_at = NOW() WHERE id = :uid");
            $up->execute([':p' => $hash, ':uid' => $uid]);

            unset($_SESSION['pwd_reset_token'], $_SESSION['pwd_reset_uid'], $_SESSION['pwd_reset_expiry']);
            $success = true;
        }
    }
}

$pageTitle = 'Set New Password — Apex Gaming Platform';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto py-12">
    <div class="glass-card p-6 sm:p-8 rounded-3xl shadow-2xl relative overflow-hidden">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-black text-white">Create New Password</h1>
            <p class="text-slate-400 text-xs mt-1">Choose a strong, unique password for your account</p>
        </div>

        <?php if ($success): ?>
            <div class="p-6 rounded-2xl bg-emerald-950/70 border border-emerald-500/30 text-emerald-300 text-center space-y-4">
                <p class="text-sm font-semibold">Your password has been successfully updated!</p>
                <a href="/user/login.php" class="inline-block px-5 py-2.5 rounded-xl bg-brand-600 text-white font-bold text-xs">
                    Sign In Now &rarr;
                </a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!$validSession): ?>
                <div class="p-4 rounded-xl bg-amber-950/70 border border-amber-500/30 text-amber-300 text-xs text-center space-y-3">
                    <p>Invalid or expired reset token.</p>
                    <a href="/user/forgot-password.php" class="text-brand-400 font-semibold underline">Request a new reset link</a>
                </div>
            <?php else: ?>
                <form method="POST" action="/user/reset-password.php?token=<?= e($token) ?>" class="space-y-4">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">New Password</label>
                        <input type="password" name="new_password" required placeholder="Min 8 characters"
                               class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Confirm New Password</label>
                        <input type="password" name="confirm_password" required placeholder="Repeat new password"
                               class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                    </div>
                    <button type="submit" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs shadow-lg shadow-brand-600/30">
                        Update Password
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
