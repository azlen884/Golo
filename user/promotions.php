<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$pdo = get_db();
$promos = $pdo->query("SELECT * FROM promotions WHERE is_active = 1 ORDER BY id DESC")->fetchAll();

$pageTitle = 'Platform Promotions — Apex Gaming Platform';
$activeNav = 'promotions';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl sm:text-3xl font-black text-white">Promotions & Offers</h1>
        <p class="text-xs sm:text-sm text-slate-400 mt-1">Claim platform deposit matches, weekly cashback, and VIP incentives</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($promos as $promo): ?>
            <div class="glass-card p-6 sm:p-8 rounded-3xl flex flex-col justify-between space-y-6">
                <div>
                    <div class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-brand-500/20 text-brand-400 mb-3">
                        <?= (float)$promo['reward_percent'] > 0 ? (float)$promo['reward_percent'] . '% Deposit Match' : 'VIP Reward' ?>
                    </div>
                    <h2 class="text-xl font-bold text-white mb-2"><?= e($promo['title']) ?></h2>
                    <p class="text-xs text-slate-400 leading-relaxed"><?= e($promo['description']) ?></p>
                </div>

                <div class="pt-4 border-t border-white/[0.06] flex items-center justify-between">
                    <div>
                        <span class="text-[11px] text-slate-500 uppercase block">Min. Qualifying Deposit</span>
                        <span class="text-sm font-bold font-mono text-white"><?= format_money($promo['min_deposit']) ?></span>
                    </div>
                    <a href="/user/deposit.php" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all">
                        Deposit & Activate &rarr;
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
