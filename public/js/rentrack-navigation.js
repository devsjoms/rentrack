(() => {
    const toggle = document.querySelector('[data-nav-toggle]');
    const sidebar = document.querySelector('#rt-sidebar');
    const scrim = document.querySelector('[data-nav-scrim]');
    const closeButton = document.querySelector('[data-nav-close]');
    const mobileViewport = window.matchMedia('(max-width: 1023px)');
    if (!toggle || !sidebar || !scrim) return;

    let opener = null;
    const isOpen = () => sidebar.classList.contains('is-open');
    const focusableItems = () => Array.from(sidebar.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'));
    const sync = (open, restoreFocus = false) => {
        const isMobile = mobileViewport.matches;
        const shouldOpen = isMobile && open;
        sidebar.classList.toggle('is-open', shouldOpen);
        sidebar.setAttribute('aria-hidden', isMobile ? String(!shouldOpen) : 'false');
        scrim.hidden = !shouldOpen;
        document.body.classList.toggle('rt-nav-open', shouldOpen);
        toggle.setAttribute('aria-expanded', String(shouldOpen));
        if (!shouldOpen && restoreFocus && opener) opener.focus();
        if (!shouldOpen) opener = null;
    };
    const close = (restoreFocus = true) => sync(false, restoreFocus);
    const open = () => {
        if (!mobileViewport.matches) return;
        opener = document.activeElement instanceof HTMLElement ? document.activeElement : toggle;
        sync(true);
        closeButton?.focus();
    };

    toggle.addEventListener('click', () => isOpen() ? close() : open());
    scrim.addEventListener('click', () => close());
    closeButton?.addEventListener('click', () => close());
    sidebar.addEventListener('click', (event) => { if (event.target instanceof Element && event.target.closest('a[href]')) close(false); });
    document.addEventListener('keydown', (event) => {
        if (!isOpen()) return;
        if (event.key === 'Escape') { event.preventDefault(); close(); return; }
        if (event.key !== 'Tab') return;
        const items = focusableItems();
        if (!items.length) return;
        const first = items[0];
        const last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    mobileViewport.addEventListener('change', () => close(false));
    sync(false);
})();
