USE `member_system`;

ALTER TABLE `admin_users`
  ADD COLUMN IF NOT EXISTS `active_session_id` VARCHAR(128) DEFAULT NULL COMMENT '目前有效後台 Session ID' AFTER `last_login_at`,
  ADD COLUMN IF NOT EXISTS `active_session_last_seen_at` DATETIME DEFAULT NULL COMMENT '目前 Session 最近活動時間' AFTER `active_session_id`,
  ADD COLUMN IF NOT EXISTS `duplicate_login_attempt_at` DATETIME DEFAULT NULL COMMENT '重複登入嘗試時間' AFTER `active_session_last_seen_at`,
  ADD COLUMN IF NOT EXISTS `duplicate_login_attempt_ip` VARCHAR(45) DEFAULT NULL COMMENT '重複登入嘗試 IP' AFTER `duplicate_login_attempt_at`;

CREATE INDEX IF NOT EXISTS `idx_admin_active_session` ON `admin_users` (`active_session_id`);
