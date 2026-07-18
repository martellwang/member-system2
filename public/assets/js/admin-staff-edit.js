// admin-staff-edit.js - 單頁編輯內部管理人員
const APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
const API = `${APP_BASE}/api`;
let permissionGroups = [];

function showEditError(message) {
  document.getElementById('staff-edit-success').classList.remove('show');
  document.getElementById('staff-edit-error-msg').textContent = message;
  document.getElementById('staff-edit-error').classList.add('show');
}

function showEditSuccess() {
  document.getElementById('staff-edit-error').classList.remove('show');
  document.getElementById('staff-edit-success').classList.add('show');
}

async function logoutAdmin() {
  try {
    await fetch(`${API}/admin/logout`, {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
    });
  } finally {
    window.location.href = `${APP_BASE}/admin/login`;
  }
}

async function requestJson(url, options = {}) {
  const res = await fetch(url, {
    headers: { 'Accept': 'application/json', ...(options.headers || {}) },
    ...options,
  });
  const data = await res.json().catch(() => ({}));
  if (res.status === 401 || res.status === 409) {
    window.location.href = `${APP_BASE}/admin/login`;
    throw new Error('請重新登入管理後台。');
  }
  if (!res.ok) {
    throw new Error(data.message || '操作失敗。');
  }
  return data;
}

function normalizePermissionGroups(groups) {
  if (!Array.isArray(groups)) return [];
  return groups
    .map(group => ({
      name: String(group?.name || '').trim(),
      permissions: Array.isArray(group?.permissions) ? group.permissions : [],
    }))
    .filter(group => group.name);
}

async function loadPermissionGroups() {
  const select = document.getElementById('staff-edit-role');
  if (!select) return;
  try {
    const settings = await requestJson(`${API}/admin/settings/security`);
    permissionGroups = normalizePermissionGroups(settings.admin_permission_groups);
    const currentGroup = select.dataset.currentGroup || '';
    select.innerHTML = '<option value="">請先選擇權限群組</option>';
    permissionGroups.forEach(group => {
      const option = document.createElement('option');
      option.value = group.name;
      option.textContent = group.name;
      select.appendChild(option);
    });
    select.value = permissionGroups.some(group => group.name === currentGroup) ? currentGroup : '';
  } catch (error) {
    showEditError(error.message || '權限群組載入失敗。');
  }
}

document.getElementById('staff-edit-page-form')?.addEventListener('submit', async (event) => {
  event.preventDefault();

  const id = document.getElementById('staff-edit-id').value;
  const payload = {
    name: document.getElementById('staff-edit-name').value.trim(),
    email: document.getElementById('staff-edit-email').value.trim(),
    permission_group: document.getElementById('staff-edit-role').value,
    status: document.getElementById('staff-edit-status').value,
    password: document.getElementById('staff-edit-password').value,
    allowed_ips: document.getElementById('staff-edit-allowed-ips').value,
  };

  try {
    const res = await fetch(`${API}/admin/staff/${id}/update`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));

    if (res.status === 401 || res.status === 409) {
      window.location.href = `${APP_BASE}/admin/login`;
      return;
    }
    if (!res.ok) {
      const message = data.errors ? Object.values(data.errors).join('、') : (data.message || '更新失敗。');
      showEditError(message);
      return;
    }

    showEditSuccess();
    window.setTimeout(() => {
      window.location.href = `${APP_BASE}/admin/staff`;
    }, 500);
  } catch (error) {
    showEditError('無法連線到伺服器。');
  }
});

loadPermissionGroups();
