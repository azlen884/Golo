<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tickets.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$ticketId = (int)($_GET['id'] ?? 0);
$tStmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = :id AND user_id = :uid LIMIT 1");
$tStmt->execute([':id' => $ticketId, ':uid' => $userId]);
$ticket = $tStmt->fetch();

if (!$ticket) {
    set_flash('error', 'Ticket not found or unauthorized.');
    redirect('/user/support.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $reply = trim($_POST['reply'] ?? '');
    if (empty($reply)) {
        $error = 'Reply message cannot be blank.';
    } else {
        $res = add_ticket_message($ticketId, 'user', $userId, $reply);
        if ($res['success']) {
            set_flash('success', 'Your reply has been posted.');
            redirect("/user/ticket.php?id={$ticketId}");
        } else {
            $error = $res['message'];
        }
    }
}

// Fetch messages
$mStmt = $pdo->prepare("SELECT * FROM support_messages WHERE ticket_id = :tid ORDER BY created_at ASC");
$mStmt->execute([':tid' => $ticketId]);
$messages = $mStmt->fetchAll();

$pageTitle = 'Ticket #' . $ticket['ticket_code'] . ' — Apex Gaming Platform';
$activeNav = 'support';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <a href="/user/support.php" class="text-xs text-brand-400 hover:underline mb-1 inline-block">&larr; Back to Tickets</a>
            <h1 class="text-xl sm:text-2xl font-black text-white flex items-center gap-2">
                <span><?= e($ticket['subject']) ?></span>
                <span class="font-mono text-xs text-slate-400 font-normal">#<?= e($ticket['ticket_code']) ?></span>
            </h1>
        </div>
        <div>
            <?= render_status_badge($ticket['status']) ?>
        </div>
    </div>

    <!-- Messages List -->
    <div class="space-y-4">
        <?php foreach ($messages as $msg): ?>
            <div class="p-6 rounded-3xl <?= $msg['sender_type'] === 'admin' ? 'bg-brand-950/40 border border-brand-500/20' : 'glass-card' ?>">
                <div class="flex items-center justify-between mb-3 text-xs">
                    <span class="font-bold <?= $msg['sender_type'] === 'admin' ? 'text-brand-400' : 'text-slate-200' ?>">
                        <?= $msg['sender_type'] === 'admin' ? 'Staff Operator' : e($user['username']) ?>
                    </span>
                    <span class="text-slate-500 font-mono"><?= format_date($msg['created_at']) ?></span>
                </div>
                <div class="text-xs sm:text-sm text-slate-300 leading-relaxed whitespace-pre-wrap">
                    <?= e($msg['message']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Reply Box -->
    <?php if ($ticket['status'] !== 'closed'): ?>
        <div class="glass-card p-6 rounded-3xl">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3">Add Response</h3>
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="/user/ticket.php?id=<?= $ticketId ?>" class="space-y-4">
                <?= csrf_field() ?>
                <textarea name="reply" rows="4" required placeholder="Type your follow-up message..."
                          class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500"></textarea>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all">
                    Send Reply
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="p-4 rounded-2xl bg-dark-900 border border-white/[0.06] text-center text-xs text-slate-400">
            This support ticket has been closed. If you require further assistance, please open a new ticket.
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
