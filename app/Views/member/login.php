<div class="auth-shell login-page-shell">
  <div class="login-brand">
    <div class="login-brand-mark">新零售行銷多元平台 - NewPay</div>
  </div>

  <div class="auth-panel login-panel">
    <div class="login-tabs" role="tablist" aria-label="會員登入類型">
      <button type="button" class="login-tab active" data-login-type="company" role="tab" aria-selected="true">公司法人</button>
      <button type="button" class="login-tab" data-login-type="personal" role="tab" aria-selected="false">個人會員</button>
    </div>

    <form id="member-login-form" data-member-type="company" novalidate>
      <div class="form-group login-identity-field" data-field="company">
        <label>公司統一編號</label>
        <input type="text" name="tax_id" placeholder="12345678" maxlength="8" inputmode="numeric" autocomplete="organization" />
      </div>

      <div class="form-group login-identity-field" data-field="personal" hidden>
        <label>身分證號</label>
        <input type="text" name="id_number" placeholder="A123456789" maxlength="10" style="text-transform:uppercase" autocomplete="off" />
      </div>

      <div class="form-group">
        <label>登入密碼</label>
        <input type="password" name="password" autocomplete="current-password" />
      </div>

      <button type="submit" class="btn login-submit">登入公司法人</button>
      <div class="alert alert-danger member-login-error"><span>登入失敗</span></div>
    </form>

    <div class="login-google-block">
      <div class="divider-text">或</div>
      <a class="btn btn-google" data-google-login-link href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/auth/google?mode=login&amp;member_type=company">使用 Google 帳號登入</a>
    </div>

    <div class="login-links">
      <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/register">註冊會員</a>
      <span></span>
      <a href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/register">忘記密碼?</a>
    </div>
  </div>
</div>

<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/member-login.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/member-login.js') ?>"></script>
