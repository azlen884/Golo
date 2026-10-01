<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $mode = isset($_POST['maintenance_mode']) ? '1' : '0';
    $message = trim($_POST['maintenance_message'] ?? 'Platform is currently undergoing scheduled maintenance.');

    set_setting('maintenance_mode', $mode, 'maintenance');
    set_setting('maintenance_message', $message, 'maintenance');

    log_admin_activity($admin['id'], 'toggle_maintenance_mode', 'settings', 'maintenance_mode', "Maintenance mode set to {$mode}");
    set_flash('success', "Maintenance mode " . ($mode === '1' ? 'ACTIVATED' : 'DEACTIVATED') . " successfully.");
    redirect('/admin/maintenance.php');
}

$isMaintenance = is_maintenance_mode();
$currentMessage = get_setting('maintenance_message', 'Platform is currently undergoing scheduled maintenance. Please check back shortly.');

$pageTitle = 'Platform Maintenance Control';
$activeAdminNav = 'maintenance';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Maintenance Mode Engine</h1>
        <p class="text-xs text-slate-400 mt-1">Safely isolate public and player traffic during platform upgrades</p>
    </div>

    <!-- Mode Status Banner -->
    <div class="p-6 rounded-3xl border flex items-center justify-between <?= $isMaintenance ? 'bg-amber-950/40 border-amber-500/40' : 'glass-card border-white/[0.08]' ?>">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center <?= $isMaintenance ? 'bg-amber-500/20 text-amber-400' : 'bg-emerald-500/10 text-emerald-400' ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-white"><?= $isMaintenance ? 'Maintenance Mode is ACTIVE' : 'Platform is LIVE to Public' ?></h3>
                <p class="text-xs text-slate-400">
                    <?= $isMaintenance ? 'All public and player requests are safely redirected to the maintenance screen. Admin routes remain operational.' : 'Normal traffic flows smoothly to all landing pages and gaming rounds.' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Toggle Form -->
    <div class="glass-card p-6 sm:p-8 rounded-3xl">
        <form method="POST" action="/admin/maintenance.php" class="space-y-4">
            <?= csrf_field() ?>

            <div class="p-4 rounded-2xl bg-dark-950 border border-white/[0.06] flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-white block">Enable Maintenance Barrier</span>
                    <span class="text-[11px] text-slate-400">Blocks player access while keeping the Admin Console fully accessible.</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="maintenance_mode" value="1" <?= $isMaintenance ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-dark-850 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Public Maintenance Notice</label>
                <textarea name="maintenance_message" rows="4" required placeholder="Explain why the platform is offline..."
                          class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs leading-relaxed"><?= e($currentMessage) ?></textarea>
                <span class="text-[11px] text-slate-500 mt-1 block">Displayed to all visiting players. No admin credentials or shortcuts are exposed.</span>
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow-lg shadow-brand-600/30">
                Apply Maintenance Configuration
            </button>
        </form>
    </div>
</div>

</body>
</html>
