<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

$sql = "SELECT l.*, 
               CASE 
                   WHEN l.user_type = 'user' THEN (SELECT username FROM users WHERE id = l.user_id)
                   WHEN l.user_type = 'admin' THEN (SELECT username FROM admins WHERE id = l.user_id)
                   ELSE 'Unknown'
               END as account_username
        FROM login_activity l
        ORDER BY l.id DESC LIMIT 60";
$logs = $pdo->query($sql)->fetchAll();

$pageTitle = 'Login Activity Logs';
$activeAdminNav = 'login-logs';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Authentication Attempts Log</h1>
        <p class="text-xs text-slate-400 mt-1">Cross-system log of player and staff login authorizations</p>
    </div>

    <div class="glass-card p-6 rounded-3xl">
        <?php if (!empty($logs)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Account Type</th>
                            <th class="pb-3 font-semibold">Username</th>
                            <th class="pb-3 font-semibold">IP Address</th>
                            <th class="pb-3 font-semibold">Device / User Agent</th>
                            <th class="pb-3 font-semibold">Result</th>
                            <th class="pb-3 font-semibold text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 uppercase font-mono font-bold <?= $log['user_type'] === 'admin' ? 'text-brand-400' : 'text-slate-400' ?>">
                                    <?= e($log['user_type']) ?>
                                </td>
                                <td class="py-3 font-bold text-white"><?= e($log['account_username'] ?? 'User #' . $log['user_id']) ?></td>
                                <td class="py-3 font-mono text-slate-300"><?= e($log['ip_address']) ?></td>
                                <td class="py-3 text-slate-400 max-w-xs truncate" title="<?= e($log['user_agent']) ?>"><?= e($log['user_agent'] ?: '—') ?></td>
                                <td class="py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $log['status'] === 'success' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' ?>">
                                        <?= strtoupper($log['status']) ?>
                                    </span>
                                    <?php if ($log['failure_reason']): ?>
                                        <span class="text-[10px] text-slate-500 ml-1"><?= e($log['failure_reason']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-right font-mono text-slate-400"><?= format_date($log['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">No login events recorded yet.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
