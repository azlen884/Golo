<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized admin']);
    exit;
}

$pdo = get_db();

// 1. Current active round
$stmt = $pdo->query("
    SELECT id, round_code, status, scheduled_start, betting_end, round_end, total_bets_count, total_pool_amount
    FROM rounds
    WHERE status IN ('open', 'scheduled', 'betting_closed', 'processing')
    ORDER BY id DESC LIMIT 1
");
$activeRound = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Recent live bets (last 15)
$stmt = $pdo->query("
    SELECT b.id, b.bet_code, b.amount, b.status, b.placed_at as created_at, u.username, r.round_code
    FROM bets b
    JOIN users u ON b.user_id = u.id
    JOIN rounds r ON b.round_id = r.id
    ORDER BY b.id DESC LIMIT 15
");
$recentBets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Stats for past 10 minutes
$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT user_id) as active_players,
        COUNT(id) as bets_last_10m,
        COALESCE(SUM(amount), 0) as volume_last_10m
    FROM bets
    WHERE placed_at >= NOW() - INTERVAL 10 MINUTE
");
$tenMinStats = $stmt->fetch(PDO::FETCH_ASSOC);

// 4. Server metrics
$now = time();
$roundEndsAt = $activeRound ? strtotime($activeRound['betting_end'] ?? $activeRound['round_end'] ?? 'now') : 0;
$timeLeft = max(0, $roundEndsAt - $now);

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'active_round' => $activeRound ? [
        'id' => (int)$activeRound['id'],
        'round_code' => $activeRound['round_code'],
        'status' => $activeRound['status'],
        'time_remaining' => $timeLeft,
        'total_bets' => (int)$activeRound['total_bets_count'],
        'total_amount' => (float)$activeRound['total_pool_amount']
    ] : null,
    'recent_bets' => $recentBets,
    'metrics' => [
        'active_players' => (int)($tenMinStats['active_players'] ?? 0),
        'bets_last_10m' => (int)($tenMinStats['bets_last_10m'] ?? 0),
        'volume_last_10m' => (float)($tenMinStats['volume_last_10m'] ?? 0)
    ]
]);
