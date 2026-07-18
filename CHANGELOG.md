# Changelog

## [1.2.0] - 2026-07-18

### 新增
- 支援 XAMPP 子目錄部署，前台與後台可使用 `/member-system2/register`、`/member-system2/admin`。
- 新增 `.env` 載入與 `.env.example` 設定範本。
- 新增後台登入、登出與會員單獨編輯頁。
- 新增會員審核、停用、刪除與更新 API。
- 個人會員新增手機電話、聯絡地址、Line ID、身分證正反面電子檔、身分證發證日期、發證地點、補領換類別。
- 新增 Google 帳號註冊流程。
- 新增民國年月曆選擇器，使用日曆小圖示開啟，年份預設顯示今年往前 80 年。
- 身分證發證地點改為選單，包含現行縣市與五都合併前舊制縣名。
- 新增台灣身分證字號檢核碼驗證，前端與後端皆套用。
- 後台列表新增既有身分證號重複提示。
- 後台身分證電子檔改為頁內小視窗預覽。
- 後台 Line ID 可點擊開啟電腦 LINE 加好友連結。

### 調整
- 後台管理頁主內容寬度改為瀏覽器寬度 95%。
- 修正後台表格列分隔線斷開的視覺問題。
- 修正後台 approve / suspend / delete 前後端 HTTP method 不一致問題。
- 會員編輯從 alert/modal 改為單獨頁面。

### 驗證
- 已執行 PHP 語法檢查。
- 已執行 `node --check` 檢查前端 JS。
- 已同步至 XAMPP 目錄 `C:\xampp\htdocs\member-system2`。

## [1.1.0] - 2026-05-12

### 新增
- **前端**
  - `frontend/css/style.css` — 完整 UI 樣式（Navbar、表單、後台表格）
  - `frontend/pages/register.html` — 會員自助註冊頁（個人/公司切換）
  - `frontend/pages/admin.html` — 後台管理頁（統計、列表、篩選、搜尋）
  - `frontend/js/register.js` — 前端驗證邏輯 & API 串接
  - `frontend/js/admin.js` — 後台資料載入、篩選、審核功能
- **後端（Laravel）**
  - `backend/app/Models/Member.php` — 會員 Model
  - `backend/app/Http/Controllers/MemberController.php` — 註冊、Email 驗證
  - `backend/app/Http/Controllers/AdminController.php` — 後台 CRUD
  - `backend/database/migrations/..._create_members_table.php` — MySQL migration
  - `backend/database/seeders/MemberSeeder.php` — 測試資料
  - `backend/routes/api.php` — API 路由定義
  - `backend/.env.example` — 環境變數範本
- 更新 README（完整啟動說明 & API 文件）

## [1.0.0] - 2026-05-11

### 新增
- 初始化專案架構
- 完成會員管理系統設計規格文件（v1.0）
