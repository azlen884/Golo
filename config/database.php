<?php
/**
 * Database Configuration & Connection
 * Apex Gaming Platform
 */

if (file_exists(__DIR__ . '/db_custom.php')) {
    require_once __DIR__ . '/db_custom.php';
}

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_NAME', getenv('DB_NAME') ?: 'gaming_platform');
    define('DB_USER', getenv('DB_USER') ?: 'gaming_user');
    define('DB_PASS', getenv('DB_PASS') ?: 'gaming_secure_pass_2026');
    define('DB_CHARSET', 'utf8mb4');
}

/**
 * Returns the singleton PDO database connection.
 *
 * @return PDO
 * @throws PDOException
 */
function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log failure locally if storage directory exists
            $logFile = __DIR__ . '/../storage/logs/database.log';
            if (is_dir(dirname($logFile))) {
                @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . 'Connection error: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
            throw $e;
        }
    }

    return $pdo;
}

/**
 * Executes any pending SQL migrations in /database/migrations/
 *
 * @param PDO $pdo
 * @return array Array of executed migration filenames
 */
function run_pending_migrations(PDO $pdo): array {
    $executed = [];
    $migrationsDir = __DIR__ . '/../database/migrations';

    if (!is_dir($migrationsDir)) {
        return $executed;
    }

    // Ensure system_migrations tracking table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `system_migrations` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `filename` VARCHAR(255) NOT NULL UNIQUE,
            `version` VARCHAR(50) NOT NULL,
            `checksum` VARCHAR(64) NOT NULL,
            `status` ENUM('executed', 'failed') NOT NULL DEFAULT 'executed',
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $files = glob($migrationsDir . '/*.sql');
    if (!$files) {
        return $executed;
    }
    sort($files);

    $stmt = $pdo->query("SELECT filename, checksum FROM system_migrations WHERE status = 'executed'");
    $doneMigrations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($files as $file) {
        $filename = basename($file);
        $content = file_get_contents($file);
        $checksum = hash('sha256', $content);

        // Skip if already executed
        if (isset($doneMigrations[$filename])) {
            continue;
        }

        // Run migration statements
        try {
            // In MySQL, DDL causes implicit commit. Execute statements directly.
            $pdo->exec($content);

            // Record execution in system_migrations
            $recordStmt = $pdo->prepare("
                INSERT INTO system_migrations (filename, version, checksum, status, executed_at)
                VALUES (:filename, :version, :checksum, 'executed', NOW())
                ON DUPLICATE KEY UPDATE status = 'executed', checksum = VALUES(checksum), executed_at = NOW()
            ");
            $recordStmt->execute([
                ':filename' => $filename,
                ':version'  => '1.0.0',
                ':checksum' => $checksum
            ]);

            $executed[] = $filename;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $logDir = __DIR__ . '/../storage/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0777, true);
            }
            @file_put_contents(
                $logDir . '/database.log',
                date('[Y-m-d H:i:s] ') . "Migration failed on {$filename}: " . $e->getMessage() . PHP_EOL,
                FILE_APPEND
            );
            throw new Exception("Migration {$filename} failed: " . $e->getMessage(), 0, $e);
        }
    }

    return $executed;
}
