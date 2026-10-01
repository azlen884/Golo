<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tickets.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $subject = trim($_POST['subject'] ?? '');
    $department = trim($_POST['department'] ?? 'General Support');
    $priority = trim($_POST['priority'] ?? 'medium');
    $message = trim($_POST['message'] ?? '');

    $res = create_support_ticket($userId, $subject, $department, $priority, $message);
    if ($res['success']) {
        set_flash('success', "Ticket #{$res['ticket_code']} opened. Our team will review your inquiry shortly.");
        redirect("/user/ticket.php?id={$res['ticket_id']}");
    } else {
        $error = $res['message'];
    }
}

$pageTitle = 'Open Support Ticket — Apex Gaming Platform';
$activeNav = 'support';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="/user/support.php" class="text-xs text-brand-400 hover:underline mb-1 inline-block">&larr; Back to Tickets</a>
            <h1 class="text-2xl font-black text-white">Create Support Ticket</h1>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="glass-card p-6 sm:p-8 rounded-3xl">
        <form method="POST" action="/user/create-ticket.php" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Subject</label>
                <input type="text" name="subject" required placeholder="Brief summary of your inquiry..."
                       class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Department</label>
                    <select name="department" class="w-full px-3 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                        <option value="General Support">General Support</option>
                        <option value="Deposit & Banking">Deposit & Banking</option>
                        <option value="Withdrawals & KYC">Withdrawals & KYC</option>
                        <option value="Rounds & Betting">Rounds & Betting</option>
                        <option value="Technical Issue">Technical Issue</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Priority</label>
                    <select name="priority" class="w-full px-3 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Message Description</label>
                <textarea name="message" rows="5" required placeholder="Describe the issue, include transaction references or round codes if applicable..."
                          class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500"></textarea>
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-brand-600/30">
                Submit Support Ticket
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
