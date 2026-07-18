-- 建立資料庫
CREATE DATABASE IF NOT EXISTS `member_system`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `member_system`;

-- 建立 members 資料表
CREATE TABLE IF NOT EXISTS `members` (
  `id`                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_code`           VARCHAR(20)  DEFAULT NULL COMMENT '會員編號',
  `type`                  ENUM('personal','company') NOT NULL COMMENT '個人用戶 / 商業公司',

  -- 共同欄位
  `name`                  VARCHAR(100) NOT NULL,
  `email`                 VARCHAR(255) NOT NULL,
  `phone_area_code`       VARCHAR(5)   DEFAULT NULL COMMENT '市話區域號碼',
  `phone`                 VARCHAR(20)  DEFAULT NULL,
  `mobile_phone`          VARCHAR(20)  DEFAULT NULL COMMENT '手機電話',
  `contact_city`          VARCHAR(30)  DEFAULT NULL COMMENT '聯絡地址縣市',
  `contact_district`      VARCHAR(30)  DEFAULT NULL COMMENT '聯絡地址地區',
  `contact_address_line`  VARCHAR(255) DEFAULT NULL COMMENT '聯絡地址詳細地址',
  `contact_address`       VARCHAR(255) DEFAULT NULL COMMENT '聯絡地址',
  `password`              VARCHAR(255) NOT NULL,
  `status`                ENUM('email_unverified','active','pending','suspended') NOT NULL DEFAULT 'pending',
  `auth_provider`         VARCHAR(20)  NOT NULL DEFAULT 'local',
  `google_id`             VARCHAR(64)  DEFAULT NULL,

  -- 個人用戶欄位
  `id_number`             VARCHAR(10)  DEFAULT NULL COMMENT '身分證號',
  `line_id`               VARCHAR(100) DEFAULT NULL COMMENT 'Line ID',
  `id_card_front_path`    VARCHAR(255) DEFAULT NULL COMMENT '身分證正面電子檔',
  `id_card_back_path`     VARCHAR(255) DEFAULT NULL COMMENT '身分證反面電子檔',
  `id_issue_date`         DATE         DEFAULT NULL COMMENT '身分證發證日期',
  `id_issue_place`        VARCHAR(50)  DEFAULT NULL COMMENT '身分證發證地點',
  `id_issue_type`         ENUM('first','replace','renew') DEFAULT NULL COMMENT '初發 / 補發 / 換發',
  `birth_date`            DATE         DEFAULT NULL COMMENT '出生日期（個人會員必填）',
  `gender`                ENUM('male','female','other') DEFAULT NULL,

  -- 商業公司欄位
  `tax_id`                VARCHAR(8)   DEFAULT NULL COMMENT '統一編號',
  `company_name`          VARCHAR(200) DEFAULT NULL COMMENT '公司名稱',
  `website`               VARCHAR(255) DEFAULT NULL COMMENT '公司網站網址',
  `industry`              VARCHAR(50)  DEFAULT NULL COMMENT '產業類別',
  `is_dealer`             TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '是否為經銷商',

  -- 驗證欄位
  `email_verified_token`  VARCHAR(128) DEFAULT NULL,
  `email_verified_at`     DATETIME     DEFAULT NULL,
  `active_session_id` VARCHAR(128) DEFAULT NULL COMMENT '目前有效會員 Session ID',
  `active_session_last_seen_at` DATETIME DEFAULT NULL COMMENT '目前會員 Session 最近活動時間',
  `duplicate_login_attempt_at` DATETIME DEFAULT NULL COMMENT '重複登入嘗試時間',
  `duplicate_login_attempt_ip` VARCHAR(45) DEFAULT NULL COMMENT '重複登入嘗試 IP',

  `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_type`   (`type`),
  INDEX `idx_status` (`status`),
  INDEX `idx_member_active_session` (`active_session_id`),
  UNIQUE KEY `uniq_member_code` (`member_code`),
  UNIQUE KEY `uniq_personal_email_id_number` (`type`, `email`, `id_number`),
  UNIQUE KEY `uniq_company_email_tax_id` (`type`, `email`, `tax_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 建立後台管理人員資料表
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(100) NOT NULL COMMENT '管理人員姓名',
  `email`        VARCHAR(255) NOT NULL COMMENT '管理人員帳號',
  `password`     VARCHAR(255) NOT NULL COMMENT '登入密碼',
  `role`         ENUM('super_admin','staff') NOT NULL DEFAULT 'staff' COMMENT '權限角色',
  `permission_group` VARCHAR(100) DEFAULT NULL COMMENT '權限群組名稱',
  `status`       ENUM('pending_activation','active','suspended') NOT NULL DEFAULT 'active' COMMENT '帳號狀態',
  `allowed_ips`  TEXT DEFAULT NULL COMMENT '允許登入 IP，一行一筆，可用 CIDR',
  `last_login_at` DATETIME DEFAULT NULL,
  `password_setup_token` VARCHAR(128) DEFAULT NULL COMMENT '設定密碼一次性 Token',
  `password_setup_expires_at` DATETIME DEFAULT NULL COMMENT '設定密碼連結到期時間',
  `email_verified_at` DATETIME DEFAULT NULL COMMENT '管理人員信箱驗證時間',
  `active_session_id` VARCHAR(128) DEFAULT NULL COMMENT '目前有效後台 Session ID',
  `active_session_last_seen_at` DATETIME DEFAULT NULL COMMENT '目前 Session 最近活動時間',
  `duplicate_login_attempt_at` DATETIME DEFAULT NULL COMMENT '重複登入嘗試時間',
  `duplicate_login_attempt_ip` VARCHAR(45) DEFAULT NULL COMMENT '重複登入嘗試 IP',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_admin_email` (`email`),
  UNIQUE KEY `uniq_admin_password_setup_token` (`password_setup_token`),
  INDEX `idx_admin_status` (`status`),
  INDEX `idx_admin_active_session` (`active_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `admin_users`
  (`name`,`email`,`password`,`role`,`status`)
VALUES
  ('系統管理員','admin@system.com','$2y$10$KHCYAdrKFvf9p8qMzaYm0OWL7Ti09HrBKUtjXjEe/6hRv8VUJiBnS','super_admin','active');

-- 建立系統設定資料表
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key_name` VARCHAR(100) NOT NULL COMMENT '設定鍵',
  `value` TEXT DEFAULT NULL COMMENT '設定值',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_setting_key` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_settings` (`key_name`, `value`)
VALUES ('admin_session_timeout_seconds', '1800')
ON DUPLICATE KEY UPDATE `key_name` = VALUES(`key_name`);

INSERT INTO `system_settings` (`key_name`, `value`)
VALUES ('admin_allowed_ips', '127.0.0.1\n::1')
ON DUPLICATE KEY UPDATE `key_name` = VALUES(`key_name`);

INSERT INTO `system_settings` (`key_name`, `value`)
VALUES ('admin_permission_groups', '[]')
ON DUPLICATE KEY UPDATE `key_name` = VALUES(`key_name`);

-- 建立後台管理人員登入紀錄
CREATE TABLE IF NOT EXISTS `admin_login_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_user_id` BIGINT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL COMMENT '登入 IP',
  `user_agent` VARCHAR(255) DEFAULT NULL COMMENT '瀏覽器資訊',
  `login_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_at` DATETIME DEFAULT NULL COMMENT '登出時間',
  `duration_seconds` INT UNSIGNED DEFAULT NULL COMMENT '使用秒數',
  INDEX `idx_admin_login_user` (`admin_user_id`),
  INDEX `idx_admin_login_at` (`login_at`),
  CONSTRAINT `fk_admin_login_logs_user`
    FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 建立會員商店申請資料表
CREATE TABLE IF NOT EXISTS `member_stores` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('pending','active','rejected','suspended') NOT NULL DEFAULT 'pending',
  `store_type` ENUM('online','physical') NOT NULL DEFAULT 'online',
  `store_name` VARCHAR(150) NOT NULL,
  `store_email` VARCHAR(255) NOT NULL,
  `foreign_statement_name` VARCHAR(150) NOT NULL,
  `store_phone` VARCHAR(30) DEFAULT NULL,
  `store_fax` VARCHAR(30) DEFAULT NULL,
  `store_city` VARCHAR(30) NOT NULL,
  `store_district` VARCHAR(30) NOT NULL,
  `store_address` VARCHAR(255) NOT NULL,
  `contact_name` VARCHAR(100) NOT NULL,
  `contact_phone` VARCHAR(30) DEFAULT NULL,
  `contact_mobile` VARCHAR(30) DEFAULT NULL,
  `industry` VARCHAR(80) NOT NULL,
  `product_type` VARCHAR(80) NOT NULL,
  `delivery_ratios` JSON DEFAULT NULL,
  `guarantee_type` VARCHAR(80) NOT NULL,
  `delivery_period` INT UNSIGNED NOT NULL DEFAULT 0,
  `delivery_unit` VARCHAR(10) NOT NULL DEFAULT '個月',
  `guarantee_note_type` VARCHAR(40) NOT NULL DEFAULT 'not_required',
  `guarantee_note` VARCHAR(100) DEFAULT NULL,
  `average_order_amount` VARCHAR(40) NOT NULL,
  `store_url_type` VARCHAR(30) NOT NULL DEFAULT 'url',
  `store_url` VARCHAR(255) DEFAULT NULL,
  `store_description` TEXT NOT NULL,
  `payment_tools` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_member_stores_member` (`member_id`),
  INDEX `idx_member_stores_status` (`status`),
  CONSTRAINT `fk_member_stores_member`
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `device_suppliers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_name` VARCHAR(150) NOT NULL COMMENT '公司名稱',
  `tax_id` VARCHAR(8) NOT NULL COMMENT '公司統一編號',
  `company_address` VARCHAR(255) NOT NULL COMMENT '公司地址',
  `contact_name` VARCHAR(100) NOT NULL COMMENT '聯絡人',
  `contact_phone` VARCHAR(30) NOT NULL COMMENT '連絡電話',
  `up_memo` VARCHAR(400) NOT NULL COMMENT '備註',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_device_suppliers_tax_id` (`tax_id`),
  INDEX `idx_device_suppliers_company_name` (`company_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `store_code_prefixes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` BIGINT UNSIGNED NOT NULL,
  `prefix` CHAR(4) NOT NULL COMMENT '會員專用商店代號前置碼',
  `setting_date` DATE NOT NULL COMMENT '設定日期',
  `remark` VARCHAR(400) DEFAULT NULL COMMENT '備註',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_store_code_prefix_prefix` (`prefix`),
  INDEX `idx_store_code_prefix_member` (`member_id`),
  CONSTRAINT `fk_store_code_prefix_member`
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 測試資料
INSERT INTO `members`
  (`member_code`,`type`,`name`,`email`,`phone`,`password`,`status`,`id_number`,`birth_date`,`gender`)
VALUES
  ('M00000001','personal','王小明','ming@mail.com','0912-111-222','$2y$10$examplehash','active','A123456789','1990-05-15','male'),
  ('M00000002','personal','林美華','hua@mail.com', '0922-333-444','$2y$10$examplehash','active','B234567890','1988-09-22','female'),
  ('M00000003','personal','張志豪','hao@mail.com', '0933-555-666','$2y$10$examplehash','pending','C345678901','1995-03-10','male');

INSERT INTO `members`
  (`member_code`,`type`,`name`,`email`,`phone`,`password`,`status`,`tax_id`,`company_name`,`website`,`industry`,`is_dealer`)
VALUES
  ('M00000004','company','陳大文','admin@techco.com',  '02-1234-5678','$2y$10$examplehash','active', '12345678','科技股份有限公司','https://techco.com',       'tech',0),
  ('M00000005','company','劉資訊','info@infosoft.com', '02-8765-4321','$2y$10$examplehash','pending','87654321','資訊軟體有限公司','https://infosoft.com.tw',   'tech',1),
  ('M00000006','company','黃貿易','biz@trade.com',     '04-9876-5432','$2y$10$examplehash','active', '11223344','全球貿易企業社', 'https://globaltrade.tw',    'retail',0);
