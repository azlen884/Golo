<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_gateway') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['method_name'] ?? '');
        $minDep = (float)($_POST['min_deposit'] ?? 10);
        $maxDep = (float)($_POST['max_deposit'] ?? 10000);
        $depFee = (float)($_POST['deposit_fee_percent'] ?? 0);
        $minWith = (float)($_POST['min_withdrawal'] ?? 20);
        $maxWith = (float)($_POST['max_withdrawal'] ?? 5000);
        $withFee = (float)($_POST['withdrawal_fee_percent'] ?? 1);
        $walletAddr = trim($_POST['wallet_address'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $pdo->prepare("
            UPDATE payment_settings
            SET method_name = :name, min_deposit = :mind, max_deposit = :maxd, deposit_fee_percent = :df,
                min_withdrawal = :minw, max_withdrawal = :maxw, withdrawal_fee_percent = :wf,
                wallet_address = :waddr, instructions = :inst, is_active = :act, updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':name'  => $name,
            ':mind'  => $minDep,
            ':maxd'  => $maxDep,
            ':df'    => $depFee,
            ':minw'  => $minWith,
            ':maxw'  => $maxWith,
            ':wf'    => $withFee,
            ':waddr' => $walletAddr,
            ':inst'  => $instructions,
            ':act'   => $isActive,
            ':id'    => $id
        ]);

        log_admin_activity($admin['id'], 'update_payment_method', 'payment_settings', (string)$id, "Updated gateway {$name}");
        set_flash('success', "Payment gateway '{$name}' updated successfully.");
        redirect('/admin/payment-settings.php');
    }
}

$gateways = $pdo->query("SELECT * FROM payment_settings ORDER BY id ASC")->fetchAll();

$pageTitle = 'Payment Gateways & Banking Settings';
$activeAdminNav = 'payment-settings';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Payment Gateway Configuration</h1>
        <p class="text-xs text-slate-400 mt-1">Configure deposit and payout limits, network transaction fees, and receiving crypto addresses</p>
    </div>

    <div class="space-y-6">
        <?php foreach ($gateways as $gw): ?>
            <div class="glass-card p-6 sm:p-8 rounded-3xl">
                <form method="POST" action="/admin/payment-settings.php" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save_gateway">
                    <input type="hidden" name="id" value="<?= $gw['id'] ?>">

                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 pb-4 border-b border-white/[0.06]">
                        <div>
                            <span class="text-[10px] font-mono font-bold text-slate-500 uppercase tracking-wider block mb-0.5"><?= e($gw['method_key']) ?></span>
                            <input type="text" name="method_name" value="<?= e($gw['method_name']) ?>" required
                                   class="text-base font-bold text-white bg-transparent border-b border-white/[0.1] focus:border-brand-500 px-1 py-0.5 focus:outline-none">
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" <?= $gw['is_active'] ? 'checked' : '' ?>
                                   class="w-4 h-4 rounded bg-dark-950 border-white/[0.1] text-brand-600 focus:ring-0">
                            <span class="text-xs font-semibold <?= $gw['is_active'] ? 'text-emerald-400' : 'text-slate-500' ?>">
                                <?= $gw['is_active'] ? 'Gateway Active' : 'Gateway Disabled' ?>
                            </span>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Min Deposit</label>
                            <input type="number" step="0.01" name="min_deposit" value="<?= (float)$gw['min_deposit'] ?>" required
                                   class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Max Deposit</label>
                            <input type="number" step="0.01" name="max_deposit" value="<?= (float)$gw['max_deposit'] ?>" required
                                   class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Min Withdrawal</label>
                            <input type="number" step="0.01" name="min_withdrawal" value="<?= (float)$gw['min_withdrawal'] ?>" required
                                   class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Max Withdrawal</label>
                            <input type="number" step="0.01" name="max_withdrawal" value="<?= (float)$gw['max_withdrawal'] ?>" required
                                   class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Deposit Fee (%)</label>
                            <input type="number" step="0.01" name="deposit_fee_percent" value="<?= (float)$gw['deposit_fee_percent'] ?>" required
                                   class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Withdrawal Fee (%)</label>
                            <input type="number" step="0.01" name="withdrawal_fee_percent" value="<?= (float)$gw['withdrawal_fee_percent'] ?>" required
                                   class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">Receiving Wallet Address / Bank Details</label>
                        <input type="text" name="wallet_address" value="<?= e($gw['wallet_address']) ?>"
                               class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">User Payment Instructions</label>
                        <textarea name="instructions" rows="2"
                                  class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs"><?= e($gw['instructions']) ?></textarea>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow-lg shadow-brand-600/30">
                            Save Gateway Settings
                        </button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
