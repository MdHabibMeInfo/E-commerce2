document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-mobile-menu-toggle]');
    const menu = document.querySelector('[data-mobile-menu]');
    const openIcon = document.querySelector('[data-menu-open-icon]');
    const closeIcon = document.querySelector('[data-menu-close-icon]');

    if (!toggle || !menu) {
        return;
    }

    const setMenuState = (open) => {
        menu.classList.toggle('hidden', !open);
        openIcon?.classList.toggle('hidden', open);
        closeIcon?.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    };

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        setMenuState(!isOpen);
    });

    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setMenuState(false));
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1280) {
            setMenuState(false);
        }
    });
});
