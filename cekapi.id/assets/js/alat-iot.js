/* Panel kontrol aktuator */
(() => {
  'use strict';
  const C = window.CEKAPI;
  const toggles = [...document.querySelectorAll('.toggle')];
  const btnSave = document.getElementById('btnSave');

  function setToggle(btn, on) {
    btn.setAttribute('aria-checked', String(on));
    btn.querySelector('.t-text').textContent = on ? 'ON' : 'OFF';
    const lbl = document.getElementById('lbl-' + btn.dataset.kode);
    lbl.textContent = lbl.dataset.nama + ' : Status ' + (on ? 'Aktif' : 'Non Aktif');
  }

  toggles.forEach(btn => btn.addEventListener('click', () => {
    setToggle(btn, btn.getAttribute('aria-checked') !== 'true');
  }));

  function renderActivities(list) {
    const ul = document.getElementById('actList');
    ul.replaceChildren();
    list.forEach(a => {
      const li = document.createElement('li');
      const t = document.createElement('time');
      t.textContent = a.waktu;
      li.append(t, a.pesan);
      ul.appendChild(li);
    });
    document.getElementById('actEmpty').hidden = list.length > 0;
  }

  btnSave.addEventListener('click', async () => {
    const controls = {};
    toggles.forEach(b => { controls[b.dataset.kode] = b.getAttribute('aria-checked') === 'true'; });
    btnSave.disabled = true;
    try {
      const d = await C.postJSON('control.php', { controls });
      renderActivities(d.activities);
      document.getElementById('warnCount').textContent = d.warnings_today;
      C.toast('Perubahan disimpan. Perangkat akan menyesuaikan dalam beberapa detik.');
    } catch (e) {
      C.toast(e.message, true);
    } finally {
      btnSave.disabled = false;
    }
  });
})();
