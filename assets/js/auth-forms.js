/* ---------------------------------------------------------------------
 * assets/js/auth-forms.js
 * Live feedback for the forms that set a password (register, reset):
 * rule checklist, strength meter, "passwords don't match", field
 * messages, and no submit until everything passes.
 *
 * The rules mirror includes/PasswordPolicy.php, whose constants arrive
 * in #passwordPolicyConfig. The server validates again on submit, so
 * this script only improves feedback.
 * ------------------------------------------------------------------- */
(() => {
  const configElement = document.getElementById('passwordPolicyConfig');
  if (!configElement) return;
  const config = JSON.parse(configElement.textContent);

  /* ---------- Policy mirror (same rules as PasswordPolicy.php) ---------- */
  let commonSet = null;          // Set once loaded, false if the list could not be loaded
  let commonLoading = null;
  const loadCommonList = () => {
    commonLoading ??= fetch(config.commonListUrl, { credentials: 'same-origin' })
      .then((response) => (response.ok ? response.json() : Promise.reject(new Error(String(response.status)))))
      .then((list) => { commonSet = new Set(list.map((entry) => String(entry).trim().toLowerCase()).filter(Boolean)); })
      .catch(() => { commonSet = false; });
    return commonLoading;
  };

  const length = (value) => [...value].length;
  const undoLookalikes = (value) => [...value].map((ch) => config.lookalikes[ch] ?? ch).join('');

  const identityTokens = (fullName, email) => {
    const emailName = (email.split('@')[0] || '').trim().toLowerCase();
    const words = `${fullName.toLowerCase()} ${emailName}`.match(/\p{L}+/gu) || [];
    return [...new Set([emailName, ...words])].filter((token) => length(token) >= config.minIdentityLength);
  };

  /** true / false, or null while the common list is still loading */
  const isCommon = (password) => {
    const lower = password.toLowerCase();
    if (config.blockedFragments.some((fragment) => lower.includes(fragment) || undoLookalikes(lower).includes(fragment))) return true;
    if (!(commonSet instanceof Set)) return commonSet === false ? false : null;
    const core = lower.replace(/^[^\p{L}]+|[^\p{L}]+$/gu, '');
    return [lower, core, undoLookalikes(core)].some((candidate, index) =>
      (index === 0 || length(candidate) >= config.minCoreLength) && commonSet.has(candidate));
  };

  /** rule id → true (met), false (broken) or null (not known yet) */
  const evaluate = (password, tokens) => {
    const lower = password.toLowerCase();
    const common = isCommon(password);
    return {
      length: length(password) >= config.minLength,
      uppercase: /\p{Lu}/u.test(password),
      lowercase: /\p{Ll}/u.test(password),
      digit: /\p{Nd}/u.test(password),
      special: /[^\p{L}\p{N}\s]/u.test(password),
      identity: !tokens.some((token) => lower.includes(token)),
      common: common === null ? null : !common,
    };
  };

  /** weak until every rule passes; strong from 14 characters with some variety */
  const strength = (password, results) => {
    if (password === '') return null;
    if (Object.values(results).includes(false)) return 'weak';
    return length(password) >= 14 && new Set(password).size >= 8 ? 'strong' : 'medium';
  };

  window.NajmiNetPasswordPolicy = { evaluate, identityTokens, isCommon, strength, loadCommonList };

  /* ---------- Form wiring ---------- */
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  const showMessage = (form, name, html) => {
    const slot = form.querySelector(`[data-field-message="${name}"]`);
    const input = form.elements[name];
    if (slot) {
      slot.innerHTML = html || '';
      slot.hidden = !html;
    }
    if (input) input.setAttribute('aria-invalid', html ? 'true' : 'false');
  };
  const escapeHtml = (text) => text.replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));

  document.querySelectorAll('form[data-password-form]').forEach((form) => {
    const password = form.querySelector('[data-password-input]');
    const confirm = form.querySelector('[data-password-confirm]');
    const checklist = form.querySelector('[data-password-checklist]');
    const meter = form.querySelector('[data-password-strength]');
    const nameInput = form.querySelector('[data-identity="name"]');
    const emailInput = form.querySelector('[data-identity="email"]');
    let submitted = false;
    let confirmTouched = false;

    const tokens = () => (nameInput || emailInput
      ? identityTokens(nameInput ? nameInput.value : '', emailInput ? emailInput.value : '')
      : config.identityTokens);

    const refreshPassword = () => {
      const results = evaluate(password.value, tokens());
      checklist.querySelectorAll('[data-rule]').forEach((item) => {
        const met = results[item.dataset.rule];
        item.dataset.state = met === null ? 'pending' : met ? 'met' : submitted ? 'error' : 'unmet';
      });

      const level = strength(password.value, results);
      meter.hidden = level === null;
      meter.dataset.level = level || '';
      meter.querySelector('[data-strength-label]').textContent = level ? config.strength[level] : '';

      const passes = !Object.values(results).includes(false);
      if (submitted || password.getAttribute('aria-invalid') === 'true') {
        showMessage(form, password.name, passes || password.value === '' && !submitted ? '' : escapeHtml(config.messages.rulesUnmet));
      }
      return results;
    };

    const refreshConfirm = () => {
      const mismatch = confirm.value !== '' && confirm.value !== password.value
        && (confirmTouched || submitted || length(confirm.value) >= length(password.value));
      showMessage(form, confirm.name, mismatch ? escapeHtml(config.messages.mismatch) : '');
      return !mismatch && (confirm.value !== '' || !submitted);
    };

    password.addEventListener('focus', loadCommonList, { once: true });
    password.addEventListener('input', () => { refreshPassword(); if (confirm.value) refreshConfirm(); });
    confirm.addEventListener('input', refreshConfirm);
    confirm.addEventListener('blur', () => { confirmTouched = true; refreshConfirm(); });
    [nameInput, emailInput].forEach((input) => input && input.addEventListener('input', () => { if (password.value) refreshPassword(); }));

    // Clear a field's message as soon as it is edited.
    form.querySelectorAll('[data-required-message], [data-email-message]').forEach((input) => {
      input.addEventListener('input', () => { if (input.getAttribute('aria-invalid') === 'true') showMessage(form, input.name, ''); });
    });

    form.addEventListener('submit', (event) => {
      // Wait for the common-password list once, then validate again.
      if (commonSet === null) {
        event.preventDefault();
        loadCommonList().then(() => (form.requestSubmit ? form.requestSubmit(event.submitter) : form.submit()));
        return;
      }

      submitted = true;
      const invalid = [];

      form.querySelectorAll('[data-required-message], [data-email-message]').forEach((input) => {
        const value = input.value.trim();
        let message = '';
        if (value === '' && input.dataset.requiredMessage) message = input.dataset.requiredMessage;
        else if (value !== '' && input.dataset.emailMessage && !emailPattern.test(value)) message = input.dataset.emailMessage;
        if (message) { showMessage(form, input.name, escapeHtml(message)); invalid.push(input); }
      });

      const results = refreshPassword();
      if (Object.values(results).includes(false) || password.value === '') {
        showMessage(form, password.name, escapeHtml(config.messages.rulesUnmet));
        invalid.push(password);
      }
      if (!refreshConfirm() || confirm.value !== password.value) {
        showMessage(form, confirm.name, escapeHtml(config.messages.mismatch));
        invalid.push(confirm);
      }

      if (invalid.length) {
        event.preventDefault();
        // Focus the first problem in page order.
        invalid.sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1))[0].focus();
      }
    });
  });
})();
