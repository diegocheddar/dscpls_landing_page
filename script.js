document.querySelectorAll('.newsletter-form').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!form.reportValidity()) return;

    const button = form.querySelector('button[type="submit"]');
    const status = form.querySelector('.newsletter-status');
    const originalText = button.textContent;

    button.disabled = true;
    button.textContent = '…';
    status.textContent = '';
    status.classList.remove('is-success', 'is-warning', 'is-error');

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json' }
      });
      const result = await response.json().catch(() => ({}));

      if (!result.ok) {
        throw new Error(result.message || 'Unable to subscribe.');
      }

      form.reset();
      status.textContent = result.message || 'Thank you — you’re signed up.';
      status.classList.add(result.customer_sent === false ? 'is-warning' : 'is-success');
    } catch (error) {
      status.textContent = error.message || 'Something went wrong. Please try again.';
      status.classList.add('is-error');
    } finally {
      button.disabled = false;
      button.textContent = originalText;
    }
  });
});

// Splash screen: 1s delay, 2s fade in, 3s hold, 2s fade out, then reveal the landing page.
(() => {
  const splash = document.querySelector('.splash-screen');
  if (!splash) return;

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const logoSequenceDuration = reducedMotion ? 700 : 8000;
  const splashFadeDuration = reducedMotion ? 200 : 600;

  // Wait until the cross has completely faded out, then fade away the splash background.
  window.setTimeout(() => {
    splash.classList.add('is-leaving');
  }, logoSequenceDuration);

  // Only after the splash is fully gone does the landing page begin its own fade-in.
  window.setTimeout(() => {
    splash.remove();
    requestAnimationFrame(() => {
      document.body.classList.remove('splash-active');
    });
  }, logoSequenceDuration + splashFadeDuration);
})();
