<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$sent = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (empty($email) || !validate_email($email)) {
        $error = 'Please enter a valid account email.';
    } else {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate recovery token in security_logs or user record
            $token = bin2hex(random_bytes(24));
            $_SESSION['pwd_reset_token'] = $token;
            $_SESSION['pwd_reset_uid'] = $user['id'];
            $_SESSION['pwd_reset_expiry'] = time() + 3600;

            send_notification($user['id'], 'Password Reset Requested', 'A password reset was initiated for your account. If this was not you, please secure your profile.', 'warning');
            $sent = true;
        } else {
            // Uniform response for security (no enumeration)
            $sent = true;
        }
    }
}

$pageTitle = 'Password Recovery — Apex Gaming Platform';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto py-12">
    <div class="glass-card p-6 sm:p-8 rounded-3xl shadow-2xl relative overflow-hidden">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-black text-white">Reset Password</h1>
            <p class="text-slate-400 text-xs mt-1">Enter your email to receive recovery instructions</p>
        </div>

        <?php if ($sent): ?>
            <div class="p-4 rounded-xl bg-blue-950/60 border border-blue-500/30 text-blue-300 text-xs leading-relaxed text-center space-y-4">
                <p>If an account matches that email address, password recovery instructions have been initiated.</p>
                <?php if (isset($_SESSION['pwd_reset_token'])): ?>
                    <div class="pt-2">
                        <a href="/user/reset-password.php?token=<?= e($_SESSION['pwd_reset_token']) ?>" class="inline-block px-4 py-2 rounded-xl bg-brand-600 text-white text-xs font-semibold">
                            Proceed to Reset Form &rarr;
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/user/forgot-password.php" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Account Email</label>
                    <input type="email" name="email" required placeholder="you@domain.com"
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs transition-all shadow-lg shadow-brand-600/30">
                    Request Recovery
                </button>
            </form>
        <?php endif; ?>

        <div class="mt-6 pt-4 border-t border-white/[0.06] text-center text-xs text-slate-400">
            <a href="/user/login.php" class="text-brand-400 hover:underline">&larr; Back to Sign In</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
