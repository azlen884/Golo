<?php
/**
 * Master Automation & Scheduler
 * Apex Gaming Platform
 *
 * Single centralized entry point for all scheduled tasks with strict locking/idempotency.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/cron.php';

$startTime = microtime(true);
$isCli = (php_sapi_name() === 'cli');

// Basic security check if triggered via web
if (!$isCli) {
    // Optional secret key check or admin session check
    $secret = $_GET['secret'] ?? '';
    $configuredSecret = get_setting('cron_secret_key', 'apex_cron_token_default');
    if ($secret !== $configuredSecret && !isset($_SESSION['admin_id'])) {
        http_response_code(403);
        die(json_encode(['error' => 'Unauthorized cron invocation']));
    }
}

$lockKey = 'master_cron_execution_lock';

// 1. Acquire distributed lock (prevent concurrent executions)
if (!acquire_cron_lock($lockKey, 300)) {
    log_cron_execution('master_cron', 'locked', 0, 'Concurrent execution aborted due to active lock.');
    if ($isCli) {
        echo "[CRON] Locked: Another master cron instance is currently executing.\n";
        exit(0);
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'locked', 'message' => 'Task locked by another process']);
    exit;
}

$taskResults = [
    'rounds'      => [],
    'settlements' => [],
    'cleanup'     => []
];
$overallStatus = 'success';
$errorMessage = null;

try {
    // 2. Automate round states
    $taskResults['rounds'] = cron_automate_rounds();

    // 3. Process settlements
    $taskResults['settlements'] = cron_process_settlements();

    // 4. Clean expired data
    $taskResults['cleanup'] = cron_system_cleanup();

    // Update settings table with timestamp of last successful run
    set_setting('cron_last_run', date('Y-m-d H:i:s'), 'cron');

} catch (Throwable $e) {
    $overallStatus = 'failed';
    $errorMessage = $e->getMessage();
} finally {
    // 5. Release distributed lock
    release_cron_lock($lockKey);
}

$durationMs = (int)round((microtime(true) - $startTime) * 1000);
$summaryText = json_encode($taskResults);

// Record in database audit log
log_cron_execution('master_cron', $overallStatus, $durationMs, $summaryText, $errorMessage);

if ($isCli) {
    echo "[CRON " . date('Y-m-d H:i:s') . "] Status: {$overallStatus} ({$durationMs}ms)\n";
    echo "Results: " . json_encode($taskResults, JSON_PRETTY_PRINT) . "\n";
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'status'      => $overallStatus,
        'duration_ms' => $durationMs,
        'results'     => $taskResults,
        'error'       => $errorMessage
    ]);
}
