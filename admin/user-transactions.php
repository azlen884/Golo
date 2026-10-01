<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

$search = trim($_GET['search'] ?? '');
$sql = "SELECT l.*, u.username, u.email
        FROM wallet_ledger l
        JOIN users u ON u.id = l.user_id";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE u.username LIKE :s OR l.description LIKE :s OR l.reference_id LIKE :s";
    $params[':s'] = "%{$search}%";
}
$sql .= " ORDER BY l.id DESC LIMIT 60";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ledger = $stmt->fetchAll();

$pageTitle = 'Player Ledger Audit';
$activeAdminNav = 'user-transactions';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">User Ledger Audit</h1>
        <p class="text-xs text-slate-400 mt-1">Immutable double-entry balance log across all player actions</p>
    </div>

    <!-- Search Bar -->
    <form method="GET" action="/admin/user-transactions.php" class="p-4 rounded-2xl bg-dark-900 border border-white/[0.06] flex items-center gap-3">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by username, description, or reference..."
               class="flex-1 px-4 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none">
        <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 text-white font-semibold text-xs">Search Ledger</button>
    </form>

    <div class="glass-card p-6 rounded-3xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-white/[0.06]">
                        <th class="pb-3 font-semibold">Ledger ID</th>
                        <th class="pb-3 font-semibold">Player</th>
                        <th class="pb-3 font-semibold">Type</th>
                        <th class="pb-3 font-semibold">Amount</th>
                        <th class="pb-3 font-semibold">Balance Before</th>
                        <th class="pb-3 font-semibold">Balance After</th>
                        <th class="pb-3 font-semibold">Reference</th>
                        <th class="pb-3 font-semibold">Description</th>
                        <th class="pb-3 font-semibold text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    <?php foreach ($ledger as $row): ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="py-3 font-mono text-slate-400">#<?= $row['id'] ?></td>
                            <td class="py-3 font-medium text-white">
                                <a href="/admin/user-details.php?id=<?= $row['user_id'] ?>" class="hover:text-brand-400">
                                    <?= e($row['username']) ?>
                                </a>
                            </td>
                            <td class="py-3"><?= render_status_badge($row['transaction_type']) ?></td>
                            <td class="py-3 font-mono font-bold <?= (float)$row['amount'] > 0 ? 'text-emerald-400' : 'text-rose-400' ?>">
                                <?= (float)$row['amount'] > 0 ? '+' : '' ?><?= format_money($row['amount']) ?>
                            </td>
                            <td class="py-3 font-mono text-slate-400"><?= format_money($row['balance_before']) ?></td>
                            <td class="py-3 font-mono text-slate-200"><?= format_money($row['balance_after']) ?></td>
                            <td class="py-3 font-mono text-slate-400"><?= e($row['reference_id'] ?? '—') ?></td>
                            <td class="py-3 text-slate-300 max-w-xs truncate" title="<?= e($row['description']) ?>"><?= e($row['description']) ?></td>
                            <td class="py-3 text-right font-mono text-slate-400"><?= format_date($row['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
