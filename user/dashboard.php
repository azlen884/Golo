<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/wallet.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

// Real database metrics
$wallet = get_user_wallet($userId, false, $pdo);

$activeBetsCount = (int)$pdo->prepare("SELECT COUNT(*) FROM bets WHERE user_id = :uid AND status = 'pending'");
$activeBetsCountStmt = $pdo->prepare("SELECT COUNT(*) FROM bets WHERE user_id = :uid AND status = 'pending'");
$activeBetsCountStmt->execute([':uid' => $userId]);
$pendingBets = (int)$activeBetsCountStmt->fetchColumn();

// Recent 5 transactions
$txStmt = $pdo->prepare("
    SELECT * FROM transactions
    WHERE user_id = :uid
    ORDER BY created_at DESC
    LIMIT 5
");
$txStmt->execute([':uid' => $userId]);
$recentTransactions = $txStmt->fetchAll();

// Recent 5 bets
$betStmt = $pdo->prepare("
    SELECT b.*, r.round_code
    FROM bets b
    JOIN rounds r ON r.id = b.round_id
    WHERE b.user_id = :uid
    ORDER BY b.placed_at DESC
    LIMIT 5
");
$betStmt->execute([':uid' => $userId]);
$recentBets = $betStmt->fetchAll();

$pageTitle = 'Player Dashboard — Apex Gaming Platform';
$activeNav = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    
    <!-- Welcome Header Banner -->
    <div class="glass-card p-6 sm:p-8 rounded-3xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-600/10 border border-brand-500/20 text-brand-400 text-xs font-semibold uppercase tracking-wider mb-2">
                Verified Player Account
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white">Welcome back, <?= e($user['username']) ?>!</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Your account is secured with double-entry cryptographic verification.
            </p>
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto">
            <a href="/user/deposit.php" class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all text-center">
                + Deposit Funds
            </a>
            <a href="/user/withdrawal.php" class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.08] text-slate-200 font-semibold text-xs text-center transition-colors">
                Withdraw
            </a>
        </div>
    </div>

    <!-- Real Financial Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Available Balance -->
        <div class="glass-card p-5 rounded-2xl stat-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-400">Available Balance</span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-white font-mono"><?= format_money($wallet['balance']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Available for rounds & withdrawal</div>
        </div>

        <!-- Bonus Balance -->
        <div class="glass-card p-5 rounded-2xl stat-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-400">Bonus Credits</span>
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-white font-mono"><?= format_money($wallet['bonus_balance']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Promotional wagering funds</div>
        </div>

        <!-- Total Deposited -->
        <div class="glass-card p-5 rounded-2xl stat-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-400">Total Deposited</span>
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-white font-mono"><?= format_money($wallet['total_deposited']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Lifetime account funding</div>
        </div>

        <!-- Active Bets / Total Won -->
        <div class="glass-card p-5 rounded-2xl stat-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-slate-400">Total Winnings</span>
                <span class="p-2 rounded-xl bg-cyan-500/10 text-cyan-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-white font-mono"><?= format_money($wallet['total_won']) ?></div>
            <div class="text-[11px] text-slate-500 mt-1"><?= $pendingBets ?> bets currently active</div>
        </div>

    </div>

    <!-- Referral Link Box -->
    <div class="p-5 rounded-2xl bg-gradient-to-r from-brand-950/60 to-dark-900 border border-brand-500/20 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="space-y-1 text-center md:text-left">
            <span class="text-xs font-bold text-brand-400 uppercase tracking-wider">Affiliate Program</span>
            <div class="text-sm font-bold text-white">Earn <?= e(get_setting('referral_commission_rate', '5.00')) ?>% on every referral deposit</div>
            <p class="text-xs text-slate-400">Share your referral link to earn lifetime commission credited instantly to your wallet.</p>
        </div>
        <div class="flex items-center gap-2 w-full md:w-auto">
            <input type="text" readonly value="<?= APP_URL ?>/user/register.php?ref=<?= e($user['referral_code']) ?>"
                   class="px-4 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-xs font-mono text-slate-300 w-full md:w-80 select-all">
            <button type="button" data-copy="<?= APP_URL ?>/user/register.php?ref=<?= e($user['referral_code']) ?>"
                    class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs transition-colors flex-shrink-0">
                Copy Link
            </button>
        </div>
    </div>

    <!-- Tables Grid: Recent Bets & Recent Transactions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Recent Bets -->
        <div class="glass-card p-6 rounded-3xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-white">Recent Rounds & Bets</h3>
                <a href="/user/my-bets.php" class="text-xs font-semibold text-brand-400 hover:text-brand-300">View All &rarr;</a>
            </div>

            <?php if (!empty($recentBets)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-white/[0.06]">
                                <th class="pb-3 font-semibold">Bet Code</th>
                                <th class="pb-3 font-semibold">Round</th>
                                <th class="pb-3 font-semibold">Wager</th>
                                <th class="pb-3 font-semibold">Payout</th>
                                <th class="pb-3 font-semibold text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04]">
                            <?php foreach ($recentBets as $b): ?>
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="py-3 font-mono font-medium text-slate-300">
                                        <a href="/user/bet-details.php?code=<?= e($b['bet_code']) ?>" class="hover:text-brand-400 underline decoration-dotted">
                                            <?= e($b['bet_code']) ?>
                                        </a>
                                    </td>
                                    <td class="py-3 font-mono text-slate-400"><?= e($b['round_code']) ?></td>
                                    <td class="py-3 font-mono text-white"><?= format_money($b['amount']) ?></td>
                                    <td class="py-3 font-mono text-emerald-400"><?= (float)$b['actual_payout'] > 0 ? format_money($b['actual_payout']) : '—' ?></td>
                                    <td class="py-3 text-right"><?= render_status_badge($b['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-slate-500 text-xs">
                    No bets placed yet. <a href="/user/game-history.php" class="text-brand-400 hover:underline">Explore active rounds &rarr;</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Transactions -->
        <div class="glass-card p-6 rounded-3xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-white">Recent Transactions</h3>
                <a href="/user/transactions.php" class="text-xs font-semibold text-brand-400 hover:text-brand-300">View All &rarr;</a>
            </div>

            <?php if (!empty($recentTransactions)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-white/[0.06]">
                                <th class="pb-3 font-semibold">Reference</th>
                                <th class="pb-3 font-semibold">Type</th>
                                <th class="pb-3 font-semibold">Amount</th>
                                <th class="pb-3 font-semibold">Date</th>
                                <th class="pb-3 font-semibold text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04]">
                            <?php foreach ($recentTransactions as $t): ?>
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="py-3 font-mono font-medium text-slate-300">
                                        <a href="/user/transaction-details.php?ref=<?= e($t['transaction_ref']) ?>" class="hover:text-brand-400 underline decoration-dotted">
                                            <?= e($t['transaction_ref']) ?>
                                        </a>
                                    </td>
                                    <td class="py-3 capitalize text-slate-300"><?= e($t['type']) ?></td>
                                    <td class="py-3 font-mono font-bold <?= $t['type'] === 'deposit' || $t['type'] === 'win' ? 'text-emerald-400' : 'text-slate-200' ?>">
                                        <?= format_money($t['amount']) ?>
                                    </td>
                                    <td class="py-3 text-slate-400"><?= format_date($t['created_at'], 'M d, H:i') ?></td>
                                    <td class="py-3 text-right"><?= render_status_badge($t['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-slate-500 text-xs">
                    No transactions recorded. <a href="/user/deposit.php" class="text-brand-400 hover:underline">Make your first deposit &rarr;</a>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
