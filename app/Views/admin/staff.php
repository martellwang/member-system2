<div class="container-wide">
  <div class="admin-header">
    <div>
      <a class="back-link" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin">← 返回會員管理</a>
      <h1>內部管理人員</h1>
    </div>
    <div class="admin-actions">
      <span class="admin-info">管理員：<?= htmlspecialchars($_SESSION['admin']['email'] ?? ADMIN_EMAIL) ?></span>
      <button class="btn btn-sm btn-outline" onclick="logoutAdmin()">登出</button>
    </div>
  </div>

  <div class="staff-layout">
    <?php $staffMenuMode = 'accounts'; require BASE_PATH . '/app/Views/admin/partials/staff-menu.php'; ?>

    <div class="staff-main">
      <div class="card staff-form-card staff-add-panel" id="staff-add-panel" hidden>
        <div class="form-title" id="staff-form-title">新增管理人員</div>
        <div class="form-subtitle">系統公司內部使用，新增後可登入後台。</div>

        <form id="staff-form" novalidate>
          <div class="form-group">
            <label>姓名 <span class="required">*</span></label>
            <input type="text" id="staff-name" />
          </div>
          <div class="form-group">
            <label>登入帳號 Email <span class="required">*</span></label>
            <input type="email" id="staff-email" autocomplete="username" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>權限 <span class="required">*</span></label>
              <select id="staff-role">
                <option value="">請先選擇權限群組</option>
                <?php foreach (($permissionGroups ?? []) as $group): ?>
                  <option value="<?= htmlspecialchars($group['name'] ?? '', ENT_QUOTES) ?>"><?= htmlspecialchars($group['name'] ?? '') ?></option>
                <?php endforeach; ?>
              </select>
              <div class="field-hint">選項來源為「資訊安全 > 群組管理」建立的群組。</div>
            </div>
            <div class="form-group" id="staff-status-group" hidden>
              <label>狀態 <span class="required">*</span></label>
              <select id="staff-status">
                <option value="active">啟用</option>
                <option value="suspended">停用</option>
              </select>
            </div>
          </div>
          <div class="field-hint invite-hint">
            新增後系統會寄出信箱認證與設定密碼連結；完成密碼設定後，該帳號才會正式啟用。
          </div>

          <div class="section-label">安全設定</div>
          <div class="form-group">
            <label>允許登入 IP Address</label>
            <textarea id="staff-allowed-ips" rows="4" placeholder="127.0.0.1&#10;192.168.1.0/24"></textarea>
            <div class="field-hint">空白代表不限 IP；可一行一筆，支援單一 IP 或 CIDR。</div>
          </div>

          <div class="alert alert-success" id="staff-success">已儲存。</div>
          <div class="alert alert-danger" id="staff-error"><span id="staff-error-msg">儲存失敗。</span></div>

          <div class="form-actions">
            <button type="button" class="btn btn-outline" id="staff-cancel">返回表列</button>
            <button type="submit" class="btn btn-success">儲存管理人員</button>
          </div>
        </form>
      </div>

      <div class="table-card staff-table-card" id="staff-list-panel">
        <div class="table-toolbar">
          <span class="muted" id="staff-count">資料載入中...</span>
          <div class="search-control">
            <label for="staff-search">全文搜尋</label>
            <div class="search-input-wrap">
              <input type="search" class="search-box" id="staff-search" placeholder="搜尋管理人員..." autocomplete="off" />
              <button type="button" class="search-clear" id="staff-search-clear" aria-label="清除搜尋內容" title="清除搜尋內容">×</button>
            </div>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>管理人員</th>
              <th>權限</th>
              <th>狀態</th>
              <th>安全設定</th>
              <th>最近登入</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody id="staff-tbody">
            <tr><td colspan="6" class="empty-cell">資料載入中...</td></tr>
          </tbody>
        </table>
      </div>

      <div class="card staff-form-card" id="security-settings">
          <div class="form-title">系統安全設定</div>
          <div class="form-subtitle">設定後台管理人員閒置自動登出，以及總後台可登入 IP 白名單。</div>
          <form id="security-settings-form" novalidate>
            <div class="form-group">
              <label>閒置自動登出時間（分鐘）</label>
              <input type="number" id="security-timeout-minutes" min="1" max="1440" step="1" value="<?= (int) ($adminTimeoutMinutes ?? 30) ?>" />
              <div class="field-hint">可設定 1 到 1440 分鐘。</div>
            </div>
            <div class="section-label">可登入 IP 設定</div>
            <div class="security-ip-panel">
              <div class="security-ip-add">
                <input type="text" id="security-ip-input" placeholder="例如 127.0.0.1、::1、192.168.1.0/24" autocomplete="off" />
                <input type="text" id="security-ip-note" placeholder="標註原因，例如 總公司、機房、VPN" autocomplete="off" />
                <button type="button" class="btn btn-outline" id="security-ip-add">新增 IP</button>
              </div>
              <div class="field-hint">未列於此處的 IP 不可登入總後台；個別管理人員資料內的允許登入 IP 可作為額外例外。</div>
              <ul class="security-ip-list" id="security-ip-list"></ul>
              <div class="empty-cell security-ip-empty" id="security-ip-empty">尚未設定全域可登入 IP。建議至少保留 127.0.0.1 與 ::1 供本機測試。</div>
            </div>
            <div class="alert alert-success" id="security-success">安全設定已更新。</div>
            <div class="alert alert-danger" id="security-error"><span id="security-error-msg">更新失敗。</span></div>
            <div class="form-actions">
              <button type="submit" class="btn btn-success">儲存安全設定</button>
            </div>
          </form>
        </div>

      <div class="card staff-form-card" id="group-settings" hidden>
        <div class="form-title">群組管理</div>
        <div class="form-subtitle">設定不同名稱的管理群組，以及各群組可以執行的後台權限。</div>
        <form id="group-settings-form" novalidate>
          <div class="form-group">
            <label>群組名稱</label>
            <input type="text" id="group-name-input" placeholder="例如 客服人員、審核人員、資訊安全人員" autocomplete="off" />
          </div>
          <div class="section-label">可執行權限</div>
          <div class="permission-grid" id="permission-options">
            <label class="checkbox-field"><input type="checkbox" value="member.view" /> <span>查看會員資料</span></label>
            <label class="checkbox-field"><input type="checkbox" value="member.edit" /> <span>編輯會員資料</span></label>
            <label class="checkbox-field"><input type="checkbox" value="member.review" /> <span>審核 / 停用會員</span></label>
            <label class="checkbox-field"><input type="checkbox" value="member.delete" /> <span>刪除會員</span></label>
            <label class="checkbox-field"><input type="checkbox" value="dealer.view" /> <span>查看經銷商</span></label>
            <label class="checkbox-field"><input type="checkbox" value="dealer.edit" /> <span>編輯經銷商旗標</span></label>
            <label class="checkbox-field"><input type="checkbox" value="security.ip" /> <span>管理可登入 IP</span></label>
            <label class="checkbox-field"><input type="checkbox" value="staff.manage" /> <span>管理內部管理帳號</span></label>
            <label class="checkbox-field"><input type="checkbox" value="group.manage" /> <span>管理群組權限</span></label>
          </div>
          <div class="form-actions group-add-actions">
            <button type="button" class="btn btn-outline" id="group-add">新增群組</button>
            <button type="button" class="btn btn-outline" id="group-cancel-edit" hidden>取消編輯</button>
          </div>
          <div class="group-list" id="group-list"></div>
          <div class="empty-cell group-empty" id="group-empty">尚未建立群組。</div>
          <div class="alert alert-success" id="group-success">群組設定已更新。</div>
          <div class="alert alert-danger" id="group-error"><span id="group-error-msg">群組設定更新失敗。</span></div>
          <div class="form-actions">
            <button type="submit" class="btn btn-success">儲存群組設定</button>
          </div>
        </form>
      </div>

      <div class="card staff-form-card" id="device-management" hidden>
        <div class="form-title">設備管理</div>
        <div class="form-subtitle">後續將在此管理設備、設備分組與維護紀錄。</div>
        <div class="device-banner-grid">
          <button type="button" class="device-banner active" data-device-banner="overview">
            <strong>設備總覽</strong>
            <span>查看設備數量、啟用狀態與異常摘要</span>
          </button>
          <button type="button" class="device-banner" data-device-banner="list">
            <strong>設備清單</strong>
            <span>查詢、檢視與編輯既有設備資料</span>
          </button>
          <button type="button" class="device-banner" data-device-banner="create">
            <strong>新增設備</strong>
            <span>建立新的設備資料與綁定資訊</span>
          </button>
          <button type="button" class="device-banner" data-device-banner="groups">
            <strong>設備群組</strong>
            <span>依部門、地點或用途設定設備群組</span>
          </button>
          <button type="button" class="device-banner" data-device-banner="maintenance">
            <strong>維護紀錄</strong>
            <span>記錄保養、故障、送修與更換歷程</span>
          </button>
        </div>
        <div class="device-panel-placeholder">
          <h3 id="device-panel-title">設備總覽</h3>
          <p id="device-panel-copy">請先從上方選擇設備功能，後續可在此接續開發完整表單與列表。</p>
          <div class="device-group-tab-row" aria-label="設備群組功能">
            <button type="button" class="device-group-tab active" data-device-group-tab="list">群組列表</button>
            <button type="button" class="device-group-tab" data-device-group-tab="create">新增群組</button>
          </div>
          <div class="device-group-panel" id="device-group-panel" aria-live="polite"></div>
        </div>
      </div>

      <div class="card staff-form-card" id="store-code-management" hidden>
        <div class="form-title">商店代號管理</div>
        <div class="form-subtitle">後續可在此管理商店代號規則、產生紀錄與商店代號狀態。</div>
        <div class="store-code-tab-row">
          <button type="button" class="store-code-tab active" data-store-code-tab="prefix">前置碼設定</button>
          <button type="button" class="store-code-tab" data-store-code-tab="list">商店代號列表</button>
        </div>
        <div class="store-code-panel" id="store-code-panel" aria-live="polite">
        </div>
      </div>

      <div class="card staff-form-card" id="payment-upstream-management" hidden>
        <div class="form-title">金流上游管理</div>
        <div class="form-subtitle">後續可在此建立金流上游相關功能選項。</div>
        <div class="payment-upstream-option-row" aria-label="金流上游功能選項"></div>
        <div class="payment-upstream-panel" aria-live="polite"></div>
      </div>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="staff-login-log-modal" aria-hidden="true">
  <div class="modal-panel staff-login-log-panel" role="dialog" aria-modal="true" aria-labelledby="staff-login-log-title">
    <div class="modal-header">
      <h2 id="staff-login-log-title">登入紀錄</h2>
      <button type="button" class="icon-btn" id="staff-login-log-close" aria-label="關閉">×</button>
    </div>
    <table>
      <thead>
        <tr>
          <th>登入時間</th>
          <th>登出時間</th>
          <th>使用時間</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody id="staff-login-log-tbody">
        <tr><td colspan="4" class="empty-cell">資料載入中...</td></tr>
      </tbody>
    </table>
    <div class="modal-footer">
      <button type="button" class="btn btn-sm btn-outline" id="staff-login-log-close-footer">關閉</button>
    </div>
  </div>
</div>

<script>
  const CURRENT_ADMIN_ID = <?= (int) ($_SESSION['admin']['id'] ?? 0) ?>;
</script>
<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/admin-staff.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/admin-staff.js') ?>"></script>
