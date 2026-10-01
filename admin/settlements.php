<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/cron.php';

require_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_settlements') {
    verify_csrf_or_abort();
    $summaries = cron_process_settlements();
    if (!empty($summaries)) {
        set_flash('success', 'Settlement cycle complete: ' . implode(' | ', $summaries));
    } else {
        set_flash('info', 'No pending processing rounds were waiting for settlement.');
    }
    redirect('/admin/settlements.php');
}

$sql = "SELECT s.*, r.round_code
        FROM settlements s
        JOIN rounds r ON r.id = s.round_id
        ORDER BY s.id DESC LIMIT 40";
$settlements = $pdo->query($sql)->fetchAll();

$pageTitle = 'Round Settlements';
$activeAdminNav = 'settlements';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Settlements Engine</h1>
            <p class="text-xs text-slate-400 mt-1">Audit log of settled rounds, winner distributions, and platform margins</p>
        </div>
        <form method="POST" action="/admin/settlements.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="run_settlements">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all">
                Run Settlement Cycle Now
            </button>
        </form>
    </div>

    <div class="glass-card p-6 rounded-3xl">
        <?php if (!empty($settlements)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-white/[0.06]">
                            <th class="pb-3 font-semibold">Round Code</th>
                            <th class="pb-3 font-semibold">Winners</th>
                            <th class="pb-3 font-semibold">Losers</th>
                            <th class="pb-3 font-semibold">Total Pool</th>
                            <th class="pb-3 font-semibold">Total Paid</th>
                            <th class="pb-3 font-semibold">Platform Margin</th>
                            <th class="pb-3 font-semibold">Settled By</th>
                            <th class="pb-3 font-semibold text-right">Settled At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <?php foreach ($settlements as $s): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 font-mono font-bold text-white"><?= e($s['round_code']) ?></td>
                                <td class="py-3 font-mono text-emerald-400 font-bold"><?= (int)$s['total_winners'] ?></td>
                                <td class="py-3 font-mono text-rose-400"><?= (int)$s['total_losers'] ?></td>
                                <td class="py-3 font-mono text-white font-bold"><?= format_money($s['total_pool']) ?></td>
                                <td class="py-3 font-mono text-cyan-400 font-bold"><?= format_money($s['total_paid']) ?></td>
                                <td class="py-3 font-mono font-bold <?= (float)$s['platform_margin'] >= 0 ? 'text-emerald-400' : 'text-rose-400' ?>">
                                    <?= format_money($s['platform_margin']) ?>
                                </td>
                                <td class="py-3 font-mono text-slate-400"><?= e($s['settled_by']) ?></td>
                                <td class="py-3 text-right font-mono text-slate-400"><?= format_date($s['settled_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-12 text-slate-500 text-xs">
                No rounds settled yet.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
