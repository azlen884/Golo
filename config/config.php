<?php
/**
 * Application Global Configuration
 * Apex Gaming Platform
 */

// Error logging configuration
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');

// Timezone
date_default_timezone_set('UTC');

// Base paths
define('APP_ROOT', dirname(__DIR__));
define('STORAGE_PATH', APP_ROOT . '/storage');
define('UPLOADS_PATH', APP_ROOT . '/assets/uploads');

// Default App configuration
define('APP_NAME', 'Apex Gaming Platform');
define('APP_VERSION', '1.0.0');
define('GITHUB_REPO_URL', 'https://github.com/Azlenali007/Games.git');

// Auto-detect base URL
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:3000';
define('APP_URL', ($isHttps ? 'https://' : 'http://') . $host);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';

/**
 * Checks if the platform has been installed.
 *
 * @return bool
 */
function is_system_installed(): bool {
    return file_exists(STORAGE_PATH . '/installed.lock');
}

/**
 * Marks system as installed by generating the lock file.
 */
function mark_system_installed(): bool {
    $dir = STORAGE_PATH;
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return file_put_contents(STORAGE_PATH . '/installed.lock', date('c')) !== false;
}

/**
 * Retrieve setting from database with static runtime cache.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function get_setting(string $key, $default = null) {
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        try {
            $pdo = get_db();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch()) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            // DB not ready yet (e.g. during installer)
            return $default;
        }
    }

    return $cache[$key] ?? $default;
}

/**
 * Update or insert a platform setting.
 *
 * @param string $key
 * @param mixed $value
 * @param string $category
 * @return bool
 */
function set_setting(string $key, $value, string $category = 'general'): bool {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, category, updated_at)
            VALUES (:key, :val, :cat, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), category = VALUES(category), updated_at = NOW()
        ");
        return $stmt->execute([
            ':key' => $key,
            ':val' => (string)$value,
            ':cat' => $category
        ]);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Check if the site is in maintenance mode.
 *
 * @return bool
 */
function is_maintenance_mode(): bool {
    return (string)get_setting('maintenance_mode', '0') === '1';
}
