USE `member_system`;

SET @email_index_name := (
  SELECT s.INDEX_NAME
  FROM INFORMATION_SCHEMA.STATISTICS s
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'members'
    AND COLUMN_NAME = 'email'
    AND NON_UNIQUE = 0
    AND (
      SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.STATISTICS si
      WHERE si.TABLE_SCHEMA = s.TABLE_SCHEMA
        AND si.TABLE_NAME = s.TABLE_NAME
        AND si.INDEX_NAME = s.INDEX_NAME
    ) = 1
  LIMIT 1
);

SET @drop_email_unique_sql := IF(
  @email_index_name IS NULL,
  'SELECT "email unique index not found" AS info',
  CONCAT('ALTER TABLE `members` DROP INDEX `', REPLACE(@email_index_name, '`', '``'), '`')
);

PREPARE drop_email_unique_stmt FROM @drop_email_unique_sql;
EXECUTE drop_email_unique_stmt;
DEALLOCATE PREPARE drop_email_unique_stmt;

SET @personal_index_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'members'
    AND INDEX_NAME = 'uniq_personal_email_id_number'
);

SET @create_personal_index_sql := IF(
  @personal_index_exists > 0,
  'SELECT "uniq_personal_email_id_number already exists" AS info',
  'ALTER TABLE `members` ADD UNIQUE KEY `uniq_personal_email_id_number` (`type`, `email`, `id_number`)'
);

PREPARE create_personal_index_stmt FROM @create_personal_index_sql;
EXECUTE create_personal_index_stmt;
DEALLOCATE PREPARE create_personal_index_stmt;

SET @company_index_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'members'
    AND INDEX_NAME = 'uniq_company_email_tax_id'
);

SET @create_company_index_sql := IF(
  @company_index_exists > 0,
  'SELECT "uniq_company_email_tax_id already exists" AS info',
  'ALTER TABLE `members` ADD UNIQUE KEY `uniq_company_email_tax_id` (`type`, `email`, `tax_id`)'
);

PREPARE create_company_index_stmt FROM @create_company_index_sql;
EXECUTE create_company_index_stmt;
DEALLOCATE PREPARE create_company_index_stmt;
