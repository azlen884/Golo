<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

$backupDir = __DIR__ . '/../storage/backups';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
}

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_or_abort();

    $action = $_POST['action'];

    if ($action === 'create_backup') {
        $type = in_array($_POST['type'] ?? '', ['database', 'files', 'full']) ? $_POST['type'] : 'database';
        $timestamp = date('Ymd_His');
        $success = false;
        $filename = '';
        $filesize = 0;

        try {
            if ($type === 'database') {
                $filename = "db_backup_{$timestamp}.sql";
                $filePath = $backupDir . '/' . $filename;
                
                // Real mysqldump
                $cmd = sprintf(
                    'mysqldump -u %s -p%s -h %s %s > %s 2>&1',
                    escapeshellarg(DB_USER),
                    escapeshellarg(DB_PASS),
                    escapeshellarg(DB_HOST),
                    escapeshellarg(DB_NAME),
                    escapeshellarg($filePath)
                );
                exec($cmd, $output, $returnCode);

                if ($returnCode === 0 && file_exists($filePath)) {
                    $filesize = filesize($filePath);
                    $success = true;
                } else {
                    $errorMsg = implode("\n", $output);
                    throw new Exception("Database backup failed: " . $errorMsg);
                }
            } elseif ($type === 'files') {
                $filename = "files_backup_{$timestamp}.tar.gz";
                $filePath = $backupDir . '/' . $filename;
                $projectRoot = dirname(__DIR__);

                // Real tar backup excluding storage/backups and node_modules
                $cmd = sprintf(
                    'tar --exclude="storage/backups" --exclude="node_modules" -czf %s -C %s . 2>&1',
                    escapeshellarg($filePath),
                    escapeshellarg($projectRoot)
                );
                exec($cmd, $output, $returnCode);

                if ($returnCode === 0 && file_exists($filePath)) {
                    $filesize = filesize($filePath);
                    $success = true;
                } else {
                    throw new Exception("File backup failed: " . implode("\n", $output));
                }
            } elseif ($type === 'full') {
                $filename = "full_backup_{$timestamp}.tar.gz";
                $dbDump = $backupDir . "/temp_db_{$timestamp}.sql";
                $filePath = $backupDir . '/' . $filename;
                $projectRoot = dirname(__DIR__);

                // Dump DB first
                $dumpCmd = sprintf(
                    'mysqldump -u %s -p%s -h %s %s > %s 2>&1',
                    escapeshellarg(DB_USER),
                    escapeshellarg(DB_PASS),
                    escapeshellarg(DB_HOST),
                    escapeshellarg(DB_NAME),
                    escapeshellarg($dbDump)
                );
                exec($dumpCmd, $dumpOutput, $dumpCode);

                if ($dumpCode === 0) {
                    $tarCmd = sprintf(
                        'tar --exclude="storage/backups" --exclude="node_modules" -czf %s -C %s . -C %s %s 2>&1',
                        escapeshellarg($filePath),
                        escapeshellarg($projectRoot),
                        escapeshellarg($backupDir),
                        escapeshellarg("temp_db_{$timestamp}.sql")
                    );
                    exec($tarCmd, $tarOutput, $tarCode);
                    @unlink($dbDump);

                    if ($tarCode === 0 && file_exists($filePath)) {
                        $filesize = filesize($filePath);
                        $success = true;
                    } else {
                        throw new Exception("Full backup archive creation failed");
                    }
                } else {
                    throw new Exception("Full backup DB dump failed");
                }
            }

            if ($success) {
                $stmt = $pdo->prepare("
                    INSERT INTO backups (backup_name, filename, file_size, backup_type, created_by, status)
                    VALUES (?, ?, ?, ?, ?, 'completed')
                ");
                $stmt->execute([
                    ucfirst($type) . ' Backup ' . date('M d, Y H:i:s'),
                    $filename,
                    $filesize,
                    $type,
                    $admin['username']
                ]);

                log_admin_activity($admin['id'], 'create_backup', 'backups', $pdo->lastInsertId(), "Created {$type} backup: {$filename}");
                set_flash('success', "Backup ({$type}) created successfully! File: {$filename} (" . round($filesize / 1024, 1) . " KB)");
            }
        } catch (Throwable $e) {
            set_flash('error', "Backup failed: " . $e->getMessage());
        }

        redirect('/admin/backups.php');
    }

    if ($action === 'delete_backup') {
        $id = (int)($_POST['backup_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM backups WHERE id = ?");
        $stmt->execute([$id]);
        $backup = $stmt->fetch();

        if ($backup) {
            $filePath = $backupDir . '/' . $backup['filename'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $pdo->prepare("DELETE FROM backups WHERE id = ?")->execute([$id]);
            log_admin_activity($admin['id'], 'delete_backup', 'backups', $id, "Deleted backup: {$backup['filename']}");
            set_flash('success', "Backup deleted successfully.");
        } else {
            set_flash('error', "Backup not found.");
        }

        redirect('/admin/backups.php');
    }
}

// Download handler
if (isset($_GET['download'])) {
    $id = (int)$_GET['download'];
    $stmt = $pdo->prepare("SELECT * FROM backups WHERE id = ?");
    $stmt->execute([$id]);
    $backup = $stmt->fetch();

    if ($backup) {
        $filePath = $backupDir . '/' . basename($backup['filename']);
        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
    set_flash('error', "File not found on disk.");
    redirect('/admin/backups.php');
}

$backups = $pdo->query("SELECT * FROM backups ORDER BY id DESC")->fetchAll();

$pageTitle = 'Database & System Backups';
$activeAdminNav = 'backups';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">System & Database Backups</h1>
            <p class="text-sm text-gray-400 mt-1">Create, download, and manage real database snapshots and project archives.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="document.getElementById('backupModal').classList.remove('hidden')" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold rounded-lg shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create New Backup
            </button>
        </div>
    </div>

    <!-- Backup Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-gray-900/60 border border-gray-800 rounded-xl p-5">
            <div class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Total Backups</div>
            <div class="text-2xl font-black text-white mt-1"><?= count($backups) ?></div>
            <div class="text-xs text-gray-500 mt-1">Stored securely in /storage/backups</div>
        </div>
        <div class="bg-gray-900/60 border border-gray-800 rounded-xl p-5">
            <div class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Storage Path</div>
            <div class="text-sm font-mono text-blue-400 mt-1 truncate">/storage/backups/</div>
            <div class="text-xs text-emerald-400 mt-1 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Writable & Accessible
            </div>
        </div>
        <div class="bg-gray-900/60 border border-gray-800 rounded-xl p-5">
            <div class="text-gray-400 text-xs font-semibold uppercase tracking-wider">MySQL Database</div>
            <div class="text-lg font-bold text-white mt-1"><?= htmlspecialchars(DB_NAME) ?></div>
            <div class="text-xs text-gray-400 mt-1">Host: <?= htmlspecialchars(DB_HOST) ?></div>
        </div>
    </div>

    <!-- Backups Table -->
    <div class="bg-gray-900/60 border border-gray-800 rounded-xl overflow-hidden shadow-xl">
        <div class="p-5 border-b border-gray-800 flex justify-between items-center">
            <h2 class="text-base font-bold text-white">Backup Archives</h2>
        </div>
        <?php if (empty($backups)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-800/80 rounded-2xl flex items-center justify-center mx-auto text-gray-500 mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M4 7c0-2 1-3 3-3h10c2 0 3 1 3 3M4 7h16m-8 4v6m0 0l-3-3m3 3l3-3"/></svg>
                </div>
                <h3 class="text-base font-semibold text-white">No backups generated yet</h3>
                <p class="text-xs text-gray-400 max-w-sm mx-auto mt-1">Click the button above to generate a full database or file backup of the platform.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-800/50 text-xs font-semibold uppercase text-gray-400 border-b border-gray-800">
                        <tr>
                            <th class="px-5 py-3.5">Backup Name & Filename</th>
                            <th class="px-5 py-3.5">Type</th>
                            <th class="px-5 py-3.5">Size</th>
                            <th class="px-5 py-3.5">Created By</th>
                            <th class="px-5 py-3.5">Date Created</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/60">
                        <?php foreach ($backups as $b): ?>
                            <tr class="hover:bg-gray-800/30 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-white"><?= htmlspecialchars($b['backup_name']) ?></div>
                                    <div class="font-mono text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($b['filename']) ?></div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?= $b['backup_type'] === 'database' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 
                                           ($b['backup_type'] === 'full' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20') ?>">
                                        <?= strtoupper($b['backup_type']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-300 font-mono text-xs">
                                    <?= number_format($b['file_size'] / 1024, 1) ?> KB
                                </td>
                                <td class="px-5 py-4 text-gray-400 text-xs">
                                    <?= htmlspecialchars($b['created_by']) ?>
                                </td>
                                <td class="px-5 py-4 text-gray-400 text-xs">
                                    <?= date('M d, Y H:i:s', strtotime($b['created_at'])) ?>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="/admin/backups.php?download=<?= $b['id'] ?>" class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-blue-400 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Download
                                        </a>
                                        <form method="POST" onsubmit="return confirm('Permanently delete this backup file?');" class="inline">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="delete_backup">
                                            <input type="hidden" name="backup_id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="px-3 py-1.5 bg-gray-800 hover:bg-red-900/40 text-red-400 text-xs font-medium rounded-lg transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Create Backup -->
<div id="backupModal" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-gray-900 border border-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-bold text-white">Create New Backup</h3>
            <button onclick="document.getElementById('backupModal').classList.add('hidden')" class="text-gray-400 hover:text-white">✕</button>
        </div>
        <form method="POST" class="space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="create_backup">

            <div>
                <label class="block text-xs font-semibold text-gray-300 uppercase tracking-wider mb-2">Select Backup Scope</label>
                <div class="space-y-2">
                    <label class="flex items-start gap-3 p-3 bg-gray-800/40 border border-gray-700/60 rounded-xl cursor-pointer hover:border-blue-500 transition-colors">
                        <input type="radio" name="type" value="database" checked class="mt-1 text-blue-600 focus:ring-blue-500">
                        <div>
                            <div class="text-sm font-semibold text-white">MySQL Database Only</div>
                            <div class="text-xs text-gray-400">Exports all tables, structure, users, ledger, and settings. Fast and lightweight.</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-3 bg-gray-800/40 border border-gray-700/60 rounded-xl cursor-pointer hover:border-blue-500 transition-colors">
                        <input type="radio" name="type" value="files" class="mt-1 text-blue-600 focus:ring-blue-500">
                        <div>
                            <div class="text-sm font-semibold text-white">Application Files Only</div>
                            <div class="text-xs text-gray-400">Archives all PHP, CSS, JS, and config files into a compressed tar.gz.</div>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-3 bg-gray-800/40 border border-gray-700/60 rounded-xl cursor-pointer hover:border-blue-500 transition-colors">
                        <input type="radio" name="type" value="full" class="mt-1 text-blue-600 focus:ring-blue-500">
                        <div>
                            <div class="text-sm font-semibold text-white">Complete Platform (Database + Files)</div>
                            <div class="text-xs text-gray-400">Full disaster recovery snapshot including database and source files.</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('backupModal').classList.add('hidden')" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-semibold rounded-lg transition-colors">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold rounded-lg shadow-lg shadow-blue-500/20 transition-all">Start Backup</button>
            </div>
        </form>
    </div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

