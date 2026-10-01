<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/tickets.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

$ticketId = (int)($_GET['id'] ?? 0);
$tStmt = $pdo->prepare("
    SELECT t.*, u.username, u.email
    FROM support_tickets t
    JOIN users u ON u.id = t.user_id
    WHERE t.id = :id
    LIMIT 1
");
$tStmt->execute([':id' => $ticketId]);
$ticket = $tStmt->fetch();

if (!$ticket) {
    set_flash('error', 'Ticket not found.');
    redirect('/admin/tickets.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';

    if ($action === 'reply') {
        $message = trim($_POST['message'] ?? '');
        if (empty($message)) {
            $error = 'Reply cannot be blank.';
        } else {
            $res = add_ticket_message($ticketId, 'admin', $admin['id'], $message);
            if ($res['success']) {
                log_admin_activity($admin['id'], 'reply_ticket', 'support_tickets', (string)$ticketId, "Replied to ticket #{$ticket['ticket_code']}");
                set_flash('success', 'Reply sent to player.');
                redirect("/admin/ticket-details.php?id={$ticketId}");
            } else {
                $error = $res['message'];
            }
        }
    } elseif ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? 'open';
        $newPri = $_POST['priority'] ?? 'medium';

        $up = $pdo->prepare("UPDATE support_tickets SET status = :st, priority = :pr, updated_at = NOW() WHERE id = :id");
        $up->execute([':st' => $newStatus, ':pr' => $newPri, ':id' => $ticketId]);

        log_admin_activity($admin['id'], 'update_ticket_status', 'support_tickets', (string)$ticketId, "Status: {$newStatus}, Priority: {$newPri}");
        set_flash('success', 'Ticket status updated.');
        redirect("/admin/ticket-details.php?id={$ticketId}");
    }
}

// Fetch messages
$mStmt = $pdo->prepare("SELECT * FROM support_messages WHERE ticket_id = :tid ORDER BY created_at ASC");
$mStmt->execute([':tid' => $ticketId]);
$messages = $mStmt->fetchAll();

$pageTitle = 'Ticket #' . $ticket['ticket_code'];
$activeAdminNav = 'tickets';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <a href="/admin/tickets.php" class="text-xs text-brand-400 hover:underline mb-1 inline-block">&larr; Back to Tickets</a>
            <h1 class="text-xl sm:text-2xl font-black text-white flex items-center gap-2">
                <span><?= e($ticket['subject']) ?></span>
                <span class="text-xs font-mono font-normal text-slate-400">(#<?= e($ticket['ticket_code']) ?>)</span>
            </h1>
            <p class="text-xs text-slate-400">Player: <strong class="text-white"><?= e($ticket['username']) ?></strong> (<?= e($ticket['email']) ?>) • Dept: <?= e($ticket['department']) ?></p>
        </div>
        <div class="flex items-center gap-2">
            <?= render_status_badge($ticket['status']) ?>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-dark-850 text-slate-300 border border-white/[0.08]"><?= e($ticket['priority']) ?></span>
        </div>
    </div>

    <!-- Status & Priority Controls -->
    <div class="glass-card p-4 rounded-2xl flex flex-wrap items-center justify-between gap-4">
        <span class="text-xs font-bold text-slate-300">Quick Status & Priority Modification</span>
        <form method="POST" action="/admin/ticket-details.php?id=<?= $ticketId ?>" class="flex items-center gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_status">
            <select name="status" class="px-3 py-1.5 rounded-lg bg-dark-950 border border-white/[0.08] text-white text-xs">
                <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="answered" <?= $ticket['status'] === 'answered' ? 'selected' : '' ?>>Answered</option>
                <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
            </select>
            <select name="priority" class="px-3 py-1.5 rounded-lg bg-dark-950 border border-white/[0.08] text-white text-xs">
                <option value="low" <?= $ticket['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                <option value="medium" <?= $ticket['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" <?= $ticket['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                <option value="urgent" <?= $ticket['priority'] === 'urgent' ? 'selected' : '' ?>>Urgent</option>
            </select>
            <button type="submit" class="px-3 py-1.5 rounded-lg bg-dark-800 hover:bg-dark-750 text-white font-semibold text-xs border border-white/[0.08]">
                Update
            </button>
        </form>
    </div>

    <!-- Messages Thread -->
    <div class="space-y-4">
        <?php foreach ($messages as $msg): ?>
            <div class="p-6 rounded-3xl <?= $msg['sender_type'] === 'admin' ? 'bg-brand-950/40 border border-brand-500/20 ml-6' : 'glass-card mr-6' ?>">
                <div class="flex items-center justify-between mb-3 text-xs">
                    <span class="font-bold <?= $msg['sender_type'] === 'admin' ? 'text-brand-400' : 'text-slate-200' ?>">
                        <?= $msg['sender_type'] === 'admin' ? 'Operator (' . e($admin['username']) . ')' : e($ticket['username']) ?>
                    </span>
                    <span class="text-slate-500 font-mono"><?= format_date($msg['created_at']) ?></span>
                </div>
                <div class="text-xs sm:text-sm text-slate-300 leading-relaxed whitespace-pre-wrap">
                    <?= e($msg['message']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Admin Reply Form -->
    <div class="glass-card p-6 rounded-3xl">
        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3">Post Staff Response</h3>
        <?php if ($error): ?>
            <div class="mb-4 p-3 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="/admin/ticket-details.php?id=<?= $ticketId ?>" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reply">
            <textarea name="message" rows="4" required placeholder="Type official response to player..."
                      class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500"></textarea>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow-lg shadow-brand-600/30">
                Dispatch Staff Reply
            </button>
        </form>
    </div>
</div>

</body>
</html>
