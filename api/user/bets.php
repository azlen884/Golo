<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user = get_auth_user();
$pdo = get_db();

$roundId = isset($_GET['round_id']) ? (int)$_GET['round_id'] : null;
$status = isset($_GET['status']) ? clean($_GET['status']) : null;

$sql = "SELECT b.id, b.bet_code, b.round_id, r.round_code, b.amount, b.potential_payout, b.status, b.payout_amount, b.created_at, b.settled_at 
        FROM bets b 
        LEFT JOIN rounds r ON b.round_id = r.id 
        WHERE b.user_id = ?";
$params = [$user['id']];

if ($roundId) {
    $sql .= " AND b.round_id = ?";
    $params[] = $roundId;
}

if ($status && in_array($status, ['pending', 'won', 'lost', 'refunded', 'cancelled'])) {
    $sql .= " AND b.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY b.id DESC LIMIT 20";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bets = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $bets
]);
