<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$filterStatus = $_GET['status'] ?? '';
$sql = "SELECT b.*, r.round_code, r.status as round_status
        FROM bets b
        JOIN rounds r ON r.id = b.round_id
        WHERE b.user_id = :uid";
$params = [':uid' => $userId];

if (!empty($filterStatus)) {
    $sql .= " AND b.status = :st";
    $params[':st'] = $filterStatus;
}
$sql .= " ORDER BY b.placed_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bets = $stmt->fetchAll();

$pageTitle = 'My Wagers — Apex Gaming Platform';
$activeNav = 'my-bets';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-white">My Wagers</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Review all your active and settled round wagers</p>
        </div>
        <div>
            <a href="/user/game-history.php" class="px-4 py-2 rounded-xl bg-brand-600 text-white font-semibold text-xs">
                Explore Active Rounds &rarr;
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2">
        <a href="/user/my-bets.php" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= empty($filterStatus) ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            All Wagers
        </a>
        <a href="/user/my-bets.php?status=pending" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $filterStatus === 'pending' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Pending
        </a>
        <a href="/user/my-bets.php?status=won" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $filterStatus === 'won' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Won
        </a>
        <a href="/user/my-bets.php?status=lost" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $filterStatus === 'lost' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Lost
        </a>
    </div>

    <!-- Bets Table -->
    <div class="glass-card p-6 rounded-3xl">
        <?php if (!empty($bets)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Bet Identifier</th>
                            <th class="pb-3 font-semibold">Round</th>
                            <th class="pb-3 font-semibold">Wager Amount</th>
                            <th class="pb-3 font-semibold">Target Odds</th>
                            <th class="pb-3 font-semibold">Potential Win</th>
                            <th class="pb-3 font-semibold">Settled Payout</th>
                            <th class="pb-3 font-semibold">Date Placed</th>
                            <th class="pb-3 font-semibold text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($bets as $b): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono font-medium text-slate-200">
                                    <a href="/user/bet-details.php?code=<?= e($b['bet_code']) ?>" class="hover:text-brand-400 underline decoration-dotted">
                                        <?= e($b['bet_code']) ?>
                                    </a>
                                </td>
                                <td class="py-3 font-mono text-slate-300"><?= e($b['round_code']) ?></td>
                                <td class="py-3 font-mono font-bold text-white"><?= format_money($b['amount']) ?></td>
                                <td class="py-3 font-mono text-cyan-400"><?= (float)$b['odds'] ?>x</td>
                                <td class="py-3 font-mono text-slate-300"><?= format_money($b['potential_payout']) ?></td>
                                <td class="py-3 font-mono font-bold <?= (float)$b['actual_payout'] > 0 ? 'text-emerald-400' : 'text-slate-500' ?>">
                                    <?= (float)$b['actual_payout'] > 0 ? format_money($b['actual_payout']) : '0.00' ?>
                                </td>
                                <td class="py-3 text-slate-400 font-mono"><?= format_date($b['placed_at']) ?></td>
                                <td class="py-3 text-right"><?= render_status_badge($b['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">
                No wagers matching this criteria. <a href="/user/game-history.php" class="text-brand-400 hover:underline">Explore active rounds &rarr;</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
