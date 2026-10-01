<?php
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$isGuestAuthPage = in_array($currentScript, ['login.php', 'register.php', 'forgot-password.php', 'reset-password.php']);
$isUserPanel = !empty($authUser) && (strpos($_SERVER['REQUEST_URI'] ?? '', '/user/') !== false || strpos($_SERVER['PHP_SELF'] ?? '', '/user/') !== false) && !$isGuestAuthPage;
?>

<?php if ($isUserPanel): ?>
            </div> <!-- End p-4 sm:p-6 lg:p-8 -->
        </div> <!-- End main content area -->
    </div> <!-- End flex layout -->
</body>
</html>
<?php else: ?>
        </div>
    </main>

    <!-- Platform Footer -->
    <footer class="bg-dark-900 border-t border-white/[0.06] mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                
                <!-- Brand Info -->
                <div class="space-y-4 md:col-span-1">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-brand-600 to-blue-400 flex items-center justify-center text-white font-bold text-sm">
                            A
                        </div>
                        <span class="text-base font-black tracking-tight text-white">APEX<span class="text-brand-500">GAMING</span></span>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Enterprise-grade common gaming infrastructure powered by deterministic cryptographic hashing, atomic relational ledgers, and real-time round automation.
                    </p>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-dark-850 border border-white/[0.06] text-[11px] text-slate-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span>Engine Status: Operational</span>
                    </div>
                </div>

                <!-- Navigation -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300 mb-3">Platform</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><a href="/index.php" class="hover:text-white transition-colors">Public Portal</a></li>
                        <li><a href="/pages/how-it-works.php" class="hover:text-white transition-colors">How It Works</a></li>
                        <li><a href="/pages/about.php" class="hover:text-white transition-colors">About Us</a></li>
                        <li><a href="/pages/faq.php" class="hover:text-white transition-colors">Frequently Asked Questions</a></li>
                        <li><a href="/pages/contact.php" class="hover:text-white transition-colors">Contact Support</a></li>
                    </ul>
                </div>

                <!-- User & Finance -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300 mb-3">Player Account</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><a href="/user/login.php" class="hover:text-white transition-colors">Account Sign In</a></li>
                        <li><a href="/user/register.php" class="hover:text-white transition-colors">Register Player</a></li>
                        <li><a href="/user/wallet.php" class="hover:text-white transition-colors">Deposit & Withdraw</a></li>
                        <li><a href="/user/referral.php" class="hover:text-white transition-colors">Affiliate Program</a></li>
                        <li><a href="/user/support.php" class="hover:text-white transition-colors">Helpdesk Tickets</a></li>
                    </ul>
                </div>

                <!-- Legal & Compliance -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300 mb-3">Compliance & Trust</h4>
                    <ul class="space-y-2 text-xs text-slate-400">
                        <li><a href="/pages/terms.php" class="hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="/pages/privacy.php" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="/pages/responsible-gaming.php" class="hover:text-white transition-colors">Responsible Gaming (18+)</a></li>
                    </ul>
                    <div class="mt-4 p-3 rounded-xl bg-dark-850 border border-white/[0.06] text-[11px] text-slate-400">
                        <strong class="text-slate-300 block mb-1">18+ Age Restriction</strong>
                        Participation strictly restricted to users of legal gambling age in their jurisdiction. Play responsibly.
                    </div>
                </div>

            </div>

            <!-- Copyright and disclaimers -->
            <div class="pt-8 border-t border-white/[0.06] flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>&copy; <?= date('Y') ?> Apex Gaming Platform. All rights reserved. Real MySQL Ledger Verified.</p>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center gap-1.5 text-slate-400 font-mono text-[11px]">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        v<?= APP_VERSION ?>
                    </span>
                    <a href="/pages/terms.php" class="hover:text-slate-400">Terms</a>
                    <a href="/pages/privacy.php" class="hover:text-slate-400">Privacy</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
<?php endif; ?>
