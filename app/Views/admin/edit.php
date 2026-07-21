<?php if (!$member): ?>
<div class="container">
  <div class="card result-card">
    <div class="result-icon fail">!</div>
    <div class="form-title">找不到會員</div>
    <div class="form-subtitle">這筆會員資料可能已被刪除。</div>
    <a class="btn btn-primary" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin">返回後台</a>
  </div>
</div>
<?php return; endif; ?>
<?php
$canEditCompanyProfile = (($_SESSION['admin']['role'] ?? '') === 'super_admin');
$isCompanyLocked = !$canEditCompanyProfile && ($member['type'] ?? '') === 'company';
$companyLockAttr = $isCompanyLocked ? 'disabled' : '';
$statusMap = [
  'active' => ['label' => '啟用', 'class' => 'badge-active'],
  'suspended' => ['label' => '停用', 'class' => 'badge-suspended'],
  'email_unverified' => ['label' => '未驗證信箱', 'class' => 'badge-suspended'],
  'pending' => ['label' => '待審', 'class' => 'badge-pending'],
];
$statusMeta = $statusMap[$member['status'] ?? 'pending'] ?? $statusMap['pending'];
$stores = $stores ?? [];
$storeStatusMap = [
  'pending' => ['label' => '待審', 'class' => 'badge-pending'],
  'active' => ['label' => '啟用', 'class' => 'badge-active'],
  'suspended' => ['label' => '停用', 'class' => 'badge-suspended'],
  'rejected' => ['label' => '退件', 'class' => 'badge-suspended'],
];
$storeTypeLabel = static fn (array $store): string => ($store['store_type'] ?? '') === 'physical' ? '實體商店' : '網路商店';
$storeCode = static function (array $store): string {
  $prefix = ($store['store_type'] ?? '') === 'physical' ? 'NEDC' : 'NPPA';
  return $prefix . str_pad((string) ($store['id'] ?? 0), 8, '0', STR_PAD_LEFT);
};
$decodeJsonList = static function ($value): array {
  $decoded = json_decode((string) $value, true);
  return is_array($decoded) ? $decoded : [];
};
$paymentToolOptions = ['一次付清（國內卡）', '一次付清（國外卡）', '銀聯卡', '分期付款', 'Apple Pay', 'Google Pay', 'Samsung Pay', '超商代碼', 'ATM 轉帳', 'icash Pay', 'LINE Pay', '街口支付', 'AFTEE'];
$contactCity = (string) ($member['contact_city'] ?? '');
$contactDistrict = (string) ($member['contact_district'] ?? '');
$contactAddressLine = (string) ($member['contact_address_line'] ?? '');
if ($contactAddressLine === '' && !empty($member['contact_address'])) {
  $legacyAddressParts = \Support\TaiwanAddress::split((string) $member['contact_address']);
  $contactCity = $contactCity !== '' ? $contactCity : $legacyAddressParts['city'];
  $contactDistrict = $contactDistrict !== '' ? $contactDistrict : $legacyAddressParts['district'];
  $contactAddressLine = $legacyAddressParts['address_line'];
}
?>

<div class="container edit-container">
  <aside class="admin-member-store-sidebar">
    <div class="card admin-member-store-card">
      <div class="form-title">會員基本資料</div>
      <div class="form-subtitle">此區僅供檢視，不提供編輯。</div>
        <div class="admin-store-readonly-list">
          <section class="admin-store-readonly-item">
              <dl>
                <div>
                  <dt>會員編號</dt>
                  <dd><?= htmlspecialchars($member['member_code'] ?? '—', ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>會員類型</dt>
                  <dd><?= htmlspecialchars(($member['type'] ?? '') === 'company' ? '商業公司' : '個人用戶', ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>姓名</dt>
                  <dd><?= htmlspecialchars($member['name'] ?? '—', ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>電子信箱</dt>
                  <dd><?= htmlspecialchars($member['email'] ?? '—', ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt><?= ($member['type'] ?? '') === 'company' ? '統一編號' : '身分證號' ?></dt>
                  <dd><?= htmlspecialchars(($member['type'] ?? '') === 'company' ? ($member['tax_id'] ?? '—') : ($member['id_number'] ?? '—'), ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>手機電話</dt>
                  <dd><?= htmlspecialchars($member['mobile_phone'] ?? '—', ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>聯絡地址</dt>
                  <dd><?= htmlspecialchars(trim($contactCity . $contactDistrict . $contactAddressLine) ?: '—', ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>Line ID</dt>
                  <dd><?= htmlspecialchars($member['line_id'] ?? '—', ENT_QUOTES) ?></dd>
                </div>
              </dl>
          </section>
        </div>
    </div>
  </aside>

  <div class="edit-page-header">
    <div>
      <a class="back-link" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin">← 返回會員列表</a>
      <h1>編輯會員</h1>
      <p><?= htmlspecialchars($member['name']) ?> · <?= htmlspecialchars($member['email']) ?></p>
    </div>
    <span class="badge <?= htmlspecialchars($statusMeta['class']) ?>">
      <?= htmlspecialchars($statusMeta['label']) ?>
    </span>
  </div>

  <nav class="member-edit-tabs" aria-label="會員編輯功能">
    <a class="member-edit-tab" href="#member-profile" data-member-edit-tab="profile">會員資料</a>
    <a class="member-edit-tab" href="#admin-store-management" data-member-edit-tab="stores">商店管理</a>
  </nav>

  <section class="admin-store-management" id="admin-store-management">
    <div class="admin-store-heading">
      <h2>商店管理</h2>
      <span>共 <?= count($stores) ?> 筆商店</span>
    </div>

    <?php if (!$stores): ?>
      <div class="card admin-store-empty">此會員尚未建立商店資料。</div>
    <?php else: ?>
      <div class="admin-store-list">
        <div class="admin-store-list-heading" aria-hidden="true">
          <span>序號</span>
          <span>商店資料</span>
        </div>
        <?php foreach ($stores as $storeIndex => $store): ?>
          <?php
            $storeStatus = $storeStatusMap[$store['status'] ?? 'pending'] ?? $storeStatusMap['pending'];
            $paymentTools = $decodeJsonList($store['payment_tools'] ?? '[]');
            $storeId = (int) ($store['id'] ?? 0);
          ?>
          <article class="card admin-store-item" data-admin-store-item>
            <div class="admin-store-index" aria-label="序號 <?= $storeIndex + 1 ?>"><?= $storeIndex + 1 ?></div>
            <div class="admin-store-summary">
              <div>
                <div class="admin-store-title">
                  <strong><?= htmlspecialchars($store['store_name'] ?? '', ENT_QUOTES) ?></strong>
                  <span class="badge <?= htmlspecialchars($storeStatus['class'], ENT_QUOTES) ?>"><?= htmlspecialchars($storeStatus['label'], ENT_QUOTES) ?></span>
                </div>
                <p>
                  <?= htmlspecialchars($storeTypeLabel($store), ENT_QUOTES) ?>
                  <span>｜<?= htmlspecialchars($storeCode($store), ENT_QUOTES) ?></span>
                  <span>｜<?= htmlspecialchars($store['store_email'] ?? '', ENT_QUOTES) ?></span>
                </p>
              </div>
            <button type="button" class="btn btn-sm btn-outline" data-admin-store-toggle>管理</button>
            </div>

            <form class="admin-store-form" data-admin-store-form data-member-id="<?= htmlspecialchars((string) $member['id'], ENT_QUOTES) ?>" data-store-id="<?= $storeId ?>" hidden>
              <div class="admin-store-detail-tabs" role="tablist" aria-label="商店功能設定">
                <button type="button" class="active" data-admin-store-tab="terms">商業條件及支付工具設定</button>
                <button type="button" data-admin-store-tab="integration">串接設定</button>
                <button type="button" data-admin-store-tab="invoice">電子發票</button>
                <button type="button" data-admin-store-tab="details">詳細資訊</button>
                <button type="button" data-admin-store-tab="limit">交易限制設定</button>
                <button type="button" data-admin-store-tab="marketing">行銷工具設定</button>
              </div>

              <div class="admin-store-tab-hint" data-admin-store-tab-hint>商業條件、支付工具與下方商店資料可直接編輯。</div>

              <section class="admin-store-setting-panel admin-store-tab-panel" data-admin-store-panel="details" hidden>
              <div class="section-label">商店資料</div>
              <div class="form-row">
                <div class="form-group">
                  <label>商店狀態 <span class="required">*</span></label>
                  <select name="status">
                    <?php foreach ($storeStatusMap as $value => $meta): ?>
                      <option value="<?= htmlspecialchars($value, ENT_QUOTES) ?>" <?= ($store['status'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($meta['label'], ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>商店類型 <span class="required">*</span></label>
                  <select name="store_type">
                    <option value="online" <?= ($store['store_type'] ?? '') === 'online' ? 'selected' : '' ?>>網路商店</option>
                    <option value="physical" <?= ($store['store_type'] ?? '') === 'physical' ? 'selected' : '' ?>>實體商店</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>商店名稱 <span class="required">*</span></label>
                  <input type="text" name="store_name" value="<?= htmlspecialchars($store['store_name'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>商店電子信箱 <span class="required">*</span></label>
                  <input type="email" name="store_email" value="<?= htmlspecialchars($store['store_email'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>國外卡英文帳單名稱</label>
                  <input type="text" name="foreign_statement_name" value="<?= htmlspecialchars($store['foreign_statement_name'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>商店電話</label>
                  <input type="text" name="store_phone" value="<?= htmlspecialchars($store['store_phone'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>商店傳真號碼</label>
                  <input type="text" name="store_fax" value="<?= htmlspecialchars($store['store_fax'] ?? '', ENT_QUOTES) ?>">
                </div>
              </div>

              <div class="section-label">聯絡與地址</div>
              <div class="form-row">
                <div class="form-group">
                  <label>縣市</label>
                  <input type="text" name="store_city" value="<?= htmlspecialchars($store['store_city'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>行政區</label>
                  <input type="text" name="store_district" value="<?= htmlspecialchars($store['store_district'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>路/段/巷/弄/號</label>
                  <input type="text" name="store_address" value="<?= htmlspecialchars($store['store_address'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>聯絡人名稱 <span class="required">*</span></label>
                  <input type="text" name="contact_name" value="<?= htmlspecialchars($store['contact_name'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>聯絡人手機號碼</label>
                  <input type="text" name="contact_mobile" value="<?= htmlspecialchars($store['contact_mobile'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>聯絡人電話</label>
                  <input type="text" name="contact_phone" value="<?= htmlspecialchars($store['contact_phone'] ?? '', ENT_QUOTES) ?>">
                </div>
              </div>

              </section>

              <section class="admin-store-setting-panel admin-store-tab-panel" data-admin-store-panel="terms">
              <div class="section-label">營運與串接資料</div>
              <div class="form-row">
                <div class="form-group">
                  <label>產業類別</label>
                  <input type="text" name="industry" value="<?= htmlspecialchars($store['industry'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>販售商品類型</label>
                  <input type="text" name="product_type" value="<?= htmlspecialchars($store['product_type'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>履約保證類型</label>
                  <input type="text" name="guarantee_type" value="<?= htmlspecialchars($store['guarantee_type'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>商品交付完成期間</label>
                  <div class="inline-input">
                    <input type="number" name="delivery_period" min="0" value="<?= htmlspecialchars((string) ($store['delivery_period'] ?? 0), ENT_QUOTES) ?>">
                    <select name="delivery_unit">
                      <?php foreach (['個月', '天'] as $unit): ?>
                        <option value="<?= htmlspecialchars($unit, ENT_QUOTES) ?>" <?= ($store['delivery_unit'] ?? '') === $unit ? 'selected' : '' ?>><?= htmlspecialchars($unit, ENT_QUOTES) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label>平均客單價</label>
                  <input type="text" name="average_order_amount" value="<?= htmlspecialchars($store['average_order_amount'] ?? '', ENT_QUOTES) ?>">
                </div>
                <div class="form-group">
                  <label>商店網址</label>
                  <input type="url" name="store_url" value="<?= htmlspecialchars($store['store_url'] ?? '', ENT_QUOTES) ?>">
                  <input type="hidden" name="store_url_type" value="<?= htmlspecialchars($store['store_url_type'] ?? 'url', ENT_QUOTES) ?>">
                </div>
                <div class="form-group form-group-wide">
                  <label>商店營運說明</label>
                  <textarea name="store_description"><?= htmlspecialchars($store['store_description'] ?? '', ENT_QUOTES) ?></textarea>
                </div>
                <div class="form-group form-group-wide">
                  <label>履約保證說明</label>
                  <textarea name="guarantee_note"><?= htmlspecialchars($store['guarantee_note'] ?? '', ENT_QUOTES) ?></textarea>
                  <input type="hidden" name="guarantee_note_type" value="<?= htmlspecialchars($store['guarantee_note_type'] ?? 'not_required', ENT_QUOTES) ?>">
                </div>
              </div>

              <div class="section-label">支付工具</div>
              <div class="admin-store-payment-grid">
                <?php foreach ($paymentToolOptions as $tool): ?>
                  <label class="checkbox-field">
                    <input type="checkbox" name="payment_tools[]" value="<?= htmlspecialchars($tool, ENT_QUOTES) ?>" <?= in_array($tool, $paymentTools, true) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($tool, ENT_QUOTES) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>

              </section>

              <section class="admin-store-setting-panel" data-admin-store-panel="integration" hidden>
                <div class="section-label">串接設定</div>
                <div class="form-row">
                  <div class="form-group"><label>Hash Key</label><input type="text" name="integration_hash_key" maxlength="255" value="<?= htmlspecialchars((string) ($store['integration_hash_key'] ?? ''), ENT_QUOTES) ?>" autocomplete="off"></div>
                  <div class="form-group"><label>IV Key</label><input type="text" name="integration_iv_key" maxlength="255" value="<?= htmlspecialchars((string) ($store['integration_iv_key'] ?? ''), ENT_QUOTES) ?>" autocomplete="off"></div>
                  <div class="form-group"><label>Notify URL</label><input type="url" name="integration_notify_url" maxlength="255" value="<?= htmlspecialchars((string) ($store['integration_notify_url'] ?? ''), ENT_QUOTES) ?>"></div>
                  <div class="form-group"><label>Return URL</label><input type="url" name="integration_return_url" maxlength="255" value="<?= htmlspecialchars((string) ($store['integration_return_url'] ?? ''), ENT_QUOTES) ?>"></div>
                  <label class="checkbox-field"><input type="checkbox" name="integration_test_mode" value="1" <?= !empty($store['integration_test_mode']) ? 'checked' : '' ?>><span>啟用測試模式</span></label>
                  <div class="form-group form-group-wide"><label>限定 API 的 IP（每行一筆 IP 或 CIDR）</label><textarea name="integration_allowed_ips" maxlength="2000" rows="3"><?= htmlspecialchars((string) ($store['integration_allowed_ips'] ?? ''), ENT_QUOTES) ?></textarea></div>
                </div>
                <div class="admin-store-switch-grid">
                  <?php foreach ([
                    'integration_credit_card_api_enabled' => '信用卡簽後授權 API', 'integration_refund_api_enabled' => '退款 API',
                    'integration_token_api_enabled' => '信用卡 Token API', 'integration_non_card_refund_api_enabled' => '非信用卡退款 API',
                    'integration_logistics_refund_api_enabled' => '物流簽後 API', 'integration_linepay_refund_api_enabled' => 'LINE Pay 簽後 API',
                    'integration_member_free_api_enabled' => '免脈轉支付元件', 'integration_discount_refund_api_enabled' => '優惠券全額折抵簽後 API',
                    'integration_street_payment_refund_api_enabled' => '街口支付簽後 API'
                  ] as $field => $label): ?>
                    <label class="checkbox-field"><input type="checkbox" name="<?= $field ?>" value="1" <?= !isset($store[$field]) || !empty($store[$field]) ? 'checked' : '' ?>><span><?= htmlspecialchars($label, ENT_QUOTES) ?></span></label>
                  <?php endforeach; ?>
                </div>
              </section>

              <section class="admin-store-setting-panel" data-admin-store-panel="invoice" hidden>
                <div class="section-label">電子發票</div>
                <div class="form-row">
                  <label class="checkbox-field"><input type="checkbox" name="e_invoice_enabled" value="1" <?= !empty($store['e_invoice_enabled']) ? 'checked' : '' ?>><span>啟用電子發票</span></label>
                  <div class="form-group"><label>電子發票加值中心</label><input type="text" name="e_invoice_center" maxlength="100" value="<?= htmlspecialchars((string) ($store['e_invoice_center'] ?? ''), ENT_QUOTES) ?>"></div>
                  <div class="form-group"><label>預設發票捐贈單位</label><input type="text" name="e_invoice_gift_unit" maxlength="30" value="<?= htmlspecialchars((string) ($store['e_invoice_gift_unit'] ?? ''), ENT_QUOTES) ?>"></div>
                  <label class="checkbox-field"><input type="checkbox" name="e_invoice_auto_issue" value="1" <?= !isset($store['e_invoice_auto_issue']) || $store['e_invoice_auto_issue'] ? 'checked' : '' ?>><span>付款完成後自動開立</span></label>
                  <div class="form-group"><label>延後開立天數</label><input type="number" name="e_invoice_delay_days" min="1" max="30" value="<?= htmlspecialchars((string) ($store['e_invoice_delay_days'] ?? ''), ENT_QUOTES) ?>"></div>
                </div>
              </section>

              <section class="admin-store-setting-panel" data-admin-store-panel="limit" hidden>
                <div class="section-label">交易限制設定</div>
                <div class="form-row">
                  <label class="checkbox-field"><input type="checkbox" name="transaction_amount_limit_enabled" value="1" <?= !isset($store['transaction_amount_limit_enabled']) || $store['transaction_amount_limit_enabled'] ? 'checked' : '' ?>><span>啟用交易金額上限</span></label>
                  <label class="checkbox-field"><input type="checkbox" name="expired_refund_enabled" value="1" <?= !empty($store['expired_refund_enabled']) ? 'checked' : '' ?>><span>接受逾期退款申請</span></label>
                  <div class="form-group"><label>信用卡交易限制</label><select name="transaction_card_limit_mode"><option value="off" <?= ($store['transaction_card_limit_mode'] ?? 'off') === 'off' ? 'selected' : '' ?>>關閉</option><option value="blacklist" <?= ($store['transaction_card_limit_mode'] ?? '') === 'blacklist' ? 'selected' : '' ?>>黑名單模式</option><option value="whitelist" <?= ($store['transaction_card_limit_mode'] ?? '') === 'whitelist' ? 'selected' : '' ?>>白名單模式</option></select></div>
                  <div class="form-group"><label>IP 交易限制</label><select name="transaction_ip_limit_mode"><option value="off" <?= ($store['transaction_ip_limit_mode'] ?? 'off') === 'off' ? 'selected' : '' ?>>關閉</option><option value="blacklist" <?= ($store['transaction_ip_limit_mode'] ?? '') === 'blacklist' ? 'selected' : '' ?>>黑名單模式</option><option value="whitelist" <?= ($store['transaction_ip_limit_mode'] ?? '') === 'whitelist' ? 'selected' : '' ?>>白名單模式</option></select></div>
                </div>
              </section>

              <section class="admin-store-setting-panel" data-admin-store-panel="marketing" hidden>
                <div class="section-label">行銷工具設定</div>
                <div class="form-row">
                  <label class="checkbox-field"><input type="checkbox" name="marketing_enabled" value="1" <?= !empty($store['marketing_enabled']) ? 'checked' : '' ?>><span>啟用行銷工具</span></label>
                  <div class="form-group form-group-wide"><label>行銷備註</label><textarea name="marketing_notes" maxlength="400" rows="4"><?= htmlspecialchars((string) ($store['marketing_notes'] ?? ''), ENT_QUOTES) ?></textarea><div class="field-hint">最多 400 字。</div></div>
                </div>
              </section>

              <div class="alert alert-success admin-store-success">商店資料已更新。</div>
              <div class="alert alert-danger admin-store-error"><span>商店資料更新失敗。</span></div>
              <div class="form-actions">
                <button type="button" class="btn btn-outline" data-admin-store-cancel>收合</button>
                <button type="submit" class="btn btn-success">儲存商店</button>
              </div>
            </form>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <div class="card member-edit-form-card">
    <form id="member-edit-page-form" novalidate>
      <input type="hidden" id="edit-id" value="<?= htmlspecialchars((string) $member['id']) ?>" />

      <div class="section-label">基本資料</div>
      <div class="form-row">
        <div class="form-group">
          <label>會員編號</label>
          <input type="text" value="<?= htmlspecialchars($member['member_code'] ?? '') ?>" readonly />
        </div>
        <div class="form-group">
          <label>會員類型 <span class="required">*</span></label>
          <select id="edit-type" onchange="switchEditType(this.value)" <?= $isCompanyLocked ? 'disabled' : '' ?>>
            <option value="personal" <?= $member['type'] === 'personal' ? 'selected' : '' ?>>個人用戶</option>
            <option value="company" <?= $member['type'] === 'company' ? 'selected' : '' ?>>商業公司</option>
          </select>
          <?php if ($isCompanyLocked): ?>
            <div class="field-hint lock-hint">法人會員類型需系統管理員權限才可修改。</div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>姓名 <span class="required">*</span></label>
          <input type="text" id="edit-name" value="<?= htmlspecialchars($member['name'] ?? '') ?>" />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>電子郵件 <span class="required">*</span></label>
          <input type="email" id="edit-email" value="<?= htmlspecialchars($member['email'] ?? '') ?>" />
          <?php if (empty($member['email_verified_at'])): ?>
            <div class="email-verification-actions">
              <button type="button" class="btn btn-sm btn-outline" id="resend-email-verification">
                重新發送信箱驗證
              </button>
              <span class="field-hint">會員尚未完成信箱驗證與密碼設定。</span>
            </div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>電話號碼</label>
          <?php $currentPhoneAreaCode = $member['phone_area_code'] ?? ''; ?>
          <div class="phone-input-group">
            <select id="edit-phone-area-code" aria-label="區域號碼">
              <option value="" <?= empty($currentPhoneAreaCode) ? 'selected' : '' ?>>區碼</option>
              <?php foreach (['02' => '02 北北基', '03' => '03 桃竹花宜', '037' => '037 苗栗', '04' => '04 中彰', '049' => '049 南投', '05' => '05 雲嘉', '06' => '06 南市澎湖', '07' => '07 高雄', '08' => '08 屏東', '089' => '089 臺東', '082' => '082 金門', '0826' => '0826 烏坵', '0836' => '0836 馬祖'] as $code => $label): ?>
                <option value="<?= htmlspecialchars($code) ?>" <?= $currentPhoneAreaCode === $code ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
              <?php if ($currentPhoneAreaCode && !in_array($currentPhoneAreaCode, ['02','03','037','04','049','05','06','07','08','089','082','0826','0836'], true)): ?>
                <option value="<?= htmlspecialchars($currentPhoneAreaCode) ?>" selected><?= htmlspecialchars($currentPhoneAreaCode) ?></option>
              <?php endif; ?>
            </select>
            <input type="tel" id="edit-phone" value="<?= htmlspecialchars($member['phone'] ?? '') ?>" />
          </div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>手機電話 <span class="required">*</span></label>
          <input type="tel" id="edit-mobile" maxlength="12" value="<?= htmlspecialchars($member['mobile_phone'] ?? '') ?>" />
          <div class="field-hint">台灣手機號碼，格式如 0912345678 或 0912-345-678。</div>
        </div>
        <div class="form-group">
          <label>聯絡地址 <span class="required">*</span></label>
          <div class="address-input-group">
            <select id="edit-contact-city" data-selected="<?= htmlspecialchars($contactCity, ENT_QUOTES) ?>" aria-label="縣市"></select>
            <select id="edit-contact-district" data-selected="<?= htmlspecialchars($contactDistrict, ENT_QUOTES) ?>" aria-label="地區"></select>
            <input type="text" id="edit-address-line" value="<?= htmlspecialchars($contactAddressLine, ENT_QUOTES) ?>" maxlength="255" />
          </div>
        </div>
      </div>

      <div id="edit-personal-fields">
        <div class="section-label">個人身份資料</div>
        <div class="form-row">
          <div class="form-group">
            <label>身分證號 <span class="required">*</span></label>
            <input type="text" id="edit-idno" maxlength="10" value="<?= htmlspecialchars($member['id_number'] ?? '') ?>" />
          </div>
          <div class="form-group">
            <label>出生日期 <span class="required">*</span></label>
            <input type="text" id="edit-birth" value="<?= htmlspecialchars($member['birth_date_roc'] ?? '') ?>" placeholder="083/05/15" inputmode="numeric" />
            <div class="roc-picker" data-target="edit-birth" aria-label="選擇出生日期"></div>
            <div class="field-hint">民國日期，格式：YYY/MM/DD</div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Line ID</label>
            <input type="text" id="edit-line-id" maxlength="100" value="<?= htmlspecialchars($member['line_id'] ?? '') ?>" />
          </div>
          <div class="form-group">
            <label>LINE 好友</label>
            <?php if (!empty($member['line_id'])): ?>
              <a class="btn btn-outline line-add-friend" href="line://ti/p/~<?= rawurlencode((string) $member['line_id']) ?>">加入 LINE 好友</a>
            <?php else: ?>
              <div class="line-empty">尚未填寫 Line ID</div>
            <?php endif; ?>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>身分證發證日期 <span class="required">*</span></label>
            <input type="text" id="edit-id-issue-date" value="<?= htmlspecialchars($member['id_issue_date_roc'] ?? '') ?>" placeholder="113/01/02" inputmode="numeric" />
            <div class="roc-picker" data-target="edit-id-issue-date" aria-label="選擇身分證發證日期"></div>
            <div class="field-hint">民國日期，格式：YYY/MM/DD</div>
          </div>
          <div class="form-group">
            <label>身分證發證地點 <span class="required">*</span></label>
            <?php
              $currentIssuePlaces = ['基隆市','臺北市','新北市','桃園市','新竹市','新竹縣','苗栗縣','臺中市','彰化縣','南投縣','雲林縣','嘉義市','嘉義縣','臺南市','高雄市','屏東縣','宜蘭縣','花蓮縣','臺東縣','澎湖縣','金門縣','連江縣'];
              $legacyIssuePlaces = ['臺北縣','桃園縣','臺中縣','臺南縣','高雄縣'];
              $issuePlaces = array_merge($currentIssuePlaces, $legacyIssuePlaces);
              $currentIssuePlace = $member['id_issue_place'] ?? '';
            ?>
            <select id="edit-id-issue-place">
              <option value="" <?= empty($currentIssuePlace) ? 'selected' : '' ?>>請選擇發證地點</option>
              <optgroup label="現行縣市">
                <?php foreach ($currentIssuePlaces as $place): ?>
                  <option value="<?= htmlspecialchars($place) ?>" <?= $currentIssuePlace === $place ? 'selected' : '' ?>><?= htmlspecialchars($place) ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="舊制縣名">
                <?php foreach ($legacyIssuePlaces as $place): ?>
                  <option value="<?= htmlspecialchars($place) ?>" <?= $currentIssuePlace === $place ? 'selected' : '' ?>><?= htmlspecialchars($place) ?></option>
                <?php endforeach; ?>
              </optgroup>
              <?php if ($currentIssuePlace && !in_array($currentIssuePlace, $issuePlaces, true)): ?>
                <option value="<?= htmlspecialchars($currentIssuePlace) ?>" selected><?= htmlspecialchars($currentIssuePlace) ?></option>
              <?php endif; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>補領換類別 <span class="required">*</span></label>
            <select id="edit-id-issue-type">
              <option value="" <?= empty($member['id_issue_type']) ? 'selected' : '' ?>>請選擇</option>
              <option value="first" <?= ($member['id_issue_type'] ?? '') === 'first' ? 'selected' : '' ?>>初發</option>
              <option value="replace" <?= ($member['id_issue_type'] ?? '') === 'replace' ? 'selected' : '' ?>>補發</option>
              <option value="renew" <?= ($member['id_issue_type'] ?? '') === 'renew' ? 'selected' : '' ?>>換發</option>
            </select>
          </div>
          <div class="form-group">
            <label>性別</label>
            <select id="edit-gender">
              <option value="" <?= empty($member['gender']) ? 'selected' : '' ?>>請選擇</option>
              <option value="male" <?= ($member['gender'] ?? '') === 'male' ? 'selected' : '' ?>>男</option>
              <option value="female" <?= ($member['gender'] ?? '') === 'female' ? 'selected' : '' ?>>女</option>
              <option value="other" <?= ($member['gender'] ?? '') === 'other' ? 'selected' : '' ?>>不公開</option>
            </select>
          </div>
        </div>
        <div class="document-links">
          <span>身分證電子檔</span>
          <?php if (!empty($member['id_card_front_path'])): ?>
            <button
              type="button"
              class="document-preview-link"
              data-document-title="身分證正面"
              data-document-url="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/api/admin/members/<?= htmlspecialchars((string) $member['id']) ?>/id-documents/front"
            >查看正面</button>
          <?php else: ?>
            <em>未上傳正面</em>
          <?php endif; ?>
          <?php if (!empty($member['id_card_back_path'])): ?>
            <button
              type="button"
              class="document-preview-link"
              data-document-title="身分證反面"
              data-document-url="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/api/admin/members/<?= htmlspecialchars((string) $member['id']) ?>/id-documents/back"
            >查看反面</button>
          <?php else: ?>
            <em>未上傳反面</em>
          <?php endif; ?>
          <?php if (!empty($member['second_id_doc_path'])): ?>
            <button
              type="button"
              class="document-preview-link"
              data-document-title="第二證件"
              data-document-url="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/api/admin/members/<?= htmlspecialchars((string) $member['id']) ?>/id-documents/second"
            >查看第二證件</button>
          <?php else: ?>
            <em>未上傳第二證件</em>
          <?php endif; ?>
        </div>
      </div>

      <div id="edit-company-fields">
        <div class="section-label">公司資料</div>
        <?php if ($isCompanyLocked): ?>
          <div class="field-hint company-lock-notice">統一編號與法人資料僅系統管理員可修改。</div>
        <?php endif; ?>
        <div class="form-row">
          <div class="form-group">
            <label>統一編號 <span class="required">*</span></label>
            <input type="text" id="edit-taxid" maxlength="8" value="<?= htmlspecialchars($member['tax_id'] ?? '') ?>" <?= $companyLockAttr ?> />
          </div>
          <div class="form-group">
            <label>公司名稱 <span class="required">*</span></label>
            <input type="text" id="edit-company" value="<?= htmlspecialchars($member['company_name'] ?? '') ?>" <?= $companyLockAttr ?> />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>公司網站</label>
            <input type="url" id="edit-website" value="<?= htmlspecialchars($member['website'] ?? '') ?>" <?= $companyLockAttr ?> />
          </div>
          <div class="form-group">
            <label>產業類別</label>
            <select id="edit-industry" <?= $companyLockAttr ?>>
              <option value="" <?= empty($member['industry']) ? 'selected' : '' ?>>請選擇</option>
              <option value="tech" <?= ($member['industry'] ?? '') === 'tech' ? 'selected' : '' ?>>科技業</option>
              <option value="mfg" <?= ($member['industry'] ?? '') === 'mfg' ? 'selected' : '' ?>>製造業</option>
              <option value="svc" <?= ($member['industry'] ?? '') === 'svc' ? 'selected' : '' ?>>服務業</option>
              <option value="retail" <?= ($member['industry'] ?? '') === 'retail' ? 'selected' : '' ?>>零售業</option>
              <option value="fin" <?= ($member['industry'] ?? '') === 'fin' ? 'selected' : '' ?>>金融業</option>
              <option value="other" <?= ($member['industry'] ?? '') === 'other' ? 'selected' : '' ?>>其他</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>法人會員身分</label>
          <label class="checkbox-field">
            <input type="checkbox" id="edit-is-dealer" value="1" <?= !empty($member['is_dealer']) ? 'checked' : '' ?> <?= $companyLockAttr ?> />
            <span>經銷商</span>
          </label>
        </div>
      </div>

      <div class="section-label">安全設定</div>
      <div class="form-group">
        <label>新密碼</label>
        <input type="password" id="edit-password" placeholder="不變更請留空" />
      </div>

      <div class="alert alert-success" id="edit-success">會員資料已更新。</div>
      <div class="alert alert-danger" id="edit-error"><span id="edit-error-msg">更新失敗。</span></div>

      <div class="form-actions">
        <a class="btn btn-outline" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin">取消</a>
        <button type="submit" class="btn btn-success">儲存變更</button>
      </div>
  </form>
</div>

<div class="modal-backdrop document-preview-modal" id="document-preview-modal" aria-hidden="true">
  <div class="modal-panel document-preview-panel" role="dialog" aria-modal="true" aria-labelledby="document-preview-title">
    <div class="modal-header">
      <h2 id="document-preview-title">身分證電子檔</h2>
      <button type="button" class="icon-btn" id="document-preview-close" aria-label="關閉">×</button>
    </div>
    <iframe id="document-preview-frame" title="身分證電子檔預覽"></iframe>
    <div class="modal-footer">
      <button type="button" class="btn btn-sm btn-outline" id="document-preview-close-footer">關閉</button>
    </div>
  </div>
</div>
</div>

<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/taiwan-address.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/taiwan-address.js') ?>"></script>
<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/edit-member.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/edit-member.js') ?>"></script>
