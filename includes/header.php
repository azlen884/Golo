<?php
/**
 * Platform Header Layout
 * Apex Gaming Platform
 * Clean, Unified Responsive Architecture
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notifications.php';

$authUser = get_auth_user();
$siteName = get_setting('site_name', 'Apex Gaming Platform');
$unreadNotifs = $authUser ? get_unread_notifications_count((int)$authUser['id']) : 0;
$pageTitle = $pageTitle ?? $siteName;
$activeNav = $activeNav ?? '';

$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$isGuestAuthPage = in_array($currentScript, ['login.php', 'register.php', 'forgot-password.php', 'reset-password.php']);
$isUserPanel = !empty($authUser) && (strpos($_SERVER['REQUEST_URI'] ?? '', '/user/') !== false || strpos($_SERVER['PHP_SELF'] ?? '', '/user/') !== false) && !$isGuestAuthPage;
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= e($pageTitle) ?></title>
    <!-- Tailwind CSS with custom configuration -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            950: '#07090e',
                            900: '#0c101a',
                            850: '#111726',
                            800: '#172033',
                            750: '#1c273e',
                            700: '#222f4b',
                        },
                        brand: {
                            50: '#eff6ff',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        accent: {
                            cyan: '#06b6d4',
                            emerald: '#10b981',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script>
        // Immediate Zero-Lag User Sidebar Handlers
        function openUserSidebar() {
            var sb = document.getElementById('userSidebar');
            var bd = document.getElementById('userSidebarBackdrop');
            if (sb) {
                sb.classList.remove('-translate-x-full');
                sb.classList.add('translate-x-0');
            }
            if (bd) {
                bd.classList.remove('hidden');
            }
            document.body.classList.add('overflow-hidden');
        }

        function closeUserSidebar() {
            var sb = document.getElementById('userSidebar');
            var bd = document.getElementById('userSidebarBackdrop');
            if (sb) {
                sb.classList.remove('translate-x-0');
                sb.classList.add('-translate-x-full');
            }
            if (bd) {
                bd.classList.add('hidden');
            }
            document.body.classList.remove('overflow-hidden');
        }

        function toggleUserSidebar() {
            var sb = document.getElementById('userSidebar');
            if (!sb) return;
            if (sb.classList.contains('translate-x-0')) {
                closeUserSidebar();
            } else {
                openUserSidebar();
            }
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                closeUserSidebar();
            }
        });
    </script>
    <script src="/assets/js/main.js" defer></script>
</head>
<body class="bg-dark-950 text-slate-200 min-h-screen flex flex-col selection:bg-brand-600 selection:text-white antialiased overflow-x-hidden">

<?php if ($isUserPanel): ?>

    <!-- User Master Layout Shell -->
    <div class="flex-1 flex min-h-screen w-full min-w-0 relative">
        
        <!-- Mobile Drawer Backdrop Overlay -->
        <div id="userSidebarBackdrop" class="fixed inset-0 z-40 bg-black/75 backdrop-blur-sm hidden transition-opacity duration-300 cursor-pointer" onclick="closeUserSidebar()"></div>

        <!-- User Sidebar: Off-canvas drawer on mobile (<768px), fixed column on desktop (>=768px) -->
        <aside id="userSidebar" class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] bg-dark-900 border-r border-white/[0.06] flex flex-col justify-between shadow-2xl transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 md:static md:w-64 lg:w-72 md:flex-shrink-0 md:shadow-none">
            
            <div class="h-full overflow-y-auto px-4 py-5 space-y-5">
                
                <!-- User Brand & Mobile Close Button -->
                <div class="flex items-center justify-between px-2 pb-2 border-b border-white/[0.06]">
                    <a href="/index.php" class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-blue-400 flex items-center justify-center text-white font-bold shadow-lg shadow-brand-600/30">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div>
                            <span class="text-sm font-black tracking-tight text-white block">APEX<span class="text-brand-500">GAMING</span></span>
                            <span class="text-[10px] text-slate-400 font-mono">Player Portal</span>
                        </div>
                    </a>
                    <!-- Close button on Mobile (< 768px) -->
                    <button type="button" id="userCloseSidebar" onclick="closeUserSidebar()" class="md:hidden p-1.5 rounded-lg bg-dark-800 text-slate-400 hover:text-white hover:bg-dark-750 transition-colors cursor-pointer" aria-label="Close Sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Player Quick Balance Card in Sidebar -->
                <div class="p-3.5 rounded-2xl bg-dark-850 border border-white/[0.06]">
                    <div class="flex items-center justify-between text-xs text-slate-400 mb-1">
                        <span>Total Balance</span>
                        <a href="/user/deposit.php" class="text-brand-400 hover:text-brand-300 font-semibold text-[11px]">+ Deposit</a>
                    </div>
                    <div class="text-lg font-bold font-mono text-emerald-400">
                        <?= format_money($authUser['balance']) ?>
                    </div>
                    <div class="mt-1 flex items-center gap-1.5 text-[10px] text-slate-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span>Wallet Verified &amp; Active</span>
                    </div>
                </div>

                <!-- User Navigation Links -->
                <nav class="space-y-4 text-xs font-medium">

                    <!-- Core Overview -->
                    <div class="space-y-0.5">
                        <a href="/user/dashboard.php" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl <?= $activeNav === 'dashboard' ? 'bg-brand-600 text-white font-semibold shadow-lg shadow-brand-600/30' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">
                            <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            <span>Dashboard</span>
                        </a>
                    </div>

                    <!-- Finances & Banking -->
                    <div>
                        <div class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Finance & Banking</div>
                        <div class="space-y-0.5">
                            <a href="/user/wallet.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeNav === 'wallet' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                <span>Wallet</span>
                            </a>
                            <a href="/user/deposit.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeNav === 'deposit' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                <span>Deposit</span>
                            </a>
                            <a href="/user/withdrawal.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeNav === 'withdrawal' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                <span>Withdrawal</span>
                            </a>
                            <a href="/user/transactions.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeNav === 'transactions' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <span>Transactions</span>
                            </a>
                        </div>
                    </div>

                    <!-- Gaming & Activity -->
                    <div>
                        <div class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Gaming Activity</div>
                        <div class="space-y-0.5">
                            <a href="/user/my-bets.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeNav === 'my-bets' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>My Bets</span>
                            </a>
                            <a href="/user/game-history.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeNav === 'game-history' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <span>Game History</span>
                            </a>
                            <a href="/user/notifications.php" class="flex items-center justify-between px-3 py-2 rounded-xl <?= $activeNav === 'notifications' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                    <span>Notifications</span>
                                </span>
                                <?php if ($unreadNotifs > 0): ?>
                                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-brand-500 text-white rounded-full"><?= $unreadNotifs > 9 ? '9+' : $unreadNotifs ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>

                    <!-- Account & Security -->
                    <div>
                        <div class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Account & Security</div>
                        <div class="space-y-0.5">
                            <a href="/user/profile.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeNav === 'profile' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <span>Profile</span>
                            </a>
                            <a href="/user/security.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeNav === 'security' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                <span>Security</span>
                            </a>
                        </div>
                    </div>

                    <!-- Rewards & Help -->
                    <div>
                        <div class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Rewards & Help</div>
                        <div class="space-y-0.5">
                            <a href="/user/referral.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeNav === 'referral' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
                                <span>Referral</span>
                            </a>
                            <a href="/user/bonuses.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeNav === 'bonuses' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Bonuses</span>
                            </a>
                            <a href="/user/promotions.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeNav === 'promotions' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                <span>Promotions</span>
                            </a>
                            <a href="/user/support.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl <?= $activeNav === 'support' ? 'bg-brand-600/10 text-brand-400 font-semibold' : 'text-slate-400 hover:text-slate-200 hover:bg-white/[0.03]' ?> transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                <span>Support</span>
                            </a>
                        </div>
                    </div>

                    <!-- Direct Logout -->
                    <div class="pt-2">
                        <a href="/user/logout.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-rose-400 hover:bg-rose-500/10 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            <span>Logout</span>
                        </a>
                    </div>

                </nav>
            </div>

            <!-- User Profile Footer in Sidebar -->
            <div class="p-4 border-t border-white/[0.06] flex items-center justify-between bg-dark-900/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-brand-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs uppercase shadow-md shadow-brand-600/30">
                        <?= substr($authUser['username'], 0, 2) ?>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-white leading-tight truncate max-w-[120px]"><?= e($authUser['username']) ?></div>
                        <div class="text-[10px] text-emerald-400 font-medium">Verified Player</div>
                    </div>
                </div>
                <a href="/user/logout.php" title="Sign out" class="text-slate-400 hover:text-rose-400 transition-colors p-1.5 rounded-lg hover:bg-white/[0.04]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </a>
            </div>
        </aside>

        <!-- Main User Content Area: Full available width on mobile, adjacent to sidebar on desktop -->
        <div class="flex-1 flex flex-col min-w-0 w-full min-h-screen overflow-x-hidden">
            
            <!-- Top User Header Bar -->
            <header class="h-16 bg-dark-900/90 border-b border-white/[0.06] backdrop-blur-md px-3 sm:px-6 flex items-center justify-between sticky top-0 z-30 flex-shrink-0 w-full min-w-0">
                <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
                    <!-- Hamburger / Menu Button (Mobile < 768px) -->
                    <button type="button" id="userSidebarToggle" onclick="toggleUserSidebar()" class="md:hidden p-2 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.06] text-slate-300 hover:text-white transition-colors flex-shrink-0 cursor-pointer active:scale-95" aria-label="Toggle Navigation Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="text-xs sm:text-sm font-bold text-white tracking-tight truncate max-w-[130px] sm:max-w-xs md:max-w-none"><?= e($pageTitle) ?></h2>
                </div>
                
                <div class="flex items-center gap-1.5 sm:gap-2.5 flex-shrink-0">
                    <!-- Balance Indicator -->
                    <div class="flex items-center gap-1 sm:gap-2 bg-dark-850 border border-white/[0.08] px-2 sm:px-3 py-1 sm:py-1.5 rounded-xl">
                        <span class="text-[11px] sm:text-xs font-bold text-emerald-400 font-mono"><?= format_money($authUser['balance']) ?></span>
                        <a href="/user/deposit.php" class="px-1.5 sm:px-2 py-0.5 rounded-md bg-brand-600 hover:bg-brand-500 text-white text-[10px] sm:text-[11px] font-semibold transition-all">
                            +<span class="hidden sm:inline"> Add</span>
                        </a>
                    </div>

                    <!-- Notifications Bell -->
                    <a href="/user/notifications.php" class="relative p-1.5 sm:p-2 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.06] text-slate-300 hover:text-white transition-colors" title="Notifications">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        <?php if ($unreadNotifs > 0): ?>
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-brand-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center animate-pulse">
                                <?= $unreadNotifs > 9 ? '9+' : $unreadNotifs ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- User Profile Dropdown -->
                    <div class="relative">
                        <button type="button" data-dropdown-toggle="userHeaderDropdown" class="flex items-center gap-1.5 p-1 sm:p-1.5 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.06] transition-colors cursor-pointer" aria-label="User Profile">
                            <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-brand-600 to-indigo-600 flex items-center justify-center text-white text-xs font-bold uppercase flex-shrink-0">
                                <?= substr($authUser['username'], 0, 2) ?>
                            </div>
                            <span class="text-xs font-semibold text-slate-200 hidden sm:inline-block max-w-[90px] truncate"><?= e($authUser['username']) ?></span>
                            <svg class="w-3.5 h-3.5 text-slate-400 hidden sm:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>

                        <div id="userHeaderDropdown" class="dropdown-menu hidden absolute right-0 mt-2 w-52 rounded-2xl bg-dark-900 border border-white/[0.08] shadow-2xl p-2 z-50 animate-fadeIn">
                            <div class="px-3 py-2 border-b border-white/[0.06] mb-1">
                                <div class="text-xs font-bold text-white"><?= e($authUser['username']) ?></div>
                                <div class="text-[11px] text-slate-400 truncate"><?= e($authUser['email']) ?></div>
                            </div>
                            <a href="/user/profile.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-white/[0.04]">
                                Profile &amp; KYC
                            </a>
                            <a href="/user/security.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-white/[0.04]">
                                Security
                            </a>
                            <a href="/index.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-white/[0.04]">
                                Public Portal
                            </a>
                            <div class="border-t border-white/[0.06] mt-1 pt-1">
                                <a href="/user/logout.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-rose-400 hover:bg-rose-500/10">
                                    Sign Out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Page Content Wrapper -->
            <main class="flex-1 p-3.5 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6 min-w-0">
                <?= render_flash() ?>

<?php else: ?>

    <!-- PUBLIC LANDING & GUEST HEADER LAYOUT -->
    <header class="sticky top-0 z-40 bg-dark-900/80 backdrop-blur-xl border-b border-white/[0.06]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                
                <!-- Logo & Brand -->
                <div class="flex items-center gap-8">
                    <a href="/index.php" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-blue-400 flex items-center justify-center shadow-lg shadow-brand-600/30 group-hover:scale-105 transition-transform duration-200">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div>
                            <span class="text-lg font-black tracking-tight text-white block leading-none">APEX<span class="text-brand-500">GAMING</span></span>
                            <span class="text-[10px] text-slate-400 tracking-wider uppercase font-semibold">Fair Engine</span>
                        </div>
                    </a>

                    <!-- Desktop Nav Links -->
                    <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
                        <a href="/index.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'home' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">Home</a>
                        <a href="/pages/how-it-works.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'how' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">How It Works</a>
                        <a href="/pages/about.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'about' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">About</a>
                        <a href="/pages/faq.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'faq' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">FAQ</a>
                        <a href="/pages/responsible-gaming.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'rg' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">Fair Gaming</a>
                    </nav>
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center gap-3">
                    <?php if ($authUser): ?>
                        <a href="/user/dashboard.php" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-brand-600 hover:bg-brand-500 shadow-lg shadow-brand-600/30 transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            <span>Dashboard</span>
                        </a>
                    <?php else: ?>
                        <!-- Guest Sign In / Register -->
                        <a href="/user/login.php" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/[0.05] transition-colors">
                            Sign In
                        </a>
                        <a href="/user/register.php" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-brand-600 hover:bg-brand-500 shadow-lg shadow-brand-600/30 transition-all">
                            Register
                        </a>
                    <?php endif; ?>

                    <!-- Mobile Menu Hamburger for Public Site -->
                    <button type="button" id="mobileMenuBtn" class="md:hidden p-2 rounded-xl bg-dark-850 border border-white/[0.06] text-slate-300 hover:text-white" aria-label="Toggle Public Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Drawer Navigation for Public Site -->
        <div id="mobileDrawer" class="hidden md:hidden fixed inset-0 z-50 bg-dark-950/95 backdrop-blur-2xl p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-6 border-b border-white/[0.08]">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-brand-600 flex items-center justify-center text-white font-bold">A</div>
                        <span class="font-bold text-white tracking-tight">APEX GAMING</span>
                    </div>
                    <button type="button" id="closeDrawerBtn" class="p-2 rounded-xl bg-dark-850 text-slate-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="py-6 space-y-2">
                    <a href="/index.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">Home</a>
                    <a href="/pages/how-it-works.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">How It Works</a>
                    <a href="/pages/about.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">About</a>
                    <a href="/pages/faq.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">FAQ</a>
                    <a href="/pages/responsible-gaming.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">Fair Gaming</a>
                </div>
            </div>

            <div class="border-t border-white/[0.08] pt-4">
                <?php if ($authUser): ?>
                    <a href="/user/dashboard.php" class="block text-center py-3 rounded-xl bg-brand-600 text-white font-semibold text-sm">Go to Dashboard</a>
                <?php else: ?>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="/user/login.php" class="py-3 rounded-xl text-center font-semibold text-sm bg-dark-850 text-white">Sign In</a>
                        <a href="/user/register.php" class="py-3 rounded-xl text-center font-semibold text-sm bg-brand-600 text-white">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content Container with Flash Messages for Public Site -->
    <main class="flex-grow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <?= render_flash() ?>

<?php endif; ?>
