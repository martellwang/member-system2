# 會員管理系統

## 技術架構

| 層級 | 技術 |
|------|------|
| 前端 | HTML / CSS / JavaScript |
| 後端 | 原生 PHP（MVC 架構） |
| 資料庫 | MySQL |

## 目錄結構

```
member-system/
├── public/                     # Web Root（Apache/Nginx 指向此處）
│   ├── index.php               # Front Controller（所有請求入口）
│   ├── .htaccess               # URL Rewrite
│   └── assets/
│       ├── css/style.css
│       └── js/register.js / admin.js
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
│       └── admin/index.php
├── config/
│   ├── app.php                 # 全域常數
│   ├── database.php            # DB 設定
│   └── routes.php              # 路由定義
├── database/
│   └── schema.sql              # 建表 & 測試資料
├── .env.example
└── push.sh
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
| GET | `/admin` | 後台管理頁 |
| GET | `/api/admin/members` | 會員列表 |
| GET | `/api/admin/stats` | 統計 |
| POST | `/api/admin/members/{id}/approve` | 審核通過 |
| POST | `/api/admin/members/{id}/suspend` | 停用 |
| POST | `/api/admin/members/{id}/delete` | 刪除 |
