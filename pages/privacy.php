<?php
$pageTitle = 'Privacy Policy — Apex Gaming Platform';
$activeNav = 'privacy';
require_once __DIR__ . '/../includes/header.php';

$content = get_setting('privacy_content', 'Standard privacy policy content.');
?>

<div class="max-w-4xl mx-auto py-8">
    <div class="mb-8">
        <span class="text-xs font-bold text-brand-400 uppercase tracking-wider">Data Protection</span>
        <h1 class="text-3xl font-black text-white mt-1">Privacy Policy</h1>
        <p class="text-slate-400 text-xs mt-1">Transparency regarding data storage and encryption protocols.</p>
    </div>

    <div class="glass-card p-6 sm:p-8 rounded-2xl space-y-6 text-sm text-slate-300 leading-relaxed">
        <div class="text-xs sm:text-sm">
            <?= nl2br(e($content)) ?>
        </div>

        <div class="border-t border-white/[0.06] pt-6 space-y-4 text-xs text-slate-400">
            <h3 class="text-sm font-bold text-white">Cryptographic Password Hashing</h3>
            <p>All passwords are encrypted using high-cost Bcrypt hashing algorithms with individualized salts. Plaintext passwords are never stored or transmitted internally.</p>

            <h3 class="text-sm font-bold text-white">Audit & Session Logging</h3>
            <p>For fraud mitigation and account security, IP addresses and device user agents are recorded during authentication attempts. These records are strictly utilized for security evaluations.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
