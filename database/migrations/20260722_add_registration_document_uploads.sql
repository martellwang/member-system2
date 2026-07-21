ALTER TABLE `members`
  ADD COLUMN IF NOT EXISTS `bank_book_cover_path` VARCHAR(255) DEFAULT NULL COMMENT '銀行帳戶封面電子檔' AFTER `second_id_doc_path`,
  ADD COLUMN IF NOT EXISTS `company_owner_id_card_front_path` VARCHAR(255) DEFAULT NULL COMMENT '公司負責人身分證正面電子檔' AFTER `industry`,
  ADD COLUMN IF NOT EXISTS `company_owner_id_card_back_path` VARCHAR(255) DEFAULT NULL COMMENT '公司負責人身分證反面電子檔' AFTER `company_owner_id_card_front_path`,
  ADD COLUMN IF NOT EXISTS `company_registration_doc_paths` TEXT DEFAULT NULL COMMENT '公司登記證書電子檔路徑 JSON' AFTER `company_owner_id_card_back_path`;
