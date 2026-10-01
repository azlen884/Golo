<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/cron.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

$error = null;

// Handle manual round creation or state force
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_round') {
        $minutesStart = max(1, (int)($_POST['start_delay_minutes'] ?? 2));
        $bettingDuration = max(1, (int)($_POST['betting_duration_minutes'] ?? 5));
        $roundDuration = max($bettingDuration + 1, (int)($_POST['round_duration_minutes'] ?? 6));

        $start = date('Y-m-d H:i:s', strtotime("+{$minutesStart} minutes"));
        $bettingEnd = date('Y-m-d H:i:s', strtotime("+{$minutesStart} minutes + {$bettingDuration} minutes"));
        $roundEnd = date('Y-m-d H:i:s', strtotime("+{$minutesStart} minutes + {$roundDuration} minutes"));
        $code = 'RND-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $stmt = $pdo->prepare("
            INSERT INTO rounds (round_code, status, total_bets_count, total_pool_amount, total_payout_amount, scheduled_start, betting_end, round_end, created_at)
            VALUES (:code, 'scheduled', 0, 0.0000, 0.0000, :st, :bend, :rend, NOW())
        ");
        $stmt->execute([
            ':code' => $code,
            ':st'   => $start,
            ':bend' => $bettingEnd,
            ':rend' => $roundEnd
        ]);

        log_admin_activity($admin['id'], 'create_round', 'rounds', $code, "Manually created round {$code}");
        set_flash('success', "Round {$code} scheduled successfully.");
        redirect('/admin/rounds.php');
    } elseif ($action === 'cancel_round') {
        $roundId = (int)($_POST['round_id'] ?? 0);
        // Refund bets
        $pdo->beginTransaction();
        try {
            $bStmt = $pdo->prepare("SELECT * FROM bets WHERE round_id = :rid AND status = 'pending' FOR UPDATE");
            $bStmt->execute([':rid' => $roundId]);
            $pendingBets = $bStmt->fetchAll();

            foreach ($pendingBets as $pb) {
                credit_wallet(
                    (int)$pb['user_id'],
                    $pb['amount'],
                    'bet_refund',
                    $pb['bet_code'],
                    "Refund for cancelled round",
                    'round_refund'
                );
                $pdo->prepare("UPDATE bets SET status = 'refunded', settled_at = NOW() WHERE id = :id")->execute([':id' => $pb['id']]);
            }

            $pdo->prepare("UPDATE rounds SET status = 'cancelled', updated_at = NOW() WHERE id = :rid")->execute([':rid' => $roundId]);
            $pdo->commit();

            log_admin_activity($admin['id'], 'cancel_round', 'rounds', (string)$roundId, "Cancelled round and refunded all pending wagers");
            set_flash('success', "Round cancelled and wagers refunded.");
            redirect('/admin/rounds.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

// Fetch rounds list
$status = $_GET['status'] ?? '';
$sql = "SELECT r.*, res.rng_seed, res.server_hash
        FROM rounds r
        LEFT JOIN results res ON res.round_id = r.id";
$params = [];
if (!empty($status)) {
    $sql .= " WHERE r.status = :st";
    $params[':st'] = $status;
}
$sql .= " ORDER BY r.id DESC LIMIT 40";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$roundsList = $stmt->fetchAll();

$pageTitle = 'Rounds Lifecycle Management';
$activeAdminNav = 'rounds';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Rounds Management</h1>
            <p class="text-xs text-slate-400 mt-1">Schedule, monitor, and manage the common gaming rounds lifecycle</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/live-monitor.php" class="px-4 py-2 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.08] text-brand-400 text-xs font-semibold">
                Live Monitor &rarr;
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Schedule New Round Card -->
    <div class="glass-card p-6 rounded-3xl">
        <h3 class="text-sm font-bold text-white mb-3">Schedule New Round Manually</h3>
        <form method="POST" action="/admin/rounds.php" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_round">

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Start Delay (Minutes)</label>
                <input type="number" name="start_delay_minutes" value="1" min="1" max="1440" required
                       class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Betting Window (Minutes)</label>
                <input type="number" name="betting_duration_minutes" value="5" min="1" max="1440" required
                       class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Total Round Duration</label>
                <input type="number" name="round_duration_minutes" value="6" min="2" max="1440" required
                       class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
            </div>

            <button type="submit" class="w-full py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-brand-600/30 transition-all">
                + Schedule Round
            </button>
        </form>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2">
        <a href="/admin/rounds.php" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= empty($status) ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            All
        </a>
        <a href="/admin/rounds.php?status=scheduled" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'scheduled' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Scheduled
        </a>
        <a href="/admin/rounds.php?status=open" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'open' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Open
        </a>
        <a href="/admin/rounds.php?status=betting_closed" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'betting_closed' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Betting Closed
        </a>
        <a href="/admin/rounds.php?status=processing" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'processing' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Processing
        </a>
        <a href="/admin/rounds.php?status=completed" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $status === 'completed' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Completed
        </a>
    </div>

    <!-- Rounds Table -->
    <div class="glass-card p-6 rounded-3xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-white/[0.06]">
                        <th class="pb-3 font-semibold">Round Code</th>
                        <th class="pb-3 font-semibold">Status</th>
                        <th class="pb-3 font-semibold">Bets Placed</th>
                        <th class="pb-3 font-semibold">Active Pool</th>
                        <th class="pb-3 font-semibold">Total Payout</th>
                        <th class="pb-3 font-semibold">Scheduled Start</th>
                        <th class="pb-3 font-semibold">Betting Cutoff</th>
                        <th class="pb-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    <?php foreach ($roundsList as $r): ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="py-3 font-mono font-bold text-white"><?= e($r['round_code']) ?></td>
                            <td class="py-3"><?= render_status_badge($r['status']) ?></td>
                            <td class="py-3 font-mono"><?= (int)$r['total_bets_count'] ?></td>
                            <td class="py-3 font-mono font-bold text-emerald-400"><?= format_money($r['total_pool_amount']) ?></td>
                            <td class="py-3 font-mono font-bold text-cyan-400"><?= format_money($r['total_payout_amount']) ?></td>
                            <td class="py-3 font-mono text-slate-300"><?= format_date($r['scheduled_start'], 'H:i:s') ?></td>
                            <td class="py-3 font-mono text-slate-300"><?= format_date($r['betting_end'], 'H:i:s') ?></td>
                            <td class="py-3 text-right">
                                <?php if ($r['status'] === 'open' || $r['status'] === 'scheduled'): ?>
                                    <form method="POST" action="/admin/rounds.php" class="inline" onsubmit="return confirm('Cancel round and refund all player bets?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel_round">
                                        <input type="hidden" name="round_id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="px-2.5 py-1 rounded bg-rose-600/10 hover:bg-rose-600/20 text-rose-400 text-xs font-semibold">
                                            Cancel
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="/admin/results.php?round_id=<?= $r['id'] ?>" class="text-brand-400 hover:underline">Results &rarr;</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
