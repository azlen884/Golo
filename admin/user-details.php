<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/wallet.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

$userId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT u.*, w.balance, w.bonus_balance, w.locked_balance, w.total_deposited, w.total_withdrawn, w.total_wagered, w.total_won,
           p.first_name, p.last_name, p.phone, p.country, p.city, p.address, p.kyc_status, p.kyc_notes
    FROM users u
    LEFT JOIN wallets w ON w.user_id = u.id
    LEFT JOIN profiles p ON p.user_id = u.id
    WHERE u.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $userId]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    set_flash('error', 'User not found.');
    redirect('/admin/users.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? 'active';
        $up = $pdo->prepare("UPDATE users SET status = :st, updated_at = NOW() WHERE id = :id");
        $up->execute([':st' => $newStatus, ':id' => $userId]);

        log_admin_activity($admin['id'], 'update_user_status', 'users', (string)$userId, "Changed status to {$newStatus}");
        set_flash('success', "Player status updated to {$newStatus}.");
        redirect("/admin/user-details.php?id={$userId}");
    } elseif ($action === 'update_kyc') {
        $kyc = $_POST['kyc_status'] ?? 'unverified';
        $notes = trim($_POST['kyc_notes'] ?? '');
        $up = $pdo->prepare("UPDATE profiles SET kyc_status = :k, kyc_notes = :n, updated_at = NOW() WHERE user_id = :id");
        $up->execute([':k' => $kyc, ':n' => $notes, ':id' => $userId]);

        log_admin_activity($admin['id'], 'update_user_kyc', 'profiles', (string)$userId, "Changed KYC status to {$kyc}");
        set_flash('success', "Player KYC status set to {$kyc}.");
        redirect("/admin/user-details.php?id={$userId}");
    } elseif ($action === 'adjust_balance') {
        $adjType = $_POST['adj_type'] ?? 'credit'; // 'credit' or 'debit'
        $amount = (float)($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Administrative balance adjustment');

        try {
            $ref = generate_reference('ADJ', 8);
            if ($adjType === 'credit') {
                credit_wallet($userId, $amount, 'admin_adjustment', $ref, $reason, 'manual_admin');
            } else {
                debit_wallet($userId, $amount, 'admin_adjustment', $ref, $reason, 'manual_admin');
            }

            log_admin_activity($admin['id'], 'adjust_wallet', 'wallets', (string)$userId, "{$adjType} {$amount} USD: {$reason}");
            set_flash('success', "Successfully adjusted user wallet by {$amount} USD.");
            redirect("/admin/user-details.php?id={$userId}");
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

// Recent transactions for this user
$tStmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = :uid ORDER BY id DESC LIMIT 5");
$tStmt->execute([':uid' => $userId]);
$userTxs = $tStmt->fetchAll();

$pageTitle = 'Player #' . $targetUser['id'] . ' (' . $targetUser['username'] . ')';
$activeAdminNav = 'users';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <a href="/admin/users.php" class="text-xs text-brand-400 hover:underline mb-1 inline-block">&larr; Back to Users List</a>
            <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span><?= e($targetUser['username']) ?></span>
                <span class="text-xs font-mono font-normal text-slate-400">(ID: #<?= $targetUser['id'] ?>)</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <?= render_status_badge($targetUser['status']) ?>
            <?= render_status_badge($targetUser['kyc_status'] ?? 'unverified') ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Balances Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="glass-card p-4 rounded-2xl">
            <span class="text-[11px] text-slate-400 block mb-1">Withdrawable Balance</span>
            <div class="text-2xl font-black text-emerald-400 font-mono"><?= format_money($targetUser['balance']) ?></div>
        </div>
        <div class="glass-card p-4 rounded-2xl">
            <span class="text-[11px] text-slate-400 block mb-1">Total Deposited</span>
            <div class="text-2xl font-black text-white font-mono"><?= format_money($targetUser['total_deposited']) ?></div>
        </div>
        <div class="glass-card p-4 rounded-2xl">
            <span class="text-[11px] text-slate-400 block mb-1">Total Withdrawn</span>
            <div class="text-2xl font-black text-white font-mono"><?= format_money($targetUser['total_withdrawn']) ?></div>
        </div>
        <div class="glass-card p-4 rounded-2xl">
            <span class="text-[11px] text-slate-400 block mb-1">Total Won</span>
            <div class="text-2xl font-black text-cyan-400 font-mono"><?= format_money($targetUser['total_won']) ?></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Account Status & KYC Controls -->
        <div class="glass-card p-6 rounded-3xl space-y-6">
            <div>
                <h3 class="text-sm font-bold text-white mb-3">Account Permissions & Status</h3>
                <form method="POST" action="/admin/user-details.php?id=<?= $userId ?>" class="flex items-center gap-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_status">
                    <select name="status" class="px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
                        <option value="active" <?= $targetUser['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $targetUser['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="banned" <?= $targetUser['status'] === 'banned' ? 'selected' : '' ?>>Banned</option>
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs transition-colors">
                        Update Status
                    </button>
                </form>
            </div>

            <div class="pt-6 border-t border-white/[0.06]">
                <h3 class="text-sm font-bold text-white mb-3">KYC Verification Status</h3>
                <form method="POST" action="/admin/user-details.php?id=<?= $userId ?>" class="space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_kyc">
                    <div>
                        <select name="kyc_status" class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
                            <option value="unverified" <?= ($targetUser['kyc_status'] ?? '') === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                            <option value="pending" <?= ($targetUser['kyc_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                            <option value="verified" <?= ($targetUser['kyc_status'] ?? '') === 'verified' ? 'selected' : '' ?>>Verified</option>
                            <option value="rejected" <?= ($targetUser['kyc_status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div>
                        <textarea name="kyc_notes" rows="2" placeholder="KYC review notes or rejection reason..."
                                  class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs"><?= e($targetUser['kyc_notes'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-dark-800 hover:bg-dark-750 text-slate-200 font-semibold text-xs border border-white/[0.08] transition-colors">
                        Save KYC Decision
                    </button>
                </form>
            </div>
        </div>

        <!-- Direct Balance Adjustment Form -->
        <div class="glass-card p-6 rounded-3xl space-y-4">
            <div>
                <h3 class="text-sm font-bold text-white">Manual Balance Adjustment</h3>
                <p class="text-xs text-slate-400 mt-0.5">Strict double-entry ledger credit or debit</p>
            </div>

            <form method="POST" action="/admin/user-details.php?id=<?= $userId ?>" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="adjust_balance">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">Adjustment Type</label>
                        <select name="adj_type" class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
                            <option value="credit">Credit Balance (+)</option>
                            <option value="debit">Debit Balance (-)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">Amount (USD)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" required placeholder="50.00"
                               class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Mandatory Audit Reason</label>
                    <input type="text" name="reason" required placeholder="e.g. VIP compensation, corrected manual deposit"
                           class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
                </div>

                <button type="submit" onclick="return confirm('Confirm balance adjustment for this user?');"
                        class="w-full py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow-lg shadow-brand-600/30">
                    Execute Ledger Adjustment
                </button>
            </form>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
