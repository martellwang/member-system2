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
          <input type="tel" id="f-phone" name="phone" placeholder="0912-345-678" />
        </div>
        <div class="form-group">
          <label>手機電話 <span class="required">*</span></label>
          <input type="tel" id="f-mobile" name="mobile_phone" placeholder="0912-345-678" />
          <div class="error-msg" id="err-mobile">請輸入手機電話</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>聯絡地址 <span class="required">*</span></label>
          <input type="text" id="f-address" name="contact_address" placeholder="請輸入聯絡地址" />
          <div class="error-msg" id="err-address">請輸入聯絡地址</div>
        </div>
        <div class="form-group">
          <label>密碼 <?= $googleSignup ? '' : '<span class="required">*</span>' ?></label>
          <input type="password" id="f-pass" name="password" placeholder="至少 8 位字元" />
          <div class="field-hint"><?= $googleSignup ? 'Google 註冊可留空' : '至少 8 位字元' ?></div>
          <div class="error-msg" id="err-pass">密碼至少需要 8 位字元</div>
        </div>
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
            <label>出生日期</label>
            <input type="text" id="f-birth" name="birth_date" placeholder="083/05/15" inputmode="numeric" />
            <div class="roc-picker" data-target="f-birth" aria-label="選擇出生日期"></div>
            <div class="field-hint">請輸入民國日期，格式：YYY/MM/DD</div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>性別</label>
            <select id="f-gender" name="gender">
              <option value="">請選擇</option>
              <option value="male">男</option>
              <option value="female">女</option>
              <option value="other">不公開</option>
            </select>
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
      </div>

      <button type="button" class="btn btn-primary" onclick="submitRegister()">立即註冊</button>

      <div class="alert alert-success" id="alert-success">✅ 註冊成功！請至信箱收取驗證信，完成帳號啟用。</div>
      <div class="alert alert-danger"  id="alert-error">❌ <span id="alert-error-msg">發生錯誤，請稍後再試。</span></div>
    </form>
  </div>
</div>

<script>const CSRF_TOKEN = '<?= htmlspecialchars($csrfToken) ?>';</script>
<script src="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/assets/js/register.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/register.js') ?>"></script>
