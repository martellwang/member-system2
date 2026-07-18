USE `member_system`;

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT '管理人員姓名',
  `email` VARCHAR(255) NOT NULL COMMENT '管理人員帳號',
  `password` VARCHAR(255) NOT NULL COMMENT '登入密碼',
  `role` ENUM('super_admin','staff') NOT NULL DEFAULT 'staff' COMMENT '權限角色',
  `status` ENUM('active','suspended') NOT NULL DEFAULT 'active' COMMENT '帳號狀態',
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_admin_email` (`email`),
  INDEX `idx_admin_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admin_users`
  (`name`,`email`,`password`,`role`,`status`)
SELECT
  '系統管理員',
  'admin@system.com',
  '$2y$10$KHCYAdrKFvf9p8qMzaYm0OWL7Ti09HrBKUtjXjEe/6hRv8VUJiBnS',
  'super_admin',
  'active'
WHERE NOT EXISTS (
  SELECT 1 FROM `admin_users` WHERE `email` = 'admin@system.com'
);
