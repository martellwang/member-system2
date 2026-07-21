ALTER TABLE `members`
  ADD COLUMN IF NOT EXISTS `second_id_doc_path` VARCHAR(255) DEFAULT NULL COMMENT '第二證件電子檔' AFTER `id_card_back_path`;
