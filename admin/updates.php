<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

// Scan database/migrations/ for sql files
$migrationDir = __DIR__ . '/../database/migrations';
$diskMigrations = [];
if (is_dir($migrationDir)) {
    $files = scandir($migrationDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $diskMigrations[] = $file;
        }
    }
    sort($diskMigrations);
}

// Fetch applied migrations from database
$appliedMigrations = [];
try {
    $rows = $pdo->query("SELECT filename, executed_at, batch FROM system_migrations ORDER BY id ASC")->fetchAll();
    foreach ($rows as $r) {
        $appliedMigrations[$r['filename']] = $r;
    }
} catch (Throwable $e) {
    // Table might not exist yet
}

$pendingMigrations = [];
foreach ($diskMigrations as $file) {
    if (!isset($appliedMigrations[$file])) {
        $pendingMigrations[] = $file;
    }
}

// Handle migration run
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_or_abort();
    $action = $_POST['action'];

    if ($action === 'run_migrations') {
        if (empty($pendingMigrations)) {
            set_flash('info', 'All database migrations are already up to date.');
            redirect('/admin/updates.php');
        }

        $executed = 0;
        $errors = [];
        $batch = (int)$pdo->query("SELECT COALESCE(MAX(batch), 0) + 1 FROM system_migrations")->fetchColumn();

        foreach ($pendingMigrations as $file) {
            $path = $migrationDir . '/' . $file;
            $sql = file_get_contents($path);
            
            try {
                // Execute multi-query safely
                $pdo->beginTransaction();
                $pdo->exec($sql);
                
                $stmt = $pdo->prepare("INSERT INTO system_migrations (filename, version, batch) VALUES (?, ?, ?)");
                $stmt->execute([$file, '1.0.0', $batch]);
                
                $pdo->commit();
                $executed++;

                log_admin_activity($admin['id'], 'run_migration', 'system_migrations', null, "Applied migration: {$file}");
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = "Failed on {$file}: " . $e->getMessage();
                
                // Record in update_history
                $stmt = $pdo->prepare("
                    INSERT INTO update_history (previous_commit, new_commit, migration_status, update_status, error_details, admin_id)
                    VALUES (?, ?, 'failed', 'failed', ?, ?)
                ");
                $stmt->execute(['current', $file, $e->getMessage(), $admin['id']]);
                break;
            }
        }

        if (empty($errors)) {
            // Record successful batch
            $stmt = $pdo->prepare("
                INSERT INTO update_history (previous_commit, new_commit, migration_status, update_status, admin_id)
                VALUES ('v1.0.0', 'batch_' . ?, 'success', 'success', ?)
            ");
            $stmt->execute([$batch, $admin['id']]);

            set_flash('success', "Successfully executed {$executed} pending migration(s)!");
        } else {
            set_flash('error', implode("<br>", $errors));
        }

        redirect('/admin/updates.php');
    }
}

// Fetch update history
$updateHistory = $pdo->query("SELECT * FROM update_history ORDER BY id DESC LIMIT 20")->fetchAll();

// System health checks
$healthChecks = [
    'PHP Version' => [
        'value' => PHP_VERSION,
        'pass' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'note' => 'PHP 8.0+ required'
    ],
    'PDO MySQL Extension' => [
        'value' => extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing',
        'pass' => extension_loaded('pdo_mysql'),
        'note' => 'Required for database communication'
    ],
    'Storage Writable' => [
        'value' => is_writable(__DIR__ . '/../storage') ? 'Writable' : 'Read-only',
        'pass' => is_writable(__DIR__ . '/../storage'),
        'note' => 'Required for logs, backups, and caches'
    ],
    'Uploads Writable' => [
        'value' => is_writable(__DIR__ . '/../assets/uploads') ? 'Writable' : 'Read-only',
        'pass' => is_writable(__DIR__ . '/../assets/uploads'),
        'note' => 'Required for KYC, avatars, and attachments'
    ],
    'Database Connection' => [
        'value' => 'Connected to ' . DB_NAME,
        'pass' => true,
        'note' => 'Real-time PDO MySQL instance'
    ]
];

$pageTitle = 'System Updates & Migrations';
$activeAdminNav = 'updates';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">System Updates & Migrations</h1>
            <p class="text-sm text-gray-400 mt-1">Manage core schema migrations, integrity checks, and version history.</p>
        </div>
        <div class="flex gap-2">
            <?php if (!empty($pendingMigrations)): ?>
                <form method="POST" onsubmit="return confirm('Run <?= count($pendingMigrations) ?> pending database migrations now?');">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="run_migrations">
                    <button type="submit" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold rounded-lg shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Execute <?= count($pendingMigrations) ?> Pending Migration<?= count($pendingMigrations) > 1 ? 's' : '' ?>
                    </button>
                </form>
            <?php else: ?>
                <div class="px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-semibold rounded-lg flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Database Schema Up to Date
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- System Info & Health -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- System Integrity -->
        <div class="lg:col-span-1 bg-gray-900/60 border border-gray-800 rounded-xl p-5 shadow-xl space-y-4">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                System Integrity Checks
            </h2>
            <div class="space-y-3">
                <?php foreach ($healthChecks as $title => $check): ?>
                    <div class="p-3 bg-gray-800/40 rounded-lg border border-gray-800/80 flex justify-between items-center">
                        <div>
                            <div class="text-xs font-semibold text-white"><?= htmlspecialchars($title) ?></div>
                            <div class="text-[11px] text-gray-400"><?= htmlspecialchars($check['note']) ?></div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold <?= $check['pass'] ? 'text-emerald-400' : 'text-red-400' ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $check['pass'] ? 'bg-emerald-400' : 'bg-red-400' ?>"></span>
                                <?= htmlspecialchars($check['value']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Migrations Overview -->
        <div class="lg:col-span-2 bg-gray-900/60 border border-gray-800 rounded-xl overflow-hidden shadow-xl">
            <div class="p-5 border-b border-gray-800 flex justify-between items-center">
                <h2 class="text-base font-bold text-white">Database Migrations Pipeline</h2>
                <span class="text-xs text-gray-400"><?= count($appliedMigrations) ?> applied / <?= count($diskMigrations) ?> total on disk</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-800/50 text-xs font-semibold uppercase text-gray-400 border-b border-gray-800">
                        <tr>
                            <th class="px-5 py-3.5">Migration File</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Batch</th>
                            <th class="px-5 py-3.5 text-right">Executed At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60">
                        <?php foreach ($diskMigrations as $file): ?>
                            <?php $isApplied = isset($appliedMigrations[$file]); ?>
                            <tr class="hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3.5 font-mono text-xs text-white">
                                    <?= htmlspecialchars($file) ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <?php if ($isApplied): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            Applied
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20 animate-pulse">
                                            Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-gray-400">
                                    <?= $isApplied ? '#' . $appliedMigrations[$file]['batch'] : '-' ?>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-gray-400 text-right">
                                    <?= $isApplied ? date('M d, Y H:i', strtotime($appliedMigrations[$file]['executed_at'])) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Update History -->
    <div class="bg-gray-900/60 border border-gray-800 rounded-xl overflow-hidden shadow-xl">
        <div class="p-5 border-b border-gray-800 flex justify-between items-center">
            <h2 class="text-base font-bold text-white">Update & Deployment History</h2>
        </div>
        <?php if (empty($updateHistory)): ?>
            <div class="p-8 text-center text-sm text-gray-400">
                No update actions recorded in update_history yet.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-800/50 text-xs font-semibold uppercase text-gray-400 border-b border-gray-800">
                        <tr>
                            <th class="px-5 py-3.5">ID</th>
                            <th class="px-5 py-3.5">Reference / Batch</th>
                            <th class="px-5 py-3.5">Migration Status</th>
                            <th class="px-5 py-3.5">Overall Status</th>
                            <th class="px-5 py-3.5">Timestamp</th>
                            <th class="px-5 py-3.5">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60">
                        <?php foreach ($updateHistory as $h): ?>
                            <tr class="hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-3 text-xs text-gray-400">#<?= $h['id'] ?></td>
                                <td class="px-5 py-3 text-xs font-semibold text-white"><?= htmlspecialchars($h['new_commit'] ?? 'N/A') ?></td>
                                <td class="px-5 py-3 text-xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium <?= $h['migration_status'] === 'success' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' ?>">
                                        <?= strtoupper($h['migration_status']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium <?= $h['update_status'] === 'success' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400' ?>">
                                        <?= strtoupper($h['update_status']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-xs text-gray-400"><?= date('M d, Y H:i:s', strtotime($h['created_at'])) ?></td>
                                <td class="px-5 py-3 text-xs text-gray-400"><?= htmlspecialchars($h['error_details'] ?? 'Clean execution') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
