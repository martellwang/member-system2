USE `member_system`;

CREATE TABLE IF NOT EXISTS `member_stores` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('pending','active','rejected','suspended') NOT NULL DEFAULT 'pending',
  `store_type` ENUM('online','physical') NOT NULL DEFAULT 'online',
  `store_name` VARCHAR(150) NOT NULL,
  `store_email` VARCHAR(255) NOT NULL,
  `foreign_statement_name` VARCHAR(150) NOT NULL,
  `store_phone` VARCHAR(30) DEFAULT NULL,
  `store_fax` VARCHAR(30) DEFAULT NULL,
  `store_city` VARCHAR(30) NOT NULL,
  `store_district` VARCHAR(30) NOT NULL,
  `store_address` VARCHAR(255) NOT NULL,
  `contact_name` VARCHAR(100) NOT NULL,
  `contact_phone` VARCHAR(30) DEFAULT NULL,
  `contact_mobile` VARCHAR(30) DEFAULT NULL,
  `industry` VARCHAR(80) NOT NULL,
  `product_type` VARCHAR(80) NOT NULL,
  `delivery_ratios` JSON DEFAULT NULL,
  `guarantee_type` VARCHAR(80) NOT NULL,
  `delivery_period` INT UNSIGNED NOT NULL DEFAULT 0,
  `delivery_unit` VARCHAR(10) NOT NULL DEFAULT '個月',
  `guarantee_note_type` VARCHAR(40) NOT NULL DEFAULT 'not_required',
  `guarantee_note` VARCHAR(100) DEFAULT NULL,
  `average_order_amount` VARCHAR(40) NOT NULL,
  `store_url_type` VARCHAR(30) NOT NULL DEFAULT 'url',
  `store_url` VARCHAR(255) DEFAULT NULL,
  `store_description` TEXT NOT NULL,
  `payment_tools` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_member_stores_member` (`member_id`),
  INDEX `idx_member_stores_status` (`status`),
  CONSTRAINT `fk_member_stores_member`
    FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
