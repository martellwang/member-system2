// admin.js — 後台管理前端邏輯
const BASE_PATH = (() => {
  const marker = '/public';
  const index = window.location.pathname.indexOf(marker);
  return index >= 0 ? window.location.pathname.slice(0, index + marker.length) : '';
})();
const API = `${BASE_PATH}/api`;
const PER_PAGE = 10;
let allMembers = [];
let filtered   = [];
let currentPage = 1;
let currentFilter = 'all';

async function loadMembers() {
  try {
    const res  = await fetch(`${API}/admin/members`, { headers: { 'Accept': 'application/json' } });
    allMembers = await res.json();
    applyFilter(currentFilter);
    updateStats();
  } catch {
    // 開發期 mock 資料
    allMembers = [
      { id:1, name:'王小明', type:'personal', id_number:'A123456789', email:'ming@mail.com', phone:'0912-111-222', company_name:'', website:'', status:'active' },
      { id:2, name:'林美華', type:'personal', id_number:'B234567890', email:'hua@mail.com',  phone:'0922-333-444', company_name:'', website:'', status:'active' },
      { id:3, name:'張志豪', type:'personal', id_number:'C345678901', email:'hao@mail.com',  phone:'0933-555-666', company_name:'', website:'', status:'pending' },
      { id:4, name:'陳大文', type:'company',  id_number:'12345678',   email:'admin@techco.com', phone:'02-1234-5678', company_name:'科技股份有限公司',  website:'https://techco.com', status:'active' },
      { id:5, name:'劉資訊', type:'company',  id_number:'87654321',   email:'info@infosoft.com',phone:'02-8765-4321', company_name:'資訊軟體有限公司',  website:'https://infosoft.com.tw', status:'pending' },
      { id:6, name:'黃貿易', type:'company',  id_number:'11223344',   email:'biz@trade.com',   phone:'04-9876-5432', company_name:'全球貿易企業社',      website:'https://globaltrade.tw', status:'active' },
    ];
    applyFilter(currentFilter);
    updateStats();
  }
}

function updateStats() {
  const personal = allMembers.filter(m => m.type === 'personal').length;
  const company  = allMembers.filter(m => m.type === 'company').length;
  const pending  = allMembers.filter(m => m.status === 'pending').length;
  const total    = allMembers.length;
  document.getElementById('stat-total').textContent    = total;
  document.getElementById('stat-personal').textContent = personal;
  document.getElementById('stat-company').textContent  = company;
  document.getElementById('stat-pending').textContent  = pending;
  document.getElementById('stat-personal-pct').textContent = total ? Math.round(personal/total*100)+'%' : '—';
  document.getElementById('stat-company-pct').textContent  = total ? Math.round(company/total*100)+'%'  : '—';
}

function applyFilter(type) {
  currentFilter = type;
  currentPage = 1;
  if (type === 'all')     filtered = [...allMembers];
  else if (type === 'pending') filtered = allMembers.filter(m => m.status === 'pending');
  else                    filtered = allMembers.filter(m => m.type === type);
  renderTable();
}

function filter(type, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  applyFilter(type);
}

function search(q) {
  const kw = q.trim().toLowerCase();
  currentPage = 1;
  if (!kw) { applyFilter(currentFilter); return; }
  const base = currentFilter === 'all' ? allMembers :
               currentFilter === 'pending' ? allMembers.filter(m => m.status === 'pending') :
               allMembers.filter(m => m.type === currentFilter);
  filtered = base.filter(m =>
    m.name.toLowerCase().includes(kw) ||
    m.email.toLowerCase().includes(kw) ||
    (m.company_name||'').toLowerCase().includes(kw) ||
    (m.id_number||'').toLowerCase().includes(kw)
  );
  renderTable();
}

function renderTable() {
  const start = (currentPage - 1) * PER_PAGE;
  const page  = filtered.slice(start, start + PER_PAGE);
  const tbody = document.getElementById('member-tbody');

  if (!page.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:32px;color:#888">沒有符合的會員</td></tr>';
  } else {
    tbody.innerHTML = page.map(m => {
      const isP   = m.type === 'personal';
      const init  = m.name.slice(0,2);
      const idLabel = isP ? '身分證：' : '統編：';
      const compInfo = isP ? '—' :
        `<span style="display:block;font-weight:500">${m.company_name}</span>
         <a href="${m.website}" target="_blank" style="font-size:11px">${m.website||'—'}</a>`;
      return `<tr>
        <td><span class="avatar ${isP?'av-p':'av-c'}">${init}</span>${m.name}</td>
        <td><span class="badge ${isP?'badge-personal':'badge-company'}">${isP?'👤 個人':'🏢 公司'}</span></td>
        <td style="font-family:monospace;font-size:12px;color:#555">${idLabel}${m.id_number}</td>
        <td><span style="display:block">${m.email}</span><span style="font-size:11px;color:#888">${m.phone||'—'}</span></td>
        <td style="font-size:12px">${compInfo}</td>
        <td><span class="badge ${m.status==='active'?'badge-active':'badge-pending'}">${m.status==='active'?'✅ 啟用':'⏳ 待審'}</span></td>
        <td>
          <button class="btn btn-sm btn-outline" onclick="editMember(${m.id})">編輯</button>
          ${m.status==='pending'?`<button class="btn btn-sm btn-success" style="margin-left:4px" onclick="approve(${m.id})">審核</button>`:''}
        </td>
      </tr>`;
    }).join('');
  }

  // pagination
  const total = filtered.length;
  const pages = Math.ceil(total / PER_PAGE);
  document.getElementById('page-info').textContent =
    `顯示 ${Math.min(start+1, total)}–${Math.min(start+PER_PAGE, total)}，共 ${total} 筆`;

  const btns = document.getElementById('page-btns');
  btns.innerHTML = Array.from({length: pages}, (_, i) =>
    `<div class="page-num ${i+1===currentPage?'cur':''}" onclick="goPage(${i+1})">${i+1}</div>`
  ).join('');
}

function goPage(n) { currentPage = n; renderTable(); }

async function approve(id) {
  try {
    await fetch(`${API}/admin/members/${id}/approve`, { method: 'PATCH', headers: {'Accept':'application/json'} });
  } catch {}
  const m = allMembers.find(x => x.id === id);
  if (m) { m.status = 'active'; applyFilter(currentFilter); updateStats(); }
}

function editMember(id) {
  const m = allMembers.find(x => x.id === id);
  alert(`編輯會員：${m.name}\n（實際系統會開啟編輯 Modal）`);
}

loadMembers();
