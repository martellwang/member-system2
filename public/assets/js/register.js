const BASE_PATH = (() => {
  const marker = '/public';
  const index = window.location.pathname.indexOf(marker);
  return index >= 0 ? window.location.pathname.slice(0, index + marker.length) : '';
})();
const API = `${BASE_PATH}/api`;

const personalFields = ['f-idno', 'f-birth', 'f-gender'];
const companyFields = ['f-taxid', 'f-company', 'f-website', 'f-industry'];

function setPanelState(type) {
  const isPersonal = type === 'personal';

  document.getElementById('member-type').value = type;
  document.getElementById('personal-fields').hidden = !isPersonal;
  document.getElementById('company-fields').hidden = isPersonal;

  document.getElementById('btn-personal').classList.toggle('active', isPersonal);
  document.getElementById('btn-company').classList.toggle('active', !isPersonal);
  document.getElementById('btn-personal').setAttribute('aria-selected', String(isPersonal));
  document.getElementById('btn-company').setAttribute('aria-selected', String(!isPersonal));

  document.getElementById('f-idno').required = isPersonal;
  document.getElementById('f-taxid').required = !isPersonal;
  document.getElementById('f-company').required = !isPersonal;

  const inactiveFields = isPersonal ? companyFields : personalFields;
  inactiveFields.forEach(id => {
    const field = document.getElementById(id);
    field.value = '';
    field.classList.remove('error');
  });
}

function switchType(type) {
  setPanelState(type);
  clearErrors();
}

function clearErrors() {
  document.querySelectorAll('.error-msg').forEach(el => el.classList.remove('show'));
  document.querySelectorAll('input, select').forEach(el => el.classList.remove('error'));
  ['alert-success', 'alert-error'].forEach(id => document.getElementById(id).classList.remove('show'));
}

function showError(fieldId, msgId) {
  document.getElementById(fieldId).classList.add('error');
  document.getElementById(msgId).classList.add('show');
}

function validateIdNo(id) {
  return /^[A-Z][12][0-9]{8}$/.test(id.toUpperCase());
}

function validateTaxId(id) {
  return /^\d{8}$/.test(id);
}

function validate() {
  let valid = true;
  const type = document.getElementById('member-type').value;
  const name = document.getElementById('f-name').value.trim();
  const email = document.getElementById('f-email').value.trim();
  const pass = document.getElementById('f-pass').value;

  if (!name) {
    showError('f-name', 'err-name');
    valid = false;
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showError('f-email', 'err-email');
    valid = false;
  }
  if (pass.length < 8) {
    showError('f-pass', 'err-pass');
    valid = false;
  }

  if (type === 'personal') {
    const idno = document.getElementById('f-idno').value.trim();
    if (!validateIdNo(idno)) {
      showError('f-idno', 'err-idno');
      valid = false;
    }
  } else {
    const taxid = document.getElementById('f-taxid').value.trim();
    const company = document.getElementById('f-company').value.trim();
    if (!validateTaxId(taxid)) {
      showError('f-taxid', 'err-taxid');
      valid = false;
    }
    if (!company) {
      showError('f-company', 'err-company');
      valid = false;
    }
  }

  return valid;
}

function buildPayload() {
  const type = document.getElementById('member-type').value;
  const payload = {
    type,
    name: document.getElementById('f-name').value.trim(),
    email: document.getElementById('f-email').value.trim(),
    phone: document.getElementById('f-phone').value.trim(),
    password: document.getElementById('f-pass').value,
  };

  if (type === 'personal') {
    payload.id_number = document.getElementById('f-idno').value.trim().toUpperCase();
    payload.birth_date = document.getElementById('f-birth').value;
    payload.gender = document.getElementById('f-gender').value;
  } else {
    payload.tax_id = document.getElementById('f-taxid').value.trim();
    payload.company_name = document.getElementById('f-company').value.trim();
    payload.website = document.getElementById('f-website').value.trim();
    payload.industry = document.getElementById('f-industry').value;
  }

  return payload;
}

async function submitRegister() {
  clearErrors();
  if (!validate()) return;

  try {
    const res = await fetch(`${API}/members/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(buildPayload())
    });
    const data = await res.json();

    if (res.ok) {
      document.getElementById('alert-success').classList.add('show');
      document.getElementById('register-form').reset();
      setPanelState('personal');
    } else {
      const messages = data.errors ? Object.values(data.errors).join('、') : data.message;
      document.getElementById('alert-error-msg').textContent = messages || '註冊失敗，請稍後再試。';
      document.getElementById('alert-error').classList.add('show');
    }
  } catch (e) {
    document.getElementById('alert-error-msg').textContent = '無法連線到伺服器，請稍後再試。';
    document.getElementById('alert-error').classList.add('show');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  setPanelState(document.getElementById('member-type').value || 'personal');
  document.getElementById('register-form').addEventListener('submit', event => {
    event.preventDefault();
    submitRegister();
  });
});
