USE `member_system`;

UPDATE `admin_login_logs`
SET
  `login_at` = DATE_ADD(`login_at`, INTERVAL 6 HOUR),
  `duration_seconds` = CASE
    WHEN `logout_at` IS NULL THEN `duration_seconds`
    ELSE GREATEST(0, TIMESTAMPDIFF(SECOND, DATE_ADD(`login_at`, INTERVAL 6 HOUR), `logout_at`))
  END
WHERE `login_at` < DATE_SUB(NOW(), INTERVAL 2 HOUR)
  AND NOT EXISTS (
    SELECT 1 FROM `system_settings`
    WHERE `key_name` = 'admin_timezone_fix_20260718'
  );

UPDATE `admin_users`
SET
  `last_login_at` = CASE
    WHEN `last_login_at` IS NULL THEN NULL
    WHEN `last_login_at` < DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN DATE_ADD(`last_login_at`, INTERVAL 6 HOUR)
    ELSE `last_login_at`
  END,
  `active_session_last_seen_at` = CASE
    WHEN `active_session_last_seen_at` IS NULL THEN NULL
    WHEN `active_session_last_seen_at` < DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN DATE_ADD(`active_session_last_seen_at`, INTERVAL 6 HOUR)
    ELSE `active_session_last_seen_at`
  END,
  `duplicate_login_attempt_at` = CASE
    WHEN `duplicate_login_attempt_at` IS NULL THEN NULL
    WHEN `duplicate_login_attempt_at` < DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN DATE_ADD(`duplicate_login_attempt_at`, INTERVAL 6 HOUR)
    ELSE `duplicate_login_attempt_at`
  END
WHERE NOT EXISTS (
  SELECT 1 FROM `system_settings`
  WHERE `key_name` = 'admin_timezone_fix_20260718'
);

INSERT INTO `system_settings` (`key_name`, `value`)
VALUES ('admin_timezone_fix_20260718', 'applied')
ON DUPLICATE KEY UPDATE `key_name` = VALUES(`key_name`);
