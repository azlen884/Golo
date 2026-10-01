<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/transactions.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$filterType = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT * FROM transactions WHERE user_id = :uid";
$params = [':uid' => $userId];

if (!empty($filterType)) {
    $sql .= " AND type = :type";
    $params[':type'] = $filterType;
}
if (!empty($filterStatus)) {
    $sql .= " AND status = :status";
    $params[':status'] = $filterStatus;
}
$sql .= " ORDER BY created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$pageTitle = 'Transactions History — Apex Gaming Platform';
$activeNav = 'wallet';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-white">Transaction History</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Full statement of all platform credits, debits, and settlements</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/user/deposit.php" class="px-4 py-2 rounded-xl bg-brand-600 text-white font-semibold text-xs">+ Deposit</a>
            <a href="/user/withdrawal.php" class="px-4 py-2 rounded-xl bg-dark-850 border border-white/[0.08] text-slate-200 text-xs">Withdraw</a>
        </div>
    </div>

    <!-- Filters Bar -->
    <form method="GET" action="/user/transactions.php" class="p-4 rounded-2xl bg-dark-900 border border-white/[0.06] flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[140px]">
            <select name="type" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none">
                <option value="">All Types</option>
                <option value="deposit" <?= $filterType === 'deposit' ? 'selected' : '' ?>>Deposits</option>
                <option value="withdrawal" <?= $filterType === 'withdrawal' ? 'selected' : '' ?>>Withdrawals</option>
                <option value="bet" <?= $filterType === 'bet' ? 'selected' : '' ?>>Bets</option>
                <option value="win" <?= $filterType === 'win' ? 'selected' : '' ?>>Winnings</option>
                <option value="bonus" <?= $filterType === 'bonus' ? 'selected' : '' ?>>Bonuses</option>
                <option value="referral" <?= $filterType === 'referral' ? 'selected' : '' ?>>Referrals</option>
            </select>
        </div>

        <div class="flex-1 min-w-[140px]">
            <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none">
                <option value="">All Statuses</option>
                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>

        <?php if (!empty($filterType) || !empty($filterStatus)): ?>
            <a href="/user/transactions.php" class="text-xs text-brand-400 hover:underline px-2">Reset Filter</a>
        <?php endif; ?>
    </form>

    <!-- Transactions Table -->
    <div class="glass-card p-6 rounded-3xl">
        <?php if (!empty($transactions)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Reference</th>
                            <th class="pb-3 font-semibold">Type</th>
                            <th class="pb-3 font-semibold">Gateway / Method</th>
                            <th class="pb-3 font-semibold">Amount</th>
                            <th class="pb-3 font-semibold">Fee</th>
                            <th class="pb-3 font-semibold">Net</th>
                            <th class="pb-3 font-semibold">Date</th>
                            <th class="pb-3 font-semibold text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($transactions as $t): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono font-medium text-slate-300">
                                    <a href="/user/transaction-details.php?ref=<?= e($t['transaction_ref']) ?>" class="hover:text-brand-400 underline decoration-dotted">
                                        <?= e($t['transaction_ref']) ?>
                                    </a>
                                </td>
                                <td class="py-3 capitalize text-slate-300"><?= e($t['type']) ?></td>
                                <td class="py-3 text-slate-400"><?= e($t['payment_method'] ?? 'Internal Ledger') ?></td>
                                <td class="py-3 font-mono font-bold text-white"><?= format_money($t['amount']) ?></td>
                                <td class="py-3 font-mono text-slate-500"><?= format_money($t['fee']) ?></td>
                                <td class="py-3 font-mono font-bold <?= $t['type'] === 'deposit' || $t['type'] === 'win' ? 'text-emerald-400' : 'text-slate-200' ?>">
                                    <?= format_money($t['net_amount']) ?>
                                </td>
                                <td class="py-3 text-slate-400 font-mono"><?= format_date($t['created_at']) ?></td>
                                <td class="py-3 text-right"><?= render_status_badge($t['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">
                No matching transactions found in your account history.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
