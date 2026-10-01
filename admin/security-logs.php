<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

$sql = "SELECT * FROM security_logs ORDER BY id DESC LIMIT 60";
$logs = $pdo->query($sql)->fetchAll();

$pageTitle = 'Security Audit Logs';
$activeAdminNav = 'security-logs';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Security & Threat Logs</h1>
        <p class="text-xs text-slate-400 mt-1">Real-time detection of brute-force attempts, CSRF mismatches, and rate limit triggers</p>
    </div>

    <div class="glass-card p-6 rounded-3xl">
        <?php if (!empty($logs)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Event Type</th>
                            <th class="pb-3 font-semibold">Severity</th>
                            <th class="pb-3 font-semibold">IP Address</th>
                            <th class="pb-3 font-semibold">Request URI</th>
                            <th class="pb-3 font-semibold">Audit Payload</th>
                            <th class="pb-3 font-semibold text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono font-medium text-slate-200"><?= e($log['event_type']) ?></td>
                                <td class="py-3">
                                    <?php
                                    $sevColors = [
                                        'low'      => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                                        'medium'   => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                        'high'     => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                        'critical' => 'bg-rose-600/30 text-rose-300 border-rose-500/40 animate-pulse',
                                    ];
                                    $sClass = $sevColors[$log['severity']] ?? $sevColors['low'];
                                    ?>
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?= $sClass ?>">
                                        <?= e($log['severity']) ?>
                                    </span>
                                </td>
                                <td class="py-3 font-mono text-slate-300"><?= e($log['ip_address']) ?></td>
                                <td class="py-3 font-mono text-slate-400 max-w-xs truncate"><?= e($log['request_uri']) ?></td>
                                <td class="py-3 text-slate-300 max-w-xs truncate" title="<?= e($log['payload']) ?>"><?= e($log['payload'] ?: '—') ?></td>
                                <td class="py-3 text-right font-mono text-slate-400"><?= format_date($log['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">No security events triggered.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
