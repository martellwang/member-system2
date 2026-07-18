<div class="auth-shell">
  <div class="auth-panel">
    <?php if (!$valid || !$member): ?>
      <div class="result-icon fail">!</div>
      <div class="form-title">連結無效或已使用</div>
      <div class="form-subtitle">請重新註冊，或聯絡系統管理人員協助確認帳號狀態。</div>
      <a class="btn btn-primary" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/register">返回會員註冊</a>
    <?php else: ?>
      <div>
        <div class="form-title">設定會員登入密碼</div>
        <div class="form-subtitle">
          <?= htmlspecialchars($member['name'] ?? '') ?>，請完成信箱驗證並設定登入密碼。
        </div>
        <div class="field-hint"><?= htmlspecialchars($member['email'] ?? '') ?></div>
      </div>

      <form id="member-password-setup-form" novalidate>
        <div class="form-group">
          <label>新密碼 <span class="required">*</span></label>
          <input type="password" id="setup-password" autocomplete="new-password" />
          <div class="field-hint">至少 8 位字元。</div>
        </div>
        <div class="form-group">
          <label>再次輸入新密碼 <span class="required">*</span></label>
          <input type="password" id="setup-password-confirm" autocomplete="new-password" />
        </div>
        <button type="submit" class="btn btn-primary">完成設定並送審</button>
        <div class="alert alert-success" id="setup-success">信箱驗證與密碼設定完成，正在前往登入頁...</div>
        <div class="alert alert-danger" id="setup-error"><span id="setup-error-msg">設定失敗</span></div>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($valid && $member): ?>
<script>
  const MEMBER_PASSWORD_SETUP_TOKEN = <?= json_encode($token, JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/member-password-setup.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/member-password-setup.js') ?>"></script>
<?php endif; ?>
