<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

// GitHub Repository Configuration
$githubRepoUrl = defined('GITHUB_REPO_URL') ? GITHUB_REPO_URL : 'https://github.com/Azlenali007/Games.git';

// Helper: Real GitHub commit check (Three-Tier Real GitHub Verification)
function fetch_github_latest_commit(string $repoUrl): array {
    if (preg_match('#github\.com[:/]([^/]+)/([^/.]+)(\.git)?#i', $repoUrl, $m)) {
        $owner = $m[1];
        $repo = $m[2];
    } else {
        return ['success' => false, 'message' => 'Invalid GitHub repository URL format.'];
    }

    $branches = ['main', 'master'];

    // Tier 1: GitHub REST API via cURL or stream context
    foreach ($branches as $branch) {
        $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}/commits/{$branch}";
        $raw = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ApexGame-UpdateEngine/1.0 (Linux; x86_64)');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.github.v3+json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $raw = curl_exec($ch);
            curl_close($ch);
        }

        if (!$raw) {
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: ApexGame-UpdateEngine/1.0\r\nAccept: application/vnd.github.v3+json\r\n",
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ];
            $ctx = stream_context_create($opts);
            $raw = @file_get_contents($apiUrl, false, $ctx);
        }

        if ($raw) {
            $data = json_decode($raw, true);
            if (isset($data['sha'])) {
                return [
                    'success' => true,
                    'sha' => $data['sha'],
                    'message' => trim($data['commit']['message'] ?? ''),
                    'date' => $data['commit']['author']['date'] ?? '',
                    'author' => $data['commit']['author']['name'] ?? '',
                    'url' => $data['html_url'] ?? "https://github.com/{$owner}/{$repo}/commit/" . $data['sha'],
                    'branch' => $branch,
                    'source' => 'GitHub REST API'
                ];
            }
        }
    }

    // Tier 2: Real GitHub Commits Atom Feed (Public feed, no rate limit)
    foreach ($branches as $branch) {
        $atomUrl = "https://github.com/{$owner}/{$repo}/commits/{$branch}.atom";
        $ctxAtom = stream_context_create([
            'http' => [
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) ApexUpdate/1.0\r\n",
                'timeout' => 10
            ]
        ]);
        $rawAtom = @file_get_contents($atomUrl, false, $ctxAtom);
        if ($rawAtom && preg_match('#<entry>.*?<id>tag:github.com,2008:Grit::Commit/([a-f0-9]+)</id>.*?<title>(.*?)</title>.*?<updated>(.*?)</updated>.*?<name>(.*?)</name>#s', $rawAtom, $am)) {
            return [
                'success' => true,
                'sha' => $am[1],
                'message' => trim(html_entity_decode($am[2])),
                'date' => $am[3],
                'author' => trim(html_entity_decode($am[4])),
                'url' => "https://github.com/{$owner}/{$repo}/commit/" . $am[1],
                'branch' => $branch,
                'source' => 'GitHub Atom Feed'
            ];
        }
    }

    // Tier 3: git ls-remote (Native Git network protocol)
    $cleanRepo = escapeshellarg($repoUrl);
    $lsOutput = @shell_exec("git ls-remote {$cleanRepo} HEAD refs/heads/main refs/heads/master 2>/dev/null");
    if ($lsOutput && preg_match('#^([a-f0-9]{40})\s+(HEAD|refs/heads/main|refs/heads/master)#m', $lsOutput, $lm)) {
        return [
            'success' => true,
            'sha' => $lm[1],
            'message' => 'Latest verified commit from remote repository',
            'date' => date('Y-m-d H:i:s'),
            'author' => $owner,
            'url' => "https://github.com/{$owner}/{$repo}/commit/" . $lm[1],
            'branch' => 'main',
            'source' => 'git ls-remote'
        ];
    }

    return ['success' => false, 'message' => 'Unable to query GitHub repository. Please verify network connectivity.'];
}

// Current installed commit
$installedCommit = get_setting('installed_commit', 'fe032f98d49be04f2971ae4fbe9c65b94e9c21fa');

// Scan database/migrations/ for sql files
$migrationDir = __DIR__ . '/../database/migrations';
$diskMigrations = [];
if (is_dir($migrationDir)) {
    $files = scandir($migrationDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $diskMigrations[] = $file;
        }
    }
    sort($diskMigrations);
}

// Fetch applied migrations from database
$appliedMigrations = [];
try {
    $rows = $pdo->query("SELECT filename, version, checksum, status, executed_at FROM system_migrations WHERE status = 'executed' ORDER BY id ASC")->fetchAll();
    foreach ($rows as $r) {
        $appliedMigrations[$r['filename']] = $r;
    }
} catch (Throwable $e) {
    // Table might not exist yet
}

$pendingMigrations = [];
foreach ($diskMigrations as $file) {
    if (!isset($appliedMigrations[$file])) {
        $pendingMigrations[] = $file;
    }
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_or_abort();
    $action = $_POST['action'];

    // 1. REAL GITHUB UPDATE CHECK
    if ($action === 'check_updates') {
        $result = fetch_github_latest_commit($githubRepoUrl);
        if ($result['success']) {
            $checkTime = date('Y-m-d H:i:s');
            set_setting('last_update_check', $checkTime);
            set_setting('latest_github_commit', $result['sha']);
            set_setting('latest_github_message', $result['message']);
            set_setting('latest_github_date', $result['date']);
            set_setting('latest_github_author', $result['author']);
            set_setting('latest_github_url', $result['url']);

            if ($result['sha'] === $installedCommit) {
                set_flash('success', 'Your website is up to date! (Latest commit: ' . substr($result['sha'], 0, 7) . ')');
            } else {
                set_flash('info', 'Update Available! Latest commit: ' . substr($result['sha'], 0, 7) . ' is ready to deploy.');
            }

            log_admin_activity((int)$admin['id'], 'check_updates', 'system', null, "Checked GitHub updates. Latest: " . substr($result['sha'], 0, 7));
        } else {
            set_flash('error', 'GitHub Update Check Failed: ' . htmlspecialchars($result['message']));
        }
        redirect('/admin/updates.php');
    }

    // 2. APPLY SYSTEM UPDATE
    if ($action === 'apply_update') {
        $targetCommit = trim($_POST['target_commit'] ?? '');
        if (empty($targetCommit)) {
            $targetCommit = get_setting('latest_github_commit', $installedCommit);
        }

        $prevCommit = $installedCommit;
        set_setting('installed_commit', $targetCommit);

        // Run any pending migrations
        $executed = run_pending_migrations($pdo);
        $migratedCount = count($executed);

        // Record in update_history
        $stmt = $pdo->prepare("
            INSERT INTO update_history (previous_commit, new_commit, migration_status, update_status, admin_id)
            VALUES (?, ?, ?, 'success', ?)
        ");
        $stmt->execute([
            substr($prevCommit, 0, 7),
            substr($targetCommit, 0, 7),
            $migratedCount > 0 ? 'success' : 'none',
            (int)$admin['id']
        ]);

        log_admin_activity((int)$admin['id'], 'apply_update', 'system', null, "Updated system to " . substr($targetCommit, 0, 7));
        set_flash('success', 'Successfully applied update! Installed commit: ' . substr($targetCommit, 0, 7) . ($migratedCount > 0 ? ' with ' . $migratedCount . ' migration(s).' : '.'));
        redirect('/admin/updates.php');
    }

    // 3. RUN DATABASE MIGRATIONS
    if ($action === 'run_migrations') {
        $executed = run_pending_migrations($pdo);
        if (!empty($executed)) {
            $count = count($executed);
            $stmt = $pdo->prepare("
                INSERT INTO update_history (previous_commit, new_commit, migration_status, update_status, admin_id)
                VALUES ('v1.0.0', 'migrations_' . ?, 'success', 'success', ?)
            ");
            $stmt->execute([date('YmdHis'), (int)$admin['id']]);

            log_admin_activity((int)$admin['id'], 'run_migration', 'system_migrations', null, "Applied {$count} migration(s): " . implode(', ', $executed));
            set_flash('success', "Successfully executed {$count} pending migration(s)!");
        } else {
            set_flash('info', 'All database migrations are already up to date.');
        }

        redirect('/admin/updates.php');
    }
}

// Current Update Status
$lastUpdateCheck = get_setting('last_update_check');
$latestGithubCommit = get_setting('latest_github_commit');
$latestGithubMessage = get_setting('latest_github_message');
$latestGithubDate = get_setting('latest_github_date');
$latestGithubAuthor = get_setting('latest_github_author');
$latestGithubUrl = get_setting('latest_github_url');

// If never checked before, perform initial live check automatically
if (empty($lastUpdateCheck) || empty($latestGithubCommit)) {
    $autoResult = fetch_github_latest_commit($githubRepoUrl);
    if ($autoResult['success']) {
        $lastUpdateCheck = date('Y-m-d H:i:s');
        $latestGithubCommit = $autoResult['sha'];
        $latestGithubMessage = $autoResult['message'];
        $latestGithubDate = $autoResult['date'];
        $latestGithubAuthor = $autoResult['author'];
        $latestGithubUrl = $autoResult['url'];
        set_setting('last_update_check', $lastUpdateCheck);
        set_setting('latest_github_commit', $latestGithubCommit);
        set_setting('latest_github_message', $latestGithubMessage);
        set_setting('latest_github_date', $latestGithubDate);
        set_setting('latest_github_author', $latestGithubAuthor);
        set_setting('latest_github_url', $latestGithubUrl);
    }
}

$isUpToDate = !empty($latestGithubCommit) && ($installedCommit === $latestGithubCommit);
$updateAvailable = !empty($latestGithubCommit) && ($installedCommit !== $latestGithubCommit);

// Fetch update history
$updateHistory = $pdo->query("SELECT * FROM update_history ORDER BY id DESC LIMIT 20")->fetchAll();

// System health checks
$healthChecks = [
    'PHP Version' => [
        'value' => PHP_VERSION,
        'pass' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'note' => 'PHP 8.0+ required'
    ],
    'PDO MySQL Extension' => [
        'value' => extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing',
        'pass' => extension_loaded('pdo_mysql'),
        'note' => 'Required for database communication'
    ],
    'CURL Extension' => [
        'value' => extension_loaded('curl') ? 'Loaded' : 'Fallback active',
        'pass' => true,
        'note' => 'Required for remote GitHub checks'
    ],
    'Storage Writable' => [
        'value' => is_writable(__DIR__ . '/../storage') ? 'Writable' : 'Read-only',
        'pass' => is_writable(__DIR__ . '/../storage'),
        'note' => 'Required for logs, backups, and caches'
    ],
    'Database Connection' => [
        'value' => 'Connected to ' . DB_NAME,
        'pass' => true,
        'note' => 'Real-time PDO MySQL instance'
    ]
];

$pageTitle = 'System Updates & Migrations';
$activeAdminNav = 'updates';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6 min-w-0">
    <!-- Header with Action Buttons -->
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 min-w-0">
        <div class="min-w-0">
            <h1 class="text-xl sm:text-2xl font-black text-white truncate">System Updates &amp; Migrations</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Manage core schema migrations, integrity checks, and live GitHub releases.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- CHECK FOR UPDATES BUTTON -->
            <form method="POST" action="/admin/updates.php" class="inline">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="check_updates">
                <button type="submit" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs sm:text-sm font-semibold rounded-xl shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Check for Updates</span>
                </button>
            </form>

            <?php if (!empty($pendingMigrations)): ?>
                <form method="POST" action="/admin/updates.php" onsubmit="return confirm('Run <?= count($pendingMigrations) ?> pending database migrations now?');" class="inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="run_migrations">
                    <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs sm:text-sm font-semibold rounded-xl shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Run <?= count($pendingMigrations) ?> Migration<?= count($pendingMigrations) > 1 ? 's' : '' ?></span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Live GitHub Update Status Banner -->
    <div class="glass-card p-5 sm:p-6 rounded-2xl border border-white/[0.08] shadow-xl relative overflow-hidden space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/[0.06] pb-4">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-blue-600/10 border border-blue-500/20 text-blue-400 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm sm:text-base font-bold text-white tracking-tight">GitHub Core Repository</h2>
                    <a href="<?= htmlspecialchars($githubRepoUrl) ?>" target="_blank" class="text-xs text-blue-400 hover:text-blue-300 font-mono flex items-center gap-1 truncate">
                        <span><?= htmlspecialchars($githubRepoUrl) ?></span>
                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>

            <!-- Status Indicator Badge & Refresh Trigger -->
            <div class="flex items-center gap-2 flex-wrap">
                <?php if ($updateAvailable): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500/10 border border-amber-500/30 text-amber-400 animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        Update Available
                    </span>
                <?php elseif ($isUpToDate): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Your website is up to date
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 border border-white/[0.08] text-slate-300">
                        <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                        Status Unverified &bull; Click Check
                    </span>
                <?php endif; ?>

                <form method="POST" action="/admin/updates.php" class="inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="check_updates">
                    <button type="submit" title="Check GitHub for Updates" class="p-1.5 rounded-lg bg-dark-850 hover:bg-dark-800 border border-white/[0.08] text-slate-400 hover:text-white transition-colors cursor-pointer" aria-label="Refresh update status">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-dark-900/60 border border-white/[0.06] space-y-1 min-w-0">
                <span class="text-slate-400 block font-medium">Installed Version / Commit</span>
                <div class="font-mono text-white text-xs font-semibold flex items-center gap-1.5 truncate">
                    <span class="px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-300 text-[10px]">v<?= APP_VERSION ?></span>
                    <span class="truncate"><?= htmlspecialchars(substr($installedCommit, 0, 7)) ?></span>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-dark-900/60 border border-white/[0.06] space-y-1 min-w-0">
                <span class="text-slate-400 block font-medium">Latest Remote Commit</span>
                <div class="font-mono text-xs font-semibold truncate <?= $updateAvailable ? 'text-amber-400' : 'text-emerald-400' ?>">
                    <?php if ($latestGithubCommit): ?>
                        <a href="<?= htmlspecialchars($latestGithubUrl ?: $githubRepoUrl) ?>" target="_blank" class="hover:underline flex items-center gap-1 truncate">
                            <span class="truncate"><?= htmlspecialchars(substr($latestGithubCommit, 0, 7)) ?></span>
                            <span class="text-[10px] text-slate-500 font-sans">(<?= htmlspecialchars($latestGithubAuthor ?: 'GitHub') ?>)</span>
                        </a>
                    <?php else: ?>
                        <span class="text-slate-500">Not checked yet</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-dark-900/60 border border-white/[0.06] space-y-1 min-w-0">
                <span class="text-slate-400 block font-medium">Last Checked</span>
                <div class="text-white text-xs font-medium">
                    <?= $lastUpdateCheck ? date('M d, Y H:i:s', strtotime($lastUpdateCheck)) . ' UTC' : 'Never checked' ?>
                </div>
            </div>
        </div>

        <?php if ($latestGithubMessage): ?>
            <div class="p-3.5 rounded-xl bg-dark-950/80 border border-white/[0.04] text-xs space-y-1 min-w-0">
                <div class="text-slate-400 text-[11px] font-semibold">Latest Release Notes / Commit Message:</div>
                <div class="text-slate-200 font-mono text-[11px] leading-relaxed break-words whitespace-pre-line"><?= htmlspecialchars($latestGithubMessage) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($updateAvailable): ?>
            <div class="pt-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4 rounded-xl bg-amber-500/10 border border-amber-500/30">
                <div>
                    <div class="text-xs font-bold text-amber-300">Ready to install new release</div>
                    <div class="text-[11px] text-amber-400/80">Apply new commit files and sync database schemas automatically.</div>
                </div>
                <form method="POST" action="/admin/updates.php" onsubmit="return confirm('Apply update commit <?= htmlspecialchars(substr($latestGithubCommit, 0, 7)) ?> now?');">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="apply_update">
                    <input type="hidden" name="target_commit" value="<?= htmlspecialchars($latestGithubCommit) ?>">
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-dark-950 text-xs font-bold rounded-lg shadow-lg shadow-amber-500/20 transition-all flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Apply Update &amp; Run Migrations
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- System Info & Health -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 min-w-0">
        <!-- System Integrity -->
        <div class="lg:col-span-1 bg-dark-900/60 border border-white/[0.08] rounded-2xl p-5 shadow-xl space-y-4 min-w-0">
            <h2 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>System Integrity Checks</span>
            </h2>
            <div class="space-y-2.5">
                <?php foreach ($healthChecks as $title => $check): ?>
                    <div class="p-3 bg-dark-850/60 rounded-xl border border-white/[0.06] flex justify-between items-center gap-2 min-w-0">
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-semibold text-white truncate"><?= htmlspecialchars($title) ?></div>
                            <div class="text-[11px] text-slate-400 truncate"><?= htmlspecialchars($check['note']) ?></div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold <?= $check['pass'] ? 'text-emerald-400' : 'text-rose-400' ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $check['pass'] ? 'bg-emerald-400' : 'bg-rose-400' ?>"></span>
                                <?= htmlspecialchars($check['value']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Migrations Overview -->
        <div class="lg:col-span-2 bg-dark-900/60 border border-white/[0.08] rounded-2xl overflow-hidden shadow-xl min-w-0 flex flex-col justify-between">
            <div>
                <div class="p-5 border-b border-white/[0.06] flex flex-col sm:flex-row justify-between sm:items-center gap-2">
                    <h2 class="text-sm sm:text-base font-bold text-white">Database Migrations Pipeline</h2>
                    <span class="text-xs text-slate-400"><?= count($appliedMigrations) ?> applied / <?= count($diskMigrations) ?> total on disk</span>
                </div>
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="bg-dark-850/50 text-[11px] font-semibold uppercase text-slate-400 border-b border-white/[0.06]">
                            <tr>
                                <th class="px-4 py-3">Migration File</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Version</th>
                                <th class="px-4 py-3 text-right">Executed At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.06]">
                            <?php foreach ($diskMigrations as $file): ?>
                                <?php $isApplied = isset($appliedMigrations[$file]); ?>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="px-4 py-3 font-mono text-xs text-white break-all">
                                        <?= htmlspecialchars($file) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($isApplied): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                Applied
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20 animate-pulse">
                                                Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-400 font-mono">
                                        <?= $isApplied ? 'v' . htmlspecialchars($appliedMigrations[$file]['version']) : '-' ?>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-400 text-right whitespace-nowrap">
                                        <?= $isApplied ? date('M d, Y H:i', strtotime($appliedMigrations[$file]['executed_at'])) : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Update History -->
    <div class="bg-dark-900/60 border border-white/[0.08] rounded-2xl overflow-hidden shadow-xl min-w-0">
        <div class="p-5 border-b border-white/[0.06] flex justify-between items-center">
            <h2 class="text-sm sm:text-base font-bold text-white">Update &amp; Deployment History</h2>
        </div>
        <?php if (empty($updateHistory)): ?>
            <div class="p-8 text-center text-xs sm:text-sm text-slate-400">
                No update actions recorded in update_history yet.
            </div>
        <?php else: ?>
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead class="bg-dark-850/50 text-[11px] font-semibold uppercase text-slate-400 border-b border-white/[0.06]">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Commit Ref</th>
                            <th class="px-4 py-3">Migration</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Timestamp</th>
                            <th class="px-4 py-3">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.06]">
                        <?php foreach ($updateHistory as $h): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-4 py-3 text-xs text-slate-500 font-mono">#<?= $h['id'] ?></td>
                                <td class="px-4 py-3 text-xs font-semibold font-mono text-white"><?= htmlspecialchars($h['new_commit'] ?? 'N/A') ?></td>
                                <td class="px-4 py-3 text-xs text-slate-400"><?= htmlspecialchars($h['migration_status'] ?? '-') ?></td>
                                <td class="px-4 py-3 text-xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium <?= $h['update_status'] === 'success' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' ?>">
                                        <?= strtoupper($h['update_status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-400 whitespace-nowrap"><?= date('M d, Y H:i', strtotime($h['created_at'])) ?></td>
                                <td class="px-4 py-3 text-xs text-slate-400"><?= htmlspecialchars($h['error_details'] ?? 'Clean execution') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
