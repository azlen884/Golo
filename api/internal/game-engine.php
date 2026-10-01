<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/wallet.php';
require_once __DIR__ . '/../../includes/transactions.php';
require_once __DIR__ . '/../../includes/notifications.php';

// Verify Internal API authorization
$headers = getallheaders();
$apiKey = $headers['X-Internal-Secret'] ?? $headers['x-internal-secret'] ?? $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? $_POST['internal_key'] ?? $_GET['internal_key'] ?? '';
$internalSecret = get_setting('internal_api_secret', 'apex_platform_internal_secret_key');

if (empty($apiKey) || !hash_equals($internalSecret, $apiKey)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or missing internal API key']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? $_GET['action'] ?? '';
$pdo = get_db();

try {
    switch ($action) {
        case 'ping':
            echo json_encode([
                'success' => true,
                'status' => 'online',
                'platform' => 'Apex Gaming Infrastructure',
                'version' => '1.0.0',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        case 'get_user_balance':
            $userId = (int)($input['user_id'] ?? 0);
            if ($userId <= 0) throw new Exception('Invalid user ID');
            
            $wallet = get_user_wallet($userId);
            if (!$wallet) throw new Exception('Wallet not found');

            echo json_encode([
                'success' => true,
                'user_id' => $userId,
                'balance' => (float)$wallet['balance'],
                'locked_balance' => (float)$wallet['locked_balance'],
                'available_balance' => (float)($wallet['balance'] - $wallet['locked_balance']),
                'currency' => $wallet['currency']
            ]);
            break;

        case 'create_round':
            $durationSeconds = max(10, (int)($input['duration_seconds'] ?? 60));
            $bettingWindowSeconds = max(5, (int)($input['betting_window_seconds'] ?? ($durationSeconds - 5)));

            // Provably fair seed generation
            $rngSeed = bin2hex(random_bytes(32));
            $serverHash = hash('sha256', $rngSeed);
            $roundCode = 'RND-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

            $startsAt = date('Y-m-d H:i:s');
            $bettingEndsAt = date('Y-m-d H:i:s', time() + $bettingWindowSeconds);
            $endsAt = date('Y-m-d H:i:s', time() + $durationSeconds);

            $stmt = $pdo->prepare("
                INSERT INTO rounds (round_code, status, scheduled_start, betting_end, round_end)
                VALUES (?, 'open', ?, ?, ?)
            ");
            $stmt->execute([$roundCode, $startsAt, $bettingEndsAt, $endsAt]);
            $roundId = (int)$pdo->lastInsertId();

            // Store preliminary hash in results table
            $stmt = $pdo->prepare("
                INSERT INTO results (round_id, result_data, rng_seed, server_hash, declared_by)
                VALUES (?, '', '', ?, 'ENGINE_SCHEDULE')
            ");
            $stmt->execute([$roundId, $serverHash]);

            echo json_encode([
                'success' => true,
                'round' => [
                    'id' => $roundId,
                    'round_code' => $roundCode,
                    'status' => 'open',
                    'server_hash' => $serverHash,
                    'scheduled_start' => $startsAt,
                    'betting_end' => $bettingEndsAt,
                    'round_end' => $endsAt
                ]
            ]);
            break;

        case 'place_bet':
            $userId = (int)($input['user_id'] ?? 0);
            $roundId = (int)($input['round_id'] ?? 0);
            $amount = (float)($input['amount'] ?? 0);
            $selectionData = $input['selection_data'] ?? $input['bet_payload'] ?? 'STANDARD';
            $potentialPayout = (float)($input['potential_payout'] ?? ($amount * 2.0));
            $odds = (float)($input['odds'] ?? 2.0);

            if ($userId <= 0 || $roundId <= 0 || $amount <= 0) {
                throw new Exception('Invalid user, round, or amount');
            }

            // Verify round is open
            $stmt = $pdo->prepare("SELECT * FROM rounds WHERE id = ?");
            $stmt->execute([$roundId]);
            $round = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$round || $round['status'] !== 'open') {
                throw new Exception('Round is not currently accepting bets');
            }

            if (strtotime($round['betting_end']) <= time()) {
                throw new Exception('Betting window for this round has closed');
            }

            // Debit wallet atomically
            $pdo->beginTransaction();

            $wallet = get_user_wallet($userId);
            if (!$wallet || $wallet['balance'] < $amount) {
                throw new Exception('Insufficient wallet balance');
            }

            $betCode = 'BET-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

            // 1. Insert Bet
            $stmt = $pdo->prepare("
                INSERT INTO bets (bet_code, user_id, round_id, amount, potential_payout, selection_data, status, odds)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([
                $betCode,
                $userId,
                $roundId,
                $amount,
                $potentialPayout,
                is_string($selectionData) ? $selectionData : json_encode($selectionData),
                $odds
            ]);
            $betId = (int)$pdo->lastInsertId();

            // 2. Deduct wallet & record transaction
            adjust_wallet_balance($userId, -$amount, 'bet', "Wager placed on Round #{$round['round_code']} (Bet #{$betCode})", 'bet', $betId);

            // 3. Update round totals
            $stmt = $pdo->prepare("
                UPDATE rounds 
                SET total_bets_count = total_bets_count + 1, 
                    total_pool_amount = total_pool_amount + ?
                WHERE id = ?
            ");
            $stmt->execute([$amount, $roundId]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'bet_id' => $betId,
                'bet_code' => $betCode,
                'amount' => $amount,
                'remaining_balance' => (float)($wallet['balance'] - $amount)
            ]);
            break;

        case 'close_round':
            $roundId = (int)($input['round_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE rounds SET status = 'betting_closed' WHERE id = ? AND status = 'open'");
            $stmt->execute([$roundId]);

            echo json_encode(['success' => true, 'message' => 'Round betting closed']);
            break;

        case 'settle_round':
            $roundId = (int)($input['round_id'] ?? 0);
            $resultData = $input['result_data'] ?? 'OUTCOME_NORMAL';
            $rngSeed = $input['rng_seed'] ?? bin2hex(random_bytes(16));
            $winningBetIds = $input['winning_bets'] ?? []; // Map of [bet_id => actual_payout]

            $stmt = $pdo->prepare("SELECT * FROM rounds WHERE id = ?");
            $stmt->execute([$roundId]);
            $round = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$round) throw new Exception('Round not found');

            $pdo->beginTransaction();

            $serverHash = hash('sha256', $rngSeed);

            // 1. Record result
            $stmt = $pdo->prepare("
                INSERT INTO results (round_id, result_data, rng_seed, server_hash, verified, declared_by)
                VALUES (?, ?, ?, ?, 1, 'API_GAME_ENGINE')
                ON DUPLICATE KEY UPDATE 
                    result_data = VALUES(result_data),
                    rng_seed = VALUES(rng_seed),
                    server_hash = VALUES(server_hash),
                    verified = 1
            ");
            $stmt->execute([
                $roundId, 
                is_string($resultData) ? $resultData : json_encode($resultData),
                $rngSeed,
                $serverHash
            ]);

            // 2. Fetch all bets on this round
            $stmt = $pdo->prepare("SELECT * FROM bets WHERE round_id = ? AND status = 'pending'");
            $stmt->execute([$roundId]);
            $bets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalPayout = 0;
            $winnersCount = 0;
            $losersCount = 0;

            foreach ($bets as $b) {
                $bId = (int)$b['id'];
                if (isset($winningBetIds[$bId]) && (float)$winningBetIds[$bId] > 0) {
                    $payout = (float)$winningBetIds[$bId];
                    $totalPayout += $payout;
                    $winnersCount++;

                    // Update bet to won
                    $pdo->prepare("UPDATE bets SET status = 'won', actual_payout = ?, settled_at = NOW() WHERE id = ?")->execute([$payout, $bId]);

                    // Credit user wallet
                    adjust_wallet_balance($b['user_id'], $payout, 'win', "Payout for won Bet #{$b['bet_code']} on Round #{$round['round_code']}", 'bet', $bId);

                    create_notification($b['user_id'], 'Bet Won!', "Congratulations! You won " . format_currency($payout) . " on round #{$round['round_code']}.", 'bet_win');
                } else {
                    $losersCount++;
                    // Update bet to lost
                    $pdo->prepare("UPDATE bets SET status = 'lost', actual_payout = 0, settled_at = NOW() WHERE id = ?")->execute([$bId]);
                }
            }

            // 3. Insert settlement
            $margin = (float)$round['total_pool_amount'] - $totalPayout;
            $stmt = $pdo->prepare("
                INSERT INTO settlements (round_id, total_winners, total_losers, total_pool, total_paid, platform_margin, settled_by)
                VALUES (?, ?, ?, ?, ?, ?, 'API_ENGINE')
                ON DUPLICATE KEY UPDATE 
                    total_winners = VALUES(total_winners),
                    total_losers = VALUES(total_losers),
                    total_pool = VALUES(total_pool),
                    total_paid = VALUES(total_paid),
                    platform_margin = VALUES(platform_margin)
            ");
            $stmt->execute([$roundId, $winnersCount, $losersCount, $round['total_pool_amount'], $totalPayout, $margin]);

            // 4. Mark round as completed
            $pdo->prepare("UPDATE rounds SET status = 'completed', round_end = NOW(), total_payout_amount = ? WHERE id = ?")->execute([$totalPayout, $roundId]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'round_id' => $roundId,
                'total_settled_bets' => count($bets),
                'winners' => $winnersCount,
                'losers' => $losersCount,
                'total_payout' => $totalPayout,
                'platform_margin' => $margin,
                'rng_seed_revealed' => $rngSeed
            ]);
            break;

        default:
            throw new Exception("Unknown action: {$action}");
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
