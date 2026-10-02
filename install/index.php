<?php
/**
 * Platform Installation Wizard
 * Apex Gaming Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$alreadyInstalled = is_system_installed();
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

// Retain submitted values
$formData = [
    'db_host' => $_POST['db_host'] ?? (defined('DB_HOST') ? DB_HOST : '127.0.0.1'),
    'db_port' => $_POST['db_port'] ?? (defined('DB_PORT') ? DB_PORT : '3306'),
    'db_name' => $_POST['db_name'] ?? (defined('DB_NAME') ? DB_NAME : 'gaming_platform'),
    'db_user' => $_POST['db_user'] ?? (defined('DB_USER') ? DB_USER : 'gaming_user'),
    'db_pass' => $_POST['db_pass'] ?? (defined('DB_PASS') ? DB_PASS : 'gaming_secure_pass_2026'),
    'admin_username' => $_POST['admin_username'] ?? 'admin',
    'admin_email' => $_POST['admin_email'] ?? 'admin@apexgame.com',
];

// Process installation action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyInstalled) {
    $action = $_POST['action'] ?? '';

    if ($action === 'install') {
        // Database credentials
        $dbHost = trim($_POST['db_host'] ?? '');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';

        // Administrator details
        $adminUser = trim($_POST['admin_username'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass = $_POST['admin_password'] ?? '';
        $adminPassConf = $_POST['admin_password_confirmation'] ?? '';

        // 1. Validate fields
        if (empty($dbHost)) {
            $error = 'Database Host is required.';
        } elseif (empty($dbPort) || !is_numeric($dbPort)) {
            $error = 'Valid Database Port is required (default: 3306).';
        } elseif (empty($dbName)) {
            $error = 'Database Name is required.';
        } elseif (empty($dbUser)) {
            $error = 'Database Username is required.';
        } elseif (empty($adminUser) || empty($adminEmail) || empty($adminPass)) {
            $error = 'All administrator fields are required.';
        } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid administrator email address.';
        } elseif (strlen($adminPass) < 8) {
            $error = 'Admin password must be at least 8 characters long.';
        } elseif ($adminPass !== $adminPassConf) {
            $error = 'Admin password and confirmation do not match.';
        } else {
            try {
                // 2. Connect to MySQL using the entered credentials
                $serverDsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
                try {
                    $serverPdo = new PDO($serverDsn, $dbUser, $dbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5
                    ]);
                } catch (PDOException $e) {
                    throw new Exception("Cannot connect to MySQL server at {$dbHost}:{$dbPort}. Error: " . $e->getMessage());
                }

                // Ensure target database exists (with fallback if user lacks CREATE DATABASE privilege)
                try {
                    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                } catch (Throwable $dbEx) {
                    // On cPanel/shared hosting, users typically create the database via cPanel beforehand.
                }

                // Connect to the specific database with foreign key checks temporarily disabled
                $dbDsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                $pdo = new PDO($dbDsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci; SET FOREIGN_KEY_CHECKS = 0;"
                ]);

                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

                // Save configured database credentials to config/db_custom.php
                $dbConfigFile = __DIR__ . '/../config/db_custom.php';
                $dbConfigContent = "<?php\n" .
                    "// Database configuration generated by installer on " . date('Y-m-d H:i:s') . "\n" .
                    "define('DB_HOST', " . var_export($dbHost, true) . ");\n" .
                    "define('DB_PORT', " . var_export((string)$dbPort, true) . ");\n" .
                    "define('DB_NAME', " . var_export($dbName, true) . ");\n" .
                    "define('DB_USER', " . var_export($dbUser, true) . ");\n" .
                    "define('DB_PASS', " . var_export($dbPass, true) . ");\n" .
                    "define('DB_CHARSET', 'utf8mb4');\n";
                file_put_contents($dbConfigFile, $dbConfigContent);

                // 3. Create/import the required database tables/migrations
                $migrated = run_pending_migrations($pdo);

                // Re-enable foreign key checks after migrations
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

                // 4. Create the initial admin account
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

                // 5. Complete the installation normally
                mark_system_installed();
                $success = 'Platform installation completed successfully! Database connected, migrations executed, and administrator account created.';
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

    <div class="w-full max-w-2xl bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-10 shadow-2xl shadow-blue-950/20 backdrop-blur-xl">
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
                    <h2 class="text-xl font-bold text-white mb-2">Installation Complete</h2>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        <?= $success ? e($success) : 'The gaming platform has been successfully installed and the installation wizard is permanently locked for security.' ?>
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
                <div class="mb-6 p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-sm flex items-start gap-3">
                    <svg class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div><?= e($error) ?></div>
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
            <form method="POST" action="/install/index.php" class="space-y-6">
                <input type="hidden" name="action" value="install">
                
                <!-- Section 1: Database Configuration -->
                <div class="space-y-3.5 p-4 sm:p-5 rounded-2xl bg-slate-800/40 border border-slate-800">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M4 7c0-2 1-3 3-3h10c2 0 3 1 3 3M4 7h16m-8 4v6m0 0l-3-3m3 3l3-3"></path></svg>
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Database Configuration</h3>
                    </div>
                    <p class="text-slate-400 text-xs">Enter your MySQL database credentials. The installer will test the connection and import tables.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-slate-300 mb-1">Database Host <span class="text-rose-400">*</span></label>
                            <input type="text" name="db_host" value="<?= htmlspecialchars($formData['db_host']) ?>" required placeholder="127.0.0.1 or localhost"
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Port <span class="text-rose-400">*</span></label>
                            <input type="number" name="db_port" value="<?= htmlspecialchars($formData['db_port']) ?>" required placeholder="3306"
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Database Name <span class="text-rose-400">*</span></label>
                        <input type="text" name="db_name" value="<?= htmlspecialchars($formData['db_name']) ?>" required placeholder="gaming_platform"
                               class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Database Username <span class="text-rose-400">*</span></label>
                            <input type="text" name="db_user" value="<?= htmlspecialchars($formData['db_user']) ?>" required placeholder="gaming_user or root"
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Database Password</label>
                            <input type="password" name="db_pass" value="<?= htmlspecialchars($formData['db_pass']) ?>" placeholder="Database password"
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Super Administrator Account -->
                <div class="space-y-3.5 p-4 sm:p-5 rounded-2xl bg-slate-800/40 border border-slate-800">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Super Administrator Account</h3>
                    </div>
                    <p class="text-slate-400 text-xs">Create your initial administrator account to access the admin console.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Admin Username <span class="text-rose-400">*</span></label>
                            <input type="text" name="admin_username" value="<?= htmlspecialchars($formData['admin_username']) ?>" required placeholder="admin"
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Admin Email <span class="text-rose-400">*</span></label>
                            <input type="email" name="admin_email" value="<?= htmlspecialchars($formData['admin_email']) ?>" required placeholder="admin@apexgame.com"
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Admin Password <span class="text-rose-400">*</span></label>
                            <input type="password" name="admin_password" value="admin123456" required placeholder="Min 8 characters"
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Confirm Password <span class="text-rose-400">*</span></label>
                            <input type="password" name="admin_password_confirmation" value="admin123456" required placeholder="Re-enter password"
                                   class="w-full px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <button type="submit" <?= !$allRequirementsPassed ? 'disabled' : '' ?>
                        class="w-full py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white font-bold text-sm uppercase tracking-wider transition-all shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Connect Database & Install Platform
                </button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>
