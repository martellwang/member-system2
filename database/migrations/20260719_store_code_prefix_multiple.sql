SET @add_store_code_prefix_member_index_sql := IF(
  (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'store_code_prefixes'
      AND INDEX_NAME = 'idx_store_code_prefix_member'
  ) = 0,
  'ALTER TABLE `store_code_prefixes` ADD INDEX `idx_store_code_prefix_member` (`member_id`)',
  'SELECT 1'
);

PREPARE add_store_code_prefix_member_index_stmt FROM @add_store_code_prefix_member_index_sql;
EXECUTE add_store_code_prefix_member_index_stmt;
DEALLOCATE PREPARE add_store_code_prefix_member_index_stmt;

SET @drop_store_code_prefix_member_unique_sql := IF(
  (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'store_code_prefixes'
      AND INDEX_NAME = 'uniq_store_code_prefix_member'
  ) > 0,
  'ALTER TABLE `store_code_prefixes` DROP INDEX `uniq_store_code_prefix_member`',
  'SELECT 1'
);

PREPARE drop_store_code_prefix_member_unique_stmt FROM @drop_store_code_prefix_member_unique_sql;
EXECUTE drop_store_code_prefix_member_unique_stmt;
DEALLOCATE PREPARE drop_store_code_prefix_member_unique_stmt;
