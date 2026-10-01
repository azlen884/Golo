<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$code = $_GET['code'] ?? '';
$stmt = $pdo->prepare("
    SELECT b.*, r.round_code, r.status as round_status, r.scheduled_start, r.round_end,
           res.rng_seed, res.server_hash, res.verified, res.result_data
    FROM bets b
    JOIN rounds r ON r.id = b.round_id
    LEFT JOIN results res ON res.round_id = r.id
    WHERE b.bet_code = :code AND b.user_id = :uid
    LIMIT 1
");
$stmt->execute([':code' => $code, ':uid' => $userId]);
$bet = $stmt->fetch();

if (!$bet) {
    set_flash('error', 'Wager not found or unauthorized.');
    redirect('/user/my-bets.php');
}

$pageTitle = 'Wager ' . $bet['bet_code'] . ' — Apex Gaming Platform';
$activeNav = 'my-bets';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="/user/my-bets.php" class="text-xs text-brand-400 hover:underline mb-1 inline-block">&larr; Back to My Wagers</a>
            <h1 class="text-2xl font-black text-white">Wager Receipt</h1>
        </div>
        <div>
            <?= render_status_badge($bet['status']) ?>
        </div>
    </div>

    <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
        <div class="text-center py-4 border-b border-white/[0.06]">
            <span class="text-xs text-slate-400 uppercase tracking-wider block mb-1">Settled Outcome Payout</span>
            <div class="text-3xl font-black font-mono <?= (float)$bet['actual_payout'] > 0 ? 'text-emerald-400' : 'text-slate-300' ?>">
                <?= format_money($bet['actual_payout']) ?>
            </div>
            <div class="text-xs font-mono text-slate-500 mt-1"><?= e($bet['bet_code']) ?></div>
        </div>

        <div class="space-y-3 text-xs">
            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                <span class="text-slate-400">Target Round</span>
                <span class="text-white font-mono font-bold"><?= e($bet['round_code']) ?> (<?= render_status_badge($bet['round_status']) ?>)</span>
            </div>
            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                <span class="text-slate-400">Stake Amount</span>
                <span class="text-white font-mono"><?= format_money($bet['amount']) ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                <span class="text-slate-400">Configured Odds</span>
                <span class="text-cyan-400 font-mono font-bold"><?= (float)$bet['odds'] ?>x</span>
            </div>
            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                <span class="text-slate-400">Potential Payout</span>
                <span class="text-slate-200 font-mono"><?= format_money($bet['potential_payout']) ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                <span class="text-slate-400">Time Placed</span>
                <span class="text-slate-300 font-mono"><?= format_date($bet['placed_at'], 'Y-m-d H:i:s') ?></span>
            </div>
            <?php if (!empty($bet['settled_at'])): ?>
                <div class="flex justify-between py-2 border-b border-white/[0.04]">
                    <span class="text-slate-400">Time Settled</span>
                    <span class="text-slate-300 font-mono"><?= format_date($bet['settled_at'], 'Y-m-d H:i:s') ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Provably Fair Verification Card -->
        <?php if (!empty($bet['server_hash'])): ?>
            <div class="p-4 rounded-2xl bg-dark-950 border border-white/[0.06] space-y-2">
                <span class="text-xs font-bold text-white block">Provably Fair Verification</span>
                <div class="space-y-1 text-[11px] font-mono text-slate-400 break-all">
                    <div><strong class="text-slate-300">Server Hash:</strong> <?= e($bet['server_hash']) ?></div>
                    <?php if (!empty($bet['rng_seed'])): ?>
                        <div><strong class="text-slate-300">Public RNG Seed:</strong> <?= e($bet['rng_seed']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
