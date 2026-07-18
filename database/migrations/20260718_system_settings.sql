USE `member_system`;

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
