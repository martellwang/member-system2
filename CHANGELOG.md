# Changelog

## [1.3.0] - 2026-07-19

### 新增
- 新增入口網站首頁，正式標題為「新零售行銷多元平台 - NewPay」。
- 新增會員登入與會員中心，包含登入狀態、自動登出閒置倒數與會員單一登入控管。
- 會員登入區分個人會員與公司法人，登入唯一辨識採 Email 加身分證號或統一編號。
- 非 Google 會員註冊改為信箱邀請制，註冊時不輸入密碼，完成信箱驗證與設定密碼後才可登入。
- 未審核但已驗證信箱的會員可登入會員中心，只能查看基本資料並顯示審核中。
- 新增管理人員邀請制啟用流程，系統寄送設定密碼連結，完成後正式啟用。
- 新增內部管理人員列表、搜尋、單獨編輯、登入紀錄與刪除功能。
- 新增資訊安全左側選單，支援後台閒置自動登出時間、可登入 IP 白名單與標註原因。
- 新增群組管理，管理人員權限下拉選單改由群組管理產生。
- 新增後台管理人員與會員單一登入防重複登入機制。
- 新增經銷商管理，僅顯示有經銷商旗標的會員。
- 新增會員商店資料表與會員中心商店功能：新增商店、商店清單、商店資料頁。
- 後台會員列表新增全文搜尋清除按鈕、搜尋後序號與商店數欄位。
- 後台會員編輯頁新增商店管理區，商店數大於 0 可從列表點入。
- 後台會員編輯頁新增重新發送信箱驗證功能。
- 新增設備管理入口與五個橫幅選項，作為後續設備功能開發位置。

### 調整
- 後台改名為「內部管理後台」。
- 登入前隱藏後台 login 頁上方功能選項。
- 法人會員統一編號與法人資料除系統管理員外不可修改。
- 商店管理於後台會員編輯頁改為顯示商店基本資料，不提供直接編輯。
- 前台會員中心改為左右兩區版型，左側建立後續功能選單。
- 會員系統品牌文字調整為「新零售行銷多元平台」。

### 部署
- 已推送 GitHub `main`，部署 commit：`8809b4c`。
- 已部署正式站 `https://www.newpay.com.tw/member/`。
- 正式站 `newpay_member` 資料庫已套用 `member_stores` 與會員 session 欄位。

### 驗證
- 已執行全專案 PHP 語法檢查。
- 已執行前端 JS `node --check`。
- 已確認正式站首頁 GET 回應 `200`。

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
