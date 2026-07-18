USE `member_system`;

ALTER TABLE `admin_login_logs`
  ADD COLUMN IF NOT EXISTS `logout_at` DATETIME DEFAULT NULL COMMENT '登出時間' AFTER `login_at`,
  ADD COLUMN IF NOT EXISTS `duration_seconds` INT UNSIGNED DEFAULT NULL COMMENT '使用秒數' AFTER `logout_at`;
