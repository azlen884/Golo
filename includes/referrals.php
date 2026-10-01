<?php
/**
 * Referral & Affiliate Management
 * Apex Gaming Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/wallet.php';
require_once __DIR__ . '/notifications.php';

/**
 * Get referral summary stats for a user.
 *
 * @param int $userId
 * @return array
 */
function get_referral_stats(int $userId): array {
    $pdo = get_db();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_referees,
               COALESCE(SUM(total_earnings), 0.0000) as total_earned
        FROM referrals
        WHERE referrer_id = :uid
    ");
    $stmt->execute([':uid' => $userId]);
    $stats = $stmt->fetch() ?: ['total_referees' => 0, 'total_earned' => 0];

    return $stats;
}

/**
 * Distribute commission to referrer when referee makes a qualified action.
 *
 * @param int $refereeId
 * @param float|string $amount
 * @param string $sourceType ('deposit' or 'bet')
 * @return bool
 */
function process_referral_commission(int $refereeId, $amount, string $sourceType = 'deposit'): bool {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            SELECT r.*, u.username as referee_name
            FROM referrals r
            JOIN users u ON u.id = r.referee_id
            WHERE r.referee_id = :rid AND r.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([':rid' => $refereeId]);
        $referral = $stmt->fetch();

        if (!$referral) {
            return false;
        }

        $rate = (float)$referral['commission_rate'];
        if ($rate <= 0) {
            return false;
        }

        $commission = round(((float)$amount * ($rate / 100)), 4);
        if ($commission <= 0.0001) {
            return false;
        }

        $refId = $referral['id'];
        $referrerId = (int)$referral['referrer_id'];
        $refCode = generate_reference('REF', 8);

        // Credit referrer wallet
        credit_wallet(
            $referrerId,
            $commission,
            'referral_commission',
            $refCode,
            "Affiliate commission from player {$referral['referee_name']} ({$sourceType})"
        );

        // Update total referral earnings
        $up = $pdo->prepare("UPDATE referrals SET total_earnings = total_earnings + :comm WHERE id = :id");
        $up->execute([':comm' => $commission, ':id' => $refId]);

        // Send notification
        send_notification(
            $referrerId,
            'Referral Commission Earned',
            "You received " . format_money($commission) . " in affiliate commission from your referral {$referral['referee_name']}.",
            'success',
            '/user/referral.php'
        );

        return true;
    } catch (Throwable $e) {
        return false;
    }
}
