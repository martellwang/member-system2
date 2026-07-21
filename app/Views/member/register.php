<?php
$csrfToken = bin2hex(random_bytes(32));
$googleSignup = $googleSignup ?? null;
?>
<div class="container">
  <div class="card">
    <div class="form-title">會員註冊</div>
    <div class="form-subtitle">請選擇您的會員類型並填寫以下資料</div>

    <?php if ($googleSignup): ?>
      <div class="alert alert-success show">已連結 Google 帳號：<?= htmlspecialchars($googleSignup['email']) ?></div>
    <?php else: ?>
      <a class="btn btn-google" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/auth/google">使用 Google 帳號註冊</a>
      <div class="divider-text">或填寫下列表單</div>
    <?php endif; ?>

    <div class="type-switch">
      <button class="type-btn active" id="btn-personal" onclick="switchType('personal')">👤 個人用戶</button>
      <button class="type-btn" id="btn-company" onclick="switchType('company')">🏢 商業公司</button>
    </div>

    <form id="register-form" novalidate>
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>" />
      <input type="hidden" id="member-type" value="personal" />
      <input type="hidden" id="f-google-id" value="<?= htmlspecialchars($googleSignup['google_id'] ?? '') ?>" />

      <div class="section-label">基本資料</div>
      <div class="form-row">
        <div class="form-group">
          <label>姓名 <span class="required">*</span></label>
          <input type="text" id="f-name" name="name" placeholder="請輸入姓名" value="<?= htmlspecialchars($googleSignup['name'] ?? '') ?>" />
          <div class="error-msg" id="err-name">請輸入姓名</div>
        </div>
        <div class="form-group">
          <label>電子郵件 <span class="required">*</span></label>
          <input type="email" id="f-email" name="email" placeholder="example@mail.com" value="<?= htmlspecialchars($googleSignup['email'] ?? '') ?>" <?= $googleSignup ? 'readonly' : '' ?> />
          <div class="error-msg" id="err-email">請輸入有效的電子郵件</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>電話號碼</label>
          <div class="phone-input-group">
            <select id="f-phone-area-code" name="phone_area_code" aria-label="區域號碼">
              <option value="">區碼</option>
              <option value="02">02 北北基</option>
              <option value="03">03 桃竹花宜</option>
              <option value="037">037 苗栗</option>
              <option value="04">04 中彰</option>
              <option value="049">049 南投</option>
              <option value="05">05 雲嘉</option>
              <option value="06">06 南市澎湖</option>
              <option value="07">07 高雄</option>
              <option value="08">08 屏東</option>
              <option value="089">089 臺東</option>
              <option value="082">082 金門</option>
              <option value="0826">0826 烏坵</option>
              <option value="0836">0836 馬祖</option>
            </select>
            <input type="tel" id="f-phone" name="phone" placeholder="1234-5678" />
          </div>
          <div class="field-hint">市話選填，請先選區域號碼。</div>
        </div>
        <div class="form-group">
          <label>手機電話 <span class="required">*</span></label>
          <input type="tel" id="f-mobile" name="mobile_phone" placeholder="0912-345-678" maxlength="12" />
          <div class="field-hint">請輸入台灣手機號碼，格式如 0912345678 或 0912-345-678。</div>
          <div class="error-msg" id="err-mobile">請輸入有效的台灣手機號碼</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group <?= $googleSignup ? 'form-group-wide' : '' ?>">
          <label>聯絡地址 <span class="required">*</span></label>
          <div class="address-input-group">
            <select id="f-contact-city" name="contact_city" aria-label="縣市"></select>
            <select id="f-contact-district" name="contact_district" aria-label="地區"></select>
            <input type="text" id="f-address-line" name="contact_address_line" placeholder="請輸入地址" maxlength="255" />
          </div>
          <div class="error-msg" id="err-address">請完整選擇縣市、地區並輸入地址</div>
        </div>
        <?php if (!$googleSignup): ?>
          <div class="form-group">
            <label>信箱驗證</label>
            <div class="readonly-note">送出註冊後，系統會寄出信箱驗證與設定密碼連結。</div>
          </div>
        <?php endif; ?>
      </div>

      <!-- 個人欄位 -->
      <div id="personal-fields">
        <div class="section-label">個人身份資料</div>
        <div class="form-group">
          <label>身分證號 <span class="required">*</span></label>
          <input type="text" id="f-idno" name="id_number" placeholder="A123456789" maxlength="10" style="text-transform:uppercase" />
          <div class="error-msg" id="err-idno">請輸入有效的身分證號（含檢核碼）</div>
        </div>
        <div class="form-group">
          <label>Line ID</label>
          <input type="text" id="f-line-id" name="line_id" placeholder="請輸入 Line ID" maxlength="100" />
          <div class="field-hint">選填，後台可用來加入 LINE 好友。</div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>身分證發證日期 <span class="required">*</span></label>
            <input type="text" id="f-id-issue-date" name="id_issue_date" placeholder="113/01/02" inputmode="numeric" />
            <div class="roc-picker" data-target="f-id-issue-date" aria-label="選擇身分證發證日期"></div>
            <div class="field-hint">請輸入民國日期，格式：YYY/MM/DD</div>
            <div class="error-msg" id="err-id-issue-date">請輸入有效的民國發證日期</div>
          </div>
          <div class="form-group">
            <label>身分證發證地點 <span class="required">*</span></label>
            <select id="f-id-issue-place" name="id_issue_place">
              <option value="">請選擇發證地點</option>
              <optgroup label="現行縣市">
                <option value="基隆市">基隆市</option>
                <option value="臺北市">臺北市</option>
                <option value="新北市">新北市</option>
                <option value="桃園市">桃園市</option>
                <option value="新竹市">新竹市</option>
                <option value="新竹縣">新竹縣</option>
                <option value="苗栗縣">苗栗縣</option>
                <option value="臺中市">臺中市</option>
                <option value="彰化縣">彰化縣</option>
                <option value="南投縣">南投縣</option>
                <option value="雲林縣">雲林縣</option>
                <option value="嘉義市">嘉義市</option>
                <option value="嘉義縣">嘉義縣</option>
                <option value="臺南市">臺南市</option>
                <option value="高雄市">高雄市</option>
                <option value="屏東縣">屏東縣</option>
                <option value="宜蘭縣">宜蘭縣</option>
                <option value="花蓮縣">花蓮縣</option>
                <option value="臺東縣">臺東縣</option>
                <option value="澎湖縣">澎湖縣</option>
                <option value="金門縣">金門縣</option>
                <option value="連江縣">連江縣</option>
              </optgroup>
              <optgroup label="舊制縣名">
                <option value="臺北縣">臺北縣</option>
                <option value="桃園縣">桃園縣</option>
                <option value="臺中縣">臺中縣</option>
                <option value="臺南縣">臺南縣</option>
                <option value="高雄縣">高雄縣</option>
              </optgroup>
            </select>
            <div class="error-msg" id="err-id-issue-place">請選擇身分證發證地點</div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>補領換類別 <span class="required">*</span></label>
            <select id="f-id-issue-type" name="id_issue_type">
              <option value="">請選擇</option>
              <option value="first">初發</option>
              <option value="replace">補發</option>
              <option value="renew">換發</option>
            </select>
            <div class="error-msg" id="err-id-issue-type">請選擇身分證補領換類別</div>
          </div>
          <div class="form-group">
            <label>出生日期 <span class="required">*</span></label>
            <input type="text" id="f-birth" name="birth_date" placeholder="083/05/15" inputmode="numeric" />
            <div class="roc-picker" data-target="f-birth" aria-label="選擇出生日期"></div>
            <div class="field-hint">請輸入民國日期，格式：YYY/MM/DD</div>
            <div class="error-msg" id="err-birth">請輸入有效的民國出生日期</div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>身分證正面電子檔 <span class="required">*</span></label>
            <input type="file" id="f-id-front" name="id_card_front" accept=".jpg,.jpeg,.png,.pdf" />
            <div class="field-hint">支援 JPG、PNG、PDF，單檔上限 5MB</div>
            <div class="error-msg" id="err-id-front">請上傳身分證正面電子檔</div>
          </div>
          <div class="form-group">
            <label>身分證反面電子檔 <span class="required">*</span></label>
            <input type="file" id="f-id-back" name="id_card_back" accept=".jpg,.jpeg,.png,.pdf" />
            <div class="field-hint">支援 JPG、PNG、PDF，單檔上限 5MB</div>
            <div class="error-msg" id="err-id-back">請上傳身分證反面電子檔</div>
          </div>
        </div>
        <div class="form-group">
          <label>第二證件電子檔 <span class="required">*</span></label>
          <input type="file" id="f-second-id-doc" name="second_id_doc" accept=".jpg,.jpeg,.png,.pdf" />
          <div class="field-hint">可用第二證件：有照片的健保卡或駕照。支援 JPG、PNG、PDF，單檔上限 5MB。</div>
          <div class="error-msg" id="err-second-id-doc">請上傳第二證件電子檔</div>
        </div>
        <div class="form-group">
          <label>申請人名稱的銀行帳戶封面電子檔 <span class="required">*</span></label>
          <input type="file" id="f-personal-bank-book" name="bank_book_cover" accept=".jpg,.jpeg,.png,.pdf" />
          <div class="field-hint">請上傳申請人本人銀行帳戶封面。支援 JPG、PNG、PDF，單檔上限 5MB。</div>
          <div class="error-msg" id="err-personal-bank-book">請上傳申請人名稱的銀行帳戶封面電子檔</div>
        </div>
      </div>

      <!-- 公司欄位 -->
      <div id="company-fields" style="display:none;">
        <div class="section-label">公司資料</div>
        <div class="form-row">
          <div class="form-group">
            <label>統一編號 <span class="required">*</span></label>
            <input type="text" id="f-taxid" name="tax_id" placeholder="12345678" maxlength="8" />
            <div class="error-msg" id="err-taxid">統一編號為 8 碼數字</div>
          </div>
          <div class="form-group">
            <label>公司名稱 <span class="required">*</span></label>
            <input type="text" id="f-company" name="company_name" placeholder="○○股份有限公司" />
            <div class="error-msg" id="err-company">請輸入公司名稱</div>
          </div>
        </div>
        <div class="form-group">
          <label>公司網站網址</label>
          <input type="url" id="f-website" name="website" placeholder="https://www.example.com" />
        </div>
        <div class="form-group">
          <label>產業類別</label>
          <select id="f-industry" name="industry">
            <option value="">請選擇</option>
            <option value="tech">科技業</option>
            <option value="mfg">製造業</option>
            <option value="svc">服務業</option>
            <option value="retail">零售業</option>
            <option value="fin">金融業</option>
            <option value="other">其他</option>
          </select>
        </div>
        <div class="form-group">
          <label>法人會員身分</label>
          <label class="checkbox-field">
            <input type="checkbox" id="f-is-dealer" name="is_dealer" value="1" />
            <span>經銷商</span>
          </label>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>公司負責人身分證正面電子檔 <span class="required">*</span></label>
            <input type="file" id="f-company-owner-id-front" name="company_owner_id_card_front" accept=".jpg,.jpeg,.png,.pdf" />
            <div class="field-hint">支援 JPG、PNG、PDF，單檔上限 5MB</div>
            <div class="error-msg" id="err-company-owner-id-front">請上傳公司負責人身分證正面電子檔</div>
          </div>
          <div class="form-group">
            <label>公司負責人身分證反面電子檔 <span class="required">*</span></label>
            <input type="file" id="f-company-owner-id-back" name="company_owner_id_card_back" accept=".jpg,.jpeg,.png,.pdf" />
            <div class="field-hint">支援 JPG、PNG、PDF，單檔上限 5MB</div>
            <div class="error-msg" id="err-company-owner-id-back">請上傳公司負責人身分證反面電子檔</div>
          </div>
        </div>
        <div class="form-group">
          <label>公司登記證書電子檔 <span class="required">*</span></label>
          <div id="company-registration-docs" class="upload-list">
            <div class="upload-list-item">
              <input type="file" class="company-registration-doc" name="company_registration_docs[]" accept=".jpg,.jpeg,.png,.pdf" />
            </div>
          </div>
          <button type="button" class="btn btn-secondary btn-sm upload-add-btn" id="add-company-registration-doc">新增上傳文件</button>
          <div class="field-hint">可上傳多份公司登記證書，最多 6 個電子檔。支援 JPG、PNG、PDF，單檔上限 5MB。</div>
          <div class="error-msg" id="err-company-registration-docs">請上傳 1 至 6 份公司登記證書電子檔</div>
        </div>
        <div class="form-group">
          <label>公司名稱的銀行帳戶封面電子檔 <span class="required">*</span></label>
          <input type="file" id="f-company-bank-book" name="bank_book_cover" accept=".jpg,.jpeg,.png,.pdf" />
          <div class="field-hint">請上傳公司名稱銀行帳戶封面。支援 JPG、PNG、PDF，單檔上限 5MB。</div>
          <div class="error-msg" id="err-company-bank-book">請上傳公司名稱的銀行帳戶封面電子檔</div>
        </div>
      </div>

      <button type="button" class="btn btn-primary" onclick="submitRegister()">立即註冊</button>

      <div class="alert alert-success" id="alert-success">✅ 註冊資料已送出，請至信箱完成驗證並設定密碼。</div>
      <div class="alert alert-danger"  id="alert-error">❌ <span id="alert-error-msg">發生錯誤，請稍後再試。</span></div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="register-success-modal" hidden>
  <div class="mini-modal" role="dialog" aria-modal="true" aria-labelledby="register-success-title">
    <div class="result-icon ok">✓</div>
    <div class="form-title" id="register-success-title">註冊資料已送出</div>
    <div class="form-subtitle" id="register-success-message">
      請先至電子郵件信箱收取驗證信，完成信箱驗證與密碼設定。
    </div>
    <div class="countdown-note">
      <strong id="register-success-countdown">10</strong> 秒後前往註冊資料填寫完成頁
    </div>
    <div class="modal-action-row">
      <button type="button" class="btn btn-primary" id="register-success-next">立即前往</button>
    </div>
  </div>
</div>

<script>const CSRF_TOKEN = '<?= htmlspecialchars($csrfToken) ?>';</script>
<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/taiwan-address.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/taiwan-address.js') ?>"></script>
<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/register.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/register.js') ?>"></script>
