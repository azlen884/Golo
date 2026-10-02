/**
 * Plain JavaScript UI Helpers & Sidebar Handlers
 * Apex Gaming Platform
 */

(function () {
    // Global helper functions exposed immediately
    window.openAdminSidebar = function () {
        const adminSidebar = document.getElementById('adminSidebar');
        const adminSidebarBackdrop = document.getElementById('adminSidebarBackdrop');
        if (!adminSidebar) return;
        adminSidebar.classList.add('open');
        if (adminSidebarBackdrop) {
            adminSidebarBackdrop.classList.add('active');
            adminSidebarBackdrop.classList.remove('hidden');
        }
        document.documentElement.classList.add('sidebar-open');
        document.body.classList.add('sidebar-open');
    };

    window.closeAdminSidebar = function () {
        const adminSidebar = document.getElementById('adminSidebar');
        const adminSidebarBackdrop = document.getElementById('adminSidebarBackdrop');
        if (!adminSidebar) return;
        adminSidebar.classList.remove('open');
        if (adminSidebarBackdrop) {
            adminSidebarBackdrop.classList.remove('active');
            adminSidebarBackdrop.classList.add('hidden');
        }
        document.documentElement.classList.remove('sidebar-open');
        document.body.classList.remove('sidebar-open');
    };

    window.toggleAdminSidebar = function (e) {
        if (e && e.preventDefault) e.preventDefault();
        const adminSidebar = document.getElementById('adminSidebar');
        if (adminSidebar && adminSidebar.classList.contains('open')) {
            window.closeAdminSidebar();
        } else {
            window.openAdminSidebar();
        }
    };

    window.openUserSidebar = function () {
        const userSidebar = document.getElementById('userSidebar');
        const userSidebarBackdrop = document.getElementById('userSidebarBackdrop');
        if (!userSidebar) return;
        userSidebar.classList.add('open');
        if (userSidebarBackdrop) {
            userSidebarBackdrop.classList.add('active');
            userSidebarBackdrop.classList.remove('hidden');
        }
        document.documentElement.classList.add('sidebar-open');
        document.body.classList.add('sidebar-open');
    };

    window.closeUserSidebar = function () {
        const userSidebar = document.getElementById('userSidebar');
        const userSidebarBackdrop = document.getElementById('userSidebarBackdrop');
        if (!userSidebar) return;
        userSidebar.classList.remove('open');
        if (userSidebarBackdrop) {
            userSidebarBackdrop.classList.remove('active');
            userSidebarBackdrop.classList.add('hidden');
        }
        document.documentElement.classList.remove('sidebar-open');
        document.body.classList.remove('sidebar-open');
    };

    window.toggleUserSidebar = function (e) {
        if (e && e.preventDefault) e.preventDefault();
        const userSidebar = document.getElementById('userSidebar');
        if (userSidebar && userSidebar.classList.contains('open')) {
            window.closeUserSidebar();
        } else {
            window.openUserSidebar();
        }
    };

    if (window.__APEX_MAIN_JS_INITIALIZED__) {
        return;
    }
    window.__APEX_MAIN_JS_INITIALIZED__ = true;

    function initApp() {
        // ==========================================
        // 1. ADMIN SIDEBAR HANDLERS (Mobile / Tablet)
        // ==========================================
        const adminSidebar = document.getElementById('adminSidebar');
        const adminSidebarToggle = document.getElementById('adminSidebarToggle');
        const adminCloseSidebar = document.getElementById('adminCloseSidebar');
        const adminSidebarBackdrop = document.getElementById('adminSidebarBackdrop');

        if (adminSidebarToggle) {
            adminSidebarToggle.addEventListener('click', window.toggleAdminSidebar);
        }

        if (adminCloseSidebar) {
            adminCloseSidebar.addEventListener('click', function (e) {
                e.preventDefault();
                window.closeAdminSidebar();
            });
        }

        if (adminSidebarBackdrop) {
            adminSidebarBackdrop.addEventListener('click', function (e) {
                e.preventDefault();
                window.closeAdminSidebar();
            });
        }

        // Auto-close admin sidebar on link clicks on mobile (< 768px)
        if (adminSidebar) {
            adminSidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.innerWidth < 768) {
                        window.closeAdminSidebar();
                    }
                });
            });
        }

        // ==========================================
        // 2. USER SIDEBAR HANDLERS (Mobile / Tablet)
        // ==========================================
        const userSidebar = document.getElementById('userSidebar');
        const userSidebarToggle = document.getElementById('userSidebarToggle');
        const userCloseSidebar = document.getElementById('userCloseSidebar');
        const userSidebarBackdrop = document.getElementById('userSidebarBackdrop');

        if (userSidebarToggle) {
            userSidebarToggle.addEventListener('click', window.toggleUserSidebar);
        }

        if (userCloseSidebar) {
            userCloseSidebar.addEventListener('click', function (e) {
                e.preventDefault();
                window.closeUserSidebar();
            });
        }

        if (userSidebarBackdrop) {
            userSidebarBackdrop.addEventListener('click', function (e) {
                e.preventDefault();
                window.closeUserSidebar();
            });
        }

        // Auto-close user sidebar on link clicks on mobile (< 768px)
        if (userSidebar) {
            userSidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.innerWidth < 768) {
                        window.closeUserSidebar();
                    }
                });
            });
        }

        // Close sidebars on ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                window.closeAdminSidebar();
                window.closeUserSidebar();
            }
        });

        // Auto cleanup on resize to tablet/desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                window.closeAdminSidebar();
                window.closeUserSidebar();
            }
        });

        // ==========================================
        // 3. PUBLIC LANDING MOBILE DRAWER
        // ==========================================
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileDrawer = document.getElementById('mobileDrawer');
        const closeDrawerBtn = document.getElementById('closeDrawerBtn');

        if (mobileMenuBtn && mobileDrawer) {
            mobileMenuBtn.addEventListener('click', function (e) {
                e.preventDefault();
                mobileDrawer.classList.toggle('hidden');
            });
        }

        if (closeDrawerBtn && mobileDrawer) {
            closeDrawerBtn.addEventListener('click', function (e) {
                e.preventDefault();
                mobileDrawer.classList.add('hidden');
            });
        }

        // ==========================================
        // 4. DROPDOWN MENUS
        // ==========================================
        document.querySelectorAll('[data-dropdown-toggle]').forEach(function (trigger) {
            const targetId = trigger.getAttribute('data-dropdown-toggle');
            const target = document.getElementById(targetId);

            if (target) {
                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    // Close other open dropdowns
                    document.querySelectorAll('.dropdown-menu').forEach(function (menu) {
                        if (menu !== target) {
                            menu.classList.add('hidden');
                        }
                    });
                    target.classList.toggle('hidden');
                });
            }
        });

        // Close dropdowns on outside click
        document.addEventListener('click', function () {
            document.querySelectorAll('.dropdown-menu').forEach(function (menu) {
                menu.classList.add('hidden');
            });
        });

        // ==========================================
        // 5. COPY TO CLIPBOARD
        // ==========================================
        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const textToCopy = btn.getAttribute('data-copy');
                if (!textToCopy) return;

                navigator.clipboard.writeText(textToCopy).then(function () {
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<span class="text-emerald-400 text-xs">Copied!</span>';
                    setTimeout(function () {
                        btn.innerHTML = originalHtml;
                    }, 2000);
                }).catch(function () {
                    prompt('Copy to clipboard: Ctrl+C, Enter', textToCopy);
                });
            });
        });

        // ==========================================
        // 6. TAB SWITCHERS
        // ==========================================
        document.querySelectorAll('[data-tabs]').forEach(function (tabContainer) {
            const tabs = tabContainer.querySelectorAll('[data-tab-target]');
            tabs.forEach(function (tab) {
                tab.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = tab.getAttribute('data-tab-target');
                    const targetContent = document.getElementById(targetId);

                    // Deactivate sibling tabs
                    tabs.forEach(function (t) {
                        t.classList.remove('border-blue-500', 'text-blue-400', 'bg-blue-600/10');
                        t.classList.add('text-slate-400', 'border-transparent');
                    });

                    // Activate clicked tab
                    tab.classList.remove('text-slate-400', 'border-transparent');
                    tab.classList.add('border-blue-500', 'text-blue-400', 'bg-blue-600/10');

                    // Toggle content panels
                    document.querySelectorAll('.tab-content').forEach(function (panel) {
                        panel.classList.add('hidden');
                    });
                    if (targetContent) {
                        targetContent.classList.remove('hidden');
                    }
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApp);
    } else {
        initApp();
    }
})();

