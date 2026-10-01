<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-auth.php';

if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$pdo = get_db();

$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingDeposits = (int)$pdo->query("SELECT COUNT(*) FROM deposits WHERE status = 'pending'")->fetchColumn();
$pendingWithdrawals = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
$openTickets = (int)$pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open', 'in_progress')")->fetchColumn();

// 24h financials
$stmt = $pdo->query("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'deposit' AND status = 'completed' THEN amount ELSE 0 END), 0) as deposits_24h,
        COALESCE(SUM(CASE WHEN type = 'withdrawal' AND status = 'completed' THEN amount ELSE 0 END), 0) as withdrawals_24h,
        COALESCE(SUM(CASE WHEN type = 'bet' THEN amount ELSE 0 END), 0) as bets_24h,
        COALESCE(SUM(CASE WHEN type = 'win' THEN amount ELSE 0 END), 0) as wins_24h
    FROM transactions
    WHERE created_at >= NOW() - INTERVAL 24 HOUR
");
$fin24h = $stmt->fetch(PDO::FETCH_ASSOC);

$ggr24h = (float)$fin24h['bets_24h'] - (float)$fin24h['wins_24h'];

echo json_encode([
    'success' => true,
    'data' => [
        'total_users' => $totalUsers,
        'pending_deposits' => $pendingDeposits,
        'pending_withdrawals' => $pendingWithdrawals,
        'open_tickets' => $openTickets,
        'deposits_24h' => (float)$fin24h['deposits_24h'],
        'withdrawals_24h' => (float)$fin24h['withdrawals_24h'],
        'bets_volume_24h' => (float)$fin24h['bets_24h'],
        'ggr_24h' => $ggr24h,
        'currency' => get_setting('currency_code', 'USD')
    ]
]);
