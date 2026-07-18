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

const storeForm = document.getElementById('member-store-form');
const storeMessage = document.getElementById('member-store-message');

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
