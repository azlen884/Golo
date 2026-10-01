<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

// Real queries
$userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$verifiedKycCount = (int)$pdo->query("SELECT COUNT(*) FROM profiles WHERE kyc_status = 'verified'")->fetchColumn();

$roundsCompleted = (int)$pdo->query("SELECT COUNT(*) FROM rounds WHERE status = 'completed'")->fetchColumn();
$totalWagersPlaced = (int)$pdo->query("SELECT COUNT(*) FROM bets")->fetchColumn();
$totalWagersVolume = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM bets")->fetchColumn();
$totalPayoutsWon = (float)$pdo->query("SELECT COALESCE(SUM(actual_payout), 0) FROM bets WHERE status = 'won'")->fetchColumn();

// Win rate
$winBetsCount = (int)$pdo->query("SELECT COUNT(*) FROM bets WHERE status = 'won'")->fetchColumn();
$winRate = $totalWagersPlaced > 0 ? round(($winBetsCount / $totalWagersPlaced) * 100, 2) : 0;

$pageTitle = 'Operational & Gaming Reports';
$activeAdminNav = 'reports';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Platform Performance Reports</h1>
        <p class="text-xs text-slate-400 mt-1">Aggregated platform gameplay metrics and user retention statistics</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card p-6 rounded-3xl">
            <span class="text-xs text-slate-400 font-semibold block mb-1">Rounds Concluded</span>
            <div class="text-2xl font-black text-white font-mono"><?= number_format($roundsCompleted) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Through automated cron cycle</div>
        </div>
        <div class="glass-card p-6 rounded-3xl">
            <span class="text-xs text-slate-400 font-semibold block mb-1">Total Wagers Placed</span>
            <div class="text-2xl font-black text-white font-mono"><?= number_format($totalWagersPlaced) ?></div>
            <div class="text-[11px] text-slate-500 mt-1"><?= $winRate ?>% player win rate</div>
        </div>
        <div class="glass-card p-6 rounded-3xl">
            <span class="text-xs text-slate-400 font-semibold block mb-1">Total Wagers Volume</span>
            <div class="text-2xl font-black text-emerald-400 font-mono"><?= format_money($totalWagersVolume) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Gross player wagers</div>
        </div>
        <div class="glass-card p-6 rounded-3xl">
            <span class="text-xs text-slate-400 font-semibold block mb-1">Disbursed Payouts</span>
            <div class="text-2xl font-black text-cyan-400 font-mono"><?= format_money($totalPayoutsWon) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Winning round settlements</div>
        </div>
    </div>

    <!-- User Growth & Compliance -->
    <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-4">
        <h3 class="text-sm font-bold text-white">Player Compliance & Onboarding Breakdown</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
            <div class="p-4 rounded-2xl bg-dark-950 border border-white/[0.06]">
                <span class="text-xs text-slate-400">Total Registered</span>
                <span class="text-xl font-bold font-mono text-white block mt-1"><?= number_format($userCount) ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-dark-950 border border-white/[0.06]">
                <span class="text-xs text-slate-400">KYC Verified Players</span>
                <span class="text-xl font-bold font-mono text-emerald-400 block mt-1"><?= number_format($verifiedKycCount) ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-dark-950 border border-white/[0.06]">
                <span class="text-xs text-slate-400">Verification Ratio</span>
                <span class="text-xl font-bold font-mono text-brand-400 block mt-1">
                    <?= $userCount > 0 ? round(($verifiedKycCount / $userCount) * 100, 1) : 0 ?>%
                </span>
            </div>
        </div>
    </div>
</div>

</body>
</html>
