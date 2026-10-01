<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$stmt = $pdo->prepare("
    SELECT * FROM login_activity
    WHERE user_type = 'user' AND user_id = :uid
    ORDER BY created_at DESC
    LIMIT 30
");
$stmt->execute([':uid' => $userId]);
$logs = $stmt->fetchAll();

$pageTitle = 'Login Activity — Apex Gaming Platform';
$activeNav = 'security';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="/user/security.php" class="text-xs text-brand-400 hover:underline mb-1 inline-block">&larr; Back to Security</a>
            <h1 class="text-2xl font-black text-white">Login Activity</h1>
            <p class="text-xs text-slate-400 mt-1">Audit log of all authentication attempts to your account</p>
        </div>
    </div>

    <div class="glass-card p-6 rounded-3xl">
        <?php if (!empty($logs)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">IP Address</th>
                            <th class="pb-3 font-semibold">Device / Browser</th>
                            <th class="pb-3 font-semibold">Status</th>
                            <th class="pb-3 font-semibold text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono text-slate-200"><?= e($log['ip_address']) ?></td>
                                <td class="py-3 text-slate-400 max-w-xs truncate" title="<?= e($log['user_agent']) ?>">
                                    <?= e($log['user_agent'] ?: 'Direct API Client') ?>
                                </td>
                                <td class="py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold <?= $log['status'] === 'success' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' ?>">
                                        <?= strtoupper($log['status']) ?>
                                    </span>
                                    <?php if ($log['failure_reason']): ?>
                                        <span class="text-[10px] text-slate-500 ml-1">(<?= e($log['failure_reason']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-right text-slate-400 font-mono"><?= format_date($log['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-slate-500 text-xs">
                No previous login records found.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
