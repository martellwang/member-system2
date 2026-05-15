<?php
$csrfToken = bin2hex(random_bytes(32));
$assetBase = $basePath ?? '';
?>
<div class="container">
  <div class="card">
    <div class="form-title">會員註冊</div>
    <div class="form-subtitle">請先選擇會員類型，表單會顯示該類型需要填寫的資料。</div>

    <div class="type-switch" role="tablist" aria-label="會員類型">
      <button class="type-btn active" id="btn-personal" type="button" role="tab" aria-selected="true" aria-controls="personal-fields" onclick="switchType('personal')">個人用戶</button>
      <button class="type-btn" id="btn-company" type="button" role="tab" aria-selected="false" aria-controls="company-fields" onclick="switchType('company')">商業公司</button>
    </div>

    <form id="register-form" novalidate>
      <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>" />
      <input type="hidden" id="member-type" name="type" value="personal" />

      <div class="section-label">基本資料</div>
      <div class="form-row">
        <div class="form-group">
          <label for="f-name">姓名 / 聯絡人 <span class="required">*</span></label>
          <input type="text" id="f-name" name="name" placeholder="請輸入姓名或聯絡人" required />
          <div class="error-msg" id="err-name">請輸入姓名或聯絡人</div>
        </div>
        <div class="form-group">
          <label for="f-email">電子信箱 <span class="required">*</span></label>
          <input type="email" id="f-email" name="email" placeholder="example@mail.com" required />
          <div class="error-msg" id="err-email">請輸入正確的電子信箱</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="f-phone">聯絡電話</label>
          <input type="tel" id="f-phone" name="phone" placeholder="0912-345-678" />
        </div>
        <div class="form-group">
          <label for="f-pass">密碼 <span class="required">*</span></label>
          <input type="password" id="f-pass" name="password" placeholder="至少 8 個字元" required minlength="8" />
          <div class="error-msg" id="err-pass">密碼至少需要 8 個字元</div>
        </div>
      </div>

      <div id="personal-fields" data-member-panel="personal">
        <div class="section-label">個人用戶資料</div>
        <div class="form-group">
          <label for="f-idno">身分證字號 <span class="required">*</span></label>
          <input type="text" id="f-idno" name="id_number" placeholder="A123456789" maxlength="10" style="text-transform:uppercase" required />
          <div class="error-msg" id="err-idno">請輸入正確的身分證字號，例如 A123456789</div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="f-birth">出生日期</label>
            <input type="date" id="f-birth" name="birth_date" />
          </div>
          <div class="form-group">
            <label for="f-gender">性別</label>
            <select id="f-gender" name="gender">
              <option value="">請選擇</option>
              <option value="male">男</option>
              <option value="female">女</option>
              <option value="other">其他</option>
            </select>
          </div>
        </div>
      </div>

      <div id="company-fields" data-member-panel="company" hidden>
        <div class="section-label">商業公司資料</div>
        <div class="form-row">
          <div class="form-group">
            <label for="f-taxid">統一編號 <span class="required">*</span></label>
            <input type="text" id="f-taxid" name="tax_id" placeholder="12345678" maxlength="8" inputmode="numeric" />
            <div class="error-msg" id="err-taxid">統一編號必須是 8 位數字</div>
          </div>
          <div class="form-group">
            <label for="f-company">公司名稱 <span class="required">*</span></label>
            <input type="text" id="f-company" name="company_name" placeholder="請輸入公司名稱" />
            <div class="error-msg" id="err-company">請輸入公司名稱</div>
          </div>
        </div>
        <div class="form-group">
          <label for="f-website">公司網站</label>
          <input type="url" id="f-website" name="website" placeholder="https://www.example.com" />
        </div>
        <div class="form-group">
          <label for="f-industry">產業類別</label>
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

      <button type="submit" class="btn btn-primary">送出註冊</button>

      <div class="alert alert-success" id="alert-success">註冊成功，請等待管理員審核。</div>
      <div class="alert alert-danger" id="alert-error"><span id="alert-error-msg">註冊失敗，請稍後再試。</span></div>
    </form>
  </div>
</div>

<script>const CSRF_TOKEN = '<?= htmlspecialchars($csrfToken) ?>';</script>
<script src="<?= htmlspecialchars($assetBase) ?>/assets/js/register.js"></script>
