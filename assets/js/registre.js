

document.addEventListener('DOMContentLoaded', () => {
  /* ---------- "Email already registered" live check ----------
     Password rules and field messages are handled by auth-forms.js, the
     show/hide password buttons by buttons.js. */
  const form = document.getElementById('registerForm');
  const emailInput = document.getElementById('email');
  const templates = document.getElementById('emailTakenTemplates');
  if (form && emailInput && templates) {
    const slot = form.querySelector('[data-field-message="email"]');
    const normalise = (value) => value.trim().toLowerCase();
    let lastChecked = '';
    let takenStatus = null; // 'registered', 'google' or 'both' for lastChecked

    const show = (status) => {
      const template = status ? templates.content.querySelector(`[data-status="${status}"]`) : null;
      slot.innerHTML = template ? template.innerHTML : '';
      slot.hidden = !template;
      emailInput.setAttribute('aria-invalid', template ? 'true' : 'false');
    };

    emailInput.addEventListener('blur', async () => {
      const email = normalise(emailInput.value);
      if (email === lastChecked || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
      lastChecked = email;
      try {
        const response = await fetch('/auth/check-email.php', {
          method: 'POST',
          headers: { 'X-Requested-With': 'fetch' },
          body: new URLSearchParams({ csrf_token: form.elements.csrf_token.value, email }),
        });
        const { status } = await response.json();
        if (normalise(emailInput.value) !== email) return; // edited while waiting
        takenStatus = ['registered', 'google', 'both'].includes(status) ? status : null;
        show(takenStatus);
      } catch {
        // No live answer (offline, rate limited…): the server checks again on submit.
      }
    });

    emailInput.addEventListener('input', () => {
      if (takenStatus && normalise(emailInput.value) !== lastChecked) {
        takenStatus = null;
        show(null);
      }
    });

    form.addEventListener('submit', (event) => {
      if (takenStatus && normalise(emailInput.value) === lastChecked) {
        event.preventDefault();
        show(takenStatus);
        emailInput.focus();
      }
    });
  }

});
