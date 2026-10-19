/**
 * assets/js/sidebar.js
 * --------------------------------------------------------------------
 * Dashboard navigation drawer (included by includes/sidebar.php).
 * Below 1024px the sidebar is an off-canvas drawer opened by any button
 * with aria-controls="appSidebar". It closes on the backdrop, Escape,
 * its close button and after following a link; while it is open the
 * page does not scroll and Tab stays inside it. From 1024px up it is the
 * normal fixed sidebar and this script does nothing visible.
 * --------------------------------------------------------------------
 */
(() => {
  const sidebar = document.getElementById('appSidebar');
  const backdrop = document.querySelector('[data-drawer-backdrop]');
  if (!sidebar || !backdrop) return;

  const toggles = [...document.querySelectorAll('[aria-controls="appSidebar"]')];
  const closeButton = sidebar.querySelector('[data-drawer-close]');
  const drawerMode = window.matchMedia('(max-width: 1023.98px)');
  let returnFocusTo = null;

  const isOpen = () => sidebar.classList.contains('is-open');
  const focusables = () => [...sidebar.querySelectorAll('a[href], button:not([disabled]), input:not([type="hidden"])')]
    .filter((el) => el.getClientRects().length > 0);

  function setModal(on) {
    if (on) {
      sidebar.setAttribute('role', 'dialog');
      sidebar.setAttribute('aria-modal', 'true');
    } else {
      sidebar.removeAttribute('role');
      sidebar.removeAttribute('aria-modal');
    }
  }

  function open(trigger) {
    if (!drawerMode.matches || isOpen()) return;
    returnFocusTo = trigger || toggles[0] || null;
    backdrop.hidden = false;
    sidebar.classList.add('is-open');
    // Next frame, so the backdrop fades in instead of appearing at once.
    requestAnimationFrame(() => backdrop.classList.add('is-open'));
    document.documentElement.classList.add('drawer-open');
    toggles.forEach((t) => t.setAttribute('aria-expanded', 'true'));
    setModal(true);
    (closeButton || focusables()[0])?.focus({ preventScroll: true });
  }

  function close({ restoreFocus = true } = {}) {
    if (!isOpen()) return;
    sidebar.classList.remove('is-open');
    backdrop.classList.remove('is-open');
    document.documentElement.classList.remove('drawer-open');
    toggles.forEach((t) => t.setAttribute('aria-expanded', 'false'));
    setModal(false);
    const fadeMs = parseFloat(getComputedStyle(backdrop).transitionDuration) * 1000 || 0;
    window.setTimeout(() => { if (!isOpen()) backdrop.hidden = true; }, fadeMs);
    if (restoreFocus && returnFocusTo) returnFocusTo.focus({ preventScroll: true });
  }

  toggles.forEach((t) => t.addEventListener('click', () => (isOpen() ? close() : open(t))));
  backdrop.addEventListener('click', () => close());
  closeButton?.addEventListener('click', () => close());

  // Following a link closes the drawer; the new page loads with it closed.
  sidebar.addEventListener('click', (event) => {
    if (event.target.closest('a[href]')) close({ restoreFocus: false });
  });

  document.addEventListener('keydown', (event) => {
    if (!isOpen()) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      close();
    } else if (event.key === 'Tab') {
      const items = focusables();
      if (items.length === 0) return;
      const first = items[0];
      const last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      } else if (!sidebar.contains(document.activeElement)) {
        event.preventDefault();
        first.focus();
      }
    }
  });

  // Growing past 1024px turns the drawer back into the fixed sidebar.
  drawerMode.addEventListener('change', () => {
    if (!drawerMode.matches) close({ restoreFocus: false });
  });

  // Back/forward cache: never come back to a page with the drawer open.
  window.addEventListener('pageshow', () => close({ restoreFocus: false }));
})();
