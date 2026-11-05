/* Dashboard real-time: kartu (polling 3 dtk), grafik & log insiden (polling 10 dtk) */
(() => {
  'use strict';
  const base = window.CEKAPI.base;
  const $ = (s, r = document) => r.querySelector(s);
  const LABEL = { aman: 'NORMAL', waspada: 'WASPADA', bahaya: 'BAHAYA' };
  const BADGE = { aman: 'Aman', waspada: 'Waspada', bahaya: 'Bahaya' };
  const baseTitle = document.title;
  let chart = null, range = 'live';

  async function getJSON(path) {
    const res = await fetch(base + '/api/' + path, { credentials: 'same-origin', cache: 'no-store' });
    if (res.status === 401) { location.href = base + '/login.php'; throw new Error('Sesi berakhir'); }
    if (!res.ok) throw new Error('HTTP ' + res.status);
    return res.json();
  }

  function paint(id, state, value, text) {
    const el = $('#card-' + id);
    if (!el) return;
    el.dataset.state = state;
    if (value !== null && value !== undefined) { const v = $('[data-val]', el); if (v) v.textContent = value; }
    $('[data-state-text]', el).textContent = text || LABEL[state];
  }

  /* ---------- Kartu indikator ---------- */
  async function pollLatest() {
    const conn = $('#conn'), cards = $('#cards');
    try {
      const d = await getJSON('latest.php');
      if (!d.data) {
        conn.className = 'conn offline';
        conn.textContent = 'Belum ada data dari perangkat';
        cards.classList.add('stale');
        return;
      }
      const on = d.online, s = d.status, x = d.data;
      conn.className = 'conn ' + (on ? 'online' : 'offline');
      conn.textContent = (on ? 'Perangkat online · ' : 'Perangkat offline · data terakhir ') + d.updated_at;
      cards.classList.toggle('stale', !on);

      const st = k => (on ? s[k] : 'aman');
      const tx = (k, t) => (on ? t : 'OFFLINE');
      paint('suhu', st('suhu'), x.suhu === null ? '--' : x.suhu.toFixed(1).replace('.', ','), tx('suhu', LABEL[s.suhu]));
      paint('asap', st('asap'), x.asap === null ? '--' : x.asap, tx('asap', LABEL[s.asap]));
      paint('gas',  st('gas'),  x.gas === null ? '--' : x.gas,   tx('gas', LABEL[s.gas]));
      paint('api',  st('api'),  null, tx('api', x.api ? 'TERDETEKSI' : 'TIDAK TERDETEKSI'));

      const bar = $('[data-bar]', $('#card-suhu'));
      if (bar && x.suhu !== null) bar.style.height = Math.max(0, Math.min(100, (x.suhu / 60) * 100)) + '%';

      document.title = (on && s.overall === 'bahaya' ? '⚠ BAHAYA · ' : '') + baseTitle;
    } catch (e) {
      conn.className = 'conn offline';
      conn.textContent = 'Koneksi ke server terputus';
    }
  }

  /* ---------- Log insiden ---------- */
  async function loadIncidents() {
    const tb = $('#incidents');
    try {
      const d = await getJSON('incidents.php');
      tb.replaceChildren();
      if (!d.items.length) {
        const tr = tb.insertRow(), td = tr.insertCell();
        td.colSpan = 4; td.className = 'empty'; td.textContent = 'Belum ada insiden tercatat.';
        return;
      }
      d.items.forEach(it => {
        const tr = tb.insertRow();
        tr.insertCell().textContent = it.alat;
        tr.insertCell().textContent = it.waktu;
        const b = document.createElement('span');
        b.className = 'badge ' + it.status; b.textContent = BADGE[it.status] || it.status;
        tr.insertCell().appendChild(b);
        tr.insertCell().textContent = it.pesan;
      });
    } catch (e) { /* biarkan data lama */ }
  }

  /* ---------- Grafik ---------- */
  function buildChart() {
    const canvas = $('#chart');
    if (typeof Chart === 'undefined') {
      canvas.parentElement.textContent = 'Grafik tidak dapat dimuat (Chart.js butuh koneksi internet).';
      return false;
    }
    const ds = (label, color, axis) => ({
      label, data: [], borderColor: color, backgroundColor: color + '22', fill: true,
      tension: .35, pointRadius: 0, borderWidth: 2, spanGaps: true, yAxisID: axis
    });
    chart = new Chart(canvas.getContext('2d'), {
      type: 'line',
      data: { labels: [], datasets: [ds('Suhu (°C)', '#2e7d32', 'y'), ds('Asap (ppm)', '#c4141c', 'y1'), ds('Gas (%)', '#1e9bd7', 'y')] },
      options: {
        responsive: true, maintainAspectRatio: false, animation: false,
        interaction: { mode: 'index', intersect: false },
        scales: {
          y:  { min: 0, max: 100, title: { display: true, text: '°C / %' } },
          y1: { position: 'right', min: 0, grid: { drawOnChartArea: false }, title: { display: true, text: 'ppm' } },
          x:  { ticks: { maxTicksLimit: 8 } }
        },
        plugins: { legend: { position: 'top' } }
      }
    });
    return true;
  }

  async function loadChart() {
    if (!chart) return;
    try {
      const d = await getJSON('chart.php?range=' + range);
      chart.data.labels = d.labels;
      chart.data.datasets[0].data = d.suhu;
      chart.data.datasets[1].data = d.asap;
      chart.data.datasets[2].data = d.gas;
      chart.update();
    } catch (e) { /* abaikan */ }
  }

  document.querySelectorAll('.seg button').forEach(btn => btn.addEventListener('click', () => {
    range = btn.dataset.range;
    document.querySelectorAll('.seg button').forEach(b => b.setAttribute('aria-pressed', String(b === btn)));
    loadChart();
  }));

  /* ---------- Jalankan ---------- */
  buildChart();
  pollLatest(); loadIncidents(); loadChart();
  setInterval(() => { if (!document.hidden) pollLatest(); }, 3000);
  setInterval(() => { if (!document.hidden) { loadIncidents(); if (range === 'live') loadChart(); } }, 10000);
})();
