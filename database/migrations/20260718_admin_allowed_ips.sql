USE `member_system`;

SET @allowed_ips_column_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'admin_users'
    AND COLUMN_NAME = 'allowed_ips'
);

SET @add_allowed_ips_sql := IF(
  @allowed_ips_column_exists > 0,
  'SELECT "allowed_ips column already exists" AS info',
  'ALTER TABLE `admin_users` ADD COLUMN `allowed_ips` TEXT DEFAULT NULL COMMENT "允許登入 IP，一行一筆，可用 CIDR" AFTER `status`'
);

PREPARE add_allowed_ips_stmt FROM @add_allowed_ips_sql;
EXECUTE add_allowed_ips_stmt;
DEALLOCATE PREPARE add_allowed_ips_stmt;
