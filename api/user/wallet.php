<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/wallet.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user = get_auth_user();
$wallet = get_user_wallet($user['id']);

echo json_encode([
    'success' => true,
    'data' => [
        'user_id' => (int)$user['id'],
        'username' => $user['username'],
        'balance' => (float)$wallet['balance'],
        'locked_balance' => (float)$wallet['locked_balance'],
        'available_balance' => (float)($wallet['balance'] - $wallet['locked_balance']),
        'currency' => $wallet['currency'] ?? 'USD',
        'currency_symbol' => get_setting('currency_symbol', '$')
    ]
]);
