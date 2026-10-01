<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

$pdo = get_db();

// Fetch currently active/open round for common gaming platform
$stmt = $pdo->prepare("
    SELECT id, round_code, status, scheduled_start, betting_end, round_end, total_bets_count, total_pool_amount
    FROM rounds 
    WHERE status IN ('open', 'scheduled', 'betting_closed', 'processing')
    ORDER BY id DESC LIMIT 1
");
$stmt->execute();
$currentRound = $stmt->fetch(PDO::FETCH_ASSOC);

// If no open round, fetch latest completed round
if (!$currentRound) {
    $stmt = $pdo->prepare("
        SELECT r.id, r.round_code, r.status, r.round_end, res.result_data, res.rng_seed, res.server_hash
        FROM rounds r
        LEFT JOIN results res ON r.id = res.round_id
        WHERE r.status = 'completed'
        ORDER BY r.id DESC LIMIT 1
    ");
    $stmt->execute();
    $latestRound = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'has_active_round' => false,
        'latest_round' => $latestRound
    ]);
    exit;
}

$now = time();
$endsAt = strtotime($currentRound['betting_end'] ?? $currentRound['round_end'] ?? 'now');
$secondsRemaining = max(0, $endsAt - $now);

echo json_encode([
    'success' => true,
    'has_active_round' => true,
    'round' => [
        'id' => (int)$currentRound['id'],
        'round_code' => $currentRound['round_code'],
        'status' => $currentRound['status'],
        'seconds_remaining' => $secondsRemaining,
        'total_bets' => (int)$currentRound['total_bets_count'],
        'total_pool' => (float)$currentRound['total_pool_amount']
    ]
]);
