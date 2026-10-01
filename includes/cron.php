<?php
/**
 * Master Cron Engine & Scheduler Infrastructure
 * Apex Gaming Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/wallet.php';
require_once __DIR__ . '/transactions.php';
require_once __DIR__ . '/notifications.php';

/**
 * Acquire database-backed distributed lock for a cron task.
 *
 * @param string $lockKey
 * @param int $ttlSeconds
 * @return bool
 */
function acquire_cron_lock(string $lockKey, int $ttlSeconds = 300): bool {
    try {
        $pdo = get_db();
        // Clean expired locks
        $clean = $pdo->prepare("DELETE FROM cron_locks WHERE lock_key = :k AND expires_at < NOW()");
        $clean->execute([':k' => $lockKey]);

        $stmt = $pdo->prepare("
            INSERT INTO cron_locks (lock_key, locked_at, expires_at)
            VALUES (:k, NOW(), DATE_ADD(NOW(), INTERVAL :ttl SECOND))
        ");
        return $stmt->execute([':k' => $lockKey, ':ttl' => $ttlSeconds]);
    } catch (Throwable $e) {
        return false; // Lock already held
    }
}

/**
 * Release a previously acquired cron lock.
 *
 * @param string $lockKey
 */
function release_cron_lock(string $lockKey): void {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("DELETE FROM cron_locks WHERE lock_key = :k");
        $stmt->execute([':k' => $lockKey]);
    } catch (Throwable $e) {
        // Silently ignore
    }
}

/**
 * Record cron execution details.
 *
 * @param string $taskName
 * @param string $status
 * @param int $durationMs
 * @param string|null $summary
 * @param string|null $error
 */
function log_cron_execution(string $taskName, string $status, int $durationMs, ?string $summary = null, ?string $error = null): void {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            INSERT INTO cron_logs (task_name, status, execution_time_ms, output_summary, error_message, executed_at)
            VALUES (:name, :status, :ms, :summary, :err, NOW())
        ");
        $stmt->execute([
            ':name'    => $taskName,
            ':status'  => $status,
            ':ms'      => $durationMs,
            ':summary' => $summary,
            ':err'     => $error
        ]);
    } catch (Throwable $e) {
        // Silently ignore
    }
}

/**
 * Common Gaming Infrastructure: Automate Round Lifecycle.
 * Maintains rolling scheduled rounds, transitions open, closes betting, and transitions to processing.
 *
 * @return array
 */
function cron_automate_rounds(): array {
    $pdo = get_db();
    $updates = [];

    // 1. Maintain active rounds pool: Ensure at least 3 upcoming rounds exist
    $cntStmt = $pdo->query("SELECT COUNT(*) FROM rounds WHERE status IN ('scheduled', 'open')");
    $activeCount = (int)$cntStmt->fetchColumn();

    if ($activeCount < 3) {
        for ($i = $activeCount; $i < 3; $i++) {
            $offsetMinutes = ($i * 10) + 1;
            $start = date('Y-m-d H:i:s', strtotime("+{$offsetMinutes} minutes"));
            $bettingEnd = date('Y-m-d H:i:s', strtotime("+{$offsetMinutes} minutes + 7 minutes"));
            $roundEnd = date('Y-m-d H:i:s', strtotime("+{$offsetMinutes} minutes + 8 minutes"));
            $code = 'RND-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $rIns = $pdo->prepare("
                INSERT INTO rounds (round_code, status, total_bets_count, total_pool_amount, total_payout_amount, scheduled_start, betting_end, round_end, created_at)
                VALUES (:code, 'scheduled', 0, 0.0000, 0.0000, :start, :bend, :rend, NOW())
            ");
            $rIns->execute([
                ':code'  => $code,
                ':start' => $start,
                ':bend'  => $bettingEnd,
                ':rend'  => $roundEnd
            ]);
            $updates[] = "Created round {$code}";
        }
    }

    // 2. Scheduled -> Open
    $op = $pdo->prepare("
        UPDATE rounds
        SET status = 'open', updated_at = NOW()
        WHERE status = 'scheduled' AND scheduled_start <= NOW()
    ");
    $op->execute();
    if ($op->rowCount() > 0) {
        $updates[] = "Opened {$op->rowCount()} rounds";
    }

    // 3. Open -> Betting Closed
    $cl = $pdo->prepare("
        UPDATE rounds
        SET status = 'betting_closed', updated_at = NOW()
        WHERE status = 'open' AND betting_end <= NOW()
    ");
    $cl->execute();
    if ($cl->rowCount() > 0) {
        $updates[] = "Closed betting on {$cl->rowCount()} rounds";
    }

    // 4. Betting Closed -> Processing (Generate RNG outcome)
    $procRounds = $pdo->query("
        SELECT id, round_code FROM rounds
        WHERE status = 'betting_closed' AND round_end <= NOW()
    ")->fetchAll();

    foreach ($procRounds as $r) {
        $rid = (int)$r['id'];
        // Generate provably-fair cryptographically secure outcome seed
        $seed = bin2hex(random_bytes(32));
        $serverHash = hash('sha256', $seed . $r['round_code']);
        $rawNumber = hexdec(substr($seed, 0, 8)) % 10000;
        $multiplier = round(1 + ($rawNumber / 1000), 2);

        $outcomeData = json_encode([
            'multiplier'   => $multiplier,
            'metric_score' => $rawNumber,
            'generated_at' => date('Y-m-d H:i:s')
        ]);

        $resStmt = $pdo->prepare("
            INSERT INTO results (round_id, result_data, rng_seed, server_hash, verified, declared_by, declared_at)
            VALUES (:rid, :res, :seed, :hash, 1, 'CRON_RNG', NOW())
            ON DUPLICATE KEY UPDATE result_data = VALUES(result_data), verified = 1
        ");
        $resStmt->execute([
            ':rid'  => $rid,
            ':res'  => $outcomeData,
            ':seed' => $seed,
            ':hash' => $serverHash
        ]);

        $pdo->prepare("UPDATE rounds SET status = 'processing', updated_at = NOW() WHERE id = :id")->execute([':id' => $rid]);
        $updates[] = "Generated result for round {$r['round_code']}";
    }

    return $updates;
}

/**
 * Common Gaming Infrastructure: Settlement Processor.
 * Evaluates pending bets in processing rounds and calculates payouts.
 *
 * @return array
 */
function cron_process_settlements(): array {
    $pdo = get_db();
    $summaries = [];

    // Select rounds ready for settlement
    $stmt = $pdo->query("
        SELECT r.*, res.result_data
        FROM rounds r
        JOIN results res ON res.round_id = r.id
        WHERE r.status = 'processing' AND res.verified = 1
        LIMIT 10
    ");
    $rounds = $stmt->fetchAll();

    foreach ($rounds as $round) {
        $roundId = (int)$round['id'];
        $result = json_encode($round['result_data']);
        $resultObj = json_decode($round['result_data'], true) ?: [];
        $winningMultiplier = (float)($resultObj['multiplier'] ?? 1.5);

        // Fetch all pending bets for this round
        $bStmt = $pdo->prepare("SELECT * FROM bets WHERE round_id = :rid AND status = 'pending' FOR UPDATE");
        $pdo->beginTransaction();

        try {
            $bStmt->execute([':rid' => $roundId]);
            $bets = $bStmt->fetchAll();

            $winnersCount = 0;
            $losersCount = 0;
            $totalPool = 0.0000;
            $totalPaid = 0.0000;

            foreach ($bets as $bet) {
                $betId = (int)$bet['id'];
                $userId = (int)$bet['user_id'];
                $amount = (float)$bet['amount'];
                $odds = (float)$bet['odds'];
                $totalPool += $amount;

                // Win condition: if odds set on bet are less than or equal to round outcome multiplier
                if ($odds <= $winningMultiplier && $odds > 1.0) {
                    $payout = round($amount * $odds, 4);
                    $totalPaid += $payout;
                    $winnersCount++;

                    // Credit user wallet
                    credit_wallet(
                        $userId,
                        $payout,
                        'bet_won',
                        $bet['bet_code'],
                        "Winnings from Round {$round['round_code']} (Odds: {$odds}x)",
                        'round_win'
                    );

                    $upBet = $pdo->prepare("UPDATE bets SET status = 'won', actual_payout = :po, settled_at = NOW() WHERE id = :id");
                    $upBet->execute([':po' => $payout, ':id' => $betId]);

                    send_notification($userId, 'Bet Settled - Won!', "You won " . format_money($payout) . " in Round {$round['round_code']}!", 'success', '/user/my-bets.php');
                } else {
                    $losersCount++;
                    $upBet = $pdo->prepare("UPDATE bets SET status = 'lost', actual_payout = 0.0000, settled_at = NOW() WHERE id = :id");
                    $upBet->execute([':id' => $betId]);
                }
            }

            $platformMargin = round($totalPool - $totalPaid, 4);

            // Record settlement
            $sIns = $pdo->prepare("
                INSERT INTO settlements (round_id, total_winners, total_losers, total_pool, total_paid, platform_margin, settled_by, settled_at)
                VALUES (:rid, :win, :lose, :pool, :paid, :margin, 'CRON_MASTER', NOW())
                ON DUPLICATE KEY UPDATE total_winners = VALUES(total_winners), total_paid = VALUES(total_paid)
            ");
            $sIns->execute([
                ':rid'    => $roundId,
                ':win'    => $winnersCount,
                ':lose'   => $losersCount,
                ':pool'   => $totalPool,
                ':paid'   => $totalPaid,
                ':margin' => $platformMargin
            ]);

            // Mark round completed
            $pdo->prepare("
                UPDATE rounds
                SET status = 'completed', total_bets_count = :cnt, total_pool_amount = :pool, total_payout_amount = :paid, updated_at = NOW()
                WHERE id = :rid
            ")->execute([
                ':cnt'  => count($bets),
                ':pool' => $totalPool,
                ':paid' => $totalPaid,
                ':rid'  => $roundId
            ]);

            $pdo->commit();
            $summaries[] = "Round {$round['round_code']} settled: {$winnersCount} winners, {$losersCount} losers, Payout: " . format_money($totalPaid);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $summaries[] = "Error settling round {$round['round_code']}: " . $e->getMessage();
        }
    }

    return $summaries;
}

/**
 * System cleanup tasks (expired sessions, stale locks).
 *
 * @return array
 */
function cron_system_cleanup(): array {
    $pdo = get_db();
    $reports = [];

    // Delete sessions older than 7 days
    $sess = $pdo->prepare("DELETE FROM user_sessions WHERE last_activity < :t");
    $sess->execute([':t' => time() - (86400 * 7)]);
    if ($sess->rowCount() > 0) {
        $reports[] = "Purged {$sess->rowCount()} expired sessions";
    }

    // Clean cron locks older than 1 hour
    $locks = $pdo->query("DELETE FROM cron_locks WHERE locked_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    if ($locks->rowCount() > 0) {
        $reports[] = "Cleaned {$locks->rowCount()} stale cron locks";
    }

    return $reports;
}
