<?php
$stores = $stores ?? [];
$storeTotal = count($stores);
$storeOnlineTotal = count(array_filter($stores, static fn ($store) => ($store['store_type'] ?? '') === 'online'));
$storePhysicalTotal = count(array_filter($stores, static fn ($store) => ($store['store_type'] ?? '') === 'physical'));
$storeActiveTotal = count(array_filter($stores, static fn ($store) => ($store['status'] ?? '') === 'active'));
$storeTypeLabel = static fn (array $store): string => ($store['store_type'] ?? '') === 'physical' ? '實體商店' : '網路商店';
$storeStatusLabel = static fn (array $store): string => match ($store['status'] ?? '') {
    'active' => '啟用',
    'suspended' => '停用',
    'rejected' => '退件',
    default => '待審',
};
$storeStatusClass = static fn (array $store): string => match ($store['status'] ?? '') {
    'active' => 'badge-active',
    'suspended', 'rejected' => 'badge-suspended',
    default => 'badge-pending',
};
$storeCode = static function (array $store): string {
    $prefix = ($store['store_type'] ?? '') === 'physical' ? 'NEDC' : 'NPPA';
    return $prefix . str_pad((string) ($store['id'] ?? 0), 8, '0', STR_PAD_LEFT);
};
$decodeJsonList = static function ($value): array {
    $decoded = json_decode((string) $value, true);
    return is_array($decoded) ? $decoded : [];
};
?>
<div class="member-portal">
  <aside class="member-portal-sidebar" aria-label="會員功能選單">
    <div class="member-portal-logo">NewPay</div>
    <nav class="member-side-menu">
      <a class="member-side-item active" href="#overview" data-member-panel-link="overview">
        <span class="member-side-icon">▣</span>
        <span>總覽</span>
      </a>
      <div class="member-side-group open">
        <button type="button" class="member-side-item member-side-toggle active">
          <span class="member-side-icon">◎</span>
          <span>會員</span>
          <span class="member-side-chevron">⌄</span>
        </button>
        <div class="member-side-children">
          <a class="active" href="#overview" data-member-panel-link="overview">帳號設定</a>
          <a href="#permission-settings" data-member-panel-link="permission-settings">權限設定</a>
          <a href="#store-list" data-member-panel-link="store-list">商店清單</a>
          <a href="#add-store" data-member-panel-link="add-store">新增商店</a>
          <a href="#notification-settings" data-member-panel-link="notification-settings">通知信設定</a>
          <a href="#review-status" data-member-panel-link="review-status">審核狀態</a>
        </div>
      </div>
      <a class="member-side-item disabled" href="#transaction-status">
        <span class="member-side-icon">▤</span>
        <span>交易動態</span>
      </a>
      <a class="member-side-item disabled" href="#logistics">
        <span class="member-side-icon">▥</span>
        <span>物流中心</span>
      </a>
      <a class="member-side-item disabled" href="#account">
        <span class="member-side-icon">▧</span>
        <span>帳戶</span>
      </a>
      <a class="member-side-item disabled" href="#payments">
        <span class="member-side-icon">＄</span>
        <span>收款管理</span>
      </a>
      <a class="member-side-item disabled" href="#marketing">
        <span class="member-side-icon">◇</span>
        <span>行銷中心</span>
      </a>
      <a class="member-side-item disabled" href="#support">
        <span class="member-side-icon">？</span>
        <span>取得協助</span>
      </a>
    </nav>
  </aside>

  <main class="member-portal-main">
    <div class="member-portal-topbar">
      <div>
        <h1>會員中心</h1>
        <p>查看目前帳號資料與審核狀態</p>
      </div>
      <a class="btn btn-outline" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/member/logout">登出</a>
    </div>

    <section class="member-panel active" id="overview" data-member-panel="overview">
      <div class="card member-dashboard-card">
      <div class="member-summary">
        <div>
          <span class="avatar <?= ($member['type'] ?? '') === 'personal' ? 'av-p' : 'av-c' ?>"><?= htmlspecialchars(mb_substr($member['name'] ?? '?', 0, 2, 'UTF-8')) ?></span>
        </div>
        <div>
          <h2><?= htmlspecialchars($member['name'] ?? '') ?></h2>
          <p><?= htmlspecialchars($member['email'] ?? '') ?></p>
        </div>
        <span class="badge <?= ($member['status'] ?? '') === 'active' ? 'badge-active' : (($member['status'] ?? '') === 'suspended' ? 'badge-suspended' : 'badge-pending') ?>">
          <?= ($member['status'] ?? '') === 'active' ? '啟用' : (($member['status'] ?? '') === 'suspended' ? '停用' : '待審') ?>
        </span>
      </div>

      <?php if (($member['status'] ?? '') === 'pending'): ?>
        <div class="member-review-notice" id="review-status">
          <strong>會員資料審核中</strong>
          <span>您的信箱已完成驗證，會員資料目前正在等待後台審核。審核通過前，會員中心僅開放查看基本資料。</span>
        </div>
      <?php endif; ?>

      <div class="section-label">基本資料</div>
      <div class="detail-grid">
        <div><span>會員編號</span><strong><?= htmlspecialchars($member['member_code'] ?? '—') ?></strong></div>
        <div><span>會員類型</span><strong><?= ($member['type'] ?? '') === 'personal' ? '個人會員' : '商業公司' ?></strong></div>
        <div><span>手機電話</span><strong><?= htmlspecialchars($member['mobile_phone'] ?? '—') ?></strong></div>
        <div><span>電話號碼</span><strong><?= htmlspecialchars(($member['phone_area_code'] ? $member['phone_area_code'] . '-' : '') . ($member['phone'] ?: '—')) ?></strong></div>
        <div><span>聯絡地址</span><strong><?= htmlspecialchars($member['contact_address'] ?? '—') ?></strong></div>
        <?php if (($member['type'] ?? '') === 'personal'): ?>
          <div><span>身分證號</span><strong><?= htmlspecialchars($member['id_number'] ?? '—') ?></strong></div>
          <div><span>Line ID</span><strong><?= htmlspecialchars($member['line_id'] ?? '—') ?></strong></div>
        <?php else: ?>
          <div><span>統一編號</span><strong><?= htmlspecialchars($member['tax_id'] ?? '—') ?></strong></div>
          <div><span>公司名稱</span><strong><?= htmlspecialchars($member['company_name'] ?? '—') ?></strong></div>
          <div><span>法人會員身分</span><strong><?= !empty($member['is_dealer']) ? '經銷商' : '一般法人' ?></strong></div>
        <?php endif; ?>
      </div>
      </div>

      <div class="member-feature-grid">
        <div class="card member-feature-card" id="permission-settings">
          <span>會員</span>
          <strong>權限設定</strong>
          <p>後續將在此管理會員可操作的功能權限。</p>
        </div>
        <div class="card member-feature-card" id="store-list-summary">
          <span>會員</span>
          <strong>商店清單</strong>
          <p>後續將在此查看已建立的商店資料。</p>
        </div>
        <div class="card member-feature-card" id="add-store-summary">
          <span>會員</span>
          <strong>新增商店</strong>
          <p>後續將在此建立新的商店申請資料。</p>
        </div>
        <div class="card member-feature-card" id="notification-settings">
          <span>會員</span>
          <strong>通知信設定</strong>
          <p>後續將在此設定交易與系統通知信。</p>
        </div>
      </div>
    </section>

    <section class="member-panel" id="store-list" data-member-panel="store-list">
      <div class="member-store-list-shell">
        <div class="member-store-list-header">
          <h2>商店清單</h2>
          <a class="btn btn-outline btn-sm" href="#add-store" data-member-panel-link="add-store">新增商店</a>
        </div>

        <div class="card member-store-list-card">
          <div class="store-quick-title">商店快捷鍵</div>
          <div class="store-filter-row" aria-label="商店狀態篩選">
            <button type="button" class="store-filter active" data-store-filter="all">
              <span></span> 全部
            </button>
            <button type="button" class="store-filter" data-store-filter="active">
              <span></span> 啟用
            </button>
          </div>

          <div class="store-search-row">
            <label class="store-search-box" for="member-store-search">
              <span>⌕</span>
              <input id="member-store-search" type="search" placeholder="請輸入商店名稱/商店代號" autocomplete="off">
            </label>
            <button type="button" class="btn btn-outline btn-sm">匯出</button>
          </div>

          <div class="store-type-tabs" aria-label="商店類型篩選">
            <button type="button" class="active" data-store-type-tab="all">全部 (<span data-store-count="all"><?= $storeTotal ?></span>)</button>
            <button type="button" data-store-type-tab="online">網路商店 (<span data-store-count="online"><?= $storeOnlineTotal ?></span>)</button>
            <button type="button" data-store-type-tab="physical">實體商店 (<span data-store-count="physical"><?= $storePhysicalTotal ?></span>)</button>
          </div>

          <div class="store-card-grid" id="member-store-card-grid">
            <?php foreach ($stores as $store): ?>
              <?php
                $displayCode = $storeCode($store);
                $searchText = trim(implode(' ', [
                    $displayCode,
                    $store['store_name'] ?? '',
                    $store['store_email'] ?? '',
                    $store['store_type'] ?? '',
                    $store['status'] ?? '',
                ]));
              ?>
              <article
                class="store-list-item"
                data-store-card
                data-store-status="<?= htmlspecialchars($store['status'] ?? 'pending', ENT_QUOTES) ?>"
                data-store-type="<?= htmlspecialchars($store['store_type'] ?? 'online', ENT_QUOTES) ?>"
                data-store-search="<?= htmlspecialchars(mb_strtolower($searchText, 'UTF-8'), ENT_QUOTES) ?>"
              >
                <div class="store-list-thumb" aria-hidden="true">
                  <?= ($store['store_type'] ?? '') === 'physical' ? '店' : '網' ?>
                </div>
                <div class="store-list-body">
                  <div class="store-list-meta">
                    <span><?= htmlspecialchars($storeTypeLabel($store), ENT_QUOTES) ?></span>
                    <em>|</em>
                    <strong><?= htmlspecialchars($displayCode, ENT_QUOTES) ?></strong>
                  </div>
                  <h3>
                    <a href="#store-detail-<?= (int) ($store['id'] ?? 0) ?>" data-member-panel-link="store-detail-<?= (int) ($store['id'] ?? 0) ?>">
                      <?= htmlspecialchars($store['store_name'] ?? '', ENT_QUOTES) ?>
                    </a>
                  </h3>
                  <div class="store-list-foot">
                    <span class="badge <?= $storeStatusClass($store) ?>"><?= htmlspecialchars($storeStatusLabel($store), ENT_QUOTES) ?></span>
                    <small>建立日期：<?= htmlspecialchars(substr((string) ($store['created_at'] ?? ''), 0, 10), ENT_QUOTES) ?></small>
                  </div>
                </div>
                <a class="store-list-arrow" href="#store-detail-<?= (int) ($store['id'] ?? 0) ?>" data-member-panel-link="store-detail-<?= (int) ($store['id'] ?? 0) ?>" aria-label="查看商店">›</a>
              </article>
            <?php endforeach; ?>
          </div>

          <div class="store-empty-state" id="member-store-empty" <?= $stores ? 'hidden' : '' ?>>
            目前尚無商店資料，請先新增商店。
          </div>
        </div>

        <div class="store-list-pagination">
          <span id="member-store-page-info">顯示 1 - <?= max(1, $storeTotal) ?> 筆，共 <?= $storeTotal ?> 筆，跳至 <input type="number" value="1" min="1"> 頁 / 1 頁 <button type="button">GO</button></span>
          <div class="store-page-buttons">
            <button type="button" disabled>‹</button>
            <button type="button" class="active">1</button>
            <button type="button" disabled>›</button>
          </div>
        </div>
      </div>
    </section>

    <?php foreach ($stores as $store): ?>
      <?php
        $displayCode = $storeCode($store);
        $paymentTools = $decodeJsonList($store['payment_tools'] ?? '[]');
        $deliveryRatios = $decodeJsonList($store['delivery_ratios'] ?? '{}');
        $domesticRate = ($deliveryRatios['prepaid'] ?? 0) > 0 ? '費率 2%' : '費率依審核結果';
        $foreignRate = ($deliveryRatios['non_prepaid'] ?? 0) > 0 ? '費率 3.5%' : '費率依審核結果';
      ?>
      <section class="member-panel" id="store-detail-<?= (int) ($store['id'] ?? 0) ?>" data-member-panel="store-detail-<?= (int) ($store['id'] ?? 0) ?>">
        <div class="store-detail-shell">
          <div class="store-detail-breadcrumb">
            <a href="#store-list" data-member-panel-link="store-list">會員</a>
            <span>/</span>
            <a href="#store-list" data-member-panel-link="store-list">商店清單</a>
            <span>/</span>
            <strong>商店資料</strong>
          </div>

          <header class="store-detail-header">
            <div>
              <h2><?= htmlspecialchars($store['store_name'] ?? '', ENT_QUOTES) ?></h2>
              <div class="store-detail-tags">
                <span class="store-code-badge">商店代號：<?= htmlspecialchars($displayCode, ENT_QUOTES) ?></span>
                <span class="badge <?= $storeStatusClass($store) ?>"><?= htmlspecialchars($storeStatusLabel($store), ENT_QUOTES) ?></span>
                <span class="badge <?= ($store['store_type'] ?? '') === 'physical' ? 'badge-company' : 'badge-personal' ?>"><?= htmlspecialchars($storeTypeLabel($store), ENT_QUOTES) ?></span>
              </div>
            </div>
            <a class="btn btn-outline btn-sm" href="#store-list" data-member-panel-link="store-list">返回商店清單</a>
          </header>

          <nav class="store-detail-tabs" aria-label="商店設定選項">
            <button type="button" class="active" data-store-detail-tab="terms">商業條件及支付工具設定</button>
            <button type="button" data-store-detail-tab="integration">串接設定</button>
            <button type="button" data-store-detail-tab="invoice">電子發票</button>
            <button type="button" data-store-detail-tab="details">詳細資訊</button>
            <button type="button" data-store-detail-tab="limit">交易限制設定</button>
            <button type="button" data-store-detail-tab="marketing">行銷工具設定</button>
          </nav>

          <div class="store-detail-tab-panel active" data-store-detail-tab-panel="terms">
            <div class="store-detail-section-title">
              <h3>商業條件</h3>
              <button type="button" class="btn btn-outline btn-sm">展開全部</button>
            </div>

            <div class="store-payment-section">
              <h4>信用卡</h4>
              <div class="store-payment-grid">
                <?php
                  $cardTools = ['一次付清（國內卡）', '一次付清（國外卡）', '銀聯卡', '分期付款', 'Apple Pay', 'Google Pay', 'Samsung Pay'];
                  foreach ($cardTools as $tool):
                    $enabled = in_array($tool, $paymentTools, true);
                ?>
                  <div class="store-payment-card">
                    <strong><?= htmlspecialchars($tool, ENT_QUOTES) ?></strong>
                    <span class="badge <?= $enabled ? 'badge-active' : 'badge-pending' ?>"><?= $enabled ? '啟用' : '未申請' ?></span>
                    <p><?= str_contains($tool, '國外') ? htmlspecialchars($foreignRate, ENT_QUOTES) : htmlspecialchars($domesticRate, ENT_QUOTES) ?>　撥款天期 7 天</p>
                    <button type="button" aria-label="展開">⌄</button>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="store-payment-section">
              <h4>非即時支付</h4>
              <div class="store-payment-grid compact">
                <?php foreach (['超商代碼', 'ATM 轉帳'] as $tool): ?>
                  <?php $enabled = in_array($tool, $paymentTools, true); ?>
                  <div class="store-payment-card">
                    <strong><?= htmlspecialchars($tool, ENT_QUOTES) ?></strong>
                    <span class="badge <?= $enabled ? 'badge-active' : 'badge-pending' ?>"><?= $enabled ? '啟用' : '未申請' ?></span>
                    <p><?= $tool === 'ATM 轉帳' ? '費率 1%' : '費率 25元' ?>　撥款天期 7 天</p>
                    <button type="button" aria-label="展開">⌄</button>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="store-payment-section">
              <h4>電子錢包</h4>
              <div class="store-payment-grid">
                <?php foreach (['icash Pay', 'LINE Pay', '街口支付'] as $tool): ?>
                  <?php $enabled = in_array($tool, $paymentTools, true); ?>
                  <div class="store-payment-card">
                    <strong><?= htmlspecialchars($tool, ENT_QUOTES) ?></strong>
                    <span class="badge <?= $enabled ? 'badge-active' : 'badge-pending' ?>"><?= $enabled ? '啟用' : '未申請' ?></span>
                    <p>費率及撥款天期請於後台審核後確認</p>
                    <button type="button" aria-label="展開">⌄</button>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="store-detail-setting-grid">
              <h4>ATM 轉帳設定</h4>
              <div>
                <label>帳號類型</label>
                <strong>單繳帳號</strong>
              </div>
              <h4>信用卡設定</h4>
              <div>
                <label>請款設定</label>
                <span class="radio-line"><label><input type="radio" checked> 自動請款</label><label><input type="radio"> 手動請款</label></span>
              </div>
              <h4>3D 設定</h4>
              <div class="store-detail-lines">
                <p>一次付清(國內卡)、分期 <strong>關閉 3D</strong></p>
                <p>一次付清(國外卡) <strong>關閉 3D</strong></p>
                <p>銀聯卡 <strong>關閉 3D</strong></p>
              </div>
            </div>
          </div>

          <div class="store-detail-tab-panel" data-store-detail-tab-panel="integration">
            <?php
              $integrationFlags = [
                'integration_credit_card_api_enabled' => '信用卡簽後授權 API',
                'integration_refund_api_enabled' => '退款 API',
                'integration_token_api_enabled' => '信用卡 Token API',
                'integration_non_card_refund_api_enabled' => '非信用卡退款 API',
                'integration_logistics_refund_api_enabled' => '物流簽後 API',
                'integration_linepay_refund_api_enabled' => 'LINE Pay 簽後 API',
                'integration_member_free_api_enabled' => '免脈轉支付元件',
                'integration_discount_refund_api_enabled' => '優惠券全額折抵簽後 API',
                'integration_street_payment_refund_api_enabled' => '街口支付簽後 API',
              ];
            ?>
            <form class="store-integration-settings" data-store-integration-form data-store-id="<?= (int) ($store['id'] ?? 0) ?>">
              <section class="integration-section">
                <div class="integration-section-heading"><h3>API 串接金鑰</h3><span>商店代號：<strong><?= htmlspecialchars($displayCode, ENT_QUOTES, 'UTF-8') ?></strong></span></div>
                <div class="integration-grid">
                  <div class="form-group"><label>Hash Key</label><input type="text" name="integration_hash_key" maxlength="255" value="<?= htmlspecialchars((string) ($store['integration_hash_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off"></div>
                  <div class="form-group"><label>IV Key</label><input type="text" name="integration_iv_key" maxlength="255" value="<?= htmlspecialchars((string) ($store['integration_iv_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off"></div>
                </div>
              </section>
              <section class="integration-section">
                <h3>API URL</h3>
                <div class="integration-grid">
                  <div class="form-group"><label>Notify URL</label><input type="url" name="integration_notify_url" maxlength="255" value="<?= htmlspecialchars((string) ($store['integration_notify_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://example.com/notify"></div>
                  <div class="form-group"><label>Return URL</label><input type="url" name="integration_return_url" maxlength="255" value="<?= htmlspecialchars((string) ($store['integration_return_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://example.com/return"></div>
                </div>
              </section>
              <section class="integration-section">
                <div class="integration-section-heading"><h3>串接模式</h3><label class="switch-control"><input type="checkbox" name="integration_test_mode" value="1" <?= !empty($store['integration_test_mode']) ? 'checked' : '' ?>><span class="switch-control-track" aria-hidden="true"></span><span>測試模式</span></label></div>
                <div class="integration-api-grid">
                  <?php foreach ($integrationFlags as $field => $label): ?>
                    <label class="integration-api-item"><span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span><span class="switch-control"><input type="checkbox" name="<?= $field ?>" value="1" <?= !isset($store[$field]) || !empty($store[$field]) ? 'checked' : '' ?>><span class="switch-control-track" aria-hidden="true"></span><small>啟用</small></span></label>
                  <?php endforeach; ?>
                </div>
              </section>
              <section class="integration-section">
                <h3>限定 API 之 IP 設定</h3>
                <p class="form-hint">每行輸入一個 IP 或 CIDR，例如 34.80.239.196 或 192.168.1.0/24；留白代表不限制來源 IP。</p>
                <textarea name="integration_allowed_ips" maxlength="2000" rows="4" placeholder="例如 34.80.239.196&#10;192.168.1.0/24"><?= htmlspecialchars((string) ($store['integration_allowed_ips'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
              </section>
              <div class="store-invoice-actions"><span class="store-invoice-message" data-integration-message role="status"></span><button type="submit" class="btn btn-primary">儲存設定</button></div>
            </form>
          </div>

          <div class="store-detail-tab-panel" data-store-detail-tab-panel="invoice">
            <form class="store-invoice-settings" data-store-invoice-form data-store-id="<?= (int) ($store['id'] ?? 0) ?>">
              <div class="store-invoice-topbar">
                <strong>電子發票設定</strong>
                <label class="switch-control">
                  <input type="checkbox" name="e_invoice_enabled" value="1" <?= !empty($store['e_invoice_enabled']) ? 'checked' : '' ?> data-invoice-enabled>
                  <span class="switch-control-track" aria-hidden="true"></span>
                  <span>啟用</span>
                </label>
              </div>

              <section class="store-invoice-section">
                <h3>電子發票加值中心</h3>
                <div class="store-invoice-field-row">
                  <div class="form-group">
                    <label for="invoice-center-<?= (int) ($store['id'] ?? 0) ?>">加值中心</label>
                    <select id="invoice-center-<?= (int) ($store['id'] ?? 0) ?>" name="e_invoice_center">
                      <option value="">請選擇電子發票加值中心</option>
                      <option value="光貿電子發票加值中心" <?= ($store['e_invoice_center'] ?? '') === '光貿電子發票加值中心' ? 'selected' : '' ?>>光貿電子發票加值中心</option>
                    </select>
                  </div>
                  <button type="button" class="btn btn-dark btn-sm" data-invoice-action="register">前往註冊</button>
                </div>
                <p class="form-hint invoice-center-hint">完成註冊後，可向加值中心查詢 APP KEY。</p>
                <button type="button" class="btn btn-dark btn-sm" data-invoice-action="app-key">查詢 APP KEY</button>
              </section>

              <section class="store-invoice-section">
                <h3>預設發票捐贈單位 <span class="info-mark" title="可設定預設捐贈碼">i</span></h3>
                <div class="store-invoice-field-row">
                  <input type="text" name="e_invoice_gift_unit" maxlength="30" value="<?= htmlspecialchars((string) ($store['e_invoice_gift_unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="請輸入發票捐贈單位">
                  <button type="button" class="btn btn-dark btn-sm" data-invoice-action="gift-unit">前往查詢捐贈碼</button>
                </div>
              </section>

              <section class="store-invoice-section">
                <div class="store-invoice-setting-row">
                  <h3>自動開立 <span class="info-mark" title="設定付款完成後的開立時間">i</span></h3>
                  <label class="switch-control">
                    <input type="checkbox" name="e_invoice_auto_issue" value="1" <?= !isset($store['e_invoice_auto_issue']) || $store['e_invoice_auto_issue'] ? 'checked' : '' ?> data-invoice-auto>
                    <span class="switch-control-track" aria-hidden="true"></span>
                    <span>啟用</span>
                  </label>
                  <div class="invoice-issue-options">
                    <label><input type="radio" name="e_invoice_issue_mode_<?= (int) ($store['id'] ?? 0) ?>" value="instant" checked> 即時開立：於付款完成或信用卡請款成功當日開立電子發票。</label>
                    <label><input type="radio" name="e_invoice_issue_mode_<?= (int) ($store['id'] ?? 0) ?>" value="delayed"> 延後開立：付款完成或信用卡請款成功日起
                      <select name="e_invoice_delay_days" data-invoice-delay <?= !empty($store['e_invoice_auto_issue']) || !isset($store['e_invoice_auto_issue']) ? 'disabled' : '' ?>>
                        <option value="">請選擇</option>
                        <?php for ($days = 1; $days <= 30; $days++): ?>
                          <option value="<?= $days ?>" <?= (int) ($store['e_invoice_delay_days'] ?? 0) === $days ? 'selected' : '' ?>><?= $days ?></option>
                        <?php endfor; ?>
                      </select> 日開立電子發票。
                    </label>
                  </div>
                </div>
              </section>

              <div class="store-invoice-actions">
                <span class="store-invoice-message" data-invoice-message role="status"></span>
                <button type="submit" class="btn btn-primary">儲存設定</button>
              </div>
            </form>
          </div>

          <div class="store-detail-tab-panel" data-store-detail-tab-panel="details">
            <div class="store-detail-reference-layout">
              <section class="store-detail-reference-section">
                <h3>基本資料</h3>
                <div class="store-detail-reference-grid">
                  <div><span>商店代號</span><strong><?= htmlspecialchars($displayCode, ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>商店類型</span><strong><?= htmlspecialchars($storeTypeLabel($store), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>商店名稱</span><strong><?= htmlspecialchars((string) ($store['store_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>國外卡英文帳單名稱</span><strong><?= htmlspecialchars((string) ($store['foreign_statement_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                </div>
              </section>

              <section class="store-detail-reference-section">
                <h3>販售商品資訊</h3>
                <div class="store-detail-reference-grid">
                  <div><span>產業類別</span><strong><?= htmlspecialchars((string) ($store['industry'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>販售商品類型</span><strong><?= htmlspecialchars((string) ($store['product_type'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>履約保證類型</span><strong><?= htmlspecialchars((string) ($store['guarantee_type'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>商品交付完成期間</span><strong><?= htmlspecialchars(trim((string) ($store['delivery_period'] ?? '0') . ' ' . (string) ($store['delivery_unit'] ?? '個月')), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>履約保證說明</span><strong><?= htmlspecialchars((string) ($store['guarantee_note'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>販售商品平均客單價</span><strong><?= htmlspecialchars((string) ($store['average_order_amount'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>商店網址</span><strong><?= $store['store_url'] ? '<a href="' . htmlspecialchars((string) $store['store_url'], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars((string) $store['store_url'], ENT_QUOTES, 'UTF-8') . '</a>' : '—' ?></strong></div>
                  <div class="store-detail-reference-wide"><span>商店營運說明</span><strong><?= htmlspecialchars((string) ($store['store_description'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                </div>
              </section>

              <section class="store-detail-reference-section">
                <h3>聯繫資訊</h3>
                <div class="store-detail-reference-grid">
                  <div><span>聯絡人中文姓名</span><strong><?= htmlspecialchars((string) ($store['contact_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>商店 E-Mail</span><strong><?= htmlspecialchars((string) ($store['store_email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>商店電話</span><strong><?= htmlspecialchars((string) ($store['store_phone'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>商店傳真</span><strong><?= htmlspecialchars((string) ($store['store_fax'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>聯絡人電話</span><strong><?= htmlspecialchars(trim((string) ($store['contact_phone_area_code'] ?? '') . ' ' . (string) ($store['contact_phone'] ?? '')) ?: '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div><span>聯絡人行動電話</span><strong><?= htmlspecialchars((string) ($store['contact_mobile'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></div>
                  <div class="store-detail-reference-wide"><span>商店地址</span><strong><?= htmlspecialchars(trim((string) ($store['store_city'] ?? '') . ' ' . (string) ($store['store_district'] ?? '') . ' ' . (string) ($store['store_address'] ?? '')) ?: '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
                </div>
              </section>
            </div>

            <div class="card store-detail-info-card legacy-details-card">
              <h3>詳細資訊</h3>
              <div class="detail-grid">
                <div><span>商店名稱</span><strong><?= htmlspecialchars($store['store_name'] ?? '—', ENT_QUOTES) ?></strong></div>
                <div><span>商店電子信箱</span><strong><?= htmlspecialchars($store['store_email'] ?? '—', ENT_QUOTES) ?></strong></div>
                <div><span>商店電話</span><strong><?= htmlspecialchars($store['store_phone'] ?: '—', ENT_QUOTES) ?></strong></div>
                <div><span>聯絡人</span><strong><?= htmlspecialchars($store['contact_name'] ?? '—', ENT_QUOTES) ?></strong></div>
                <div><span>聯絡人手機</span><strong><?= htmlspecialchars($store['contact_mobile'] ?: '—', ENT_QUOTES) ?></strong></div>
                <div><span>商店地址</span><strong><?= htmlspecialchars(trim(($store['store_city'] ?? '') . ($store['store_district'] ?? '') . ($store['store_address'] ?? '')) ?: '—', ENT_QUOTES) ?></strong></div>
                <div><span>產業類別</span><strong><?= htmlspecialchars($store['industry'] ?? '—', ENT_QUOTES) ?></strong></div>
                <div><span>販售商品類型</span><strong><?= htmlspecialchars($store['product_type'] ?? '—', ENT_QUOTES) ?></strong></div>
              </div>
            </div>
          </div>

          <div class="store-detail-tab-panel" data-store-detail-tab-panel="limit">
            <form class="store-transaction-settings" data-store-transaction-form data-store-id="<?= (int) ($store['id'] ?? 0) ?>">
              <div class="transaction-setting-card transaction-setting-summary">
                <div>
                  <h3>開放交易金額上限</h3>
                  <p>可依商店需求啟用交易金額上限控管。</p>
                </div>
                <label class="switch-control">
                  <input type="checkbox" name="transaction_amount_limit_enabled" value="1" <?= !isset($store['transaction_amount_limit_enabled']) || $store['transaction_amount_limit_enabled'] ? 'checked' : '' ?>>
                  <span class="switch-control-track" aria-hidden="true"></span><span>啟用</span>
                </label>
              </div>
              <div class="transaction-setting-card transaction-setting-summary">
                <div><h3>開放交易金額下限</h3><p>交易金額下限將於後續功能開發。</p></div>
              </div>
              <div class="transaction-setting-card transaction-setting-summary">
                <div><h3>逾期請退款申請</h3><p>可設定是否接受逾期退款申請。</p></div>
                <label class="switch-control">
                  <input type="checkbox" name="expired_refund_enabled" value="1" <?= !empty($store['expired_refund_enabled']) ? 'checked' : '' ?>><span class="switch-control-track" aria-hidden="true"></span><span>啟用</span>
                </label>
              </div>
              <div class="transaction-mode-card">
                <label>信用卡交易限制</label>
                <?php $cardMode = $store['transaction_card_limit_mode'] ?? 'off'; ?>
                <label><input type="radio" name="transaction_card_limit_mode" value="off" <?= $cardMode === 'off' ? 'checked' : '' ?>> 關閉信用卡交易限制</label>
                <label><input type="radio" name="transaction_card_limit_mode" value="blacklist" <?= $cardMode === 'blacklist' ? 'checked' : '' ?>> 啟用信用卡黑名單模式</label>
                <label><input type="radio" name="transaction_card_limit_mode" value="whitelist" <?= $cardMode === 'whitelist' ? 'checked' : '' ?>> 啟用信用卡白名單模式</label>
              </div>
              <div class="transaction-mode-card">
                <label>IP 交易限制</label>
                <?php $ipMode = $store['transaction_ip_limit_mode'] ?? 'off'; ?>
                <label><input type="radio" name="transaction_ip_limit_mode" value="off" <?= $ipMode === 'off' ? 'checked' : '' ?>> 關閉 IP 交易限制</label>
                <label><input type="radio" name="transaction_ip_limit_mode" value="blacklist" <?= $ipMode === 'blacklist' ? 'checked' : '' ?>> 啟用 IP 黑名單模式</label>
                <label><input type="radio" name="transaction_ip_limit_mode" value="whitelist" <?= $ipMode === 'whitelist' ? 'checked' : '' ?>> 啟用 IP 白名單模式</label>
              </div>
              <div class="store-invoice-actions"><span class="store-invoice-message" data-transaction-message role="status"></span><button type="submit" class="btn btn-primary">儲存設定</button></div>
            </form>
          </div>

          <div class="store-detail-tab-panel" data-store-detail-tab-panel="marketing">
            <div class="card store-detail-info-card">
              <h3>行銷工具設定</h3>
              <p>優惠券、活動與行銷工具設定將於後續功能開發。</p>
            </div>
          </div>
        </div>
      </section>
    <?php endforeach; ?>

    <section class="member-panel" id="add-store" data-member-panel="add-store">
      <form class="member-store-form" id="member-store-form" novalidate>
        <div class="member-form-note"><span>*</span> 為必填欄位</div>

        <div class="member-form-section">
          <aside>
            <h2>商店基本資訊</h2>
          </aside>
          <div class="member-form-fields">
            <div class="form-group form-group-wide">
              <label><span class="required">*</span> 商店類型</label>
              <div class="radio-line">
                <label><input type="radio" name="store_type" value="online" checked> 網路商店</label>
                <label><input type="radio" name="store_type" value="physical"> 實體商店</label>
              </div>
            </div>
            <div class="form-group">
              <label><span class="required">*</span> 商店名稱</label>
              <input type="text" name="store_name">
            </div>
            <div class="form-group">
              <label><span class="required">*</span> 商店電子信箱</label>
              <input type="email" name="store_email">
            </div>
            <div class="form-group">
              <label><span class="required">*</span> 國外卡英文帳單名稱</label>
              <input type="text" name="foreign_statement_name">
            </div>
            <div class="form-group">
              <label>商店電話</label>
              <input type="text" name="store_phone">
              <div class="field-hint">長度 20 碼可接受符號。</div>
            </div>
            <div class="form-group">
              <label>商店傳真號碼</label>
              <input type="text" name="store_fax">
              <div class="field-hint">長度 20 碼可接受符號。</div>
            </div>
            <div class="form-group form-group-wide">
              <label><span class="required">*</span> 商店聯絡地址</label>
              <div class="store-address-row">
                <select id="member-store-city" name="store_city" aria-label="縣市">
                  <option value="">縣市別</option>
                  <option>臺北市</option>
                  <option>新北市</option>
                  <option>桃園市</option>
                  <option>臺中市</option>
                  <option>臺南市</option>
                  <option>高雄市</option>
                </select>
                <select id="member-store-district" name="store_district" aria-label="行政區">
                  <option value="">行政區</option>
                  <option>中正區</option>
                  <option>大安區</option>
                  <option>信義區</option>
                  <option>板橋區</option>
                  <option>三重區</option>
                  <option>桃園區</option>
                  <option>西屯區</option>
                  <option>安平區</option>
                  <option>前鎮區</option>
                </select>
                <input type="text" name="store_address" placeholder="路/段/巷/弄/號">
              </div>
            </div>
            <div class="form-group">
              <label><span class="required">*</span> 聯絡人名稱</label>
              <input type="text" name="contact_name">
            </div>
            <div class="form-group">
                <label><span class="required" data-contact-required="mobile">*</span> 聯絡人手機號碼</label>
              <input type="text" name="contact_mobile" inputmode="tel" placeholder="0912-345-678">
              <div class="field-hint">手機或市話擇一填寫。</div>
            </div>
            <div class="form-group">
                <label><span class="required" data-contact-required="phone">*</span> 聯絡人電話</label>
              <div class="phone-input-group contact-phone-group">
                <select name="contact_phone_area_code" aria-label="聯絡人電話區碼">
                  <option value="">區碼</option>
                  <?php foreach (['02' => '02 北北基', '03' => '03 桃竹花宜', '037' => '037 苗栗', '04' => '04 中彰', '049' => '049 南投', '05' => '05 雲嘉', '06' => '06 南市澎湖', '07' => '07 高雄', '08' => '08 屏東', '089' => '089 臺東', '082' => '082 金門', '0826' => '0826 烏坵', '0836' => '0836 馬祖'] as $code => $label): ?>
                    <option value="<?= $code ?>"><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="text" name="contact_phone" inputmode="tel" placeholder="例如 1234567">
              </div>
              <div class="field-hint">手機或市話擇一填寫；市話請選區碼並輸入 6 至 8 碼。</div>
            </div>
          </div>
        </div>

        <div class="member-form-section">
          <aside>
            <h2>販售商品資訊</h2>
          </aside>
          <div class="member-form-fields">
            <div class="form-group">
              <label><span class="required">*</span> 產業類別</label>
              <select name="industry">
                <option value="">請選擇</option>
                <option>零售業</option>
                <option>服務業</option>
                <option>餐飲業</option>
                <option>科技業</option>
                <option>教育服務</option>
              </select>
            </div>
            <div class="form-group">
              <label><span class="required">*</span> 販售商品類型</label>
              <select name="product_type">
                <option>服務</option>
                <option>實體商品</option>
                <option>數位商品</option>
                <option>票券</option>
              </select>
            </div>
            <div class="form-group form-group-wide">
              <label><span class="required">*</span> 商品交付型態占比</label>
              <p class="form-warning">自動占比總和 0%，請設定各類比例如總值加為 100%</p>
              <div class="ratio-grid">
                <label>預付 <input type="number" name="ratio_prepaid" min="0" max="100" value="0"> %</label>
                <label>非預付 <input type="number" name="ratio_non_prepaid" min="0" max="100" value="100"> %</label>
                <label>儲值遞延 <input type="number" name="ratio_deferred" min="0" max="100" value="0"> %</label>
                <label>票券 <input type="number" name="ratio_voucher" min="0" max="100" value="0"> %</label>
              </div>
            </div>
            <div class="form-group form-group-wide reserve-box">
              <a href="#reserve-info">請填寫您的履約保證資訊：</a>
              <div class="form-row">
                <div class="form-group">
                  <label><span class="required">*</span> 履約保證類型</label>
                  <select name="guarantee_type">
                    <option>無</option>
                    <option>信託</option>
                    <option>保證保險</option>
                  </select>
                </div>
                <div class="form-group">
                  <label><span class="required">*</span> 商品交付完成期間</label>
                  <div class="inline-input">
                    <input type="number" name="delivery_period" value="0" min="0">
                    <select name="delivery_unit"><option>個月</option><option>日</option></select>
                  </div>
                </div>
              </div>
              <label class="field-label"><span class="required">*</span> 履約保證說明</label>
              <div class="radio-stack">
                <label><input type="radio" name="guarantee_note_type" value="not_required" checked> 非預付商品不須履約保證</label>
                <label><input type="radio" name="guarantee_note_type" value="other"> 其他：</label>
              </div>
              <textarea name="guarantee_note" maxlength="100"></textarea>
              <div class="char-count">0 / 100</div>
            </div>
            <div class="form-group">
              <label><span class="required">*</span> 販售商品平均客單價</label>
              <input type="text" name="average_order_amount" value="NT$ 0">
            </div>
            <div class="form-group form-group-wide">
              <label><span class="required">*</span> 商店網址</label>
              <div class="radio-stack">
                <label><input type="radio" name="store_url_type" value="url" checked> 請輸入販售商品網址</label>
                <input id="member-store-url" class="store-url-input" type="url" name="store_url" maxlength="255" autocomplete="url" placeholder="請輸入 https:// 或 http:// 開頭之有效網址">
                <label><input type="radio" name="store_url_type" value="none"> 無網址</label>
              </div>
            </div>
            <div class="form-group form-group-wide">
              <label><span class="required">*</span> 商店營運說明</label>
              <textarea name="store_description" maxlength="500"></textarea>
              <div class="char-count">0 / 500</div>
            </div>
          </div>
        </div>

        <div class="member-form-section">
          <aside>
            <h2>選擇預設啟用的支付工具</h2>
            <ul class="form-help-list">
              <li>至少啟用一種支付工具</li>
              <li>後續如需變更請由後台審核</li>
              <li>審核時間約 3-5 個工作天</li>
            </ul>
          </aside>
          <div class="payment-tool-grid">
            <div class="payment-tool-card">
              <h3>信用卡</h3>
              <?php foreach (['一次付清', '國外卡', '銀聯卡', 'Apple Pay', 'Google Pay', 'Samsung Pay'] as $tool): ?>
                <label class="switch-row"><span><?= htmlspecialchars($tool, ENT_QUOTES) ?></span><input type="checkbox" name="payment_tools[]" value="<?= htmlspecialchars($tool, ENT_QUOTES) ?>"><em>不啟用</em></label>
              <?php endforeach; ?>
            </div>
            <div class="payment-tool-card">
              <h3>信用卡分期付款</h3>
              <?php foreach (['分期付款-3期', '分期付款-6期', '分期付款-9期', '分期付款-12期', '分期付款-18期', '分期付款-24期', '分期付款-30期'] as $tool): ?>
                <label class="switch-row"><span><?= htmlspecialchars($tool, ENT_QUOTES) ?></span><input type="checkbox" name="payment_tools[]" value="<?= htmlspecialchars($tool, ENT_QUOTES) ?>"><em>不啟用</em></label>
              <?php endforeach; ?>
            </div>
            <div class="payment-tool-card">
              <h3>非即時支付工具</h3>
              <?php foreach (['ATM 轉帳', '超商代碼'] as $tool): ?>
                <label class="switch-row"><span><?= htmlspecialchars($tool, ENT_QUOTES) ?></span><input type="checkbox" name="payment_tools[]" value="<?= htmlspecialchars($tool, ENT_QUOTES) ?>"><em>不啟用</em></label>
              <?php endforeach; ?>
            </div>
            <div class="payment-tool-card">
              <h3>其他閘道</h3>
              <?php foreach (['icash Pay', 'LINE Pay', '街口支付', 'AFTEE'] as $tool): ?>
                <label class="switch-row"><span><?= htmlspecialchars($tool, ENT_QUOTES) ?></span><input type="checkbox" name="payment_tools[]" value="<?= htmlspecialchars($tool, ENT_QUOTES) ?>"><em>不啟用</em></label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="member-form-actions">
          <div class="member-store-message" id="member-store-message" aria-live="polite"></div>
          <button type="submit" class="btn btn-primary">新增商店</button>
        </div>
      </form>
    </section>
  </main>
</div>

<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/taiwan-address.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/taiwan-address.js') ?>"></script>
<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/member-dashboard.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/member-dashboard.js') ?>"></script>
