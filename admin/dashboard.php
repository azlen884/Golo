<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

// Real database metrics (Zero fake data)
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalWalletsBalance = (float)$pdo->query("SELECT COALESCE(SUM(balance), 0.0000) FROM wallets")->fetchColumn();
$totalDepositsApproved = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0.0000) FROM deposits WHERE status = 'approved'")->fetchColumn();
$totalWithdrawalsApproved = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0.0000) FROM withdrawals WHERE status = 'approved'")->fetchColumn();

$totalBetsCount = (int)$pdo->query("SELECT COUNT(*) FROM bets")->fetchColumn();
$totalBetsVolume = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0.0000) FROM bets")->fetchColumn();
$totalRoundsCount = (int)$pdo->query("SELECT COUNT(*) FROM rounds")->fetchColumn();

$pendingDeposits = (int)$pdo->query("SELECT COUNT(*) FROM deposits WHERE status = 'pending'")->fetchColumn();
$pendingWithdrawals = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
$openTickets = (int)$pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();

// Recent Registrations
$recentUsers = $pdo->query("
    SELECT u.*, w.balance, p.first_name, p.last_name
    FROM users u
    LEFT JOIN wallets w ON w.user_id = u.id
    LEFT JOIN profiles p ON p.user_id = u.id
    ORDER BY u.created_at DESC
    LIMIT 5
")->fetchAll();

// Recent Transactions
$recentTxs = $pdo->query("
    SELECT t.*, u.username
    FROM transactions t
    JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC
    LIMIT 5
")->fetchAll();

$pageTitle = 'Executive Dashboard';
$activeAdminNav = 'dashboard';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-8">
    
    <!-- Platform Status Banner -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-5 rounded-2xl bg-dark-900 border border-white/[0.06]">
        <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full bg-emerald-400 animate-ping"></div>
            <div>
                <span class="text-xs font-bold text-white">Common Gaming Platform Core Active</span>
                <span class="text-[11px] text-slate-400 block">MariaDB MySQL Engine • Cron Last Run: <?= format_date(get_setting('cron_last_run'), 'Y-m-d H:i:s') ?></span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?php if (is_maintenance_mode()): ?>
                <span class="px-3 py-1 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 text-xs font-semibold">
                    Maintenance Mode Active
                </span>
            <?php else: ?>
                <span class="px-3 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-semibold">
                    Platform Live
                </span>
            <?php endif; ?>
            <a href="/admin/rounds.php" class="px-3 py-1 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold transition-colors">
                Rounds Control &rarr;
            </a>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <div class="glass-card p-5 rounded-2xl stat-card">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-semibold text-slate-400">Total Registered Players</span>
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-white font-mono"><?= number_format($totalUsers) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Real database records</div>
        </div>

        <div class="glass-card p-5 rounded-2xl stat-card">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-semibold text-slate-400">Total User Funds</span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-emerald-400 font-mono"><?= format_money($totalWalletsBalance) ?></div>
            <div class="text-[11px] text-slate-500 mt-1">Cumulative wallet liabilities</div>
        </div>

        <div class="glass-card p-5 rounded-2xl stat-card">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-semibold text-slate-400">Total Settled Deposits</span>
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-white font-mono"><?= format_money($totalDepositsApproved) ?></div>
            <div class="text-[11px] text-slate-500 mt-1"><?= $pendingDeposits ?> pending verification</div>
        </div>

        <div class="glass-card p-5 rounded-2xl stat-card">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-semibold text-slate-400">Total Wagers Volume</span>
                <span class="p-2 rounded-xl bg-cyan-500/10 text-cyan-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-white font-mono"><?= format_money($totalBetsVolume) ?></div>
            <div class="text-[11px] text-slate-500 mt-1"><?= number_format($totalBetsCount) ?> wagers placed</div>
        </div>

    </div>

    <!-- Quick Action / Pending Review Alerts -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="/admin/deposits.php" class="p-5 rounded-2xl glass-card border-brand-500/20 flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-400 font-semibold block">Pending Deposits</span>
                <span class="text-2xl font-black font-mono <?= $pendingDeposits > 0 ? 'text-amber-400' : 'text-slate-300' ?>"><?= $pendingDeposits ?></span>
            </div>
            <span class="px-3 py-1 rounded-xl bg-dark-850 text-xs font-semibold text-brand-400">Review &rarr;</span>
        </a>

        <a href="/admin/withdrawals.php" class="p-5 rounded-2xl glass-card border-brand-500/20 flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-400 font-semibold block">Pending Withdrawals</span>
                <span class="text-2xl font-black font-mono <?= $pendingWithdrawals > 0 ? 'text-rose-400' : 'text-slate-300' ?>"><?= $pendingWithdrawals ?></span>
            </div>
            <span class="px-3 py-1 rounded-xl bg-dark-850 text-xs font-semibold text-brand-400">Review &rarr;</span>
        </a>

        <a href="/admin/tickets.php" class="p-5 rounded-2xl glass-card border-brand-500/20 flex items-center justify-between">
            <div>
                <span class="text-xs text-slate-400 font-semibold block">Open Support Tickets</span>
                <span class="text-2xl font-black font-mono <?= $openTickets > 0 ? 'text-blue-400' : 'text-slate-300' ?>"><?= $openTickets ?></span>
            </div>
            <span class="px-3 py-1 rounded-xl bg-dark-850 text-xs font-semibold text-brand-400">Respond &rarr;</span>
        </a>
    </div>

    <!-- Data Tables: Recent Users & Recent Transactions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Latest User Registrations -->
        <div class="glass-card p-6 rounded-3xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-white">Latest Registered Users</h3>
                <a href="/admin/users.php" class="text-xs font-semibold text-brand-400 hover:text-brand-300">Manage All &rarr;</a>
            </div>

            <?php if (!empty($recentUsers)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-white/[0.06]">
                                <th class="pb-3 font-semibold">User</th>
                                <th class="pb-3 font-semibold">Balance</th>
                                <th class="pb-3 font-semibold">Joined</th>
                                <th class="pb-3 font-semibold text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04]">
                            <?php foreach ($recentUsers as $ru): ?>
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="py-3">
                                        <a href="/admin/user-details.php?id=<?= $ru['id'] ?>" class="font-semibold text-white hover:text-brand-400">
                                            <?= e($ru['username']) ?>
                                        </a>
                                        <span class="block text-[11px] text-slate-400"><?= e($ru['email']) ?></span>
                                    </td>
                                    <td class="py-3 font-mono font-bold text-emerald-400"><?= format_money($ru['balance']) ?></td>
                                    <td class="py-3 font-mono text-slate-400"><?= format_date($ru['created_at'], 'M d, H:i') ?></td>
                                    <td class="py-3 text-right"><?= render_status_badge($ru['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-slate-500 text-xs">No registered players yet.</div>
            <?php endif; ?>
        </div>

        <!-- Latest Transactions -->
        <div class="glass-card p-6 rounded-3xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-white">Latest Transactions</h3>
                <a href="/admin/transactions.php" class="text-xs font-semibold text-brand-400 hover:text-brand-300">All Transactions &rarr;</a>
            </div>

            <?php if (!empty($recentTxs)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-white/[0.06]">
                                <th class="pb-3 font-semibold">Reference</th>
                                <th class="pb-3 font-semibold">Player</th>
                                <th class="pb-3 font-semibold">Amount</th>
                                <th class="pb-3 font-semibold text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04]">
                            <?php foreach ($recentTxs as $rt): ?>
                                <tr class="hover:bg-white/[0.02]">
                                    <td class="py-3 font-mono text-slate-300">
                                        <?= e($rt['transaction_ref']) ?>
                                        <span class="block text-[10px] text-slate-500 uppercase"><?= e($rt['type']) ?></span>
                                    </td>
                                    <td class="py-3 text-slate-300 font-medium"><?= e($rt['username']) ?></td>
                                    <td class="py-3 font-mono font-bold text-white"><?= format_money($rt['amount']) ?></td>
                                    <td class="py-3 text-right"><?= render_status_badge($rt['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-slate-500 text-xs">No transactions recorded yet.</div>
            <?php endif; ?>
        </div>

    </div>

</div>

</body>
</html>
