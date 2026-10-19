

document.addEventListener('DOMContentLoaded', () => {

  /* ---------- Sticky navbar shadow on scroll ---------- */
  const navbar = document.getElementById('navbar');
  const scrollTopBtn = document.getElementById('scrollTop');

  const onScroll = () => {
    const scrolled = window.scrollY > 12;
    navbar.classList.toggle('scrolled', scrolled);
    scrollTopBtn.classList.toggle('visible', window.scrollY > 480);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  scrollTopBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  /* ---------- Mobile burger menu ---------- */
  const burger = document.getElementById('burger');
  const navLinks = document.getElementById('navLinks');

  // Below 1024px the links open as a panel under the header. It closes on
  // Escape (focus goes back to the button), on a click outside, and when a
  // link is followed.
  const burgerIcon = burger.querySelector('.nn-icon');
  const setMenu = (open, { restoreFocus = false } = {}) => {
    burger.classList.toggle('open', open);
    burgerIcon?.classList.toggle('nn-icon--menu', !open);
    burgerIcon?.classList.toggle('nn-icon--close', open);
    navLinks.classList.toggle('open', open);
    burger.setAttribute('aria-expanded', String(open));
    burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    if (open) navLinks.querySelector('a')?.focus({ preventScroll: true });
    else if (restoreFocus) burger.focus({ preventScroll: true });
  };

  burger.addEventListener('click', () => setMenu(!navLinks.classList.contains('open')));

  navLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => setMenu(false));
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && navLinks.classList.contains('open')) setMenu(false, { restoreFocus: true });
  });

  document.addEventListener('click', (event) => {
    if (navLinks.classList.contains('open') && !navbar.contains(event.target)) setMenu(false);
  });

  window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
    if (event.matches) setMenu(false);
  });

  /* ---------- Scroll reveal animations ---------- */
  const animatedEls = document.querySelectorAll('[data-animate]');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });

  animatedEls.forEach(el => observer.observe(el));

  /* ---------- Newsletter subscription ---------- */
  const newsletterForm = document.getElementById('newsletterForm');
  const newsletterMsg = document.getElementById('newsletterMsg');

  if (newsletterForm && newsletterMsg) {
    const emailInput = newsletterForm.querySelector('input[type="email"]');

    const showMessage = (text, ok) => {
      newsletterMsg.textContent = text;
      newsletterMsg.classList.toggle('is-ok', ok);
      newsletterMsg.classList.toggle('is-error', !ok);
    };

    newsletterForm.addEventListener('submit', async (event) => {
      // Without JS this submits normally and redirects back to #newsletter,
      // so the preventDefault only applies to the enhanced path.
      event.preventDefault();

      if (newsletterForm.classList.contains('is-sending')) return;

      const submitButton = newsletterForm.querySelector('button[type="submit"]');
      newsletterForm.classList.add('is-sending');
      if (submitButton) submitButton.disabled = true;
      window.NNButtons?.setLoading(submitButton, true);
      showMessage(newsletterForm.dataset.sending || '', true);

      try {
        const response = await fetch(newsletterForm.action, {
          method: 'POST',
          headers: { 'X-Requested-With': 'fetch' },
          body: new FormData(newsletterForm)
        });

        const result = await response.json();
        const ok = result.ok === true;
        showMessage(result.message || '', ok);
        if (ok) newsletterForm.reset();
      } catch (error) {
        showMessage(newsletterForm.dataset.error || '', false);
      } finally {
        newsletterForm.classList.remove('is-sending');
        if (submitButton) submitButton.disabled = false;
        window.NNButtons?.setLoading(submitButton, false);
        if (emailInput) emailInput.focus();
      }
    });
  }

});
