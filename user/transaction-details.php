<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/transactions.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];

$ref = $_GET['ref'] ?? '';
$tx = get_transaction_by_ref($ref);

if (!$tx || (int)$tx['user_id'] !== $userId) {
    set_flash('error', 'Transaction not found or access denied.');
    redirect('/user/transactions.php');
}

$pageTitle = 'Transaction ' . $tx['transaction_ref'] . ' — Apex Gaming Platform';
$activeNav = 'wallet';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="/user/transactions.php" class="text-xs text-brand-400 hover:underline mb-1 inline-block">&larr; Back to Transactions</a>
            <h1 class="text-2xl font-black text-white">Transaction Breakdown</h1>
        </div>
        <div>
            <?= render_status_badge($tx['status']) ?>
        </div>
    </div>

    <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
        <div class="text-center py-4 border-b border-white/[0.06]">
            <span class="text-xs text-slate-400 uppercase tracking-wider block mb-1">Transaction Net Amount</span>
            <div class="text-3xl font-black font-mono <?= $tx['type'] === 'deposit' || $tx['type'] === 'win' ? 'text-emerald-400' : 'text-white' ?>">
                <?= format_money($tx['net_amount']) ?>
            </div>
            <div class="text-xs font-mono text-slate-400 mt-1"><?= e($tx['transaction_ref']) ?></div>
        </div>

        <div class="space-y-3 text-xs">
            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                <span class="text-slate-400">Operation Type</span>
                <span class="text-white font-semibold uppercase"><?= e($tx['type']) ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                <span class="text-slate-400">Gross Amount</span>
                <span class="text-white font-mono"><?= format_money($tx['amount']) ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                <span class="text-slate-400">Processed Fee</span>
                <span class="text-slate-300 font-mono"><?= format_money($tx['fee']) ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                <span class="text-slate-400">Payment Channel</span>
                <span class="text-white font-medium"><?= e($tx['payment_method'] ?? 'Internal Engine Ledger') ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                <span class="text-slate-400">Date Initiated</span>
                <span class="text-white font-mono"><?= format_date($tx['created_at'], 'Y-m-d H:i:s') ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                <span class="text-slate-400">Last Status Update</span>
                <span class="text-white font-mono"><?= format_date($tx['updated_at'], 'Y-m-d H:i:s') ?></span>
            </div>
            <?php if (!empty($tx['meta_data'])): ?>
                <div class="pt-2">
                    <span class="text-slate-400 block mb-1">Audit Metadata:</span>
                    <pre class="p-3 rounded-xl bg-dark-950 border border-white/[0.06] text-[11px] font-mono text-slate-300 overflow-x-auto"><?= e($tx['meta_data']) ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
