<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/payments.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';
    $withdrawalId = (int)($_POST['withdrawal_id'] ?? 0);
    $adminNote = trim($_POST['admin_note'] ?? '');

    if ($action === 'approve') {
        $res = approve_withdrawal($withdrawalId, $admin['id'], $adminNote);
        if ($res['success']) {
            log_admin_activity($admin['id'], 'approve_withdrawal', 'withdrawals', (string)$withdrawalId, 'Approved payout disbursal');
            set_flash('success', $res['message']);
        } else {
            set_flash('error', $res['message']);
        }
    } elseif ($action === 'reject') {
        $res = reject_withdrawal($withdrawalId, $admin['id'], $adminNote);
        if ($res['success']) {
            log_admin_activity($admin['id'], 'reject_withdrawal', 'withdrawals', (string)$withdrawalId, "Rejected withdrawal and refunded balance: {$adminNote}");
            set_flash('success', $res['message']);
        } else {
            set_flash('error', $res['message']);
        }
    }
    redirect('/admin/withdrawals.php');
}

$status = $_GET['status'] ?? '';
$sql = "SELECT w.*, u.username, u.email
        FROM withdrawals w
        JOIN users u ON u.id = w.user_id";
$params = [];
if (!empty($status)) {
    $sql .= " WHERE w.status = :st";
    $params[':st'] = $status;
}
$sql .= " ORDER BY w.id DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$withdrawals = $stmt->fetchAll();

$pageTitle = 'Player Withdrawals Desk';
$activeAdminNav = 'withdrawals';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Withdrawals Desk</h1>
        <p class="text-xs text-slate-400 mt-1">Review player payout requests, verify recipient accounts, and authorize funds</p>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2">
        <a href="/admin/withdrawals.php" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= empty($status) ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            All Withdrawals
        </a>
        <a href="/admin/withdrawals.php?status=pending" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'pending' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Pending Approval
        </a>
        <a href="/admin/withdrawals.php?status=approved" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'approved' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Approved
        </a>
        <a href="/admin/withdrawals.php?status=rejected" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'rejected' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Rejected / Refunded
        </a>
    </div>

    <!-- Withdrawals Table -->
    <div class="glass-card p-6 rounded-3xl">
        <?php if (!empty($withdrawals)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Reference</th>
                            <th class="pb-3 font-semibold">Player</th>
                            <th class="pb-3 font-semibold">Method</th>
                            <th class="pb-3 font-semibold">Amount</th>
                            <th class="pb-3 font-semibold">Destination Details</th>
                            <th class="pb-3 font-semibold">Date</th>
                            <th class="pb-3 font-semibold">Status</th>
                            <th class="pb-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($withdrawals as $w): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono font-medium text-slate-200"><?= e($w['withdrawal_ref']) ?></td>
                                <td class="py-3 font-medium text-white">
                                    <a href="/admin/user-details.php?id=<?= $w['user_id'] ?>" class="hover:text-brand-400">
                                        <?= e($w['username']) ?>
                                    </a>
                                </td>
                                <td class="py-3 text-slate-300"><?= e($w['payment_method']) ?></td>
                                <td class="py-3 font-mono font-bold text-white"><?= format_money($w['amount']) ?></td>
                                <td class="py-3 font-mono text-[11px] text-slate-300 max-w-xs truncate" title="<?= e($w['account_details']) ?>">
                                    <?= e($w['account_details']) ?>
                                </td>
                                <td class="py-3 font-mono text-slate-400"><?= format_date($w['created_at']) ?></td>
                                <td class="py-3"><?= render_status_badge($w['status']) ?></td>
                                <td class="py-3 text-right">
                                    <?php if ($w['status'] === 'pending'): ?>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <form method="POST" action="/admin/withdrawals.php" class="inline" onsubmit="return confirm('Approve and mark payout dispatched?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-[11px]">
                                                    Disburse
                                                </button>
                                            </form>
                                            <form method="POST" action="/admin/withdrawals.php" class="inline" onsubmit="return confirm('Reject withdrawal and return funds to player balance?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                                <input type="hidden" name="admin_note" value="Address check failed">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 font-semibold text-[11px]">
                                                    Reject & Refund
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-[11px] font-mono">Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">
                No withdrawal records found.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
