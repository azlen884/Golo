<?php
/**
 * Platform Installation Wizard
 * Apex Gaming Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$alreadyInstalled = is_system_installed();
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = null;
$success = null;

// Requirements check
$requirements = [
    'PHP Version >= 8.1'           => version_compare(PHP_VERSION, '8.1.0', '>='),
    'PDO Extension'                => extension_loaded('pdo'),
    'PDO MySQL Driver'             => extension_loaded('pdo_mysql'),
    'cURL Extension'               => extension_loaded('curl'),
    'Mbstring Extension'           => extension_loaded('mbstring'),
    'OpenSSL Extension'            => extension_loaded('openssl'),
    'Storage Directory Writable'   => is_writable(APP_ROOT . '/storage') || @mkdir(APP_ROOT . '/storage', 0777, true),
    'Uploads Directory Writable'   => is_writable(APP_ROOT . '/assets/uploads') || @mkdir(APP_ROOT . '/assets/uploads', 0777, true),
];

$allRequirementsPassed = !in_array(false, $requirements, true);

// Process installation action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyInstalled) {
    $action = $_POST['action'] ?? '';

    if ($action === 'install') {
        $adminUser = trim($_POST['admin_username'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass = $_POST['admin_password'] ?? '';
        $adminPassConf = $_POST['admin_password_confirmation'] ?? '';

        if (empty($adminUser) || empty($adminEmail) || empty($adminPass)) {
            $error = 'All administrator fields are required.';
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($adminPass) < 8) {
            $error = 'Admin password must be at least 8 characters long.';
        } elseif ($adminPass !== $adminPassConf) {
            $error = 'Password and confirmation do not match.';
        } else {
            try {
                $pdo = get_db();
                // 1. Run migrations
                $migrated = run_pending_migrations($pdo);

                // 2. Insert or update Admin account
                $hash = password_hash($adminPass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO admins (username, email, password_hash, full_name, is_super, status, created_at)
                    VALUES (:u, :e, :p, 'Super Administrator', 1, 'active', NOW())
                    ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), email = VALUES(email), status = 'active'
                ");
                $stmt->execute([
                    ':u' => $adminUser,
                    ':e' => $adminEmail,
                    ':p' => $hash
                ]);

                // 3. Lock installer
                mark_system_installed();
                $success = 'Platform installation completed successfully! You can now access your platform.';
                $alreadyInstalled = true;
            } catch (Throwable $e) {
                $error = 'Installation failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer - Apex Gaming Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-[#0a0d14] text-slate-100 min-h-screen flex flex-col justify-center items-center p-4 sm:p-8">

    <div class="w-full max-w-xl bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-10 shadow-2xl shadow-blue-950/20 backdrop-blur-xl">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-semibold uppercase tracking-wider mb-3">
                Setup Wizard
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center justify-center gap-2">
                <span class="text-blue-500">APEX</span> GAMING PLATFORM
            </h1>
            <p class="text-slate-400 text-sm mt-1">Enterprise Common Gaming Infrastructure</p>
        </div>

        <?php if ($alreadyInstalled): ?>
            <div class="text-center space-y-6">
                <div class="w-16 h-16 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-2xl flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white mb-2">Installation Locked</h2>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        The gaming platform has been successfully installed and the installation wizard is permanently locked for security.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 justify-center pt-2">
                    <a href="/index.php" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-medium text-sm transition-all text-center">
                        Landing Page
                    </a>
                    <a href="/admin/login.php" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-medium text-sm transition-all shadow-lg shadow-blue-600/30 text-center">
                        Admin Console &rarr;
                    </a>
                </div>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-sm">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- Prerequisites Checklist -->
            <div class="mb-8">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">System Prerequisites</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($requirements as $label => $passed): ?>
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-800/40 border border-slate-800 text-xs">
                            <span class="text-slate-300"><?= e($label) ?></span>
                            <?php if ($passed): ?>
                                <span class="text-emerald-400 font-semibold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    OK
                                </span>
                            <?php else: ?>
                                <span class="text-rose-400 font-semibold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Fail
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Install Form -->
            <form method="POST" action="/install/index.php" class="space-y-4">
                <input type="hidden" name="action" value="install">
                
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Create Super Administrator</h3>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Admin Username</label>
                    <input type="text" name="admin_username" value="admin" required
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Admin Email</label>
                    <input type="email" name="admin_email" value="admin@apexgame.com" required
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Password</label>
                        <input type="password" name="admin_password" value="admin123456" required
                               class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Confirm Password</label>
                        <input type="password" name="admin_password_confirmation" value="admin123456" required
                               class="w-full px-4 py-2.5 rounded-xl bg-slate-800/80 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                    </div>
                </div>

                <button type="submit" <?= !$allRequirementsPassed ? 'disabled' : '' ?>
                        class="w-full mt-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-semibold text-sm transition-all shadow-lg shadow-blue-600/30">
                    Run Migrations & Complete Installation
                </button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>
