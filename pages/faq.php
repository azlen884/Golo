<?php
$pageTitle = 'Frequently Asked Questions — Apex Gaming Platform';
$activeNav = 'faq';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto py-8">
    <div class="mb-10 text-center">
        <span class="text-xs font-bold text-brand-400 uppercase tracking-wider">Help & Answers</span>
        <h1 class="text-3xl sm:text-4xl font-black text-white mt-2">Frequently Asked Questions</h1>
        <p class="text-slate-400 text-sm mt-2">Everything you need to know about the platform infrastructure.</p>
    </div>

    <div class="space-y-4">
        <div class="glass-card p-6 rounded-2xl">
            <h3 class="text-sm font-bold text-white mb-2">How are game round results generated?</h3>
            <p class="text-xs text-slate-400 leading-relaxed">
                Results are determined using cryptographically secure random number generators (CSPRNG). Each round generates a unique seed and SHA-256 hash published to the database, ensuring no retrospective outcome manipulation can take place.
            </p>
        </div>

        <div class="glass-card p-6 rounded-2xl">
            <h3 class="text-sm font-bold text-white mb-2">What is the minimum deposit and withdrawal limit?</h3>
            <p class="text-xs text-slate-400 leading-relaxed">
                Minimum deposit is <?= format_money(get_setting('min_deposit', 10)) ?> and minimum withdrawal is <?= format_money(get_setting('min_withdrawal', 20)) ?>. Limits vary depending on the chosen payment method (crypto vs bank wire).
            </p>
        </div>

        <div class="glass-card p-6 rounded-2xl">
            <h3 class="text-sm font-bold text-white mb-2">How does the affiliate referral system work?</h3>
            <p class="text-xs text-slate-400 leading-relaxed">
                Every registered user receives a unique referral code and URL. When an invited player signs up and deposits, the referrer automatically receives <?= e(get_setting('referral_commission_rate', '5.00')) ?>% commission credited directly to their wallet ledger.
            </p>
        </div>

        <div class="glass-card p-6 rounded-2xl">
            <h3 class="text-sm font-bold text-white mb-2">How long do withdrawals take to process?</h3>
            <p class="text-xs text-slate-400 leading-relaxed">
                Cryptocurrency withdrawals (USDT, BTC, ETH) are processed swiftly upon verification, typically within 15–60 minutes. Bank wire transfers require 1 to 3 business days for interbank settlement.
            </p>
        </div>

        <div class="glass-card p-6 rounded-2xl">
            <h3 class="text-sm font-bold text-white mb-2">What happens if a round is interrupted or cancelled?</h3>
            <p class="text-xs text-slate-400 leading-relaxed">
                If any round cannot be resolved due to external disruptions, our atomic settlement engine marks the round as cancelled, and 100% of all placed bets are immediately refunded back to player balances.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
