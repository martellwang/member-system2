<div class="auth-shell">
  <div class="auth-panel">
    <div>
      <div class="form-title">管理員登入</div>
      <div class="form-subtitle">登入後即可審核、編輯與管理會員資料</div>
    </div>

    <form id="admin-login-form" novalidate>
      <div class="form-group">
        <label>管理員帳號</label>
        <input type="email" id="login-email" value="admin@system.com" autocomplete="username" />
      </div>
      <div class="form-group">
        <label>密碼</label>
        <input type="password" id="login-password" value="admin12345" autocomplete="current-password" />
      </div>
      <button type="submit" class="btn btn-primary">登入後台</button>
      <div class="alert alert-danger" id="login-error"><span id="login-error-msg">登入失敗</span></div>
    </form>
  </div>
</div>

<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/login.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/login.js') ?>"></script>
