<?php
/**
 * Admin Authentication & Session Management
 * Apex Gaming Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Get current authenticated admin details.
 *
 * @return array|null
 */
function get_auth_admin(): ?array {
    static $admin = null;

    if ($admin !== null) {
        return $admin;
    }

    if (empty($_SESSION['admin_id'])) {
        return null;
    }

    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            SELECT id, username, email, full_name, is_super, status, last_login, created_at
            FROM admins
            WHERE id = :id AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([':id' => (int)$_SESSION['admin_id']]);
        $row = $stmt->fetch();

        if (!$row) {
            unset($_SESSION['admin_id']);
            return null;
        }

        $admin = $row;
        return $admin;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Check if admin is currently authenticated.
 *
 * @return bool
 */
function is_admin_logged_in(): bool {
    return get_auth_admin() !== null;
}

/**
 * Enforce admin authentication.
 */
function require_admin(): void {
    $admin = get_auth_admin();
    if (!$admin) {
        $_SESSION['admin_intended_url'] = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard.php';
        set_flash('info', 'Please sign in to access the Administration Console.');
        redirect('/admin/login.php');
    }

    if ($admin['status'] !== 'active') {
        unset($_SESSION['admin_id']);
        set_flash('error', 'Administrator account suspended.');
        redirect('/admin/login.php');
    }
}

/**
 * Attempt to log in an admin.
 *
 * @param string $usernameOrEmail
 * @param string $password
 * @return array ['success' => bool, 'message' => string]
 */
function attempt_admin_login(string $usernameOrEmail, string $password): array {
    $identifier = trim($usernameOrEmail);

    if (empty($identifier) || empty($password)) {
        return ['success' => false, 'message' => 'Please enter admin username/email and password.'];
    }

    if (!check_rate_limit('admin_login_' . md5($identifier), 5, 300)) {
        return ['success' => false, 'message' => 'Too many failed admin login attempts. Try again in 5 minutes.'];
    }

    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            SELECT * FROM admins
            WHERE (username = :u OR email = :e)
            LIMIT 1
        ");
        $stmt->execute([':u' => $identifier, ':e' => $identifier]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            log_login_activity('admin', $admin['id'] ?? 0, 'failed', 'Invalid admin credentials');
            log_security_event('admin_failed_login', $admin['id'] ?? null, "Failed admin login for '{$identifier}'", 'medium');
            return ['success' => false, 'message' => 'Invalid administrator credentials.'];
        }

        if ($admin['status'] !== 'active') {
            log_login_activity('admin', $admin['id'], 'failed', 'Account suspended');
            return ['success' => false, 'message' => 'This administrator account is suspended.'];
        }

        // Regenerate session ID
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['full_name'];

        // Update last login
        $up = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
        $up->execute([':id' => $admin['id']]);

        log_login_activity('admin', $admin['id'], 'success');
        log_admin_activity($admin['id'], 'login', 'admin', (string)$admin['id'], 'Admin logged in');

        return ['success' => true, 'message' => 'Login successful.'];
    } catch (Throwable $e) {
        return ['success' => false, 'message' => 'Database error occurred during admin authentication.'];
    }
}

/**
 * Logout administrator.
 */
function logout_admin(): void {
    if (!empty($_SESSION['admin_id'])) {
        log_admin_activity((int)$_SESSION['admin_id'], 'logout', 'admin', (string)$_SESSION['admin_id'], 'Admin logged out');
    }
    unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_name']);
    session_regenerate_id(true);
}
