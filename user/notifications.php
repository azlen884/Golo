<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid OR user_id IS NULL");
        $stmt->execute([':uid' => $userId]);
        set_flash('success', 'All notifications marked as read.');
        redirect('/user/notifications.php');
    } elseif ($action === 'mark_read') {
        $nid = (int)($_POST['notification_id'] ?? 0);
        mark_notification_read($nid, $userId);
        redirect('/user/notifications.php');
    }
}

$notifications = get_user_notifications($userId, 50);

$pageTitle = 'Notifications — Apex Gaming Platform';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-white">Notifications</h1>
            <p class="text-xs text-slate-400 mt-1">Platform announcements, deposit alerts, and payout notices</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <form method="POST" action="/user/notifications.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.08] text-xs font-semibold text-slate-300">
                    Mark All As Read
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="space-y-3">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $n): ?>
                <div class="glass-card p-5 rounded-2xl flex items-start justify-between gap-4 <?= $n['is_read'] ? 'opacity-70' : 'border-brand-500/30' ?>">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-white"><?= e($n['title']) ?></span>
                            <?php if (!$n['is_read']): ?>
                                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed"><?= e($n['message']) ?></p>
                        <div class="flex items-center gap-4 pt-2 text-[11px] text-slate-500 font-mono">
                            <span><?= time_ago($n['created_at']) ?></span>
                            <?php if (!empty($n['action_url'])): ?>
                                <a href="<?= e($n['action_url']) ?>" class="text-brand-400 hover:underline">View Link &rarr;</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$n['is_read']): ?>
                        <form method="POST" action="/user/notifications.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                            <button type="submit" class="text-slate-500 hover:text-white p-1 text-xs">
                                Mark read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-12 glass-card rounded-2xl text-slate-500 text-xs">
                No notifications to display.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
