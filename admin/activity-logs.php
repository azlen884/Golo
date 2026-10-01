<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

$sql = "SELECT a.*, adm.username as admin_name
        FROM admin_activity a
        JOIN admins adm ON adm.id = a.admin_id
        ORDER BY a.id DESC LIMIT 60";
$logs = $pdo->query($sql)->fetchAll();

$pageTitle = 'Admin Audit & Activity Logs';
$activeAdminNav = 'activity-logs';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Staff Activity Audit Trail</h1>
        <p class="text-xs text-slate-400 mt-1">Immutable forensic log of administrative actions, balance adjustments, and configuration changes</p>
    </div>

    <div class="glass-card p-6 rounded-3xl">
        <?php if (!empty($logs)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Admin</th>
                            <th class="pb-3 font-semibold">Action</th>
                            <th class="pb-3 font-semibold">Target</th>
                            <th class="pb-3 font-semibold">Details</th>
                            <th class="pb-3 font-semibold">IP Address</th>
                            <th class="pb-3 font-semibold text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-bold text-white"><?= e($log['admin_name']) ?></td>
                                <td class="py-3">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-brand-500/10 text-brand-400 border border-brand-500/20">
                                        <?= e($log['action']) ?>
                                    </span>
                                </td>
                                <td class="py-3 font-mono text-slate-400">
                                    <?= e($log['target_type'] ?? '') ?> <?= $log['target_id'] ? '#' . e($log['target_id']) : '' ?>
                                </td>
                                <td class="py-3 text-slate-300 max-w-sm truncate" title="<?= e($log['details']) ?>"><?= e($log['details']) ?></td>
                                <td class="py-3 font-mono text-slate-400"><?= e($log['ip_address']) ?></td>
                                <td class="py-3 text-right font-mono text-slate-400"><?= format_date($log['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">No admin activity logged yet.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
