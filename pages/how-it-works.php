<?php
$pageTitle = 'How It Works — Apex Gaming Platform';
$activeNav = 'how';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto py-8">
    <div class="mb-12 text-center">
        <span class="text-xs font-bold text-brand-400 uppercase tracking-wider">Engine Mechanics</span>
        <h1 class="text-3xl sm:text-4xl font-black text-white mt-2">How The Platform Works</h1>
        <p class="text-slate-400 text-sm mt-2 max-w-xl mx-auto">
            From player onboarding and wallet deposits to round settlement and payout execution.
        </p>
    </div>

    <div class="space-y-6">
        <!-- Step 1 -->
        <div class="glass-card p-6 sm:p-8 rounded-2xl flex gap-6 items-start">
            <div class="w-10 h-10 rounded-xl bg-brand-600/20 text-brand-400 border border-brand-500/30 flex items-center justify-center font-bold font-mono text-base flex-shrink-0">
                01
            </div>
            <div>
                <h3 class="text-base font-bold text-white mb-1">Create Account & Initialize Wallet</h3>
                <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                    Register with a username and secure password. An isolated multi-currency wallet is instantly provisioned in our database ledger with zero balance, tied strictly to your cryptographic session.
                </p>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="glass-card p-6 sm:p-8 rounded-2xl flex gap-6 items-start">
            <div class="w-10 h-10 rounded-xl bg-blue-600/20 text-blue-400 border border-blue-500/30 flex items-center justify-center font-bold font-mono text-base flex-shrink-0">
                02
            </div>
            <div>
                <h3 class="text-base font-bold text-white mb-1">Deposit Funds with Instant Confirmation</h3>
                <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                    Select any configured payment gateway (USDT, Bitcoin, Ethereum, or Bank Wire). Funds are audited and credited to your available balance via atomic database transactions.
                </p>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="glass-card p-6 sm:p-8 rounded-2xl flex gap-6 items-start">
            <div class="w-10 h-10 rounded-xl bg-purple-600/20 text-purple-400 border border-purple-500/30 flex items-center justify-center font-bold font-mono text-base flex-shrink-0">
                03
            </div>
            <div>
                <h3 class="text-base font-bold text-white mb-1">Participate in Automated Rounds</h3>
                <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                    Browse active rounds created by the master cron scheduler. Choose your position, specify odds, and place your wager before the round betting window closes.
                </p>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="glass-card p-6 sm:p-8 rounded-2xl flex gap-6 items-start">
            <div class="w-10 h-10 rounded-xl bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center font-bold font-mono text-base flex-shrink-0">
                04
            </div>
            <div>
                <h3 class="text-base font-bold text-white mb-1">RNG Outcome Generation & Settlement</h3>
                <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                    Once betting closes, the platform generates a cryptographic seed hash. Winning bets are calculated automatically by the settlement engine, and payouts are immediately deposited into your balance.
                </p>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="glass-card p-6 sm:p-8 rounded-2xl flex gap-6 items-start">
            <div class="w-10 h-10 rounded-xl bg-cyan-600/20 text-cyan-400 border border-cyan-500/30 flex items-center justify-center font-bold font-mono text-base flex-shrink-0">
                05
            </div>
            <div>
                <h3 class="text-base font-bold text-white mb-1">Instant Withdrawals</h3>
                <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                    Request a withdrawal at any time to your preferred cryptocurrency wallet or bank account. Withdrawals are processed with complete ledger transparency and zero hidden deduction fees.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
