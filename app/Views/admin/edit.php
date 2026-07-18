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

<div class="container edit-container">
  <div class="edit-page-header">
    <div>
      <a class="back-link" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/admin">← 返回會員列表</a>
      <h1>編輯會員</h1>
      <p><?= htmlspecialchars($member['name']) ?> · <?= htmlspecialchars($member['email']) ?></p>
    </div>
    <span class="badge <?= $member['status'] === 'active' ? 'badge-active' : ($member['status'] === 'suspended' ? 'badge-suspended' : 'badge-pending') ?>">
      <?= $member['status'] === 'active' ? '啟用' : ($member['status'] === 'suspended' ? '停用' : '待審') ?>
    </span>
  </div>

  <div class="card">
    <form id="member-edit-page-form" novalidate>
      <input type="hidden" id="edit-id" value="<?= htmlspecialchars((string) $member['id']) ?>" />

      <div class="section-label">基本資料</div>
      <div class="form-row">
        <div class="form-group">
          <label>會員類型 <span class="required">*</span></label>
          <select id="edit-type" onchange="switchEditType(this.value)">
            <option value="personal" <?= $member['type'] === 'personal' ? 'selected' : '' ?>>個人用戶</option>
            <option value="company" <?= $member['type'] === 'company' ? 'selected' : '' ?>>商業公司</option>
          </select>
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
        </div>
        <div class="form-group">
          <label>電話號碼</label>
          <input type="tel" id="edit-phone" value="<?= htmlspecialchars($member['phone'] ?? '') ?>" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>手機電話 <span class="required">*</span></label>
          <input type="tel" id="edit-mobile" value="<?= htmlspecialchars($member['mobile_phone'] ?? '') ?>" />
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
            <label>出生日期</label>
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
        <div class="form-row">
          <div class="form-group">
            <label>統一編號 <span class="required">*</span></label>
            <input type="text" id="edit-taxid" maxlength="8" value="<?= htmlspecialchars($member['tax_id'] ?? '') ?>" />
          </div>
          <div class="form-group">
            <label>公司名稱 <span class="required">*</span></label>
            <input type="text" id="edit-company" value="<?= htmlspecialchars($member['company_name'] ?? '') ?>" />
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>公司網站</label>
            <input type="url" id="edit-website" value="<?= htmlspecialchars($member['website'] ?? '') ?>" />
          </div>
          <div class="form-group">
            <label>產業類別</label>
            <select id="edit-industry">
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
