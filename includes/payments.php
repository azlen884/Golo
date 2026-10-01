<?php
/**
 * Payments & Banking Engine
 * Apex Gaming Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/wallet.php';
require_once __DIR__ . '/transactions.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/referrals.php';

/**
 * Get active payment methods.
 *
 * @param string|null $type
 * @return array
 */
function get_active_payment_methods(?string $type = null): array {
    $pdo = get_db();
    if ($type !== null) {
        $stmt = $pdo->prepare("SELECT * FROM payment_settings WHERE is_active = 1 AND method_type = :t ORDER BY id ASC");
        $stmt->execute([':t' => $type]);
    } else {
        $stmt = $pdo->query("SELECT * FROM payment_settings WHERE is_active = 1 ORDER BY id ASC");
    }
    return $stmt->fetchAll();
}

/**
 * Get single payment method.
 *
 * @param string $key
 * @return array|null
 */
function get_payment_method(string $key): ?array {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM payment_settings WHERE method_key = :k LIMIT 1");
    $stmt->execute([':k' => $key]);
    return $stmt->fetch() ?: null;
}

/**
 * Submit user deposit request.
 *
 * @param int $userId
 * @param string $methodKey
 * @param float|string $amount
 * @param string|null $proofPath
 * @param array $meta
 * @return array ['success' => bool, 'message' => string, 'deposit_ref' => string|null]
 */
function submit_deposit_request(int $userId, string $methodKey, $amount, ?string $proofPath = null, array $meta = []): array {
    $method = get_payment_method($methodKey);
    if (!$method || !$method['is_active']) {
        return ['success' => false, 'message' => 'Selected payment gateway is unavailable.'];
    }

    $amount = (float)$amount;
    $min = (float)$method['min_deposit'];
    $max = (float)$method['max_deposit'];

    if ($amount < $min || $amount > $max) {
        return ['success' => false, 'message' => "Deposit amount must be between " . format_money($min) . " and " . format_money($max) . "."];
    }

    $feePercent = (float)$method['deposit_fee_percent'];
    $fee = round($amount * ($feePercent / 100), 4);
    $depositRef = generate_reference('DEP', 12);

    $pdo = get_db();
    $pdo->beginTransaction();

    try {
        // 1. Create transaction record
        $tx = create_transaction($userId, 'deposit', $amount, $fee, $method['method_name'], array_merge($meta, ['method_key' => $methodKey]), $depositRef);

        // 2. Create deposit entry
        $dStmt = $pdo->prepare("
            INSERT INTO deposits (user_id, transaction_id, deposit_ref, payment_method, amount, fee, status, payment_proof, created_at)
            VALUES (:uid, :txid, :ref, :pm, :amt, :fee, 'pending', :proof, NOW())
        ");
        $dStmt->execute([
            ':uid'   => $userId,
            ':txid'  => $tx['id'],
            ':ref'   => $depositRef,
            ':pm'    => $method['method_name'],
            ':amt'   => $amount,
            ':fee'   => $fee,
            ':proof' => $proofPath
        ]);

        $pdo->commit();

        send_notification($userId, 'Deposit Submitted', "Your deposit request for " . format_money($amount) . " ({$depositRef}) has been submitted for verification.", 'info', '/user/transactions.php');

        return [
            'success'     => true,
            'message'     => 'Deposit request submitted successfully.',
            'deposit_ref' => $depositRef
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Failed to process deposit: ' . $e->getMessage()];
    }
}

/**
 * Approve deposit and credit user wallet.
 *
 * @param int $depositId
 * @param int $adminId
 * @param string|null $adminNote
 * @return array
 */
function approve_deposit(int $depositId, int $adminId, ?string $adminNote = null): array {
    $pdo = get_db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("SELECT * FROM deposits WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $depositId]);
        $dep = $stmt->fetch();

        if (!$dep) {
            throw new Exception("Deposit request not found.");
        }
        if ($dep['status'] !== 'pending') {
            throw new Exception("Deposit is already marked as " . $dep['status'] . ".");
        }

        $userId = (int)$dep['user_id'];
        $creditAmount = (float)$dep['amount'] - (float)$dep['fee'];

        // 1. Credit wallet
        credit_wallet(
            $userId,
            $creditAmount,
            'deposit',
            $dep['deposit_ref'],
            "Deposit confirmed via {$dep['payment_method']}",
            'deposit'
        );

        // 2. Update deposit status
        $upD = $pdo->prepare("
            UPDATE deposits
            SET status = 'approved', admin_note = :note, processed_by = :aid, processed_at = NOW()
            WHERE id = :id
        ");
        $upD->execute([
            ':note' => $adminNote,
            ':aid'  => $adminId,
            ':id'   => $depositId
        ]);

        // 3. Update transaction status
        update_transaction_status((int)$dep['transaction_id'], 'completed');

        $pdo->commit();

        // Process referral commission if eligible
        process_referral_commission($userId, $creditAmount, 'deposit');

        // Notify user
        send_notification($userId, 'Deposit Approved', "Your deposit of " . format_money($creditAmount) . " has been credited to your wallet balance.", 'success', '/user/wallet.php');

        return ['success' => true, 'message' => 'Deposit approved and wallet credited successfully.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Reject deposit.
 *
 * @param int $depositId
 * @param int $adminId
 * @param string|null $reason
 * @return array
 */
function reject_deposit(int $depositId, int $adminId, ?string $reason = null): array {
    $pdo = get_db();
    try {
        $stmt = $pdo->prepare("SELECT * FROM deposits WHERE id = :id");
        $stmt->execute([':id' => $depositId]);
        $dep = $stmt->fetch();

        if (!$dep || $dep['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Deposit cannot be rejected in current state.'];
        }

        $up = $pdo->prepare("
            UPDATE deposits
            SET status = 'rejected', admin_note = :note, processed_by = :aid, processed_at = NOW()
            WHERE id = :id
        ");
        $up->execute([':note' => $reason, ':aid' => $adminId, ':id' => $depositId]);

        update_transaction_status((int)$dep['transaction_id'], 'rejected');

        send_notification($dep['user_id'], 'Deposit Rejected', "Your deposit request ({$dep['deposit_ref']}) was rejected. Reason: " . ($reason ?: 'Documentation unverified'), 'danger', '/user/transactions.php');

        return ['success' => true, 'message' => 'Deposit rejected.'];
    } catch (Throwable $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Submit user withdrawal request.
 * Debits user wallet immediately into escrow/locked state.
 *
 * @param int $userId
 * @param string $methodKey
 * @param float|string $amount
 * @param string $accountDetails
 * @return array
 */
function submit_withdrawal_request(int $userId, string $methodKey, $amount, string $accountDetails): array {
    $method = get_payment_method($methodKey);
    if (!$method || !$method['is_active']) {
        return ['success' => false, 'message' => 'Selected payout gateway is unavailable.'];
    }

    $amount = (float)$amount;
    $min = (float)$method['min_withdrawal'];
    $max = (float)$method['max_withdrawal'];

    if ($amount < $min || $amount > $max) {
        return ['success' => false, 'message' => "Withdrawal amount must be between " . format_money($min) . " and " . format_money($max) . "."];
    }

    if (empty(trim($accountDetails))) {
        return ['success' => false, 'message' => 'Please provide destination account / wallet address details.'];
    }

    $feePercent = (float)$method['withdrawal_fee_percent'];
    $fee = round($amount * ($feePercent / 100), 4);
    $withRef = generate_reference('WIT', 12);

    $pdo = get_db();
    $pdo->beginTransaction();

    try {
        // 1. Debit wallet first (prevents double spending)
        debit_wallet($userId, $amount, 'withdrawal', $withRef, "Withdrawal request to {$method['method_name']}", 'withdrawal');

        // 2. Create transaction record
        $tx = create_transaction($userId, 'withdrawal', $amount, $fee, $method['method_name'], ['destination' => $accountDetails, 'method_key' => $methodKey], $withRef);

        // 3. Create withdrawal record
        $wStmt = $pdo->prepare("
            INSERT INTO withdrawals (user_id, transaction_id, withdrawal_ref, payment_method, amount, fee, account_details, status, created_at)
            VALUES (:uid, :txid, :ref, :pm, :amt, :fee, :acc, 'pending', NOW())
        ");
        $wStmt->execute([
            ':uid'  => $userId,
            ':txid' => $tx['id'],
            ':ref'  => $withRef,
            ':pm'   => $method['method_name'],
            ':amt'  => $amount,
            ':fee'  => $fee,
            ':acc'  => $accountDetails
        ]);

        $pdo->commit();

        send_notification($userId, 'Withdrawal Requested', "Your withdrawal request for " . format_money($amount) . " ({$withRef}) is under review.", 'info', '/user/transactions.php');

        return [
            'success'        => true,
            'message'        => 'Withdrawal request submitted successfully.',
            'withdrawal_ref' => $withRef
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Approve withdrawal.
 *
 * @param int $withdrawalId
 * @param int $adminId
 * @param string|null $adminNote
 * @return array
 */
function approve_withdrawal(int $withdrawalId, int $adminId, ?string $adminNote = null): array {
    $pdo = get_db();
    try {
        $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = :id");
        $stmt->execute([':id' => $withdrawalId]);
        $with = $stmt->fetch();

        if (!$with || $with['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Withdrawal cannot be approved in current state.'];
        }

        $up = $pdo->prepare("
            UPDATE withdrawals
            SET status = 'approved', admin_note = :note, processed_by = :aid, processed_at = NOW()
            WHERE id = :id
        ");
        $up->execute([':note' => $adminNote, ':aid' => $adminId, ':id' => $withdrawalId]);

        update_transaction_status((int)$with['transaction_id'], 'completed');

        send_notification($with['user_id'], 'Withdrawal Approved', "Your withdrawal of " . format_money($with['amount']) . " has been sent.", 'success', '/user/transactions.php');

        return ['success' => true, 'message' => 'Withdrawal marked as approved and dispatched.'];
    } catch (Throwable $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Reject withdrawal and refund money to user wallet.
 *
 * @param int $withdrawalId
 * @param int $adminId
 * @param string|null $reason
 * @return array
 */
function reject_withdrawal(int $withdrawalId, int $adminId, ?string $reason = null): array {
    $pdo = get_db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $withdrawalId]);
        $with = $stmt->fetch();

        if (!$with || $with['status'] !== 'pending') {
            throw new Exception("Withdrawal cannot be rejected in current state.");
        }

        $userId = (int)$with['user_id'];
        $refundAmount = (float)$with['amount'];

        // 1. Refund wallet balance
        credit_wallet(
            $userId,
            $refundAmount,
            'admin_adjustment',
            $with['withdrawal_ref'],
            "Refund for rejected withdrawal ({$with['withdrawal_ref']}): " . ($reason ?: 'Declined'),
            'withdrawal_refund'
        );

        // 2. Mark withdrawal rejected
        $up = $pdo->prepare("
            UPDATE withdrawals
            SET status = 'rejected', admin_note = :note, processed_by = :aid, processed_at = NOW()
            WHERE id = :id
        ");
        $up->execute([':note' => $reason, ':aid' => $adminId, ':id' => $withdrawalId]);

        // 3. Mark transaction rejected
        update_transaction_status((int)$with['transaction_id'], 'rejected');

        $pdo->commit();

        send_notification($userId, 'Withdrawal Declined (Refunded)', "Your withdrawal request ({$with['withdrawal_ref']}) was rejected and " . format_money($refundAmount) . " has been restored to your balance.", 'warning', '/user/wallet.php');

        return ['success' => true, 'message' => 'Withdrawal rejected and balance restored to user.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
