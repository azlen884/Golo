<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

// Financial metrics
$totBalance = (float)$pdo->query("SELECT COALESCE(SUM(balance), 0) FROM wallets")->fetchColumn();
$totBonus = (float)$pdo->query("SELECT COALESCE(SUM(bonus_balance), 0) FROM wallets")->fetchColumn();
$totLocked = (float)$pdo->query("SELECT COALESCE(SUM(locked_balance), 0) FROM wallets")->fetchColumn();
$totDeposited = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'approved'")->fetchColumn();
$totWithdrawn = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'approved'")->fetchColumn();
$totWagered = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM bets")->fetchColumn();
$totWon = (float)$pdo->query("SELECT COALESCE(SUM(actual_payout), 0) FROM bets WHERE status = 'won'")->fetchColumn();

// Gross Gaming Revenue (GGR)
$ggr = round($totWagered - $totWon, 4);

$pageTitle = 'Master Wallet & Solvency';
$activeAdminNav = 'wallet';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Master Financial Health & Solvency</h1>
        <p class="text-xs text-slate-400 mt-1">Real-time ledger solvency across all user balances, deposits, and game holds</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card p-6 rounded-3xl stat-card">
            <span class="text-xs font-semibold text-slate-400 block mb-1">Total Player Liabilities</span>
            <div class="text-2xl font-black text-emerald-400 font-mono"><?= format_money($totBalance) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Liquid withdrawable player balances</div>
        </div>

        <div class="glass-card p-6 rounded-3xl stat-card">
            <span class="text-xs font-semibold text-slate-400 block mb-1">Total Bonus Credits</span>
            <div class="text-2xl font-black text-purple-400 font-mono"><?= format_money($totBonus) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Subject to wagering requirements</div>
        </div>

        <div class="glass-card p-6 rounded-3xl stat-card">
            <span class="text-xs font-semibold text-slate-400 block mb-1">Gross Gaming Revenue</span>
            <div class="text-2xl font-black text-white font-mono"><?= format_money($ggr) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Wagers minus player payouts</div>
        </div>

        <div class="glass-card p-6 rounded-3xl stat-card">
            <span class="text-xs font-semibold text-slate-400 block mb-1">Net Platform Cashflow</span>
            <div class="text-2xl font-black text-cyan-400 font-mono"><?= format_money($totDeposited - $totWithdrawn) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Approved deposits minus withdrawals</div>
        </div>
    </div>

    <!-- Quick Navigation Links -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="/admin/deposits.php" class="p-6 rounded-2xl glass-card hover:border-brand-500/40 transition-all flex justify-between items-center">
            <div>
                <span class="text-sm font-bold text-white block">Deposits Desk</span>
                <span class="text-xs text-slate-400">Total processed: <?= format_money($totDeposited) ?></span>
            </div>
            <span class="text-brand-400 text-sm font-bold">&rarr;</span>
        </a>

        <a href="/admin/withdrawals.php" class="p-6 rounded-2xl glass-card hover:border-brand-500/40 transition-all flex justify-between items-center">
            <div>
                <span class="text-sm font-bold text-white block">Withdrawals Desk</span>
                <span class="text-xs text-slate-400">Total disbursed: <?= format_money($totWithdrawn) ?></span>
            </div>
            <span class="text-brand-400 text-sm font-bold">&rarr;</span>
        </a>

        <a href="/admin/payment-settings.php" class="p-6 rounded-2xl glass-card hover:border-brand-500/40 transition-all flex justify-between items-center">
            <div>
                <span class="text-sm font-bold text-white block">Gateway Configuration</span>
                <span class="text-xs text-slate-400">Crypto addresses & limits</span>
            </div>
            <span class="text-brand-400 text-sm font-bold">&rarr;</span>
        </a>
    </div>
</div>

</body>
</html>
