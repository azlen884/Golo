<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

require_admin();
$admin = get_auth_admin();
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $terms = trim($_POST['terms_content'] ?? '');
    $privacy = trim($_POST['privacy_content'] ?? '');
    $responsible = trim($_POST['responsible_gaming_content'] ?? '');

    set_setting('terms_content', $terms, 'legal');
    set_setting('privacy_content', $privacy, 'legal');
    set_setting('responsible_gaming_content', $responsible, 'legal');

    log_admin_activity($admin['id'], 'update_legal_content', 'settings', 'legal', 'Updated platform legal policies');
    set_flash('success', 'Terms, Privacy, and Responsible Gaming policies updated.');
    redirect('/admin/legal-settings.php');
}

$pageTitle = 'Legal & Regulatory Policies';
$activeAdminNav = 'legal-settings';
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-black text-white">Legal & Compliance Text</h1>
        <p class="text-xs text-slate-400 mt-1">Directly modify public terms, privacy disclosures, and player safety policies</p>
    </div>

    <form method="POST" action="/admin/legal-settings.php" class="space-y-6">
        <?= csrf_field() ?>

        <div class="glass-card p-6 rounded-3xl space-y-2">
            <label class="block text-xs font-bold text-white uppercase tracking-wider">Terms of Service Content</label>
            <textarea name="terms_content" rows="6" required
                      class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono leading-relaxed focus:outline-none focus:border-brand-500"><?= e(get_setting('terms_content', '')) ?></textarea>
        </div>

        <div class="glass-card p-6 rounded-3xl space-y-2">
            <label class="block text-xs font-bold text-white uppercase tracking-wider">Privacy Policy Content</label>
            <textarea name="privacy_content" rows="6" required
                      class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono leading-relaxed focus:outline-none focus:border-brand-500"><?= e(get_setting('privacy_content', '')) ?></textarea>
        </div>

        <div class="glass-card p-6 rounded-3xl space-y-2">
            <label class="block text-xs font-bold text-white uppercase tracking-wider">Responsible Gaming & 18+ Protection</label>
            <textarea name="responsible_gaming_content" rows="6" required
                      class="w-full px-4 py-2.5 rounded-xl bg-dark-950 border border-white/[0.08] text-white text-xs font-mono leading-relaxed focus:outline-none focus:border-brand-500"><?= e(get_setting('responsible_gaming_content', '')) ?></textarea>
        </div>

        <button type="submit" class="w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-colors shadow-lg shadow-brand-600/30">
            Save All Legal Policies
        </button>
    </form>
</div>

</body>
</html>
