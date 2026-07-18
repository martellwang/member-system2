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
?>

<div class="container edit-container">
  <aside class="admin-member-store-sidebar">
    <div class="card admin-member-store-card">
      <div class="form-title">商店基本資料</div>
      <div class="form-subtitle">此區僅供檢視，不提供編輯。</div>
      <?php if (!$stores): ?>
        <div class="admin-store-readonly-empty">此會員尚未建立商店。</div>
      <?php else: ?>
        <div class="admin-store-readonly-list">
          <?php foreach ($stores as $store): ?>
            <?php
              $storeStatus = $storeStatusMap[$store['status'] ?? 'pending'] ?? $storeStatusMap['pending'];
              $storeAddress = trim(($store['store_city'] ?? '') . ($store['store_district'] ?? '') . ($store['store_address'] ?? ''));
            ?>
            <section class="admin-store-readonly-item">
              <div class="admin-store-readonly-head">
                <strong><?= htmlspecialchars($store['store_name'] ?? '未命名商店', ENT_QUOTES) ?></strong>
                <span class="badge <?= htmlspecialchars($storeStatus['class'], ENT_QUOTES) ?>"><?= htmlspecialchars($storeStatus['label'], ENT_QUOTES) ?></span>
              </div>
              <dl>
                <div>
                  <dt>商店代號</dt>
                  <dd><?= htmlspecialchars($storeCode($store), ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>商店類型</dt>
                  <dd><?= htmlspecialchars($storeTypeLabel($store), ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>電子信箱</dt>
                  <dd><?= htmlspecialchars($store['store_email'] ?: '—', ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>商店電話</dt>
                  <dd><?= htmlspecialchars($store['store_phone'] ?: '—', ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>聯絡人</dt>
                  <dd><?= htmlspecialchars($store['contact_name'] ?: '—', ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>聯絡手機</dt>
                  <dd><?= htmlspecialchars($store['contact_mobile'] ?: '—', ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>商店地址</dt>
                  <dd><?= htmlspecialchars($storeAddress !== '' ? $storeAddress : '—', ENT_QUOTES) ?></dd>
                </div>
                <div>
                  <dt>建立日期</dt>
                  <dd><?= htmlspecialchars(substr((string) ($store['created_at'] ?? ''), 0, 10) ?: '—', ENT_QUOTES) ?></dd>
                </div>
              </dl>
            </section>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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

  <section class="admin-store-management" id="admin-store-management">
    <div class="admin-store-heading">
      <h2>商店管理</h2>
      <span>共 <?= count($stores) ?> 筆商店</span>
    </div>

    <?php if (!$stores): ?>
      <div class="card admin-store-empty">此會員尚未建立商店資料。</div>
    <?php else: ?>
      <div class="admin-store-list">
        <?php foreach ($stores as $store): ?>
          <?php
            $storeStatus = $storeStatusMap[$store['status'] ?? 'pending'] ?? $storeStatusMap['pending'];
            $paymentTools = $decodeJsonList($store['payment_tools'] ?? '[]');
            $storeId = (int) ($store['id'] ?? 0);
          ?>
          <article class="card admin-store-item" data-admin-store-item>
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
              <button type="button" class="btn btn-sm btn-outline" data-admin-store-toggle>編輯</button>
            </div>

            <form class="admin-store-form" data-admin-store-form data-member-id="<?= htmlspecialchars((string) $member['id'], ENT_QUOTES) ?>" data-store-id="<?= $storeId ?>" hidden>
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

  <div class="card">
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
          <input type="text" id="edit-address" value="<?= htmlspecialchars($member['contact_address'] ?? '') ?>" />
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

<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/edit-member.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/edit-member.js') ?>"></script>
