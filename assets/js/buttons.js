/**
 * assets/js/buttons.js
 * --------------------------------------------------------------------
 * Behaviour for the shared button system (assets/css/buttons.css), loaded
 * on every page. Everything here is progressive enhancement: with no JS,
 * every button and link still works.
 *
 * 1. Back button (includes/back-button.php): a tap uses history.back()
 *    when the browser came from another page of this same site, else it
 *    follows its href (the explicit fallback). Never sends anyone to
 *    another site. A page that is itself the result of a form POST
 *    renders the link without [data-back-smart], so it is left alone.
 *
 * 2. Confirmation: a form (or its submit button) with
 *    data-confirm="Delete this invoice?" asks first and submits only on
 *    OK. Used for every destructive action.
 *
 * 3. No double submission: after the first real submit (not cancelled
 *    by validation or the confirmation), the clicked button shows a
 *    loading state and every submit button of the form is disabled. They
 *    are enabled again if the page is still here after 15 s (request
 *    failed or a file download), or when the page comes back from the
 *    back/forward cache. Forms that send with fetch() call
 *    NNButtons.setLoading(button, true/false) themselves.
 *
 * 4. Password fields: <button data-toggle-password="fieldId"> shows or
 *    hides the password and keeps its icon, label and aria-pressed in
 *    step.
 * --------------------------------------------------------------------
 */
(() => {
  const setLoading = (button, on) => {
    if (!button) return;
    button.classList.toggle('is-loading', on);
    if (on) button.setAttribute('aria-busy', 'true'); else button.removeAttribute('aria-busy');
  };
  window.NNButtons = { setLoading };

  // ---------- 1. Back button ----------
  document.querySelectorAll('a.back-btn[data-back-smart]').forEach((link) => {
    link.addEventListener('click', (event) => {
      let referrer = null;
      try {
        referrer = document.referrer ? new URL(document.referrer) : null;
      } catch (error) {
        referrer = null; // malformed referrer: treat as "no referrer"
      }
      const cameFromThisSite = referrer
        && referrer.origin === window.location.origin
        && referrer.href !== window.location.href;
      if (cameFromThisSite) {
        event.preventDefault();
        history.back();
      }
      // Otherwise: let the click follow href as normal, i.e. the fallback.
    });
  });

  // ---------- 2 + 3. Confirmation, then lock against double submission ----------
  const submitButtons = (form) => [...form.querySelectorAll('button:not([type]), button[type="submit"], input[type="submit"]')];

  const release = (form) => {
    clearTimeout(form.nnReleaseTimer);
    delete form.dataset.submitting;
    submitButtons(form).forEach((button) => {
      button.disabled = false;
      setLoading(button, false);
    });
  };

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;

    const submitter = event.submitter || submitButtons(form)[0] || null;
    if (form.dataset.submitting === '1') {
      event.preventDefault(); // already on its way: ignore the second click
      return;
    }

    const message = (submitter && submitter.dataset.confirm) || form.dataset.confirm;
    if (message && !window.confirm(message)) {
      event.preventDefault();
      return;
    }

    form.dataset.submitting = '1';
    setLoading(submitter, true);
    // Disable on the next tick: the form's data (including the submitter's
    // own name/value) has been collected by then.
    setTimeout(() => submitButtons(form).forEach((button) => { button.disabled = true; }), 0);
    form.nnReleaseTimer = setTimeout(() => release(form), 15000);
  });

  window.addEventListener('pageshow', (event) => {
    if (event.persisted) document.querySelectorAll('form[data-submitting]').forEach(release);
  });

  // ---------- 4. Show / hide password ----------
  document.querySelectorAll('button[data-toggle-password]').forEach((button) => {
    const input = document.getElementById(button.dataset.togglePassword);
    if (!input) return;
    const icon = button.querySelector('.nn-icon');
    const sync = () => {
      const shown = input.type === 'text';
      button.setAttribute('aria-pressed', String(shown));
      button.setAttribute('aria-label', shown ? 'Hide password' : 'Show password');
      if (icon) {
        icon.classList.toggle('nn-icon--eye', !shown);
        icon.classList.toggle('nn-icon--eye-slash', shown);
      }
    };
    button.addEventListener('click', () => {
      input.type = input.type === 'password' ? 'text' : 'password';
      sync();
    });
    sync();
  });
})();
