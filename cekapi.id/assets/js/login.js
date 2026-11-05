(() => {
  'use strict';
  const pass = document.getElementById('password');
  const eye  = document.getElementById('toggleEye');
  const on   = eye.querySelector('.ico-eye'), off = eye.querySelector('.ico-eye-off');
  eye.addEventListener('click', () => {
    const show = pass.type === 'password';
    pass.type = show ? 'text' : 'password';
    on.style.display  = show ? 'none' : '';
    off.style.display = show ? '' : 'none';
    eye.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
  });
  const forgot = document.getElementById('forgot'), hint = document.getElementById('hint');
  forgot.addEventListener('click', e => { e.preventDefault(); hint.classList.toggle('show'); });
})();
