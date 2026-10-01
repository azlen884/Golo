<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payments.php';
require_once __DIR__ . '/../includes/wallet.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$wallet = get_user_wallet($userId, false, $pdo);
$gateways = get_active_payment_methods();
$selectedKey = $_GET['method'] ?? ($gateways[0]['method_key'] ?? '');
$currentGateway = get_payment_method($selectedKey);

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $methodKey = trim($_POST['method_key'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $details = trim($_POST['account_details'] ?? '');

    if ((float)$wallet['balance'] < $amount) {
        $error = 'Insufficient available balance. You have ' . format_money($wallet['balance']);
    } else {
        $res = submit_withdrawal_request($userId, $methodKey, $amount, $details);
        if ($res['success']) {
            set_flash('success', "Payout request ({$res['withdrawal_ref']}) submitted. Funds have been placed into processing escrow.");
            redirect('/user/transactions.php');
        } else {
            $error = $res['message'];
        }
    }
}

$pageTitle = 'Request Withdrawal — Apex Gaming Platform';
$activeNav = 'wallet';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-8">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-white">Withdrawal Request</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Disburse your winnings to verified cryptocurrency or bank destination</p>
        </div>
        <div class="px-4 py-2 rounded-xl bg-dark-900 border border-white/[0.08] text-right">
            <span class="text-[10px] text-slate-400 uppercase font-semibold block">Available to Withdraw</span>
            <span class="text-base font-bold font-mono text-emerald-400"><?= format_money($wallet['balance']) ?></span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Gateway Selector Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php foreach ($gateways as $gw): ?>
            <a href="/user/withdrawal.php?method=<?= e($gw['method_key']) ?>"
               class="p-4 rounded-2xl border text-center transition-all <?= $selectedKey === $gw['method_key'] ? 'bg-brand-600/15 border-brand-500 text-white shadow-lg shadow-brand-600/20' : 'bg-dark-900 border-white/[0.06] text-slate-400 hover:border-white/[0.15] hover:text-white' ?>">
                <div class="text-xs font-bold block mb-1"><?= e($gw['method_name']) ?></div>
                <div class="text-[10px] text-slate-500 uppercase font-mono"><?= e($gw['method_type']) ?></div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($currentGateway): ?>
        <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
            
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 pb-6 border-b border-white/[0.06]">
                <div>
                    <h3 class="text-base font-bold text-white"><?= e($currentGateway['method_name']) ?></h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Min: <strong class="text-white"><?= format_money($currentGateway['min_withdrawal']) ?></strong> | 
                        Max: <strong class="text-white"><?= format_money($currentGateway['max_withdrawal']) ?></strong>
                    </p>
                </div>
                <div class="px-3 py-1.5 rounded-xl bg-dark-850 border border-white/[0.06] text-xs font-mono text-amber-400">
                    Network/Handling Fee: <?= (float)$currentGateway['withdrawal_fee_percent'] ?>%
                </div>
            </div>

            <!-- Withdrawal Form -->
            <form method="POST" action="/user/withdrawal.php?method=<?= e($selectedKey) ?>" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="method_key" value="<?= e($currentGateway['method_key']) ?>">

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Amount to Withdraw (USD)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-slate-500 font-mono text-xs">$</span>
                        <input type="number" step="0.01" min="<?= (float)$currentGateway['min_withdrawal'] ?>" max="<?= min((float)$currentGateway['max_withdrawal'], (float)$wallet['balance']) ?>"
                               name="amount" required placeholder="50.00"
                               class="w-full pl-8 pr-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs font-mono focus:outline-none focus:border-brand-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Destination Address / Bank Account Details</label>
                    <textarea name="account_details" rows="3" required placeholder="Provide exact cryptocurrency wallet address or recipient bank account name, IBAN/SWIFT..."
                              class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs font-mono focus:outline-none focus:border-brand-500"></textarea>
                    <span class="text-[11px] text-slate-500 mt-1 block">Verify details carefully. Irreversible blockchain transactions cannot be recalled once dispatched.</span>
                </div>

                <div class="p-4 rounded-xl bg-dark-900/60 border border-white/[0.06] text-xs text-slate-400 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Requested amount will be debited into processing escrow immediately to prevent concurrent wager overlap.</span>
                </div>

                <button type="submit" class="w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all">
                    Request Payout Disbursal
                </button>
            </form>

        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
