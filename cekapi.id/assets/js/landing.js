(() => {
  'use strict';
  // Menu mobile
  const burger = document.getElementById('burger'), menu = document.getElementById('menu');
  burger.addEventListener('click', () => {
    const open = menu.classList.toggle('open');
    burger.setAttribute('aria-expanded', String(open));
  });
  menu.addEventListener('click', e => { if (e.target.closest('a')) menu.classList.remove('open'); });

  // Filter kategori alat
  const tabs = [...document.querySelectorAll('[role=tab]')];
  const cards = [...document.querySelectorAll('.tcard')];
  const track = document.getElementById('track');
  tabs.forEach(t => t.addEventListener('click', () => {
    tabs.forEach(x => x.setAttribute('aria-selected', String(x === t)));
    cards.forEach(c => { c.hidden = c.dataset.cat !== t.dataset.cat; });
    track.scrollTo({ left: 0 });
  }));

  // Panah carousel
  const step = () => (cards.find(c => !c.hidden) || { offsetWidth: 220 }).offsetWidth + 22;
  document.getElementById('prev').addEventListener('click', () => track.scrollBy({ left: -step() }));
  document.getElementById('next').addEventListener('click', () => track.scrollBy({ left: step() }));
})();
