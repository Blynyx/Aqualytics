import './bootstrap';

const sidebar = document.querySelector('#app-sidebar');
const overlay = document.querySelector('#sidebar-overlay');
const openButton = document.querySelector('[data-sidebar-open]');
const closeButton = document.querySelector('[data-sidebar-close]');

if (sidebar && overlay && openButton) {
    let isSidebarOpen = false;

    const setSidebarOpen = (isOpen) => {
        isSidebarOpen = isOpen;
        sidebar.classList.toggle('-translate-x-full', !isOpen);
        overlay.classList.toggle('hidden', !isOpen);
        openButton.setAttribute('aria-expanded', String(isOpen));
        document.body.classList.toggle('overflow-hidden', isOpen);

        if (isOpen) {
            closeButton?.focus();
        } else if (document.activeElement && sidebar.contains(document.activeElement)) {
            openButton.focus();
        }
    };

    openButton.addEventListener('click', () => setSidebarOpen(true));
    closeButton?.addEventListener('click', () => setSidebarOpen(false));
    overlay.addEventListener('click', () => setSidebarOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isSidebarOpen) {
            setSidebarOpen(false);
        }

        if (event.key === 'Tab' && isSidebarOpen) {
            const focusableElements = [...sidebar.querySelectorAll('a[href], button:not([disabled])')];
            const firstElement = focusableElements[0];
            const lastElement = focusableElements.at(-1);

            if (event.shiftKey && document.activeElement === firstElement) {
                event.preventDefault();
                lastElement?.focus();
            } else if (!event.shiftKey && document.activeElement === lastElement) {
                event.preventDefault();
                firstElement?.focus();
            }
        }
    });
}
