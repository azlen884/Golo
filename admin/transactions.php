<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "SELECT t.*, u.username, u.email
        FROM transactions t
        JOIN users u ON u.id = t.user_id";
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(u.username LIKE :s OR t.transaction_ref LIKE :s)";
    $params[':s'] = "%{$search}%";
}
if (!empty($type)) {
    $where[] = "t.type = :tp";
    $params[':tp'] = $type;
}
if (!empty($status)) {
    $where[] = "t.status = :st";
    $params[':st'] = $status;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY t.id DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$pageTitle = 'Transactions Master Log';
$activeAdminNav = 'transactions';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">All Platform Transactions</h1>
        <p class="text-xs text-slate-400 mt-1">Audit trail of all deposits, withdrawals, wagers, winnings, and adjustments</p>
    </div>

    <!-- Filters -->
    <form method="GET" action="/admin/transactions.php" class="p-4 rounded-2xl bg-dark-900 border border-white/[0.06] flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search user or transaction reference..."
               class="flex-1 min-w-[200px] px-4 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none">
        
        <select name="type" class="px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none">
            <option value="">All Types</option>
            <option value="deposit" <?= $type === 'deposit' ? 'selected' : '' ?>>Deposits</option>
            <option value="withdrawal" <?= $type === 'withdrawal' ? 'selected' : '' ?>>Withdrawals</option>
            <option value="bet" <?= $type === 'bet' ? 'selected' : '' ?>>Bets</option>
            <option value="win" <?= $type === 'win' ? 'selected' : '' ?>>Winnings</option>
            <option value="adjustment" <?= $type === 'adjustment' ? 'selected' : '' ?>>Adjustments</option>
        </select>

        <select name="status" class="px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none">
            <option value="">All Statuses</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>

        <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 text-white font-semibold text-xs">Filter</button>
    </form>

    <div class="glass-card p-6 rounded-3xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-white/[0.06]">
                        <th class="pb-3 font-semibold">Ref Code</th>
                        <th class="pb-3 font-semibold">Player</th>
                        <th class="pb-3 font-semibold">Type</th>
                        <th class="pb-3 font-semibold">Channel</th>
                        <th class="pb-3 font-semibold">Gross</th>
                        <th class="pb-3 font-semibold">Fee</th>
                        <th class="pb-3 font-semibold">Net</th>
                        <th class="pb-3 font-semibold">Date</th>
                        <th class="pb-3 font-semibold text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    <?php foreach ($transactions as $t): ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="py-3 font-mono font-medium text-slate-200"><?= e($t['transaction_ref']) ?></td>
                            <td class="py-3 font-medium text-white">
                                <a href="/admin/user-details.php?id=<?= $t['user_id'] ?>" class="hover:text-brand-400">
                                    <?= e($t['username']) ?>
                                </a>
                            </td>
                            <td class="py-3 capitalize text-slate-300"><?= e($t['type']) ?></td>
                            <td class="py-3 text-slate-400"><?= e($t['payment_method'] ?? 'Internal Ledger') ?></td>
                            <td class="py-3 font-mono font-bold text-white"><?= format_money($t['amount']) ?></td>
                            <td class="py-3 font-mono text-slate-500"><?= format_money($t['fee']) ?></td>
                            <td class="py-3 font-mono font-bold <?= $t['type'] === 'deposit' || $t['type'] === 'win' ? 'text-emerald-400' : 'text-slate-200' ?>">
                                <?= format_money($t['net_amount']) ?>
                            </td>
                            <td class="py-3 font-mono text-slate-400"><?= format_date($t['created_at']) ?></td>
                            <td class="py-3 text-right"><?= render_status_badge($t['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
