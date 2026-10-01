/**
 * Plain JavaScript UI Helpers
 * Apex Gaming Platform
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Menu Drawer
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileDrawer = document.getElementById('mobileDrawer');
    const closeDrawerBtn = document.getElementById('closeDrawerBtn');

    if (mobileMenuBtn && mobileDrawer) {
        mobileMenuBtn.addEventListener('click', function () {
            mobileDrawer.classList.toggle('hidden');
        });
    }

    if (closeDrawerBtn && mobileDrawer) {
        closeDrawerBtn.addEventListener('click', function () {
            mobileDrawer.classList.add('hidden');
        });
    }

    // 2. Dropdown Menus
    document.querySelectorAll('[data-dropdown-toggle]').forEach(function (trigger) {
        const targetId = trigger.getAttribute('data-dropdown-toggle');
        const target = document.getElementById(targetId);

        if (target) {
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
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

    // 3. Copy to Clipboard
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
                // Fallback prompt
                prompt('Copy to clipboard: Ctrl+C, Enter', textToCopy);
            });
        });
    });

    // 4. Tab switcher
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
