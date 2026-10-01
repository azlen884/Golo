<?php
$pageTitle = 'Terms of Service — Apex Gaming Platform';
$activeNav = 'terms';
require_once __DIR__ . '/../includes/header.php';

$content = get_setting('terms_content', 'Standard terms of service content.');
?>

<div class="max-w-4xl mx-auto py-8">
    <div class="mb-8">
        <span class="text-xs font-bold text-brand-400 uppercase tracking-wider">Legal & Compliance</span>
        <h1 class="text-3xl font-black text-white mt-1">Terms of Service</h1>
        <p class="text-slate-400 text-xs mt-1">Last revised: October 2026</p>
    </div>

    <div class="glass-card p-6 sm:p-8 rounded-2xl space-y-6 text-sm text-slate-300 leading-relaxed">
        <div class="prose prose-invert max-w-none text-xs sm:text-sm">
            <?= nl2br(e($content)) ?>
        </div>

        <div class="border-t border-white/[0.06] pt-6 space-y-4 text-xs text-slate-400">
            <h3 class="text-sm font-bold text-white">1. Eligibility & Age Restriction</h3>
            <p>You must be at least 18 years old or the age of majority in your legal jurisdiction, whichever is greater. Providing false identification constitutes a breach of contract.</p>

            <h3 class="text-sm font-bold text-white">2. Account Responsibility</h3>
            <p>Players are solely responsible for all wagers, financial actions, and session security on their registered profile. Two-factor authentication is strongly advised.</p>

            <h3 class="text-sm font-bold text-white">3. Provable Fairness & Settlement</h3>
            <p>All gaming cycles rely on server-client deterministic seeds. Once the betting cutoff has expired, wagers cannot be revoked or altered.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
