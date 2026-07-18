// admin.js — 後台管理前端邏輯
const APP_BASE = document.querySelector('meta[name="app-base"]')?.content || '';
const API = `${APP_BASE}/api`;
const PER_PAGE = 10;
const MODULE = typeof ADMIN_MEMBER_MODULE === 'string' ? ADMIN_MEMBER_MODULE : 'members';
const DEALER_ONLY = MODULE === 'dealers';

let allMembers = [];
let filtered = [];
let currentPage = 1;
let currentFilter = 'all';
let currentKeyword = '';

function formatPhone(areaCode, phone) {
  if (!phone) return '—';
  return areaCode ? `${areaCode}-${phone}` : phone;
}

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

async function loadMembers() {
  try {
    const members = await requestJson(`${API}/admin/members`);
    allMembers = DEALER_ONLY ? members.filter(member => Number(member.is_dealer) === 1) : members;
    applyFilter(currentFilter, false);
    updateStats();
  } catch (error) {
    showTableMessage(error.message || '資料載入失敗。');
  }
}

function updateStats() {
  const personal = allMembers.filter(m => m.type === 'personal').length;
  const company = allMembers.filter(m => m.type === 'company').length;
  const pending = allMembers.filter(m => m.status === 'pending').length;
  const suspended = allMembers.filter(m => m.status === 'suspended').length;
  const total = allMembers.length;

  document.getElementById('stat-total').textContent = total;
  document.getElementById('stat-personal').textContent = personal;
  document.getElementById('stat-company').textContent = company;
  document.getElementById('stat-pending').textContent = pending;
  document.getElementById('stat-suspended').textContent = suspended;
  document.getElementById('stat-personal-pct').textContent = total ? `${Math.round(personal / total * 100)}%` : '—';
  document.getElementById('stat-company-pct').textContent = total ? `${Math.round(company / total * 100)}%` : '—';
}

function applyFilter(type, resetPage = true) {
  currentFilter = type;
  if (resetPage) currentPage = 1;

  const byFilter = type === 'all' ? allMembers :
    ['email_unverified', 'pending', 'suspended'].includes(type) ? allMembers.filter(m => m.status === type) :
    allMembers.filter(m => m.type === type);

  const kw = currentKeyword.trim().toLowerCase();
  filtered = kw ? byFilter.filter(m => memberSearchText(m).includes(kw)) : [...byFilter];

  renderTable();
}

function filter(type, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  applyFilter(type);
}

function search(q) {
  currentKeyword = q;
  applyFilter(currentFilter);
}

function clearMemberSearch() {
  const input = document.getElementById('member-search');
  if (!input) return;
  input.value = '';
  currentKeyword = '';
  applyFilter(currentFilter);
  input.focus();
}

function showTableMessage(message) {
  document.getElementById('member-tbody').innerHTML =
    `<tr><td colspan="9" class="empty-cell">${escapeHtml(message)}</td></tr>`;
  document.getElementById('page-info').textContent = '—';
  document.getElementById('page-btns').innerHTML = '';
}

function renderTable() {
  const start = (currentPage - 1) * PER_PAGE;
  const page = filtered.slice(start, start + PER_PAGE);
  const tbody = document.getElementById('member-tbody');
  const duplicateIdNumbers = getDuplicateIdNumbers();

  if (!page.length) {
    showTableMessage(DEALER_ONLY ? '沒有符合的經銷商會員' : '沒有符合的會員');
    return;
  }

  tbody.innerHTML = page.map((m, index) => {
    const isPersonal = m.type === 'personal';
    const init = escapeHtml((m.name || '?').slice(0, 2));
    const idValue = isPersonal ? m.id_number : m.tax_id;
    const idLabel = isPersonal ? '身分證：' : '統編：';
    const duplicateWarning = isPersonal && idValue && duplicateIdNumbers.has(idValue)
      ? '<span class="duplicate-warning">身分證號重複</span>'
      : '';
    const lineInfo = isPersonal
      ? `<span class="muted">LINE：${m.line_id ? escapeHtml(m.line_id) : '—'}</span>
         ${m.line_id ? `<a class="small-link line-friend-link" href="${lineFriendUrl(m.line_id)}">加入 LINE 好友</a>` : ''}`
      : '';
    const companyInfo = isPersonal ? '—' :
      `<span class="company-name">${escapeHtml(m.company_name || '—')}</span>
       ${Number(m.is_dealer) === 1 ? '<span class="dealer-badge">經銷商</span>' : ''}
       ${m.website ? `<a href="${escapeAttr(m.website)}" target="_blank" rel="noopener" class="small-link">${escapeHtml(m.website)}</a>` : '<span class="muted">—</span>'}`;

    const statusMeta = getStatusMeta(m.status);
    const storeCount = Number(m.store_count || 0);
    const storeCountCell = storeCount > 0
      ? `<a class="store-count-link" href="${APP_BASE}/admin/members/${Number(m.id)}/edit#admin-store-management" title="查看此會員商店列表">${storeCount}</a>`
      : '<span class="store-count-empty">0</span>';

    const statusAction = m.status === 'email_unverified'
      ? '<span class="muted">等待驗證</span>'
      : m.status !== 'active'
        ? `<button class="btn btn-sm btn-success" onclick="approve(${m.id})">審核</button>`
        : `<button class="btn btn-sm btn-outline" onclick="suspendMember(${m.id})">停用</button>`;

    return `<tr>
      <td class="sequence-cell">${start + index + 1}</td>
      <td><span class="avatar ${isPersonal ? 'av-p' : 'av-c'}">${init}</span>${escapeHtml(m.name)}<span class="member-code">${escapeHtml(m.member_code || '—')}</span></td>
      <td><span class="badge ${isPersonal ? 'badge-personal' : 'badge-company'}">${isPersonal ? '個人' : '公司'}</span></td>
      <td class="mono-cell">${idLabel}${escapeHtml(idValue || '—')}${duplicateWarning}</td>
      <td><span class="cell-main">${escapeHtml(m.email)}</span><span class="muted">手機：${escapeHtml(m.mobile_phone || '—')}</span><span class="muted">電話：${escapeHtml(formatPhone(m.phone_area_code, m.phone))}</span>${lineInfo}</td>
      <td>${companyInfo}</td>
      <td class="store-count-cell">${storeCountCell}</td>
      <td><span class="badge ${statusMeta.className}">${statusMeta.label}</span></td>
      <td class="action-cell">
        <button class="btn btn-sm btn-outline" onclick="editMember(${m.id})">編輯</button>
        ${statusAction}
        <button class="btn btn-sm btn-danger" onclick="deleteMember(${m.id})">刪除</button>
      </td>
    </tr>`;
  }).join('');

  const total = filtered.length;
  const pages = Math.ceil(total / PER_PAGE);
  document.getElementById('page-info').textContent =
    `顯示 ${Math.min(start + 1, total)}–${Math.min(start + PER_PAGE, total)}，共 ${total} 筆`;

  document.getElementById('page-btns').innerHTML = Array.from({ length: pages }, (_, i) =>
    `<button type="button" class="page-num ${i + 1 === currentPage ? 'cur' : ''}" onclick="goPage(${i + 1})">${i + 1}</button>`
  ).join('');
}

function memberSearchText(member) {
  const isPersonal = member.type === 'personal';
  const values = Object.values(member).map(value => String(value ?? ''));
  values.push(isPersonal ? '個人 個人用戶' : '公司 商業公司 法人 商業會員');
  values.push(getStatusMeta(member.status).label);
  values.push(Number(member.is_dealer) === 1 ? '經銷商' : '非經銷商');
  values.push(Number(member.store_count || 0) > 0 ? `有商店 商店數 ${member.store_count}` : '無商店 商店數 0');
  values.push(formatPhone(member.phone_area_code, member.phone));
  return values.join(' ').toLowerCase();
}

function getDuplicateIdNumbers() {
  const counts = new Map();
  allMembers
    .filter(m => m.type === 'personal' && m.id_number)
    .forEach(m => counts.set(m.id_number, (counts.get(m.id_number) || 0) + 1));

  return new Set([...counts.entries()].filter(([, count]) => count > 1).map(([idNumber]) => idNumber));
}

function lineFriendUrl(lineId) {
  return `line://ti/p/~${encodeURIComponent(lineId)}`;
}

function getStatusMeta(status) {
  if (status === 'active') return { label: '啟用', className: 'badge-active' };
  if (status === 'suspended') return { label: '停用', className: 'badge-suspended' };
  if (status === 'email_unverified') return { label: '未驗證信箱', className: 'badge-suspended' };
  return { label: '待審', className: 'badge-pending' };
}

function goPage(n) {
  currentPage = n;
  renderTable();
}

async function approve(id) {
  await runMemberAction(`${API}/admin/members/${id}/approve`, '已審核通過。');
}

async function suspendMember(id) {
  await runMemberAction(`${API}/admin/members/${id}/suspend`, '已停用帳號。');
}

async function deleteMember(id) {
  if (!confirm('確定要刪除這位會員嗎？此操作無法復原。')) return;
  await runMemberAction(`${API}/admin/members/${id}/delete`, '已刪除會員。');
}

async function runMemberAction(url, successMessage) {
  try {
    await requestJson(url, { method: 'POST' });
    await loadMembers();
    setNotice(successMessage);
  } catch (error) {
    alert(error.message || '操作失敗。');
  }
}

function editMember(id) {
  window.location.href = `${APP_BASE}/admin/members/${id}/edit`;
}

async function logoutAdmin() {
  try {
    await requestJson(`${API}/admin/logout`, { method: 'POST' });
  } finally {
    window.location.href = `${APP_BASE}/admin/login`;
  }
}

function setNotice(message) {
  document.getElementById('page-info').textContent = message;
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

function escapeAttr(value) {
  return escapeHtml(value).replace(/`/g, '&#096;');
}

loadMembers();
