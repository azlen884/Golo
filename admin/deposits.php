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
    $depositId = (int)($_POST['deposit_id'] ?? 0);
    $adminNote = trim($_POST['admin_note'] ?? '');

    if ($action === 'approve') {
        $res = approve_deposit($depositId, $admin['id'], $adminNote);
        if ($res['success']) {
            log_admin_activity($admin['id'], 'approve_deposit', 'deposits', (string)$depositId, 'Approved deposit and credited wallet');
            set_flash('success', $res['message']);
        } else {
            set_flash('error', $res['message']);
        }
    } elseif ($action === 'reject') {
        $res = reject_deposit($depositId, $admin['id'], $adminNote);
        if ($res['success']) {
            log_admin_activity($admin['id'], 'reject_deposit', 'deposits', (string)$depositId, "Rejected deposit: {$adminNote}");
            set_flash('success', $res['message']);
        } else {
            set_flash('error', $res['message']);
        }
    }
    redirect('/admin/deposits.php');
}

$status = $_GET['status'] ?? '';
$sql = "SELECT d.*, u.username, u.email
        FROM deposits d
        JOIN users u ON u.id = d.user_id";
$params = [];
if (!empty($status)) {
    $sql .= " WHERE d.status = :st";
    $params[':st'] = $status;
}
$sql .= " ORDER BY d.id DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$deposits = $stmt->fetchAll();

$pageTitle = 'Player Deposits Desk';
$activeAdminNav = 'deposits';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Deposits Management</h1>
            <p class="text-xs text-slate-400 mt-1">Audit, verify, and approve incoming player payments</p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex gap-2">
        <a href="/admin/deposits.php" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= empty($status) ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            All Deposits
        </a>
        <a href="/admin/deposits.php?status=pending" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'pending' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Pending Verification
        </a>
        <a href="/admin/deposits.php?status=approved" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'approved' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Approved
        </a>
        <a href="/admin/deposits.php?status=rejected" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'rejected' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Rejected
        </a>
    </div>

    <!-- Deposits Table -->
    <div class="glass-card p-6 rounded-3xl">
        <?php if (!empty($deposits)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Reference</th>
                            <th class="pb-3 font-semibold">Player</th>
                            <th class="pb-3 font-semibold">Method</th>
                            <th class="pb-3 font-semibold">Gross Amount</th>
                            <th class="pb-3 font-semibold">Proof</th>
                            <th class="pb-3 font-semibold">Date</th>
                            <th class="pb-3 font-semibold">Status</th>
                            <th class="pb-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($deposits as $d): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono font-medium text-slate-200"><?= e($d['deposit_ref']) ?></td>
                                <td class="py-3">
                                    <a href="/admin/user-details.php?id=<?= $d['user_id'] ?>" class="font-bold text-white hover:text-brand-400">
                                        <?= e($d['username']) ?>
                                    </a>
                                </td>
                                <td class="py-3 text-slate-300"><?= e($d['payment_method']) ?></td>
                                <td class="py-3 font-mono font-bold text-emerald-400"><?= format_money($d['amount']) ?></td>
                                <td class="py-3">
                                    <?php if (!empty($d['payment_proof'])): ?>
                                        <a href="<?= e($d['payment_proof']) ?>" target="_blank" class="text-brand-400 hover:underline font-semibold">View Receipt &rarr;</a>
                                    <?php else: ?>
                                        <span class="text-slate-500">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 font-mono text-slate-400"><?= format_date($d['created_at']) ?></td>
                                <td class="py-3"><?= render_status_badge($d['status']) ?></td>
                                <td class="py-3 text-right">
                                    <?php if ($d['status'] === 'pending'): ?>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <form method="POST" action="/admin/deposits.php" class="inline" onsubmit="return confirm('Approve deposit and credit user wallet?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="deposit_id" value="<?= $d['id'] ?>">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-[11px]">
                                                    Approve
                                                </button>
                                            </form>
                                            <form method="POST" action="/admin/deposits.php" class="inline" onsubmit="return confirm('Reject deposit?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="deposit_id" value="<?= $d['id'] ?>">
                                                <input type="hidden" name="admin_note" value="Transaction unverified">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 font-semibold text-[11px]">
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-500 text-[11px] font-mono">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">
                No deposit records found.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
