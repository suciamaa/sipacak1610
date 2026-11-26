(() => {
  'use strict';

  // Bootstrap tooltips, if a view opts in with data-bs-toggle="tooltip".
  document.addEventListener('DOMContentLoaded', () => {
    if (window.bootstrap?.Tooltip) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        bootstrap.Tooltip.getOrCreateInstance(el);
      });
    }

    // Close the responsive menu after a navigation choice.
    document.querySelectorAll('.app-nav-links .nav-link').forEach(link => {
      link.addEventListener('click', () => {
        const menu = document.getElementById('sipacakNavbar');
        if (menu && menu.classList.contains('show') && window.bootstrap?.Collapse) {
          bootstrap.Collapse.getOrCreateInstance(menu).hide();
        }
      });
    });

    // Friendly submit state, without changing form payloads or submit behavior.
    document.querySelectorAll('form').forEach(form => {
      form.addEventListener('submit', event => {
        if (event.defaultPrevented || form.dataset.noLoading === 'true') return;
        const submitter = event.submitter || form.querySelector('button[type="submit"],button:not([type])');
        if (!submitter || submitter.dataset.noLoading === 'true') return;
        if (submitter.dataset.confirm && !window.confirm(submitter.dataset.confirm)) {
          event.preventDefault();
          return;
        }
        if (form.checkValidity && !form.checkValidity()) return;
        window.setTimeout(() => {
          if (!form.isConnected) return;
          submitter.dataset.originalLabel ??= submitter.innerHTML;
          submitter.disabled = true;
          submitter.dataset.loading = 'true';
          submitter.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memproses...';
        }, 0);
      });
    });

    // Add accessible focus state to interactive table rows containing a primary link.
    document.querySelectorAll('.table tbody tr').forEach(row => {
      const link = row.querySelector('a.btn, a[href]');
      if (!link) return;
      row.classList.add('table-row-interactive');
    });

    // Mark current page navigation item.
    const current = location.pathname.replace(/\/+$/, '');
    document.querySelectorAll('.app-nav-links a[href]').forEach(link => {
      try {
        const target = new URL(link.href, location.origin).pathname.replace(/\/+$/, '');
        if (target && target === current) link.classList.add('active');
      } catch (_) {}
    });
  });

  // Expose a small helper for views that want to ask before destructive actions.
  window.sipacakConfirm = message => window.confirm(message || 'Apakah Anda yakin melanjutkan tindakan ini?');
})();
