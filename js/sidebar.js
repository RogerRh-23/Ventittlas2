// js/sidebar.js
// Handles sidebar toggle, accessibility, active link highlighting and closing on outside click
(function () {
    function init() {
        const sidebar = document.querySelector('.app-sidebar');
        if (!sidebar) return;

        const toggle = sidebar.querySelector('.sidebar-toggle');
        const nav = sidebar.querySelector('#sidebar-nav');
        if (!toggle || !nav) return;

        // Toggle open/close
        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            const willOpen = !expanded;
            toggle.setAttribute('aria-expanded', String(willOpen));
            nav.classList.toggle('open', willOpen);
            nav.setAttribute('aria-hidden', String(!willOpen));

            // position the menu to the right of the toggle button so it "unfolds" to the right
            if (willOpen) {
                const rect = toggle.getBoundingClientRect();
                // set fixed positioning so the menu appears relative to viewport
                nav.style.position = 'fixed';
                nav.style.top = (rect.bottom + 6 + window.scrollY) + 'px';
                nav.style.left = (rect.right + 8) + 'px';
            } else {
                // remove inline positioning to allow CSS fallback
                nav.style.position = '';
                nav.style.top = '';
                nav.style.left = '';
            }
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!nav.classList.contains('open')) return;
            if (!sidebar.contains(e.target)) {
                nav.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
                nav.setAttribute('aria-hidden', 'true');
            }
        });

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                nav.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Highlight active link
        try {
            const links = nav.querySelectorAll('a');
            const current = location.pathname.replace(/\/+$/, '');
            links.forEach(a => {
                const target = a.getAttribute('href');
                if (!target) return;
                // Normalize both paths
                const linkPath = new URL(a.href, location.origin).pathname.replace(/\/+$/, '');
                if (linkPath === current) {
                    a.classList.add('active');
                }
            });
        } catch (err) {
            // ignore
        }
    }

    // include.js dispatches 'includes:loaded' when any includes finish
    document.addEventListener('includes:loaded', init);
    // also try init in case the sidebar was already present
    document.addEventListener('DOMContentLoaded', () => setTimeout(init, 50));

// handle logout action if present
document.addEventListener('includes:loaded', function() {
    const logout = document.getElementById('sidebar-logout');
    if (!logout) return;
    logout.addEventListener('click', async function(e) {
        e.preventDefault();
        try {
            const res = await fetch('/php/api/logout.php', { method: 'POST', credentials: 'same-origin' });
            const j = await res.json().catch(() => null);
            // redirect to home after logout
            window.location.href = '/index.html';
        } catch (err) {
            window.location.href = '/index.html';
        }
    });
});
})();
