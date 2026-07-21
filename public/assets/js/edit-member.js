// edit-member.js — 單頁會員編輯
const APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
const API = `${APP_BASE}/api`;

function switchEditType(type) {
  document.getElementById('edit-personal-fields').style.display = type === 'personal' ? '' : 'none';
  document.getElementById('edit-company-fields').style.display = type === 'company' ? '' : 'none';
}

function showError(message) {
  document.getElementById('edit-success').classList.remove('show');
  document.getElementById('edit-error-msg').textContent = message;
  document.getElementById('edit-error').classList.add('show');
}

function showSuccess() {
  showSuccessMessage('會員資料已更新。');
}

function showSuccessMessage(message) {
  const success = document.getElementById('edit-success');
  document.getElementById('edit-error').classList.remove('show');
  success.textContent = message;
  success.classList.add('show');
}

function normalizeTaiwanMobile(value) {
  return value.replace(/[\s\-()]/g, '');
}

function validateTaiwanMobile(value) {
  return /^09\d{8}$/.test(normalizeTaiwanMobile(value));
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

function payloadFromForm(form) {
  const formData = new FormData(form);
  const payload = {};

  formData.forEach((value, key) => {
    if (key === 'payment_tools[]') {
      payload.payment_tools ||= [];
      payload.payment_tools.push(value);
      return;
    }
    payload[key] = value;
  });

  payload.payment_tools ||= [];
  return payload;
}

function bindAdminStoreManagement() {
  document.querySelectorAll('[data-admin-store-item]').forEach((item) => {
    const toggle = item.querySelector('[data-admin-store-toggle]');
    const form = item.querySelector('[data-admin-store-form]');
    const cancel = item.querySelector('[data-admin-store-cancel]');
    const success = item.querySelector('.admin-store-success');
    const error = item.querySelector('.admin-store-error');
    const errorText = error?.querySelector('span');
    const tabs = item.querySelectorAll('[data-admin-store-tab]');
    const panels = item.querySelectorAll('[data-admin-store-panel]');
    const hint = item.querySelector('[data-admin-store-tab-hint]');

     // 商店管理頁初次載入只顯示列表；個別表單須由使用者點選「管理」展開。
    if (form) {
      form.hidden = true;
      form.setAttribute('hidden', 'hidden');
    }
     if (toggle) toggle.textContent = '管理';

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.adminStoreTab;
        tabs.forEach((candidate) => candidate.classList.toggle('active', candidate === tab));
        panels.forEach((panel) => { panel.hidden = panel.dataset.adminStorePanel !== target; });

        if (hint) {
          hint.textContent = target === 'terms'
            ? '商業條件與支付工具可直接編輯。'
            : target === 'details'
              ? '商店基本資料與聯絡地址可直接編輯。'
              : '此分頁設定會與商店資料一起儲存。';
        }
      });
    });

    function showStoreMessage(type, message = '') {
      success?.classList.remove('show');
      error?.classList.remove('show');
      if (type === 'success' && success) {
        success.textContent = message || '商店資料已更新。';
        success.classList.add('show');
      }
      if (type === 'error' && error) {
        if (errorText) errorText.textContent = message || '商店資料更新失敗。';
        error.classList.add('show');
      }
    }

    toggle?.addEventListener('click', () => {
      form.hidden = !form.hidden;
      if (form.hidden) {
        form.setAttribute('hidden', 'hidden');
      } else {
        form.removeAttribute('hidden');
      }
       toggle.textContent = form.hidden ? '管理' : '收合';
    });

    cancel?.addEventListener('click', () => {
      form.hidden = true;
      form.setAttribute('hidden', 'hidden');
       if (toggle) toggle.textContent = '管理';
    });

    form?.addEventListener('submit', async (event) => {
      event.preventDefault();
      showStoreMessage('');

      const memberId = form.dataset.memberId;
      const storeId = form.dataset.storeId;
      const submit = form.querySelector('button[type="submit"]');
      const originalText = submit?.textContent || '儲存商店';
      if (submit) {
        submit.disabled = true;
        submit.textContent = '儲存中...';
      }

      try {
        const res = await fetch(`${API}/admin/members/${memberId}/stores/${storeId}/update`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(payloadFromForm(form)),
        });
        const data = await res.json().catch(() => ({}));

        if (res.status === 401 || res.status === 409) {
          window.location.href = `${APP_BASE}/admin/login`;
          return;
        }
        if (!res.ok) {
          const message = data.errors ? Object.values(data.errors).join('、') : (data.message || '商店資料更新失敗。');
          showStoreMessage('error', message);
          return;
        }

        showStoreMessage('success', data.message || '商店資料已更新。');
      } catch {
        showStoreMessage('error', '無法連線到伺服器。');
      } finally {
        if (submit) {
          submit.disabled = false;
          submit.textContent = originalText;
        }
      }
    });
  });
}

function syncAdminStoreViewMode() {
  const container = document.querySelector('.edit-container');
  if (!container) return;

  const isStoreView = window.location.hash === '#admin-store-management';
  container.classList.toggle('store-view-mode', isStoreView);

  if (isStoreView) {
    document.querySelectorAll('[data-admin-store-form]').forEach((form) => {
      form.hidden = true;
      form.setAttribute('hidden', 'hidden');
      const toggle = form.closest('[data-admin-store-item]')?.querySelector('[data-admin-store-toggle]');
           if (toggle) toggle.textContent = '管理';
    });
  }

  document.querySelectorAll('[data-member-edit-tab]').forEach((tab) => {
    const tabMode = tab.dataset.memberEditTab;
    tab.classList.toggle('active', tabMode === (isStoreView ? 'stores' : 'profile'));
  });
}

document.querySelectorAll('[data-member-edit-tab]').forEach((tab) => {
  tab.addEventListener('click', () => {
    if (tab.dataset.memberEditTab === 'stores') {
      // 即使目前已在同一個 hash，也要回到商店摘要列表。
      window.setTimeout(syncAdminStoreViewMode, 0);
    }
  });
});

document.getElementById('member-edit-page-form').addEventListener('submit', async (event) => {
  event.preventDefault();

  const id = document.getElementById('edit-id').value;
  const type = document.getElementById('edit-type').value;
  const mobile = document.getElementById('edit-mobile').value.trim();
  if (!validateTaiwanMobile(mobile)) {
    showError('請輸入有效的台灣手機號碼，例如 0912345678 或 0912-345-678。');
    return;
  }
  const contactCity = document.getElementById('edit-contact-city').value.trim();
  const contactDistrict = document.getElementById('edit-contact-district').value.trim();
  const contactAddressLine = document.getElementById('edit-address-line').value.trim();
  if (!contactCity || !contactDistrict || !contactAddressLine) {
    showError('請完整選擇縣市、地區並輸入聯絡地址。');
    return;
  }

  const payload = {
    type,
    name: document.getElementById('edit-name').value.trim(),
    email: document.getElementById('edit-email').value.trim(),
    phone_area_code: document.getElementById('edit-phone-area-code').value,
    phone: document.getElementById('edit-phone').value.trim(),
    mobile_phone: normalizeTaiwanMobile(mobile),
    contact_city: contactCity,
    contact_district: contactDistrict,
    contact_address_line: contactAddressLine,
    contact_address: `${contactCity}${contactDistrict}${contactAddressLine}`,
    password: document.getElementById('edit-password').value,
  };

  if (type === 'personal') {
    const idno = document.getElementById('edit-idno').value.trim().toUpperCase();
    if (!validateIdNo(idno)) {
      showError('請輸入有效的身分證號（含檢核碼）。');
      return;
    }
    if (!validateRocDate(document.getElementById('edit-id-issue-date').value, true)) {
      showError('請輸入有效的民國發證日期，例如 113/01/02。');
      return;
    }
    if (!validateRocDate(document.getElementById('edit-birth').value, true)) {
      showError('請輸入有效的民國出生日期，例如 083/05/15。');
      return;
    }
    payload.id_number = idno;
    payload.line_id = document.getElementById('edit-line-id').value.trim();
    payload.id_issue_date = document.getElementById('edit-id-issue-date').value;
    payload.id_issue_place = document.getElementById('edit-id-issue-place').value.trim();
    payload.id_issue_type = document.getElementById('edit-id-issue-type').value;
    payload.birth_date = document.getElementById('edit-birth').value;
    payload.gender = document.getElementById('edit-gender').value;
  } else {
    payload.tax_id = document.getElementById('edit-taxid').value.trim();
    payload.company_name = document.getElementById('edit-company').value.trim();
    payload.website = document.getElementById('edit-website').value.trim();
    payload.industry = document.getElementById('edit-industry').value;
    payload.is_dealer = document.getElementById('edit-is-dealer').checked ? 1 : 0;
  }

  try {
    const res = await fetch(`${API}/admin/members/${id}/update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));

    if (res.status === 401) {
      window.location.href = `${APP_BASE}/admin/login`;
      return;
    }
    if (res.status === 409) {
      window.location.href = `${APP_BASE}/admin/login`;
      return;
    }
    if (!res.ok) {
      const message = data.errors ? Object.values(data.errors).join('、') : (data.message || '更新失敗。');
      showError(message);
      return;
    }

    showSuccess();
    window.setTimeout(() => {
      window.location.href = `${APP_BASE}/admin`;
    }, 450);
  } catch {
    showError('無法連線到伺服器。');
  }
});

document.getElementById('resend-email-verification')?.addEventListener('click', async (event) => {
  const button = event.currentTarget;
  const id = document.getElementById('edit-id').value;
  const originalText = button.textContent;

  if (!confirm('確定要重新發送信箱驗證與初始登入資料嗎？')) {
    return;
  }

  button.disabled = true;
  button.textContent = '發送中...';

  try {
    const res = await fetch(`${API}/admin/members/${id}/resend-verification`, {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
    });
    const data = await res.json().catch(() => ({}));

    if (res.status === 401 || res.status === 409) {
      window.location.href = `${APP_BASE}/admin/login`;
      return;
    }
    if (!res.ok) {
      throw new Error(data.message || '重新發送失敗。');
    }

    const devLink = data.verification_url ? ` 開發測試連結：${data.verification_url}` : '';
    showSuccessMessage(`${data.message || '已重新發送信箱驗證。'}${devLink}`);
  } catch (error) {
    showError(error.message || '重新發送失敗。');
  } finally {
    button.disabled = false;
    button.textContent = originalText;
  }
});

function bindDocumentPreview() {
  const modal = document.getElementById('document-preview-modal');
  const frame = document.getElementById('document-preview-frame');
  const title = document.getElementById('document-preview-title');
  if (!modal || !frame || !title) return;

  function closePreview() {
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    frame.removeAttribute('src');
  }

  document.querySelectorAll('.document-preview-link').forEach((button) => {
    button.addEventListener('click', () => {
      title.textContent = button.dataset.documentTitle || '身分證電子檔';
      frame.src = button.dataset.documentUrl;
      modal.classList.add('show');
      modal.setAttribute('aria-hidden', 'false');
    });
  });

  document.getElementById('document-preview-close')?.addEventListener('click', closePreview);
  document.getElementById('document-preview-close-footer')?.addEventListener('click', closePreview);
  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closePreview();
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('show')) {
      closePreview();
    }
  });
}

switchEditType(document.getElementById('edit-type').value);
bindTaiwanAddressSelects('edit-contact-city', 'edit-contact-district');
bindRocDatePicker('edit-id-issue-date');
bindRocDatePicker('edit-birth');
bindDocumentPreview();
bindAdminStoreManagement();
syncAdminStoreViewMode();
window.addEventListener('hashchange', syncAdminStoreViewMode);
