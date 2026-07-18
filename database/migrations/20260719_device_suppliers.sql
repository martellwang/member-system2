CREATE TABLE IF NOT EXISTS `device_suppliers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_name` VARCHAR(150) NOT NULL COMMENT '公司名稱',
  `tax_id` VARCHAR(8) NOT NULL COMMENT '公司統一編號',
  `company_address` VARCHAR(255) NOT NULL COMMENT '公司地址',
  `contact_name` VARCHAR(100) NOT NULL COMMENT '聯絡人',
  `contact_phone` VARCHAR(30) NOT NULL COMMENT '連絡電話',
  `up_memo` VARCHAR(400) NOT NULL COMMENT '備註',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_device_suppliers_tax_id` (`tax_id`),
  INDEX `idx_device_suppliers_company_name` (`company_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
