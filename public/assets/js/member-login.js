// member-login.js - 會員登入
const APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
const API = `${APP_BASE}/api`;

const loginForm = document.getElementById('member-login-form');
const loginTabs = document.querySelectorAll('.login-tab');
const identityFields = document.querySelectorAll('.login-identity-field');
const googleBlock = document.querySelector('.login-google-block');
const googleLoginLink = document.querySelector('[data-google-login-link]');
const submitButton = loginForm?.querySelector('.login-submit');

function showLoginError(text) {
  const error = loginForm?.querySelector('.member-login-error');
  const message = error?.querySelector('span');
  if (!error || !message) return;
  message.textContent = text;
  error.classList.add('show');
}

function hideLoginError() {
  loginForm?.querySelector('.member-login-error')?.classList.remove('show');
}

function setLoginType(type) {
  if (!loginForm) return;
  loginForm.dataset.memberType = type;

  loginTabs.forEach((tab) => {
    const active = tab.dataset.loginType === type;
    tab.classList.toggle('active', active);
    tab.setAttribute('aria-selected', active ? 'true' : 'false');
  });

  identityFields.forEach((field) => {
    field.hidden = field.dataset.field !== type;
  });

  if (googleBlock) {
    googleBlock.hidden = false;
  }
  if (googleLoginLink) {
    googleLoginLink.href = `${APP_BASE}/auth/google?mode=login&member_type=${encodeURIComponent(type)}`;
  }
  if (submitButton) {
    submitButton.textContent = type === 'personal' ? '登入個人會員' : '登入公司法人';
  }
  hideLoginError();
}

loginTabs.forEach((tab) => {
  tab.addEventListener('click', () => setLoginType(tab.dataset.loginType || 'company'));
});

loginForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  hideLoginError();

  const memberType = loginForm.dataset.memberType || 'company';
  const password = loginForm.querySelector('[name="password"]')?.value || '';
  const payload = { member_type: memberType, password };

  if (memberType === 'personal') {
    const idNumber = (loginForm.querySelector('[name="id_number"]')?.value || '').trim().toUpperCase();
    if (!/^[A-Z][12][0-9]{8}$/.test(idNumber)) {
      showLoginError('個人會員登入請輸入身分證號。');
      return;
    }
    payload.id_number = idNumber;
  } else {
    const taxId = (loginForm.querySelector('[name="tax_id"]')?.value || '').replace(/\D/g, '');
    if (!/^\d{8}$/.test(taxId)) {
      showLoginError('公司法人登入請輸入 8 碼統一編號。');
      return;
    }
    payload.tax_id = taxId;
  }

  try {
    const res = await fetch(`${API}/members/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    if (!res.ok) {
      showLoginError(data.message || '登入失敗，請確認登入資料。');
      return;
    }

    window.location.href = `${APP_BASE}/member`;
  } catch {
    showLoginError('無法連線到伺服器。');
  }
});

googleLoginLink?.addEventListener('click', (event) => {
  const memberType = loginForm?.dataset.memberType || 'company';
  if (memberType !== 'company') return;

  const taxId = (loginForm?.querySelector('[name="tax_id"]')?.value || '').replace(/\D/g, '');
  if (!/^\d{8}$/.test(taxId)) {
    event.preventDefault();
    showLoginError('公司法人使用 Google 登入前，請先輸入 8 碼統一編號。');
    return;
  }

  googleLoginLink.href = `${APP_BASE}/auth/google?mode=login&member_type=company&tax_id=${encodeURIComponent(taxId)}`;
});

setLoginType(loginForm?.dataset.memberType || 'company');
