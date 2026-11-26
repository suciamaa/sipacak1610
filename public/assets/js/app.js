(() => {
  'use strict';

  // Bootstrap tooltips, if a view opts in with data-bs-toggle="tooltip".
  document.addEventListener('DOMContentLoaded', () => {
    // Accessible password visibility controls used on login and registration.
    document.querySelectorAll('[data-password-toggle]').forEach(button => {
      button.addEventListener('click', () => {
        const field = document.getElementById(button.dataset.passwordToggle);
        if (!field) return;
        const reveal = field.type === 'password';
        field.type = reveal ? 'text' : 'password';
        button.innerHTML = reveal ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        button.setAttribute('aria-label', reveal ? 'Sembunyikan password' : 'Tampilkan password');
      });
    });

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

    // Keep document download menus tidy: only one can be open at a time.
    const downloadMenus = [...document.querySelectorAll('.download-menu')];
    downloadMenus.forEach(menu => {
      menu.addEventListener('toggle', () => {
        if (menu.open) {
          downloadMenus.forEach(other => {
            if (other !== menu) other.open = false;
          });
        }
      });
    });

    // Close an open download menu when the user clicks outside it.
    document.addEventListener('click', event => {
      if (event.target.closest('.download-menu')) return;
      downloadMenus.forEach(menu => { menu.open = false; });
    });

    // Escape closes an open download menu and returns focus to its trigger.
    document.addEventListener('keydown', event => {
      if (event.key !== 'Escape') return;
      const opened = downloadMenus.find(menu => menu.open);
      if (!opened) return;
      opened.open = false;
      opened.querySelector('summary')?.focus();
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
