USE `member_system`;

SET @permission_group_column_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'admin_users'
    AND COLUMN_NAME = 'permission_group'
);

SET @add_permission_group_sql := IF(
  @permission_group_column_exists > 0,
  'SELECT "permission_group column already exists" AS info',
  'ALTER TABLE `admin_users` ADD COLUMN `permission_group` VARCHAR(100) DEFAULT NULL COMMENT "權限群組名稱" AFTER `role`'
);

PREPARE add_permission_group_stmt FROM @add_permission_group_sql;
EXECUTE add_permission_group_stmt;
DEALLOCATE PREPARE add_permission_group_stmt;
