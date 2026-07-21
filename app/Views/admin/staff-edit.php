<?php if (!$staff): ?>
<div class="container">
  <div class="card result-card">
    <div class="result-icon fail">!</div>
    <div class="form-title">找不到操作人員</div>
    <div class="form-subtitle">這筆總後台操作人員資料可能已被刪除。</div>
    <a class="btn btn-primary" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin/staff">返回表列</a>
  </div>
</div>
<?php return; endif; ?>

<div class="container-wide">
  <div class="admin-header">
    <div>
      <a class="back-link" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin/staff">← 返回操作人員表列</a>
      <div class="admin-kicker">總後台</div>
      <h1>編輯操作人員</h1>
    </div>
    <div class="admin-actions">
      <span class="admin-info">管理員：<?= htmlspecialchars($_SESSION['admin']['email'] ?? ADMIN_EMAIL) ?></span>
      <button class="btn btn-sm btn-outline" onclick="logoutAdmin()">登出</button>
    </div>
  </div>

  <div class="staff-layout">
    <?php $staffMenuMode = 'accounts'; require BASE_PATH . '/app/Views/admin/partials/staff-menu.php'; ?>

    <div class="staff-main">
      <div class="edit-page-header">
        <div>
          <h1>編輯操作人員</h1>
          <p><?= htmlspecialchars($staff['name'] ?? '') ?> · <?= htmlspecialchars($staff['email'] ?? '') ?></p>
        </div>
        <span class="badge <?= ($staff['status'] ?? '') === 'active' ? 'badge-active' : (($staff['status'] ?? '') === 'pending_activation' ? 'badge-pending' : 'badge-suspended') ?>">
          <?= ($staff['status'] ?? '') === 'active' ? '啟用' : (($staff['status'] ?? '') === 'pending_activation' ? '待啟用' : '停用') ?>
        </span>
      </div>

      <div class="card">
        <form id="staff-edit-page-form" novalidate>
          <input type="hidden" id="staff-edit-id" value="<?= (int) ($staff['id'] ?? 0) ?>" />

          <div class="section-label">基本資料</div>
          <div class="form-row">
            <div class="form-group">
              <label>姓名 <span class="required">*</span></label>
              <input type="text" id="staff-edit-name" value="<?= htmlspecialchars($staff['name'] ?? '') ?>" />
            </div>
            <div class="form-group">
              <label>登入帳號 Email <span class="required">*</span></label>
              <input type="email" id="staff-edit-email" value="<?= htmlspecialchars($staff['email'] ?? '') ?>" autocomplete="username" />
            </div>
            <div class="form-group">
              <label>權限 <span class="required">*</span></label>
              <select id="staff-edit-role" data-current-group="<?= htmlspecialchars($staff['permission_group'] ?? '', ENT_QUOTES) ?>">
                <option value="">請先選擇權限群組</option>
                <?php foreach (($permissionGroups ?? []) as $group): ?>
                  <?php $groupName = (string) ($group['name'] ?? ''); ?>
                  <option value="<?= htmlspecialchars($groupName, ENT_QUOTES) ?>" <?= ($staff['permission_group'] ?? '') === $groupName ? 'selected' : '' ?>><?= htmlspecialchars($groupName) ?></option>
                <?php endforeach; ?>
              </select>
              <div class="field-hint">選項來源為「資訊安全 > 群組管理」建立的群組。</div>
            </div>
            <div class="form-group">
              <label>狀態 <span class="required">*</span></label>
              <select id="staff-edit-status">
                <option value="pending_activation" <?= ($staff['status'] ?? '') === 'pending_activation' ? 'selected' : '' ?>>待啟用</option>
                <option value="active" <?= ($staff['status'] ?? '') === 'active' ? 'selected' : '' ?>>啟用</option>
                <option value="suspended" <?= ($staff['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>停用</option>
              </select>
            </div>
          </div>

          <div class="section-label">安全設定</div>
          <div class="form-group">
            <label>新密碼</label>
            <input type="password" id="staff-edit-password" autocomplete="new-password" />
            <div class="field-hint">不變更密碼請留空；若輸入至少 8 位字元。</div>
          </div>
          <div class="form-group">
            <label>允許登入 IP Address</label>
            <textarea id="staff-edit-allowed-ips" rows="5" placeholder="127.0.0.1&#10;192.168.1.0/24"><?= htmlspecialchars($staff['allowed_ips'] ?? '') ?></textarea>
            <div class="field-hint">空白代表不限 IP；可一行一筆，支援單一 IP 或 CIDR。</div>
          </div>

          <div class="alert alert-success" id="staff-edit-success">操作人員已更新，正在返回表列...</div>
          <div class="alert alert-danger" id="staff-edit-error"><span id="staff-edit-error-msg">更新失敗。</span></div>

          <div class="form-actions">
            <a class="btn btn-outline" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin/staff">取消</a>
            <button type="submit" class="btn btn-success">儲存變更</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  const CURRENT_ADMIN_ID = <?= (int) ($_SESSION['admin']['id'] ?? 0) ?>;
</script>
<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/admin-staff-edit.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/admin-staff-edit.js') ?>"></script>
