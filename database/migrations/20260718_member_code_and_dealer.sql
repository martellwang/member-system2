USE `member_system`;

SET @member_code_column_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'members'
    AND COLUMN_NAME = 'member_code'
);

SET @add_member_code_sql := IF(
  @member_code_column_exists > 0,
  'SELECT "member_code column already exists" AS info',
  'ALTER TABLE `members` ADD COLUMN `member_code` VARCHAR(20) DEFAULT NULL COMMENT "會員編號" AFTER `id`'
);

PREPARE add_member_code_stmt FROM @add_member_code_sql;
EXECUTE add_member_code_stmt;
DEALLOCATE PREPARE add_member_code_stmt;

UPDATE `members`
SET `member_code` = CONCAT('M', LPAD(`id`, 8, '0'))
WHERE `member_code` IS NULL OR `member_code` = '';

SET @member_code_index_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'members'
    AND INDEX_NAME = 'uniq_member_code'
);

SET @add_member_code_index_sql := IF(
  @member_code_index_exists > 0,
  'SELECT "uniq_member_code already exists" AS info',
  'ALTER TABLE `members` ADD UNIQUE KEY `uniq_member_code` (`member_code`)'
);

PREPARE add_member_code_index_stmt FROM @add_member_code_index_sql;
EXECUTE add_member_code_index_stmt;
DEALLOCATE PREPARE add_member_code_index_stmt;

SET @is_dealer_column_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'members'
    AND COLUMN_NAME = 'is_dealer'
);

SET @add_is_dealer_sql := IF(
  @is_dealer_column_exists > 0,
  'SELECT "is_dealer column already exists" AS info',
  'ALTER TABLE `members` ADD COLUMN `is_dealer` TINYINT(1) NOT NULL DEFAULT 0 COMMENT "是否為經銷商" AFTER `industry`'
);

PREPARE add_is_dealer_stmt FROM @add_is_dealer_sql;
EXECUTE add_is_dealer_stmt;
DEALLOCATE PREPARE add_is_dealer_stmt;

UPDATE `members`
SET `is_dealer` = 0
WHERE `type` <> 'company';
