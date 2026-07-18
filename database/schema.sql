-- 建立資料庫
CREATE DATABASE IF NOT EXISTS `member_system`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `member_system`;

-- 建立 members 資料表
CREATE TABLE IF NOT EXISTS `members` (
  `id`                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `type`                  ENUM('personal','company') NOT NULL COMMENT '個人用戶 / 商業公司',

  -- 共同欄位
  `name`                  VARCHAR(100) NOT NULL,
  `email`                 VARCHAR(255) NOT NULL UNIQUE,
  `phone`                 VARCHAR(20)  DEFAULT NULL,
  `mobile_phone`          VARCHAR(20)  DEFAULT NULL COMMENT '手機電話',
  `contact_address`       VARCHAR(255) DEFAULT NULL COMMENT '聯絡地址',
  `password`              VARCHAR(255) NOT NULL,
  `status`                ENUM('active','pending','suspended') NOT NULL DEFAULT 'pending',
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
  `birth_date`            DATE         DEFAULT NULL,
  `gender`                ENUM('male','female','other') DEFAULT NULL,

  -- 商業公司欄位
  `tax_id`                VARCHAR(8)   DEFAULT NULL COMMENT '統一編號',
  `company_name`          VARCHAR(200) DEFAULT NULL COMMENT '公司名稱',
  `website`               VARCHAR(255) DEFAULT NULL COMMENT '公司網站網址',
  `industry`              VARCHAR(50)  DEFAULT NULL COMMENT '產業類別',

  -- 驗證欄位
  `email_verified_token`  VARCHAR(128) DEFAULT NULL,
  `email_verified_at`     DATETIME     DEFAULT NULL,

  `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_type`   (`type`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 測試資料
INSERT INTO `members`
  (`type`,`name`,`email`,`phone`,`password`,`status`,`id_number`,`birth_date`,`gender`)
VALUES
  ('personal','王小明','ming@mail.com','0912-111-222','$2y$10$examplehash','active','A123456789','1990-05-15','male'),
  ('personal','林美華','hua@mail.com', '0922-333-444','$2y$10$examplehash','active','B234567890','1988-09-22','female'),
  ('personal','張志豪','hao@mail.com', '0933-555-666','$2y$10$examplehash','pending','C345678901','1995-03-10','male');

INSERT INTO `members`
  (`type`,`name`,`email`,`phone`,`password`,`status`,`tax_id`,`company_name`,`website`,`industry`)
VALUES
  ('company','陳大文','admin@techco.com',  '02-1234-5678','$2y$10$examplehash','active', '12345678','科技股份有限公司','https://techco.com',       'tech'),
  ('company','劉資訊','info@infosoft.com', '02-8765-4321','$2y$10$examplehash','pending','87654321','資訊軟體有限公司','https://infosoft.com.tw',   'tech'),
  ('company','黃貿易','biz@trade.com',     '04-9876-5432','$2y$10$examplehash','active', '11223344','全球貿易企業社', 'https://globaltrade.tw',    'retail');
