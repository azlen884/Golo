<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $siteName = trim($_POST['site_name'] ?? 'Apex Gaming Platform');
    $siteTagline = trim($_POST['site_tagline'] ?? '');
    $siteEmail = trim($_POST['site_email'] ?? '');
    $currencySymbol = trim($_POST['currency_symbol'] ?? '$');
    $currencyCode = trim($_POST['currency_code'] ?? 'USD');
    $minDeposit = (float)($_POST['min_deposit'] ?? 10);
    $maxDeposit = (float)($_POST['max_deposit'] ?? 10000);
    $minWithdrawal = (float)($_POST['min_withdrawal'] ?? 20);
    $maxWithdrawal = (float)($_POST['max_withdrawal'] ?? 5000);

    set_setting('site_name', $siteName, 'general');
    set_setting('site_tagline', $siteTagline, 'general');
    set_setting('site_email', $siteEmail, 'general');
    set_setting('currency_symbol', $currencySymbol, 'financial');
    set_setting('currency_code', $currencyCode, 'financial');
    set_setting('min_deposit', $minDeposit, 'financial');
    set_setting('max_deposit', $maxDeposit, 'financial');
    set_setting('min_withdrawal', $minWithdrawal, 'financial');
    set_setting('max_withdrawal', $maxWithdrawal, 'financial');

    log_admin_activity($admin['id'], 'update_site_settings', 'settings', null, 'Updated site identity & limits');
    set_flash('success', 'Site identity and financial limits updated successfully.');
    redirect('/admin/site-settings.php');
}

$pageTitle = 'Site Identity & Brand Configuration';
$activeAdminNav = 'site-settings';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Platform Identity & Limits</h1>
        <p class="text-xs text-slate-400 mt-1">Configure brand metadata, support contact, and global deposit/withdrawal thresholds</p>
    </div>

    <div class="glass-card p-6 sm:p-8 rounded-3xl">
        <form method="POST" action="/admin/site-settings.php" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Platform Name</label>
                <input type="text" name="site_name" value="<?= e(get_setting('site_name', 'Apex Gaming Platform')) ?>" required
                       class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Site Tagline</label>
                <input type="text" name="site_tagline" value="<?= e(get_setting('site_tagline', 'Provably Fair Gaming Infrastructure')) ?>"
                       class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Official Support Email</label>
                <input type="email" name="site_email" value="<?= e(get_setting('site_email', 'support@apexgaming.io')) ?>" required
                       class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="<?= e(get_setting('currency_symbol', '$')) ?>" required
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Currency Code</label>
                    <input type="text" name="currency_code" value="<?= e(get_setting('currency_code', 'USD')) ?>" required
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono uppercase">
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Min Deposit ($)</label>
                    <input type="number" step="0.01" name="min_deposit" value="<?= (float)get_setting('min_deposit', 10) ?>" required
                           class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Max Deposit ($)</label>
                    <input type="number" step="0.01" name="max_deposit" value="<?= (float)get_setting('max_deposit', 10000) ?>" required
                           class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Min Payout ($)</label>
                    <input type="number" step="0.01" name="min_withdrawal" value="<?= (float)get_setting('min_withdrawal', 20) ?>" required
                           class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Max Payout ($)</label>
                    <input type="number" step="0.01" name="max_withdrawal" value="<?= (float)get_setting('max_withdrawal', 5000) ?>" required
                           class="w-full px-3 py-2 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono">
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow-lg shadow-brand-600/30">
                Save Platform Configuration
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
