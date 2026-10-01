<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

// Real financial calculations
$depositsApproved = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'approved'")->fetchColumn();
$withdrawalsApproved = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'approved'")->fetchColumn();
$totalDepositFees = (float)$pdo->query("SELECT COALESCE(SUM(fee), 0) FROM deposits WHERE status = 'approved'")->fetchColumn();
$totalWithdrawalFees = (float)$pdo->query("SELECT COALESCE(SUM(fee), 0) FROM withdrawals WHERE status = 'approved'")->fetchColumn();

$wagersPlaced = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM bets")->fetchColumn();
$wagersWon = (float)$pdo->query("SELECT COALESCE(SUM(actual_payout), 0) FROM bets WHERE status = 'won'")->fetchColumn();
$ggr = round($wagersPlaced - $wagersWon, 4);

$affiliatePaid = (float)$pdo->query("SELECT COALESCE(SUM(total_earnings), 0) FROM referrals")->fetchColumn();
$netPlatformProfit = round($ggr + $totalDepositFees + $totalWithdrawalFees - $affiliatePaid, 4);

$pageTitle = 'Financial Accounting Reports';
$activeAdminNav = 'financial-reports';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Financial Statement & Accounting</h1>
        <p class="text-xs text-slate-400 mt-1">Calculated platform revenue, fees earned, player liabilities, and affiliate deductions</p>
    </div>

    <!-- PnL Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="glass-card p-6 rounded-3xl">
            <span class="text-xs font-semibold text-slate-400 block mb-1">Gross Gaming Revenue (GGR)</span>
            <div class="text-3xl font-black font-mono <?= $ggr >= 0 ? 'text-emerald-400' : 'text-rose-400' ?>"><?= format_money($ggr) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Total wagers placed minus total prizes awarded</div>
        </div>

        <div class="glass-card p-6 rounded-3xl">
            <span class="text-xs font-semibold text-slate-400 block mb-1">Gateway Fees Collected</span>
            <div class="text-3xl font-black font-mono text-cyan-400"><?= format_money($totalDepositFees + $totalWithdrawalFees) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Deposits + withdrawals fee income</div>
        </div>

        <div class="glass-card p-6 rounded-3xl border-brand-500/30">
            <span class="text-xs font-semibold text-slate-400 block mb-1">Net Platform Operating Margin</span>
            <div class="text-3xl font-black font-mono <?= $netPlatformProfit >= 0 ? 'text-emerald-400' : 'text-rose-400' ?>"><?= format_money($netPlatformProfit) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">GGR + Fees minus Affiliate Commissions</div>
        </div>
    </div>

    <!-- Accounting Ledger Statement Table -->
    <div class="glass-card p-6 sm:p-8 rounded-3xl">
        <h3 class="text-sm font-bold text-white mb-4">Detailed Accounting Breakdown</h3>
        <div class="divide-y divide-white/[0.06] text-xs">
            <div class="py-3 flex justify-between items-center">
                <span class="text-slate-300">Total User Cash Inflow (Approved Deposits)</span>
                <span class="font-mono font-bold text-emerald-400"><?= format_money($depositsApproved) ?></span>
            </div>
            <div class="py-3 flex justify-between items-center">
                <span class="text-slate-300">Total User Cash Outflow (Approved Withdrawals)</span>
                <span class="font-mono font-bold text-rose-400">-<?= format_money($withdrawalsApproved) ?></span>
            </div>
            <div class="py-3 flex justify-between items-center">
                <span class="text-slate-300">Total Gaming Handle (Volume of all Bets)</span>
                <span class="font-mono font-bold text-white"><?= format_money($wagersPlaced) ?></span>
            </div>
            <div class="py-3 flex justify-between items-center">
                <span class="text-slate-300">Total Gaming Payouts (Awarded to Winners)</span>
                <span class="font-mono font-bold text-rose-400">-<?= format_money($wagersWon) ?></span>
            </div>
            <div class="py-3 flex justify-between items-center">
                <span class="text-slate-300">Affiliate Commission Outlay</span>
                <span class="font-mono font-bold text-amber-400">-<?= format_money($affiliatePaid) ?></span>
            </div>
            <div class="py-3 flex justify-between items-center font-bold text-sm bg-dark-950/60 px-4 rounded-xl mt-2">
                <span class="text-white">Net Solvency Surplus</span>
                <span class="font-mono text-emerald-400"><?= format_money($depositsApproved - $withdrawalsApproved) ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
