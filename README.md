# 會員管理系統

XAMPP 本機網址：

- 前台註冊：`http://localhost/member-system2/register`
- 會員登入：`http://localhost/member-system2/login`
- 會員中心：`http://localhost/member-system2/member`
- 後台管理：`http://localhost/member-system2/admin`
- 管理員帳密：`admin@system.com` / `admin12345`
- 正式站：`https://www.newpay.com.tw/member/`

## 技術架構

| 層級 | 技術 |
|------|------|
| 前端 | HTML / CSS / JavaScript |
| 後端 | 原生 PHP（MVC 架構） |
| 資料庫 | MySQL |

## 目前功能進度

### 前台會員註冊

- 支援個人會員與商業公司註冊。
- 非 Google 帳號註冊已改為邀請制信箱驗證流程：註冊時不輸入密碼，系統寄出驗證信與設定密碼連結。
- 未完成信箱驗證與密碼設定者，狀態為「未驗證信箱」。
- 註冊資料送出後會先彈出提示視窗，提醒會員先收信，10 秒後導向完成頁。
- 個人會員資料包含身分證號、Line ID、身分證發證日期、發證地點、補領換類別、出生日期、聯絡地址、手機電話，性別由身分證字號自動判斷。
- 身分證正反面電子檔上傳，支援 JPG、PNG、PDF，單檔上限 5MB。
- 個人會員需上傳第二證件電子檔，可用有照片的健保卡或駕照，支援 JPG、PNG、PDF，單檔上限 5MB。
- 個人會員需上傳申請人名稱的銀行帳戶封面電子檔。
- 公司會員需上傳公司負責人身分證正反面電子檔、公司登記證書電子檔、公司名稱的銀行帳戶封面電子檔。
- 公司登記證書電子檔可由填表人自行新增上傳欄位，一次註冊最多 6 個電子檔。
- 身分證號支援台灣身分證字號檢核碼驗證，並檢查既有會員是否重複。
- 身分證發證日期與出生日期使用民國日期格式 `YYY/MM/DD`，可點日曆小圖示開啟民國年月曆選擇器。
- 身分證發證地點使用下拉選單，包含現行縣市與五都合併前舊制縣名。
- 支援 Google 帳號註冊流程設定，需於 `.env` 設定 Google OAuth 參數。
- 手機電話支援台灣手機號碼格式檢核。
- 市話支援台灣區域號碼欄位。

### 會員登入與會員中心

- 會員登入頁標題為「新零售行銷多元平台 - NewPay」，並支援 Google 帳號登入入口。
- 登入唯一辨識以 Email 加身分證號或統一編號組合判斷，支援同 Email 同時註冊個人與公司法人。
- 公司法人登入需輸入統一編號；個人會員登入需輸入身分證號。
- 已完成信箱驗證與密碼設定但仍待後台審核的會員可登入會員中心，只能查看基本資料，並顯示「會員資料審核中」。
- 會員登入後上方顯示登入狀態、自動登出閒置倒數與登出選項，預設閒置 10 分鐘。
- 會員帳號支援單一登入，同一身分第二次登入會被阻止，原登入頁面會顯示嘗試登入時間與 IP。
- 會員中心已建立左右兩區版型，左側預先建立「總覽」「會員」「交易動態」「物流中心」「帳戶」「收款管理」「行銷中心」「取得協助」等功能入口。
- 會員功能下已建立帳號設定、權限設定、商店清單、新增商店、通知信設定、審核狀態等子選項。
- 新增商店表單已建立並可送出資料，商店清單可檢視會員已建立商店。
- 商店清單可依全部、啟用、網路商店、實體商店等狀態呈現，並可點選商店名稱進入商店資料頁。

### 後台管理

- 管理員登入、登出。
- 會員列表、統計卡片、篩選、搜尋、分頁。
- 會員列表支援全文搜尋，搜尋框提供清除按鈕。
- 會員列表新增搜尋後序號與「商店數」欄位，商店數大於 0 可點入該會員商店管理區。
- 會員審核、停用、刪除。
- 會員資料可進入單獨編輯頁修改。
- 會員編輯頁可重新發送信箱驗證信，提供未驗證會員重新驗證與設定密碼。
- 會員編輯頁新增商店管理區，列出該會員已建立商店，並顯示商店基本資料。
- 法人會員的統一編號與法人資料除系統管理員外不可修改。
- 經銷商管理比照會員管理製作，只顯示有經銷商旗標的會員。
- 後台列表會提示個人會員身分證號既有資料重複情況。
- 後台可查看身分證正反面電子檔，使用頁內小視窗預覽，不另開頁面。
- 後台可查看與修改 Line ID，並可點「加入 LINE 好友」嘗試開啟電腦上的 LINE。
- 後台上方功能分為會員管理、經銷商管理、內部管理人員。
- 後台左側會依目前模組顯示對應功能，確認目前所在功能。

### 內部管理後台與安全設定

- 後台名稱調整為「內部管理後台」。
- 新增內部管理人員管理介面，支援列表、搜尋、單獨編輯頁、新增、刪除、登入紀錄。
- 新增管理人員不再手動設定密碼，改由系統寄送信箱驗證與設定密碼連結，完成後帳號才正式啟用。
- 管理人員權限改由「群組管理」建立的權限群組作為下拉選單。
- 資訊安全移至左側選單，包含可登入 IP 設定與群組管理。
- 可登入 IP 支援條列式新增、移除與標註原因；未列入全域白名單或個別帳號允許 IP 者不可登入後台。
- 後台管理人員支援閒置自動登出，時間可於後台安全設定中設定。
- 後台登入後顯示自動登出閒置倒數；有操作時會重置倒數。
- 後台管理帳號支援單一登入，第二個相同帳號登入會被阻止，原登入頁會跳出警告並顯示嘗試時間與 IP。
- 管理人員登入紀錄可查看登入時間、登出時間、使用時間與 IP Address。

### 入口網站與部署

- `index.php` 已建立系統服務平台 SaaS 公司入口首頁，保留模板 A。
- 正式站部署目錄為 `/var/www/clients/client1/web1/home/newpay_web/web/member`。
- 正式站已部署至 `https://www.newpay.com.tw/member/`。
- 正式站資料庫 `newpay_member` 已套用商店資料表與會員 session 欄位。
- GitHub `main` 已推送目前版本，最後部署 commit：`8809b4c`。

### XAMPP 部署

- 已支援 `http://localhost/member-system2/register` 與 `http://localhost/member-system2/admin` 這類路徑。
- 根目錄與 `public/` 皆有 rewrite 設定，方便放在 `C:\xampp\htdocs\member-system2`。
- `.env` 會由 `config/env.php` 載入；正式設定請使用本機 `.env`，不要提交到 Git。

## 環境與寄信設定

系統會用 `APP_URL` 產生會員驗證信、會員重寄驗證信、管理人員密碼設定信與 Google OAuth callback 的絕對網址。正式站請設定：

```env
APP_URL=https://www.newpay.com.tw/member
APP_ENV=production
```

本機 XAMPP 請使用：

```env
APP_URL=http://localhost/member-system2
APP_ENV=development
```

SMTP 設定集中於 `.env`，可參考 [docs/env.local.example](docs/env.local.example) 與 [docs/env.production.example](docs/env.production.example)。正式環境請至少確認：

- `MAIL_HOST`、`MAIL_PORT`、`MAIL_USERNAME`、`MAIL_PASSWORD` 使用正式 SMTP 或機密管理服務注入。
- `MAIL_ENCRYPTION=tls` 或 `ssl`，且 `MAIL_VERIFY_PEER=true`。
- `MAIL_EHLO_DOMAIN=www.newpay.com.tw`，避免 SMTP 對話使用 localhost。
- `.env` 不提交 Git，正式密碼、SMTP app password、Google secret 不寫入範例檔或文件。

### 驗證與密碼設定信測試

1. 複製 [docs/env.local.example](docs/env.local.example) 為 `.env`，設定本機 DB 與可用 SMTP。若只測流程可先留空 `MAIL_HOST`，系統會退回 PHP `mail()`，但實際寄達需主機 mail 設定可用。
2. 執行 PHP 語法檢查：`php -l config/app.php`、`php -l app/Core/Mailer.php`、`php -l app/Controllers/MemberController.php`、`php -l app/Controllers/AdminController.php`。
3. 到 `http://localhost/member-system2/register` 註冊非 Google 會員，確認 API 回傳的開發測試連結與信件內容皆為 `APP_URL` 開頭。
4. 進入後台新增管理人員，確認管理員啟用信連結為 `APP_URL/admin/setup-password/{token}`。
5. 在會員編輯頁點擊重新發送信箱驗證，確認信件與開發測試連結為 `APP_URL/verify/{token}`。
6. 正式環境部署前將 `.env` 改用 [docs/env.production.example](docs/env.production.example) 的值，特別確認 `APP_URL=https://www.newpay.com.tw/member` 與 `MAIL_VERIFY_PEER=true`。

## 資料庫更新

既有本機或正式資料庫若已經建立 `member_stores`，請先備份資料庫，再套用：

```sql
database/migrations/20260721_add_member_store_advanced_settings.sql
```

此 migration 會補上商店聯絡市話區碼、API 串接設定、電子發票設定、交易限制與行銷設定欄位；全新安裝可直接使用 [database/schema.sql](database/schema.sql)。

## 目錄結構

```
member-system/
├── public/                     # Web Root（Apache/Nginx 指向此處）
│   ├── index.php               # Front Controller（所有請求入口）
│   ├── .htaccess               # URL Rewrite
│   └── assets/
│       ├── css/style.css
│       └── js/register.js / admin.js / edit-member.js / login.js
├── app/
│   ├── Core/
│   │   ├── Router.php          # 路由核心
│   │   ├── Controller.php      # Controller 基礎類別
│   │   ├── Model.php           # Model 基礎類別（PDO）
│   │   └── Database.php        # PDO 連線（Singleton）
│   ├── Controllers/
│   │   ├── MemberController.php
│   │   ├── MemberAuthController.php
│   │   ├── HomeController.php
│   │   └── AdminController.php
│   ├── Models/
│   │   ├── Member.php
│   │   ├── MemberStore.php
│   │   ├── AdminUser.php
│   │   ├── AdminLoginLog.php
│   │   └── SystemSetting.php
│   └── Views/
│       ├── layouts/
│       │   ├── header.php
│       │   └── footer.php
│       ├── member/register.php
│       ├── member/login.php / dashboard.php / setup-password.php
│       ├── member/verify.php
│       ├── home/index.php
│       └── admin/index.php / edit.php / login.php / staff.php
├── config/
│   ├── app.php                 # 全域常數
│   ├── database.php            # DB 設定
│   ├── env.php                 # .env 載入
│   └── routes.php              # 路由定義
├── database/
│   ├── schema.sql              # 建表 & 測試資料
│   ├── demo-data.sql           # 測試資料補充
│   └── migrations/             # 既有資料庫升級 SQL
├── storage/
│   └── id-documents/           # 身分證電子檔，上傳檔不提交 Git
├── .env.example
├── .htaccess
└── index.php
```

## 快速啟動

```bash
# 1. 建立 .env
cp .env.example .env
# 編輯 .env 填入 DB 資訊

# 2. 建立資料庫
mysql -u root -p < database/schema.sql

# 3. Apache 將 DocumentRoot 指向 public/
# 4. 確認 mod_rewrite 啟用，AllowOverride All
```

## API 路由

| Method | 路徑 | 說明 |
|--------|------|------|
| GET | `/` | 入口網站 |
| GET | `/register` | 會員註冊頁 |
| GET | `/register/complete` | 註冊完成提示頁 |
| POST | `/api/members/register` | 處理註冊 |
| GET | `/verify/{token}` | Email 驗證 |
| GET | `/login` | 會員登入頁 |
| POST | `/api/members/login` | 會員登入 |
| GET | `/member` | 會員中心 |
| GET | `/member/logout` | 會員登出 |
| POST | `/api/members/logout` | 會員登出 API |
| GET | `/api/members/session-status` | 會員 session 狀態 |
| POST | `/api/members/session-touch` | 會員閒置倒數重置 |
| POST | `/api/members/stores` | 新增會員商店 |
| GET | `/auth/google` | Google 註冊開始 |
| GET | `/auth/google/callback` | Google 註冊回呼 |
| GET | `/admin/login` | 後台登入頁 |
| GET | `/admin/setup-password/{token}` | 管理人員設定密碼頁 |
| POST | `/api/admin/login` | 後台登入 |
| POST | `/api/admin/logout` | 後台登出 |
| GET | `/api/admin/session-status` | 後台 session 狀態 |
| POST | `/api/admin/session-touch` | 後台閒置倒數重置 |
| GET | `/admin` | 後台管理頁 |
| GET | `/admin/staff` | 內部管理人員頁 |
| GET | `/admin/staff/{id}/edit` | 管理人員單獨編輯頁 |
| GET | `/admin/members/{id}/edit` | 後台會員編輯頁 |
| GET | `/api/admin/members` | 會員列表 |
| GET | `/api/admin/stats` | 統計 |
| GET | `/api/admin/staff` | 管理人員列表 |
| POST | `/api/admin/staff/create` | 新增管理人員邀請 |
| POST | `/api/admin/staff/{id}/update` | 更新管理人員 |
| POST | `/api/admin/staff/{id}/delete` | 刪除管理人員 |
| GET | `/api/admin/staff/{id}/login-logs` | 管理人員登入紀錄 |
| GET | `/api/admin/settings/security` | 後台安全設定 |
| POST | `/api/admin/settings/security` | 更新後台安全設定 |
| POST | `/api/admin/members/{id}/update` | 更新會員資料 |
| POST | `/api/admin/members/{memberId}/stores/{storeId}/update` | 更新會員商店 |
| POST | `/api/admin/members/{id}/resend-verification` | 重新發送會員信箱驗證 |
| GET | `/api/admin/members/{id}/id-documents/{side}` | 查看身分證電子檔 |
| POST | `/api/admin/members/{id}/approve` | 審核通過 |
| POST | `/api/admin/members/{id}/suspend` | 停用 |
| POST | `/api/admin/members/{id}/delete` | 刪除 |

## 資料庫異動摘要

`members` 目前主要欄位包含：

- 共同欄位：姓名、Email、電話、手機、聯絡地址、密碼、狀態、註冊來源、Google ID。
- 個人會員：身分證號、Line ID、身分證正反面電子檔路徑、第二證件電子檔路徑、銀行帳戶封面電子檔路徑、發證日期、發證地點、補領換類別、出生日期，性別由身分證字號自動判斷。
- 商業公司：統一編號、公司名稱、網站、產業類別、公司負責人身分證正反面電子檔路徑、公司登記證書電子檔路徑 JSON、銀行帳戶封面電子檔路徑。
- 會員編號：`member_code`。
- 經銷商旗標：`is_dealer`。
- 信箱驗證與邀請制密碼設定：`email_verified_token`、`email_verified_at`。
- 單一登入控管：`active_session_id`、`active_session_last_seen_at`、`duplicate_login_attempt_at`、`duplicate_login_attempt_ip`。

新增資料表包含 `admin_users`、`admin_login_logs`、`system_settings`、`member_stores`。

若既有 XAMPP 資料庫尚未更新，可重新匯入 `database/schema.sql`，或依序套用 `database/migrations/` 內的 SQL。
