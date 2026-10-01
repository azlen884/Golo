<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$pdo = get_db();

$settings = $pdo->query("SELECT * FROM settings ORDER BY category ASC, setting_key ASC")->fetchAll();

$pageTitle = 'Global Platform Settings';
$activeAdminNav = 'settings';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-black text-white">System Settings Overview</h1>
            <p class="text-xs text-slate-400 mt-1">Configured keys and relational database configuration values</p>
        </div>
        <div class="flex gap-2">
            <a href="/admin/site-settings.php" class="px-4 py-2 rounded-xl bg-brand-600 text-white text-xs font-semibold">Site Identity &rarr;</a>
            <a href="/admin/legal-settings.php" class="px-4 py-2 rounded-xl bg-dark-850 text-slate-200 text-xs font-semibold">Legal &rarr;</a>
            <a href="/admin/maintenance.php" class="px-4 py-2 rounded-xl bg-dark-850 text-slate-200 text-xs font-semibold">Maintenance &rarr;</a>
        </div>
    </div>

    <div class="glass-card p-6 rounded-3xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 border-b border-white/[0.06]">
                        <th class="pb-3 font-semibold">Setting Key</th>
                        <th class="pb-3 font-semibold">Category</th>
                        <th class="pb-3 font-semibold">Configured Value</th>
                        <th class="pb-3 font-semibold text-right">Last Modified</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    <?php foreach ($settings as $s): ?>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="py-3 font-mono font-bold text-white"><?= e($s['setting_key']) ?></td>
                            <td class="py-3 uppercase text-[10px] font-bold text-brand-400 font-mono"><?= e($s['category']) ?></td>
                            <td class="py-3 font-mono text-slate-300 max-w-md truncate" title="<?= e($s['setting_value']) ?>">
                                <?= e($s['setting_value'] ?? 'NULL') ?>
                            </td>
                            <td class="py-3 text-right font-mono text-slate-400"><?= format_date($s['updated_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
