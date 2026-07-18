SET @store_code_prefix_remark_sql := IF(
  (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'store_code_prefixes'
      AND COLUMN_NAME = 'remark'
  ) = 0,
  'ALTER TABLE `store_code_prefixes` ADD COLUMN `remark` VARCHAR(400) DEFAULT NULL COMMENT ''備註'' AFTER `setting_date`',
  'SELECT 1'
);

PREPARE store_code_prefix_remark_stmt FROM @store_code_prefix_remark_sql;
EXECUTE store_code_prefix_remark_stmt;
DEALLOCATE PREPARE store_code_prefix_remark_stmt;
