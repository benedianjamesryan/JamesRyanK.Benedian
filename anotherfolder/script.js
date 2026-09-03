document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('loginOverlay');
  const close = document.getElementById('closeLogin');
  const password = document.getElementById('password');
  const showPassword = document.getElementById('showPassword');
  const triggers = document.querySelectorAll('.requires-login');

  function openLogin(e) {
    e.preventDefault();
    overlay.classList.add('show');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    setTimeout(() => document.getElementById('email')?.focus(), 50);
  }
  function closeLogin() {
    overlay.classList.remove('show');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  }
  triggers.forEach(el => el.addEventListener('click', openLogin));
  close?.addEventListener('click', closeLogin);
  overlay?.addEventListener('click', e => { if (e.target === overlay) closeLogin(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLogin(); });
  showPassword?.addEventListener('click', () => {
    const isPassword = password.type === 'password';
    password.type = isPassword ? 'text' : 'password';
    showPassword.textContent = isPassword ? 'HIDE' : 'SHOW';
  });
});
