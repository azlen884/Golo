<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $rate = (float)($_POST['referral_commission_rate'] ?? 5.0);
    set_setting('referral_commission_rate', $rate, 'financial');
    log_admin_activity($admin['id'], 'update_referral_rate', 'settings', 'referral_commission_rate', "Updated commission rate to {$rate}%");
    set_flash('success', "Referral commission rate set to {$rate}%.");
    redirect('/admin/referrals.php');
}

$currentRate = get_setting('referral_commission_rate', '5.00');

$sql = "SELECT r.*, u1.username as referrer_name, u2.username as referee_name
        FROM referrals r
        JOIN users u1 ON u1.id = r.referrer_id
        JOIN users u2 ON u2.id = r.referee_id
        ORDER BY r.id DESC LIMIT 40";
$referrals = $pdo->query($sql)->fetchAll();

$totCommissions = (float)$pdo->query("SELECT COALESCE(SUM(total_earnings), 0) FROM referrals")->fetchColumn();

$pageTitle = 'Affiliate & Referral Operations';
$activeAdminNav = 'referrals';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Referrals & Affiliates</h1>
        <p class="text-xs text-slate-400 mt-1">Global commission rate settings and player network tracking</p>
    </div>

    <!-- Global Rate Configuration Card -->
    <div class="glass-card p-6 sm:p-8 rounded-3xl flex flex-col md:flex-row justify-between md:items-center gap-6">
        <div>
            <span class="text-xs font-bold text-brand-400 uppercase tracking-wider block mb-1">Global System Rate</span>
            <div class="text-3xl font-black text-white font-mono"><?= (float)$currentRate ?>%</div>
            <p class="text-xs text-slate-400 mt-1">Commission percentage automatically credited on qualified deposit execution.</p>
            <div class="text-xs text-emerald-400 mt-2 font-mono">Total Paid to Affiliates: <?= format_money($totCommissions) ?></div>
        </div>

        <form method="POST" action="/admin/referrals.php" class="flex items-center gap-3">
            <?= csrf_field() ?>
            <div class="relative">
                <input type="number" step="0.1" min="0" max="50" name="referral_commission_rate" value="<?= (float)$currentRate ?>" required
                       class="w-32 px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono focus:outline-none focus:border-brand-500">
                <span class="absolute right-3 top-2.5 text-slate-400 text-xs font-bold">%</span>
            </div>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow-lg shadow-brand-600/30">
                Save Rate
            </button>
        </form>
    </div>

    <!-- Active Referral Relationships -->
    <div class="glass-card p-6 rounded-3xl">
        <h3 class="text-sm font-bold text-white mb-4">Referral Relationships</h3>

        <?php if (!empty($referrals)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Referrer</th>
                            <th class="pb-3 font-semibold">Referee (Player)</th>
                            <th class="pb-3 font-semibold">Referral Code</th>
                            <th class="pb-3 font-semibold">Commission Rate</th>
                            <th class="pb-3 font-semibold">Total Paid</th>
                            <th class="pb-3 font-semibold">Established Date</th>
                            <th class="pb-3 font-semibold text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($referrals as $ref): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-medium text-white">
                                    <a href="/admin/user-details.php?id=<?= $ref['referrer_id'] ?>" class="hover:text-brand-400">
                                        <?= e($ref['referrer_name']) ?>
                                    </a>
                                </td>
                                <td class="py-3 text-slate-300">
                                    <a href="/admin/user-details.php?id=<?= $ref['referee_id'] ?>" class="hover:text-brand-400">
                                        <?= e($ref['referee_name']) ?>
                                    </a>
                                </td>
                                <td class="py-3 font-mono text-slate-400"><?= e($ref['referral_code']) ?></td>
                                <td class="py-3 font-mono text-cyan-400"><?= (float)$ref['commission_rate'] ?>%</td>
                                <td class="py-3 font-mono font-bold text-emerald-400"><?= format_money($ref['total_earnings']) ?></td>
                                <td class="py-3 font-mono text-slate-400"><?= format_date($ref['created_at']) ?></td>
                                <td class="py-3 text-right"><?= render_status_badge($ref['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">
                No affiliate connections recorded yet.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
