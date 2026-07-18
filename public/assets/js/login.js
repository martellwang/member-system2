// login.js — 管理員登入
const APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
const API = `${APP_BASE}/api`;

document.getElementById('admin-login-form').addEventListener('submit', async (event) => {
  event.preventDefault();

  const error = document.getElementById('login-error');
  const message = document.getElementById('login-error-msg');
  error.classList.remove('show');

  try {
    const res = await fetch(`${API}/admin/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({
        email: document.getElementById('login-email').value.trim(),
        password: document.getElementById('login-password').value,
      }),
    });
    const data = await res.json();

    if (!res.ok) {
      message.textContent = data.message || '登入失敗，請確認帳號密碼。';
      error.classList.add('show');
      return;
    }

    window.location.href = `${APP_BASE}/admin`;
  } catch {
    message.textContent = '無法連線到伺服器。';
    error.classList.add('show');
  }
});
