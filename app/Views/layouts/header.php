<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$appBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($appBase === '.' || $appBase === '/') {
    $appBase = '';
}

$loggedInMember = $_SESSION['member'] ?? null;
$isMemberLoggedIn = !empty($loggedInMember['id']);
$loggedInAdmin = $_SESSION['admin'] ?? null;
$isAdminLoggedIn = !empty($loggedInAdmin['id']);
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isAdminArea = str_contains($currentUri, '/admin');
$isAdminStaffArea = str_contains($currentUri, '/admin/staff');
$isDealerModule = $isAdminArea && !$isAdminStaffArea && (($_GET['module'] ?? '') === 'dealers');
$navbarBrand = $isAdminArea ? '內部管理後台' : '新零售行銷多元平台';
if ($isMemberLoggedIn) {
    $memberTimeoutSeconds = max(60, MEMBER_SESSION_TIMEOUT_SECONDS);
    $lastActivityAt = (int) ($loggedInMember['last_activity_at'] ?? 0);
    if ($lastActivityAt > 0 && (time() - $lastActivityAt) > $memberTimeoutSeconds) {
        unset($_SESSION['member']);
        $loggedInMember = null;
        $isMemberLoggedIn = false;
    } else {
        $_SESSION['member']['last_activity_at'] = time();
        $loggedInMember = $_SESSION['member'];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="app-base" content="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>" />
  <title><?= htmlspecialchars($title ?? APP_NAME) ?> — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/css/style.css" />
</head>
<body>

<?php if (empty($hideNavbar)): ?>
  <nav class="navbar <?= $isAdminArea ? 'navbar-admin' : '' ?>">
    <div class="navbar-brand">★ <?= htmlspecialchars($navbarBrand, ENT_QUOTES) ?></div>
    <?php if ($isAdminArea && $isAdminLoggedIn): ?>
      <div class="admin-module-nav" aria-label="後台功能模組">
        <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin" class="<?= !$isAdminStaffArea && !$isDealerModule ? 'active' : '' ?>">會員管理</a>
        <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin?module=dealers" class="<?= $isDealerModule ? 'active' : '' ?>">經銷商管理</a>
        <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin/staff" class="<?= $isAdminStaffArea ? 'active' : '' ?>">內部管理人員</a>
      </div>
    <?php endif; ?>
    <div class="navbar-nav">
      <?php if ($isAdminArea && $isAdminLoggedIn): ?>
        <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin" class="nav-link active">後台管理</a>
      <?php elseif ($isAdminArea): ?>
      <?php elseif ($isMemberLoggedIn): ?>
        <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/member" class="nav-link <?= str_contains($currentUri, 'member') ? 'active' : '' ?>">會員中心</a>
      <?php else: ?>
        <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/register" class="nav-link <?= str_contains($currentUri, 'register') ? 'active' : '' ?>">會員註冊</a>
        <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/login" class="nav-link <?= str_contains($currentUri, 'login') ? 'active' : '' ?>">會員登入</a>
      <?php endif; ?>
      <?php if ($isMemberLoggedIn): ?>
        <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/member/logout" class="nav-link nav-link-logout">登出</a>
      <?php endif; ?>
    </div>
  </nav>
  <?php if ($isMemberLoggedIn): ?>
    <?php
      $memberLastActivity = (int) ($loggedInMember['last_activity_at'] ?? time());
      $memberExpiresAt = $memberLastActivity + max(60, MEMBER_SESSION_TIMEOUT_SECONDS);
      $memberRemainingSeconds = max(0, $memberExpiresAt - time());
      $memberDisplayName = trim((string) ($loggedInMember['name'] ?? ''));
      $memberDisplayEmail = trim((string) ($loggedInMember['email'] ?? ''));
      $memberDisplayText = $memberDisplayName !== '' ? $memberDisplayName : $memberDisplayEmail;
    ?>
    <div class="member-timeout-bar" data-member-timeout="<?= (int) $memberRemainingSeconds ?>" data-member-timeout-total="<?= (int) $memberTimeoutSeconds ?>" data-logout-url="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/member/logout">
      <span>目前登入狀態：<strong>已登入<?= $memberDisplayText !== '' ? '，' . htmlspecialchars($memberDisplayText, ENT_QUOTES) : '' ?></strong></span>
      <span>距離系統自動登出時間：<strong id="member-timeout-countdown"><?= gmdate('i:s', $memberRemainingSeconds) ?></strong></span>
      <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/member/logout">登出</a>
    </div>
  <?php endif; ?>
  <?php if ($isAdminArea && $isAdminLoggedIn): ?>
    <?php
      $adminTimeoutSeconds = max(60, (int) ($adminTimeoutSeconds ?? ADMIN_SESSION_TIMEOUT_SECONDS));
      $adminLastActivity = (int) ($loggedInAdmin['last_activity_at'] ?? time());
      $adminExpiresAt = $adminLastActivity + $adminTimeoutSeconds;
      $adminRemainingSeconds = max(0, $adminExpiresAt - time());
      $adminDisplayName = trim((string) ($loggedInAdmin['name'] ?? ''));
      $adminDisplayEmail = trim((string) ($loggedInAdmin['email'] ?? ''));
      $adminDisplayText = $adminDisplayName !== '' ? $adminDisplayName : $adminDisplayEmail;
    ?>
    <div class="admin-timeout-bar" data-timeout-seconds="<?= (int) $adminRemainingSeconds ?>" data-timeout-total="<?= (int) $adminTimeoutSeconds ?>" data-timeout-logout="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/api/admin/logout" data-timeout-redirect="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin/login">
      <span>後台登入狀態：<strong>已登入<?= $adminDisplayText !== '' ? '，' . htmlspecialchars($adminDisplayText, ENT_QUOTES) : '' ?></strong></span>
      <span>距離系統自動登出時間：<strong id="admin-timeout-countdown"><?= gmdate('i:s', $adminRemainingSeconds) ?></strong></span>
    </div>
  <?php endif; ?>
<?php endif; ?>
