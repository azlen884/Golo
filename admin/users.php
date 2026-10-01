<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "SELECT u.*, w.balance, p.first_name, p.last_name, p.kyc_status
        FROM users u
        LEFT JOIN wallets w ON w.user_id = u.id
        LEFT JOIN profiles p ON p.user_id = u.id";
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(u.username LIKE :s OR u.email LIKE :s OR u.referral_code LIKE :s)";
    $params[':s'] = "%{$search}%";
}
if (!empty($status)) {
    $where[] = "u.status = :st";
    $params[':st'] = $status;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY u.id DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

$pageTitle = 'Player Accounts Management';
$activeAdminNav = 'users';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Registered Users</h1>
            <p class="text-xs text-slate-400 mt-1">Player registry, balances, KYC status, and security permissions</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <form method="GET" action="/admin/users.php" class="p-4 rounded-2xl bg-dark-900 border border-white/[0.06] flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search username, email, or referral code..."
                   class="w-full px-4 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
        </div>
        <div class="w-40">
            <select name="status" class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none">
                <option value="">All Statuses</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                <option value="banned" <?= $status === 'banned' ? 'selected' : '' ?>>Banned</option>
            </select>
        </div>
        <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs transition-colors">
            Filter
        </button>
        <?php if (!empty($search) || !empty($status)): ?>
            <a href="/admin/users.php" class="text-xs text-brand-400 hover:underline px-2">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Users Table -->
    <div class="glass-card p-6 rounded-3xl">
        <?php if (!empty($usersList)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">User ID</th>
                            <th class="pb-3 font-semibold">Username / Email</th>
                            <th class="pb-3 font-semibold">Wallet Balance</th>
                            <th class="pb-3 font-semibold">Referral Code</th>
                            <th class="pb-3 font-semibold">KYC</th>
                            <th class="pb-3 font-semibold">Joined Date</th>
                            <th class="pb-3 font-semibold">Status</th>
                            <th class="pb-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($usersList as $u): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono text-slate-400">#<?= $u['id'] ?></td>
                                <td class="py-3">
                                    <a href="/admin/user-details.php?id=<?= $u['id'] ?>" class="font-bold text-white hover:text-brand-400">
                                        <?= e($u['username']) ?>
                                    </a>
                                    <span class="block text-[11px] text-slate-400"><?= e($u['email']) ?></span>
                                </td>
                                <td class="py-3 font-mono font-bold text-emerald-400"><?= format_money($u['balance']) ?></td>
                                <td class="py-3 font-mono text-slate-300"><?= e($u['referral_code']) ?></td>
                                <td class="py-3"><?= render_status_badge($u['kyc_status'] ?? 'unverified') ?></td>
                                <td class="py-3 font-mono text-slate-400"><?= format_date($u['created_at']) ?></td>
                                <td class="py-3"><?= render_status_badge($u['status']) ?></td>
                                <td class="py-3 text-right">
                                    <a href="/admin/user-details.php?id=<?= $u['id'] ?>" class="px-3 py-1 rounded-lg bg-dark-800 hover:bg-dark-750 text-brand-400 text-xs font-semibold">
                                        Manage &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">
                No users found matching your search.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
