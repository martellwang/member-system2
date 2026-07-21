const APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
const panelLinks = document.querySelectorAll('[data-member-panel-link]');
const panels = document.querySelectorAll('[data-member-panel]');

function showMemberPanel(panelName) {
  const target = panelName || 'overview';
  panels.forEach((panel) => {
    panel.classList.toggle('active', panel.dataset.memberPanel === target);
  });

  panelLinks.forEach((link) => {
    const linkTarget = link.dataset.memberPanelLink || '';
    const isStoreDetail = target.startsWith('store-detail-') && linkTarget === 'store-list';
    link.classList.toggle('active', linkTarget === target || isStoreDetail);
  });
}

panelLinks.forEach((link) => {
  link.addEventListener('click', (event) => {
    event.preventDefault();
    const panelName = link.dataset.memberPanelLink || 'overview';
    history.replaceState(null, '', `#${panelName}`);
    showMemberPanel(panelName);
  });
});

showMemberPanel((location.hash || '#overview').slice(1));

const storeCards = document.querySelectorAll('[data-store-card]');
const storeFilterButtons = document.querySelectorAll('[data-store-filter]');
const storeTypeButtons = document.querySelectorAll('[data-store-type-tab]');
const storeSearchInput = document.getElementById('member-store-search');
const storeEmpty = document.getElementById('member-store-empty');
const storePageInfo = document.getElementById('member-store-page-info');

let currentStoreStatus = 'all';
let currentStoreType = 'all';

function applyStoreFilters() {
  const keyword = (storeSearchInput?.value || '').trim().toLowerCase();
  let visibleCount = 0;

  storeCards.forEach((card) => {
    const statusMatched = currentStoreStatus === 'all' || card.dataset.storeStatus === currentStoreStatus;
    const typeMatched = currentStoreType === 'all' || card.dataset.storeType === currentStoreType;
    const keywordMatched = keyword === '' || (card.dataset.storeSearch || '').toLowerCase().includes(keyword);
    const visible = statusMatched && typeMatched && keywordMatched;
    card.hidden = !visible;
    if (visible) visibleCount += 1;
  });

  if (storeEmpty) {
    storeEmpty.hidden = visibleCount > 0;
    storeEmpty.textContent = storeCards.length === 0
      ? '目前尚無商店資料，請先新增商店。'
      : '沒有符合條件的商店資料。';
  }

  if (storePageInfo) {
    const start = visibleCount === 0 ? 0 : 1;
    storePageInfo.innerHTML = `顯示 ${start} - ${visibleCount} 筆，共 ${visibleCount} 筆，跳至 <input type="number" value="1" min="1"> 頁 / 1 頁 <button type="button">GO</button>`;
  }
}

storeFilterButtons.forEach((button) => {
  button.addEventListener('click', () => {
    currentStoreStatus = button.dataset.storeFilter || 'all';
    storeFilterButtons.forEach((item) => item.classList.toggle('active', item === button));
    applyStoreFilters();
  });
});

storeTypeButtons.forEach((button) => {
  button.addEventListener('click', () => {
    currentStoreType = button.dataset.storeTypeTab || 'all';
    storeTypeButtons.forEach((item) => item.classList.toggle('active', item === button));
    applyStoreFilters();
  });
});

storeSearchInput?.addEventListener('input', applyStoreFilters);
applyStoreFilters();

document.querySelectorAll('.store-detail-shell').forEach((shell) => {
  const tabs = shell.querySelectorAll('[data-store-detail-tab]');
  const tabPanels = shell.querySelectorAll('[data-store-detail-tab-panel]');

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.storeDetailTab || 'terms';
      tabs.forEach((item) => item.classList.toggle('active', item === tab));
      tabPanels.forEach((panel) => {
        panel.classList.toggle('active', panel.dataset.storeDetailTabPanel === target);
      });
    });
  });
});

document.querySelectorAll('[data-store-invoice-form]').forEach((form) => {
  const enabled = form.querySelector('[data-invoice-enabled]');
  const autoIssue = form.querySelector('[data-invoice-auto]');
  const delay = form.querySelector('[data-invoice-delay]');
  const delayedMode = form.querySelector('input[value="delayed"]');
  const message = form.querySelector('[data-invoice-message]');

  const syncInvoiceFields = () => {
    const isDelayed = delayedMode?.checked === true;
    if (delay) delay.disabled = !isDelayed || autoIssue?.checked !== true;
    if (enabled) form.classList.toggle('is-invoice-disabled', !enabled.checked);
  };

  form.querySelectorAll('input[name^="e_invoice_issue_mode_"]').forEach((input) => input.addEventListener('change', syncInvoiceFields));
  enabled?.addEventListener('change', syncInvoiceFields);
  autoIssue?.addEventListener('change', syncInvoiceFields);
  syncInvoiceFields();

  form.querySelectorAll('[data-invoice-action]').forEach((button) => {
    button.addEventListener('click', () => {
      if (message) message.textContent = '此功能將於完成電子發票服務串接後開放。';
    });
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (delay && !delay.disabled && !delay.value) {
      if (message) message.textContent = '請選擇延後開立天數。';
      delay.focus();
      return;
    }

    const payload = {
      e_invoice_enabled: enabled?.checked === true,
      e_invoice_center: form.querySelector('[name="e_invoice_center"]')?.value || '',
      e_invoice_gift_unit: form.querySelector('[name="e_invoice_gift_unit"]')?.value || '',
      e_invoice_auto_issue: autoIssue?.checked !== false,
      e_invoice_delay_days: delay?.disabled ? null : Number(delay.value),
    };
    const button = form.querySelector('button[type="submit"]');
    if (button) button.disabled = true;
    try {
      const response = await fetch(`${APP_BASE}/api/members/stores/${form.dataset.storeId}/invoice-settings`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.message || '儲存失敗。');
      if (message) message.textContent = result.message || '電子發票設定已儲存。';
    } catch (error) {
      if (message) message.textContent = error.message;
    } finally {
      if (button) button.disabled = false;
    }
  });
});

document.querySelectorAll('[data-store-integration-form]').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = form.querySelector('[data-integration-message]');
    const button = form.querySelector('button[type="submit"]');
    const payload = {};

    form.querySelectorAll('input[type="checkbox"]').forEach((input) => {
      payload[input.name] = input.checked;
    });
    form.querySelectorAll('input[type="text"], input[type="url"], textarea').forEach((input) => {
      payload[input.name] = input.value.trim();
    });

    if (button) button.disabled = true;
    if (message) message.textContent = '';
    try {
      const response = await fetch(`${APP_BASE}/api/members/stores/${form.dataset.storeId}/integration-settings`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.message || '串接設定儲存失敗。');
      if (message) message.textContent = result.message || '串接設定已儲存。';
    } catch (error) {
      if (message) message.textContent = error.message;
    } finally {
      if (button) button.disabled = false;
    }
  });
});

const storeForm = document.getElementById('member-store-form');
const storeMessage = document.getElementById('member-store-message');
const storeUrlInput = document.getElementById('member-store-url');
const storeUrlTypeInputs = storeForm?.querySelectorAll('input[name="store_url_type"]') || [];

if (storeForm && typeof bindTaiwanAddressSelects === 'function') {
  bindTaiwanAddressSelects('member-store-city', 'member-store-district');
}

function syncStoreUrlField() {
  if (!storeUrlInput) return;
  const selectedType = storeForm?.querySelector('input[name="store_url_type"]:checked')?.value || 'url';
  const enabled = selectedType === 'url';
  storeUrlInput.disabled = !enabled;
  storeUrlInput.required = enabled;
  if (!enabled) storeUrlInput.value = '';
}

storeUrlTypeInputs.forEach((input) => input.addEventListener('change', syncStoreUrlField));
syncStoreUrlField();

const contactMobileInput = storeForm?.querySelector('[name="contact_mobile"]');
const contactPhoneInput = storeForm?.querySelector('[name="contact_phone"]');
const contactPhoneAreaCode = storeForm?.querySelector('[name="contact_phone_area_code"]');
const contactMobileRequiredMark = storeForm?.querySelector('[data-contact-required="mobile"]');
const contactPhoneRequiredMark = storeForm?.querySelector('[data-contact-required="phone"]');

function syncContactPhoneRequirements() {
  const hasMobile = Boolean(contactMobileInput?.value.trim());
  const hasPhone = Boolean(contactPhoneInput?.value.trim());
  const phoneRequired = !hasMobile;
  const mobileRequired = !hasPhone;
  if (contactMobileInput) contactMobileInput.required = mobileRequired;
  if (contactPhoneInput) contactPhoneInput.required = phoneRequired;
  if (contactPhoneAreaCode) contactPhoneAreaCode.required = phoneRequired;
  if (contactMobileRequiredMark) contactMobileRequiredMark.hidden = !mobileRequired;
  if (contactPhoneRequiredMark) contactPhoneRequiredMark.hidden = !phoneRequired;
}

contactMobileInput?.addEventListener('input', syncContactPhoneRequirements);
contactPhoneInput?.addEventListener('input', syncContactPhoneRequirements);
syncContactPhoneRequirements();

function isCompliantStoreUrl(value) {
  try {
    const url = new URL(value);
    return ['http:', 'https:'].includes(url.protocol) && url.hostname !== '';
  } catch {
    return false;
  }
}

function setStoreMessage(message, type = '') {
  if (!storeMessage) return;
  storeMessage.textContent = message;
  storeMessage.className = `member-store-message ${type}`.trim();
}

function formPayload(form) {
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

  return payload;
}

storeForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  setStoreMessage('');

  const storeUrlType = storeForm.querySelector('input[name="store_url_type"]:checked')?.value || 'url';
  if (storeUrlType === 'url' && (!storeUrlInput?.value.trim() || !isCompliantStoreUrl(storeUrlInput.value.trim()))) {
    setStoreMessage('請輸入有效的商店網址，網址必須以 http:// 或 https:// 開頭並包含網域。', 'error');
    storeUrlInput?.focus();
    return;
  }

  const submitButton = storeForm.querySelector('button[type="submit"]');
  const originalText = submitButton?.textContent || '新增商店';
  if (submitButton) {
    submitButton.disabled = true;
    submitButton.textContent = '送出中...';
  }

  try {
    const res = await fetch(`${APP_BASE}/api/members/stores`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(formPayload(storeForm)),
    });
    const data = await res.json().catch(() => ({}));

    if (res.status === 401 || res.status === 409) {
      window.location.href = `${APP_BASE}/login`;
      return;
    }

    if (!res.ok) {
      const firstError = data.errors ? Object.values(data.errors)[0] : '';
      throw new Error(firstError || data.message || '新增商店失敗。');
    }

    storeForm.reset();
    setStoreMessage(data.message || '新增商店申請已送出。', 'success');
    window.location.href = `${APP_BASE}/member#store-list`;
    window.location.reload();
  } catch (error) {
    setStoreMessage(error.message || '新增商店失敗。', 'error');
  } finally {
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.textContent = originalText;
    }
  }
});

document.querySelectorAll('[data-store-transaction-form]').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = form.querySelector('[data-transaction-message]');
    const button = form.querySelector('button[type="submit"]');
    const payload = {
      transaction_amount_limit_enabled: form.querySelector('[name="transaction_amount_limit_enabled"]')?.checked === true,
      expired_refund_enabled: form.querySelector('[name="expired_refund_enabled"]')?.checked === true,
      transaction_card_limit_mode: form.querySelector('[name="transaction_card_limit_mode"]:checked')?.value || 'off',
      transaction_ip_limit_mode: form.querySelector('[name="transaction_ip_limit_mode"]:checked')?.value || 'off',
    };
    if (button) button.disabled = true;
    try {
      const response = await fetch(`${APP_BASE}/api/members/stores/${form.dataset.storeId}/transaction-settings`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload),
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.message || '儲存失敗。');
      if (message) message.textContent = result.message || '交易限制設定已儲存。';
    } catch (error) {
      if (message) message.textContent = error.message;
    } finally {
      if (button) button.disabled = false;
    }
  });
});
