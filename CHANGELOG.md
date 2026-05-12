# Changelog

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
