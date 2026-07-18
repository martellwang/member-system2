<?php
$isDealerModule = ($_GET['module'] ?? '') === 'dealers';
$moduleTitle = $isDealerModule ? '經銷商管理' : '會員管理';
?>
<div class="container-wide">
  <div class="admin-header">
    <h1>後台管理</h1>
    <div class="admin-actions">
      <span class="admin-info">管理員：<?= htmlspecialchars($_SESSION['admin']['email'] ?? ADMIN_EMAIL) ?></span>
      <?php if (($_SESSION['admin']['role'] ?? '') === 'super_admin'): ?>
        <a class="btn btn-sm btn-outline" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin/staff">管理人員</a>
      <?php endif; ?>
      <button class="btn btn-sm btn-outline" onclick="logoutAdmin()">登出</button>
    </div>
  </div>

  <div class="admin-member-layout">
    <aside class="admin-member-side">
      <div class="card staff-menu-card">
        <a class="staff-menu-option active" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin<?= $isDealerModule ? '?module=dealers' : '' ?>">
          <span><?= htmlspecialchars($moduleTitle, ENT_QUOTES) ?></span>
        </a>
      </div>
    </aside>

    <main class="admin-member-main">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label"><?= $isDealerModule ? '總經銷商數' : '總會員數' ?></div>
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
        <div class="stat-card">
          <div class="stat-label">已停用</div>
          <div class="stat-value" id="stat-suspended">—</div>
          <div class="stat-sub" style="color:#C0392B;">暫停服務</div>
        </div>
      </div>

      <div class="table-card">
        <div class="table-toolbar">
          <button class="filter-btn active" onclick="filter('all', this)">全部</button>
          <button class="filter-btn" onclick="filter('personal', this)">個人用戶</button>
          <button class="filter-btn" onclick="filter('company', this)">商業公司</button>
          <button class="filter-btn" onclick="filter('email_unverified', this)">未驗證信箱</button>
          <button class="filter-btn" onclick="filter('pending', this)">待審核</button>
          <button class="filter-btn" onclick="filter('suspended', this)">已停用</button>
          <div class="search-control">
            <label for="member-search">全文搜尋</label>
            <div class="search-input-wrap">
              <input type="text" class="search-box" id="member-search" placeholder="<?= $isDealerModule ? '搜尋經銷商...' : '搜尋會員...' ?>" oninput="search(this.value)" />
              <button type="button" class="search-clear" aria-label="清除搜尋內容" title="清除搜尋內容" onclick="clearMemberSearch()">×</button>
            </div>
          </div>
        </div>

        <table>
          <thead>
            <tr>
              <th>序號</th>
              <th>會員</th>
              <th>類型</th>
              <th>身份證號 / 統編</th>
              <th>聯絡資料</th>
              <th>公司 / 網址</th>
              <th>商店數</th>
              <th>狀態</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody id="member-tbody">
            <tr><td colspan="9" style="text-align:center;padding:32px;color:#888;">資料載入中...</td></tr>
          </tbody>
        </table>

        <div class="pagination">
          <span id="page-info">載入中...</span>
          <div class="page-btns" id="page-btns"></div>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
  const ADMIN_MEMBER_MODULE = '<?= $isDealerModule ? 'dealers' : 'members' ?>';
</script>
<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/admin.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/admin.js') ?>"></script>
