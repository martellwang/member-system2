USE `member_system`;

ALTER TABLE `admin_users`
  MODIFY `status` ENUM('pending_activation','active','suspended') NOT NULL DEFAULT 'active' COMMENT '帳號狀態';

SET @password_setup_token_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'password_setup_token'
);
SET @add_password_setup_token_sql := IF(
  @password_setup_token_exists > 0,
  'SELECT "password_setup_token column already exists" AS info',
  'ALTER TABLE `admin_users` ADD COLUMN `password_setup_token` VARCHAR(128) DEFAULT NULL COMMENT "設定密碼一次性 Token" AFTER `last_login_at`'
);
PREPARE add_password_setup_token_stmt FROM @add_password_setup_token_sql;
EXECUTE add_password_setup_token_stmt;
DEALLOCATE PREPARE add_password_setup_token_stmt;

SET @password_setup_expires_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'password_setup_expires_at'
);
SET @add_password_setup_expires_sql := IF(
  @password_setup_expires_exists > 0,
  'SELECT "password_setup_expires_at column already exists" AS info',
  'ALTER TABLE `admin_users` ADD COLUMN `password_setup_expires_at` DATETIME DEFAULT NULL COMMENT "設定密碼連結到期時間" AFTER `password_setup_token`'
);
PREPARE add_password_setup_expires_stmt FROM @add_password_setup_expires_sql;
EXECUTE add_password_setup_expires_stmt;
DEALLOCATE PREPARE add_password_setup_expires_stmt;

SET @admin_email_verified_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND COLUMN_NAME = 'email_verified_at'
);
SET @add_admin_email_verified_sql := IF(
  @admin_email_verified_exists > 0,
  'SELECT "email_verified_at column already exists" AS info',
  'ALTER TABLE `admin_users` ADD COLUMN `email_verified_at` DATETIME DEFAULT NULL COMMENT "管理人員信箱驗證時間" AFTER `password_setup_expires_at`'
);
PREPARE add_admin_email_verified_stmt FROM @add_admin_email_verified_sql;
EXECUTE add_admin_email_verified_stmt;
DEALLOCATE PREPARE add_admin_email_verified_stmt;

SET @password_setup_token_index_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_users' AND INDEX_NAME = 'uniq_admin_password_setup_token'
);
SET @add_password_setup_token_index_sql := IF(
  @password_setup_token_index_exists > 0,
  'SELECT "password setup token index already exists" AS info',
  'ALTER TABLE `admin_users` ADD UNIQUE KEY `uniq_admin_password_setup_token` (`password_setup_token`)'
);
PREPARE add_password_setup_token_index_stmt FROM @add_password_setup_token_index_sql;
EXECUTE add_password_setup_token_index_stmt;
DEALLOCATE PREPARE add_password_setup_token_index_stmt;
