SET @device_name_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'device_suppliers'
    AND COLUMN_NAME = 'device_name'
);

SET @up_memo_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'device_suppliers'
    AND COLUMN_NAME = 'up_memo'
);

SET @rename_up_memo_sql := IF(
  @device_name_exists > 0 AND @up_memo_exists = 0,
  'ALTER TABLE `device_suppliers` CHANGE `device_name` `up_memo` VARCHAR(400) NOT NULL COMMENT "備註"',
  'SELECT "up_memo column already exists or device_name column missing" AS info'
);

PREPARE rename_up_memo_stmt FROM @rename_up_memo_sql;
EXECUTE rename_up_memo_stmt;
DEALLOCATE PREPARE rename_up_memo_stmt;

ALTER TABLE `device_suppliers`
  MODIFY `up_memo` VARCHAR(400) NOT NULL COMMENT '備註';
