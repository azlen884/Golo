<?php
/**
 * Validation Utilities
 * Apex Gaming Platform
 */

/**
 * Validate username format.
 *
 * @param string $username
 * @return bool
 */
function validate_username(string $username): bool {
    return (bool)preg_match('/^[a-zA-Z0-9_]{3,30}$/', trim($username));
}

/**
 * Validate email address.
 *
 * @param string $email
 * @return bool
 */
function validate_email(string $email): bool {
    return (bool)filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password strength (minimum 8 characters).
 *
 * @param string $password
 * @return bool
 */
function validate_password(string $password): bool {
    return strlen($password) >= 8;
}

/**
 * Validate positive decimal financial amount.
 *
 * @param mixed $amount
 * @param float $min
 * @param float $max
 * @return bool
 */
function validate_positive_amount($amount, float $min = 0.01, float $max = 1000000.00): bool {
    if (!is_numeric($amount)) {
        return false;
    }
    $val = (float)$amount;
    return $val >= $min && $val <= $max;
}

/**
 * Format money string to fixed 2 or 4 decimal places.
 *
 * @param mixed $amount
 * @param int $decimals
 * @return string
 */
function format_money($amount, int $decimals = 2): string {
    $symbol = get_setting('currency_symbol', '$');
    return $symbol . number_format((float)$amount, $decimals, '.', ',');
}
