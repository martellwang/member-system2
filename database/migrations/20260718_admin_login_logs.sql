USE `member_system`;

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
