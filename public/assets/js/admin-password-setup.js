const APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
const API = `${APP_BASE}/api`;

function showSetupError(message) {
  document.getElementById('setup-success')?.classList.remove('show');
  const messageNode = document.getElementById('setup-error-msg');
  if (messageNode) messageNode.textContent = message;
  document.getElementById('setup-error')?.classList.add('show');
}

document.getElementById('admin-password-setup-form')?.addEventListener('submit', async (event) => {
  event.preventDefault();

  const password = document.getElementById('setup-password')?.value || '';
  const passwordConfirm = document.getElementById('setup-password-confirm')?.value || '';

  if (password.length < 8) {
    showSetupError('密碼至少需要 8 位字元。');
    return;
  }
  if (password !== passwordConfirm) {
    showSetupError('兩次輸入的密碼不一致。');
    return;
  }

  try {
    const res = await fetch(`${API}/admin/setup-password/${encodeURIComponent(PASSWORD_SETUP_TOKEN)}`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        password,
        password_confirm: passwordConfirm,
      }),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const message = data.errors ? Object.values(data.errors).join('、') : data.message;
      throw new Error(message || '設定密碼失敗。');
    }

    document.getElementById('setup-error')?.classList.remove('show');
    document.getElementById('setup-success')?.classList.add('show');
    window.setTimeout(() => {
      window.location.href = data.login_url || `${APP_BASE}/admin/login`;
    }, 900);
  } catch (error) {
    showSetupError(error.message || '設定密碼失敗。');
  }
});
