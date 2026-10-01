<?php
$pageTitle = 'About Us — Apex Gaming Platform';
$activeNav = 'about';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto py-8">
    <div class="mb-10 text-center">
        <span class="text-xs font-bold text-brand-400 uppercase tracking-wider">Company & Vision</span>
        <h1 class="text-3xl sm:text-4xl font-black text-white mt-2">Engineered for Trust</h1>
        <p class="text-slate-400 text-sm mt-2 max-w-xl mx-auto">
            Apex Gaming Platform provides enterprise-grade infrastructure for real-time multiplayer gaming with transparent outcomes.
        </p>
    </div>

    <div class="space-y-8 text-sm leading-relaxed text-slate-300">
        <div class="glass-card p-6 sm:p-8 rounded-2xl">
            <h2 class="text-lg font-bold text-white mb-3">Our Core Philosophy</h2>
            <p>
                In an industry historically clouded by black-box algorithms and uncertain payout timing, Apex was conceived with one foundational directive: mathematical provability and absolute transaction integrity.
            </p>
            <p class="mt-3">
                Every gaming cycle on this platform is executed through open, auditable cryptographic seeds. Financial balances do not rely on loose estimates or floating-point abstractions; they are strictly accounted for via double-entry database ledgers.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="glass-card p-6 rounded-2xl">
                <div class="text-2xl font-bold text-brand-400 font-mono mb-1">100%</div>
                <div class="text-xs font-semibold text-white mb-2">Deterministic Verification</div>
                <p class="text-xs text-slate-400">Post-round cryptographic verification enables any participant to check server and client seeds.</p>
            </div>
            <div class="glass-card p-6 rounded-2xl">
                <div class="text-2xl font-bold text-emerald-400 font-mono mb-1">0.00s</div>
                <div class="text-xs font-semibold text-white mb-2">Settlement Delay</div>
                <p class="text-xs text-slate-400">Winning payouts credit directly to wallets as soon as the round is concluded by the master scheduler.</p>
            </div>
            <div class="glass-card p-6 rounded-2xl">
                <div class="text-2xl font-bold text-cyan-400 font-mono mb-1">24/7</div>
                <div class="text-xs font-semibold text-white mb-2">Continuous Automation</div>
                <p class="text-xs text-slate-400">Autonomous round orchestrator and state engine operates without manual bottlenecks.</p>
            </div>
        </div>

        <div class="glass-card p-6 sm:p-8 rounded-2xl">
            <h2 class="text-lg font-bold text-white mb-3">Security & Compliance</h2>
            <p>
                Platform operations adhere strictly to international compliance guidelines, rate limiting, and protection against unauthorized account access. All administrative actions are timestamped and logged in relational audit records.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
