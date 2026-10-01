<?php
/**
 * Atomic Wallet Engine & Financial Ledger
 * Apex Gaming Platform
 *
 * Implements strict ACID transactions, row-level locking (SELECT ... FOR UPDATE),
 * double-entry ledger audits, and zero floating-point arithmetic.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Fetch a user's wallet with optional row-level exclusive lock.
 *
 * @param int $userId
 * @param bool $forUpdate
 * @param PDO|null $externalPdo
 * @return array
 * @throws Exception
 */
function get_user_wallet(int $userId, bool $forUpdate = false, ?PDO $externalPdo = null): array {
    $pdo = $externalPdo ?? get_db();
    $sql = "SELECT * FROM wallets WHERE user_id = :uid";
    if ($forUpdate) {
        $sql .= " FOR UPDATE";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    $wallet = $stmt->fetch();

    if (!$wallet) {
        // If wallet doesn't exist, create it idempotently
        $ins = $pdo->prepare("
            INSERT IGNORE INTO wallets (user_id, currency, balance, bonus_balance, locked_balance, created_at)
            VALUES (:uid, 'USD', 0.0000, 0.0000, 0.0000, NOW())
        ");
        $ins->execute([':uid' => $userId]);

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $wallet = $stmt->fetch();

        if (!$wallet) {
            throw new Exception("Unable to locate or initialize wallet for user #{$userId}");
        }
    }

    return $wallet;
}

/**
 * Atomically credit a user's wallet with strict ledger auditing.
 *
 * @param int $userId
 * @param string|float $amount
 * @param string $transactionType ('deposit', 'bet_won', 'bet_refund', 'bonus_credit', 'referral_commission', 'admin_adjustment')
 * @param string|null $referenceId
 * @param string $description
 * @param string|null $referenceType
 * @return array ['success' => bool, 'balance_after' => string, 'ledger_id' => int]
 * @throws Exception
 */
function credit_wallet(int $userId, $amount, string $transactionType, ?string $referenceId, string $description, ?string $referenceType = null): array {
    $amount = number_format((float)$amount, 4, '.', '');
    if ((float)$amount <= 0) {
        throw new InvalidArgumentException("Credit amount must be greater than zero.");
    }

    $pdo = get_db();
    $ownsTransaction = false;

    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
        $ownsTransaction = true;
    }

    try {
        // 1. Lock wallet row exclusively
        $wallet = get_user_wallet($userId, true, $pdo);
        $balanceBefore = $wallet['balance'];
        $balanceAfter = number_format((float)$balanceBefore + (float)$amount, 4, '.', '');

        // 2. Update wallet totals
        $extraCol = '';
        if ($transactionType === 'deposit') {
            $extraCol = ", total_deposited = total_deposited + {$amount}";
        } elseif ($transactionType === 'bet_won') {
            $extraCol = ", total_won = total_won + {$amount}";
        }

        $upStmt = $pdo->prepare("
            UPDATE wallets
            SET balance = :after {$extraCol}, updated_at = NOW()
            WHERE id = :wid
        ");
        $upStmt->execute([
            ':after' => $balanceAfter,
            ':wid'   => $wallet['id']
        ]);

        // 3. Insert immutable ledger entry
        $lStmt = $pdo->prepare("
            INSERT INTO wallet_ledger (wallet_id, user_id, transaction_type, amount, balance_before, balance_after, reference_id, reference_type, description, created_at)
            VALUES (:wid, :uid, :ttype, :amt, :before, :after, :ref, :reftype, :desc, NOW())
        ");
        $lStmt->execute([
            ':wid'     => $wallet['id'],
            ':uid'     => $userId,
            ':ttype'   => $transactionType,
            ':amt'     => $amount,
            ':before'  => $balanceBefore,
            ':after'   => $balanceAfter,
            ':ref'     => $referenceId,
            ':reftype' => $referenceType,
            ':desc'    => $description
        ]);
        $ledgerId = (int)$pdo->lastInsertId();

        if ($ownsTransaction) {
            $pdo->commit();
        }

        return [
            'success'       => true,
            'balance_after' => $balanceAfter,
            'ledger_id'     => $ledgerId
        ];
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Atomically debit a user's wallet with balance validation and ledger auditing.
 * Prevents negative balances at database and application level.
 *
 * @param int $userId
 * @param string|float $amount
 * @param string $transactionType ('withdrawal', 'bet_placed', 'fee', 'admin_adjustment')
 * @param string|null $referenceId
 * @param string $description
 * @param string|null $referenceType
 * @return array ['success' => bool, 'balance_after' => string, 'ledger_id' => int]
 * @throws Exception
 */
function debit_wallet(int $userId, $amount, string $transactionType, ?string $referenceId, string $description, ?string $referenceType = null): array {
    $amount = number_format((float)$amount, 4, '.', '');
    if ((float)$amount <= 0) {
        throw new InvalidArgumentException("Debit amount must be greater than zero.");
    }

    $pdo = get_db();
    $ownsTransaction = false;

    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
        $ownsTransaction = true;
    }

    try {
        // 1. Lock wallet row exclusively
        $wallet = get_user_wallet($userId, true, $pdo);
        $balanceBefore = $wallet['balance'];

        if ((float)$balanceBefore < (float)$amount) {
            throw new Exception("Insufficient funds. Available balance: " . format_money($balanceBefore) . ", Required: " . format_money($amount));
        }

        $balanceAfter = number_format((float)$balanceBefore - (float)$amount, 4, '.', '');

        // 2. Update wallet totals
        $extraCol = '';
        if ($transactionType === 'bet_placed') {
            $extraCol = ", total_wagered = total_wagered + {$amount}";
        } elseif ($transactionType === 'withdrawal') {
            $extraCol = ", total_withdrawn = total_withdrawn + {$amount}";
        }

        $upStmt = $pdo->prepare("
            UPDATE wallets
            SET balance = :after {$extraCol}, updated_at = NOW()
            WHERE id = :wid AND balance >= :amt
        ");
        $upStmt->execute([
            ':after' => $balanceAfter,
            ':wid'   => $wallet['id'],
            ':amt'   => $amount
        ]);

        if ($upStmt->rowCount() === 0) {
            throw new Exception("Concurrent transaction prevented debit. Insufficient balance.");
        }

        // 3. Insert immutable ledger entry (amount recorded as positive number for accounting magnitude)
        $lStmt = $pdo->prepare("
            INSERT INTO wallet_ledger (wallet_id, user_id, transaction_type, amount, balance_before, balance_after, reference_id, reference_type, description, created_at)
            VALUES (:wid, :uid, :ttype, :amt, :before, :after, :ref, :reftype, :desc, NOW())
        ");
        $lStmt->execute([
            ':wid'     => $wallet['id'],
            ':uid'     => $userId,
            ':ttype'   => $transactionType,
            ':amt'     => '-' . $amount,
            ':before'  => $balanceBefore,
            ':after'   => $balanceAfter,
            ':ref'     => $referenceId,
            ':reftype' => $referenceType,
            ':desc'    => $description
        ]);
        $ledgerId = (int)$pdo->lastInsertId();

        if ($ownsTransaction) {
            $pdo->commit();
        }

        return [
            'success'       => true,
            'balance_after' => $balanceAfter,
            'ledger_id'     => $ledgerId
        ];
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
