SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'members' AND COLUMN_NAME = 'contact_city') = 0,
  'ALTER TABLE `members` ADD COLUMN `contact_city` VARCHAR(30) DEFAULT NULL COMMENT ''聯絡地址縣市'' AFTER `mobile_phone`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'members' AND COLUMN_NAME = 'contact_district') = 0,
  'ALTER TABLE `members` ADD COLUMN `contact_district` VARCHAR(30) DEFAULT NULL COMMENT ''聯絡地址地區'' AFTER `contact_city`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'members' AND COLUMN_NAME = 'contact_address_line') = 0,
  'ALTER TABLE `members` ADD COLUMN `contact_address_line` VARCHAR(255) DEFAULT NULL COMMENT ''聯絡地址詳細地址'' AFTER `contact_district`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `members`
SET `contact_address_line` = `contact_address`
WHERE (`contact_address_line` IS NULL OR `contact_address_line` = '')
  AND `contact_address` IS NOT NULL
  AND `contact_address` <> '';
