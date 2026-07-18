<?php
$appBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($appBase === '.' || $appBase === '/') {
    $appBase = '';
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

<nav class="navbar">
  <div class="navbar-brand">★ <?= APP_NAME ?></div>
  <div class="navbar-nav">
    <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/register" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'register') ? 'active' : '' ?>">會員註冊</a>
    <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin"    class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin')    ? 'active' : '' ?>">後台管理</a>
  </div>
</nav>
