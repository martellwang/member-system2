// register.js — 會員註冊前端邏輯
const APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
const API = `${APP_BASE}/api`;

function switchType(type) {
  document.getElementById('member-type').value = type;
  document.getElementById('personal-fields').style.display = type === 'personal' ? '' : 'none';
  document.getElementById('company-fields').style.display  = type === 'company'  ? '' : 'none';
  document.getElementById('btn-personal').classList.toggle('active', type === 'personal');
  document.getElementById('btn-company').classList.toggle('active', type === 'company');
  clearErrors();
}

function clearErrors() {
  document.querySelectorAll('.error-msg').forEach(el => el.classList.remove('show'));
  document.querySelectorAll('input, select, textarea').forEach(el => el.classList.remove('error'));
  ['alert-success','alert-error'].forEach(id => document.getElementById(id).classList.remove('show'));
}

function showError(fieldId, msgId) {
  document.getElementById(fieldId).classList.add('error');
  document.getElementById(msgId).classList.add('show');
}

function validateIdNo(id) {
  const value = id.toUpperCase();
  if (!/^[A-Z][12][0-9]{8}$/.test(value)) return false;

  const letterCodes = {
    A: 10, B: 11, C: 12, D: 13, E: 14, F: 15, G: 16, H: 17, I: 34, J: 18,
    K: 19, L: 20, M: 21, N: 22, O: 35, P: 23, Q: 24, R: 25, S: 26, T: 27,
    U: 28, V: 29, W: 32, X: 30, Y: 31, Z: 33
  };
  const code = letterCodes[value[0]];
  let sum = Math.floor(code / 10) + (code % 10) * 9;

  for (let i = 1; i <= 8; i += 1) {
    sum += Number(value[i]) * (9 - i);
  }
  sum += Number(value[9]);

  return sum % 10 === 0;
}

function validateTaxId(id) {
  return /^\d{8}$/.test(id);
}

function normalizeTaiwanMobile(value) {
  return value.replace(/[\s\-()]/g, '');
}

function validateTaiwanMobile(value) {
  return /^09\d{8}$/.test(normalizeTaiwanMobile(value));
}

function validateUpload(input) {
  const file = input.files[0];
  if (!file) return false;
  const allowed = ['image/jpeg', 'image/png', 'application/pdf'];
  return allowed.includes(file.type) && file.size <= 5 * 1024 * 1024;
}

function validateRocDate(value, required = false) {
  const text = value.trim();
  if (!text) return !required;
  const match = text.match(/^(\d{2,3})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/);
  if (!match) return false;
  const year = Number(match[1]) + 1911;
  const month = Number(match[2]);
  const day = Number(match[3]);
  const date = new Date(year, month - 1, day);
  return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
}

function parseRocParts(value) {
  const match = value.trim().match(/^(\d{2,3})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/);
  if (!match) return null;
  const year = Number(match[1]) + 1911;
  const month = Number(match[2]);
  const day = Number(match[3]);
  const date = new Date(year, month - 1, day);
  if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null;
  return { rocYear: year - 1911, month, day };
}

function bindRocDatePicker(textId) {
  const textInput = document.getElementById(textId);
  const picker = document.querySelector(`.roc-picker[data-target="${textId}"]`);
  if (!textInput || !picker) return;

  const nowRocYear = new Date().getFullYear() - 1911;
  let minRocYear = Math.max(1, nowRocYear - 80);
  const currentDate = new Date();
  let currentRocYear = nowRocYear;
  let currentMonth = currentDate.getMonth() + 1;

  const initial = parseRocParts(textInput.value);
  if (initial) {
    minRocYear = Math.min(minRocYear, initial.rocYear);
    currentRocYear = initial.rocYear;
    currentMonth = initial.month;
  }

  picker.innerHTML = `
    <button type="button" class="roc-picker-toggle" aria-label="開啟日期選擇器" title="選擇日期">📅</button>
    <div class="roc-calendar" hidden>
      <div class="roc-calendar-title">
        <span>民國年月曆</span>
        <strong data-part="caption"></strong>
      </div>
      <div class="roc-calendar-head">
        <button type="button" class="icon-btn mini" data-action="prev" aria-label="上一個月">‹</button>
        <select data-part="year" aria-label="民國年"></select>
        <select data-part="month" aria-label="月份"></select>
        <button type="button" class="icon-btn mini" data-action="next" aria-label="下一個月">›</button>
      </div>
      <div class="roc-calendar-weekdays">
        <span>日</span><span>一</span><span>二</span><span>三</span><span>四</span><span>五</span><span>六</span>
      </div>
      <div class="roc-calendar-days"></div>
    </div>
  `;

  const toggle = picker.querySelector('.roc-picker-toggle');
  const calendar = picker.querySelector('.roc-calendar');
  const yearSelect = picker.querySelector('[data-part="year"]');
  const monthSelect = picker.querySelector('[data-part="month"]');
  const caption = picker.querySelector('[data-part="caption"]');
  const days = picker.querySelector('.roc-calendar-days');
  textInput.classList.add('has-roc-picker');
  textInput.insertAdjacentElement('afterend', picker);

  for (let year = nowRocYear; year >= minRocYear; year -= 1) {
    yearSelect.insertAdjacentHTML('beforeend', `<option value="${year}">民國 ${String(year).padStart(3, '0')} 年</option>`);
  }
  for (let month = 1; month <= 12; month += 1) {
    monthSelect.insertAdjacentHTML('beforeend', `<option value="${month}">${String(month).padStart(2, '0')} 月</option>`);
  }

  function formatRoc(rocYear, month, day) {
    return `${String(rocYear).padStart(3, '0')}/${String(month).padStart(2, '0')}/${String(day).padStart(2, '0')}`;
  }

  function renderCalendar() {
    yearSelect.value = String(currentRocYear);
    monthSelect.value = String(currentMonth);

    const westernYear = currentRocYear + 1911;
    const firstDay = new Date(westernYear, currentMonth - 1, 1).getDay();
    const maxDay = new Date(westernYear, currentMonth, 0).getDate();
    const selected = parseRocParts(textInput.value);
    caption.textContent = `民國 ${String(currentRocYear).padStart(3, '0')} 年 ${String(currentMonth).padStart(2, '0')} 月`;

    days.innerHTML = '';
    for (let i = 0; i < firstDay; i += 1) {
      days.insertAdjacentHTML('beforeend', '<span></span>');
    }
    for (let day = 1; day <= maxDay; day += 1) {
      const isSelected = selected && selected.rocYear === currentRocYear && selected.month === currentMonth && selected.day === day;
      days.insertAdjacentHTML('beforeend', `<button type="button" class="${isSelected ? 'selected' : ''}" data-day="${day}">${day}</button>`);
    }
  }

  function syncPicker() {
    const parts = parseRocParts(textInput.value);
    if (!parts) return;
    currentRocYear = parts.rocYear;
    currentMonth = parts.month;
    renderCalendar();
  }

  toggle.addEventListener('click', () => {
    calendar.hidden = !calendar.hidden;
    renderCalendar();
  });
  picker.querySelector('[data-action="prev"]').addEventListener('click', () => {
    currentMonth -= 1;
    if (currentMonth < 1) {
      currentMonth = 12;
      currentRocYear = Math.max(minRocYear, currentRocYear - 1);
    }
    renderCalendar();
  });
  picker.querySelector('[data-action="next"]').addEventListener('click', () => {
    currentMonth += 1;
    if (currentMonth > 12) {
      currentMonth = 1;
      currentRocYear = Math.min(nowRocYear, currentRocYear + 1);
    }
    renderCalendar();
  });
  yearSelect.addEventListener('change', () => {
    currentRocYear = Number(yearSelect.value);
    renderCalendar();
  });
  monthSelect.addEventListener('change', () => {
    currentMonth = Number(monthSelect.value);
    renderCalendar();
  });
  days.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-day]');
    if (!button) return;
    textInput.value = formatRoc(currentRocYear, currentMonth, Number(button.dataset.day));
    textInput.classList.remove('error');
    calendar.hidden = true;
    renderCalendar();
  });
  textInput.addEventListener('input', () => {
    const parts = parseRocParts(textInput.value);
    if (parts && parts.rocYear < minRocYear) {
      minRocYear = parts.rocYear;
      yearSelect.insertAdjacentHTML('beforeend', `<option value="${parts.rocYear}">民國 ${String(parts.rocYear).padStart(3, '0')} 年</option>`);
    }
    syncPicker();
  });
  textInput.addEventListener('change', () => {
    syncPicker();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      calendar.hidden = true;
    }
  });
  document.addEventListener('click', (event) => {
    if (!picker.contains(event.target)) {
      calendar.hidden = true;
    }
  });

  renderCalendar();
}

function validate() {
  let valid = true;
  const type = document.getElementById('member-type').value;
  const name  = document.getElementById('f-name').value.trim();
  const email = document.getElementById('f-email').value.trim();
  const mobile = document.getElementById('f-mobile').value.trim();
  const contactCity = document.getElementById('f-contact-city').value.trim();
  const contactDistrict = document.getElementById('f-contact-district').value.trim();
  const contactAddressLine = document.getElementById('f-address-line').value.trim();

  if (!name)                            { showError('f-name', 'err-name');  valid = false; }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showError('f-email', 'err-email'); valid = false; }
  if (!validateTaiwanMobile(mobile))    { showError('f-mobile', 'err-mobile'); valid = false; }
  if (!contactCity || !contactDistrict || !contactAddressLine) {
    ['f-contact-city', 'f-contact-district', 'f-address-line'].forEach((id) => {
      document.getElementById(id)?.classList.add('error');
    });
    document.getElementById('err-address').classList.add('show');
    valid = false;
  }

  if (type === 'personal') {
    const idno = document.getElementById('f-idno').value.trim();
    if (!validateIdNo(idno)) { showError('f-idno', 'err-idno'); valid = false; }
    if (!validateRocDate(document.getElementById('f-id-issue-date').value, true)) { showError('f-id-issue-date', 'err-id-issue-date'); valid = false; }
    if (!validateRocDate(document.getElementById('f-birth').value, true)) { showError('f-birth', 'err-birth'); valid = false; }
    if (!document.getElementById('f-id-issue-place').value.trim()) { showError('f-id-issue-place', 'err-id-issue-place'); valid = false; }
    if (!document.getElementById('f-id-issue-type').value) { showError('f-id-issue-type', 'err-id-issue-type'); valid = false; }
    if (!validateUpload(document.getElementById('f-id-front'))) { showError('f-id-front', 'err-id-front'); valid = false; }
    if (!validateUpload(document.getElementById('f-id-back'))) { showError('f-id-back', 'err-id-back'); valid = false; }
  } else {
    const taxid   = document.getElementById('f-taxid').value.trim();
    const company = document.getElementById('f-company').value.trim();
    if (!validateTaxId(taxid)) { showError('f-taxid', 'err-taxid'); valid = false; }
    if (!company)              { showError('f-company', 'err-company'); valid = false; }
  }
  return valid;
}

function showRegisterSuccessModal(data) {
  const modal = document.getElementById('register-success-modal');
  const countdownNode = document.getElementById('register-success-countdown');
  const nextButton = document.getElementById('register-success-next');
  const messageNode = document.getElementById('register-success-message');
  const destination = data.complete_url || `${APP_BASE}/register/complete`;
  let seconds = 10;

  if (!modal || !countdownNode || !nextButton) {
    window.setTimeout(() => {
      window.location.href = destination;
    }, 10000);
    return;
  }

  if (messageNode) {
    messageNode.textContent = data.message || '請先至電子郵件信箱收取驗證信，完成信箱驗證與密碼設定。';
  }

  countdownNode.textContent = String(seconds);
  modal.hidden = false;

  const goNext = () => {
    window.location.href = destination;
  };

  nextButton.onclick = goNext;
  const timer = window.setInterval(() => {
    seconds -= 1;
    countdownNode.textContent = String(Math.max(seconds, 0));
    if (seconds <= 0) {
      window.clearInterval(timer);
      goNext();
    }
  }, 1000);
}

async function submitRegister() {
  clearErrors();
  if (!validate()) return;

  const type = document.getElementById('member-type').value;
  const payload = new FormData();
  payload.append('type', type);
  payload.append('name', document.getElementById('f-name').value.trim());
  payload.append('email', document.getElementById('f-email').value.trim());
  payload.append('phone_area_code', document.getElementById('f-phone-area-code').value);
  payload.append('phone', document.getElementById('f-phone').value.trim());
  payload.append('mobile_phone', normalizeTaiwanMobile(document.getElementById('f-mobile').value.trim()));
  const contactCity = document.getElementById('f-contact-city').value.trim();
  const contactDistrict = document.getElementById('f-contact-district').value.trim();
  const contactAddressLine = document.getElementById('f-address-line').value.trim();
  payload.append('contact_city', contactCity);
  payload.append('contact_district', contactDistrict);
  payload.append('contact_address_line', contactAddressLine);
  payload.append('contact_address', `${contactCity}${contactDistrict}${contactAddressLine}`);
  payload.append('google_id', document.getElementById('f-google-id').value);

  if (type === 'personal') {
    payload.append('id_number', document.getElementById('f-idno').value.trim().toUpperCase());
    payload.append('line_id', document.getElementById('f-line-id').value.trim());
    payload.append('id_issue_date', document.getElementById('f-id-issue-date').value);
    payload.append('id_issue_place', document.getElementById('f-id-issue-place').value.trim());
    payload.append('id_issue_type', document.getElementById('f-id-issue-type').value);
    payload.append('birth_date', document.getElementById('f-birth').value);
    payload.append('gender', document.getElementById('f-gender').value);
    payload.append('id_card_front', document.getElementById('f-id-front').files[0]);
    payload.append('id_card_back', document.getElementById('f-id-back').files[0]);
  } else {
    payload.append('tax_id', document.getElementById('f-taxid').value.trim());
    payload.append('company_name', document.getElementById('f-company').value.trim());
    payload.append('website', document.getElementById('f-website').value.trim());
    payload.append('industry', document.getElementById('f-industry').value);
    payload.append('is_dealer', document.getElementById('f-is-dealer').checked ? '1' : '0');
  }

  try {
    const res = await fetch(`${API}/members/register`, {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: payload
    });
    const data = await res.json();

    if (res.ok) {
      const verifyLink = data.verification_url
        ? `<a href="${data.verification_url}" target="_blank" rel="noopener">開啟驗證連結</a>`
        : '';
      document.getElementById('alert-success').innerHTML = `註冊資料已送出，請至信箱完成驗證並設定密碼。${verifyLink ? `<span class="dev-link">${verifyLink}</span>` : ''}`;
      document.getElementById('alert-success').classList.add('show');
      document.getElementById('register-form').reset();
      showRegisterSuccessModal(data);
    } else {
      const msg = data.message || (data.errors ? Object.values(data.errors).join('、') : '發生錯誤，請稍後再試。');
      document.getElementById('alert-error-msg').textContent = msg;
      document.getElementById('alert-error').classList.add('show');
    }
  } catch (e) {
    document.getElementById('alert-error-msg').textContent = '無法連線至伺服器，請稍後再試。';
    document.getElementById('alert-error').classList.add('show');
  }
}

bindRocDatePicker('f-id-issue-date');
bindRocDatePicker('f-birth');
bindTaiwanAddressSelects('f-contact-city', 'f-contact-district');
