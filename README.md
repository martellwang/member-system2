# 會員管理系統

XAMPP 本機網址：

- 前台註冊：`http://localhost/member-system2/register`
- 後台管理：`http://localhost/member-system2/admin`
- 管理員帳密：`admin@system.com` / `admin12345`

## 技術架構

| 層級 | 技術 |
|------|------|
| 前端 | HTML / CSS / JavaScript |
| 後端 | 原生 PHP（MVC 架構） |
| 資料庫 | MySQL |

## 目前功能進度

### 前台會員註冊

- 支援個人會員與商業公司註冊。
- 個人會員資料包含身分證號、Line ID、身分證發證日期、發證地點、補領換類別、出生日期、性別、聯絡地址、手機電話。
- 身分證正反面電子檔上傳，支援 JPG、PNG、PDF，單檔上限 5MB。
- 身分證號支援台灣身分證字號檢核碼驗證，並檢查既有會員是否重複。
- 身分證發證日期與出生日期使用民國日期格式 `YYY/MM/DD`，可點日曆小圖示開啟民國年月曆選擇器。
- 身分證發證地點使用下拉選單，包含現行縣市與五都合併前舊制縣名。
- 支援 Google 帳號註冊流程設定，需於 `.env` 設定 Google OAuth 參數。

### 後台管理

- 管理員登入、登出。
- 會員列表、統計卡片、篩選、搜尋、分頁。
- 會員審核、停用、刪除。
- 會員資料可進入單獨編輯頁修改。
- 後台列表會提示個人會員身分證號既有資料重複情況。
- 後台可查看身分證正反面電子檔，使用頁內小視窗預覽，不另開頁面。
- 後台可查看與修改 Line ID，並可點「加入 LINE 好友」嘗試開啟電腦上的 LINE。

### XAMPP 部署

- 已支援 `http://localhost/member-system2/register` 與 `http://localhost/member-system2/admin` 這類路徑。
- 根目錄與 `public/` 皆有 rewrite 設定，方便放在 `C:\xampp\htdocs\member-system2`。
- `.env` 會由 `config/env.php` 載入；正式設定請使用本機 `.env`，不要提交到 Git。

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
│   │   └── AdminController.php
│   ├── Models/
│   │   └── Member.php
│   └── Views/
│       ├── layouts/
│       │   ├── header.php
│       │   └── footer.php
│       ├── member/register.php
│       ├── member/verify.php
│       └── admin/index.php / edit.php / login.php
├── config/
│   ├── app.php                 # 全域常數
│   ├── database.php            # DB 設定
│   ├── env.php                 # .env 載入
│   └── routes.php              # 路由定義
├── database/
│   ├── schema.sql              # 建表 & 測試資料
│   └── seed_sample.sql         # 測試資料補充
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
| GET | `/register` | 會員註冊頁 |
| POST | `/api/members/register` | 處理註冊 |
| GET | `/verify/{token}` | Email 驗證 |
| GET | `/auth/google` | Google 註冊開始 |
| GET | `/auth/google/callback` | Google 註冊回呼 |
| GET | `/admin/login` | 後台登入頁 |
| POST | `/api/admin/login` | 後台登入 |
| POST | `/api/admin/logout` | 後台登出 |
| GET | `/admin` | 後台管理頁 |
| GET | `/admin/members/{id}/edit` | 後台會員編輯頁 |
| GET | `/api/admin/members` | 會員列表 |
| GET | `/api/admin/stats` | 統計 |
| POST | `/api/admin/members/{id}/update` | 更新會員資料 |
| GET | `/api/admin/members/{id}/id-documents/{side}` | 查看身分證電子檔 |
| POST | `/api/admin/members/{id}/approve` | 審核通過 |
| POST | `/api/admin/members/{id}/suspend` | 停用 |
| POST | `/api/admin/members/{id}/delete` | 刪除 |

## 資料庫異動摘要

`members` 目前主要欄位包含：

- 共同欄位：姓名、Email、電話、手機、聯絡地址、密碼、狀態、註冊來源、Google ID。
- 個人會員：身分證號、Line ID、身分證正反面電子檔路徑、發證日期、發證地點、補領換類別、出生日期、性別。
- 商業公司：統一編號、公司名稱、網站、產業類別。

若既有 XAMPP 資料庫尚未更新，可重新匯入 `database/schema.sql`，或手動補上缺少欄位，例如：

```sql
ALTER TABLE members ADD COLUMN line_id VARCHAR(100) DEFAULT NULL COMMENT 'Line ID' AFTER id_number;
```
