<?php
$staffMenuMode = $staffMenuMode ?? 'accounts';
$staffBaseHref = htmlspecialchars($appBase, ENT_QUOTES) . '/admin/staff';
$staffCreateHref = htmlspecialchars($appBase, ENT_QUOTES) . '/admin/staff?action=create';
$staffSecurityHref = htmlspecialchars($appBase, ENT_QUOTES) . '/admin/staff#security-settings';
$staffGroupsHref = htmlspecialchars($appBase, ENT_QUOTES) . '/admin/staff#group-settings';
$staffDeviceHref = htmlspecialchars($appBase, ENT_QUOTES) . '/admin/staff#device-management';
$staffStoreCodeHref = htmlspecialchars($appBase, ENT_QUOTES) . '/admin/staff#store-code-management';
$staffPaymentUpstreamHref = htmlspecialchars($appBase, ENT_QUOTES) . '/admin/staff#payment-upstream-management';
?>
<div class="staff-side">
  <div class="card staff-menu-card">
    <div class="staff-menu-section-title">核心作業</div>
    <a class="staff-menu-option" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin">
      <span>會員管理</span>
      <small>一般會員與公司會員</small>
    </a>
    <a class="staff-menu-option" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin?module=dealers">
      <span>經銷商管理</span>
      <small>經銷商會員與商店代號</small>
    </a>
    <div class="staff-menu-section-title">總後台操作人員</div>
    <a class="staff-menu-option <?= $staffMenuMode === 'accounts' ? 'active' : '' ?>" href="<?= $staffBaseHref ?>" id="staff-show-accounts">
      <span>操作人員帳號</span>
      <small>列表、搜尋與登入紀錄</small>
    </a>
    <a class="staff-menu-option staff-menu-child <?= $staffMenuMode === 'create' ? 'active' : '' ?>" href="<?= $staffCreateHref ?>" id="staff-show-create">
      <span>新增操作人員</span>
    </a>
    <a class="staff-menu-option <?= $staffMenuMode === 'security' ? 'active' : '' ?>" href="<?= $staffSecurityHref ?>" id="staff-show-security">
      <span>資訊安全</span>
      <small>登入 IP 與閒置登出</small>
    </a>
    <a class="staff-menu-option staff-menu-child <?= $staffMenuMode === 'groups' ? 'active' : '' ?>" href="<?= $staffGroupsHref ?>" id="staff-show-groups">
      <span>群組管理</span>
    </a>
    <a class="staff-menu-option <?= $staffMenuMode === 'devices' ? 'active' : '' ?>" href="<?= $staffDeviceHref ?>" id="staff-show-devices">
      <span>設備管理</span>
    </a>
    <a class="staff-menu-option <?= $staffMenuMode === 'store-codes' ? 'active' : '' ?>" href="<?= $staffStoreCodeHref ?>" id="staff-show-store-codes">
      <span>商店代號管理</span>
    </a>
    <a class="staff-menu-option <?= $staffMenuMode === 'payment-upstream' ? 'active' : '' ?>" href="<?= $staffPaymentUpstreamHref ?>" id="staff-show-payment-upstream">
      <span>金流上游管理</span>
    </a>
  </div>
</div>
