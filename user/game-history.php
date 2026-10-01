<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/wallet.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$error = null;
$success = null;

// Handle placing a wager on an open round (common gaming infrastructure)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_bet') {
    verify_csrf_or_abort();

    $roundId = (int)($_POST['round_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $targetOdds = (float)($_POST['odds'] ?? 1.5);
    $selectionKey = trim($_POST['selection_key'] ?? 'STANDARD_MULTIPLIER');

    if ($amount < 1.00) {
        $error = 'Minimum wager is ' . format_money(1.00);
    } elseif ($targetOdds < 1.01 || $targetOdds > 100.00) {
        $error = 'Target odds must be between 1.01x and 100.00x';
    } else {
        $pdo->beginTransaction();
        try {
            // Check round is still open
            $rStmt = $pdo->prepare("SELECT * FROM rounds WHERE id = :id FOR UPDATE");
            $rStmt->execute([':id' => $roundId]);
            $round = $rStmt->fetch();

            if (!$round || $round['status'] !== 'open') {
                throw new Exception("This round is no longer open for wagers.");
            }
            if (strtotime($round['betting_end']) <= time()) {
                throw new Exception("Betting cutoff for this round has passed.");
            }

            // Debit user wallet (atomic, checks balance)
            $betCode = generate_reference('BET', 10);
            debit_wallet($userId, $amount, 'bet_placed', $betCode, "Wager on Round {$round['round_code']}", 'round_bet');

            // Insert bet
            $potentialPayout = round($amount * $targetOdds, 4);
            $bIns = $pdo->prepare("
                INSERT INTO bets (bet_code, round_id, user_id, amount, potential_payout, actual_payout, selection_data, status, odds, placed_at)
                VALUES (:code, :rid, :uid, :amt, :pot, 0.0000, :sel, 'pending', :odds, NOW())
            ");
            $bIns->execute([
                ':code' => $betCode,
                ':rid'  => $roundId,
                ':uid'  => $userId,
                ':amt'  => $amount,
                ':pot'  => $potentialPayout,
                ':sel'  => json_encode(['selection' => $selectionKey, 'target_odds' => $targetOdds]),
                ':odds' => $targetOdds
            ]);

            // Update round pool and bet count
            $pdo->prepare("
                UPDATE rounds
                SET total_bets_count = total_bets_count + 1,
                    total_pool_amount = total_pool_amount + :amt,
                    updated_at = NOW()
                WHERE id = :rid
            ")->execute([':amt' => $amount, ':rid' => $roundId]);

            $pdo->commit();
            set_flash('success', "Wager of " . format_money($amount) . " placed on Round {$round['round_code']}! Code: {$betCode}");
            redirect('/user/my-bets.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

// Fetch rounds list
$filterStatus = $_GET['status'] ?? '';
$sql = "SELECT r.*, res.server_hash, res.verified
        FROM rounds r
        LEFT JOIN results res ON res.round_id = r.id";
$params = [];

if (!empty($filterStatus)) {
    $sql .= " WHERE r.status = :st";
    $params[':st'] = $filterStatus;
}
$sql .= " ORDER BY r.id DESC LIMIT 40";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rounds = $stmt->fetchAll();

$pageTitle = 'Rounds & Outcomes — Apex Gaming Platform';
$activeNav = 'game-history';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-white">Platform Rounds</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Autonomous gaming rounds with provably-fair outcomes</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/user/my-bets.php" class="px-4 py-2 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.08] text-slate-200 text-xs font-semibold">
                My Placed Bets &rarr;
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="p-4 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Status Filter Tabs -->
    <div class="flex flex-wrap gap-2">
        <a href="/user/game-history.php" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= empty($filterStatus) ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            All Rounds
        </a>
        <a href="/user/game-history.php?status=open" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $filterStatus === 'open' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Open
        </a>
        <a href="/user/game-history.php?status=scheduled" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $filterStatus === 'scheduled' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Scheduled
        </a>
        <a href="/user/game-history.php?status=completed" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold <?= $filterStatus === 'completed' ? 'bg-brand-600 text-white' : 'bg-dark-900 text-slate-400 hover:text-white border border-white/[0.06]' ?>">
            Completed
        </a>
    </div>

    <!-- Rounds Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($rounds as $r): ?>
            <div class="glass-card p-6 rounded-3xl flex flex-col justify-between relative overflow-hidden">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-mono text-xs font-bold text-white"><?= e($r['round_code']) ?></span>
                        <?= render_status_badge($r['status']) ?>
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Total Bets Placed:</span>
                            <span class="text-white font-mono font-bold"><?= (int)$r['total_bets_count'] ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Active Pool:</span>
                            <span class="text-emerald-400 font-mono font-bold"><?= format_money($r['total_pool_amount']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Scheduled Start:</span>
                            <span class="text-slate-300 font-mono"><?= format_date($r['scheduled_start'], 'H:i:s') ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Betting Cutoff:</span>
                            <span class="text-slate-300 font-mono"><?= format_date($r['betting_end'], 'H:i:s') ?></span>
                        </div>
                        <?php if (!empty($r['server_hash'])): ?>
                            <div class="pt-2 border-t border-white/[0.06]">
                                <span class="text-[10px] text-slate-500 uppercase tracking-wider block">RNG Server Hash</span>
                                <span class="font-mono text-[10px] text-slate-400 truncate block"><?= e($r['server_hash']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Button or Wager Form -->
                <div class="mt-6 pt-4 border-t border-white/[0.06]">
                    <?php if ($r['status'] === 'open'): ?>
                        <details class="group">
                            <summary class="list-none cursor-pointer w-full py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider text-center shadow-lg shadow-brand-600/30 transition-all">
                                Place Wager In Round &darr;
                            </summary>
                            <form method="POST" action="/user/game-history.php" class="mt-4 p-4 rounded-2xl bg-dark-950 border border-white/[0.08] space-y-3">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="place_bet">
                                <input type="hidden" name="round_id" value="<?= $r['id'] ?>">

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Wager Amount ($)</label>
                                    <input type="number" step="0.50" min="1.00" max="<?= (float)$user['balance'] ?>" name="amount" required value="5.00"
                                           class="w-full px-3 py-1.5 rounded-lg bg-dark-900 border border-white/[0.08] text-white text-xs font-mono">
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Target Multiplier / Odds</label>
                                    <input type="number" step="0.05" min="1.05" max="50.00" name="odds" required value="2.00"
                                           class="w-full px-3 py-1.5 rounded-lg bg-dark-900 border border-white/[0.08] text-white text-xs font-mono">
                                </div>

                                <button type="submit" class="w-full py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition-colors">
                                    Confirm Wager
                                </button>
                            </form>
                        </details>
                    <?php else: ?>
                        <div class="text-center py-2 text-xs text-slate-500 font-mono">
                            <?= $r['status'] === 'completed' ? 'Settled • Winnings Distributed' : 'Awaiting Schedule Execution' ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
