/* Halaman riwayat: filter, tambah/edit/hapus */
(() => {
  'use strict';
  const C = window.CEKAPI;
  const $ = id => document.getElementById(id);
  const modal = $('modal'), form = $('modalForm'), err = $('fErr');

  // Filter periode langsung diterapkan saat tanggal diubah
  document.querySelectorAll('#filterForm input[type=date]').forEach(i =>
    i.addEventListener('change', () => $('filterForm').submit()));

  if (new URLSearchParams(location.search).has('focus')) $('searchBox').focus();

  const pad = n => String(n).padStart(2, '0');
  function nowLocal() {
    const d = new Date();
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
  }

  function openModal(row) {
    err.textContent = '';
    $('modalTitle').textContent = row ? 'Edit Data #' + String(row.id).padStart(3, '0') : 'Tambah Data';
    $('fId').value     = row ? row.id : '';
    $('fJenis').value  = row ? row.jenis_sensor : 'Sensor Suhu';
    $('fStatus').value = row ? row.status : 'aman';
    $('fNilai').value  = row ? row.nilai : '';
    $('fWaktu').value  = row ? row.waktu : nowLocal();
    $('fPesan').value  = row ? row.pesan : '';
    modal.showModal();
    $('fNilai').focus();
  }

  $('btnAdd').addEventListener('click', () => openModal(null));
  $('btnCancel').addEventListener('click', () => modal.close());

  document.querySelector('.hist tbody').addEventListener('click', async e => {
    const btn = e.target.closest('button');
    const tr  = e.target.closest('tr[data-row]');
    if (!btn || !tr) return;
    const row = JSON.parse(tr.dataset.row);

    if (btn.hasAttribute('data-edit')) return openModal(row);

    if (btn.hasAttribute('data-delete')) {
      if (!confirm('Hapus data #' + String(row.id).padStart(3, '0') + '? Tindakan ini tidak bisa dibatalkan.')) return;
      try {
        await C.postJSON('history_crud.php', { action: 'delete', id: row.id });
        C.toast('Data dihapus.');
        setTimeout(() => location.reload(), 500);
      } catch (ex) { C.toast(ex.message, true); }
    }
  });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const id = $('fId').value;
    $('btnSubmit').disabled = true;
    err.textContent = '';
    try {
      await C.postJSON('history_crud.php', {
        action: id ? 'update' : 'create',
        id: id ? Number(id) : 0,
        jenis_sensor: $('fJenis').value,
        status: $('fStatus').value,
        nilai: $('fNilai').value.trim(),
        waktu: $('fWaktu').value,
        pesan: $('fPesan').value.trim()
      });
      modal.close();
      C.toast('Data disimpan.');
      setTimeout(() => location.reload(), 500);
    } catch (ex) {
      err.textContent = ex.message;
    } finally {
      $('btnSubmit').disabled = false;
    }
  });
})();
