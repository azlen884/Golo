<?php
/**
 * Transactions Management
 * Apex Gaming Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/wallet.php';

/**
 * Create a new financial transaction record.
 *
 * @param int $userId
 * @param string $type ('deposit', 'withdrawal', 'bet', 'win', 'bonus', 'referral', 'adjustment')
 * @param float|string $amount
 * @param float|string $fee
 * @param string|null $paymentMethod
 * @param array|null $meta
 * @param string|null $customRef
 * @return array ['id' => int, 'transaction_ref' => string]
 * @throws Exception
 */
function create_transaction(int $userId, string $type, $amount, $fee = 0.00, ?string $paymentMethod = null, ?array $meta = null, ?string $customRef = null): array {
    $pdo = get_db();
    $wallet = get_user_wallet($userId, false, $pdo);

    $amount = number_format((float)$amount, 4, '.', '');
    $fee = number_format((float)$fee, 4, '.', '');
    $netAmount = number_format((float)$amount - (float)$fee, 4, '.', '');
    $ref = $customRef ?: generate_reference('TX', 12);

    $stmt = $pdo->prepare("
        INSERT INTO transactions (user_id, wallet_id, transaction_ref, type, amount, fee, net_amount, status, payment_method, meta_data, ip_address, created_at)
        VALUES (:uid, :wid, :ref, :type, :amt, :fee, :net, 'pending', :pm, :meta, :ip, NOW())
    ");
    $stmt->execute([
        ':uid'  => $userId,
        ':wid'  => $wallet['id'],
        ':ref'  => $ref,
        ':type' => $type,
        ':amt'  => $amount,
        ':fee'  => $fee,
        ':net'  => $netAmount,
        ':pm'   => $paymentMethod,
        ':meta' => $meta ? json_encode($meta) : null,
        ':ip'   => get_client_ip()
    ]);

    return [
        'id'              => (int)$pdo->lastInsertId(),
        'transaction_ref' => $ref,
        'net_amount'      => $netAmount
    ];
}

/**
 * Update transaction status.
 *
 * @param int $txId
 * @param string $status ('pending', 'completed', 'failed', 'cancelled', 'rejected')
 * @param string|null $gatewayTxId
 * @return bool
 */
function update_transaction_status(int $txId, string $status, ?string $gatewayTxId = null): bool {
    $pdo = get_db();
    $sql = "UPDATE transactions SET status = :status, updated_at = NOW()";
    $params = [':status' => $status, ':id' => $txId];

    if ($gatewayTxId !== null) {
        $sql .= ", gateway_tx_id = :gtx";
        $params[':gtx'] = $gatewayTxId;
    }
    $sql .= " WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Fetch a transaction by reference code.
 *
 * @param string $ref
 * @return array|null
 */
function get_transaction_by_ref(string $ref): ?array {
    $pdo = get_db();
    $stmt = $pdo->prepare("
        SELECT t.*, u.username, u.email
        FROM transactions t
        JOIN users u ON u.id = t.user_id
        WHERE t.transaction_ref = :ref
        LIMIT 1
    ");
    $stmt->execute([':ref' => $ref]);
    return $stmt->fetch() ?: null;
}
