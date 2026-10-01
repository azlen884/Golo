<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payments.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];

$gateways = get_active_payment_methods();
$selectedKey = $_GET['method'] ?? ($gateways[0]['method_key'] ?? '');
$currentGateway = get_payment_method($selectedKey);

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $methodKey = trim($_POST['method_key'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $proofPath = null;

    // Handle optional proof file upload
    if (!empty($_FILES['proof']['name']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
        $ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            $error = 'Invalid proof file format. Only JPG, PNG, WEBP, or PDF allowed.';
        } else {
            $filename = 'proof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = UPLOADS_PATH . '/' . $filename;
            if (move_uploaded_file($_FILES['proof']['tmp_name'], $dest)) {
                $proofPath = '/assets/uploads/' . $filename;
            }
        }
    }

    if (!$error) {
        $res = submit_deposit_request($userId, $methodKey, $amount, $proofPath, [
            'submitted_by' => $user['username'],
            'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        if ($res['success']) {
            set_flash('success', "Deposit request ({$res['deposit_ref']}) submitted! Our banking team will verify and credit your wallet.");
            redirect('/user/transactions.php');
        } else {
            $error = $res['message'];
        }
    }
}

$pageTitle = 'Deposit Funds — Apex Gaming Platform';
$activeNav = 'wallet';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-8">
    <div>
        <h1 class="text-2xl sm:text-3xl font-black text-white">Deposit Funds</h1>
        <p class="text-xs sm:text-sm text-slate-400 mt-1">Select an active gateway to fund your player balance</p>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Gateway Selector Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php foreach ($gateways as $gw): ?>
            <a href="/user/deposit.php?method=<?= e($gw['method_key']) ?>"
               class="p-4 rounded-2xl border text-center transition-all <?= $selectedKey === $gw['method_key'] ? 'bg-brand-600/15 border-brand-500 text-white shadow-lg shadow-brand-600/20' : 'bg-dark-900 border-white/[0.06] text-slate-400 hover:border-white/[0.15] hover:text-white' ?>">
                <div class="text-xs font-bold block mb-1"><?= e($gw['method_name']) ?></div>
                <div class="text-[10px] text-slate-500 uppercase font-mono"><?= e($gw['method_type']) ?></div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($currentGateway): ?>
        <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
            
            <!-- Gateway details -->
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 pb-6 border-b border-white/[0.06]">
                <div>
                    <h3 class="text-base font-bold text-white"><?= e($currentGateway['method_name']) ?></h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Min: <strong class="text-white"><?= format_money($currentGateway['min_deposit']) ?></strong> | 
                        Max: <strong class="text-white"><?= format_money($currentGateway['max_deposit']) ?></strong>
                    </p>
                </div>
                <div class="px-3 py-1.5 rounded-xl bg-dark-850 border border-white/[0.06] text-xs font-mono text-emerald-400">
                    Fee: <?= (float)$currentGateway['deposit_fee_percent'] ?>%
                </div>
            </div>

            <!-- Payment instructions & Wallet address -->
            <?php if (!empty($currentGateway['wallet_address'])): ?>
                <div class="p-4 rounded-2xl bg-dark-900 border border-white/[0.06] space-y-2">
                    <span class="text-xs text-slate-400 font-semibold block">Official Platform Address / Beneficiary:</span>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="<?= e($currentGateway['wallet_address']) ?>"
                               class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-xs font-mono text-slate-200 select-all">
                        <button type="button" data-copy="<?= e($currentGateway['wallet_address']) ?>"
                                class="px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold whitespace-nowrap">
                            Copy
                        </button>
                    </div>
                    <?php if (!empty($currentGateway['instructions'])): ?>
                        <p class="text-[11px] text-slate-400 mt-2 leading-relaxed"><?= nl2br(e($currentGateway['instructions'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Deposit Form -->
            <form method="POST" action="/user/deposit.php?method=<?= e($selectedKey) ?>" enctype="multipart/form-data" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="method_key" value="<?= e($currentGateway['method_key']) ?>">

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Amount to Deposit (USD)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-slate-500 font-mono text-xs">$</span>
                        <input type="number" step="0.01" min="<?= (float)$currentGateway['min_deposit'] ?>" max="<?= (float)$currentGateway['max_deposit'] ?>"
                               name="amount" required placeholder="100.00"
                               class="w-full pl-8 pr-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs font-mono focus:outline-none focus:border-brand-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Transaction Proof / Screenshot <span class="text-slate-500 font-normal">(Optional)</span></label>
                    <input type="file" name="proof" accept="image/*,.pdf"
                           class="w-full px-4 py-2 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-600 file:text-white hover:file:bg-brand-500">
                </div>

                <button type="submit" class="w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all">
                    Submit Deposit Request
                </button>
            </form>

        </div>
    <?php else: ?>
        <div class="text-center py-12 glass-card rounded-2xl text-slate-400 text-xs">
            No active payment gateways are currently configured. Please contact support.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
