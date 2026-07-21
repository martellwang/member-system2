// admin-staff.js - 後台內部管理人員
const APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
const API = `${APP_BASE}/api`;

let staffRows = [];
let filteredStaffRows = [];
let staffKeyword = '';
let securityAllowedIps = [];
let permissionGroups = [];
let editingGroupIndex = null;
let activeDealerRows = [];
let selectedStoreCodeDealer = null;
let selectedStoreCodePrefixData = null;
let editingStoreCodePrefixId = null;
let storeCodeRows = [];
let deviceSupplierRows = [];
let editingDeviceSupplierId = null;
const PERMISSION_LABELS = {
  'member.view': '查看會員資料',
  'member.edit': '編輯會員資料',
  'member.review': '審核 / 停用會員',
  'member.delete': '刪除會員',
  'dealer.view': '查看經銷商',
  'dealer.edit': '編輯經銷商旗標',
  'security.ip': '管理可登入 IP',
  'staff.manage': '管理內部管理帳號',
  'group.manage': '管理群組權限',
};

async function requestJson(url, options = {}) {
  const res = await fetch(url, {
    headers: { 'Accept': 'application/json', ...(options.headers || {}) },
    ...options,
  });
  const data = await res.json().catch(() => ({}));

  if (res.status === 401) {
    window.location.href = `${APP_BASE}/admin/login`;
    throw new Error('請先登入管理後台。');
  }
  if (res.status === 409) {
    window.location.href = `${APP_BASE}/admin/login`;
    throw new Error(data.message || '此管理帳號已在其他地方登入。');
  }
  if (!res.ok) {
    const error = new Error(data.message || '操作失敗。');
    error.details = data.errors || null;
    throw error;
  }

  return data;
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  }[char]));
}

function isValidIpRule(value) {
  const rule = String(value || '').trim();
  if (!rule) return false;

  const [ip, prefix, extra] = rule.split('/');
  if (extra !== undefined) return false;

  const ipv4 = /^(25[0-5]|2[0-4]\d|1?\d?\d)(\.(25[0-5]|2[0-4]\d|1?\d?\d)){3}$/;
  const ipv6 = /^(([0-9a-f]{1,4}:){2,7}[0-9a-f]{0,4}|::1|::)$/i;
  const isIpv4 = ipv4.test(ip);
  const isIpv6 = ipv6.test(ip);
  if (!isIpv4 && !isIpv6) return false;
  if (prefix === undefined) return true;
  if (!/^\d+$/.test(prefix)) return false;

  const prefixNumber = Number(prefix);
  return prefixNumber >= 0 && prefixNumber <= (isIpv6 ? 128 : 32);
}

function renderSecurityIpList() {
  const list = document.getElementById('security-ip-list');
  const empty = document.getElementById('security-ip-empty');
  if (!list || !empty) return;

  empty.hidden = securityAllowedIps.length > 0;
  list.innerHTML = securityAllowedIps.map((item, index) => `
    <li class="security-ip-item">
      <span class="mono-cell">${escapeHtml(item.ip)}</span>
      <span class="security-ip-note">${item.note ? escapeHtml(item.note) : '—'}</span>
      <button type="button" class="btn btn-sm btn-outline" data-remove-security-ip="${index}">移除</button>
    </li>
  `).join('');
}

function addSecurityIp() {
  const input = document.getElementById('security-ip-input');
  const noteInput = document.getElementById('security-ip-note');
  const value = input?.value.trim() || '';
  const note = noteInput?.value.trim() || '';
  if (!value) return;
  if (!isValidIpRule(value)) {
    document.getElementById('security-success').classList.remove('show');
    document.getElementById('security-error-msg').textContent = '可登入 IP 格式不正確，請使用單一 IP 或 CIDR。';
    document.getElementById('security-error').classList.add('show');
    input?.focus();
    return;
  }

  const existingIndex = securityAllowedIps.findIndex(item => item.ip === value);
  if (existingIndex >= 0) {
    securityAllowedIps[existingIndex] = { ip: value, note };
  } else {
    securityAllowedIps = [...securityAllowedIps, { ip: value, note }];
  }
  if (input) input.value = '';
  if (noteInput) noteInput.value = '';
  document.getElementById('security-error').classList.remove('show');
  renderSecurityIpList();
}

function selectedPermissions() {
  return Array.from(document.querySelectorAll('#permission-options input[type="checkbox"]:checked'))
    .map(input => input.value);
}

function clearGroupForm() {
  const nameInput = document.getElementById('group-name-input');
  const addButton = document.getElementById('group-add');
  const cancelButton = document.getElementById('group-cancel-edit');
  if (nameInput) nameInput.value = '';
  document.querySelectorAll('#permission-options input[type="checkbox"]').forEach(input => {
    input.checked = false;
  });
  editingGroupIndex = null;
  if (addButton) addButton.textContent = '新增群組';
  if (cancelButton) cancelButton.hidden = true;
}

function renderPermissionGroups() {
  const list = document.getElementById('group-list');
  const empty = document.getElementById('group-empty');
  if (!list || !empty) return;

  empty.hidden = permissionGroups.length > 0;
  list.innerHTML = permissionGroups.map((group, index) => {
    const permissions = group.permissions.length
      ? group.permissions.map(permission => `<span class="permission-chip">${escapeHtml(PERMISSION_LABELS[permission] || permission)}</span>`).join('')
      : '<span class="muted">尚未設定權限</span>';
    return `
      <div class="group-item">
        <div>
          <div class="cell-main">${escapeHtml(group.name)}</div>
          <div class="permission-chip-row">${permissions}</div>
        </div>
        <div class="group-actions">
          <button type="button" class="btn btn-sm btn-outline" data-edit-group="${index}">編輯</button>
          <button type="button" class="btn btn-sm btn-outline" data-remove-group="${index}">移除</button>
        </div>
      </div>
    `;
  }).join('');
}

function addPermissionGroup() {
  const nameInput = document.getElementById('group-name-input');
  const name = nameInput?.value.trim() || '';
  if (!name) {
    document.getElementById('group-success').classList.remove('show');
    document.getElementById('group-error-msg').textContent = '請輸入群組名稱。';
    document.getElementById('group-error').classList.add('show');
    nameInput?.focus();
    return;
  }

  const permissions = selectedPermissions();
  const duplicateIndex = permissionGroups.findIndex((group, index) => group.name === name && index !== editingGroupIndex);
  if (duplicateIndex >= 0) {
    document.getElementById('group-success').classList.remove('show');
    document.getElementById('group-error-msg').textContent = '已有相同名稱的群組。';
    document.getElementById('group-error').classList.add('show');
    nameInput?.focus();
    return;
  }

  if (editingGroupIndex !== null && permissionGroups[editingGroupIndex]) {
    permissionGroups[editingGroupIndex] = { name, permissions };
  } else {
    const existingIndex = permissionGroups.findIndex(group => group.name === name);
    if (existingIndex >= 0) {
    permissionGroups[existingIndex] = { name, permissions };
    } else {
      permissionGroups = [...permissionGroups, { name, permissions }];
    }
  }
  document.getElementById('group-error').classList.remove('show');
  clearGroupForm();
  renderPermissionGroups();
}

function editPermissionGroup(index) {
  const group = permissionGroups[index];
  const nameInput = document.getElementById('group-name-input');
  const addButton = document.getElementById('group-add');
  const cancelButton = document.getElementById('group-cancel-edit');
  if (!group || !nameInput) return;

  editingGroupIndex = index;
  nameInput.value = group.name;
  document.querySelectorAll('#permission-options input[type="checkbox"]').forEach(input => {
    input.checked = group.permissions.includes(input.value);
  });
  if (addButton) addButton.textContent = '更新群組';
  if (cancelButton) cancelButton.hidden = false;
  document.getElementById('group-error').classList.remove('show');
  document.getElementById('group-success').classList.remove('show');
  nameInput.focus();
  nameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function roleLabel(role) {
  return role === 'super_admin' ? '系統管理員' : '一般管理人員';
}

function staffPermissionLabel(staff) {
  return staff.permission_group || roleLabel(staff.role);
}

function statusLabel(status) {
  if (status === 'active') return '啟用';
  if (status === 'pending_activation') return '待啟用';
  return '停用';
}

function statusClass(status) {
  if (status === 'active') return 'badge-active';
  if (status === 'pending_activation') return 'badge-pending';
  return 'badge-suspended';
}

async function loadActiveDealers() {
  const members = await requestJson(`${API}/admin/members`);
  activeDealerRows = members.filter(member => Number(member.is_dealer) === 1 && member.status === 'active');
  activeDealerRows = await Promise.all(activeDealerRows.map(async (dealer) => {
    try {
      const data = await requestJson(`${API}/admin/store-code-prefixes/${Number(dealer.id)}`);
      const prefixes = Array.isArray(data.prefixes) ? data.prefixes.map((item) => item.prefix).filter(Boolean) : [];
      return { ...dealer, store_code_prefixes: prefixes, store_code_prefix: prefixes.join('、') };
    } catch (_) {
      return { ...dealer, store_code_prefixes: [], store_code_prefix: '' };
    }
  }));
  return activeDealerRows;
}

function dealerDisplayName(dealer) {
  return dealer.company_name || dealer.name || dealer.email || `會員 #${dealer.id}`;
}

async function loadStoreCodeRows() {
  storeCodeRows = await requestJson(`${API}/admin/store-codes`);
  return storeCodeRows;
}

function storeStatusLabel(status) {
  if (status === 'active') return '啟用';
  if (status === 'pending') return '待審';
  if (status === 'suspended') return '停用';
  if (status === 'rejected') return '退件';
  return '未設定';
}

function storeStatusBadgeClass(status) {
  if (status === 'active') return 'badge-active';
  if (status === 'pending') return 'badge-pending';
  return 'badge-suspended';
}

function renderStoreCodeRows() {
  const panel = document.getElementById('store-code-panel');
  if (!panel) return;

  if (!storeCodeRows.length) {
    panel.innerHTML = `${renderStoreCodeChildNav('list')}<div class="store-code-empty">目前尚無商店代號資料。</div>`;
    return;
  }

  panel.innerHTML = `
    ${renderStoreCodeChildNav('list')}
    <div class="table-card store-code-list-card">
      <table class="store-code-list-table">
        <thead>
          <tr>
            <th>序號</th>
            <th>商店代號</th>
            <th>會員名稱</th>
            <th>經銷商</th>
            <th>設備數量</th>
            <th>啟用狀態</th>
            <th>編輯</th>
          </tr>
        </thead>
        <tbody>
          ${storeCodeRows.map((row, index) => {
            const editUrl = `${APP_BASE}/admin/members/${Number(row.member_id)}/edit#admin-store-management`;
            return `
              <tr>
                <td>${index + 1}</td>
                <td>
                  <strong>${escapeHtml(row.store_code || '-')}</strong>
                  <span class="muted block">${escapeHtml(row.store_name || '')}</span>
                </td>
                <td>
                  <strong>${escapeHtml(row.member_name || '-')}</strong>
                  <span class="muted block">${escapeHtml(row.member_code || '')}${row.member_email ? `｜${escapeHtml(row.member_email)}` : ''}</span>
                </td>
                <td>${row.dealer_name ? escapeHtml(row.dealer_name) : '<span class="muted">—</span>'}</td>
                <td><span class="store-code-device-count">${Number(row.device_count || 0)}</span></td>
                <td><span class="badge ${storeStatusBadgeClass(row.status)}">${storeStatusLabel(row.status)}</span></td>
                <td><a class="btn btn-sm btn-outline" href="${editUrl}">編輯</a></td>
              </tr>
            `;
          }).join('')}
        </tbody>
      </table>
    </div>
  `;
}

function renderStoreCodePrefixDealerList() {
  const panel = document.getElementById('store-code-panel');
  if (!panel) return;

  selectedStoreCodeDealer = null;
  if (!activeDealerRows.length) {
    panel.innerHTML = `
      <div class="store-code-empty">
        目前沒有標註為運作中的經銷商會員。
      </div>
    `;
    return;
  }

  panel.innerHTML = `
    ${renderStoreCodeChildNav('prefix')}
    <div class="table-card store-code-dealer-table-card">
      <table class="store-code-dealer-table">
        <thead>
          <tr>
            <th>序號</th>
            <th>公司名稱</th>
            <th>現有前置碼列表</th>
          </tr>
        </thead>
        <tbody>
          ${activeDealerRows.map((dealer, index) => `
            <tr class="store-code-dealer-row" data-store-code-dealer-id="${Number(dealer.id)}">
              <td>${index + 1}</td>
              <td>
                <strong>${escapeHtml(dealerDisplayName(dealer))}</strong>
                <span class="muted block">${escapeHtml(dealer.member_code || '')}${dealer.member_code ? ' ｜ ' : ''}${escapeHtml(dealer.email || '')}</span>
              </td>
              <td>
                ${dealer.store_code_prefixes?.length
                  ? dealer.store_code_prefixes.map((prefix) => `<span class="store-code-prefix-chip">${escapeHtml(prefix)}</span>`).join('')
                  : '<span class="muted">尚未設定</span>'}
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

async function renderStoreCodePrefixSettings(dealerId) {
  const panel = document.getElementById('store-code-panel');
  if (!panel) return;

  selectedStoreCodeDealer = activeDealerRows.find(dealer => Number(dealer.id) === Number(dealerId)) || null;
  if (!selectedStoreCodeDealer) {
    renderStoreCodePrefixDealerList();
    return;
  }

  panel.innerHTML = '<div class="store-code-empty">前置碼設定載入中...</div>';
  selectedStoreCodePrefixData = await requestJson(`${API}/admin/store-code-prefixes/${Number(dealerId)}`);
  renderStoreCodePrefixWorkspace('list');
}

function renderStoreCodePrefixWorkspace(mode = 'list', prefixId = null) {
  const panel = document.getElementById('store-code-panel');
  if (!panel || !selectedStoreCodeDealer || !selectedStoreCodePrefixData) return;

  editingStoreCodePrefixId = mode === 'edit' ? Number(prefixId) : null;
  const isList = mode === 'list';
  const isCreate = mode === 'create';

  panel.innerHTML = `
    ${renderStoreCodeChildNav('prefix')}
    <div class="store-code-prefix-settings">
      <div class="store-code-prefix-header">
        <button type="button" class="btn btn-sm btn-outline" id="store-code-back-dealers">返回經銷商列表</button>
        <div>
          <h3>${escapeHtml(dealerDisplayName(selectedStoreCodeDealer))}</h3>
          <p>${escapeHtml(selectedStoreCodeDealer.email || '')}</p>
        </div>
      </div>
      <div class="store-code-prefix-mode-row">
        <button type="button" class="store-code-prefix-mode ${isList ? 'active' : ''}" data-store-code-prefix-mode="list">前置碼列表</button>
        <button type="button" class="store-code-prefix-mode ${isCreate ? 'active' : ''}" data-store-code-prefix-mode="create">新增前置碼</button>
      </div>
      <div class="store-code-prefix-content">
        ${isList ? renderStoreCodePrefixList() : renderStoreCodePrefixForm(mode)}
      </div>
    </div>
  `;
}

function renderStoreCodeChildNav(activeTab) {
  return `
    <div class="store-code-child-tab-row" aria-label="商店代號子功能">
      <button type="button" class="store-code-child-tab ${activeTab === 'list' ? 'active' : ''}" data-store-code-child-tab="list">商店代號列表</button>
      <button type="button" class="store-code-child-tab ${activeTab === 'prefix' ? 'active' : ''}" data-store-code-child-tab="prefix">前置碼設定</button>
    </div>
  `;
}

function renderStoreCodePrefixList() {
  const prefixes = Array.isArray(selectedStoreCodePrefixData?.prefixes) ? selectedStoreCodePrefixData.prefixes : [];
  if (!prefixes.length) {
    return `
      <div class="store-code-prefix-list-empty">
        此會員目前尚未建立前置碼。
      </div>
    `;
  }

  return `
    <div class="table-card store-code-prefix-list-card">
      <table class="store-code-list-table">
        <thead>
          <tr>
            <th>序號</th>
              <th>前置碼</th>
              <th>設定時間</th>
              <th>適用會員</th>
              <th>備註</th>
              <th>操作</th>
            </tr>
          </thead>
        <tbody>
          ${prefixes.map((prefix, index) => `
            <tr>
              <td>${index + 1}</td>
                <td><strong>${escapeHtml(prefix.prefix || '-')}</strong></td>
                <td>${escapeHtml(prefix.setting_date || '-')}</td>
                <td>${escapeHtml(dealerDisplayName(selectedStoreCodeDealer))}</td>
                <td>${prefix.remark ? escapeHtml(prefix.remark) : '<span class="muted">—</span>'}</td>
                <td><button type="button" class="btn btn-sm btn-outline" data-edit-store-code-prefix="${Number(prefix.id)}">編輯</button></td>
              </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

function renderStoreCodePrefixForm(mode = 'create') {
  const isEdit = mode === 'edit';
  const prefixData = isEdit
    ? (selectedStoreCodePrefixData?.prefixes || []).find((item) => Number(item.id) === Number(editingStoreCodePrefixId))
    : null;
  const prefix = prefixData?.prefix || '';
  const settingDate = prefixData?.setting_date || selectedStoreCodePrefixData?.today || '';
  const remark = prefixData?.remark || '';

  return `
    <form class="store-code-prefix-form" id="store-code-prefix-form" data-store-code-prefix-member-id="${Number(selectedStoreCodeDealer.id)}" data-store-code-prefix-id="${prefixData ? Number(prefixData.id) : ''}" novalidate>
      <div class="form-title">${isEdit ? '編輯前置碼' : '新增前置碼'}</div>
      <div class="form-subtitle">此前置碼只用在此會員身上。</div>
      <div class="form-row">
        <div class="form-group">
          <label>前置碼 <span class="required">*</span></label>
          <input type="text" id="store-code-prefix-input" value="${escapeHtml(prefix)}" maxlength="4" autocomplete="off" />
          <div class="field-hint">前置碼必須剛好為四個英文字母。</div>
        </div>
        <div class="form-group">
          <label>設定時間</label>
          <input type="date" id="store-code-prefix-date" value="${escapeHtml(settingDate)}" readonly />
          <div class="field-hint">系統自動帶入當天日期。</div>
          </div>
        </div>
        <div class="form-group">
          <label>備註</label>
          <textarea id="store-code-prefix-remark" maxlength="400" rows="4">${escapeHtml(remark)}</textarea>
          <div class="field-hint">最多 400 字元。</div>
        </div>
        <div class="alert alert-success" id="store-code-prefix-success"></div>
      <div class="alert alert-danger" id="store-code-prefix-error"><span id="store-code-prefix-error-msg"></span></div>
      <div class="form-actions">
        <button type="button" class="btn btn-outline" id="store-code-prefix-list-back">返回前置碼列表</button>
        <button type="submit" class="btn btn-success">${isEdit ? '儲存修改' : '儲存前置碼'}</button>
      </div>
    </form>
  `;
}

async function showStoreCodePrefixTab() {
  const panel = document.getElementById('store-code-panel');
  if (panel) {
    panel.innerHTML = '<div class="store-code-empty">經銷商資料載入中...</div>';
  }

  await loadActiveDealers();
  renderStoreCodePrefixDealerList();
}

async function showStoreCodeListTab() {
  const panel = document.getElementById('store-code-panel');
  selectedStoreCodeDealer = null;
  if (panel) {
    panel.innerHTML = '<div class="store-code-empty">商店代號列表載入中...</div>';
  }
  await loadStoreCodeRows();
  renderStoreCodeRows();
}

function activateStoreCodeTab(tabName) {
  document.querySelectorAll('[data-store-code-tab]').forEach((item) => {
    item.classList.toggle('active', item.dataset.storeCodeTab === tabName);
  });
}

function showStoreCodePrefixError(message) {
  document.getElementById('store-code-prefix-success')?.classList.remove('show');
  const msg = document.getElementById('store-code-prefix-error-msg');
  const box = document.getElementById('store-code-prefix-error');
  if (msg) msg.textContent = message;
  box?.classList.add('show');
}

function showStoreCodePrefixSuccess(message) {
  document.getElementById('store-code-prefix-error')?.classList.remove('show');
  const box = document.getElementById('store-code-prefix-success');
  if (box) box.textContent = message;
  box?.classList.add('show');
}

async function saveStoreCodePrefix(form) {
  const memberId = Number(form.dataset.storeCodePrefixMemberId);
  const prefixId = Number(form.dataset.storeCodePrefixId || 0);
  const input = document.getElementById('store-code-prefix-input');
  const remarkInput = document.getElementById('store-code-prefix-remark');
  const prefix = (input?.value || '').trim().toUpperCase();
  const remark = (remarkInput?.value || '').trim();
  if (input) input.value = prefix;

  if (!/^[A-Z]{4}$/.test(prefix)) {
    showStoreCodePrefixError('前置碼必須剛好為 4 個英文字母。');
    input?.focus();
    return;
  }
  if (remark.length > 400) {
    showStoreCodePrefixError('備註最多 400 字元。');
    remarkInput?.focus();
    return;
  }

  const url = prefixId
    ? `${API}/admin/store-code-prefixes/${memberId}/${prefixId}`
    : `${API}/admin/store-code-prefixes/${memberId}`;
  const data = await requestJson(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ prefix, remark }),
  });
  showStoreCodePrefixSuccess(data.message || (prefixId ? '前置碼已更新。' : '前置碼已新增。'));
}

async function loadDeviceSuppliers() {
  deviceSupplierRows = await requestJson(`${API}/admin/device-suppliers`);
  return deviceSupplierRows;
}

function renderDeviceSupplierList() {
  const panel = document.getElementById('device-group-panel');
  if (!panel) return;

  editingDeviceSupplierId = null;
  if (!deviceSupplierRows.length) {
    panel.innerHTML = '<div class="device-supplier-empty">尚未建立設備供應商群組。</div>';
    return;
  }

  panel.innerHTML = `
    <div class="device-supplier-list">
      ${deviceSupplierRows.map((supplier) => `
        <div class="device-supplier-item">
          <div>
            <strong>${escapeHtml(supplier.company_name)}</strong>
            <span>${escapeHtml(supplier.tax_id)} ｜ 備註：${escapeHtml(supplier.up_memo)}</span>
            <em>${escapeHtml(supplier.contact_name)} ｜ ${escapeHtml(supplier.contact_phone)}</em>
          </div>
          <button type="button" class="btn btn-sm btn-outline" data-edit-device-supplier="${Number(supplier.id)}">編輯</button>
        </div>
      `).join('')}
    </div>
  `;
}

function renderDeviceSupplierForm(supplier = null) {
  const panel = document.getElementById('device-group-panel');
  if (!panel) return;

  editingDeviceSupplierId = supplier ? Number(supplier.id) : null;
  panel.innerHTML = `
    <form class="device-supplier-form" id="device-supplier-form" novalidate>
      <div class="form-title">${supplier ? '編輯設備供應商' : '新增設備供應商'}</div>
      <div class="form-subtitle">建立設備供應商資料，後續可在群組列表中修改。</div>
      <div class="form-row">
        <div class="form-group">
          <label>公司名稱 <span class="required">*</span></label>
          <input type="text" id="device-supplier-company-name" value="${escapeHtml(supplier?.company_name || '')}" />
        </div>
        <div class="form-group">
          <label>公司統一編號 <span class="required">*</span></label>
          <input type="text" id="device-supplier-tax-id" value="${escapeHtml(supplier?.tax_id || '')}" maxlength="8" inputmode="numeric" />
        </div>
      </div>
      <div class="form-group">
        <label>公司地址 <span class="required">*</span></label>
        <input type="text" id="device-supplier-company-address" value="${escapeHtml(supplier?.company_address || '')}" />
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>聯絡人 <span class="required">*</span></label>
          <input type="text" id="device-supplier-contact-name" value="${escapeHtml(supplier?.contact_name || '')}" />
        </div>
        <div class="form-group">
          <label>連絡電話 <span class="required">*</span></label>
          <input type="text" id="device-supplier-contact-phone" value="${escapeHtml(supplier?.contact_phone || '')}" />
        </div>
      </div>
      <div class="form-group">
        <label>備註 <span class="required">*</span></label>
        <input type="text" id="device-supplier-up-memo" value="${escapeHtml(supplier?.up_memo || '')}" maxlength="400" />
        <div class="field-hint">最多 400 字元。</div>
      </div>
      <div class="alert alert-success" id="device-supplier-success"></div>
      <div class="alert alert-danger" id="device-supplier-error"><span id="device-supplier-error-msg"></span></div>
      <div class="form-actions">
        <button type="button" class="btn btn-outline" id="device-supplier-back-list">返回列表</button>
        <button type="submit" class="btn btn-success">${supplier ? '儲存修改' : '儲存設備供應商'}</button>
      </div>
    </form>
  `;
}

function deviceSupplierPayload() {
  return {
    company_name: document.getElementById('device-supplier-company-name')?.value.trim() || '',
    tax_id: document.getElementById('device-supplier-tax-id')?.value.trim() || '',
    company_address: document.getElementById('device-supplier-company-address')?.value.trim() || '',
    contact_name: document.getElementById('device-supplier-contact-name')?.value.trim() || '',
    contact_phone: document.getElementById('device-supplier-contact-phone')?.value.trim() || '',
    up_memo: document.getElementById('device-supplier-up-memo')?.value.trim() || '',
  };
}

function showDeviceSupplierError(message) {
  document.getElementById('device-supplier-success')?.classList.remove('show');
  const msg = document.getElementById('device-supplier-error-msg');
  const box = document.getElementById('device-supplier-error');
  if (msg) msg.textContent = message;
  box?.classList.add('show');
}

function showDeviceSupplierSuccess(message) {
  document.getElementById('device-supplier-error')?.classList.remove('show');
  const box = document.getElementById('device-supplier-success');
  if (box) box.textContent = message;
  box?.classList.add('show');
}

async function saveDeviceSupplier() {
  const payload = deviceSupplierPayload();
  if (payload.up_memo.length > 400) {
    showDeviceSupplierError('備註最多 400 字元。');
    document.getElementById('device-supplier-up-memo')?.focus();
    return;
  }
  const url = editingDeviceSupplierId
    ? `${API}/admin/device-suppliers/${editingDeviceSupplierId}/update`
    : `${API}/admin/device-suppliers/create`;

  const data = await requestJson(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  showDeviceSupplierSuccess(data.message || '設備供應商已儲存。');
  await loadDeviceSuppliers();
  renderDeviceSupplierList();
}

async function showDeviceSupplierList() {
  const panel = document.getElementById('device-group-panel');
  if (panel) panel.innerHTML = '<div class="device-supplier-empty">資料載入中...</div>';
  await loadDeviceSuppliers();
  renderDeviceSupplierList();
}

function showStaffError(message) {
  document.getElementById('staff-success').classList.remove('show');
  document.getElementById('staff-error-msg').textContent = message;
  document.getElementById('staff-error').classList.add('show');
}

function showStaffSuccess(message) {
  document.getElementById('staff-error').classList.remove('show');
  document.getElementById('staff-success').textContent = message;
  document.getElementById('staff-success').classList.add('show');
}

function resetStaffForm() {
  document.getElementById('staff-form').reset();
  document.getElementById('staff-form-title').textContent = '新增管理人員';
  document.getElementById('staff-status-group').hidden = true;
  document.getElementById('staff-allowed-ips').value = '';
  document.getElementById('staff-error').classList.remove('show');
  document.getElementById('staff-success').classList.remove('show');
}

function showStaffList() {
  if (['#security-settings', '#group-settings', '#device-management', '#store-code-management', '#payment-upstream-management'].includes(window.location.hash)) {
    history.replaceState(null, '', `${APP_BASE}/admin/staff`);
  }
  document.getElementById('staff-add-panel').hidden = true;
  document.getElementById('staff-list-panel').hidden = false;
  document.getElementById('security-settings').hidden = true;
  document.getElementById('group-settings').hidden = true;
  document.getElementById('device-management').hidden = true;
  document.getElementById('store-code-management').hidden = true;
  document.getElementById('payment-upstream-management').hidden = true;
  document.getElementById('staff-show-accounts')?.classList.add('active');
  document.getElementById('staff-show-create')?.classList.remove('active');
  document.getElementById('staff-show-security')?.classList.remove('active');
  document.getElementById('staff-show-groups')?.classList.remove('active');
  document.getElementById('staff-show-devices')?.classList.remove('active');
  document.getElementById('staff-show-store-codes')?.classList.remove('active');
  document.getElementById('staff-show-payment-upstream')?.classList.remove('active');
  resetStaffForm();
}

function showStaffCreate() {
  if (window.location.search.includes('action=create')) {
    history.replaceState(null, '', `${APP_BASE}/admin/staff`);
  }
  document.getElementById('staff-list-panel').hidden = true;
  document.getElementById('security-settings').hidden = true;
  document.getElementById('group-settings').hidden = true;
  document.getElementById('device-management').hidden = true;
  document.getElementById('store-code-management').hidden = true;
  document.getElementById('payment-upstream-management').hidden = true;
  document.getElementById('staff-add-panel').hidden = false;
  document.getElementById('staff-show-accounts')?.classList.add('active');
  document.getElementById('staff-show-create')?.classList.add('active');
  document.getElementById('staff-show-security')?.classList.remove('active');
  document.getElementById('staff-show-groups')?.classList.remove('active');
  document.getElementById('staff-show-devices')?.classList.remove('active');
  document.getElementById('staff-show-store-codes')?.classList.remove('active');
  document.getElementById('staff-show-payment-upstream')?.classList.remove('active');
  resetStaffForm();
  document.getElementById('staff-name')?.focus();
}

function showSecuritySettings() {
  if (window.location.hash !== '#security-settings') {
    history.replaceState(null, '', `${APP_BASE}/admin/staff#security-settings`);
  }
  document.getElementById('staff-add-panel').hidden = true;
  document.getElementById('staff-list-panel').hidden = true;
  document.getElementById('security-settings').hidden = false;
  document.getElementById('group-settings').hidden = true;
  document.getElementById('device-management').hidden = true;
  document.getElementById('store-code-management').hidden = true;
  document.getElementById('payment-upstream-management').hidden = true;
  document.getElementById('staff-show-accounts')?.classList.remove('active');
  document.getElementById('staff-show-create')?.classList.remove('active');
  document.getElementById('staff-show-security')?.classList.add('active');
  document.getElementById('staff-show-groups')?.classList.remove('active');
  document.getElementById('staff-show-devices')?.classList.remove('active');
  document.getElementById('staff-show-store-codes')?.classList.remove('active');
  document.getElementById('staff-show-payment-upstream')?.classList.remove('active');
  resetStaffForm();
}

function showGroupSettings() {
  if (window.location.hash !== '#group-settings') {
    history.replaceState(null, '', `${APP_BASE}/admin/staff#group-settings`);
  }
  document.getElementById('staff-add-panel').hidden = true;
  document.getElementById('staff-list-panel').hidden = true;
  document.getElementById('security-settings').hidden = true;
  document.getElementById('group-settings').hidden = false;
  document.getElementById('device-management').hidden = true;
  document.getElementById('store-code-management').hidden = true;
  document.getElementById('payment-upstream-management').hidden = true;
  document.getElementById('staff-show-accounts')?.classList.remove('active');
  document.getElementById('staff-show-create')?.classList.remove('active');
  document.getElementById('staff-show-security')?.classList.add('active');
  document.getElementById('staff-show-groups')?.classList.add('active');
  document.getElementById('staff-show-devices')?.classList.remove('active');
  document.getElementById('staff-show-store-codes')?.classList.remove('active');
  document.getElementById('staff-show-payment-upstream')?.classList.remove('active');
  resetStaffForm();
}

function showDeviceManagement() {
  if (window.location.hash !== '#device-management') {
    history.replaceState(null, '', `${APP_BASE}/admin/staff#device-management`);
  }
  document.getElementById('staff-add-panel').hidden = true;
  document.getElementById('staff-list-panel').hidden = true;
  document.getElementById('security-settings').hidden = true;
  document.getElementById('group-settings').hidden = true;
  document.getElementById('device-management').hidden = false;
  document.getElementById('store-code-management').hidden = true;
  document.getElementById('payment-upstream-management').hidden = true;
  document.getElementById('staff-show-accounts')?.classList.remove('active');
  document.getElementById('staff-show-create')?.classList.remove('active');
  document.getElementById('staff-show-security')?.classList.remove('active');
  document.getElementById('staff-show-groups')?.classList.remove('active');
  document.getElementById('staff-show-devices')?.classList.add('active');
  document.getElementById('staff-show-store-codes')?.classList.remove('active');
  document.getElementById('staff-show-payment-upstream')?.classList.remove('active');
  resetStaffForm();
}

function showStoreCodeManagement() {
  if (window.location.hash !== '#store-code-management') {
    history.replaceState(null, '', `${APP_BASE}/admin/staff#store-code-management`);
  }
  document.getElementById('staff-add-panel').hidden = true;
  document.getElementById('staff-list-panel').hidden = true;
  document.getElementById('security-settings').hidden = true;
  document.getElementById('group-settings').hidden = true;
  document.getElementById('device-management').hidden = true;
  document.getElementById('store-code-management').hidden = false;
  document.getElementById('payment-upstream-management').hidden = true;
  document.getElementById('staff-show-accounts')?.classList.remove('active');
  document.getElementById('staff-show-create')?.classList.remove('active');
  document.getElementById('staff-show-security')?.classList.remove('active');
  document.getElementById('staff-show-groups')?.classList.remove('active');
  document.getElementById('staff-show-devices')?.classList.remove('active');
  document.getElementById('staff-show-store-codes')?.classList.add('active');
  document.getElementById('staff-show-payment-upstream')?.classList.remove('active');
  resetStaffForm();
  activateStoreCodeTab('list');
  showStoreCodeListTab().catch(() => {
    const panel = document.getElementById('store-code-panel');
    if (panel) {
      panel.innerHTML = '<div class="store-code-empty">經銷商資料載入失敗，請稍後再試。</div>';
    }
  });
}

function showPaymentUpstreamManagement() {
  if (window.location.hash !== '#payment-upstream-management') {
    history.replaceState(null, '', `${APP_BASE}/admin/staff#payment-upstream-management`);
  }
  document.getElementById('staff-add-panel').hidden = true;
  document.getElementById('staff-list-panel').hidden = true;
  document.getElementById('security-settings').hidden = true;
  document.getElementById('group-settings').hidden = true;
  document.getElementById('device-management').hidden = true;
  document.getElementById('store-code-management').hidden = true;
  document.getElementById('payment-upstream-management').hidden = false;
  document.getElementById('staff-show-accounts')?.classList.remove('active');
  document.getElementById('staff-show-create')?.classList.remove('active');
  document.getElementById('staff-show-security')?.classList.remove('active');
  document.getElementById('staff-show-groups')?.classList.remove('active');
  document.getElementById('staff-show-devices')?.classList.remove('active');
  document.getElementById('staff-show-store-codes')?.classList.remove('active');
  document.getElementById('staff-show-payment-upstream')?.classList.add('active');
  resetStaffForm();
}

function renderStaffTable() {
  const tbody = document.getElementById('staff-tbody');
  const count = document.getElementById('staff-count');
  if (!filteredStaffRows.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="empty-cell">沒有符合條件的管理人員</td></tr>';
    if (count) count.textContent = staffRows.length ? `顯示 0 / ${staffRows.length} 筆` : '尚無管理人員';
    return;
  }

  if (count) {
    count.textContent = staffKeyword ? `顯示 ${filteredStaffRows.length} / ${staffRows.length} 筆` : `共 ${staffRows.length} 筆`;
  }

  tbody.innerHTML = filteredStaffRows.map((staff) => {
    const isSelf = Number(staff.id) === Number(CURRENT_ADMIN_ID);
    const allowedIpCount = String(staff.allowed_ips || '').split(/\r?\n/).filter(Boolean).length;
    return `<tr>
      <td><span class="cell-main">${escapeHtml(staff.name)}</span><span class="muted">${escapeHtml(staff.email)}${isSelf ? '（目前登入）' : ''}</span></td>
      <td><span class="badge ${staff.role === 'super_admin' ? 'badge-personal' : 'badge-company'}">${escapeHtml(staffPermissionLabel(staff))}</span></td>
      <td><span class="badge ${statusClass(staff.status)}">${statusLabel(staff.status)}</span></td>
      <td>${allowedIpCount ? `<span class="badge badge-pending">限制 ${allowedIpCount} 筆 IP</span>` : '<span class="muted">不限 IP</span>'}</td>
      <td>${escapeHtml(staff.last_login_at || '—')}</td>
      <td class="action-cell">
        <button class="btn btn-sm btn-outline" onclick="editStaff(${Number(staff.id)})">編輯</button>
        <button class="btn btn-sm btn-outline" onclick="viewLoginLogs(${Number(staff.id)})">登入紀錄</button>
        ${isSelf ? '' : `<button class="btn btn-sm btn-danger" onclick="deleteStaff(${Number(staff.id)})">刪除</button>`}
      </td>
    </tr>`;
  }).join('');
}

function applyStaffSearch() {
  const keyword = staffKeyword.trim().toLowerCase();
  if (!keyword) {
    filteredStaffRows = [...staffRows];
    renderStaffTable();
    return;
  }

  filteredStaffRows = staffRows.filter((staff) => {
    const allowedIpCount = String(staff.allowed_ips || '').split(/\r?\n/).filter(Boolean).length;
    const values = [
      staff.name,
      staff.email,
      staffPermissionLabel(staff),
      statusLabel(staff.status),
      staff.status,
      staff.role,
      staff.permission_group,
      staff.allowed_ips,
      allowedIpCount ? `限制 ${allowedIpCount} 筆 IP` : '不限 IP',
      staff.last_login_at,
    ];
    return values.some((value) => String(value || '').toLowerCase().includes(keyword));
  });
  renderStaffTable();
}

async function loadStaff() {
  try {
    staffRows = await requestJson(`${API}/admin/staff`);
    applyStaffSearch();
  } catch (error) {
    document.getElementById('staff-tbody').innerHTML =
      `<tr><td colspan="6" class="empty-cell">${escapeHtml(error.message || '資料載入失敗。')}</td></tr>`;
  }
}

async function loadSecuritySettings() {
  try {
    const settings = await requestJson(`${API}/admin/settings/security`);
    document.getElementById('security-timeout-minutes').value = settings.admin_session_timeout_minutes || 30;
    securityAllowedIps = normalizeSecurityIpItems(settings.admin_allowed_ips);
    permissionGroups = normalizePermissionGroups(settings.admin_permission_groups);
    renderStaffRoleOptions();
    renderSecurityIpList();
    renderPermissionGroups();
  } catch (error) {
    document.getElementById('security-error-msg').textContent = error.message || '安全設定載入失敗。';
    document.getElementById('security-error').classList.add('show');
  }
}

function renderStaffRoleOptions() {
  const select = document.getElementById('staff-role');
  if (!select) return;
  const selected = select.value;
  select.innerHTML = '<option value="">請先選擇權限群組</option>';
  permissionGroups.forEach(group => {
    const option = document.createElement('option');
    option.value = group.name;
    option.textContent = group.name;
    select.appendChild(option);
  });
  select.value = permissionGroups.some(group => group.name === selected) ? selected : '';
}

function normalizePermissionGroups(groups) {
  if (!Array.isArray(groups)) return [];
  return groups
    .map(group => ({
      name: String(group?.name || '').trim(),
      permissions: Array.isArray(group?.permissions)
        ? group.permissions.filter(permission => PERMISSION_LABELS[permission])
        : [],
    }))
    .filter(group => group.name);
}

function normalizeSecurityIpItems(items) {
  if (!Array.isArray(items)) return [];
  return items
    .map(item => {
      if (typeof item === 'string') {
        return { ip: item.trim(), note: '' };
      }
      return {
        ip: String(item?.ip || '').trim(),
        note: String(item?.note || '').trim(),
      };
    })
    .filter(item => item.ip);
}

function editStaff(id) {
  window.location.href = `${APP_BASE}/admin/staff/${Number(id)}/edit`;
}

document.getElementById('staff-cancel')?.addEventListener('click', resetStaffForm);
document.getElementById('staff-cancel')?.addEventListener('click', showStaffList);
document.getElementById('staff-show-accounts')?.addEventListener('click', (event) => {
  event.preventDefault();
  showStaffList();
});
document.getElementById('staff-show-create')?.addEventListener('click', (event) => {
  event.preventDefault();
  showStaffCreate();
});
document.getElementById('staff-show-security')?.addEventListener('click', (event) => {
  event.preventDefault();
  showSecuritySettings();
});
document.getElementById('staff-show-groups')?.addEventListener('click', (event) => {
  event.preventDefault();
  showGroupSettings();
});
document.getElementById('staff-show-devices')?.addEventListener('click', (event) => {
  event.preventDefault();
  showDeviceManagement();
});
document.getElementById('staff-show-store-codes')?.addEventListener('click', (event) => {
  event.preventDefault();
  showStoreCodeManagement();
});
document.getElementById('staff-show-payment-upstream')?.addEventListener('click', (event) => {
  event.preventDefault();
  showPaymentUpstreamManagement();
});
document.querySelectorAll('[data-device-banner]').forEach((button) => {
  button.addEventListener('click', () => {
    const panel = document.querySelector('.device-panel-placeholder');
    const title = document.getElementById('device-panel-title');
    const copy = document.getElementById('device-panel-copy');
    const text = button.querySelector('strong')?.textContent || '設備管理';
    const subtitle = button.querySelector('span')?.textContent || '後續可在此接續開發完整表單與列表。';
    const isBlankPanel = button.dataset.deviceBanner === 'groups';

    document.querySelectorAll('[data-device-banner]').forEach((item) => {
      item.classList.toggle('active', item === button);
    });
    panel?.classList.toggle('device-panel-blank', isBlankPanel);
    if (title) title.textContent = isBlankPanel ? '' : text;
    if (copy) copy.textContent = isBlankPanel ? '' : subtitle;
    if (isBlankPanel) {
      document.querySelectorAll('[data-device-group-tab]').forEach((item) => {
        item.classList.toggle('active', item.dataset.deviceGroupTab === 'list');
      });
      showDeviceSupplierList().catch(() => {
        const groupPanel = document.getElementById('device-group-panel');
        if (groupPanel) groupPanel.innerHTML = '<div class="device-supplier-empty">設備供應商資料載入失敗，請稍後再試。</div>';
      });
    }
  });
});
document.querySelectorAll('[data-store-code-tab]').forEach((button) => {
  button.addEventListener('click', () => {
    activateStoreCodeTab(button.dataset.storeCodeTab);
    showStoreCodeListTab().catch(() => {
      const panel = document.getElementById('store-code-panel');
      if (panel) {
        panel.innerHTML = '<div class="store-code-empty">商店代號列表載入失敗，請稍後再試。</div>';
      }
    });
  });
});
document.getElementById('store-code-panel')?.addEventListener('click', (event) => {
  const childTab = event.target.closest('[data-store-code-child-tab]');
  if (childTab) {
    if (childTab.dataset.storeCodeChildTab === 'prefix') {
      showStoreCodePrefixTab().catch(() => {
        const panel = document.getElementById('store-code-panel');
        if (panel) panel.innerHTML = `${renderStoreCodeChildNav('prefix')}<div class="store-code-empty">經銷商資料載入失敗，請稍後再試。</div>`;
      });
    } else {
      showStoreCodeListTab().catch(() => {
        const panel = document.getElementById('store-code-panel');
        if (panel) panel.innerHTML = `${renderStoreCodeChildNav('list')}<div class="store-code-empty">商店代號列表載入失敗，請稍後再試。</div>`;
      });
    }
    return;
  }

  const dealerButton = event.target.closest('[data-store-code-dealer-id]');
  if (dealerButton) {
    renderStoreCodePrefixSettings(dealerButton.dataset.storeCodeDealerId).catch((error) => {
      const panel = document.getElementById('store-code-panel');
      if (panel) {
        panel.innerHTML = `<div class="store-code-empty">${escapeHtml(error.message || '前置碼設定載入失敗，請稍後再試。')}</div>`;
      }
    });
    return;
  }

  if (event.target.closest('#store-code-back-dealers') || event.target.closest('#store-code-prefix-back')) {
    renderStoreCodePrefixDealerList();
    return;
  }

  const editStoreCodePrefixButton = event.target.closest('[data-edit-store-code-prefix]');
  if (editStoreCodePrefixButton) {
    renderStoreCodePrefixWorkspace('edit', editStoreCodePrefixButton.dataset.editStoreCodePrefix);
    return;
  }

  const prefixModeButton = event.target.closest('[data-store-code-prefix-mode]');
  if (prefixModeButton) {
    renderStoreCodePrefixWorkspace(prefixModeButton.dataset.storeCodePrefixMode);
    return;
  }

  if (event.target.closest('#store-code-prefix-list-back')) {
    renderStoreCodePrefixWorkspace('list');
  }
});
document.getElementById('store-code-panel')?.addEventListener('input', (event) => {
  if (event.target.id === 'store-code-prefix-input') {
    event.target.value = event.target.value.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 4);
  }
});
document.getElementById('store-code-panel')?.addEventListener('submit', async (event) => {
  if (event.target.id !== 'store-code-prefix-form') return;
  event.preventDefault();
  try {
    await saveStoreCodePrefix(event.target);
    await renderStoreCodePrefixSettings(event.target.dataset.storeCodePrefixMemberId);
  } catch (error) {
    const message = error.details ? Object.values(error.details).join(' ') : error.message;
    showStoreCodePrefixError(message || '前置碼設定儲存失敗。');
  }
});
document.querySelectorAll('[data-device-group-tab]').forEach((button) => {
  button.addEventListener('click', () => {
    document.querySelectorAll('[data-device-group-tab]').forEach((item) => {
      item.classList.toggle('active', item === button);
    });
    if (button.dataset.deviceGroupTab === 'create') {
      renderDeviceSupplierForm();
    } else {
      showDeviceSupplierList().catch(() => {
        const panel = document.getElementById('device-group-panel');
        if (panel) panel.innerHTML = '<div class="device-supplier-empty">設備供應商資料載入失敗，請稍後再試。</div>';
      });
    }
  });
});
document.getElementById('device-group-panel')?.addEventListener('click', (event) => {
  const editButton = event.target.closest('[data-edit-device-supplier]');
  if (editButton) {
    const supplier = deviceSupplierRows.find((row) => Number(row.id) === Number(editButton.dataset.editDeviceSupplier));
    if (supplier) {
      document.querySelectorAll('[data-device-group-tab]').forEach((item) => {
        item.classList.toggle('active', item.dataset.deviceGroupTab === 'create');
      });
      renderDeviceSupplierForm(supplier);
    }
    return;
  }

  if (event.target.closest('#device-supplier-back-list')) {
    document.querySelectorAll('[data-device-group-tab]').forEach((item) => {
      item.classList.toggle('active', item.dataset.deviceGroupTab === 'list');
    });
    renderDeviceSupplierList();
  }
});
document.getElementById('device-group-panel')?.addEventListener('submit', async (event) => {
  if (event.target.id !== 'device-supplier-form') return;
  event.preventDefault();
  try {
    await saveDeviceSupplier();
    document.querySelectorAll('[data-device-group-tab]').forEach((item) => {
      item.classList.toggle('active', item.dataset.deviceGroupTab === 'list');
    });
  } catch (error) {
    const message = error.details ? Object.values(error.details).join(' ') : error.message;
    showDeviceSupplierError(message || '設備供應商儲存失敗。');
  }
});
document.getElementById('staff-search')?.addEventListener('input', (event) => {
  staffKeyword = event.target.value || '';
  applyStaffSearch();
});
document.getElementById('staff-search-clear')?.addEventListener('click', () => {
  const input = document.getElementById('staff-search');
  if (!input) return;
  input.value = '';
  staffKeyword = '';
  applyStaffSearch();
  input.focus();
});
document.getElementById('security-ip-add')?.addEventListener('click', addSecurityIp);
document.getElementById('security-ip-input')?.addEventListener('keydown', (event) => {
  if (event.key === 'Enter') {
    event.preventDefault();
    addSecurityIp();
  }
});
document.getElementById('security-ip-list')?.addEventListener('click', (event) => {
  const button = event.target.closest('[data-remove-security-ip]');
  if (!button) return;
  const index = Number(button.dataset.removeSecurityIp);
  securityAllowedIps = securityAllowedIps.filter((_, itemIndex) => itemIndex !== index);
  renderSecurityIpList();
});
document.getElementById('group-add')?.addEventListener('click', addPermissionGroup);
document.getElementById('group-cancel-edit')?.addEventListener('click', clearGroupForm);
document.getElementById('group-name-input')?.addEventListener('keydown', (event) => {
  if (event.key === 'Enter') {
    event.preventDefault();
    addPermissionGroup();
  }
});
document.getElementById('group-list')?.addEventListener('click', (event) => {
  const editButton = event.target.closest('[data-edit-group]');
  if (editButton) {
    editPermissionGroup(Number(editButton.dataset.editGroup));
    return;
  }

  const button = event.target.closest('[data-remove-group]');
  if (!button) return;
  const index = Number(button.dataset.removeGroup);
  permissionGroups = permissionGroups.filter((_, itemIndex) => itemIndex !== index);
  if (editingGroupIndex === index) {
    clearGroupForm();
  } else if (editingGroupIndex !== null && index < editingGroupIndex) {
    editingGroupIndex -= 1;
  }
  renderPermissionGroups();
});

document.getElementById('staff-form').addEventListener('submit', async (event) => {
  event.preventDefault();

  const payload = {
    name: document.getElementById('staff-name').value.trim(),
    email: document.getElementById('staff-email').value.trim(),
    permission_group: document.getElementById('staff-role').value,
    allowed_ips: document.getElementById('staff-allowed-ips').value,
  };

  try {
    const data = await requestJson(`${API}/admin/staff/create`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    showStaffSuccess(data.setup_url
      ? `管理人員已新增，請使用信箱啟用連結完成密碼設定。開發測試連結：${data.setup_url}`
      : '管理人員已新增，系統已寄出信箱認證與設定密碼連結。');
    await loadStaff();
    if (!data.setup_url) {
      window.setTimeout(showStaffList, 450);
    }
  } catch (error) {
    const message = error.details ? Object.values(error.details).join('、') : error.message;
    showStaffError(message || '儲存失敗。');
  }
});

document.getElementById('security-settings-form').addEventListener('submit', async (event) => {
  event.preventDefault();
  document.getElementById('security-success').classList.remove('show');
  document.getElementById('security-error').classList.remove('show');

  try {
    await requestJson(`${API}/admin/settings/security`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        admin_session_timeout_minutes: Number(document.getElementById('security-timeout-minutes').value),
        admin_allowed_ips: securityAllowedIps,
        admin_permission_groups: permissionGroups,
      }),
    });
    document.getElementById('security-success').classList.add('show');
  } catch (error) {
    document.getElementById('security-error-msg').textContent = error.message || '安全設定更新失敗。';
    document.getElementById('security-error').classList.add('show');
  }
});

document.getElementById('group-settings-form')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  document.getElementById('group-success').classList.remove('show');
  document.getElementById('group-error').classList.remove('show');

  try {
    await requestJson(`${API}/admin/settings/security`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        admin_session_timeout_minutes: Number(document.getElementById('security-timeout-minutes').value),
        admin_allowed_ips: securityAllowedIps,
        admin_permission_groups: permissionGroups,
      }),
    });
    document.getElementById('group-success').classList.add('show');
  } catch (error) {
    document.getElementById('group-error-msg').textContent = error.message || '群組設定更新失敗。';
    document.getElementById('group-error').classList.add('show');
  }
});

async function deleteStaff(id) {
  if (!confirm('確定要刪除這位管理人員嗎？')) return;

  try {
    await requestJson(`${API}/admin/staff/${id}/delete`, { method: 'POST' });
    await loadStaff();
  } catch (error) {
    alert(error.message || '刪除失敗。');
  }
}

async function viewLoginLogs(id) {
  const staff = staffRows.find((row) => Number(row.id) === Number(id));
  const modal = document.getElementById('staff-login-log-modal');
  const tbody = document.getElementById('staff-login-log-tbody');
  const title = document.getElementById('staff-login-log-title');
  if (!modal || !tbody || !title) return;

  title.textContent = `${staff ? staff.name : '管理人員'} - 登入紀錄`;
  tbody.innerHTML = '<tr><td colspan="4" class="empty-cell">資料載入中...</td></tr>';
  modal.classList.add('show');
  modal.setAttribute('aria-hidden', 'false');

  try {
    const logs = await requestJson(`${API}/admin/staff/${id}/login-logs`);
    if (!logs.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="empty-cell">尚無登入紀錄</td></tr>';
      return;
    }

    tbody.innerHTML = logs.map((log) => `<tr>
      <td>${escapeHtml(log.login_at || '—')}</td>
      <td>${escapeHtml(log.logout_at || '—')}</td>
      <td>${escapeHtml(formatDuration(log.usage_seconds ?? log.duration_seconds))}</td>
      <td class="mono-cell">${escapeHtml(log.ip_address || '—')}</td>
    </tr>`).join('');
  } catch (error) {
    tbody.innerHTML = `<tr><td colspan="4" class="empty-cell">${escapeHtml(error.message || '登入紀錄載入失敗。')}</td></tr>`;
  }
}

function formatDuration(value) {
  const seconds = Number(value);
  if (!Number.isFinite(seconds) || seconds < 0) return '—';
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = Math.floor(seconds % 60);
  if (hours > 0) {
    return `${hours}時 ${minutes}分 ${secs}秒`;
  }
  if (minutes > 0) {
    return `${minutes}分 ${secs}秒`;
  }
  return `${secs}秒`;
}

function closeLoginLogModal() {
  const modal = document.getElementById('staff-login-log-modal');
  if (!modal) return;
  modal.classList.remove('show');
  modal.setAttribute('aria-hidden', 'true');
}

document.getElementById('staff-login-log-close')?.addEventListener('click', closeLoginLogModal);
document.getElementById('staff-login-log-close-footer')?.addEventListener('click', closeLoginLogModal);
document.getElementById('staff-login-log-modal')?.addEventListener('click', (event) => {
  if (event.target.id === 'staff-login-log-modal') {
    closeLoginLogModal();
  }
});
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    closeLoginLogModal();
  }
});

async function logoutAdmin() {
  try {
    await requestJson(`${API}/admin/logout`, { method: 'POST' });
  } finally {
    window.location.href = `${APP_BASE}/admin/login`;
  }
}

async function initStaffPage() {
  await Promise.all([loadStaff(), loadSecuritySettings()]);
  if (window.location.hash === '#group-settings') {
    showGroupSettings();
  } else if (window.location.hash === '#store-code-management') {
    showStoreCodeManagement();
  } else if (window.location.hash === '#payment-upstream-management') {
    showPaymentUpstreamManagement();
  } else if (window.location.hash === '#device-management') {
    showDeviceManagement();
  } else if (window.location.hash === '#security-settings') {
    showSecuritySettings();
  } else if (new URLSearchParams(window.location.search).get('action') === 'create') {
    showStaffCreate();
  } else {
    showStaffList();
  }
}

initStaffPage();
