<?php
/**
 * Administrator Layout Header
 * Apex Gaming Platform
 * Clean, Unified Responsive Architecture
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/admin-auth.php';

require_admin();
$authAdmin = get_auth_admin();
$siteName = get_setting('site_name', 'Apex Gaming Platform');
$pageTitle = $pageTitle ?? 'Admin Console';
$activeAdminNav = $activeAdminNav ?? 'dashboard';

// Quick stats for badges
$pdo = get_db();
$pendingDepositsCount = (int)$pdo->query("SELECT COUNT(*) FROM deposits WHERE status = 'pending'")->fetchColumn();
$pendingWithdrawalsCount = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
$openTicketsCount = (int)$pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= e($pageTitle) ?> — Admin Console</title>
    <!-- Tailwind CSS with custom configuration -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            950: '#06080d',
                            900: '#0b0f19',
                            850: '#111726',
                            800: '#172033',
                            750: '#1c273e',
                            700: '#222f4b',
                        },
                        brand: {
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script>
        // Immediate Zero-Lag Sidebar Handlers
        function openAdminSidebar() {
            var sb = document.getElementById('adminSidebar');
            var bd = document.getElementById('adminSidebarBackdrop');
            if (sb) {
                sb.classList.remove('-translate-x-full');
                sb.classList.add('translate-x-0');
            }
            if (bd) {
                bd.classList.remove('hidden');
            }
            document.body.classList.add('overflow-hidden');
        }

        function closeAdminSidebar() {
            var sb = document.getElementById('adminSidebar');
            var bd = document.getElementById('adminSidebarBackdrop');
            if (sb) {
                sb.classList.remove('translate-x-0');
                sb.classList.add('-translate-x-full');
            }
            if (bd) {
                bd.classList.add('hidden');
            }
            document.body.classList.remove('overflow-hidden');
        }

        function toggleAdminSidebar() {
            var sb = document.getElementById('adminSidebar');
            if (!sb) return;
            if (sb.classList.contains('translate-x-0')) {
                closeAdminSidebar();
            } else {
                openAdminSidebar();
            }
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                closeAdminSidebar();
            }
        });
    </script>
    <script src="/assets/js/main.js" defer></script>
</head>
<body class="bg-dark-950 text-slate-200 min-h-screen flex flex-col selection:bg-brand-600 selection:text-white antialiased overflow-x-hidden">

    <!-- Admin Master Layout Shell -->
    <div class="flex-1 flex min-h-screen w-full min-w-0 relative">
        
        <!-- Mobile Drawer Backdrop Overlay -->
        <div id="adminSidebarBackdrop" class="fixed inset-0 z-40 bg-black/75 backdrop-blur-sm hidden transition-opacity duration-300 cursor-pointer" onclick="closeAdminSidebar()"></div>

        <!-- Admin Sidebar Navigation: Off-canvas drawer on mobile (<768px), fixed column on desktop (>=768px) -->
        <aside id="adminSidebar" class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] bg-dark-900 border-r border-white/[0.06] flex flex-col justify-between shadow-2xl transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 md:static md:w-64 md:flex-shrink-0 md:shadow-none">
            
            <div class="h-full overflow-y-auto px-4 py-5 space-y-5">
                
                <!-- Admin Brand & Mobile Close Button -->
                <div class="flex items-center justify-between px-2 pb-2 border-b border-white/[0.06]">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-600 flex items-center justify-center text-white font-bold shadow-lg shadow-brand-600/30">
                            A
                        </div>
                        <div>
                            <span class="text-sm font-black tracking-tight text-white block">APEX<span class="text-brand-500">ADMIN</span></span>
                            <span class="text-[10px] text-slate-400 font-mono">v<?= APP_VERSION ?> • Core Engine</span>
                        </div>
                    </div>
                    <!-- Close button on Mobile (< 768px) -->
                    <button type="button" id="adminCloseSidebar" onclick="closeAdminSidebar()" class="md:hidden p-1.5 rounded-lg bg-dark-800 text-slate-400 hover:text-white hover:bg-dark-750 transition-colors cursor-pointer" aria-label="Close Sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Nav Menu Sections -->
                <nav class="space-y-4 text-xs font-medium">

                    <!-- Dashboard -->
                    <div>
                        <a href="/admin/dashboard.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'dashboard' ? 'bg-brand-600 text-white font-semibold shadow-lg shadow-brand-600/30' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">
                            <svg class="w-4 h-4 <?= $activeAdminNav === 'dashboard' ? 'text-white' : 'text-brand-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            <span>Dashboard</span>
                        </a>
                    </div>

                    <!-- User Management -->
                    <div>
                        <div class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">User Management</div>
                        <div class="space-y-0.5">
                            <a href="/admin/users.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'users' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                <span>All Users</span>
                            </a>
                            <a href="/admin/user-wallet.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'user-wallet' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                <span>User Wallets</span>
                            </a>
                            <a href="/admin/user-transactions.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'user-transactions' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <span>User Ledger</span>
                            </a>
                            <a href="/admin/user-bets.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'user-bets' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>User Bets</span>
                            </a>
                        </div>
                    </div>

                    <!-- Gaming Infrastructure -->
                    <div>
                        <div class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Gaming Engine</div>
                        <div class="space-y-0.5">
                            <a href="/admin/rounds.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'rounds' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span>Rounds Management</span>
                            </a>
                            <a href="/admin/live-monitor.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'live-monitor' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <span>Live Monitor</span>
                            </a>
                            <a href="/admin/bets.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'bets' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <span>All Bets</span>
                            </a>
                            <a href="/admin/results.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'results' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                <span>Results & RNG</span>
                            </a>
                            <a href="/admin/settlements.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'settlements' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <span>Settlements</span>
                            </a>
                        </div>
                    </div>

                    <!-- Finances & Banking -->
                    <div>
                        <div class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Finance & Banking</div>
                        <div class="space-y-0.5">
                            <a href="/admin/wallet.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'wallet' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                <span>Master Wallets</span>
                            </a>
                            <a href="/admin/deposits.php" class="flex items-center justify-between px-3 py-2 rounded-xl <?= $activeAdminNav === 'deposits' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
                                    <span>Deposits</span>
                                </span>
                                <?php if ($pendingDepositsCount > 0): ?>
                                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-amber-500/20 text-amber-400 rounded-md"><?= $pendingDepositsCount ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="/admin/withdrawals.php" class="flex items-center justify-between px-3 py-2 rounded-xl <?= $activeAdminNav === 'withdrawals' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path></svg>
                                    <span>Withdrawals</span>
                                </span>
                                <?php if ($pendingWithdrawalsCount > 0): ?>
                                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-amber-500/20 text-amber-400 rounded-md"><?= $pendingWithdrawalsCount ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="/admin/transactions.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'transactions' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <span>Transactions</span>
                            </a>
                            <a href="/admin/payment-settings.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'payment-settings' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                                <span>Payment Settings</span>
                            </a>
                        </div>
                    </div>

                    <!-- Marketing & Support -->
                    <div>
                        <div class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Marketing & Support</div>
                        <div class="space-y-0.5">
                            <a href="/admin/referrals.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'referrals' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <span>Referrals</span>
                            </a>
                            <a href="/admin/bonuses.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'bonuses' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
                                <span>Bonuses</span>
                            </a>
                            <a href="/admin/promotions.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'promotions' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                <span>Promotions</span>
                            </a>
                            <a href="/admin/notifications.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'notifications' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                <span>Notifications</span>
                            </a>
                            <a href="/admin/tickets.php" class="flex items-center justify-between px-3 py-2 rounded-xl <?= $activeAdminNav === 'tickets' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                    <span>Tickets</span>
                                </span>
                                <?php if ($openTicketsCount > 0): ?>
                                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-blue-500/20 text-blue-400 rounded-md"><?= $openTicketsCount ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>

                    <!-- Reports & Audit Logs -->
                    <div>
                        <div class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Reports & Logs</div>
                        <div class="space-y-0.5">
                            <a href="/admin/reports.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'reports' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <span>General Reports</span>
                            </a>
                            <a href="/admin/financial-reports.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'financial-reports' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Financial Reports</span>
                            </a>
                            <a href="/admin/activity-logs.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'activity-logs' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Admin Activity</span>
                            </a>
                            <a href="/admin/login-logs.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'login-logs' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                <span>Login Logs</span>
                            </a>
                            <a href="/admin/security-logs.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'security-logs' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                <span>Security Logs</span>
                            </a>
                        </div>
                    </div>

                    <!-- System & Platform -->
                    <div>
                        <div class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">System & Platform</div>
                        <div class="space-y-0.5">
                            <a href="/admin/settings.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'settings' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <span>Settings</span>
                            </a>
                            <a href="/admin/site-settings.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'site-settings' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                <span>Site Identity</span>
                            </a>
                            <a href="/admin/legal-settings.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'legal-settings' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>Legal & Terms</span>
                            </a>
                            <a href="/admin/maintenance.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'maintenance' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span>Maintenance Mode</span>
                            </a>
                            <a href="/admin/cron-settings.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'cron-settings' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Master Cron</span>
                            </a>
                            <a href="/admin/updates.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'updates' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                <span>Updates</span>
                            </a>
                            <a href="/admin/backups.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeAdminNav === 'backups' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                <span>Backups</span>
                            </a>
                        </div>
                    </div>

                    <!-- Direct Logout -->
                    <div class="pt-2">
                        <a href="/admin/logout.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-rose-400 hover:bg-rose-500/10 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            <span>Logout</span>
                        </a>
                    </div>

                </nav>
            </div>

            <!-- Admin Profile Footer in Sidebar -->
            <div class="p-4 border-t border-white/[0.06] flex items-center justify-between bg-dark-900/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-brand-600 text-white flex items-center justify-center font-bold text-xs uppercase shadow-md shadow-brand-600/30">
                        <?= substr($authAdmin['username'], 0, 2) ?>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-white leading-tight"><?= e($authAdmin['username']) ?></div>
                        <div class="text-[10px] text-brand-400 font-medium">Super Admin</div>
                    </div>
                </div>
                <a href="/admin/logout.php" title="Sign out" class="text-slate-400 hover:text-rose-400 transition-colors p-1.5 rounded-lg hover:bg-white/[0.04]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </a>
            </div>
        </aside>

        <!-- Main Admin Content Area: Full available width on mobile, adjacent to sidebar on desktop -->
        <div class="flex-1 flex flex-col min-w-0 w-full min-h-screen overflow-x-hidden">
            
            <!-- Top Admin Header Bar -->
            <header class="h-16 bg-dark-900/90 border-b border-white/[0.06] backdrop-blur-md px-3 sm:px-6 flex items-center justify-between sticky top-0 z-30 flex-shrink-0 w-full min-w-0">
                <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                    <!-- Hamburger / Menu Button (Mobile < 768px) -->
                    <button type="button" id="adminSidebarToggle" onclick="toggleAdminSidebar()" class="md:hidden p-2 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.06] text-slate-300 hover:text-white transition-colors flex-shrink-0 cursor-pointer active:scale-95" aria-label="Toggle Sidebar Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="text-sm font-bold text-white tracking-tight truncate max-w-[140px] sm:max-w-xs md:max-w-none"><?= e($pageTitle) ?></h2>
                </div>
                
                <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                    <a href="/index.php" target="_blank" class="px-2.5 sm:px-3 py-1.5 rounded-lg bg-dark-850 hover:bg-dark-800 border border-white/[0.06] text-xs font-medium text-slate-300 hover:text-white flex items-center gap-1.5 transition-colors">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        <span class="hidden sm:inline">View Public Site</span>
                    </a>
                    <a href="/admin/change-password.php" class="hidden sm:inline-flex px-3 py-1.5 rounded-lg bg-dark-850 hover:bg-dark-800 border border-white/[0.06] text-xs font-medium text-slate-300 hover:text-white transition-colors">
                        Security
                    </a>
                    <a href="/admin/logout.php" class="px-2.5 sm:px-3 py-1.5 rounded-lg bg-rose-600/10 hover:bg-rose-600/20 text-rose-400 text-xs font-semibold transition-colors flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>
            </header>

            <!-- Main Admin Content Area Wrapper -->
            <main class="flex-1 p-3.5 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6 min-w-0">
                <?= render_flash() ?>
