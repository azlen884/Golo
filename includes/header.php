<?php
/**
 * Platform Header Layout
 * Apex Gaming Platform
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
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <script src="/assets/js/main.js" defer></script>
</head>
<body class="bg-dark-950 text-slate-200 min-h-screen flex flex-col selection:bg-brand-600 selection:text-white">

    <!-- Top Navigation Bar -->
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
                        <?php if ($authUser): ?>
                            <a href="/user/dashboard.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'dashboard' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">Dashboard</a>
                            <a href="/user/wallet.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'wallet' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">Wallet</a>
                            <a href="/user/my-bets.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'my-bets' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">My Bets</a>
                            <a href="/user/game-history.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'game-history' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">Rounds</a>
                            <a href="/user/promotions.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'promotions' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">Promos</a>
                            <a href="/user/support.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'support' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">Support</a>
                        <?php else: ?>
                            <a href="/index.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'home' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">Home</a>
                            <a href="/pages/how-it-works.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'how' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">How It Works</a>
                            <a href="/pages/about.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'about' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">About</a>
                            <a href="/pages/faq.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'faq' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">FAQ</a>
                            <a href="/pages/responsible-gaming.php" class="px-3 py-2 rounded-lg <?= $activeNav === 'rg' ? 'text-brand-400 bg-brand-600/10' : 'text-slate-300 hover:text-white hover:bg-white/[0.04]' ?> transition-colors">Fair Gaming</a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Right Side Actions -->
                <div class="flex items-center gap-3">
                    <?php if ($authUser): ?>
                        <!-- Real Wallet Balance Badge -->
                        <div class="hidden sm:flex items-center gap-3 bg-dark-850 border border-white/[0.08] px-3.5 py-1.5 rounded-xl">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Balance</span>
                                <span class="text-sm font-bold text-emerald-400 font-mono"><?= format_money($authUser['balance']) ?></span>
                            </div>
                            <a href="/user/deposit.php" class="px-3 py-1 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-xs font-semibold shadow-md shadow-brand-600/30 transition-all">
                                Deposit
                            </a>
                        </div>

                        <!-- Notifications Bell -->
                        <a href="/user/notifications.php" class="relative p-2 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.06] text-slate-300 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <?php if ($unreadNotifs > 0): ?>
                                <span class="absolute -top-1 -right-1 w-4 h-4 bg-brand-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center animate-pulse">
                                    <?= $unreadNotifs > 9 ? '9+' : $unreadNotifs ?>
                                </span>
                            <?php endif; ?>
                        </a>

                        <!-- User Profile Dropdown -->
                        <div class="relative">
                            <button type="button" data-dropdown-toggle="userDropdown" class="flex items-center gap-2 p-1.5 rounded-xl bg-dark-850 hover:bg-dark-800 border border-white/[0.06] transition-colors">
                                <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-brand-600 to-indigo-600 flex items-center justify-center text-white text-xs font-bold uppercase">
                                    <?= substr($authUser['username'], 0, 2) ?>
                                </div>
                                <span class="text-xs font-semibold text-slate-200 hidden sm:inline-block max-w-[100px] truncate"><?= e($authUser['username']) ?></span>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="userDropdown" class="dropdown-menu hidden absolute right-0 mt-2 w-56 rounded-2xl bg-dark-900 border border-white/[0.08] shadow-2xl p-2 z-50 animate-fadeIn">
                                <div class="px-3 py-2 border-b border-white/[0.06] mb-1">
                                    <div class="text-xs font-bold text-white"><?= e($authUser['username']) ?></div>
                                    <div class="text-[11px] text-slate-400 truncate"><?= e($authUser['email']) ?></div>
                                    <div class="mt-2 text-xs font-mono text-emerald-400 font-bold"><?= format_money($authUser['balance']) ?></div>
                                </div>
                                <a href="/user/dashboard.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-white/[0.04]">
                                    <svg class="w-4 h-4 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                                    Dashboard
                                </a>
                                <a href="/user/wallet.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-white/[0.04]">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                    Wallet & Balances
                                </a>
                                <a href="/user/deposit.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-white/[0.04]">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Deposit Funds
                                </a>
                                <a href="/user/withdrawal.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-white/[0.04]">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    Withdrawal
                                </a>
                                <a href="/user/transactions.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-white/[0.04]">
                                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                    Transactions
                                </a>
                                <a href="/user/referral.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-white/[0.04]">
                                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    Referral & Affiliates
                                </a>
                                <a href="/user/profile.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-white/[0.04]">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    Profile & KYC
                                </a>
                                <a href="/user/security.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-white/[0.04]">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    Security & 2FA
                                </a>
                                <div class="border-t border-white/[0.06] mt-1 pt-1">
                                    <a href="/user/logout.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-rose-400 hover:bg-rose-500/10">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        Sign Out
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Guest Sign In / Register -->
                        <a href="/user/login.php" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/[0.05] transition-colors">
                            Sign In
                        </a>
                        <a href="/user/register.php" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-brand-600 hover:bg-brand-500 shadow-lg shadow-brand-600/30 transition-all">
                            Register
                        </a>
                    <?php endif; ?>

                    <!-- Mobile Menu Hamburger -->
                    <button type="button" id="mobileMenuBtn" class="md:hidden p-2 rounded-xl bg-dark-850 border border-white/[0.06] text-slate-300 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Drawer Navigation -->
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
                    <?php if ($authUser): ?>
                        <div class="p-4 rounded-2xl bg-dark-850 border border-white/[0.08] mb-4">
                            <div class="text-xs text-slate-400">Available Balance</div>
                            <div class="text-xl font-bold font-mono text-emerald-400 mt-1"><?= format_money($authUser['balance']) ?></div>
                        </div>
                        <a href="/user/dashboard.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">Dashboard</a>
                        <a href="/user/wallet.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">Wallet & Banking</a>
                        <a href="/user/deposit.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-brand-400 hover:bg-white/[0.04]">Deposit</a>
                        <a href="/user/withdrawal.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">Withdrawal</a>
                        <a href="/user/my-bets.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">My Bets</a>
                        <a href="/user/game-history.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">Rounds & Outcomes</a>
                        <a href="/user/referral.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">Referral Program</a>
                        <a href="/user/support.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">Support Helpdesk</a>
                    <?php else: ?>
                        <a href="/index.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">Home</a>
                        <a href="/pages/how-it-works.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">How It Works</a>
                        <a href="/pages/about.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">About</a>
                        <a href="/pages/faq.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">FAQ</a>
                        <a href="/pages/responsible-gaming.php" class="block px-4 py-3 rounded-xl text-sm font-semibold text-slate-200 hover:bg-white/[0.04]">Fair Gaming</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border-t border-white/[0.08] pt-4">
                <?php if ($authUser): ?>
                    <a href="/user/logout.php" class="block text-center py-3 rounded-xl bg-rose-600/20 text-rose-300 font-semibold text-sm">Sign Out</a>
                <?php else: ?>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="/user/login.php" class="py-3 rounded-xl text-center font-semibold text-sm bg-dark-850 text-white">Sign In</a>
                        <a href="/user/register.php" class="py-3 rounded-xl text-center font-semibold text-sm bg-brand-600 text-white">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content Container with Flash Messages -->
    <main class="flex-grow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <?= render_flash() ?>
