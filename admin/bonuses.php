<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/wallet.php';
require_once __DIR__ . '/../includes/notifications.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $username = trim($_POST['username'] ?? '');
    $bonusType = $_POST['bonus_type'] ?? 'welcome';
    $title = trim($_POST['title'] ?? 'Bonus Credit');
    $amount = (float)($_POST['amount'] ?? 0);
    $wagerReq = (float)($_POST['wager_requirement'] ?? 0);

    // Find user
    $uStmt = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
    $uStmt->execute([':u' => $username]);
    $u = $uStmt->fetch();

    if (!$u) {
        $error = "Player username '{$username}' was not found.";
    } elseif ($amount <= 0) {
        $error = 'Bonus amount must be positive.';
    } else {
        $userId = (int)$u['id'];
        $bIns = $pdo->prepare("
            INSERT INTO bonuses (user_id, bonus_type, title, amount, wager_requirement, wagered_so_far, status, created_at)
            VALUES (:uid, :type, :title, :amt, :req, 0.0000, 'active', NOW())
        ");
        $bIns->execute([
            ':uid'   => $userId,
            ':type'  => $bonusType,
            ':title' => $title,
            ':amt'   => $amount,
            ':req'   => $wagerReq
        ]);

        // Credit bonus balance in wallet
        $pdo->prepare("UPDATE wallets SET bonus_balance = bonus_balance + :amt, updated_at = NOW() WHERE user_id = :uid")
            ->execute([':amt' => $amount, ':uid' => $userId]);

        log_admin_activity($admin['id'], 'grant_bonus', 'bonuses', (string)$userId, "Granted {$amount} USD {$bonusType} bonus to {$username}");
        send_notification($userId, 'Bonus Credited!', "You have been awarded " . format_money($amount) . " bonus credit ({$title}).", 'success', '/user/bonuses.php');

        set_flash('success', "Bonus of " . format_money($amount) . " credited to {$username}.");
        redirect('/admin/bonuses.php');
    }
}

$sql = "SELECT b.*, u.username
        FROM bonuses b
        JOIN users u ON u.id = b.user_id
        ORDER BY b.id DESC LIMIT 40";
$bonuses = $pdo->query($sql)->fetchAll();

$pageTitle = 'Player Bonuses & Incentives';
$activeAdminNav = 'bonuses';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Bonuses Management</h1>
        <p class="text-xs text-slate-400 mt-1">Award promotional credits and audit player rollover targets</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Grant Bonus Form -->
    <div class="glass-card p-6 rounded-3xl">
        <h3 class="text-sm font-bold text-white mb-3">Grant Player Bonus</h3>
        <form method="POST" action="/admin/bonuses.php" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
            <?= csrf_field() ?>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Player Username</label>
                <input type="text" name="username" required placeholder="player1"
                       class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Bonus Category</label>
                <select name="bonus_type" class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
                    <option value="welcome">Welcome</option>
                    <option value="deposit_match">Deposit Match</option>
                    <option value="cashback">Cashback</option>
                    <option value="vip">VIP Loyalty</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Bonus Amount ($)</label>
                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="25.00"
                       class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Rollover Target ($)</label>
                <input type="number" step="0.01" min="0.00" name="wager_requirement" value="100.00" required
                       class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
            </div>

            <button type="submit" class="w-full py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow-lg shadow-brand-600/30">
                Grant Bonus
            </button>
        </form>
    </div>

    <!-- Bonuses Table -->
    <div class="glass-card p-6 rounded-3xl">
        <h3 class="text-sm font-bold text-white mb-4">All Granted Bonuses</h3>
        <?php if (!empty($bonuses)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">User</th>
                            <th class="pb-3 font-semibold">Category</th>
                            <th class="pb-3 font-semibold">Title</th>
                            <th class="pb-3 font-semibold">Amount</th>
                            <th class="pb-3 font-semibold">Rollover Required</th>
                            <th class="pb-3 font-semibold">Wagered So Far</th>
                            <th class="pb-3 font-semibold">Date</th>
                            <th class="pb-3 font-semibold text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($bonuses as $b): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-medium text-white"><?= e($b['username']) ?></td>
                                <td class="py-3 capitalize text-slate-300"><?= e($b['bonus_type']) ?></td>
                                <td class="py-3 text-slate-300"><?= e($b['title']) ?></td>
                                <td class="py-3 font-mono font-bold text-emerald-400"><?= format_money($b['amount']) ?></td>
                                <td class="py-3 font-mono text-slate-300"><?= format_money($b['wager_requirement']) ?></td>
                                <td class="py-3 font-mono text-cyan-400"><?= format_money($b['wagered_so_far']) ?></td>
                                <td class="py-3 font-mono text-slate-400"><?= format_date($b['created_at']) ?></td>
                                <td class="py-3 text-right"><?= render_status_badge($b['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">No active bonuses found.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
