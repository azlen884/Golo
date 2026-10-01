<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE user_id = :uid ORDER BY last_reply_at DESC");
$stmt->execute([':uid' => $userId]);
$tickets = $stmt->fetchAll();

$pageTitle = 'Support Helpdesk — Apex Gaming Platform';
$activeNav = 'support';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-white">Support Helpdesk</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Direct communication with our technical and compliance team</p>
        </div>
        <a href="/user/create-ticket.php" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all">
            + Open New Ticket
        </a>
    </div>

    <div class="glass-card p-6 rounded-3xl">
        <?php if (!empty($tickets)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Ticket #</th>
                            <th class="pb-3 font-semibold">Department</th>
                            <th class="pb-3 font-semibold">Subject</th>
                            <th class="pb-3 font-semibold">Priority</th>
                            <th class="pb-3 font-semibold">Last Reply</th>
                            <th class="pb-3 font-semibold text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($tickets as $t): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono font-medium text-slate-200">
                                    <a href="/user/ticket.php?id=<?= $t['id'] ?>" class="hover:text-brand-400 underline decoration-dotted">
                                        <?= e($t['ticket_code']) ?>
                                    </a>
                                </td>
                                <td class="py-3 text-slate-300"><?= e($t['department']) ?></td>
                                <td class="py-3 font-medium text-white max-w-xs truncate">
                                    <a href="/user/ticket.php?id=<?= $t['id'] ?>" class="hover:text-brand-400">
                                        <?= e($t['subject']) ?>
                                    </a>
                                </td>
                                <td class="py-3 uppercase text-[10px] font-bold text-slate-400"><?= e($t['priority']) ?></td>
                                <td class="py-3 text-slate-400 font-mono"><?= time_ago($t['last_reply_at']) ?></td>
                                <td class="py-3 text-right"><?= render_status_badge($t['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">
                You have no active support tickets. Need assistance? 
                <a href="/user/create-ticket.php" class="text-brand-400 hover:underline">Open a ticket now &rarr;</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
