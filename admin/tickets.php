<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

$status = $_GET['status'] ?? '';
$sql = "SELECT t.*, u.username, u.email
        FROM support_tickets t
        JOIN users u ON u.id = t.user_id";
$params = [];
if (!empty($status)) {
    $sql .= " WHERE t.status = :st";
    $params[':st'] = $status;
}
$sql .= " ORDER BY t.last_reply_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$pageTitle = 'Support Tickets Desk';
$activeAdminNav = 'tickets';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-black text-white">Support Tickets Helpdesk</h1>
            <p class="text-xs text-slate-400 mt-1">Player tickets, questions, technical reports, and complaints</p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2">
        <a href="/admin/tickets.php" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= empty($status) ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            All Tickets
        </a>
        <a href="/admin/tickets.php?status=open" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'open' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Open
        </a>
        <a href="/admin/tickets.php?status=in_progress" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'in_progress' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            In Progress
        </a>
        <a href="/admin/tickets.php?status=answered" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'answered' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Answered
        </a>
        <a href="/admin/tickets.php?status=closed" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'closed' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Closed
        </a>
    </div>

    <div class="glass-card p-6 rounded-3xl">
        <?php if (!empty($tickets)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Ticket #</th>
                            <th class="pb-3 font-semibold">Player</th>
                            <th class="pb-3 font-semibold">Department</th>
                            <th class="pb-3 font-semibold">Subject</th>
                            <th class="pb-3 font-semibold">Priority</th>
                            <th class="pb-3 font-semibold">Last Reply</th>
                            <th class="pb-3 font-semibold">Status</th>
                            <th class="pb-3 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($tickets as $t): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono font-medium text-slate-200"><?= e($t['ticket_code']) ?></td>
                                <td class="py-3 font-medium text-white">
                                    <a href="/admin/user-details.php?id=<?= $t['user_id'] ?>" class="hover:text-brand-400">
                                        <?= e($t['username']) ?>
                                    </a>
                                </td>
                                <td class="py-3 text-slate-300"><?= e($t['department']) ?></td>
                                <td class="py-3 font-medium text-white max-w-xs truncate">
                                    <a href="/admin/ticket-details.php?id=<?= $t['id'] ?>" class="hover:text-brand-400">
                                        <?= e($t['subject']) ?>
                                    </a>
                                </td>
                                <td class="py-3 uppercase text-[10px] font-bold text-slate-400"><?= e($t['priority']) ?></td>
                                <td class="py-3 font-mono text-slate-400"><?= time_ago($t['last_reply_at']) ?></td>
                                <td class="py-3"><?= render_status_badge($t['status']) ?></td>
                                <td class="py-3 text-right">
                                    <a href="/admin/ticket-details.php?id=<?= $t['id'] ?>" class="px-3 py-1 rounded-lg bg-dark-800 hover:bg-dark-750 text-brand-400 text-xs font-semibold">
                                        Reply &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">
                No tickets matching this status.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
