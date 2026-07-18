// admin-staff.js - 後台內部管理人員
const APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
const API = `${APP_BASE}/api`;

let staffRows = [];
let filteredStaffRows = [];
let staffKeyword = '';
let securityAllowedIps = [];
let permissionGroups = [];
let editingGroupIndex = null;
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
  if (['#security-settings', '#group-settings', '#device-management'].includes(window.location.hash)) {
    history.replaceState(null, '', `${APP_BASE}/admin/staff`);
  }
  document.getElementById('staff-add-panel').hidden = true;
  document.getElementById('staff-list-panel').hidden = false;
  document.getElementById('security-settings').hidden = true;
  document.getElementById('group-settings').hidden = true;
  document.getElementById('device-management').hidden = true;
  document.getElementById('staff-show-accounts')?.classList.add('active');
  document.getElementById('staff-show-create')?.classList.remove('active');
  document.getElementById('staff-show-security')?.classList.remove('active');
  document.getElementById('staff-show-groups')?.classList.remove('active');
  document.getElementById('staff-show-devices')?.classList.remove('active');
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
  document.getElementById('staff-add-panel').hidden = false;
  document.getElementById('staff-show-accounts')?.classList.add('active');
  document.getElementById('staff-show-create')?.classList.add('active');
  document.getElementById('staff-show-security')?.classList.remove('active');
  document.getElementById('staff-show-groups')?.classList.remove('active');
  document.getElementById('staff-show-devices')?.classList.remove('active');
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
  document.getElementById('staff-show-accounts')?.classList.remove('active');
  document.getElementById('staff-show-create')?.classList.remove('active');
  document.getElementById('staff-show-security')?.classList.add('active');
  document.getElementById('staff-show-groups')?.classList.remove('active');
  document.getElementById('staff-show-devices')?.classList.remove('active');
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
  document.getElementById('staff-show-accounts')?.classList.remove('active');
  document.getElementById('staff-show-create')?.classList.remove('active');
  document.getElementById('staff-show-security')?.classList.add('active');
  document.getElementById('staff-show-groups')?.classList.add('active');
  document.getElementById('staff-show-devices')?.classList.remove('active');
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
  document.getElementById('staff-show-accounts')?.classList.remove('active');
  document.getElementById('staff-show-create')?.classList.remove('active');
  document.getElementById('staff-show-security')?.classList.remove('active');
  document.getElementById('staff-show-groups')?.classList.remove('active');
  document.getElementById('staff-show-devices')?.classList.add('active');
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
document.querySelectorAll('[data-device-banner]').forEach((button) => {
  button.addEventListener('click', () => {
    const title = document.getElementById('device-panel-title');
    const copy = document.getElementById('device-panel-copy');
    const text = button.querySelector('strong')?.textContent || '設備管理';
    const subtitle = button.querySelector('span')?.textContent || '後續可在此接續開發完整表單與列表。';

    document.querySelectorAll('[data-device-banner]').forEach((item) => {
      item.classList.toggle('active', item === button);
    });
    if (title) title.textContent = text;
    if (copy) copy.textContent = subtitle;
  });
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
