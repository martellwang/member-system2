<?php
// 後台管理頁面
// TODO: 加入管理員 session 驗證
// session_start();
// if (!isset($_SESSION['admin'])) {
//     header('Location: login.php');
//     exit;
// }

$pageTitle  = '後台管理';
$adminEmail = 'admin@system.com'; // 未來可從 session 取得

// 統計資料（未來改為從 DB 查詢）
$stats = [
    'total'    => 0,
    'personal' => 0,
    'company'  => 0,
    'pending'  => 0,
];
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?> — 會員管理系統</title>
  <link rel="stylesheet" href="../css/style.css" />
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand">★ 會員管理系統</div>
  <div class="navbar-nav">
    <a href="register.php" class="nav-link">會員註冊</a>
    <a href="admin.php" class="nav-link active">後台管理</a>
  </div>
</nav>

<div class="container-wide">
  <div class="admin-header">
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    <span class="admin-info">管理員：<?= htmlspecialchars($adminEmail) ?></span>
  </div>

  <!-- 統計卡片（初始值由 JS 從 API 更新） -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">總會員數</div>
      <div class="stat-value" id="stat-total"><?= $stats['total'] ?: '—' ?></div>
      <div class="stat-sub">↑ 本週新增 2</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">個人用戶</div>
      <div class="stat-value" id="stat-personal"><?= $stats['personal'] ?: '—' ?></div>
      <div class="stat-sub" id="stat-personal-pct">—</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">商業公司</div>
      <div class="stat-value" id="stat-company"><?= $stats['company'] ?: '—' ?></div>
      <div class="stat-sub" id="stat-company-pct">—</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">待審核</div>
      <div class="stat-value" id="stat-pending"><?= $stats['pending'] ?: '—' ?></div>
      <div class="stat-sub" style="color:#BA7517;">需要處理</div>
    </div>
  </div>

  <!-- 會員列表 -->
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
        <tr>
          <td colspan="7" style="text-align:center; padding:32px; color:#888;">
            資料載入中...
          </td>
        </tr>
      </tbody>
    </table>

    <div class="pagination">
      <span id="page-info">載入中...</span>
      <div class="page-btns" id="page-btns"></div>
    </div>
  </div>
</div>

<script src="../js/admin.js"></script>
</body>
</html>
