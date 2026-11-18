/* Scan2Chat Admin — sidebar, snackbar, confirm dialog */
(function () {
  'use strict';

  // ───────── Sidebar (mobile drawer) ─────────
  var body = document.body;
  var toggle = document.getElementById('sbToggle');
  var backdrop = document.getElementById('backdrop');
  function setSidebar(open) { body.classList.toggle('sb-open', open); }
  if (toggle) toggle.addEventListener('click', function () { setSidebar(!body.classList.contains('sb-open')); });
  if (backdrop) backdrop.addEventListener('click', function () { setSidebar(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setSidebar(false); });
  window.addEventListener('resize', function () { if (window.innerWidth >= 992) setSidebar(false); });

  // ───────── Snackbar ─────────
  var ICONS = {
    success: 'bi-check-circle-fill', danger: 'bi-x-octagon-fill',
    warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill'
  };
  var TITLES = { success: 'Success', danger: 'Error', warning: 'Warning', info: 'Info' };

  window.snack = function (type, msg, opts) {
    var host = document.getElementById('snackbars');
    if (!host) return;
    type = ICONS[type] ? type : 'info';
    var ms = (opts && opts.duration) || (type === 'danger' ? 6500 : 4500);

    var el = document.createElement('div');
    el.className = 'snack ' + type;
    el.setAttribute('role', type === 'danger' ? 'alert' : 'status');
    el.style.setProperty('--t', ms + 'ms');

    var ic = document.createElement('i'); ic.className = 'bi ' + ICONS[type];
    var box = document.createElement('div'); box.className = 's-msg';
    var b = document.createElement('b'); b.textContent = (opts && opts.title) || TITLES[type];
    box.appendChild(b); box.appendChild(document.createTextNode(String(msg)));
    var x = document.createElement('button'); x.type = 'button'; x.className = 's-x'; x.setAttribute('aria-label', 'Dismiss');
    x.innerHTML = '<i class="bi bi-x-lg"></i>';
    var bar = document.createElement('div'); bar.className = 's-bar';
    el.append(ic, box, x, bar);
    host.appendChild(el);
    while (host.children.length > 4) host.removeChild(host.firstChild);

    requestAnimationFrame(function () { requestAnimationFrame(function () { el.classList.add('show'); }); });

    var closed = false;
    function close() {
      if (closed) return; closed = true;
      el.classList.remove('show'); el.classList.add('hide');
      setTimeout(function () { el.remove(); }, 350);
    }
    x.addEventListener('click', close);
    bar.addEventListener('animationend', close);
    return close;
  };

  (window.__flash || []).forEach(function (f, i) {
    setTimeout(function () { window.snack(f.type, f.msg); }, 120 + i * 150);
  });

  // ───────── Confirm dialog ─────────
  var modalEl = document.getElementById('confirmModal');
  if (modalEl && window.bootstrap) {
    var modal = new bootstrap.Modal(modalEl);
    var okBtn = document.getElementById('cOk');
    var pending = null;

    function ask(o, run) {
      document.getElementById('cTitle').textContent = o.title || 'Are you sure?';
      document.getElementById('cText').textContent = o.text || '';
      document.getElementById('cIcon').className = 'bi ' + (o.icon || 'bi-exclamation-triangle');
      okBtn.textContent = o.ok || 'Confirm';
      okBtn.className = 'btn flex-fill ' + (o.btn || 'btn-danger');
      pending = run; modal.show();
    }
    okBtn.addEventListener('click', function () {
      var fn = pending; pending = null; modal.hide(); if (fn) fn();
    });

    // <form data-confirm="Text" data-confirm-title="" data-confirm-ok="Delete">
    document.addEventListener('submit', function (e) {
      var f = e.target;
      if (!f.matches || !f.matches('form[data-confirm]') || f.dataset.confirmed) return;
      e.preventDefault();
      ask({
        text: f.dataset.confirm, title: f.dataset.confirmTitle, ok: f.dataset.confirmOk,
        icon: f.dataset.confirmIcon, btn: f.dataset.confirmBtn
      }, function () { f.dataset.confirmed = '1'; f.submit(); });
    });

    // <a data-confirm-link="Text">
    document.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('a[data-confirm-link]');
      if (!a) return;
      e.preventDefault();
      ask({ text: a.dataset.confirmLink, title: 'Confirm', ok: a.dataset.confirmOk, icon: a.dataset.confirmIcon, btn: 'btn-danger' },
          function () { window.location.href = a.href; });
    });
  }

  // ───────── Password show/hide ─────────
  document.querySelectorAll('.pw-toggle').forEach(function (b) {
    b.addEventListener('click', function () {
      var inp = b.parentElement.querySelector('input');
      var show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      b.firstElementChild.className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
    });
  });

  // ───────── Disable submit buttons after submit (anti double-click) ─────────
  document.addEventListener('submit', function (e) {
    if (e.defaultPrevented) return;
    var btn = e.target.querySelector && e.target.querySelector('button[type=submit]:not([data-nolock]), button:not([type])');
    if (btn && e.target.dataset.lock !== undefined) {
      setTimeout(function () { btn.disabled = true; btn.dataset.t = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Please wait…'; }, 0);
    }
  });
})();
