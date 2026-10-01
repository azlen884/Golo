<?php
/**
 * Security, Session, CSRF & Audit Logging
 * Apex Gaming Platform
 */

// Configure session cookies securely before starting
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_name('APEX_SESSION');
    session_start();
}

/**
 * Escape string for safe HTML output (XSS protection).
 *
 * @param mixed $value
 * @return string
 */
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Get client IP address reliably.
 *
 * @return string
 */
function get_client_ip(): string {
    $candidates = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    ];

    foreach ($candidates as $key) {
        if (!empty($_SERVER[$key])) {
            $ipList = explode(',', $_SERVER[$key]);
            $ip = trim($ipList[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '127.0.0.1';
}

/**
 * Generate or retrieve CSRF token for the session.
 *
 * @return string
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field.
 *
 * @return string
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validate submitted CSRF token.
 *
 * @param string|null $token
 * @return bool
 */
function validate_csrf(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token on POST requests or terminate.
 */
function verify_csrf_or_abort(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validate_csrf()) {
            log_security_event('csrf_violation', $_SESSION['user_id'] ?? null, 'Token mismatch on ' . ($_SERVER['REQUEST_URI'] ?? ''), 'medium');
            http_response_code(403);
            die('Security Error: Invalid or expired security token. Please refresh the page and try again.');
        }
    }
}

/**
 * Log security event to database.
 *
 * @param string $eventType
 * @param int|null $userId
 * @param string|null $payload
 * @param string $severity
 */
function log_security_event(string $eventType, ?int $userId = null, ?string $payload = null, string $severity = 'low'): void {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            INSERT INTO security_logs (event_type, user_id, ip_address, request_uri, payload, severity, created_at)
            VALUES (:type, :uid, :ip, :uri, :payload, :sev, NOW())
        ");
        $stmt->execute([
            ':type'    => $eventType,
            ':uid'     => $userId,
            ':ip'      => get_client_ip(),
            ':uri'     => substr($_SERVER['REQUEST_URI'] ?? '/', 0, 255),
            ':payload' => $payload ? substr($payload, 0, 1000) : null,
            ':sev'     => $severity
        ]);
    } catch (Throwable $e) {
        // Silently ignore to avoid secondary crashes
    }
}

/**
 * Log admin activity to database.
 *
 * @param int $adminId
 * @param string $action
 * @param string|null $targetType
 * @param string|null $targetId
 * @param string|null $details
 */
function log_admin_activity(int $adminId, string $action, ?string $targetType = null, ?string $targetId = null, ?string $details = null): void {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            INSERT INTO admin_activity (admin_id, action, target_type, target_id, details, ip_address, user_agent, created_at)
            VALUES (:aid, :act, :tt, :tid, :det, :ip, :ua, NOW())
        ");
        $stmt->execute([
            ':aid' => $adminId,
            ':act' => $action,
            ':tt'  => $targetType,
            ':tid' => $targetId,
            ':det' => $details,
            ':ip'  => get_client_ip(),
            ':ua'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);
    } catch (Throwable $e) {
        // Silently ignore
    }
}

/**
 * Log login attempts.
 *
 * @param string $userType 'user' or 'admin'
 * @param int $userId
 * @param string $status 'success' or 'failed'
 * @param string|null $reason
 */
function log_login_activity(string $userType, int $userId, string $status, ?string $reason = null): void {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            INSERT INTO login_activity (user_type, user_id, ip_address, user_agent, status, failure_reason, created_at)
            VALUES (:utype, :uid, :ip, :ua, :status, :reason, NOW())
        ");
        $stmt->execute([
            ':utype'  => $userType,
            ':uid'    => $userId,
            ':ip'     => get_client_ip(),
            ':ua'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ':status' => $status,
            ':reason' => $reason
        ]);
    } catch (Throwable $e) {
        // Silently ignore
    }
}

/**
 * Simple database-backed IP rate limiter.
 *
 * @param string $actionKey
 * @param int $maxAttempts
 * @param int $decaySeconds
 * @return bool True if allowed, false if rate limited
 */
function check_rate_limit(string $actionKey, int $maxAttempts = 10, int $decaySeconds = 300): bool {
    try {
        $pdo = get_db();
        $ip = get_client_ip();
        $cutoff = date('Y-m-d H:i:s', time() - $decaySeconds);

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM security_logs
            WHERE event_type = :event AND ip_address = :ip AND created_at >= :cutoff
        ");
        $stmt->execute([
            ':event'  => 'rate_attempt_' . $actionKey,
            ':ip'     => $ip,
            ':cutoff' => $cutoff
        ]);
        $attempts = (int)$stmt->fetchColumn();

        if ($attempts >= $maxAttempts) {
            log_security_event('rate_limited', null, "Action {$actionKey} limit exceeded by {$ip}", 'medium');
            return false;
        }

        // Record attempt
        $stmt = $pdo->prepare("
            INSERT INTO security_logs (event_type, ip_address, request_uri, severity, created_at)
            VALUES (:event, :ip, :uri, 'low', NOW())
        ");
        $stmt->execute([
            ':event' => 'rate_attempt_' . $actionKey,
            ':ip'    => $ip,
            ':uri'   => substr($_SERVER['REQUEST_URI'] ?? '', 0, 255)
        ]);

        return true;
    } catch (Throwable $e) {
        return true; // Fail open if rate table has temporary issue
    }
}
