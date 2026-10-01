<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "SELECT b.*, u.username, r.round_code
        FROM bets b
        JOIN users u ON u.id = b.user_id
        JOIN rounds r ON r.id = b.round_id";
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(u.username LIKE :s OR b.bet_code LIKE :s OR r.round_code LIKE :s)";
    $params[':s'] = "%{$search}%";
}
if (!empty($status)) {
    $where[] = "b.status = :st";
    $params[':st'] = $status;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY b.id DESC LIMIT 60";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bets = $stmt->fetchAll();

$pageTitle = 'Player Bets Overview';
$activeAdminNav = 'user-bets';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Player Wagers</h1>
        <p class="text-xs text-slate-400 mt-1">Cross-platform overview of all user bets, stakes, odds, and settlement states</p>
    </div>

    <!-- Search & Filter Bar -->
    <form method="GET" action="/admin/user-bets.php" class="p-4 rounded-2xl bg-dark-900 border border-white/[0.06] flex items-center gap-3">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search user, bet code, or round code..."
               class="flex-1 px-4 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none">
        <select name="status" class="px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none">
            <option value="">All Statuses</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="won" <?= $status === 'won' ? 'selected' : '' ?>>Won</option>
            <option value="lost" <?= $status === 'lost' ? 'selected' : '' ?>>Lost</option>
            <option value="refunded" <?= $status === 'refunded' ? 'selected' : '' ?>>Refunded</option>
        </select>
        <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 text-white font-semibold text-xs">Filter</button>
    </form>

    <div class="glass-card p-6 rounded-3xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-white/[0.06]">
                        <th class="pb-3 font-semibold">Bet Code</th>
                        <th class="pb-3 font-semibold">Player</th>
                        <th class="pb-3 font-semibold">Round</th>
                        <th class="pb-3 font-semibold">Wager Amount</th>
                        <th class="pb-3 font-semibold">Odds</th>
                        <th class="pb-3 font-semibold">Potential</th>
                        <th class="pb-3 font-semibold">Actual Payout</th>
                        <th class="pb-3 font-semibold">Placed Date</th>
                        <th class="pb-3 font-semibold text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    <?php foreach ($bets as $b): ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="py-3 font-mono font-medium text-slate-200"><?= e($b['bet_code']) ?></td>
                            <td class="py-3 font-medium text-white">
                                <a href="/admin/user-details.php?id=<?= $b['user_id'] ?>" class="hover:text-brand-400">
                                    <?= e($b['username']) ?>
                                </a>
                            </td>
                            <td class="py-3 font-mono text-slate-400"><?= e($b['round_code']) ?></td>
                            <td class="py-3 font-mono font-bold text-white"><?= format_money($b['amount']) ?></td>
                            <td class="py-3 font-mono text-cyan-400"><?= (float)$b['odds'] ?>x</td>
                            <td class="py-3 font-mono text-slate-300"><?= format_money($b['potential_payout']) ?></td>
                            <td class="py-3 font-mono font-bold text-emerald-400"><?= format_money($b['actual_payout']) ?></td>
                            <td class="py-3 text-slate-400 font-mono"><?= format_date($b['placed_at']) ?></td>
                            <td class="py-3 text-right"><?= render_status_badge($b['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
