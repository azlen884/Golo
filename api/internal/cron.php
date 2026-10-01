<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/cron.php';

$key = $_GET['key'] ?? $_POST['key'] ?? '';
$configuredKey = get_setting('cron_secret_key', 'apex_cron_scheduler_key_2026');

if (empty($key) || !hash_equals($configuredKey, $key)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid cron authentication key']);
    exit;
}

$startTime = microtime(true);
$lockKey = 'http_cron_execution_lock';

if (!acquire_cron_lock($lockKey, 300)) {
    echo json_encode(['success' => false, 'message' => 'Cron execution lock active; skipping cycle.']);
    exit;
}

$results = [];
$status = 'success';
$error = null;

try {
    $results['rounds'] = cron_automate_rounds();
    $results['settlements'] = cron_process_settlements();
    $results['cleanup'] = cron_system_cleanup();
    set_setting('cron_last_run', date('Y-m-d H:i:s'), 'cron');
} catch (Throwable $e) {
    $status = 'failed';
    $error = $e->getMessage();
} finally {
    release_cron_lock($lockKey);
}

$durationMs = (int)round((microtime(true) - $startTime) * 1000);
log_cron_execution('http_cron_trigger', $status, $durationMs, json_encode($results), $error);

echo json_encode([
    'success' => $status === 'success',
    'status' => $status,
    'execution_time_ms' => $durationMs,
    'results' => $results,
    'error' => $error
]);
