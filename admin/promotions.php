<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_promo') {
        $title = trim($_POST['title'] ?? '');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $desc = trim($_POST['description'] ?? '');
        $minDep = (float)($_POST['min_deposit'] ?? 0);
        $rewardPct = (float)($_POST['reward_percent'] ?? 0);
        $maxBonus = (float)($_POST['max_bonus'] ?? 0);

        $stmt = $pdo->prepare("
            INSERT INTO promotions (title, slug, description, min_deposit, reward_percent, max_bonus, is_active, created_at)
            VALUES (:t, :s, :d, :md, :rp, :mb, 1, NOW())
        ");
        $stmt->execute([
            ':t'  => $title,
            ':s'  => $slug . '-' . bin2hex(random_bytes(2)),
            ':d'  => $desc,
            ':md' => $minDep,
            ':rp' => $rewardPct,
            ':mb' => $maxBonus
        ]);

        log_admin_activity($admin['id'], 'create_promotion', 'promotions', $title, "Created promotion {$title}");
        set_flash('success', "Promotion '{$title}' published.");
        redirect('/admin/promotions.php');
    } elseif ($action === 'toggle_promo') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE promotions SET is_active = NOT is_active WHERE id = :id")->execute([':id' => $id]);
        set_flash('success', "Promotion status toggled.");
        redirect('/admin/promotions.php');
    }
}

$promos = $pdo->query("SELECT * FROM promotions ORDER BY id DESC")->fetchAll();

$pageTitle = 'Platform Promotions Management';
$activeAdminNav = 'promotions';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Promotions Management</h1>
        <p class="text-xs text-slate-400 mt-1">Configure public platform campaigns and deposit incentive offers</p>
    </div>

    <!-- Create Promo Card -->
    <div class="glass-card p-6 rounded-3xl">
        <h3 class="text-sm font-bold text-white mb-3">Create New Campaign</h3>
        <form method="POST" action="/admin/promotions.php" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_promo">

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Campaign Title</label>
                <input type="text" name="title" required placeholder="e.g. Weekend 50% Reload Bonus"
                       class="w-full px-4 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Min Deposit ($)</label>
                    <input type="number" step="0.01" name="min_deposit" value="20.00" required
                           class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Reward Percentage (%)</label>
                    <input type="number" step="0.1" name="reward_percent" value="50.0" required
                           class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Max Bonus Cap ($)</label>
                    <input type="number" step="0.01" name="max_bonus" value="250.00" required
                           class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Description</label>
                <textarea name="description" rows="2" required placeholder="Details, terms and wagering rules..."
                          class="w-full px-4 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs"></textarea>
            </div>

            <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow-lg shadow-brand-600/30">
                Publish Promotion
            </button>
        </form>
    </div>

    <!-- Promos List -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($promos as $pr): ?>
            <div class="glass-card p-6 rounded-3xl flex flex-col justify-between space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-brand-400 font-mono"><?= (float)$pr['reward_percent'] ?>% Reward</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= $pr['is_active'] ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-500/10 text-slate-400' ?>">
                            <?= $pr['is_active'] ? 'Active' : 'Disabled' ?>
                        </span>
                    </div>
                    <h3 class="text-base font-bold text-white"><?= e($pr['title']) ?></h3>
                    <p class="text-xs text-slate-400 mt-1"><?= e($pr['description']) ?></p>
                </div>

                <div class="pt-3 border-t border-white/[0.06] flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-mono">Min: <?= format_money($pr['min_deposit']) ?></span>
                    <form method="POST" action="/admin/promotions.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle_promo">
                        <input type="hidden" name="id" value="<?= $pr['id'] ?>">
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-dark-800 text-xs font-semibold text-slate-300 hover:text-white">
                            <?= $pr['is_active'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
