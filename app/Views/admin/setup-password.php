<div class="auth-shell">
  <div class="auth-panel">
    <?php if (!$valid || !$admin): ?>
      <div class="result-icon fail">!</div>
      <div class="form-title">連結無效或已過期</div>
      <div class="form-subtitle">請聯絡系統管理員重新建立管理帳號邀請。</div>
      <a class="btn btn-primary" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin/login">返回管理員登入</a>
    <?php else: ?>
      <div>
        <div class="form-title">設定管理後台密碼</div>
        <div class="form-subtitle">
          <?= htmlspecialchars($admin['name'] ?? '') ?>，請完成信箱認證並設定登入密碼。
        </div>
        <div class="field-hint"><?= htmlspecialchars($admin['email'] ?? '') ?></div>
      </div>

      <form id="admin-password-setup-form" novalidate>
        <div class="form-group">
          <label>新密碼 <span class="required">*</span></label>
          <input type="password" id="setup-password" autocomplete="new-password" />
          <div class="field-hint">至少 8 位字元。</div>
        </div>
        <div class="form-group">
          <label>再次輸入新密碼 <span class="required">*</span></label>
          <input type="password" id="setup-password-confirm" autocomplete="new-password" />
        </div>
        <button type="submit" class="btn btn-primary">完成設定並啟用帳號</button>
        <div class="alert alert-success" id="setup-success">密碼設定完成，正在前往登入頁...</div>
        <div class="alert alert-danger" id="setup-error"><span id="setup-error-msg">設定失敗</span></div>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($valid && $admin): ?>
<script>
  const PASSWORD_SETUP_TOKEN = <?= json_encode($token, JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/admin-password-setup.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/admin-password-setup.js') ?>"></script>
<?php endif; ?>
