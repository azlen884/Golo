<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$admin = get_auth_admin();
$adminId = (int)$admin['id'];
$pdo = get_db();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $newPassConf = $_POST['new_password_confirmation'] ?? '';

    // Fetch existing hash
    $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = :id");
    $stmt->execute([':id' => $adminId]);
    $existingHash = $stmt->fetchColumn();

    if (!password_verify($currentPass, $existingHash)) {
        $error = 'Current admin password incorrect.';
    } elseif (strlen($newPass) < 8) {
        $error = 'New password must be at least 8 characters long.';
    } elseif ($newPass !== $newPassConf) {
        $error = 'New password and confirmation do not match.';
    } else {
        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        $up = $pdo->prepare("UPDATE admins SET password_hash = :p, updated_at = NOW() WHERE id = :id");
        $up->execute([':p' => $newHash, ':id' => $adminId]);

        log_admin_activity($adminId, 'change_password', 'admins', (string)$adminId, 'Admin updated credentials');
        set_flash('success', 'Administrator password updated successfully.');
        redirect('/admin/dashboard.php');
    }
}

$pageTitle = 'Update Administrator Password';
$activeAdminNav = 'settings';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-xl mx-auto space-y-6">
    <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
        <div>
            <h1 class="text-xl font-bold text-white">Change Admin Password</h1>
            <p class="text-xs text-slate-400 mt-1">Update your administrative credential securely</p>
        </div>

        <?php if ($error): ?>
            <div class="p-3 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/change-password.php" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Current Password</label>
                <input type="password" name="current_password" required placeholder="••••••••"
                       class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">New Password</label>
                <input type="password" name="new_password" required placeholder="Min 8 characters"
                       class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Confirm New Password</label>
                <input type="password" name="new_password_confirmation" required placeholder="Confirm new password"
                       class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-brand-600/30">
                Update Admin Credentials
            </button>
        </form>
    </div>
</div>

</body>
</html>
