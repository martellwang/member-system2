<?php
$staffMenuMode = $staffMenuMode ?? 'accounts';
$staffBaseHref = htmlspecialchars($appBase, ENT_QUOTES) . '/admin/staff';
$staffCreateHref = htmlspecialchars($appBase, ENT_QUOTES) . '/admin/staff?action=create';
$staffSecurityHref = htmlspecialchars($appBase, ENT_QUOTES) . '/admin/staff#security-settings';
$staffGroupsHref = htmlspecialchars($appBase, ENT_QUOTES) . '/admin/staff#group-settings';
$staffDeviceHref = htmlspecialchars($appBase, ENT_QUOTES) . '/admin/staff#device-management';
?>
<div class="staff-side">
  <div class="card staff-menu-card">
    <a class="staff-menu-option <?= $staffMenuMode === 'accounts' ? 'active' : '' ?>" href="<?= $staffBaseHref ?>" id="staff-show-accounts">
      <span>內部管理帳號</span>
    </a>
    <a class="staff-menu-option staff-menu-child <?= $staffMenuMode === 'create' ? 'active' : '' ?>" href="<?= $staffCreateHref ?>" id="staff-show-create">
      <span>新增管理人員</span>
    </a>
    <a class="staff-menu-option <?= $staffMenuMode === 'security' ? 'active' : '' ?>" href="<?= $staffSecurityHref ?>" id="staff-show-security">
      <span>資訊安全</span>
    </a>
    <a class="staff-menu-option staff-menu-child <?= $staffMenuMode === 'groups' ? 'active' : '' ?>" href="<?= $staffGroupsHref ?>" id="staff-show-groups">
      <span>群組管理</span>
    </a>
    <a class="staff-menu-option <?= $staffMenuMode === 'devices' ? 'active' : '' ?>" href="<?= $staffDeviceHref ?>" id="staff-show-devices">
      <span>設備管理</span>
    </a>
  </div>
</div>
