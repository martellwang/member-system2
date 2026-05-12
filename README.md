# 會員管理系統

## 技術架構

| 層級 | 技術 |
|------|------|
| 前端 | HTML / CSS / JavaScript |
| 後端 | PHP Laravel |
| 資料庫 | MySQL |

## 目錄結構

```
member-system/
├── docs/                          # 設計規格文件
├── frontend/
│   ├── css/style.css              # 樣式
│   ├── js/register.js             # 註冊邏輯
│   ├── js/admin.js                # 後台邏輯
│   └── pages/register.html        # 會員註冊頁
│         pages/admin.html         # 後台管理頁
├── backend/
│   ├── app/Models/Member.php
│   ├── app/Http/Controllers/MemberController.php
│   ├── app/Http/Controllers/AdminController.php
│   ├── database/migrations/       # MySQL migration
│   ├── database/seeders/          # 測試資料
│   └── routes/api.php             # API 路由
├── push.sh                        # 自動 push 腳本
└── CHANGELOG.md
```

## 快速啟動

### 1. 建立資料庫

```sql
CREATE DATABASE member_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. 後端

```bash
cd backend
cp .env.example .env        # 填入 DB_USERNAME / DB_PASSWORD
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed --class=MemberSeeder
php artisan serve           # http://localhost:8000
```

### 3. 前端

直接用瀏覽器開啟：
- `frontend/pages/register.html` — 會員註冊
- `frontend/pages/admin.html`    — 後台管理

## API 端點

| Method | 路由 | 說明 |
|--------|------|------|
| POST | `/api/members/register` | 會員自助註冊 |
| GET | `/api/members/verify/{token}` | Email 驗證 |
| GET | `/api/admin/members` | 會員列表（篩選/搜尋） |
| GET | `/api/admin/stats` | 統計數字 |
| PATCH | `/api/admin/members/{id}/approve` | 審核通過 |
| PATCH | `/api/admin/members/{id}/suspend` | 停用帳號 |
| DELETE | `/api/admin/members/{id}` | 刪除會員 |

## 自動推送

```bash
./push.sh "feat: 說明本次變更"
```
