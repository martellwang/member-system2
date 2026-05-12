<div class="container-wide">
  <div class="admin-header">
    <h1>後台管理</h1>
    <span class="admin-info">管理員：admin@system.com</span>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">總會員數</div>
      <div class="stat-value" id="stat-total">—</div>
      <div class="stat-sub">↑ 本週新增 2</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">個人用戶</div>
      <div class="stat-value" id="stat-personal">—</div>
      <div class="stat-sub" id="stat-personal-pct">—</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">商業公司</div>
      <div class="stat-value" id="stat-company">—</div>
      <div class="stat-sub" id="stat-company-pct">—</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">待審核</div>
      <div class="stat-value" id="stat-pending">—</div>
      <div class="stat-sub" style="color:#BA7517;">需要處理</div>
    </div>
  </div>

  <div class="table-card">
    <div class="table-toolbar">
      <button class="filter-btn active" onclick="filter('all', this)">全部</button>
      <button class="filter-btn" onclick="filter('personal', this)">個人用戶</button>
      <button class="filter-btn" onclick="filter('company', this)">商業公司</button>
      <button class="filter-btn" onclick="filter('pending', this)">待審核</button>
      <input type="text" class="search-box" placeholder="搜尋會員..." oninput="search(this.value)" />
    </div>

    <table>
      <thead>
        <tr>
          <th>會員</th>
          <th>類型</th>
          <th>身份證號 / 統編</th>
          <th>聯絡資料</th>
          <th>公司 / 網址</th>
          <th>狀態</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody id="member-tbody">
        <tr><td colspan="7" style="text-align:center;padding:32px;color:#888;">資料載入中...</td></tr>
      </tbody>
    </table>

    <div class="pagination">
      <span id="page-info">載入中...</span>
      <div class="page-btns" id="page-btns"></div>
    </div>
  </div>
</div>

<script src="/assets/js/admin.js"></script>
