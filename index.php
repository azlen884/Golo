<?php
/**
 * Public Landing Page
 * Apex Gaming Platform
 */

$pageTitle = 'Apex Gaming Platform — Fair & Deterministic Gaming Infrastructure';
$activeNav = 'home';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();

// Real database metrics (no hardcoded fake statistics)
$totalRoundsCount = (int)$pdo->query("SELECT COUNT(*) FROM rounds WHERE status = 'completed'")->fetchColumn();
$activePromos = $pdo->query("SELECT * FROM promotions WHERE is_active = 1 LIMIT 3")->fetchAll();
$activeGateways = $pdo->query("SELECT method_name, method_type, min_deposit, max_deposit FROM payment_settings WHERE is_active = 1 LIMIT 4")->fetchAll();

// Active / upcoming rounds preview
$liveRounds = $pdo->query("
    SELECT * FROM rounds
    WHERE status IN ('open', 'scheduled')
    ORDER BY scheduled_start ASC
    LIMIT 3
")->fetchAll();
?>

<!-- Hero Section -->
<section class="relative pt-12 pb-20 overflow-hidden">
    <!-- Background Glow -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[350px] bg-brand-600/15 rounded-full blur-[140px] pointer-events-none -z-10"></div>

    <div class="text-center max-w-3xl mx-auto space-y-6">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-600/10 border border-brand-500/20 text-brand-400 text-xs font-semibold uppercase tracking-wider">
            <span class="w-2 h-2 rounded-full bg-brand-400 animate-pulse"></span>
            Provably Fair Gaming Infrastructure
        </div>

        <h1 class="text-4xl sm:text-6xl font-black text-white tracking-tight leading-[1.1]">
            Next-Gen Gaming Engine Built on <span class="bg-gradient-to-r from-blue-400 via-brand-500 to-indigo-400 bg-clip-text text-transparent">True Integrity</span>
        </h1>

        <p class="text-base sm:text-lg text-slate-400 leading-relaxed max-w-2xl mx-auto">
            A high-throughput gaming foundation with real-time round automation, cryptographic RNG verification, and atomic double-entry financial ledgers.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
            <?php if ($authUser): ?>
                <a href="/user/dashboard.php" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-xl shadow-brand-600/30 transition-all text-center">
                    Enter Player Dashboard &rarr;
                </a>
                <a href="/user/wallet.php" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-dark-850 hover:bg-dark-800 border border-white/[0.08] text-slate-200 font-semibold text-sm transition-all text-center">
                    Manage Wallet
                </a>
            <?php else: ?>
                <a href="/user/register.php" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-xl shadow-brand-600/30 transition-all text-center">
                    Create Player Account &rarr;
                </a>
                <a href="/pages/how-it-works.php" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-dark-850 hover:bg-dark-800 border border-white/[0.08] text-slate-200 font-semibold text-sm transition-all text-center">
                    Explore Architecture
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Live Rounds Infrastructure Preview -->
<section class="py-12 border-t border-white/[0.06]">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="inline-flex items-center gap-2 text-xs font-semibold text-brand-400 uppercase tracking-wider mb-1">
                Automated Engine
            </div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Active Gaming Rounds</h2>
            <p class="text-xs text-slate-400">Rounds managed continuously via decentralized scheduler</p>
        </div>
        <a href="<?= $authUser ? '/user/game-history.php' : '/user/login.php' ?>" class="text-xs font-semibold text-brand-400 hover:text-brand-300 flex items-center gap-1">
            View All Rounds &rarr;
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php if (!empty($liveRounds)): ?>
            <?php foreach ($liveRounds as $round): ?>
                <div class="glass-card p-6 rounded-2xl relative overflow-hidden">
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-mono text-xs text-slate-400 font-bold"><?= e($round['round_code']) ?></span>
                        <?= render_status_badge($round['status']) ?>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-400">Scheduled Start</span>
                            <span class="text-slate-200 font-mono"><?= format_date($round['scheduled_start'], 'H:i:s') ?></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-400">Betting Cutoff</span>
                            <span class="text-slate-200 font-mono"><?= format_date($round['betting_end'], 'H:i:s') ?></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-400">Active Pool</span>
                            <span class="text-emerald-400 font-mono font-bold"><?= format_money($round['total_pool_amount']) ?></span>
                        </div>
                    </div>
                    <div class="mt-6 pt-4 border-t border-white/[0.06]">
                        <a href="<?= $authUser ? '/user/game-history.php' : '/user/login.php' ?>" class="block text-center py-2 rounded-xl bg-brand-600/10 hover:bg-brand-600/20 text-brand-400 font-semibold text-xs transition-colors">
                            <?= $round['status'] === 'open' ? 'Join Active Pool' : 'Round Details' ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-3 text-center py-10 bg-dark-900 border border-white/[0.06] rounded-2xl text-slate-400 text-xs">
                No active rounds currently waiting. The automated master scheduler will spawn new rounds momentarily.
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Platform Architecture Pillars -->
<section class="py-16 border-t border-white/[0.06]">
    <div class="text-center max-w-2xl mx-auto mb-12">
        <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Core Infrastructure Standards</h2>
        <p class="text-xs sm:text-sm text-slate-400 mt-2">Every transaction, outcome, and settlement is strictly verifiable.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="glass-card p-6 rounded-2xl">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <h3 class="text-base font-bold text-white mb-2">Cryptographic RNG</h3>
            <p class="text-xs text-slate-400 leading-relaxed">Provably-fair outcomes generated using combined client-server seed hashes, verifiable by players post-settlement.</p>
        </div>

        <div class="glass-card p-6 rounded-2xl">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-base font-bold text-white mb-2">Atomic Ledger</h3>
            <p class="text-xs text-slate-400 leading-relaxed">Financial integrity protected via row-level locks, avoiding floating-point math and preserving strict audit records.</p>
        </div>

        <div class="glass-card p-6 rounded-2xl">
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <h3 class="text-base font-bold text-white mb-2">Master Automation</h3>
            <p class="text-xs text-slate-400 leading-relaxed">Idempotent background scheduler handling automated round transitions, prize distribution, and transaction settlement.</p>
        </div>

        <div class="glass-card p-6 rounded-2xl">
            <div class="w-12 h-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </div>
            <h3 class="text-base font-bold text-white mb-2">Affiliate Engine</h3>
            <p class="text-xs text-slate-400 leading-relaxed">Multi-tier commission tracking with immediate automatic credits upon player participation and deposit execution.</p>
        </div>
    </div>
</section>

<!-- Active Promotions (Dynamic from MySQL) -->
<?php if (!empty($activePromos)): ?>
<section class="py-12 border-t border-white/[0.06]">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white tracking-tight">Active Platform Promotions</h2>
        <p class="text-xs text-slate-400">Exclusive incentive programs configured in our database</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($activePromos as $promo): ?>
            <div class="glass-card p-6 rounded-2xl flex flex-col justify-between">
                <div>
                    <div class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-brand-500/20 text-brand-400 mb-3">
                        <?= (float)$promo['reward_percent'] > 0 ? (float)$promo['reward_percent'] . '% Bonus' : 'Promotion' ?>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2"><?= e($promo['title']) ?></h3>
                    <p class="text-xs text-slate-400 leading-relaxed"><?= e($promo['description']) ?></p>
                </div>
                <div class="mt-6 pt-4 border-t border-white/[0.06] flex items-center justify-between">
                    <span class="text-xs text-slate-400">Min. Deposit: <strong class="text-white"><?= format_money($promo['min_deposit']) ?></strong></span>
                    <a href="<?= $authUser ? '/user/deposit.php' : '/user/register.php' ?>" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold shadow-md shadow-brand-600/20 transition-all">
                        Claim Offer &rarr;
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Payment Gateways Available (Dynamic from MySQL) -->
<?php if (!empty($activeGateways)): ?>
<section class="py-12 border-t border-white/[0.06]">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white tracking-tight">Integrated Settlement Methods</h2>
        <p class="text-xs text-slate-400">Active banking and cryptocurrency payment gateways</p>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <?php foreach ($activeGateways as $gw): ?>
            <div class="glass-card p-4 rounded-xl text-center">
                <div class="text-xs font-bold text-white mb-1"><?= e($gw['method_name']) ?></div>
                <div class="text-[10px] text-slate-400 uppercase font-mono tracking-wider"><?= e($gw['method_type']) ?></div>
                <div class="mt-3 text-[11px] text-slate-300">Min: <?= format_money($gw['min_deposit']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Responsible Gaming CTA -->
<section class="py-12 border-t border-white/[0.06] mb-8">
    <div class="p-8 rounded-3xl bg-gradient-to-r from-dark-900 to-dark-850 border border-white/[0.08] flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="space-y-2">
            <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">Player Protection</span>
            <h3 class="text-xl font-bold text-white">Committed to Fair & Responsible Gaming</h3>
            <p class="text-xs text-slate-400 max-w-xl">
                We advocate safe entertainment. Set deposit limits, schedule cool-off timeouts, or self-exclude whenever necessary.
            </p>
        </div>
        <a href="/pages/responsible-gaming.php" class="px-6 py-3 rounded-xl bg-dark-800 hover:bg-dark-750 border border-white/[0.1] text-xs font-semibold text-white whitespace-nowrap transition-colors">
            Read Policy & Support
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
