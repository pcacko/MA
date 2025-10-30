(() => {
  'use strict';

  function qs(id) { return document.getElementById(id); }

  function showMessage(box, type, text) {
    const cls = type === 'success' ? 'success' : (type === 'error' ? 'error' : 'info');
    box.innerHTML = "<div class='" + cls + "'><label>" + text + "</label></div>";
  }

  function initPartnershipForm() {
    const form = qs('partnership-request');
    if (!form) return;

    const resultBox = qs('subscribe-result');
    const submitBtn = qs('pi-submit');

    form.addEventListener('submit', async (e) => {
      if (!form.checkValidity()) {
        e.preventDefault();
        form.reportValidity();
        return;
      }

      e.preventDefault();

      const email = (form.email?.value || '').trim();
      const website = (form.website?.value || '').trim();

      if (!email) {
        showMessage(resultBox, 'error', 'Podaj adres e-mail.');
        return;
      }
      if (website) {
        showMessage(resultBox, 'success', 'Dziękujemy! Skontaktujemy się wkrótce.');
        form.reset();
        return;
      }

      submitBtn.disabled = true;
      showMessage(resultBox, 'info', 'Wysyłanie...');

      try {
        const res = await fetch('/api/partner.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, website }),
          credentials: 'same-origin',
        });

        let data = {};
        try { data = await res.json(); } catch (_) {}

        if (res.ok && data.ok) {
          showMessage(resultBox, 'success', 'Dziękujemy! Skontaktujemy się wkrótce.');
          form.reset();
        } else {
          showMessage(
            resultBox,
            'error',
            (data && data.error) ? String(data.error) : 'Coś poszło nie tak. Spróbuj ponownie.'
          );
        }
      } catch (_) {
        showMessage(resultBox, 'error', 'Błąd sieci. Spróbuj ponownie.');
      } finally {
        submitBtn.disabled = false;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', initPartnershipForm);
})();
