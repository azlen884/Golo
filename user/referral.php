<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/referrals.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$stats = get_referral_stats($userId);
$commRate = get_setting('referral_commission_rate', '5.00');
$refLink = APP_URL . '/user/register.php?ref=' . $user['referral_code'];

// List of referred players
$rStmt = $pdo->prepare("
    SELECT r.*, u.username as referee_name, u.created_at as joined_at
    FROM referrals r
    JOIN users u ON u.id = r.referee_id
    WHERE r.referrer_id = :uid
    ORDER BY r.created_at DESC
");
$rStmt->execute([':uid' => $userId]);
$referralsList = $rStmt->fetchAll();

$pageTitle = 'Affiliate Program — Apex Gaming Platform';
$activeNav = 'referral';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl sm:text-3xl font-black text-white">Affiliate Program</h1>
        <p class="text-xs sm:text-sm text-slate-400 mt-1">Invite friends and earn recurring commission on their deposits</p>
    </div>

    <!-- Referral Link Hero Card -->
    <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-brand-400 uppercase tracking-wider block mb-1">Your Commission Rate</span>
                <div class="text-3xl font-black text-white font-mono"><?= (float)$commRate ?>% Lifetime</div>
                <p class="text-xs text-slate-400 mt-1">Automatically credited to your wallet whenever your referral makes a qualifying deposit.</p>
            </div>
            <div class="space-y-2 w-full md:w-auto">
                <span class="text-xs font-semibold text-slate-300 block">Personal Referral Link</span>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="<?= e($refLink) ?>"
                           class="w-full md:w-80 px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-xs font-mono text-slate-200 select-all">
                    <button type="button" data-copy="<?= e($refLink) ?>"
                            class="px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs whitespace-nowrap shadow-lg shadow-brand-600/30">
                        Copy Link
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="glass-card p-6 rounded-2xl">
            <span class="text-xs font-semibold text-slate-400 block mb-1">Total Referred Players</span>
            <div class="text-2xl font-black text-white font-mono"><?= (int)$stats['total_referees'] ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Players registered with your link</div>
        </div>
        <div class="glass-card p-6 rounded-2xl">
            <span class="text-xs font-semibold text-slate-400 block mb-1">Total Commission Earned</span>
            <div class="text-2xl font-black text-emerald-400 font-mono"><?= format_money($stats['total_earned']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Directly credited to your wallet balance</div>
        </div>
    </div>

    <!-- Referees Table -->
    <div class="glass-card p-6 rounded-3xl">
        <h3 class="text-sm font-bold text-white mb-4">Referred Players</h3>

        <?php if (!empty($referralsList)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Player</th>
                            <th class="pb-3 font-semibold">Joined Date</th>
                            <th class="pb-3 font-semibold">Commission Rate</th>
                            <th class="pb-3 font-semibold">Your Earnings</th>
                            <th class="pb-3 font-semibold text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($referralsList as $ref): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-medium text-slate-200"><?= e($ref['referee_name']) ?></td>
                                <td class="py-3 text-slate-400 font-mono"><?= format_date($ref['joined_at'], 'Y-m-d') ?></td>
                                <td class="py-3 font-mono text-cyan-400"><?= (float)$ref['commission_rate'] ?>%</td>
                                <td class="py-3 font-mono font-bold text-emerald-400"><?= format_money($ref['total_earnings']) ?></td>
                                <td class="py-3 text-right"><?= render_status_badge($ref['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">
                No referrals yet. Share your link above to begin earning passive commission.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
