<?php
/**
 * User Authentication & Session Management
 * Apex Gaming Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Get current authenticated user details with profile and wallet.
 *
 * @return array|null
 */
function get_auth_user(): ?array {
    static $user = null;

    if ($user !== null) {
        return $user;
    }

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            SELECT u.*, p.first_name, p.last_name, p.phone, p.country, p.avatar, p.kyc_status,
                   w.balance, w.bonus_balance, w.locked_balance, w.currency
            FROM users u
            LEFT JOIN profiles p ON p.user_id = u.id
            LEFT JOIN wallets w ON w.user_id = u.id
            WHERE u.id = :id AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([':id' => (int)$_SESSION['user_id']]);
        $row = $stmt->fetch();

        if (!$row) {
            unset($_SESSION['user_id']);
            return null;
        }

        $user = $row;
        return $user;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Check if a user is currently logged in.
 *
 * @return bool
 */
function is_user_logged_in(): bool {
    return get_auth_user() !== null;
}

/**
 * Enforce authentication for user panel pages.
 */
function require_auth(): void {
    // Check maintenance mode
    if (is_maintenance_mode()) {
        render_maintenance_screen();
        exit;
    }

    $user = get_auth_user();
    if (!$user) {
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/user/dashboard.php';
        set_flash('info', 'Please sign in to access your player account.');
        redirect('/user/login.php');
    }

    if ($user['status'] !== 'active') {
        unset($_SESSION['user_id']);
        set_flash('error', 'Your account has been suspended or banned. Please contact support.');
        redirect('/user/login.php');
    }
}

/**
 * Render maintenance screen when active.
 */
function render_maintenance_screen(): void {
    $msg = get_setting('maintenance_message', 'Platform is currently undergoing scheduled maintenance. Please check back shortly.');
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Maintenance Mode - <?= e(get_setting('site_name', 'Apex Gaming')) ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="/assets/css/style.css">
    </head>
    <body class="bg-[#0a0d14] text-slate-200 min-h-screen flex items-center justify-center p-6 selection:bg-blue-600 selection:text-white">
        <div class="max-w-lg w-full text-center bg-slate-900/80 border border-slate-800 p-8 sm:p-12 rounded-3xl backdrop-blur-xl shadow-2xl shadow-blue-950/20">
            <div class="w-16 h-16 bg-blue-600/10 border border-blue-500/30 rounded-2xl flex items-center justify-center mx-auto mb-6 text-blue-400">
                <svg class="w-8 h-8 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white mb-3">System Maintenance</h1>
            <p class="text-slate-400 text-sm leading-relaxed mb-6"><?= nl2br(e($msg)) ?></p>
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800/80 border border-slate-700/60 rounded-full text-xs text-slate-300">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                <span>Security & wallet balances are fully preserved</span>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Attempt to log in a user.
 *
 * @param string $usernameOrEmail
 * @param string $password
 * @return array ['success' => bool, 'message' => string]
 */
function attempt_user_login(string $usernameOrEmail, string $password): array {
    $identifier = trim($usernameOrEmail);

    if (empty($identifier) || empty($password)) {
        return ['success' => false, 'message' => 'Please provide both username/email and password.'];
    }

    if (!check_rate_limit('login_' . md5($identifier), 5, 300)) {
        return ['success' => false, 'message' => 'Too many login attempts. Please wait 5 minutes before trying again.'];
    }

    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            SELECT * FROM users
            WHERE (username = :u OR email = :e)
            LIMIT 1
        ");
        $stmt->execute([':u' => $identifier, ':e' => $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            log_login_activity('user', $user['id'] ?? 0, 'failed', 'Invalid credentials');
            log_security_event('failed_login', $user['id'] ?? null, "Failed login for '{$identifier}'", 'low');
            return ['success' => false, 'message' => 'Invalid username/email or password.'];
        }

        if ($user['status'] === 'banned') {
            log_login_activity('user', $user['id'], 'failed', 'Account banned');
            return ['success' => false, 'message' => 'This account has been banned.'];
        }

        if ($user['status'] === 'suspended') {
            log_login_activity('user', $user['id'], 'failed', 'Account suspended');
            return ['success' => false, 'message' => 'This account is currently suspended.'];
        }

        // Regenerate session ID upon authentication to prevent session fixation
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_username'] = $user['username'];

        // Record user session
        $sessStmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, last_activity, created_at)
            VALUES (:uid, :sid, :ip, :ua, :last, NOW())
        ");
        $sessStmt->execute([
            ':uid'  => $user['id'],
            ':sid'  => session_id(),
            ':ip'   => get_client_ip(),
            ':ua'   => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ':last' => time()
        ]);

        log_login_activity('user', $user['id'], 'success');
        return ['success' => true, 'message' => 'Login successful.'];
    } catch (Throwable $e) {
        return ['success' => false, 'message' => 'Database error occurred during login.'];
    }
}

/**
 * Register a new user and create their initial wallet and profile.
 *
 * @param string $username
 * @param string $email
 * @param string $password
 * @param string|null $referralCode
 * @return array ['success' => bool, 'message' => string]
 */
function register_user(string $username, string $email, string $password, ?string $referralCode = null): array {
    $username = trim($username);
    $email = strtolower(trim($email));

    if (!validate_username($username)) {
        return ['success' => false, 'message' => 'Username must be 3-30 characters (letters, numbers, underscore only).'];
    }

    if (!validate_email($email)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    if (!validate_password($password)) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
    }

    if (!check_rate_limit('register_ip', 5, 3600)) {
        return ['success' => false, 'message' => 'Too many registrations from this IP. Please try again later.'];
    }

    try {
        $pdo = get_db();

        // Check uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1");
        $stmt->execute([':u' => $username, ':e' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username or email already in use.'];
        }

        // Check referrer
        $referrerId = null;
        if (!empty($referralCode)) {
            $refStmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = :code LIMIT 1");
            $refStmt->execute([':code' => trim($referralCode)]);
            $refUser = $refStmt->fetch();
            if ($refUser) {
                $referrerId = (int)$refUser['id'];
            }
        }

        $myReferralCode = strtoupper(bin2hex(random_bytes(4)));
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $pdo->beginTransaction();

        // 1. Insert user
        $ins = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, status, referral_code, referred_by, created_at)
            VALUES (:u, :e, :p, 'active', :ref, :ref_by, NOW())
        ");
        $ins->execute([
            ':u'      => $username,
            ':e'      => $email,
            ':p'      => $passwordHash,
            ':ref'    => $myReferralCode,
            ':ref_by' => $referrerId
        ]);
        $newUserId = (int)$pdo->lastInsertId();

        // 2. Insert profile
        $pIns = $pdo->prepare("INSERT INTO profiles (user_id, kyc_status) VALUES (:uid, 'unverified')");
        $pIns->execute([':uid' => $newUserId]);

        // 3. Insert wallet
        $wIns = $pdo->prepare("
            INSERT INTO wallets (user_id, currency, balance, bonus_balance, locked_balance, total_deposited, total_withdrawn, total_wagered, total_won, created_at)
            VALUES (:uid, 'USD', 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, NOW())
        ");
        $wIns->execute([':uid' => $newUserId]);

        // 4. Record referral relationship if applicable
        if ($referrerId !== null) {
            $commRate = (float)get_setting('referral_commission_rate', '5.00');
            $rIns = $pdo->prepare("
                INSERT INTO referrals (referrer_id, referee_id, referral_code, commission_rate, total_earnings, status, created_at)
                VALUES (:rid, :referee, :code, :rate, 0.0000, 'active', NOW())
            ");
            $rIns->execute([
                ':rid'     => $referrerId,
                ':referee' => $newUserId,
                ':code'    => trim($referralCode),
                ':rate'    => $commRate
            ]);
        }

        // 5. Welcome notification
        $nIns = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, action_url, created_at)
            VALUES (:uid, 'Welcome to Apex Gaming Platform', 'Your account and secure wallet have been successfully initialized. Deposit funds to start participating.', 'success', 0, '/user/wallet.php', NOW())
        ");
        $nIns->execute([':uid' => $newUserId]);

        $pdo->commit();

        // Log into session immediately
        session_regenerate_id(true);
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_username'] = $username;

        log_login_activity('user', $newUserId, 'success');
        return ['success' => true, 'message' => 'Registration successful!'];
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

/**
 * Logout current user.
 */
function logout_user(): void {
    if (!empty($_SESSION['user_id'])) {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = :uid AND session_id = :sid");
            $stmt->execute([
                ':uid' => (int)$_SESSION['user_id'],
                ':sid' => session_id()
            ]);
        } catch (Throwable $e) {
            // Silently ignore
        }
    }
    unset($_SESSION['user_id'], $_SESSION['user_username']);
    session_regenerate_id(true);
}
