SET @up_memo_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'device_suppliers'
    AND COLUMN_NAME = 'up_memo'
);

SET @device_name_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'device_suppliers'
    AND COLUMN_NAME = 'device_name'
);

SET @device_supplier_note_sql := IF(
  @up_memo_exists > 0,
  'ALTER TABLE `device_suppliers` MODIFY `up_memo` VARCHAR(400) NOT NULL COMMENT "備註"',
  IF(
    @device_name_exists > 0,
    'ALTER TABLE `device_suppliers` MODIFY `device_name` VARCHAR(400) NOT NULL COMMENT "備註"',
    'SELECT "device supplier memo column missing" AS info'
  )
);

PREPARE device_supplier_note_stmt FROM @device_supplier_note_sql;
EXECUTE device_supplier_note_stmt;
DEALLOCATE PREPARE device_supplier_note_stmt;
