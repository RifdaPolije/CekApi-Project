/* Fungsi umum: menu mobile + toast + helper fetch ber-CSRF */
(() => {
  'use strict';
  const C = window.CEKAPI = window.CEKAPI || {};

  // ---- Menu samping (mobile) ----
  const sidebar = document.getElementById('sidebar');
  const toggle  = document.getElementById('navToggle');
  const backdrop = document.getElementById('backdrop');
  function setNav(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('is-open', open);
    backdrop && backdrop.classList.toggle('show', open);
    toggle && toggle.setAttribute('aria-expanded', String(open));
  }
  toggle && toggle.addEventListener('click', () => setNav(!sidebar.classList.contains('is-open')));
  backdrop && backdrop.addEventListener('click', () => setNav(false));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') setNav(false); });

  // ---- Toast ----
  let timer;
  C.toast = (msg, isError = false) => {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = msg;
    el.classList.toggle('error', isError);
    el.classList.add('show');
    clearTimeout(timer);
    timer = setTimeout(() => el.classList.remove('show'), 2800);
  };

  // ---- POST JSON dengan token CSRF ----
  C.postJSON = async (path, body) => {
    const res = await fetch(C.base + '/api/' + path, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': C.csrf },
      body: JSON.stringify(body)
    });
    if (res.status === 401) { location.href = C.base + '/login.php'; throw new Error('Sesi berakhir'); }
    let data = {};
    try { data = await res.json(); } catch (_) { /* abaikan */ }
    if (!res.ok || data.ok === false) throw new Error(data.error || ('Gagal (HTTP ' + res.status + ')'));
    return data;
  };
})();
