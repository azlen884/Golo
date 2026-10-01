<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

// Active / open rounds
$openRounds = $pdo->query("
    SELECT * FROM rounds
    WHERE status IN ('open', 'betting_closed', 'processing')
    ORDER BY scheduled_start ASC
")->fetchAll();

// Latest 20 bets in active rounds
$latestBets = $pdo->query("
    SELECT b.*, u.username, r.round_code
    FROM bets b
    JOIN users u ON u.id = b.user_id
    JOIN rounds r ON r.id = b.round_id
    ORDER BY b.placed_at DESC
    LIMIT 20
")->fetchAll();

$pageTitle = 'Live Gaming Operations Monitor';
$activeAdminNav = 'live-monitor';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <div class="inline-flex items-center gap-2 text-xs font-bold text-emerald-400 uppercase tracking-wider mb-1">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                Real-Time Operations Feed
            </div>
            <h1 class="text-2xl font-black text-white">Live Monitor</h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.location.reload();" class="px-4 py-2 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.08] text-xs font-semibold text-slate-200 flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Refresh Stream
            </button>
        </div>
    </div>

    <!-- Live Rounds Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php if (!empty($openRounds)): ?>
            <?php foreach ($openRounds as $r): ?>
                <div class="glass-card p-6 rounded-3xl border-brand-500/30 glow-blue space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs font-bold text-white"><?= e($r['round_code']) ?></span>
                        <?= render_status_badge($r['status']) ?>
                    </div>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Total Wagers</span>
                            <span class="text-white font-mono font-bold"><?= (int)$r['total_bets_count'] ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Active Pool Volume</span>
                            <span class="text-emerald-400 font-mono font-bold"><?= format_money($r['total_pool_amount']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Betting Cutoff</span>
                            <span class="text-slate-200 font-mono"><?= format_date($r['betting_end'], 'H:i:s') ?></span>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-white/[0.06] text-center">
                        <span class="text-[11px] text-slate-400 font-mono">Engine Status: In Progress</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-3 text-center py-10 glass-card rounded-3xl text-slate-500 text-xs">
                No active rounds in play right now. Run master cron or schedule a round in Rounds Management.
            </div>
        <?php endif; ?>
    </div>

    <!-- Live Incoming Bets Feed -->
    <div class="glass-card p-6 rounded-3xl">
        <h3 class="text-sm font-bold text-white mb-4">Live Bets Stream</h3>

        <?php if (!empty($latestBets)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Bet Code</th>
                            <th class="pb-3 font-semibold">Player</th>
                            <th class="pb-3 font-semibold">Round</th>
                            <th class="pb-3 font-semibold">Stake</th>
                            <th class="pb-3 font-semibold">Odds Target</th>
                            <th class="pb-3 font-semibold">Potential Win</th>
                            <th class="pb-3 font-semibold">Time Placed</th>
                            <th class="pb-3 font-semibold text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($latestBets as $b): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono font-medium text-slate-200"><?= e($b['bet_code']) ?></td>
                                <td class="py-3 font-medium text-white"><?= e($b['username']) ?></td>
                                <td class="py-3 font-mono text-slate-400"><?= e($b['round_code']) ?></td>
                                <td class="py-3 font-mono font-bold text-white"><?= format_money($b['amount']) ?></td>
                                <td class="py-3 font-mono text-cyan-400"><?= (float)$b['odds'] ?>x</td>
                                <td class="py-3 font-mono text-slate-300"><?= format_money($b['potential_payout']) ?></td>
                                <td class="py-3 font-mono text-slate-400"><?= format_date($b['placed_at'], 'H:i:s') ?></td>
                                <td class="py-3 text-right"><?= render_status_badge($b['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-10 text-slate-500 text-xs">
                No recent wagers detected in stream.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
