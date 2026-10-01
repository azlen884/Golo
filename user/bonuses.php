<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$stmt = $pdo->prepare("SELECT * FROM bonuses WHERE user_id = :uid ORDER BY id DESC");
$stmt->execute([':uid' => $userId]);
$bonuses = $stmt->fetchAll();

$pageTitle = 'My Bonuses — Apex Gaming Platform';
$activeNav = 'bonuses';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-white">Active Bonuses & Rewards</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Track rollover progress and promotional bonus rewards</p>
        </div>
        <a href="/user/promotions.php" class="px-4 py-2 rounded-xl bg-brand-600 text-white font-semibold text-xs">
            Browse All Promotions &rarr;
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php if (!empty($bonuses)): ?>
            <?php foreach ($bonuses as $b): ?>
                <?php
                $wReq = (float)$b['wager_requirement'];
                $wSoFar = (float)$b['wagered_so_far'];
                $pct = $wReq > 0 ? min(100, round(($wSoFar / $wReq) * 100)) : 100;
                ?>
                <div class="glass-card p-6 rounded-3xl space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-brand-400 uppercase tracking-wider"><?= e($b['bonus_type']) ?></span>
                        <?= render_status_badge($b['status']) ?>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white"><?= e($b['title']) ?></h3>
                        <div class="text-2xl font-black text-emerald-400 font-mono mt-1"><?= format_money($b['amount']) ?></div>
                    </div>
                    <div class="space-y-1.5">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-400">Rollover Progress</span>
                            <span class="text-slate-200 font-mono"><?= $pct ?>%</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-dark-950 overflow-hidden">
                            <div class="h-full bg-brand-500 rounded-full" style="width: <?= $pct ?>%"></div>
                        </div>
                        <div class="flex justify-between text-[11px] text-slate-500 font-mono pt-1">
                            <span>Wagered: <?= format_money($wSoFar) ?></span>
                            <span>Target: <?= format_money($wReq) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-2 text-center py-12 glass-card rounded-3xl text-slate-500 text-xs">
                No active bonuses on your account right now. Check our <a href="/user/promotions.php" class="text-brand-400 hover:underline">Promotions page</a> for upcoming offers.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
