/**
 * Plain JavaScript UI Helpers & Sidebar Handlers
 * Apex Gaming Platform
 */

document.addEventListener('DOMContentLoaded', function () {
    
    // ==========================================
    // 1. ADMIN SIDEBAR HANDLERS (Mobile / Tablet)
    // ==========================================
    const adminSidebar = document.getElementById('adminSidebar');
    const adminSidebarToggle = document.getElementById('adminSidebarToggle');
    const adminCloseSidebar = document.getElementById('adminCloseSidebar');
    const adminSidebarBackdrop = document.getElementById('adminSidebarBackdrop');

    function openAdminSidebar() {
        if (!adminSidebar) return;
        adminSidebar.classList.remove('-translate-x-full');
        adminSidebar.classList.add('translate-x-0');
        if (adminSidebarBackdrop) {
            adminSidebarBackdrop.classList.remove('hidden');
        }
        document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
    }

    function closeAdminSidebar() {
        if (!adminSidebar) return;
        adminSidebar.classList.add('-translate-x-full');
        adminSidebar.classList.remove('translate-x-0');
        if (adminSidebarBackdrop) {
            adminSidebarBackdrop.classList.add('hidden');
        }
        document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
    }

    if (adminSidebarToggle) {
        adminSidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (adminSidebar && adminSidebar.classList.contains('translate-x-0')) {
                closeAdminSidebar();
            } else {
                openAdminSidebar();
            }
        });
    }

    if (adminCloseSidebar) {
        adminCloseSidebar.addEventListener('click', function (e) {
            e.preventDefault();
            closeAdminSidebar();
        });
    }

    if (adminSidebarBackdrop) {
        adminSidebarBackdrop.addEventListener('click', function () {
            closeAdminSidebar();
        });
    }

    // ==========================================
    // 2. USER SIDEBAR HANDLERS (Mobile / Tablet)
    // ==========================================
    const userSidebar = document.getElementById('userSidebar');
    const userSidebarToggle = document.getElementById('userSidebarToggle');
    const userCloseSidebar = document.getElementById('userCloseSidebar');
    const userSidebarBackdrop = document.getElementById('userSidebarBackdrop');

    function openUserSidebar() {
        if (!userSidebar) return;
        userSidebar.classList.remove('-translate-x-full');
        userSidebar.classList.add('translate-x-0');
        if (userSidebarBackdrop) {
            userSidebarBackdrop.classList.remove('hidden');
        }
        document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
    }

    function closeUserSidebar() {
        if (!userSidebar) return;
        userSidebar.classList.add('-translate-x-full');
        userSidebar.classList.remove('translate-x-0');
        if (userSidebarBackdrop) {
            userSidebarBackdrop.classList.add('hidden');
        }
        document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
    }

    if (userSidebarToggle) {
        userSidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (userSidebar && userSidebar.classList.contains('translate-x-0')) {
                closeUserSidebar();
            } else {
                openUserSidebar();
            }
        });
    }

    if (userCloseSidebar) {
        userCloseSidebar.addEventListener('click', function (e) {
            e.preventDefault();
            closeUserSidebar();
        });
    }

    if (userSidebarBackdrop) {
        userSidebarBackdrop.addEventListener('click', function () {
            closeUserSidebar();
        });
    }

    // Close sidebars on ESC key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAdminSidebar();
            closeUserSidebar();
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
});
