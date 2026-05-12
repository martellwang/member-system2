<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($title ?? APP_NAME) ?> — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="/assets/css/style.css" />
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand">★ <?= APP_NAME ?></div>
  <div class="navbar-nav">
    <a href="/register" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'register') ? 'active' : '' ?>">會員註冊</a>
    <a href="/admin"    class="nav-link <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'admin')    ? 'active' : '' ?>">後台管理</a>
  </div>
</nav>
