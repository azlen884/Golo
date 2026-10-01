<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/notifications.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $target = $_POST['target'] ?? 'all';
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = $_POST['type'] ?? 'info';
    $actionUrl = trim($_POST['action_url'] ?? '');

    if ($target === 'all') {
        broadcast_notification($title, $message, $type, $actionUrl ?: null);
        log_admin_activity($admin['id'], 'broadcast_notification', 'notifications', null, "Dispatched broadcast: {$title}");
        set_flash('success', 'Global broadcast notification sent to all active players.');
    } else {
        $username = trim($_POST['target_username'] ?? '');
        $uStmt = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $uStmt->execute([':u' => $username]);
        $uid = $uStmt->fetchColumn();

        if ($uid) {
            send_notification((int)$uid, $title, $message, $type, $actionUrl ?: null);
            log_admin_activity($admin['id'], 'send_user_notification', 'notifications', (string)$uid, "Sent direct notice to {$username}: {$title}");
            set_flash('success', "Targeted notification sent to player {$username}.");
        } else {
            set_flash('error', "User '{$username}' was not found.");
        }
    }
    redirect('/admin/notifications.php');
}

$recentNotifs = $pdo->query("SELECT * FROM notifications ORDER BY id DESC LIMIT 30")->fetchAll();

$pageTitle = 'Player Notifications Desk';
$activeAdminNav = 'notifications';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Notifications Broadcast Center</h1>
        <p class="text-xs text-slate-400 mt-1">Dispatch global announcements or targeted player messages</p>
    </div>

    <!-- Dispatch Form -->
    <div class="glass-card p-6 rounded-3xl">
        <h3 class="text-sm font-bold text-white mb-3">Compose Notification</h3>
        <form method="POST" action="/admin/notifications.php" class="space-y-4">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Target Audience</label>
                    <select name="target" class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
                        <option value="all">All Registered Players (Broadcast)</option>
                        <option value="single">Single Player by Username</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Player Username (if single)</label>
                    <input type="text" name="target_username" placeholder="player1"
                           class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Type / Style</label>
                    <select name="type" class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
                        <option value="info">Information (Blue)</option>
                        <option value="success">Success (Green)</option>
                        <option value="warning">Warning (Amber)</option>
                        <option value="system">System Notice (Cyan)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Title</label>
                <input type="text" name="title" required placeholder="Important update regarding upcoming platform maintenance..."
                       class="w-full px-4 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Message Body</label>
                <textarea name="message" rows="3" required placeholder="Detailed message text..."
                          class="w-full px-4 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs"></textarea>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Action URL <span class="text-slate-500 font-normal">(Optional, e.g. /user/promotions.php)</span></label>
                <input type="text" name="action_url" placeholder="/user/wallet.php"
                       class="w-full px-4 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
            </div>

            <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow-lg shadow-brand-600/30">
                Dispatch Notification
            </button>
        </form>
    </div>

    <!-- Notifications History Table -->
    <div class="glass-card p-6 rounded-3xl">
        <h3 class="text-sm font-bold text-white mb-4">Recent Notifications Log</h3>
        <?php if (!empty($recentNotifs)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Audience</th>
                            <th class="pb-3 font-semibold">Title</th>
                            <th class="pb-3 font-semibold">Type</th>
                            <th class="pb-3 font-semibold">Message</th>
                            <th class="pb-3 font-semibold text-right">Date Dispatched</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($recentNotifs as $rn): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono text-slate-300"><?= $rn['user_id'] ? "User #{$rn['user_id']}" : 'Global Broadcast' ?></td>
                                <td class="py-3 font-medium text-white"><?= e($rn['title']) ?></td>
                                <td class="py-3"><?= render_status_badge($rn['type']) ?></td>
                                <td class="py-3 text-slate-300 max-w-xs truncate"><?= e($rn['message']) ?></td>
                                <td class="py-3 text-right font-mono text-slate-400"><?= format_date($rn['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">No notifications recorded yet.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
