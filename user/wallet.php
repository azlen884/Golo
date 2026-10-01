<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/wallet.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$wallet = get_user_wallet($userId, false, $pdo);

// Ledger entries
$ledgerStmt = $pdo->prepare("
    SELECT * FROM wallet_ledger
    WHERE user_id = :uid
    ORDER BY id DESC
    LIMIT 30
");
$ledgerStmt->execute([':uid' => $userId]);
$ledgerEntries = $ledgerStmt->fetchAll();

$pageTitle = 'Player Wallet & Ledger — Apex Gaming Platform';
$activeNav = 'wallet';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-white">Player Wallet</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Real-time balances and verified double-entry accounting ledger</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/user/deposit.php" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs shadow-lg shadow-brand-600/30 transition-all">
                + Deposit Funds
            </a>
            <a href="/user/withdrawal.php" class="px-5 py-2.5 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.08] text-slate-200 font-semibold text-xs transition-colors">
                Request Payout
            </a>
        </div>
    </div>

    <!-- Balance Breakdown Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="glass-card p-6 rounded-2xl border-brand-500/20">
            <span class="text-xs font-semibold text-slate-400 block mb-1">Withdrawable Balance</span>
            <div class="text-3xl font-black text-white font-mono"><?= format_money($wallet['balance']) ?></div>
            <div class="mt-4 pt-3 border-t border-white/[0.06] flex justify-between text-[11px] text-slate-400">
                <span>Currency: <strong class="text-slate-200 font-mono"><?= e($wallet['currency']) ?></strong></span>
                <span>Status: <strong class="text-emerald-400">Liquid</strong></span>
            </div>
        </div>

        <div class="glass-card p-6 rounded-2xl">
            <span class="text-xs font-semibold text-slate-400 block mb-1">Bonus Credits</span>
            <div class="text-3xl font-black text-purple-400 font-mono"><?= format_money($wallet['bonus_balance']) ?></div>
            <div class="mt-4 pt-3 border-t border-white/[0.06] flex justify-between text-[11px] text-slate-400">
                <span>Wagering required</span>
                <a href="/user/bonuses.php" class="text-brand-400 hover:underline">View Rules &rarr;</a>
            </div>
        </div>

        <div class="glass-card p-6 rounded-2xl">
            <span class="text-xs font-semibold text-slate-400 block mb-1">Escrow / In-Play</span>
            <div class="text-3xl font-black text-amber-400 font-mono"><?= format_money($wallet['locked_balance']) ?></div>
            <div class="mt-4 pt-3 border-t border-white/[0.06] flex justify-between text-[11px] text-slate-400">
                <span>Committed to active rounds</span>
                <a href="/user/my-bets.php" class="text-brand-400 hover:underline">Active Bets &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Lifetime Wallet Metrics -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-xl bg-dark-900 border border-white/[0.06]">
            <span class="text-[11px] text-slate-400 block">Total Deposited</span>
            <span class="text-base font-bold text-white font-mono mt-0.5 block"><?= format_money($wallet['total_deposited']) ?></span>
        </div>
        <div class="p-4 rounded-xl bg-dark-900 border border-white/[0.06]">
            <span class="text-[11px] text-slate-400 block">Total Withdrawn</span>
            <span class="text-base font-bold text-white font-mono mt-0.5 block"><?= format_money($wallet['total_withdrawn']) ?></span>
        </div>
        <div class="p-4 rounded-xl bg-dark-900 border border-white/[0.06]">
            <span class="text-[11px] text-slate-400 block">Total Wagered</span>
            <span class="text-base font-bold text-white font-mono mt-0.5 block"><?= format_money($wallet['total_wagered']) ?></span>
        </div>
        <div class="p-4 rounded-xl bg-dark-900 border border-white/[0.06]">
            <span class="text-[11px] text-slate-400 block">Total Won</span>
            <span class="text-base font-bold text-emerald-400 font-mono mt-0.5 block"><?= format_money($wallet['total_won']) ?></span>
        </div>
    </div>

    <!-- Double-Entry Immutable Ledger Audit -->
    <div class="glass-card p-6 sm:p-8 rounded-3xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-base font-bold text-white">Immutable Wallet Ledger</h3>
                <p class="text-xs text-slate-400">Cryptographically verifiable before-and-after balance ledger entries</p>
            </div>
            <div class="px-2.5 py-1 rounded-lg bg-dark-850 border border-white/[0.06] text-[10px] text-slate-400 font-mono">
                Real MySQL Engine
            </div>
        </div>

        <?php if (!empty($ledgerEntries)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Entry ID</th>
                            <th class="pb-3 font-semibold">Type</th>
                            <th class="pb-3 font-semibold">Description</th>
                            <th class="pb-3 font-semibold">Amount</th>
                            <th class="pb-3 font-semibold">Before</th>
                            <th class="pb-3 font-semibold">After</th>
                            <th class="pb-3 font-semibold text-right">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($ledgerEntries as $entry): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono text-slate-400">#<?= $entry['id'] ?></td>
                                <td class="py-3"><?= render_status_badge($entry['transaction_type']) ?></td>
                                <td class="py-3 text-slate-300 max-w-xs truncate"><?= e($entry['description']) ?></td>
                                <td class="py-3 font-mono font-bold <?= (float)$entry['amount'] > 0 ? 'text-emerald-400' : 'text-rose-400' ?>">
                                    <?= (float)$entry['amount'] > 0 ? '+' : '' ?><?= format_money($entry['amount']) ?>
                                </td>
                                <td class="py-3 font-mono text-slate-400"><?= format_money($entry['balance_before']) ?></td>
                                <td class="py-3 font-mono text-slate-200"><?= format_money($entry['balance_after']) ?></td>
                                <td class="py-3 text-slate-400 text-right font-mono"><?= format_date($entry['created_at'], 'Y-m-d H:i') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">
                No ledger activity recorded yet. Initial ledger entries generate upon first deposit or promotion credit.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
