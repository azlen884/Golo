<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/wallet.php';

require_admin();
$pdo = get_db();

$search = trim($_GET['search'] ?? '');
$sql = "SELECT w.*, u.username, u.email, u.status as user_status
        FROM wallets w
        JOIN users u ON u.id = w.user_id";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE u.username LIKE :s OR u.email LIKE :s";
    $params[':s'] = "%{$search}%";
}
$sql .= " ORDER BY w.balance DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$wallets = $stmt->fetchAll();

$pageTitle = 'Player Wallets';
$activeAdminNav = 'user-wallet';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-black text-white">Player Wallets</h1>
            <p class="text-xs text-slate-400 mt-1">Review live liquid balances, bonus credits, and lifetime wagering records</p>
        </div>
    </div>

    <!-- Search Bar -->
    <form method="GET" action="/admin/user-wallet.php" class="p-4 rounded-2xl bg-dark-900 border border-white/[0.06] flex items-center gap-3">
        <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search user by username or email..."
               class="flex-1 px-4 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none">
        <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 text-white font-semibold text-xs">Search</button>
    </form>

    <!-- Wallets Table -->
    <div class="glass-card p-6 rounded-3xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-white/[0.06]">
                        <th class="pb-3 font-semibold">User</th>
                        <th class="pb-3 font-semibold">Liquid Balance</th>
                        <th class="pb-3 font-semibold">Bonus Credits</th>
                        <th class="pb-3 font-semibold">In-Play Escrow</th>
                        <th class="pb-3 font-semibold">Total Deposited</th>
                        <th class="pb-3 font-semibold">Total Withdrawn</th>
                        <th class="pb-3 font-semibold">Total Wagered</th>
                        <th class="pb-3 font-semibold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    <?php foreach ($wallets as $w): ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="py-3">
                                <a href="/admin/user-details.php?id=<?= $w['user_id'] ?>" class="font-bold text-white hover:text-brand-400">
                                    <?= e($w['username']) ?>
                                </a>
                                <span class="block text-[11px] text-slate-400"><?= e($w['email']) ?></span>
                            </td>
                            <td class="py-3 font-mono font-bold text-emerald-400"><?= format_money($w['balance']) ?></td>
                            <td class="py-3 font-mono text-purple-400"><?= format_money($w['bonus_balance']) ?></td>
                            <td class="py-3 font-mono text-amber-400"><?= format_money($w['locked_balance']) ?></td>
                            <td class="py-3 font-mono text-slate-300"><?= format_money($w['total_deposited']) ?></td>
                            <td class="py-3 font-mono text-slate-300"><?= format_money($w['total_withdrawn']) ?></td>
                            <td class="py-3 font-mono text-slate-300"><?= format_money($w['total_wagered']) ?></td>
                            <td class="py-3 text-right">
                                <a href="/admin/user-details.php?id=<?= $w['user_id'] ?>" class="px-3 py-1 rounded-lg bg-dark-800 text-brand-400 text-xs font-semibold">
                                    Adjust &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
