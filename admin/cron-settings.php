<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/cron.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_cron') {
    verify_csrf_or_abort();

    $startTime = microtime(true);
    $lockKey = 'master_cron_execution_lock';

    if (!acquire_cron_lock($lockKey, 300)) {
        set_flash('error', 'Cron execution locked: Another automated process is currently executing.');
    } else {
        $taskResults = [];
        $overallStatus = 'success';
        $errorMessage = null;

        try {
            $taskResults['rounds'] = cron_automate_rounds();
            $taskResults['settlements'] = cron_process_settlements();
            $taskResults['cleanup'] = cron_system_cleanup();
            set_setting('cron_last_run', date('Y-m-d H:i:s'), 'cron');
        } catch (Throwable $e) {
            $overallStatus = 'failed';
            $errorMessage = $e->getMessage();
        } finally {
            release_cron_lock($lockKey);
        }

        $durationMs = (int)round((microtime(true) - $startTime) * 1000);
        log_cron_execution('master_cron_admin_trigger', $overallStatus, $durationMs, json_encode($taskResults), $errorMessage);
        log_admin_activity($admin['id'], 'trigger_master_cron', 'cron', null, "Manual execution {$overallStatus} ({$durationMs}ms)");

        if ($overallStatus === 'success') {
            set_flash('success', "Master cron ran successfully in {$durationMs}ms! Rounds & settlements updated.");
        } else {
            set_flash('error', "Master cron failed: {$errorMessage}");
        }
    }
    redirect('/admin/cron-settings.php');
}

$lastRun = get_setting('cron_last_run');
$logs = $pdo->query("SELECT * FROM cron_logs ORDER BY id DESC LIMIT 30")->fetchAll();
$activeLocks = $pdo->query("SELECT * FROM cron_locks WHERE expires_at >= NOW()")->fetchAll();

$pageTitle = 'Master Cron & Scheduler Configuration';
$activeAdminNav = 'cron-settings';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Master Cron Infrastructure</h1>
            <p class="text-xs text-slate-400 mt-1">Autonomous scheduled tasks with distributed mutex locking and idempotency</p>
        </div>
        <form method="POST" action="/admin/cron-settings.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="run_cron">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Execute Master Cron Now
            </button>
        </form>
    </div>

    <!-- Instructions & Single File Path Requirement Card -->
    <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-4">
        <div class="flex flex-col md:flex-row justify-between gap-6">
            <div class="space-y-2">
                <span class="text-xs font-bold text-brand-400 uppercase tracking-wider">Single Master Cron Entry</span>
                <h3 class="text-base font-bold text-white">Centralized Automation Engine</h3>
                <p class="text-xs text-slate-400 leading-relaxed max-w-xl">
                    All scheduled automation is handled exclusively through one single master file: <code class="px-2 py-0.5 rounded bg-dark-950 text-brand-400 font-mono text-[11px]">/cron/master.php</code>.
                    Locking is enforced via the database table <code class="px-2 py-0.5 rounded bg-dark-950 text-slate-300 font-mono text-[11px]">cron_locks</code> to eliminate race conditions.
                </p>
            </div>
            <div class="space-y-1 text-right">
                <span class="text-xs text-slate-500 block">Last Execution Timestamp</span>
                <span class="text-base font-mono font-bold text-emerald-400"><?= format_date($lastRun, 'Y-m-d H:i:s') ?></span>
                <div class="text-[11px] text-slate-500"><?= time_ago($lastRun) ?></div>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-dark-950 border border-white/[0.06] space-y-2">
            <div class="text-xs font-semibold text-slate-300">Server Crontab Setup Command:</div>
            <pre class="p-3 rounded-xl bg-black border border-white/[0.08] text-xs font-mono text-emerald-400 select-all">* * * * * php <?= APP_ROOT ?>/cron/master.php >> /dev/null 2>&1</pre>
        </div>
    </div>

    <!-- Active Distributed Locks -->
    <?php if (!empty($activeLocks)): ?>
        <div class="p-4 rounded-2xl bg-amber-950/40 border border-amber-500/30 text-amber-300 text-xs">
            <strong>Active Concurrency Locks:</strong>
            <?php foreach ($activeLocks as $al): ?>
                <span class="font-mono ml-2">[<?= e($al['lock_key']) ?> expires in <?= strtotime($al['expires_at']) - time() ?>s]</span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Cron Execution History Logs -->
    <div class="glass-card p-6 rounded-3xl">
        <h3 class="text-sm font-bold text-white mb-4">Cron Execution Audit Log</h3>
        <?php if (!empty($logs)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Log ID</th>
                            <th class="pb-3 font-semibold">Task Name</th>
                            <th class="pb-3 font-semibold">Duration</th>
                            <th class="pb-3 font-semibold">Output Summary</th>
                            <th class="pb-3 font-semibold">Status</th>
                            <th class="pb-3 font-semibold text-right">Executed At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($logs as $l): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono text-slate-500">#<?= $l['id'] ?></td>
                                <td class="py-3 font-mono font-medium text-white"><?= e($l['task_name']) ?></td>
                                <td class="py-3 font-mono text-slate-300"><?= $l['execution_time_ms'] ?> ms</td>
                                <td class="py-3 text-slate-400 max-w-sm truncate" title="<?= e($l['output_summary']) ?>">
                                    <?= e($l['output_summary'] ?: '—') ?>
                                </td>
                                <td class="py-3"><?= render_status_badge($l['status']) ?></td>
                                <td class="py-3 text-right font-mono text-slate-400"><?= format_date($l['executed_at'], 'H:i:s') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">No cron logs recorded yet.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
