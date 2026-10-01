<?php
$pageTitle = 'Responsible Gaming — Apex Gaming Platform';
$activeNav = 'rg';
require_once __DIR__ . '/../includes/header.php';

$content = get_setting('responsible_gaming_content', 'Responsible gaming guidance and player protections.');
?>

<div class="max-w-4xl mx-auto py-8">
    <div class="mb-8">
        <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">Player Wellbeing</span>
        <h1 class="text-3xl font-black text-white mt-1">Responsible Gaming</h1>
        <p class="text-slate-400 text-xs mt-1">Our commitment to safe and controlled entertainment.</p>
    </div>

    <div class="glass-card p-6 sm:p-8 rounded-2xl space-y-6 text-sm text-slate-300 leading-relaxed">
        <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs sm:text-sm">
            <strong>Important Notice:</strong> Gaming should be enjoyed exclusively as a paid form of recreation, not as a source of income or financial recovery. Never wager funds essential for living expenses.
        </div>

        <div class="text-xs sm:text-sm">
            <?= nl2br(e($content)) ?>
        </div>

        <div class="border-t border-white/[0.06] pt-6 space-y-4 text-xs text-slate-400">
            <h3 class="text-sm font-bold text-white">Platform Protection Features</h3>
            <ul class="list-disc pl-5 space-y-1.5">
                <li><strong>Self-Exclusion:</strong> Contact our support helpdesk at any time to temporarily suspend or permanently terminate your account.</li>
                <li><strong>Deposit Caps:</strong> Restrict weekly or monthly maximum deposit limits directly in your player settings.</li>
                <li><strong>Strict 18+ Verification:</strong> Minors are strictly prohibited from participating. Accounts suspected of underage activity are immediately frozen.</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
