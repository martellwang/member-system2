CREATE TABLE IF NOT EXISTS `store_code_prefixes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` BIGINT UNSIGNED NOT NULL,
  `prefix` CHAR(4) NOT NULL COMMENT '會員專用商店代號前置碼',
  `setting_date` DATE NOT NULL COMMENT '設定日期',
  `remark` VARCHAR(400) DEFAULT NULL COMMENT '備註',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_store_code_prefix_prefix` (`prefix`),
  INDEX `idx_store_code_prefix_member` (`member_id`),
  CONSTRAINT `fk_store_code_prefix_member`
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
